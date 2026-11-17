<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('person_phone_locks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->string('number_key', 64)->primary();
            $table->timestamp('created_at');
        });
    }

    public function down(): void { Schema::dropIfExists('person_phone_locks'); }
};
