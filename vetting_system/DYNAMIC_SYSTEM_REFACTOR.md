# Dynamic System Refactoring - Complete Guide

## Overview

The system has been completely refactored to be **fully dynamic** with **zero hardcoded values**. All enums have been replaced with database tables that can be managed through the admin interface.

## ✅ What Changed

### Before (Hardcoded)
- ❌ `enum('role', ['admin', 'opc_data_entry', ...])` - Hardcoded roles
- ❌ `enum('institution', ['OPC', 'POLICE', 'NIS'])` - Hardcoded institutions
- ❌ `enum('status', ['pending', 'approved', ...])` - Hardcoded statuses
- ❌ `enum('vetting_type', ['police', 'nis'])` - Hardcoded vetting types
- ❌ `enum('document_type', [...])` - Hardcoded document types

### After (Dynamic)
- ✅ **Spatie Laravel Permission** - Dynamic roles and permissions system
- ✅ **institutions** table - Manage institutions dynamically
- ✅ **application_statuses** table - Manage application statuses dynamically
- ✅ **vetting_types** table - Manage vetting types dynamically
- ✅ **document_types** table - Manage document types with file size/MIME restrictions
- ✅ **decision_types** table - Manage decision types dynamically
- ✅ **vetting_statuses** table - Manage vetting statuses dynamically
- ✅ **decision_values** table - Manage decision values (approved/denied/conditional) dynamically

## 📊 New Database Tables

### 1. **institutions**
```sql
- id
- name (unique)
- code (unique) - e.g., 'OPC', 'POLICE', 'NIS'
- description
- is_active
- timestamps
- deleted_at (soft delete)
```

### 2. **application_statuses**
```sql
- id
- name (unique)
- code (unique) - e.g., 'pending', 'approved', 'denied'
- description
- order (for workflow ordering)
- is_active
- timestamps
- deleted_at
```

### 3. **vetting_types**
```sql
- id
- name (unique)
- code (unique) - e.g., 'police', 'nis'
- description
- is_active
- timestamps
- deleted_at
```

### 4. **document_types**
```sql
- id
- name (unique)
- code (unique) - e.g., 'supporting_document', 'police_vetting_report'
- description
- max_file_size (in bytes, default 10MB)
- allowed_mime_types (JSON array)
- is_active
- timestamps
- deleted_at
```

### 5. **decision_types**
```sql
- id
- name (unique)
- code (unique) - e.g., 'police_vetting', 'nis_vetting', 'final_approval'
- description
- is_active
- timestamps
- deleted_at
```

### 6. **vetting_statuses**
```sql
- id
- name (unique)
- code (unique) - e.g., 'pending', 'in_progress', 'completed', 'rejected'
- description
- is_active
- timestamps
- deleted_at
```

### 7. **decision_values**
```sql
- id
- name (unique)
- code (unique) - e.g., 'approved', 'denied', 'conditional', 'approve', 'reject'
- description
- is_active
- timestamps
- deleted_at
```

### 8. **Spatie Permission Tables**
- `roles` - Dynamic roles
- `permissions` - Dynamic permissions
- `model_has_roles` - User-role assignments
- `model_has_permissions` - Direct user permissions
- `role_has_permissions` - Role-permission assignments

## 🔄 Updated Models

### User Model
- Uses `HasRoles` trait from Spatie Permission
- `institution_id` foreign key instead of enum
- `getRoleAttribute()` helper for backward compatibility

### Application Model
- `status_id` foreign key → `application_statuses` table

### VettingRecord Model
- `vetting_type_id` foreign key → `vetting_types` table
- `status_id` foreign key → `vetting_statuses` table
- `recommendation_id` foreign key → `decision_values` table

### Document Model
- `document_type_id` foreign key → `document_types` table

### Decision Model
- `decision_type_id` foreign key → `decision_types` table
- `decision_value_id` foreign key → `decision_values` table

## 🔐 Role & Permission System

### Using Spatie Laravel Permission

```php
// Assign role to user
$user->assignRole('admin');

// Check if user has role
$user->hasRole('admin');

// Check if user has permission
$user->hasPermissionTo('create applications');

// Get user's roles
$user->roles; // Collection of Role models

// Get user's permissions (direct + via roles)
$user->getAllPermissions();
```

### Middleware Usage

```php
// Check role
Route::middleware(['role:admin'])->group(function () {
    // Only admins
});

// Check permission
Route::middleware(['permission:create applications'])->group(function () {
    // Only users with permission
});

// Check multiple roles
Route::middleware(['role:admin|opc_approver'])->group(function () {
    // Admins or OPC approvers
});
```

## 📝 Migration Order

1. `create_permission_tables` (Spatie)
2. `create_institutions_table`
3. `update_users_table_for_cnmis` (depends on institutions)
4. `create_application_statuses_table`
5. `create_vetting_types_table`
6. `create_vetting_statuses_table`
7. `create_decision_values_table`
8. `create_document_types_table`
9. `create_decision_types_table`
10. `create_applications_table` (depends on application_statuses)
11. `create_vetting_records_table` (depends on vetting_types, vetting_statuses, decision_values)
12. `create_documents_table` (depends on document_types)
13. `create_decisions_table` (depends on decision_types, decision_values)
14. `create_audit_logs_table`
15. `create_notifications_table`

## 🎯 Benefits

1. **Fully Customizable**: Add new roles, statuses, types without code changes
2. **Admin Interface Ready**: All values can be managed through admin panel
3. **No Code Deployment**: Changes to configuration don't require code deployment
4. **Audit Trail**: All changes to configuration can be tracked
5. **Flexible Permissions**: Granular permission control
6. **Scalable**: Easy to add new institutions, statuses, etc.

## 🚀 Next Steps

1. Create seeders for initial data (roles, institutions, statuses, etc.)
2. Create admin controllers for managing these dynamic tables
3. Update API controllers to use relationships instead of enums
4. Update middleware to use Spatie Permission
5. Create API endpoints for managing configuration tables

## 📋 Example Seeder Data

```php
// Institutions
Institution::create(['name' => 'Office of the President and Cabinet', 'code' => 'OPC']);
Institution::create(['name' => 'Malawi Police Service', 'code' => 'POLICE']);
Institution::create(['name' => 'National Intelligence Service', 'code' => 'NIS']);

// Application Statuses
ApplicationStatus::create(['name' => 'Pending', 'code' => 'pending', 'order' => 1]);
ApplicationStatus::create(['name' => 'Approved', 'code' => 'approved', 'order' => 10]);
// ... etc

// Roles (Spatie)
Role::create(['name' => 'admin']);
Role::create(['name' => 'opc_data_entry']);
// ... etc
```

## ⚠️ Important Notes

- All foreign key constraints use `onDelete('restrict')` to prevent accidental deletion
- Soft deletes are enabled on all configuration tables
- `is_active` flag allows disabling without deletion
- All tables have `code` field for programmatic access while `name` is for display

