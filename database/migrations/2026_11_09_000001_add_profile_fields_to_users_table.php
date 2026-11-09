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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar', 255)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('users', 'name_changes_count')) {
                $table->unsignedInteger('name_changes_count')->default(0)->after('avatar');
            }
            if (!Schema::hasColumn('users', 'name_changed_at')) {
                $table->timestamp('name_changed_at')->nullable()->after('name_changes_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar', 'name_changes_count', 'name_changed_at']);
        });
    }
};

