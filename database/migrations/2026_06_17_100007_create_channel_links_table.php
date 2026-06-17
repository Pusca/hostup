<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Mapping between an internal property and an OTA listing.
        // Phase 1 = iCal (import url to pull OTA bookings, export token for our .ics).
        // Phase 2 = API (external_listing_id + provider credentials/ref).
        Schema::create('channel_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->string('external_listing_id')->nullable();
            $table->string('ical_import_url', 1024)->nullable();
            $table->string('ical_export_token')->nullable()->unique();
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['property_id', 'channel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_links');
    }
};
