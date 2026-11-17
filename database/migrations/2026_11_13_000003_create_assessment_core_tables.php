<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('distribution_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->date('event_date')->nullable();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subcounty_id')->nullable()->constrained()->nullOnDelete();
            $table->string('legacy_table', 120)->nullable()->unique();
            $table->string('status', 24)->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 180);
            $table->string('phone', 40)->nullable();
            $table->text('nin')->nullable(); // Encrypted at the model layer; never used for automatic matching.
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index('full_name');
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->restrictOnDelete();
            $table->foreignId('distribution_event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subcounty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('intake_code', 80)->nullable()->index();
            $table->date('assessed_on')->nullable();
            $table->unsignedTinyInteger('age_years')->nullable();
            $table->string('gender', 24)->nullable();
            $table->string('wearing', 180)->nullable();
            $table->string('device_requested', 80)->nullable();
            $table->string('device_size', 40)->nullable();
            $table->text('not_given_reason')->nullable();
            $table->string('back_measurement', 40)->nullable();
            $table->string('thigh_measurement', 40)->nullable();
            $table->string('leg_measurement', 40)->nullable();
            $table->string('hips_width', 40)->nullable();
            $table->longText('disability_story')->nullable();
            $table->json('causes')->nullable();
            $table->json('diagnoses')->nullable();
            $table->string('cause_other', 180)->nullable();
            $table->string('diagnosis_other', 180)->nullable();
            $table->text('current_mobility')->nullable();
            $table->boolean('has_wheelchair')->nullable();
            $table->text('has_wheelchair_explanation')->nullable();
            $table->boolean('ever_had_wheelchair')->nullable();
            $table->text('ever_had_wheelchair_explanation')->nullable();
            $table->text('mobility_hopes')->nullable();
            $table->longText('conversation_notes')->nullable();
            $table->longText('thanksgiving_message')->nullable();
            $table->longText('other_notes')->nullable();
            $table->string('salvation_status', 40)->nullable();
            $table->string('church_referral', 180)->nullable();
            $table->string('staff_volunteer_names', 255)->nullable();
            $table->boolean('registration_complete')->default(false);
            $table->boolean('assessment_complete')->default(false);
            $table->boolean('safety_lesson_received')->default(false);
            $table->boolean('therapy_received')->default(false);
            $table->text('evangelism_notes')->nullable();
            $table->string('before_photo_number', 80)->nullable();
            $table->string('after_photo_number', 80)->nullable();
            $table->boolean('released_to_go')->default(false);
            $table->string('status', 24)->default('draft');
            $table->boolean('legacy_followup')->nullable();
            $table->timestamps();
            $table->index(['distribution_event_id', 'status']);
            $table->index(['district_id', 'subcounty_id']);
        });

        Schema::create('media_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->restrictOnDelete();
            $table->foreignId('assessment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('not_recorded');
            $table->date('signed_on')->nullable();
            $table->string('signer_name', 180)->nullable();
            $table->string('guardian_name', 180)->nullable();
            $table->date('guardian_signed_on')->nullable();
            $table->string('document_path', 500)->nullable();
            $table->string('form_version', 40)->default('2026-paper');
            $table->timestamps();
        });

        Schema::create('legacy_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('source_database', 50);
            $table->string('status', 24)->default('dry_run');
            $table->json('counts')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('legacy_source_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('legacy_import_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_key', 64)->unique(); // SHA-256(database|table|row ID).
            $table->string('source_database', 50);
            $table->string('source_table', 120);
            $table->string('source_row_id', 80);
            $table->string('mapped_type', 80)->nullable();
            $table->unsignedBigInteger('mapped_id')->nullable();
            $table->string('checksum', 64);
            $table->longText('raw_payload');
            $table->string('status', 24)->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
            $table->index(['source_table', 'source_row_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_source_records');
        Schema::dropIfExists('legacy_import_batches');
        Schema::dropIfExists('media_consents');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('people');
        Schema::dropIfExists('distribution_events');
    }
};
