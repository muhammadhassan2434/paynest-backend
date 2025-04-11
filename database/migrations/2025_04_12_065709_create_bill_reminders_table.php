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
        Schema::create('bill_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('bill_type'); 
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('frequency', ['once', 'monthly', 'yearly'])->default('once');
            $table->boolean('is_notified')->default(false); // To track if notification was sent
            $table->timestamp('last_notified_at')->nullable(); // Optional: when reminder was last sent
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_reminders');
    }
};
