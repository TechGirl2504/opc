<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class BootstrapUserSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Creates initial users for staging/production.
         *
         * This seeder is controlled by env vars (no hardcoded passwords).
         *
         * Required:
         * - BOOTSTRAP_USERS=true
         *
         * Optional (defaults shown):
         * - BOOTSTRAP_ADMIN_USERNAME=admin
         * - BOOTSTRAP_ADMIN_EMAIL=admin@example.com
         * - BOOTSTRAP_ADMIN_PASSWORD=...
         *
         * - BOOTSTRAP_OPC_DATA_ENTRY_USERNAME=opc_data_entry
         * - BOOTSTRAP_OPC_DATA_ENTRY_EMAIL=opc_data_entry@example.com
         * - BOOTSTRAP_OPC_DATA_ENTRY_PASSWORD=...
         *
         * - BOOTSTRAP_OPC_APPROVER_USERNAME=opc_approver
         * - BOOTSTRAP_OPC_APPROVER_EMAIL=opc_approver@example.com
         * - BOOTSTRAP_OPC_APPROVER_PASSWORD=...
         *
         * - BOOTSTRAP_POLICE_USERNAME=police_officer
         * - BOOTSTRAP_POLICE_EMAIL=police_officer@example.com
         * - BOOTSTRAP_POLICE_PASSWORD=...
         *
         * - BOOTSTRAP_NIS_USERNAME=nis_officer
         * - BOOTSTRAP_NIS_EMAIL=nis_officer@example.com
         * - BOOTSTRAP_NIS_PASSWORD=...
         *
         * Safety:
         * - Passwords are REQUIRED when creating a user.
         * - Existing users are NOT re-passworded unless BOOTSTRAP_RESET_PASSWORDS=true.
         */

        if (!filter_var((string) env('BOOTSTRAP_USERS', 'false'), FILTER_VALIDATE_BOOL)) {
            return;
        }

        $roles = [
            'admin' => 'admin',
            'opc_data_entry' => 'opc_data_entry',
            'opc_approver' => 'opc_approver',
            'police_officer' => 'police_officer',
            'nis_officer' => 'nis_officer',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $opcInstitution = Institution::where('code', 'OPC')->first();
        $policeInstitution = Institution::where('code', 'POLICE')->first();
        $nisInstitution = Institution::where('code', 'NIS')->first();

        $this->createUserWithRole([
            'username_env' => 'BOOTSTRAP_ADMIN_USERNAME',
            'email_env' => 'BOOTSTRAP_ADMIN_EMAIL',
            'password_env' => 'BOOTSTRAP_ADMIN_PASSWORD',
            'default_username' => 'admin',
            'default_email' => 'admin@example.com',
            'institution_id' => $opcInstitution?->id,
            'role' => 'admin',
        ]);

        $this->createUserWithRole([
            'username_env' => 'BOOTSTRAP_OPC_DATA_ENTRY_USERNAME',
            'email_env' => 'BOOTSTRAP_OPC_DATA_ENTRY_EMAIL',
            'password_env' => 'BOOTSTRAP_OPC_DATA_ENTRY_PASSWORD',
            'default_username' => 'opc_data_entry',
            'default_email' => 'opc_data_entry@example.com',
            'institution_id' => $opcInstitution?->id,
            'role' => 'opc_data_entry',
        ]);

        $this->createUserWithRole([
            'username_env' => 'BOOTSTRAP_OPC_APPROVER_USERNAME',
            'email_env' => 'BOOTSTRAP_OPC_APPROVER_EMAIL',
            'password_env' => 'BOOTSTRAP_OPC_APPROVER_PASSWORD',
            'default_username' => 'opc_approver',
            'default_email' => 'opc_approver@example.com',
            'institution_id' => $opcInstitution?->id,
            'role' => 'opc_approver',
        ]);

        $this->createUserWithRole([
            'username_env' => 'BOOTSTRAP_POLICE_USERNAME',
            'email_env' => 'BOOTSTRAP_POLICE_EMAIL',
            'password_env' => 'BOOTSTRAP_POLICE_PASSWORD',
            'default_username' => 'police_officer',
            'default_email' => 'police_officer@example.com',
            'institution_id' => $policeInstitution?->id,
            'role' => 'police_officer',
        ]);

        $this->createUserWithRole([
            'username_env' => 'BOOTSTRAP_NIS_USERNAME',
            'email_env' => 'BOOTSTRAP_NIS_EMAIL',
            'password_env' => 'BOOTSTRAP_NIS_PASSWORD',
            'default_username' => 'nis_officer',
            'default_email' => 'nis_officer@example.com',
            'institution_id' => $nisInstitution?->id,
            'role' => 'nis_officer',
        ]);
    }

    /**
     * @param array{
     *   username_env: string,
     *   email_env: string,
     *   password_env: string,
     *   default_username: string,
     *   default_email: string,
     *   institution_id: int|null,
     *   role: string,
     * } $cfg
     */
    private function createUserWithRole(array $cfg): void
    {
        $username = trim((string) env($cfg['username_env'], $cfg['default_username']));
        $email = trim((string) env($cfg['email_env'], $cfg['default_email']));
        $password = (string) env($cfg['password_env'], '');
        $resetPasswords = filter_var((string) env('BOOTSTRAP_RESET_PASSWORDS', 'false'), FILTER_VALIDATE_BOOL);

        $user = User::withTrashed()->firstOrNew(['username' => $username]);
        $user->email = $email !== '' ? $email : null;

        // Only set/reset password when explicitly requested.
        if (!$user->exists || $resetPasswords) {
            if ($password === '') {
                throw new \RuntimeException("Missing required env var {$cfg['password_env']} for bootstrap user '{$username}'.");
            }
            $user->password = Hash::make($password);
        }
        $user->institution_id = $cfg['institution_id'];
        $user->is_active = true;

        // If the record existed but was soft-deleted, restore it.
        $user->deleted_at = null;

        // Ensure remember_token exists (some old schemas may rely on it)
        if (!$user->remember_token) {
            $user->remember_token = Str::random(60);
        }

        $user->save();

        if (!$user->hasRole($cfg['role'])) {
            $user->assignRole($cfg['role']);
        }
    }
}

