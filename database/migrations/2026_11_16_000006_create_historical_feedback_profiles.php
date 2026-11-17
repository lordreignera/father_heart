<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historical_feedback_profiles', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('legacy_source_record_id')->unique()->constrained('legacy_source_records')->restrictOnDelete();
            $table->text('staff')->nullable();
            $table->text('recipient')->nullable();
            $table->text('rating')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_feedback_profiles');
    }
};
