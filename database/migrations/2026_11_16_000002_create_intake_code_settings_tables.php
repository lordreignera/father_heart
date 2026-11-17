<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('intake_code_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('prefix', 20)->default('');
            $table->string('date_format', 10)->default('mY');
            $table->string('sequence_reset', 15)->default('monthly');
            $table->unsignedInteger('starting_number')->default(1000);
            $table->timestamps();
        });
        DB::table('intake_code_settings')->insert(['id' => 1, 'prefix' => '', 'date_format' => 'mY', 'sequence_reset' => 'monthly', 'starting_number' => 1000, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('intake_code_sequences', function (Blueprint $table) {
            $table->string('period', 20)->primary();
            $table->unsignedBigInteger('last_number');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_code_sequences');
        Schema::dropIfExists('intake_code_settings');
    }
};
