<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistoricalTeamProfile;
use App\Models\LegacySourceRecord;
use App\Models\User;
use App\Support\UserSessionRevoker;
use App\Support\PendingRegistrationEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class HistoricalTeamController extends Controller
{
    public function index(Request $request): View
    {
        $archived = $request->boolean('archived');
        $query = LegacySourceRecord::with('teamProfile.user')->where('source_database', 'fathersheart')->whereIn('source_table', ['staff', 'volunteer', 'employee']);
        if ($archived) $query->whereHas('teamProfile', fn ($q) => $q->whereNotNull('archived_at'));
        else $query->whereDoesntHave('teamProfile', fn ($q) => $q->whereNotNull('archived_at'));
        $records = $query->orderBy('source_table')->orderBy('id')->paginate(25)->withQueryString()->through(function ($record) {
            $defaults = $this->defaults($record);
            $profile = $record->teamProfile;
            return ['id' => $record->id, 'source_table' => $record->source_table, 'source_id' => $record->source_row_id,
                'name' => $profile?->name ?? $defaults['name'], 'username' => $profile ? $profile->legacy_username : $defaults['legacy_username'],
                'phone' => $profile ? $profile->phone : $defaults['phone'], 'user' => $profile?->user, 'archived' => (bool) $profile?->archived_at];
        });
        $roles = Role::where('guard_name', 'web')->whereNotIn('name', ['Super Administrator', 'super-admin'])->orderBy('name')->get();
        $linkableUsers = $request->user()->isSuperAdmin() ? User::whereKeyNot($request->user()->id)
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['Super Administrator', 'super-admin']))
            ->whereNotIn('id', HistoricalTeamProfile::whereNotNull('user_id')->select('user_id'))->orderBy('name')->get(['id', 'name', 'email']) : collect();
        return view('admin.historical.team', compact('records', 'roles', 'archived', 'linkableUsers'));
    }

    public function update(Request $request, LegacySourceRecord $record): RedirectResponse
    {
        $this->superAdmin();
        $this->assertTeam($record);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'legacy_username' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:40']]);
        DB::transaction(function () use ($record, $data) {
            $original = LegacySourceRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $profile = $this->profile($original);
            if ($profile->archived_at) throw ValidationException::withMessages(['record' => 'Restore this historical record before editing it.']);
            $profile->fill($data)->save();
            if ($profile->user) $profile->user->update(['name' => $data['name']]);
        });
        return redirect()->route('admin.historical.team.index')->with('status', 'Team details updated. The encrypted historical original remains unchanged.');
    }

    public function createAccount(Request $request, LegacySourceRecord $record): RedirectResponse
    {
        $this->superAdmin();
        $this->assertTeam($record);
        $allowedRoles = Role::where('guard_name', 'web')->whereNotIn('name', ['Super Administrator', 'super-admin'])->pluck('id')->all();
        if (is_string($request->input('email'))) $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email'), PendingRegistrationEmail::rule()],
            'password' => ['required', 'string', 'min:12', 'confirmed'], 'roles' => ['nullable', 'array'], 'roles.*' => ['integer', 'distinct', Rule::in($allowedRoles)],
        ]);
        DB::transaction(function () use ($record, $data) {
            $original = LegacySourceRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $profile = $this->profile($original);
            if ($profile->archived_at) throw ValidationException::withMessages(['record' => 'Restore this historical record before creating an account.']);
            if ($profile->user_id) throw ValidationException::withMessages(['record' => 'This historical record already has an account.']);
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password']]);
            $user->syncRoles(Role::where('guard_name', 'web')->whereNotIn('name', ['Super Administrator', 'super-admin'])->whereIn('id', $data['roles'] ?? [])->get());
            $profile->update(['name' => $data['name'], 'user_id' => $user->id]);
        });
        return redirect()->route('admin.historical.team.index')->with('status', 'Staff account created with a new password. Confirm its roles before the person signs in.');
    }

    public function resetPassword(Request $request, LegacySourceRecord $record): RedirectResponse
    {
        $this->superAdmin();
        $this->assertTeam($record);
        $data = $request->validate(['password' => ['required', 'string', 'min:12', 'confirmed']]);
        DB::transaction(function () use ($record, $data, $request) {
            $profile = HistoricalTeamProfile::where('legacy_source_record_id', $record->id)->lockForUpdate()->firstOrFail();
            if ($profile->archived_at) throw ValidationException::withMessages(['record' => 'Restore this historical record first.']);
            $user = User::whereKey($profile->user_id)->lockForUpdate()->firstOrFail();
            abort_if($user->isSuperAdmin() || $user->is($request->user()), 403, 'Use the dedicated user account controls for super administrators.');
            $user->forceFill(['password' => $data['password']])->save();
            UserSessionRevoker::revoke($user);
        });
        return redirect()->route('admin.historical.team.index')->with('status', 'The password was changed and active sessions and API tokens were revoked.');
    }

    public function linkAccount(Request $request, LegacySourceRecord $record): RedirectResponse
    {
        $this->superAdmin();
        $this->assertTeam($record);
        $data = $request->validate(['user_id' => ['required', 'integer', 'exists:users,id'], 'confirm_identity' => ['required', 'accepted']]);
        DB::transaction(function () use ($record, $data, $request) {
            $original = LegacySourceRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $user = User::whereKey($data['user_id'])->lockForUpdate()->firstOrFail();
            abort_if($user->isSuperAdmin() || $user->is($request->user()), 403, 'Super administrator accounts cannot be linked here.');
            $profile = $this->profile($original);
            if ($profile->archived_at) throw ValidationException::withMessages(['record' => 'Restore this historical record before linking an account.']);
            if ($profile->user_id) throw ValidationException::withMessages(['record' => 'This historical record already has an account.']);
            if (HistoricalTeamProfile::where('user_id', $user->id)->exists()) throw ValidationException::withMessages(['user_id' => 'This account is already linked to another historical entry.']);
            $profile->update(['user_id' => $user->id]);
        });
        return redirect()->route('admin.historical.team.index')->with('status', 'Existing account linked. Its password and roles were not changed.');
    }

    public function archive(LegacySourceRecord $record): RedirectResponse
    {
        $this->superAdmin();
        $this->assertTeam($record);
        DB::transaction(function () use ($record) {
            $original = LegacySourceRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            $this->profile($original)->update(['archived_at' => now()]);
        });
        return redirect()->route('admin.historical.team.index')->with('status', 'Historical team entry archived. Its original and any linked login account were not deleted.');
    }

    public function restore(LegacySourceRecord $record): RedirectResponse
    {
        $this->superAdmin();
        $this->assertTeam($record);
        HistoricalTeamProfile::where('legacy_source_record_id', $record->id)->whereNotNull('archived_at')->update(['archived_at' => null]);
        return redirect()->route('admin.historical.team.index', ['archived' => 1])->with('status', 'Historical team entry restored.');
    }

    private function profile(LegacySourceRecord $record): HistoricalTeamProfile
    {
        return HistoricalTeamProfile::firstOrCreate(['legacy_source_record_id' => $record->id], $this->defaults($record));
    }

    private function defaults(LegacySourceRecord $record): array
    {
        $original = $record->raw_payload;
        $name = trim(trim((string) ($original['firstname'] ?? '')).' '.trim((string) ($original['lastname'] ?? '')));
        return ['name' => $name !== '' ? $name : (trim((string) ($original['username'] ?? '')) ?: 'Historical team #'.$record->source_row_id),
            'legacy_username' => $original['username'] ?? null, 'phone' => $original['phone'] ?? null];
    }

    private function assertTeam(LegacySourceRecord $record): void
    {
        abort_unless($record->source_database === 'fathersheart' && in_array($record->source_table, ['staff', 'volunteer', 'employee'], true), 404);
    }

    private function superAdmin(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }
}
