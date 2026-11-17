<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('therapy_services', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->string('name', 120); $table->string('category', 30); $table->boolean('is_active')->default(true); $table->timestamps();
            $table->unique(['name', 'category']);
        });
        Schema::create('therapy_options', function (Blueprint $table) {
            $table->engine = 'InnoDB'; $table->id(); $table->string('category', 40); $table->string('name', 180);
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->unique(['category', 'name']);
        });
        Schema::create('therapy_children', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->foreignId('person_id')->unique()->constrained('people')->restrictOnDelete();
            $table->date('registered_on'); $table->date('date_of_birth')->nullable();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->longText('registration_data'); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('therapy_visits', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id(); $table->foreignId('therapy_child_id')->constrained('therapy_children')->restrictOnDelete();
            $table->date('visited_on')->index(); $table->string('status', 24); $table->longText('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        Schema::create('therapy_visit_service', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->foreignId('therapy_visit_id')->constrained('therapy_visits')->cascadeOnDelete();
            $table->foreignId('therapy_service_id')->constrained('therapy_services')->restrictOnDelete();
            $table->primary(['therapy_visit_id', 'therapy_service_id']);
        });
        Schema::create('therapy_documents', function (Blueprint $table) {
            $table->engine = 'InnoDB'; $table->id();
            $table->foreignId('therapy_child_id')->constrained('therapy_children')->restrictOnDelete();
            $table->string('category', 40); $table->string('original_name', 255); $table->string('path', 500);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
        foreach (['Music', 'Physio', 'Occupational', 'Aqua', 'Speech'] as $name) DB::table('therapy_services')->insert(['name' => $name, 'category' => 'therapy', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['Counseling', 'Respite', 'Family pool facility use'] as $name) DB::table('therapy_services')->insert(['name' => $name, 'category' => 'activity', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $options = [
            'disabilities' => ['Autism spectrum', 'Down syndrome', 'Cerebral palsy', 'ADHD', 'Hearing impairment', 'Visual impairment', 'Speech / language delay', 'Other'],
            'mobility' => ['Walks independently', 'Uses wheelchair', 'Uses crutches / walker', 'Assisted mobility'],
            'communication' => ['Verbal', 'Non-verbal', 'Uses AAC device', 'Sign language'],
            'previous_therapy' => ['Music therapy', 'Occupational therapy', 'Speech therapy', 'None'],
            'musical_interests' => ['Singing', 'Drums / percussion', 'Piano / keyboard', 'Guitar', 'Listening to music', 'Dancing / movement', 'Flute / wind instruments', 'Other'],
            'session_type' => ['Individual (1-on-1)', 'Small group (2–4)', 'Large group (5+)', 'No preference'],
            'frequency' => ['Once a week', 'Twice a week', 'Three times a week', 'As recommended'],
        ];
        foreach ($options as $category => $values) foreach ($values as $name) DB::table('therapy_options')->insert(['category' => $category, 'name' => $name, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $names = ['route-admin-therapy-children-index', 'route-admin-therapy-children-create', 'route-admin-therapy-children-store', 'route-admin-therapy-children-show', 'route-admin-therapy-children-edit', 'route-admin-therapy-children-update', 'route-admin-therapy-visits-store', 'route-admin-therapy-visits-show', 'route-admin-therapy-visits-edit', 'route-admin-therapy-visits-update', 'route-admin-therapy-reports-index', 'route-admin-therapy-reports-export', 'route-admin-therapy-services-index', 'route-admin-therapy-services-store', 'route-admin-therapy-services-update', 'route-admin-therapy-documents-store', 'route-admin-therapy-documents-download', 'route-admin-therapy-options-index', 'route-admin-therapy-options-store', 'route-admin-therapy-options-update'];
        foreach ($names as $name) DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $ids = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $names)->pluck('id');
        foreach (DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['Super Administrator', 'super-admin'])->pluck('id') as $role) foreach ($ids as $id) DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $id]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        Schema::dropIfExists('therapy_documents'); Schema::dropIfExists('therapy_visit_service'); Schema::dropIfExists('therapy_visits'); Schema::dropIfExists('therapy_children'); Schema::dropIfExists('therapy_options'); Schema::dropIfExists('therapy_services');
    }
};
