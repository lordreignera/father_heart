<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserSessionRevoker
{
    public static function revoke(User $user): void
    {
        // A different session driver cannot enumerate all of a user's sessions.
        // Fail closed rather than reporting a successful logout that did not occur.
        if (config('session.driver') !== 'database' && ! app()->environment('testing')) {
            throw ValidationException::withMessages(['session' => 'Database-backed sessions are required to revoke all staff sessions.']);
        }

        $user->forceFill(['remember_token' => Str::random(60)])->save();
        $user->tokens()->delete();
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)->delete();
        }
    }

    /** Revoke each account affected by a reduction in role permissions. */
    public static function revokeRoleMembers(Role $role, ?User $actor): bool
    {
        $table = config('permission.table_names.model_has_roles');
        $roleKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $modelKey = config('permission.column_names.model_morph_key', 'model_id');
        $ids = DB::table($table)->where($roleKey, $role->id)
            ->where('model_type', (new User)->getMorphClass())->pluck($modelKey);
        $includesActor = $actor && $ids->contains($actor->id);
        User::whereIn('id', $ids)->lockForUpdate()->get()->each(fn (User $user) => self::revoke($user));
        return (bool) $includesActor;
    }
}
