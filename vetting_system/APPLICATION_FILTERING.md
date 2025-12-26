# Application Filtering by User Role

## Issue
All users were seeing all applications regardless of their role. This has been fixed.

## Solution Implemented

### Backend Changes

1. **ApplicationService** - Added role-based filtering:
   - **Police Officers**: Only see applications assigned to them (`assigned_police_officer_id`)
   - **NIS Officers**: Only see applications assigned to them (`assigned_nis_officer_id`)
   - **OPC Data Entry**: Only see applications they created (`created_by`)
   - **OPC Approver**: See all applications (no filter)
   - **Admin**: See all applications (no filter)

2. **ApplicationController** - Now passes the authenticated user to the service

3. **TestApplicationSeeder** - Updated to:
   - Assign some applications to police officers
   - Assign some applications to NIS officers
   - Create applications in different statuses

## What Each Role Sees

### Admin (`admin` / `Admin@123`)
- **Sees**: ALL applications (no filtering)
- **Can**: Create, edit, delete, assign officers, approve/deny

### OPC Data Entry (`opc_data_entry` / `DataEntry@123`)
- **Sees**: Only applications they created
- **Can**: Create new applications, edit their own applications

### OPC Approver (`opc_approver` / `Approver@123`)
- **Sees**: ALL applications (no filtering)
- **Can**: Approve/deny applications that are ready

### Police Officer (`police_officer` / `Police@123`)
- **Sees**: Only applications assigned to them
- **Can**: Conduct police vetting on assigned applications
- **Note**: If no applications are assigned, they won't see any

### NIS Officer (`nis_officer` / `Nis@123`)
- **Sees**: Only applications assigned to them
- **Can**: Conduct NIS vetting on assigned applications
- **Note**: If no applications are assigned, they won't see any

## How to Assign Applications to Officers

### Option 1: Via Admin/OPC Data Entry Interface
1. Login as `admin` or `opc_data_entry`
2. Go to Applications
3. Click on an application
4. Use the "Assign Police Officer" or "Assign NIS Officer" buttons
5. Select the officer from the dropdown

### Option 2: Via API
```bash
# Assign to Police Officer
POST /api/v1/applications/{id}/assign-police
{
  "user_id": <police_officer_id>
}

# Assign to NIS Officer
POST /api/v1/applications/{id}/assign-nis
{
  "user_id": <nis_officer_id>
}
```

### Option 3: Re-seed with Assignments
```bash
cd cnmis-api
php artisan db:seed --class=TestApplicationSeeder
```

The updated seeder will create applications with some already assigned to officers.

## Testing the Filtering

1. **Login as Admin**:
   - Should see all 6 test applications
   - Can assign applications to officers

2. **Login as OPC Data Entry**:
   - Should see only applications they created (all 6 if they created them)
   - Cannot see applications created by others

3. **Login as Police Officer**:
   - Should see only applications assigned to them
   - If none assigned, will see empty list
   - After assignment, will see those applications

4. **Login as NIS Officer**:
   - Should see only applications assigned to them
   - If none assigned, will see empty list
   - After assignment, will see those applications

5. **Login as OPC Approver**:
   - Should see all applications
   - Can approve/deny ready applications

## Current Test Data

After running the updated seeder:
- Some applications are assigned to police officers
- Some applications are assigned to NIS officers
- Applications are in different statuses (pending, police_vetting, nis_vetting)

## Notes

- The filtering happens automatically on the backend
- Frontend doesn't need to send additional filters for role-based access
- Officers will only see applications in their "My Applications" and "Vetting" views if assigned
- Admin and OPC Approver always see everything for oversight purposes

