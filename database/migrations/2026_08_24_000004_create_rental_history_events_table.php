<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void { Schema::create('rental_history_events', function (Blueprint $table): void { $table->id(); $table->foreignId('rental_id')->constrained('rentals'); $table->string('event_type'); $table->timestamp('occurred_at'); $table->string('state'); $table->text('reason')->nullable(); $table->date('effective_end_date')->nullable(); $table->index(['rental_id','occurred_at']); }); }
    public function down(): void { Schema::dropIfExists('rental_history_events'); }
};
