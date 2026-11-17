<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_registration_requests', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password_hash')->nullable();
            $table->unsignedBigInteger('requested_role_id')->nullable();
            $table->string('requested_role_name');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('approved_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('staff_registration_requests'); }
};
