<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media_consents', function (Blueprint $table) {
            $table->text('recipient_identity')->nullable();
            $table->string('recipient_phone', 40)->nullable();
        });
    }

    public function down(): void
    {
        throw new LogicException('Consent details may contain irreplaceable records; restore a verified backup instead of rolling back.');
    }
};
