<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Person;
use App\Support\PersonPhone;
use App\Support\PersonPhoneGuard;
use App\Support\AdminRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PersonController extends Controller
{
    public function index(Request $request): View
    {
        $people = $this->filteredPeople($request)->withCount(['assessments', 'mediaConsents'])->withExists(['assessments as has_legacy_assessment' => fn ($query) => $query->whereHas('legacySource')])
            ->latest()->paginate(25)->withQueryString();

        return view('admin.people.index', ['people' => $people, 'newPerson' => new Person]);
    }

    public function export(Request $request): StreamedResponse
    {
        $people = $this->filteredPeople($request)->withCount('assessments')->withExists(['assessments as has_legacy_assessment' => fn ($query) => $query->whereHas('legacySource')])->latest();

        return response()->streamDownload(function () use ($people): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Person number', 'Name', 'Phone', 'Assessments', 'Registered date'], ',', '"', '');
            foreach ($people->cursor() as $person) {
                $name = preg_match('/^[\s\x00-\x1f]*[=+\-@]/u', $person->full_name) ? "'".$person->full_name : $person->full_name;
                $phone = filled($person->phone) ? "'".$person->phone : '';
                fputcsv($output, [$person->id, $name, $phone, $person->assessments_count, $person->has_legacy_assessment ? 'Historical — date unknown' : $person->created_at?->format('Y-m-d')], ',', '"', '');
            }
            fclose($output);
        }, 'people-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredPeople(Request $request): Builder
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => array_filter(['nullable', 'date_format:Y-m-d', $request->filled('date_from') ? 'after_or_equal:date_from' : null]),
        ]);

        return Person::query()
            ->when(filled($filters['date_from'] ?? null) || filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDoesntHave('assessments.legacySource'))
            ->when(filled($filters['q'] ?? null), function (Builder $query) use ($filters) {
                $term = '%'.$filters['q'].'%';
                $query->where(function (Builder $query) use ($term, $filters) {
                    $query->where('full_name', 'like', $term)->orWhere('phone', 'like', $term);
                    if (ctype_digit($filters['q']) && strlen($filters['q']) < 19) $query->orWhere('id', $filters['q']);
                });
            })
            ->when(filled($filters['date_from'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['date_to']));
    }

    public function create(): View
    {
        return view('admin.people.form', ['person' => new Person]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9() .\/-]+$/', function ($attribute, $value, $fail) {
                $length = strlen(preg_replace('/\D+/', '', $value));
                if ($length < 7 || $length > 15) $fail('Enter a phone number with 7 to 15 digits.');
            }],
            'nin' => ['nullable', 'string', 'max:30'],
        ]);
        return PersonPhoneGuard::synchronized($data['phone'] ?? null, function () use ($request, $data) {
            if ($warning = $this->checkDuplicatePhone($request, $data)) return $warning;
            $person = Person::create($data + ['created_by' => $request->user()->id]);
            return AdminRedirect::firstAllowed(['admin.people.show' => [$person], 'admin.people.index' => []])->with('status', 'Person registered. Add an assessment next.');
        });
    }

    public function show(Person $person): View
    {
        if (auth()->user()->can('route-admin-assessments-index')) {
            $person->load(['assessments' => fn ($query) => $query->with('distributionEvent')->latest()]);
        }

        return view('admin.people.show', compact('person'));
    }

    public function edit(Person $person): View
    {
        return view('admin.people.form', compact('person'));
    }

    public function update(Request $request, Person $person): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9() .\/-]+$/', function ($attribute, $value, $fail) {
                $length = strlen(preg_replace('/\D+/', '', $value));
                if ($length < 7 || $length > 15) $fail('Enter a phone number with 7 to 15 digits.');
            }],
            'nin' => ['nullable', 'string', 'max:30'],
        ]);
        return PersonPhoneGuard::synchronized($data['phone'] ?? null, function () use ($request, $data, $person) {
            if (PersonPhone::normalize($data['phone'] ?? null) !== PersonPhone::normalize($person->phone)) {
                if ($warning = $this->checkDuplicatePhone($request, $data, $person)) return $warning;
            }
            if (blank($data['nin'] ?? null)) unset($data['nin']);
            $person->update($data);
            return AdminRedirect::firstAllowed(['admin.people.show' => [$person], 'admin.people.index' => []])->with('status', 'Person updated.');
        });
    }

    private function checkDuplicatePhone(Request $request, array $data, ?Person $person = null): ?RedirectResponse
    {
        $number = PersonPhone::normalize($data['phone'] ?? null);
        if ($number === null) return null;

        $matches = Person::withTrashed()->whereNotNull('phone')
            ->when($person, fn (Builder $query) => $query->where('id', '<>', $person->id))
            ->orderBy('id')->get(['id', 'full_name', 'phone', 'deleted_at'])
            ->filter(fn (Person $candidate) => PersonPhone::normalize($candidate->phone) === $number)->values();
        if ($matches->isEmpty()) return null;

        $token = hash_hmac('sha256', json_encode([
            $request->user()->id, $person?->id, trim($data['full_name']), $number, $matches->modelKeys(),
        ], JSON_THROW_ON_ERROR), config('app.key'));

        if ($request->boolean('confirm_duplicate') && hash_equals($token, (string) $request->input('duplicate_token'))) return null;

        return back()->withInput($request->except(['nin', '_token', 'confirm_duplicate', 'duplicate_token']))
            ->with('duplicate_review', [
                'token' => $token,
                'matches' => $matches->take(10)->map(fn (Person $candidate) => [
                    'id' => $candidate->id, 'name' => $candidate->full_name, 'archived' => $candidate->trashed(),
                ])->all(),
                'count' => $matches->count(),
                'nin_cleared' => filled($data['nin'] ?? null),
            ])->withErrors(['phone' => 'This phone number is already recorded. Review the matching people before continuing.']);
    }

    public function destroy(Person $person): RedirectResponse
    {
        if ($person->assessments()->exists() || $person->mediaConsents()->exists() || $person->therapyChild()->exists()) {
            return redirect()->route('admin.people.index')->withErrors([
                'person' => 'This person cannot be deleted because they have assessments, consent or therapy records.',
            ]);
        }

        $person->delete();

        return redirect()->route('admin.people.index')->with('status', 'Person removed from the registry.');
    }
}
