<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();   // HU-XXXXXX
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('channel_link_id')->nullable()->constrained()->nullOnDelete();

            $table->string('channel')->default('direct'); // direct, airbnb, booking...
            $table->string('external_ref')->nullable();   // OTA reservation id / iCal UID

            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights')->default(1);
            $table->unsignedSmallInteger('guests_count')->default(1);

            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('EUR');

            $table->string('status')->default('pending');          // pending, confirmed, cancelled
            $table->string('payment_status')->default('unpaid');   // unpaid, deposit_paid, paid
            $table->string('payment_intent_id')->nullable();       // Stripe

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'check_in', 'check_out']);
            $table->index('external_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
