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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('paynest_id')->unique();
            $table->string('phone');
            $table->string('gender');
            $table->string('address');
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->enum('currency', ['PKR', 'USD'])->default('PKR');
            $table->enum('status',['pending','active','blocked'])->default('pending');
            $table->foreign('user_id')->on('users')->references('id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
