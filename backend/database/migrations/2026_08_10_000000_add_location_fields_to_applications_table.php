<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('district', 255)->nullable()->after('national_id');
            $table->string('traditional_authority', 255)->nullable()->after('district');
            $table->string('village', 255)->nullable()->after('traditional_authority');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['district', 'traditional_authority', 'village']);
        });
    }
};
