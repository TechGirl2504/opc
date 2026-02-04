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
        if (Schema::hasTable('vetting_records')) {
            return;
        }

        Schema::create('vetting_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            // Lookup tables are created in later migrations; avoid FK ordering issues.
            $table->unsignedBigInteger('vetting_type_id');
            $table->foreignId('conducted_by')->constrained('users')->onDelete('restrict');
            $table->unsignedBigInteger('status_id')->default(1);
            $table->text('remarks')->nullable();
            $table->text('findings')->nullable();
            $table->unsignedBigInteger('recommendation_id')->nullable();
            $table->date('vetting_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('application_id');
            $table->index('vetting_type_id');
            $table->index('status_id');
            $table->index('recommendation_id');
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
