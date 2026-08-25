<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('effective_end_date');
            $table->string('status');
            $table->unsignedBigInteger('daily_rate_snapshot_cents');
            $table->unsignedBigInteger('subtotal_cents');
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('total_cents');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['vehicle_id', 'status', 'start_date', 'effective_end_date']);
        });
    }

    public function down(): void { Schema::dropIfExists('rentals'); }
};
