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
        Schema::create('split_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by'); // initiator
            $table->string('receiver_account_no'); // restaurant or final receiver
            $table->string('receiver_bank'); // restaurant or final receiver
            $table->decimal('total_amount', 10, 2);
            $table->decimal('collected_amount', 10, 2)->default(0.00);
            $table->string('title');
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'partial', 'completed', 'transferred'])->default('pending');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('split_bills');
    }
};
