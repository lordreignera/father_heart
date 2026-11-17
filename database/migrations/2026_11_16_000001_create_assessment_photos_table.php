<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessment_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->restrictOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 10);
            $table->string('path', 500);
            $table->timestamps();
            $table->index(['assessment_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_photos');
    }
};
