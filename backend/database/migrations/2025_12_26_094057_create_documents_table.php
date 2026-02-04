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
        if (Schema::hasTable('documents')) {
            return;
        }

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->onDelete('cascade');
            // `document_types` is created in a later migration; avoid FK ordering issues.
            $table->unsignedBigInteger('document_type_id');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->bigInteger('file_size'); // in bytes
            $table->string('mime_type', 100);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            $table->text('description')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('application_id');
            $table->index('document_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
