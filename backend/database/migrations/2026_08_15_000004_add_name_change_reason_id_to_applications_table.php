<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->foreignId('name_change_reason_id')
                ->nullable()
                ->after('requested_name')
                ->constrained('name_change_reasons')
                ->nullOnDelete();
        });

        if (Schema::hasTable('name_change_reasons')) {
            $reasons = DB::table('name_change_reasons')->select('id', 'name')->get();

            foreach ($reasons as $reason) {
                DB::table('applications')
                    ->where('reason', $reason->name)
                    ->update(['name_change_reason_id' => $reason->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('name_change_reason_id');
        });
    }
};
