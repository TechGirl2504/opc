<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('applications')) {
            return;
        }

        Schema::table('applications', function (Blueprint $table) {
            // Only add constraints if the referenced tables exist and the local column exists.
            if (Schema::hasColumn('applications', 'status_id') && Schema::hasTable('application_statuses')) {
                $table->foreign('status_id')->references('id')->on('application_statuses')->onDelete('restrict');
            }

            if (Schema::hasColumn('applications', 'created_by') && Schema::hasTable('users')) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            }

            if (Schema::hasColumn('applications', 'assigned_police_officer_id') && Schema::hasTable('users')) {
                $table->foreign('assigned_police_officer_id')->references('id')->on('users')->onDelete('set null');
            }

            if (Schema::hasColumn('applications', 'assigned_nis_officer_id') && Schema::hasTable('users')) {
                $table->foreign('assigned_nis_officer_id')->references('id')->on('users')->onDelete('set null');
            }

            if (Schema::hasColumn('applications', 'assigned_opc_approver_id') && Schema::hasTable('users')) {
                $table->foreign('assigned_opc_approver_id')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('applications')) {
            return;
        }

        Schema::table('applications', function (Blueprint $table) {
            // Drop constraints if they exist (ignore failures).
            foreach ([
                'applications_status_id_foreign',
                'applications_created_by_foreign',
                'applications_assigned_police_officer_id_foreign',
                'applications_assigned_nis_officer_id_foreign',
                'applications_assigned_opc_approver_id_foreign',
            ] as $fkName) {
                try {
                    $table->dropForeign($fkName);
                } catch (\Throwable) {
                    // no-op
                }
            }
        });
    }
};

