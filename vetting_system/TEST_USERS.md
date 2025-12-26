# Test Users for CNMIS System

## All Test User Credentials

Use these credentials to test the system with different roles:

### 1. Admin User
- **Username:** `admin`
- **Password:** `Admin@123`
- **Email:** admin@cnmis.test
- **Role:** Admin
- **Institution:** OPC
- **Permissions:** Full access to all features

### 2. OPC Data Entry
- **Username:** `opc_data_entry`
- **Password:** `DataEntry@123`
- **Email:** opc_data_entry@cnmis.test
- **Role:** OPC Data Entry
- **Institution:** OPC
- **Permissions:** Can create and edit applications

### 3. OPC Approver
- **Username:** `opc_approver`
- **Password:** `Approver@123`
- **Email:** opc_approver@cnmis.test
- **Role:** OPC Approver
- **Institution:** OPC
- **Permissions:** Can approve/deny applications

### 4. Police Officer
- **Username:** `police_officer`
- **Password:** `Police@123`
- **Email:** police@cnmis.test
- **Role:** Police Officer
- **Institution:** Police
- **Permissions:** Can conduct police vetting on assigned applications

### 5. NIS Officer
- **Username:** `nis_officer`
- **Password:** `Nis@123`
- **Email:** nis@cnmis.test
- **Role:** NIS Officer
- **Institution:** NIS
- **Permissions:** Can conduct NIS vetting on assigned applications

## Quick Reference Table

| Role | Username | Password | Access Level |
|------|----------|----------|--------------|
| Admin | `admin` | `Admin@123` | Full access |
| OPC Data Entry | `opc_data_entry` | `DataEntry@123` | Create/Edit applications |
| OPC Approver | `opc_approver` | `Approver@123` | Approve/Deny applications |
| Police Officer | `police_officer` | `Police@123` | Police vetting only |
| NIS Officer | `nis_officer` | `Nis@123` | NIS vetting only |

## Testing Scenarios

### Admin Testing
1. Login as `admin` / `Admin@123`
2. Should see all menu items
3. Can access Admin Panel
4. Can view all applications
5. Can create, edit, delete applications
6. Can manage users, roles, and configurations

### OPC Data Entry Testing
1. Login as `opc_data_entry` / `DataEntry@123`
2. Should see Dashboard, Applications, Create Application, Reports
3. Can create new applications
4. Can only edit applications they created
5. Cannot access Admin Panel
6. Cannot delete applications

### OPC Approver Testing
1. Login as `opc_approver` / `Approver@123`
2. Should see Dashboard, Applications, Reports
3. Can view all applications
4. Can approve/deny applications that are ready
5. Cannot create or edit applications
6. Cannot access Admin Panel

### Police Officer Testing
1. Login as `police_officer` / `Police@123`
2. Should see Dashboard, Applications, Police Vetting, Reports
3. Can only see applications assigned to them
4. Can conduct police vetting on assigned applications
5. Cannot create or edit applications
6. Cannot access Admin Panel

### NIS Officer Testing
1. Login as `nis_officer` / `Nis@123`
2. Should see Dashboard, Applications, NIS Vetting, Reports
3. Can only see applications assigned to them
4. Can conduct NIS vetting on assigned applications
5. Cannot create or edit applications
6. Cannot access Admin Panel

## Seeding Test Users

To create these test users in your database, run:

```bash
cd cnmis-api
php artisan db:seed --class=TestUserSeeder
```

Or seed all data including test users:

```bash
php artisan db:seed
```

## Notes

- All passwords follow the pattern: `[Role]@123`
- All users are active by default
- Users are assigned to their respective institutions
- Roles are assigned using Spatie Laravel Permission package

