<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void {
    Schema::create('brands', fn(Blueprint $t) => ($t->id() && $t->string('name')->unique() && $t->timestamps()));
    Schema::create('types', function (Blueprint $t) { $t->id(); $t->foreignId('brand_id')->constrained()->restrictOnDelete(); $t->string('name'); $t->unique(['brand_id', 'name']); $t->timestamps(); });
    Schema::create('vehicles', function (Blueprint $t) { $t->id(); $t->foreignId('brand_id')->constrained(); $t->foreignId('type_id')->constrained(); $t->string('plate')->unique(); $t->unsignedBigInteger('daily_rate_cents'); $t->unsignedSmallInteger('year')->nullable(); $t->string('color')->nullable(); $t->timestamp('archived_at')->nullable(); $t->timestamps(); });
    Schema::create('customers', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('email')->unique(); $t->string('phone')->unique(); $t->timestamps(); });
} public function down(): void { Schema::dropIfExists('customers'); Schema::dropIfExists('vehicles'); Schema::dropIfExists('types'); Schema::dropIfExists('brands'); } };
