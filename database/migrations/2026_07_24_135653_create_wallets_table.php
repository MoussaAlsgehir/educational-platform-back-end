<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0.00);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('direction', ['credit', 'debit']); // credit: شحن (زائد), debit: خصم (ناقص)
            $table->string('transaction_type'); // مثلاً: manual_top_up, course_purchase
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 10, 2); // الرصيد بعد العملية مباشرة (مهم للـ Audit Log)
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('wallets');
    }
};
