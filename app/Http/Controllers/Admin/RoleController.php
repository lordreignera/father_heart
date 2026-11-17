<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RoleAccess;
use App\Support\PermissionCatalogue;
use App\Support\UserSessionRevoker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const RESERVED = ['Super Administrator', 'super-admin'];

    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'users' => ['nullable', Rule::in(['with', 'without'])]]);
        $pivot = config('permission.table_names.model_has_roles');
        $roleKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $modelKey = config('permission.column_names.model_morph_key', 'model_id');
        $userCount = DB::table($pivot.' as assignments')->join((new User)->getTable().' as staff_users', 'staff_users.id', '=', 'assignments.'.$modelKey)
            ->where('assignments.model_type', (new User)->getMorphClass())->whereColumn('assignments.'.$roleKey, 'roles.id')->selectRaw('count(*)');
        // Do not use Role::withCount('users'): under auth:sanctum the package can resolve a null user model for the transient guard.
        $roles = Role::query()->where('guard_name', 'web')->with('permissions')->select('roles.*')->selectSub($userCount, 'users_count')->orderBy('name')->get();
        $roles = $roles->filter(function ($role) use ($filters) {
            $term = mb_strtolower($filters['q'] ?? '');
            $permissionText = $role->permissions->map(fn ($permission) => $permission->name.' '.PermissionCatalogue::label($permission->name))->join(' ');
            return ($term === '' || str_contains(mb_strtolower($role->name.' '.$permissionText), $term))
                && (($filters['users'] ?? null) !== 'with' || (int) $role->users_count > 0)
                && (($filters['users'] ?? null) !== 'without' || (int) $role->users_count === 0);
        });
        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role, 'permissions' => $this->permissions()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $permissionNames = $data['permissions'] ?? [];
        unset($data['permissions']);

        $role = DB::transaction(function () use ($data, $permissionNames) {
            $role = Role::create($data + ['guard_name' => 'web']);
            $role->syncPermissions($permissionNames);
            return $role;
        });

        return redirect()->route('admin.roles.index')->with('status', 'Role created.');
    }

    public function edit(Role $role): View
    {
        abort_if(in_array($role->name, self::RESERVED, true), 403, 'Built-in super administrator roles are protected.');
        abort_unless(RoleAccess::canManage(auth()->user(), $role), 403);
        $role->load('permissions');
        return view('admin.roles.form', ['role' => $role, 'permissions' => $this->permissions()]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if(in_array($role->name, self::RESERVED, true), 403, 'Built-in super administrator roles are protected.');
        abort_unless(RoleAccess::canManage($request->user(), $role), 403);
        $data = $this->validated($request, $role);
        $permissionNames = $request->boolean('_role_name_only') ? null : ($data['permissions'] ?? []);
        unset($data['permissions']);

        $removedPermissions = $permissionNames !== null && $role->permissions->pluck('name')->diff($permissionNames)->isNotEmpty();
        $signOutActor = DB::transaction(function () use ($role, $data, $permissionNames, $removedPermissions, $request): bool {
            $role->update($data);
            if ($permissionNames !== null) $role->syncPermissions($permissionNames);
            return $removedPermissions && UserSessionRevoker::revokeRoleMembers($role, $request->user());
        });

        if ($signOutActor) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('status', 'Your role changed. Please sign in again.');
        }

        return redirect()->route('admin.roles.index')->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(in_array($role->name, self::RESERVED, true), 403, 'Built-in super administrator roles are protected.');
        abort_unless(RoleAccess::canManage(auth()->user(), $role), 403);
        if (DB::table(config('permission.table_names.model_has_roles'))->where(config('permission.column_names.role_pivot_key') ?: 'role_id', $role->id)->exists()) {
            return back()->withErrors(['role' => 'Remove this role from every staff account before deleting it.']);
        }
        $role->delete();
        return redirect()->route('admin.roles.index')->with('status', 'Role deleted.');
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::notIn(self::RESERVED), Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web'), ...($request->user()->isSuperAdmin() ? [] : [Rule::in(RoleAccess::assignablePermissions($request->user()))])],
        ]);
        $data['guard_name'] = 'web';
        return $data;
    }

    private function permissions()
    {
        $query = Permission::where('guard_name', 'web');
        if (! auth()->user()->isSuperAdmin()) $query->whereIn('name', RoleAccess::assignablePermissions(auth()->user()));
        return PermissionCatalogue::assignable($query->orderBy('name')->get());
    }
}
