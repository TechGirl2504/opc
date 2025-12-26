# CNMIS API Documentation

## Base URL
```
http://localhost:8000/api/v1
```

## Authentication
All protected endpoints require authentication using Laravel Sanctum. Include the token in the Authorization header:

```
Authorization: Bearer {token}
```

## Rate Limiting
- **Limit**: 60 requests per minute per user
- **Headers**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`

## Response Format

### Success Response
```json
{
  "success": true,
  "data": {},
  "message": "Optional success message",
  "meta": {
    "timestamp": "2025-01-26T10:00:00Z"
  }
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Error message",
    "errors": {}
  },
  "meta": {
    "timestamp": "2025-01-26T10:00:00Z"
  }
}
```

## Endpoints

### Authentication

#### Login
```
POST /auth/login
```

**Request Body:**
```json
{
  "username": "string",
  "password": "string"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "username": "admin",
      "email": "admin@cnmis.test",
      "role": "admin",
      "roles": ["admin"],
      "permissions": [],
      "institution": "OPC",
      "institution_id": 1
    },
    "token": "1|..."
  }
}
```

#### Get Authenticated User
```
GET /auth/user
```

#### Logout
```
POST /auth/logout
```

#### Forgot Password
```
POST /auth/forgot-password
```

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

#### Reset Password
```
POST /auth/reset-password
```

**Request Body:**
```json
{
  "token": "string",
  "email": "user@example.com",
  "password": "string",
  "password_confirmation": "string"
}
```

#### Update Password
```
POST /auth/update-password
```

**Request Body:**
```json
{
  "current_password": "string",
  "new_password": "string",
  "new_password_confirmation": "string"
}
```

### Applications

#### List Applications
```
GET /applications
```

**Query Parameters:**
- `search` - Search by application number, name, national ID
- `status` - Filter by status code
- `status_id` - Filter by status ID
- `created_by` - Filter by creator ID
- `date_from` - Filter from date (YYYY-MM-DD)
- `date_to` - Filter to date (YYYY-MM-DD)
- `per_page` - Items per page (default: 20, max: 100)
- `page` - Page number

#### Create Application
```
POST /applications
```
**Required Role:** `admin`, `opc_data_entry`

**Request Body:**
```json
{
  "full_name": "string",
  "national_id": "string (optional)",
  "current_name": "string (optional)",
  "requested_name": "string",
  "reason": "string"
}
```

#### Get Application
```
GET /applications/{id}
```

#### Update Application
```
PUT /applications/{id}
```
**Required Role:** `admin`, `opc_data_entry`

#### Delete Application
```
DELETE /applications/{id}
```
**Required Role:** `admin`

#### Assign to Police
```
POST /applications/{id}/assign-police
```
**Required Role:** `admin`, `opc_data_entry`

**Request Body:**
```json
{
  "police_officer_id": 1
}
```

#### Assign to NIS
```
POST /applications/{id}/assign-nis
```
**Required Role:** `admin`, `opc_data_entry`

**Request Body:**
```json
{
  "nis_officer_id": 1
}
```

#### Get Status History
```
GET /applications/{id}/status
```

### Vetting

#### Get Police Vetting
```
GET /applications/{id}/vetting/police
```

#### Submit Police Vetting
```
POST /applications/{id}/vetting/police
```
**Required Role:** `police_officer`

**Request Body:**
```json
{
  "remarks": "string",
  "findings": "string",
  "recommendation_id": 1,
  "vetting_date": "YYYY-MM-DD",
  "file": "file (optional)"
}
```

#### Update Police Vetting
```
PUT /applications/{id}/vetting/police
```
**Required Role:** `police_officer`

#### Get NIS Vetting
```
GET /applications/{id}/vetting/nis
```

#### Submit NIS Vetting
```
POST /applications/{id}/vetting/nis
```
**Required Role:** `nis_officer`

#### Update NIS Vetting
```
PUT /applications/{id}/vetting/nis
```
**Required Role:** `nis_officer`

### Documents

#### List Documents
```
GET /applications/{id}/documents
```

#### Upload Document
```
POST /applications/{id}/documents
```

**Request Body (multipart/form-data):**
```
file: File
document_type_id: integer
description: string (optional)
```

#### Get Document
```
GET /documents/{id}
```

#### Download Document
```
GET /documents/{id}/download
```

#### Delete Document
```
DELETE /documents/{id}
```

### Decisions

#### Approve Application
```
POST /applications/{id}/approve
```
**Required Role:** `opc_approver`

**Request Body:**
```json
{
  "conditions": "string (optional)"
}
```

#### Deny Application
```
POST /applications/{id}/deny
```
**Required Role:** `opc_approver`

**Request Body:**
```json
{
  "denial_reason": "string"
}
```

#### Get Decision History
```
GET /applications/{id}/decisions
```

### Users (Admin Only)

#### List Users
```
GET /users
```

#### Create User
```
POST /users
```

#### Get User
```
GET /users/{id}
```

#### Update User
```
PUT /users/{id}
```

#### Delete User
```
DELETE /users/{id}
```

#### Activate User
```
POST /users/{id}/activate
```

#### Deactivate User
```
POST /users/{id}/deactivate
```

### Reports

#### Dashboard Statistics
```
GET /reports/dashboard
```

**Query Parameters:**
- `date_from` - Filter from date
- `date_to` - Filter to date

#### Application Reports
```
GET /reports/applications
```

#### Vetting Statistics
```
GET /reports/vetting
```

#### Audit Logs
```
GET /reports/audit
```
**Required Role:** `admin`

#### Export Reports
```
GET /reports/export
```

**Query Parameters:**
- `type` - `applications`, `vetting`, `audit`
- `format` - `csv`, `pdf`
- `date_from`, `date_to`, `status`, etc.

### Notifications

#### List Notifications
```
GET /notifications
```

#### Get Unread Notifications
```
GET /notifications/unread
```

#### Mark as Read
```
POST /notifications/{id}/read
```

#### Mark All as Read
```
POST /notifications/read-all
```

### Admin Configuration (Admin Only)

#### Institutions
```
GET    /admin/institutions
POST   /admin/institutions
GET    /admin/institutions/{id}
PUT    /admin/institutions/{id}
DELETE /admin/institutions/{id}
```

#### Application Statuses
```
GET    /admin/application-statuses
POST   /admin/application-statuses
GET    /admin/application-statuses/{id}
PUT    /admin/application-statuses/{id}
DELETE /admin/application-statuses/{id}
```

#### Vetting Types
```
GET    /admin/vetting-types
POST   /admin/vetting-types
GET    /admin/vetting-types/{id}
PUT    /admin/vetting-types/{id}
DELETE /admin/vetting-types/{id}
```

#### Document Types
```
GET    /admin/document-types
POST   /admin/document-types
GET    /admin/document-types/{id}
PUT    /admin/document-types/{id}
DELETE /admin/document-types/{id}
```

#### Roles
```
GET    /admin/roles
POST   /admin/roles
GET    /admin/roles/{id}
PUT    /admin/roles/{id}
DELETE /admin/roles/{id}
GET    /admin/roles/{id}/permissions
GET    /admin/permissions
```

## Error Codes

- `VALIDATION_ERROR` - Validation failed (422)
- `NOT_FOUND` - Resource not found (404)
- `UNAUTHORIZED` - Unauthorized access (403)
- `UNAUTHENTICATED` - Not authenticated (401)
- `SERVER_ERROR` - Server error (500)
- `ACCOUNT_INACTIVE` - Account is inactive (403)
- `PASSWORD_UPDATE_FAILED` - Password update failed (500)
- `RESET_LINK_FAILED` - Password reset link failed (400)
- `PASSWORD_RESET_FAILED` - Password reset failed (400)

## Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `429` - Too Many Requests (Rate Limit)
- `500` - Server Error

## Notes

- All timestamps are in ISO 8601 format
- File uploads use multipart/form-data
- Pagination metadata is included in list responses
- Rate limit headers are included in all responses

