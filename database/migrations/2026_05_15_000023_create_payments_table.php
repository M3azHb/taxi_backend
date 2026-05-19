<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ride_id')->unique()->constrained('rides')->cascadeOnDelete();
            $table->foreignId('discount_code_id')->nullable()->constrained('discount_codes')->nullOnDelete();

            $table->decimal('subtotal', 8, 2);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->decimal('amount', 8, 2);

            $table->decimal('commission_percentage', 5, 2);
            $table->decimal('commission_amount', 8, 2);
            $table->decimal('driver_earning', 8, 2);

            $table->enum('payment_method', ['cash'])->default('cash');
            $table->enum('status', ['pending', 'paid'])->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
