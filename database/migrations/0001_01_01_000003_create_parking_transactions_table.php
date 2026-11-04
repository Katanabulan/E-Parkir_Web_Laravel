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
        if (!Schema::hasTable('parking_transactions')) {
            Schema::create('parking_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_code', 50)->nullable()->index('idx_ticket_code');
                $table->tinyInteger('parking_floor')->default(1);
                $table->foreignId('card_id')->nullable()->constrained('rfid_cards')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignId('device_in_id')->constrained('devices');
                $table->foreignId('device_out_id')->nullable()->constrained('devices');
                $table->timestamp('tap_in_time')->useCurrent();
                $table->timestamp('tap_out_time')->nullable();
                $table->integer('duration_minutes')->nullable();
                $table->integer('total_fee')->default(0);
                $table->integer('balance_before')->nullable();
                $table->integer('balance_after')->nullable();
                $table->enum('status', ['PARKED', 'COMPLETED'])->default('PARKED');
                $table->enum('payment_method', ['card', 'cash', 'qris'])->default('card');
                $table->timestamp('created_at')->useCurrent();

                $table->index(['card_id', 'status'], 'idx_active_parking');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_transactions');
    }
};

