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
        // Defensive: some environments have tables created manually/out-of-band.
        // If the table already exists, treat this migration as a no-op so Laravel
        // can record it as "Ran" and continue.
        if (Schema::hasTable('decisions')) {
            return;
        }

        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            // `decision_types` / `decision_values` are created in later migrations; avoid FK ordering issues.
            $table->unsignedBigInteger('decision_type_id');
            $table->unsignedBigInteger('decision_value_id');
            $table->foreignId('decided_by')->constrained('users')->onDelete('restrict');
            $table->text('denial_reason')->nullable();
            $table->text('conditions')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
            
            // Indexes
            $table->index('application_id');
            $table->index('decision_type_id');
            $table->index('decision_value_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};
