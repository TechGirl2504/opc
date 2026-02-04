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
        Schema::create('vetting_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('vetting_type_id')->constrained('vetting_types')->onDelete('restrict');
            $table->foreignId('conducted_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('status_id')->default(1)->constrained('vetting_statuses')->onDelete('restrict');
            $table->text('remarks')->nullable();
            $table->text('findings')->nullable();
            $table->foreignId('recommendation_id')->nullable()->constrained('decision_values')->onDelete('set null');
            $table->date('vetting_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('application_id');
            $table->index('vetting_type_id');
            $table->index('status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vetting_records');
    }
};
