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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 50)->unique();
            $table->string('full_name', 255);
            $table->string('national_id', 20)->nullable();
            $table->string('current_name', 255)->nullable();
            $table->string('requested_name', 255);
            $table->text('reason');
            $table->foreignId('status_id')->default(1)->constrained('application_statuses')->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_police_officer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_nis_officer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('assigned_opc_approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('police_vetting_completed_at')->nullable();
            $table->timestamp('nis_vetting_completed_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('application_number');
            $table->index('status_id');
            $table->index('created_by');
            $table->index('national_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
