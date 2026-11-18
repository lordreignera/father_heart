<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('testimony_videos', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->string('title', 180); $table->text('description')->nullable();
            $table->string('file_path', 500); $table->string('mime_type', 80); $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        $names = ['route-admin-testimonies-index', 'route-admin-testimonies-store', 'route-admin-testimonies-show'];
        foreach ($names as $name) DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $names)->pluck('id');
        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['Super Administrator', 'super-admin'])->pluck('id') as $role) foreach ($ids as $id) DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $id]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void { throw new LogicException('Testimony records may contain important media; restore a verified backup instead of rolling this migration back.'); }
};
