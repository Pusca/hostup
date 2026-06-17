<?php

use App\Models\Property;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('ical_token')->nullable()->unique()->after('slug');
        });

        // Backfill existing properties with a token
        Property::whereNull('ical_token')->get()->each(function (Property $p) {
            $p->forceFill(['ical_token' => Str::random(32)])->saveQuietly();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('ical_token');
        });
    }
};
