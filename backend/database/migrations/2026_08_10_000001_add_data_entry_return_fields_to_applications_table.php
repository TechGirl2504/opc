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
        Schema::table('applications', function (Blueprint $table) {
            $table->text('data_entry_return_reason')->nullable()->after('approver_send_back_at');
            $table->timestamp('data_entry_return_at')->nullable()->after('data_entry_return_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['data_entry_return_reason', 'data_entry_return_at']);
        });
    }
};
