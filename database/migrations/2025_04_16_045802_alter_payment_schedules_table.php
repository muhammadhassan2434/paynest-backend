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
        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('transaction_id')->nullable(); // Add nullable transaction_id
            $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('set null'); // Foreign key constraint
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_schedules', function (Blueprint $table) {
            $table->dropForeign(['transaction_id']); // Drop the foreign key
            $table->dropColumn('transaction_id'); 
        });
    }
};
