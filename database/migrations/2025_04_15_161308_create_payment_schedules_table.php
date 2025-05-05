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
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
        
            $table->unsignedBigInteger('account_id');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_funded')->default(true); // paisa held hua ya nahi
            $table->enum('status', ['scheduled', 'executed', 'cancelled', 'failed'])->default('scheduled');
            $table->date('scheduled_for');
        
            // Purpose & Type
            $table->string('purpose'); // e.g., "Electricity", "Hostel Rent"
            $table->enum('type', ['bill', 'transfer'])->default('bill');
        
            // Related to "hostel"/utility/etc.
            $table->string('category')->nullable(); // e.g., hostel, electricity, internet, fee
            $table->string('reference_no')->nullable(); // bill number, hostel ref, etc.
        
            // Receiver info for transfers
            $table->string('receiver_name')->nullable();
            $table->string('receiver_account_no')->nullable();
            $table->string('receiver_bank')->nullable();
        
            $table->text('note')->nullable();
        
            $table->timestamps();
        
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
