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
        if (!Schema::hasTable('devices')) {
            Schema::create('devices', function (Blueprint $table) {
                $table->id();
                $table->string('device_code', 50)->unique();
                $table->enum('gate_type', ['IN', 'OUT', 'BIDIRECTIONAL'])->default('BIDIRECTIONAL');
                $table->string('location', 100);
                $table->enum('status', ['online', 'offline'])->default('offline');
                $table->boolean('manual_override')->default(false);
                $table->timestamp('last_seen')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};

