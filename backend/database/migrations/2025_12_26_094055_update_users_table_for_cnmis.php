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
        /**
         * This migration is intentionally defensive.
         *
         * Some environments may already have a partially-migrated `users` table
         * (e.g. `name` already removed). We only apply changes when needed so
         * `php artisan migrate --force` is safe to run repeatedly.
         *
         * Important: we DO NOT add the `institutions` foreign key here because
         * the `institutions` table is created in a later migration. A follow-up
         * migration can add the constraint after both exist.
         */
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'name')) {
                $table->dropColumn('name');
            }

            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('id');
            }

            // Keep `email` as-is if it already exists. (Altering columns via `change()`
            // may require doctrine/dbal depending on environment.)
            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->after('username');
            }

            if (!Schema::hasColumn('users', 'institution_id')) {
                $table->unsignedBigInteger('institution_id')->nullable()->after('email');
            }

            if (!Schema::hasColumn('users', 'profile_picture')) {
                $table->string('profile_picture')->nullable()->after('institution_id');
            }

            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('profile_picture');
            }

            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('is_active');
            }

            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Best-effort rollback; only drop columns/keys if present.
            if (Schema::hasColumn('users', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            // Foreign key may be added by a later migration; only drop if column exists.
            if (Schema::hasColumn('users', 'institution_id')) {
                // Drop FK if present (ignore if not).
                try {
                    $table->dropForeign(['institution_id']);
                } catch (\Throwable) {
                    // no-op
                }
            }

            $drop = array_values(array_filter([
                Schema::hasColumn('users', 'username') ? 'username' : null,
                Schema::hasColumn('users', 'institution_id') ? 'institution_id' : null,
                Schema::hasColumn('users', 'profile_picture') ? 'profile_picture' : null,
                Schema::hasColumn('users', 'is_active') ? 'is_active' : null,
                Schema::hasColumn('users', 'last_login_at') ? 'last_login_at' : null,
            ]));

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }

            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->after('id');
            }
        });
    }
};
