<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        // Fixed names keep historical migrations independent of future application changes.
        $tables = ['disabilities' => 'therapy_disabilities', 'mobility' => 'therapy_mobility_options',
            'communication' => 'therapy_communication_options', 'previous_therapy' => 'therapy_previous_therapies',
            'musical_interests' => 'therapy_musical_interests', 'session_type' => 'therapy_session_types', 'frequency' => 'therapy_frequencies'];
        foreach ($tables as $category => $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id(); $table->string('name', 180)->unique();
                $table->boolean('is_active')->default(true); $table->timestamps();
            });
            foreach (DB::table('therapy_options')->where('category', $category)->orderBy('id')->get() as $option) {
                DB::table($name)->insert(['name' => $option->name, 'is_active' => $option->is_active,
                    'created_at' => $option->created_at, 'updated_at' => $option->updated_at]);
            }
        }
        foreach (['index', 'store', 'update'] as $action) {
            $old = DB::table('permissions')->where('guard_name', 'web')->where('name', 'route-admin-therapy-options-'.$action)->value('id');
            $newName = 'route-admin-therapy-catalogues-'.$action;
            DB::table('permissions')->insertOrIgnore(['name' => $newName, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
            $new = DB::table('permissions')->where('guard_name', 'web')->where('name', $newName)->value('id');
            if ($old) {
                foreach (DB::table('role_has_permissions')->where('permission_id', $old)->get() as $grant) {
                    DB::table('role_has_permissions')->insertOrIgnore(['permission_id' => $new, 'role_id' => $grant->role_id]);
                }
                foreach (DB::table('model_has_permissions')->where('permission_id', $old)->get() as $grant) {
                    DB::table('model_has_permissions')->insertOrIgnore(['permission_id' => $new, 'model_type' => $grant->model_type, 'model_id' => $grant->model_id]);
                }
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        throw new LogicException('Therapy settings may contain new data; restore a verified backup instead of rolling this migration back.');
    }
};
