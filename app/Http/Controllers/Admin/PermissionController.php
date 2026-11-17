<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\RoleAccess;
use App\Support\PermissionCatalogue;
use App\Support\UserSessionRevoker;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    private const RESERVED = [
        'access-admin', 'manage-staff', 'manage-roles', 'manage-people',
        'manage-distributions', 'manage-inventory', 'view-reports',
    ];

    public function index(Request $request): View
    {
        $data = $request->validate(['role' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('guard_name', 'web')]]);
        $roles = Role::where('guard_name', 'web')->with('permissions')->orderBy('name')->get();
        $selectedRole = isset($data['role']) ? $roles->firstWhere('id', (int) $data['role']) : ($roles->first(fn ($role) => ! in_array($role->name, ['Super Administrator', 'super-admin'], true)) ?? $roles->first());
        $permissions = PermissionCatalogue::assignable(Permission::where('guard_name', 'web')->withCount('roles')->orderBy('name')->get());
        $assignable = RoleAccess::assignablePermissions($request->user());

        return view('admin.permissions.index', [
            'permissions' => $permissions,
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'assignablePermissions' => $assignable === null ? $permissions : $permissions->whereIn('name', $assignable),
        ]);
    }

    public function updateRolePermissions(Request $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);
        abort_if(in_array($role->name, ['Super Administrator', 'super-admin'], true), 403, 'Built-in administrator roles are protected.');
        abort_unless(RoleAccess::canManage($request->user(), $role), 403);
        $rules = ['string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')];
        if (($assignable = RoleAccess::assignablePermissions($request->user())) !== null) {
            $rules[] = Rule::in($assignable);
        }
        $data = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => $rules]);
        $permissionNames = $data['permissions'] ?? [];
        $removedPermissions = $role->permissions->pluck('name')->diff($permissionNames)->isNotEmpty();
        $signOutActor = DB::transaction(function () use ($role, $permissionNames, $removedPermissions, $request): bool {
            $role->syncPermissions($permissionNames);
            return $removedPermissions && UserSessionRevoker::revokeRoleMembers($role, $request->user());
        });

        if ($signOutActor) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('status', 'Your role changed. Please sign in again.');
        }

        return redirect()->route('admin.permissions.index', ['role' => $role->id])->with('status', 'Role permissions updated.');
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return view('admin.permissions.form', ['permission' => new Permission]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $permission = Permission::create($this->validated($request) + ['guard_name' => 'web']);
        return redirect()->route('admin.permissions.index')->with('status', 'Permission created. Assign it to a role to make it available.');
    }

    public function edit(Permission $permission): View
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_if(PermissionCatalogue::isBuiltIn($permission->name), 403, 'Built-in permissions are protected.');
        abort_if(in_array($permission->name, self::RESERVED, true), 403, 'Built-in permissions are protected.');
        return view('admin.permissions.form', compact('permission'));
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        abort_if(PermissionCatalogue::isBuiltIn($permission->name), 403, 'Built-in permissions are protected.');
        abort_if(in_array($permission->name, self::RESERVED, true), 403, 'Built-in permissions are protected.');
        $permission->update($this->validated($request, $permission));
        return redirect()->route('admin.permissions.index')->with('status', 'Permission updated.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        abort_if(PermissionCatalogue::isBuiltIn($permission->name), 403, 'Built-in permissions are protected.');
        abort_if(in_array($permission->name, self::RESERVED, true), 403, 'Built-in permissions are protected.');
        $signOutActor = DB::transaction(function () use ($permission): bool {
            $roleIds = DB::table(config('permission.table_names.role_has_permissions'))
                ->where(config('permission.column_names.permission_pivot_key') ?: 'permission_id', $permission->id)
                ->pluck(config('permission.column_names.role_pivot_key') ?: 'role_id');
            $signOutActor = false;
            foreach (Role::whereIn('id', $roleIds)->get() as $role) {
                $signOutActor = UserSessionRevoker::revokeRoleMembers($role, auth()->user()) || $signOutActor;
            }
            $userIds = DB::table(config('permission.table_names.model_has_permissions'))
                ->where(config('permission.column_names.permission_pivot_key') ?: 'permission_id', $permission->id)
                ->where('model_type', (new User)->getMorphClass())
                ->pluck(config('permission.column_names.model_morph_key', 'model_id'));
            foreach (User::whereIn('id', $userIds)->lockForUpdate()->get() as $user) {
                $signOutActor = $user->is(auth()->user()) || $signOutActor;
                UserSessionRevoker::revoke($user);
            }
            $permission->delete();
            return $signOutActor;
        });
        if ($signOutActor) {
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login')->with('status', 'Your permission changed. Please sign in again.');
        }
        return redirect()->route('admin.permissions.index')->with('status', 'Permission deleted and removed from assigned roles.');
    }

    private function validated(Request $request, ?Permission $permission = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:120', 'regex:/^[a-z][a-z0-9-]*$/', 'not_regex:/^route-admin-/', Rule::notIn(self::RESERVED),
                Rule::unique('permissions', 'name')->where('guard_name', 'web')->ignore($permission?->id),
            ],
        ]);
    }
}
