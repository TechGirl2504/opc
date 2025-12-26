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
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            $table->foreignId('decision_type_id')->constrained('decision_types')->onDelete('restrict');
            $table->foreignId('decision_value_id')->constrained('decision_values')->onDelete('restrict');
            $table->foreignId('decided_by')->constrained('users')->onDelete('restrict');
            $table->text('denial_reason')->nullable();
            $table->text('conditions')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
            
            // Indexes
            $table->index('application_id');
            $table->index('decision_type_id');
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
