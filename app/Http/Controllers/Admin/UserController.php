<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RoleAccess;
use App\Support\AdminRedirect;
use App\Support\UserSessionRevoker;
use App\Support\PendingRegistrationEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('roles')->when($request->filled('q'), fn ($query) => $query->where(function ($query) use ($request) {
            $term = '%'.$request->string('q').'%';
            $query->where('name', 'like', $term)->orWhere('email', 'like', $term);
        }))->orderBy('name')->paginate(25)->withQueryString();

        $roles = $this->availableRoles();
        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create(): View
    {
        return view('admin.users.form', ['staff' => new User, 'roles' => $this->availableRoles()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $roleIds = $data['roles'] ?? [];
        unset($data['roles']);

        DB::transaction(function () use ($data, $roleIds): void {
            $staff = User::create($data + ['email_verified_at' => now()]);
            $staff->syncRoles(Role::whereIn('id', $roleIds)->get());
        });

        return AdminRedirect::firstAllowed(['admin.users.index' => []])->with('status', 'Staff account created.');
    }

    public function show(User $user): View
    {
        $user->load('roles.permissions');
        return view('admin.users.show', ['staff' => $user]);
    }

    public function edit(User $user): View
    {
        $this->authorizeManageTarget($user);
        $user->load('roles');
        return view('admin.users.form', ['staff' => $user, 'roles' => $this->availableRoles()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManageTarget($user);
        $data = $this->validated($request, $user);
        $roleIds = $data['roles'] ?? [];
        unset($data['roles']);

        $removedRole = $user->roles()->pluck('roles.id')->diff(collect($roleIds)->map(fn ($id) => (int) $id))->isNotEmpty();
        $revokeAccess = $removedRole || filled($data['password'] ?? null);

        if ($user->isSuperAdmin() && ! collect($roleIds)->contains(fn ($id) => in_array(
            Role::find($id)?->name, ['Super Administrator', 'super-admin'], true
        ))) {
            abort_if($user->is(auth()->user()), 422, 'You cannot remove your own super administrator access.');
            $otherAdmins = User::where('id', '<>', $user->id)->get()->filter->isSuperAdmin()->isNotEmpty();
            abort_unless($otherAdmins, 422, 'The last super administrator cannot be demoted.');
        }

        if (blank($data['password'] ?? null)) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);

        DB::transaction(function () use ($user, $data, $roleIds, $revokeAccess): void {
            $user->update($data);
            $user->syncRoles(Role::whereIn('id', $roleIds)->get());
            if ($revokeAccess) UserSessionRevoker::revoke($user);
        });

        if ($revokeAccess && $user->is($request->user())) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('status', 'Your account changed. Please sign in again.');
        }

        $destinations = $request->input('_modal') === 'staff-edit-'.$user->id ? ['admin.users.index' => [], 'admin.users.show' => [$user]] : ['admin.users.show' => [$user], 'admin.users.index' => []];
        return AdminRedirect::firstAllowed($destinations)->with('status', $revokeAccess ? 'Staff account updated; sessions and API tokens were revoked.' : 'Staff account updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->is(auth()->user()), 422, 'You cannot delete your own account here.');
        $this->authorizeManageTarget($user);

        if ($user->isSuperAdmin()) {
            $otherAdmins = User::where('id', '<>', $user->id)->get()->filter->isSuperAdmin()->isNotEmpty();
            abort_unless($otherAdmins, 422, 'The last super administrator cannot be deleted.');
        }

        DB::transaction(function () use ($user): void {
            UserSessionRevoker::revoke($user);
            $user->delete();
        });
        return AdminRedirect::firstAllowed(['admin.users.index' => []])->with('status', 'Staff account deleted.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $roleIds = $this->availableRoles()->modelKeys();
        if (is_string($request->input('email'))) $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id), PendingRegistrationEmail::rule()],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:12', 'confirmed'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', Rule::in($roleIds)],
        ]);
    }

    private function availableRoles()
    {
        $roles = Role::where('guard_name', 'web')->orderBy('name');
        if (! auth()->user()->isSuperAdmin()) {
            $roles->whereNotIn('name', ['Super Administrator', 'super-admin']);
        }

        return $roles->with('permissions')->get()->filter(fn ($role) => RoleAccess::canManage(auth()->user(), $role));
    }

    private function authorizeManageTarget(User $user): void
    {
        abort_unless(RoleAccess::canManageUser(auth()->user(), $user), 403);
    }
}
