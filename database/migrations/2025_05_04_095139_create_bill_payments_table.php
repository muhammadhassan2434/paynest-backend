<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
            // Foreign keys to services and providers
            $table->foreignId('service_id')->constrained('services')->onDelete('cascade');
            $table->foreignId('service_provider_id')->constrained('service_providers')->onDelete('cascade');
        
            $table->string('consumer_number');
            $table->string('customer_name')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->enum('status', ['unpaid', 'paid', 'failed'])->default('unpaid');
            $table->string('transaction_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};
