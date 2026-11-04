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
        if (!Schema::hasTable('access_logs')) {
            Schema::create('access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
                $table->string('scan_uid', 50);
                $table->enum('access_method', ['rfid', 'ticket', 'mobile_app', 'web_admin'])->default('rfid');
                $table->enum('status', ['granted', 'denied']);
                $table->string('notes', 255)->nullable();
                $table->timestamp('created_at')->useCurrent()->index('idx_created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};

