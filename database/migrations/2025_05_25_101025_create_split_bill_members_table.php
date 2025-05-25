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
           Schema::create('split_bill_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('split_bill_id');
                $table->unsignedBigInteger('user_id');
                $table->decimal('amount', 10, 2);
                $table->boolean('is_paid')->default(false);
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->foreign('split_bill_id')->references('id')->on('split_bills')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('split_bill_members');
    }
};
