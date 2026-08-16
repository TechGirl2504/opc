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
            if (!Schema::hasColumn('applications', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('national_id');
            }

            if (!Schema::hasColumn('applications', 'phone_number')) {
                $table->string('phone_number', 25)->nullable()->after('date_of_birth');
            }
        });

        $duplicateNationalIds = DB::table('applications')
            ->select('national_id')
            ->whereNotNull('national_id')
            ->groupBy('national_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('national_id');

        foreach ($duplicateNationalIds as $nationalId) {
            $applicationIds = DB::table('applications')
                ->where('national_id', $nationalId)
                ->orderBy('id')
                ->pluck('id');

            $keepId = $applicationIds->shift();

            if ($keepId && $applicationIds->isNotEmpty()) {
                DB::table('applications')
                    ->whereIn('id', $applicationIds)
                    ->update(['national_id' => null]);
            }
        }

        $hasUniqueNationalIdIndex = collect(DB::select("PRAGMA index_list('applications')"))
            ->contains(function ($index) {
                return str_contains($index->name, 'national_id') && (int) $index->unique === 1;
            });

        if (!$hasUniqueNationalIdIndex) {
            Schema::table('applications', function (Blueprint $table) {
                $table->unique('national_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(['national_id']);
            $table->dropColumn(['date_of_birth', 'phone_number']);
        });
    }
};
