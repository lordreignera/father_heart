<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('legacy_source_records', function (Blueprint $table) {
            $table->index(['mapped_type', 'mapped_id'], 'legacy_mapping_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('legacy_source_records', fn (Blueprint $table) => $table->dropIndex('legacy_mapping_lookup'));
    }
};
