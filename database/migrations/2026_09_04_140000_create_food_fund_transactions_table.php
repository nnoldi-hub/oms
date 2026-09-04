<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense'])->index();
            $table->date('transaction_date')->index();
            $table->decimal('amount', 12, 2);
            $table->string('category')->nullable();
            $table->string('counterparty')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('reference')->nullable();
            $table->text('description');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_fund_transactions');
    }
};
