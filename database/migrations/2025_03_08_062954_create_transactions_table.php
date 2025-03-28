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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('cascade'); 
            $table->string('receiver_number')->nullable(); 
            $table->decimal('amount', 10, 2);
            $table->string('transaction_type');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('reference')->unique(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
