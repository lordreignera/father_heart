<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('name', 160)->unique();
            $table->string('unit', 40)->default('item');
            $table->unsignedBigInteger('quantity')->default(0);
            $table->date('opening_date')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->restrictOnDelete();
            // Existing users table is MyISAM, so it cannot be a foreign-key target.
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->string('type', 12);
            $table->unsignedBigInteger('quantity');
            $table->date('occurred_on');
            $table->string('reference', 180)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['occurred_on', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
    }
};
