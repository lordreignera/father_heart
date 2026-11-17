<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('source_id', 150)->nullable()->unique();
            $table->string('ubos_code', 16)->nullable()->index();
            $table->string('name', 120);
            $table->string('kind', 24)->default('district');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['name', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
