# Role-Based Access Control (RBAC) Implementation

## Overview
The frontend now implements comprehensive role-based access control (RBAC) that ensures users only see and can access features they're permitted to use based on their roles and permissions.

## Auth Store Enhancements

### New Computed Properties
- `userPermissions` - Array of all user permissions
- `isPoliceOfficer` - Boolean for police officer role
- `isNisOfficer` - Boolean for NIS officer role
- `isOpcDataEntry` - Boolean for OPC data entry role
- `isOpcApprover` - Boolean for OPC approver role
- `canViewAllApplications` - Can view all applications (admin, opc_data_entry, opc_approver)
- `canEditApplications` - Can create/edit applications (admin, opc_data_entry)
- `canDeleteApplications` - Can delete applications (admin only)
- `canAssignOfficers` - Can assign officers to applications (admin, opc_data_entry)

### New Methods
- `hasPermission(permission: string)` - Check if user has a specific permission
- `hasAnyPermission(permissions: string[])` - Check if user has any of the specified permissions

## Route-Level Protection

Routes are protected with role-based guards:
- `/applications/create` - Requires `admin` or `opc_data_entry`
- `/applications/:id/vetting/police` - Requires `police_officer` or `admin`
- `/applications/:id/vetting/nis` - Requires `nis_officer` or `admin`
- `/admin` - Requires `admin` only

## Menu Visibility

### Admin Layout
- **Dashboard** - Visible to all authenticated users
- **Applications** - Visible to all authenticated users
- **Create Application** - Only visible if `canEditApplications`
- **Reports** - Visible to all authenticated users
- **Admin Panel** - Only visible if `isAdmin`

### Officer Layout
- **Dashboard** - Visible to all authenticated users
- **Applications** - Label changes to "My Applications" for non-admin users
- **Create Application** - Only visible if `canEditApplications`
- **Police Vetting** - Only visible if `isPoliceOfficer`
- **NIS Vetting** - Only visible if `isNisOfficer`
- **Reports** - Visible to all authenticated users

## Component-Level Access Control

### Applications List (`List.vue`)
- **Create Button** - Only shown if `canEditApplications`
- **Edit Button** - Only shown if `canEditApplications` AND user created the application (or is admin)
- **Delete Button** - Only shown if `canDeleteApplications` (admin only)

### Application Detail (`Show.vue`)
- **Edit Application** - Only shown if user created the application (or is admin)
- **Upload Document** - Only shown if `canEditApplications`
- **Police Vetting Button** - Only shown if:
  - User is admin, OR
  - User is police officer AND assigned to this application
- **NIS Vetting Button** - Only shown if:
  - User is admin, OR
  - User is NIS officer AND assigned to this application
- **Approve Button** - Only shown if:
  - User is OPC approver AND application is ready for approval
- **Deny Button** - Only shown if:
  - User is OPC approver AND application can be denied

### Reports View (`Index.vue`)
- **Audit Log Tab** - Only visible if `isAdmin`
- Data is automatically filtered by the backend based on user role

### Vetting Views
- **Police Vetting** - Only accessible if user is police officer assigned to the application (or admin)
- **NIS Vetting** - Only accessible if user is NIS officer assigned to the application (or admin)

### Admin Panel
- All tabs are only accessible to admin users
- User management, configuration management, and role management are admin-only

## Data Filtering

The backend automatically filters data based on user roles:
- **Police Officers** - Only see applications assigned to them
- **NIS Officers** - Only see applications assigned to them
- **OPC Data Entry** - Only see applications they created
- **OPC Approver** - See all applications
- **Admin** - See all applications

## Permission Checks

In addition to role checks, the system supports permission-based checks:
- `hasPermission(permission)` - Check for specific permission
- `hasAnyPermission(permissions[])` - Check for any of the specified permissions
- Admin users automatically have all permissions

## Security Features

1. **Route Guards** - Prevent unauthorized route access
2. **Component Guards** - Hide/show UI elements based on permissions
3. **Action Guards** - Disable actions user cannot perform
4. **Data Filtering** - Backend filters data based on user role
5. **Assignment Checks** - Officers can only vet assigned applications

## Testing RBAC

To test the RBAC implementation:

1. **Login as Admin** - Should see all features and all data
2. **Login as OPC Data Entry** - Should only see applications they created, can create new applications
3. **Login as Police Officer** - Should only see assigned applications, can only do police vetting on assigned apps
4. **Login as NIS Officer** - Should only see assigned applications, can only do NIS vetting on assigned apps
5. **Login as OPC Approver** - Should see all applications, can approve/deny ready applications

## Notes

- All permission checks are done client-side for UX, but the backend enforces the actual security
- If a user tries to access a route they don't have permission for, they're redirected to `/unauthorized`
- Menu items are dynamically shown/hidden based on user permissions
- Action buttons are conditionally rendered based on user permissions and application state

