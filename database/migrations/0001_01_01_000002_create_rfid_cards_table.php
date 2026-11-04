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
        if (!Schema::hasTable('rfid_cards')) {
            Schema::create('rfid_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('uid', 50)->unique();
                $table->string('license_plate', 20)->nullable();
                $table->integer('balance')->default(50000);
                $table->enum('status', ['active', 'blocked'])->default('active');
                $table->timestamp('created_at')->useCurrent();

                $table->index('uid', 'idx_uid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_cards');
    }
};

