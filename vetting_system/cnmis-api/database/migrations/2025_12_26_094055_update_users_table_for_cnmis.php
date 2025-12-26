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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['name']);
            $table->string('username')->unique()->after('id');
            $table->string('email')->nullable()->change();
            // Remove enum for institution, use foreign key instead
            $table->foreignId('institution_id')->nullable()->after('email')->constrained('institutions')->onDelete('set null');
            $table->string('profile_picture')->nullable()->after('institution_id');
            $table->boolean('is_active')->default(true)->after('profile_picture');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->softDeletes();

            // Indexes
            $table->index('username');
            $table->index('email');
            $table->index('institution_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['username']);
            $table->dropIndex(['email']);
            $table->dropIndex(['institution_id']);
            $table->dropSoftDeletes();
            $table->dropForeign(['institution_id']);
            $table->dropColumn([
                'username',
                'institution_id',
                'profile_picture',
                'is_active',
                'last_login_at'
            ]);
            $table->string('name')->after('id');
            $table->string('email')->nullable(false)->change();
        });
    }
};
