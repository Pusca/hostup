<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // THE MASTER CALENDAR: one row per property per date. Single source of truth.
        Schema::create('availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('available'); // available, booked, blocked
            $table->decimal('price', 10, 2)->nullable();     // per-day override
            $table->unsignedSmallInteger('min_nights')->nullable();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->nullable();            // direct, airbnb, booking, manual
            $table->timestamps();

            $table->unique(['property_id', 'date']);
            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability');
    }
};
