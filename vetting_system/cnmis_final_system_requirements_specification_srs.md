# OFFICE OF THE PRESIDENT AND CABINET

## CHANGE OF NAME MANAGEMENT INFORMATION SYSTEM (CNMIS)

### FINAL SYSTEM DESIGN, ARCHITECTURE AND USER STORIES

**Prepared by:** ICT Section, Office of the President and Cabinet  
**Date:** December 2025

---

## TABLE OF CONTENTS

1. Introduction
2. Design Objectives
3. Overall System Architecture
4. Logical Architecture (UML – Component View)
5. Physical Deployment Architecture
6. Module Design
7. UML Use Case Diagrams (Canva Ready)
8. UML Activity Workflow Diagram (Canva Ready)
9. API Architecture (Laravel-Based)
10. User Stories
11. Application Workflow States
12. Database Schema (Detailed)
13. Validation Rules and Business Logic
14. File Upload and Document Management
15. Notification System
16. Search and Filtering Capabilities
17. Performance Requirements
18. Error Handling and Logging
19. Migration Strategy from Current System
20. Testing Requirements
21. Deployment Procedures

---

## 1. Introduction

This document presents the final system design and architecture for the Change of Name Management Information System (CNMIS). It is derived directly from the approved System Requirements Specification (SRS) and provides a clear technical blueprint for system implementation.

The document is intended for developers, system reviewers, ICT management, and implementation partners.

---

## 2. Design Objectives

The system design aims to:
- Support a clear and auditable approval workflow
- Integrate OPC, Malawi Police Service, and National Intelligence Service
- Ensure security, accountability, and role separation
- Provide a scalable foundation using a Laravel API architecture

---

## 3. Overall System Architecture

CNMIS will be implemented as a centralized, web-based system using a three-tier architecture:

- Presentation Layer (Web Interface)
- Application Layer (Laravel API)
- Data Layer (Centralized Database and Document Storage)

The system will be accessed securely by OPC, Police, and NIS through role-based dashboards.

---

## 4. Logical Architecture (UML Component Diagram – Canva Ready)

```
+----------------------------+
|      Web Browser UI        |
|  (OPC / Police / NIS)      |
+-------------+--------------+
              |
              v
+--------------------------------------------+
|        CNMIS Application (Laravel)          |
|--------------------------------------------|
| - Authentication & Authorization            |
| - Application Management                    |
| - Vetting Management                        |
| - Approval & Decision Management            |
| - Document Management                       |
| - Reporting & Audit Logging                 |
+----------------------+---------------------+
                       |
                       v
+--------------------------------------------+
|           Central CNMIS Database            |
|--------------------------------------------|
| - Applications                              |
| - Vetting Records                           |
| - Users & Roles                             |
| - Audit Logs                                |
+--------------------------------------------+
                       |
                       v
+--------------------------------------------+
|        Secure Document Storage              |
|  (Scanned Vetting Documents)                |
+--------------------------------------------+
```

*This diagram can be recreated in Canva using rectangles and directional connectors.*

---

## 5. Physical Deployment Architecture

- Application Server hosted within Government infrastructure
- Database Server with regular backups
- Secure internal network access for OPC, Police, and NIS
- HTTPS enforced for all communications

---

## 6. Module Design

### 6.1 User Management Module
- User registration and role assignment
- Role-based access control

### 6.2 Application Management Module
- Capture and update change of name applications
- Upload supporting documents

### 6.3 Vetting Module
- Police vetting interface
- NIS vetting interface
- Upload of scanned vetting reports

### 6.4 Approval Module
- Review consolidated vetting results
- Record final approval or rejection

### 6.5 Reporting and Audit Module
- Application status reports
- Vetting statistics
- Audit trail of actions

---

## 7. UML Use Case Diagrams (Canva Ready)

```
Actors:
- Applicant
- OPC Data Entry Officer
- Police Officer
- NIS Officer
- OPC Approving Officer
- System Administrator

Use Cases:
- Register Application
- Upload Supporting Documents
- Conduct Police Vetting
- Upload Police Vetting Report
- Review Police Vetting (OPC)
- Conduct NIS Vetting
- Upload NIS Vetting Report
- Approve or Reject Application
- Track Application Status
- Manage Users and Roles
- Generate Reports
```

*These actors and use cases can be visually arranged in Canva using UML use case symbols.*

---

## 8. UML Activity Workflow Diagram (Canva Ready)

```
Start
  |
  v
OPC Receives Application
  |
  v
Capture Application Data
  |
  v
Police Vetting
  |
  v
Upload Police Vetting Report
  |
  v
OPC Administrative Review
  |
  v
NIS Vetting (All Cases)
  |
  v
Upload NIS Vetting Report
  |
  v
Final OPC Approval / Rejection
  |
  v
Publication and Archiving
  |
  v
End
```

*This directly maps to a UML Activity Diagram for Canva.*

---

## 9. API Architecture (Laravel-Based)

### 9.1 Authentication and Security
- **Authentication Method**: Laravel Sanctum (token-based API authentication)
- **Token Expiration**: 24 hours (configurable)
- **Refresh Tokens**: Supported for extended sessions
- **Middleware**: Role-based access control middleware
- **Rate Limiting**: 60 requests per minute per user
- **CORS**: Configured for allowed origins only

### 9.2 API Versioning
- Base URL: `/api/v1/`
- Version header support: `Accept: application/vnd.cnmis.v1+json`

### 9.3 Core API Endpoints

#### Authentication Endpoints
```
POST   /api/v1/auth/login          - User login
POST   /api/v1/auth/logout          - User logout
POST   /api/v1/auth/refresh         - Refresh access token
GET    /api/v1/auth/user            - Get authenticated user
POST   /api/v1/auth/password/reset  - Request password reset
POST   /api/v1/auth/password/update - Update password
```

#### Application Endpoints
```
GET    /api/v1/applications                    - List applications (with filters)
POST   /api/v1/applications                    - Create new application
GET    /api/v1/applications/{id}               - Get application details
PUT    /api/v1/applications/{id}               - Update application (OPC Data Entry only)
DELETE /api/v1/applications/{id}               - Delete application (Admin only)
POST   /api/v1/applications/{id}/assign-police - Assign to Police officer
POST   /api/v1/applications/{id}/assign-nis    - Assign to NIS officer
GET    /api/v1/applications/{id}/status        - Get application status history
```

#### Vetting Endpoints
```
GET    /api/v1/applications/{id}/vetting/police     - Get police vetting record
POST   /api/v1/applications/{id}/vetting/police     - Submit police vetting
PUT    /api/v1/applications/{id}/vetting/police     - Update police vetting
GET    /api/v1/applications/{id}/vetting/nis        - Get NIS vetting record
POST   /api/v1/applications/{id}/vetting/nis        - Submit NIS vetting
PUT    /api/v1/applications/{id}/vetting/nis        - Update NIS vetting
```

#### Document Endpoints
```
GET    /api/v1/applications/{id}/documents          - List application documents
POST   /api/v1/applications/{id}/documents          - Upload document
GET    /api/v1/documents/{id}                       - Get document details
DELETE /api/v1/documents/{id}                       - Delete document
GET    /api/v1/documents/{id}/download              - Download document
```

#### Decision Endpoints
```
POST   /api/v1/applications/{id}/approve            - Approve application
POST   /api/v1/applications/{id}/deny               - Deny application
GET    /api/v1/applications/{id}/decisions          - Get decision history
```

#### User Management Endpoints (Admin only)
```
GET    /api/v1/users                                - List users
POST   /api/v1/users                                - Create user
GET    /api/v1/users/{id}                           - Get user details
PUT    /api/v1/users/{id}                           - Update user
DELETE /api/v1/users/{id}                           - Delete user (soft delete)
POST   /api/v1/users/{id}/activate                  - Activate user
POST   /api/v1/users/{id}/deactivate                - Deactivate user
```

#### Reporting Endpoints
```
GET    /api/v1/reports/dashboard                    - Dashboard statistics
GET    /api/v1/reports/applications                - Application reports
GET    /api/v1/reports/vetting                     - Vetting statistics
GET    /api/v1/reports/audit                       - Audit logs (Admin only)
GET    /api/v1/reports/export                      - Export reports (CSV/PDF)
```

#### Notification Endpoints
```
GET    /api/v1/notifications                        - List user notifications
GET    /api/v1/notifications/unread                - Get unread notifications
POST   /api/v1/notifications/{id}/read              - Mark notification as read
POST   /api/v1/notifications/read-all               - Mark all as read
```

### 9.4 API Response Format
All API responses follow a consistent format:
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "meta": {
    "timestamp": "2025-12-01T10:00:00Z"
  }
}
```

Error responses:
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Validation failed",
    "errors": {
      "field_name": ["Error message"]
    }
  },
  "meta": {
    "timestamp": "2025-12-01T10:00:00Z"
  }
}
```

### 9.5 API Documentation
- Swagger/OpenAPI documentation
- Postman collection for testing
- Example requests and responses
- Authentication examples

---

## 10. User Stories

### Administrator
- As an administrator, I want to manage users and roles so that access is controlled.
- As an administrator, I want to view audit logs so that actions are traceable.

### OPC Data Entry Officer
- As an OPC officer, I want to register applications so that requests are digitized.
- As an OPC officer, I want to upload documents so that records are complete.

### Police Officer
- As a police officer, I want to conduct vetting so that applications are verified.
- As a police officer, I want to upload scanned vetting reports so that findings are recorded.

### NIS Officer
- As an NIS officer, I want to conduct intelligence vetting so that security risks are assessed.

### OPC Approving Officer
- As an approving officer, I want to review all vetting results so that I can make informed decisions.
- As an approving officer, I want to approve or reject applications so that outcomes are finalised.

---

## 11. Application Workflow States

The system will track applications through the following states:

1. **pending** - Initial state when application is registered by OPC Data Entry Officer
2. **police_vetting** - Application assigned to Police for vetting
3. **police_completed** - Police vetting completed, awaiting OPC review
4. **opc_review** - OPC administrative review after police vetting
5. **nis_vetting** - Application assigned to NIS for intelligence vetting
6. **nis_completed** - NIS vetting completed, awaiting final decision
7. **pending_approval** - All vetting completed, awaiting final OPC approval/rejection
8. **approved** - Application approved by OPC Approving Officer
9. **denied** - Application rejected by OPC Approving Officer
10. **archived** - Application archived after completion

### State Transitions

- `pending` → `police_vetting` (when assigned to Police)
- `police_vetting` → `police_completed` (when Police uploads report)
- `police_completed` → `opc_review` (automatic transition)
- `opc_review` → `nis_vetting` (when OPC approves for NIS vetting)
- `nis_vetting` → `nis_completed` (when NIS uploads report)
- `nis_completed` → `pending_approval` (automatic transition)
- `pending_approval` → `approved` (when OPC Approving Officer approves)
- `pending_approval` → `denied` (when OPC Approving Officer rejects)
- `approved` → `archived` (after publication period)
- `denied` → `archived` (after retention period)

---

## 12. Database Schema (Detailed)

### 12.1 Users Table
```sql
users
- id (PK, BIGINT, AUTO_INCREMENT)
- username (VARCHAR(255), UNIQUE, NOT NULL)
- email (VARCHAR(255), UNIQUE, NULLABLE)
- password (VARCHAR(255), NOT NULL) - bcrypt hashed
- role (ENUM: 'admin', 'opc_data_entry', 'opc_approver', 'police_officer', 'nis_officer'), NOT NULL
- institution (ENUM: 'OPC', 'POLICE', 'NIS'), NOT NULL
- profile_picture (VARCHAR(255), NULLABLE)
- is_active (BOOLEAN, DEFAULT TRUE)
- last_login_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- deleted_at (TIMESTAMP, NULLABLE) - Soft delete
```

### 12.2 Applications Table
```sql
applications (renamed from 'clients' in current system)
- id (PK, BIGINT, AUTO_INCREMENT)
- application_number (VARCHAR(50), UNIQUE, NOT NULL) - Auto-generated
- full_name (VARCHAR(255), NOT NULL)
- national_id (VARCHAR(20), NULLABLE) - Format: 8 alphanumeric uppercase
- current_name (VARCHAR(255), NULLABLE)
- requested_name (VARCHAR(255), NOT NULL)
- reason (TEXT, NOT NULL)
- status (ENUM: 'pending', 'police_vetting', 'police_completed', 'opc_review', 'nis_vetting', 'nis_completed', 'pending_approval', 'approved', 'denied', 'archived'), DEFAULT 'pending'
- created_by (FK -> users.id), NOT NULL
- assigned_police_officer_id (FK -> users.id), NULLABLE
- assigned_nis_officer_id (FK -> users.id), NULLABLE
- assigned_opc_approver_id (FK -> users.id), NULLABLE
- submitted_at (TIMESTAMP, NULLABLE)
- police_vetting_completed_at (TIMESTAMP, NULLABLE)
- nis_vetting_completed_at (TIMESTAMP, NULLABLE)
- decided_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- deleted_at (TIMESTAMP, NULLABLE)
```

### 12.3 Documents Table
```sql
documents
- id (PK, BIGINT, AUTO_INCREMENT)
- application_id (FK -> applications.id), NOT NULL
- document_type (ENUM: 'supporting_document', 'police_vetting_report', 'nis_vetting_report', 'other'), NOT NULL
- file_name (VARCHAR(255), NOT NULL)
- file_path (VARCHAR(500), NOT NULL)
- file_size (BIGINT, NOT NULL) - in bytes
- mime_type (VARCHAR(100), NOT NULL)
- uploaded_by (FK -> users.id), NOT NULL
- description (TEXT, NULLABLE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### 12.4 Vetting Records Table
```sql
vetting_records
- id (PK, BIGINT, AUTO_INCREMENT)
- application_id (FK -> applications.id), NOT NULL
- vetting_type (ENUM: 'police', 'nis'), NOT NULL
- conducted_by (FK -> users.id), NOT NULL
- status (ENUM: 'pending', 'in_progress', 'completed', 'rejected'), DEFAULT 'pending'
- remarks (TEXT, NULLABLE)
- findings (TEXT, NULLABLE)
- recommendation (ENUM: 'approve', 'reject', 'conditional'), NULLABLE
- vetting_date (DATE, NULLABLE)
- completed_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### 12.5 Decisions Table
```sql
decisions
- id (PK, BIGINT, AUTO_INCREMENT)
- application_id (FK -> applications.id), NOT NULL
- decision_type (ENUM: 'police_vetting', 'nis_vetting', 'final_approval'), NOT NULL
- decision (ENUM: 'approved', 'denied', 'conditional'), NOT NULL
- decided_by (FK -> users.id), NOT NULL
- denial_reason (TEXT, NULLABLE) - Required if decision is 'denied'
- conditions (TEXT, NULLABLE) - Required if decision is 'conditional'
- decided_at (TIMESTAMP, NOT NULL)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### 12.6 Audit Logs Table
```sql
audit_logs
- id (PK, BIGINT, AUTO_INCREMENT)
- user_id (FK -> users.id), NULLABLE - NULL for system actions
- action (VARCHAR(100), NOT NULL) - e.g., 'application_created', 'application_approved', 'user_created'
- model_type (VARCHAR(100), NULLABLE) - Laravel model class
- model_id (BIGINT, NULLABLE) - ID of affected record
- old_values (JSON, NULLABLE) - Previous state
- new_values (JSON, NULLABLE) - New state
- ip_address (VARCHAR(45), NULLABLE)
- user_agent (TEXT, NULLABLE)
- created_at (TIMESTAMP)
```

### 12.7 Notifications Table
```sql
notifications
- id (PK, BIGINT, AUTO_INCREMENT)
- user_id (FK -> users.id), NOT NULL
- type (VARCHAR(100), NOT NULL) - e.g., 'application_assigned', 'vetting_completed'
- title (VARCHAR(255), NOT NULL)
- message (TEXT, NOT NULL)
- related_model_type (VARCHAR(100), NULLABLE)
- related_model_id (BIGINT, NULLABLE)
- is_read (BOOLEAN, DEFAULT FALSE)
- read_at (TIMESTAMP, NULLABLE)
- created_at (TIMESTAMP)
```

### 12.8 Indexes
- `users`: INDEX on `username`, `email`, `role`, `institution`
- `applications`: INDEX on `application_number`, `status`, `created_by`, `national_id`
- `documents`: INDEX on `application_id`, `document_type`
- `vetting_records`: INDEX on `application_id`, `vetting_type`, `status`
- `decisions`: INDEX on `application_id`, `decision_type`
- `audit_logs`: INDEX on `user_id`, `action`, `created_at`
- `notifications`: INDEX on `user_id`, `is_read`, `created_at`

---

## 13. Validation Rules and Business Logic

### 13.1 Application Registration
- **Full Name**: Required, min 2 characters, max 255 characters, alphabetic and spaces only
- **National ID**: Optional, must match pattern `^[A-Z0-9]{8}$` if provided
- **Current Name**: Optional, max 255 characters
- **Requested Name**: Required, min 2 characters, max 255 characters, must differ from current_name
- **Reason**: Required, min 10 characters, max 5000 characters
- **Documents**: Optional, max 5 files per application, max 10MB per file, allowed types: PDF, JPG, JPEG, PNG

### 13.2 User Management
- **Username**: Required, min 3 characters, max 50 characters, alphanumeric and underscore only, unique
- **Email**: Optional, must be valid email format if provided, unique
- **Password**: Required, min 8 characters, must contain at least one uppercase, one lowercase, one number
- **Role**: Required, must be valid role enum value
- **Institution**: Required, must match role (OPC users can have OPC roles, Police users can only have police_officer role, etc.)

### 13.3 Vetting Rules
- Police vetting must be completed before NIS vetting can begin
- NIS vetting is mandatory for all applications
- Both vetting reports must be uploaded before final approval
- Vetting officers can only access applications assigned to them or their institution

### 13.4 Approval Rules
- Final approval can only be made by OPC Approving Officer
- Approval requires both Police and NIS vetting to be completed
- Denial requires a mandatory denial reason (min 10 characters)
- Once approved or denied, application status cannot be changed except by system administrator

---

## 14. File Upload and Document Management

### 14.1 File Upload Specifications
- **Maximum File Size**: 10MB per file
- **Allowed File Types**: PDF, JPG, JPEG, PNG
- **Storage Location**: `storage/app/documents/{year}/{month}/{application_id}/`
- **File Naming**: `{timestamp}_{sanitized_filename}_{random_string}.{ext}`
- **Virus Scanning**: All uploaded files must be scanned before storage
- **File Validation**: Server-side validation of MIME type and file extension

### 14.2 Document Types
1. **Supporting Documents**: Initial documents uploaded with application
2. **Police Vetting Report**: Scanned report from Police Service
3. **NIS Vetting Report**: Scanned report from National Intelligence Service
4. **Other Documents**: Additional documents as needed

### 14.3 Document Access Control
- Users can only view documents for applications they have access to
- Document download is logged in audit trail
- Documents are encrypted at rest
- Documents are served through secure download endpoints with authentication

### 14.4 Document Retention
- Approved applications: Retain documents for 10 years
- Denied applications: Retain documents for 5 years
- Archived applications: Move to cold storage after retention period

---

## 15. Notification System

### 15.1 Notification Types
1. **Application Assigned**: Notify when application is assigned to a vetting officer
2. **Vetting Completed**: Notify OPC when Police or NIS vetting is completed
3. **Approval Required**: Notify OPC Approving Officer when application is ready for final decision
4. **Application Status Changed**: Notify relevant users when application status changes
5. **Document Uploaded**: Notify when new documents are uploaded
6. **System Alerts**: Notify administrators of system issues or important events

### 15.2 Notification Delivery
- **In-App Notifications**: Real-time notifications in the web interface
- **Email Notifications**: Optional email notifications for critical events
- **Notification Preferences**: Users can configure which notifications they receive

### 15.3 Notification Display
- Notification badge showing unread count
- Notification dropdown/list in navigation
- Mark as read functionality
- Bulk mark as read
- Notification history (last 30 days)

---

## 16. Search and Filtering Capabilities

### 16.1 Application Search
- Search by: Application number, Full name, National ID, Current name, Requested name
- Full-text search across application fields
- Date range filtering (submitted date, decision date)
- Status filtering (all workflow states)
- Institution filtering (OPC, Police, NIS)
- Assigned officer filtering

### 16.2 Advanced Filters
- Filter by date ranges (created, submitted, decided)
- Filter by status combinations
- Filter by vetting completion status
- Filter by assigned officers
- Filter by institution

### 16.3 Search Performance
- Database indexes on searchable fields
- Pagination for large result sets (default 20 per page, configurable)
- Caching of frequently accessed searches
- Search results sorted by relevance or date (user selectable)

---

## 17. Performance Requirements

### 17.1 Response Time Requirements
- Page load time: < 2 seconds for standard pages
- API response time: < 500ms for standard operations
- Search results: < 1 second for queries with < 1000 results
- File upload: Progress indication for files > 1MB

### 17.2 Scalability Requirements
- Support minimum 100 concurrent users
- Support minimum 10,000 applications per year
- Database query optimization for large datasets
- Caching strategy for frequently accessed data

### 17.3 Resource Requirements
- Server: Minimum 4 CPU cores, 8GB RAM
- Database: Minimum 50GB storage, with growth projection
- File Storage: Minimum 500GB for document storage
- Backup Storage: Separate backup storage with 2x primary storage capacity

---

## 18. Error Handling and Logging

### 18.1 Error Handling
- User-friendly error messages (no technical details exposed)
- Detailed error logging for administrators
- Error codes for different error types
- Graceful degradation for non-critical errors
- Validation error messages displayed inline with form fields

### 18.2 Logging Levels
- **Error**: System errors, exceptions, failures
- **Warning**: Potential issues, deprecated features
- **Info**: Important business events (approvals, rejections)
- **Debug**: Detailed debugging information (development only)

### 18.3 Log Retention
- Error logs: Retain for 1 year
- Audit logs: Retain for 10 years (immutable)
- Access logs: Retain for 6 months
- Debug logs: Retain for 30 days

### 18.4 Monitoring
- Application health monitoring
- Database performance monitoring
- File storage monitoring
- User activity monitoring
- Automated alerts for critical errors

---

## 19. Migration Strategy from Current System

### 19.1 Current System Analysis
The existing PHP-based system has the following structure:
- **Database**: MySQL with tables: `users`, `clients`, `decisions`
- **Authentication**: Session-based
- **Roles**: `admin`, `vetter`
- **Status Flow**: `pending` → `approved`/`denied`

### 19.2 Migration Approach
1. **Phase 1: Parallel Running**
   - Deploy Laravel system alongside current system
   - Migrate data from current system to Laravel
   - Test data integrity

2. **Phase 2: Data Migration**
   - Map current `clients` table to new `applications` table
   - Map current `users` table to new `users` table (add missing fields)
   - Migrate `decisions` table with enhanced structure
   - Create audit logs from existing data where possible

3. **Phase 3: User Training**
   - Train users on new Laravel-based system
   - Provide user documentation
   - Conduct training sessions

4. **Phase 4: Cutover**
   - Switch to Laravel system
   - Decommission old PHP system
   - Monitor for issues

### 19.3 Data Migration Scripts
- SQL migration scripts to transform existing data
- Laravel seeders for initial data
- Data validation scripts
- Rollback procedures

### 19.4 Migration Checklist
- [ ] Backup current database
- [ ] Export all file uploads
- [ ] Create Laravel database schema
- [ ] Run data migration scripts
- [ ] Validate migrated data
- [ ] Test all functionality
- [ ] Train users
- [ ] Schedule cutover date
- [ ] Execute cutover
- [ ] Monitor system post-migration

---

## 20. Testing Requirements

### 20.1 Unit Testing
- Test all models and business logic
- Test validation rules
- Test helper functions
- Minimum 80% code coverage

### 20.2 Integration Testing
- Test API endpoints
- Test database operations
- Test file upload functionality
- Test authentication and authorization

### 20.3 Functional Testing
- Test all user workflows
- Test role-based access control
- Test application state transitions
- Test notification system

### 20.4 Security Testing
- Test authentication mechanisms
- Test authorization checks
- Test SQL injection prevention
- Test XSS prevention
- Test CSRF protection
- Test file upload security

### 20.5 Performance Testing
- Load testing with expected user load
- Stress testing to find breaking points
- Database query performance testing
- File upload performance testing

### 20.6 User Acceptance Testing (UAT)
- Test with actual users from OPC, Police, and NIS
- Gather feedback and iterate
- Document issues and resolutions

---

## 21. Deployment Procedures

### 21.1 Pre-Deployment Checklist
- [ ] All tests passing
- [ ] Code review completed
- [ ] Documentation updated
- [ ] Database migrations prepared
- [ ] Environment variables configured
- [ ] SSL certificates installed
- [ ] Backup procedures tested

### 21.2 Deployment Steps
1. **Backup Current System**
   - Database backup
   - File storage backup
   - Configuration backup

2. **Deploy Laravel Application**
   - Clone repository to server
   - Install dependencies (Composer, NPM)
   - Run database migrations
   - Seed initial data
   - Configure environment
   - Set file permissions

3. **Configure Web Server**
   - Nginx/Apache configuration
   - SSL certificate installation
   - Domain configuration

4. **Post-Deployment Verification**
   - Test authentication
   - Test critical workflows
   - Verify file uploads
   - Check error logs
   - Monitor performance

### 21.3 Rollback Procedure
- Keep previous system backup for 30 days
- Document rollback steps
- Test rollback procedure
- Maintain rollback scripts

### 21.4 Deployment Environment
- **Development**: Local development environment
- **Staging**: Pre-production testing environment
- **Production**: Live system environment

---

---

# APPENDIX A: CONCEPT NOTE / EXECUTIVE SUMMARY

## 1. Background

The Office of the President and Cabinet (OPC) processes applications for change of name in accordance with established legal and administrative procedures. The current manual process is paper-based, time-consuming, and prone to delays, document loss, and limited traceability.

## 2. Problem Statement

Manual handling of change of name applications results in inefficiencies, limited transparency, and challenges in coordinating vetting activities with external agencies such as the Malawi Police Service and the National Intelligence Service (NIS).

## 3. Proposed Solution

The Change of Name Management Information System (CNMIS) is proposed as a centralized digital platform to manage the full lifecycle of change of name applications, from submission through vetting to final approval and archiving.

## 4. Expected Benefits

- Improved efficiency and reduced processing time
- Enhanced transparency and accountability
- Secure and auditable records
- Improved coordination with Police and NIS

## 5. Target Users

- Office of the President and Cabinet
- Malawi Police Service
- National Intelligence Service
- System Administrators

---

# APPENDIX B: DATA MODEL (ERD – CANVA READY)

## Core Entities and Relationships

### Entity Relationship Summary

```
USERS (1) ──< (many) APPLICATIONS (created_by)
USERS (1) ──< (many) APPLICATIONS (assigned_police_officer_id)
USERS (1) ──< (many) APPLICATIONS (assigned_nis_officer_id)
USERS (1) ──< (many) APPLICATIONS (assigned_opc_approver_id)
USERS (1) ──< (many) VETTING_RECORDS
USERS (1) ──< (many) DECISIONS
USERS (1) ──< (many) DOCUMENTS
USERS (1) ──< (many) AUDIT_LOGS
USERS (1) ──< (many) NOTIFICATIONS

APPLICATIONS (1) ──< (many) DOCUMENTS
APPLICATIONS (1) ──< (many) VETTING_RECORDS
APPLICATIONS (1) ──< (many) DECISIONS
```

### Entity Details

**USERS**
- Primary Key: id
- Attributes: username, email, password, role, institution, profile_picture, is_active, last_login_at, timestamps
- Relationships: One-to-many with Applications, Vetting Records, Decisions, Documents, Audit Logs, Notifications

**APPLICATIONS**
- Primary Key: id
- Attributes: application_number, full_name, national_id, current_name, requested_name, reason, status, timestamps
- Foreign Keys: created_by → Users, assigned_police_officer_id → Users, assigned_nis_officer_id → Users, assigned_opc_approver_id → Users
- Relationships: One-to-many with Documents, Vetting Records, Decisions

**VETTING_RECORDS**
- Primary Key: id
- Attributes: vetting_type, status, remarks, findings, recommendation, vetting_date, timestamps
- Foreign Keys: application_id → Applications, conducted_by → Users
- Relationships: Many-to-one with Applications, Many-to-one with Users

**DOCUMENTS**
- Primary Key: id
- Attributes: document_type, file_name, file_path, file_size, mime_type, description, timestamps
- Foreign Keys: application_id → Applications, uploaded_by → Users
- Relationships: Many-to-one with Applications, Many-to-one with Users

**DECISIONS**
- Primary Key: id
- Attributes: decision_type, decision, denial_reason, conditions, decided_at, timestamps
- Foreign Keys: application_id → Applications, decided_by → Users
- Relationships: Many-to-one with Applications, Many-to-one with Users

**AUDIT_LOGS**
- Primary Key: id
- Attributes: action, model_type, model_id, old_values, new_values, ip_address, user_agent, created_at
- Foreign Keys: user_id → Users (nullable)
- Relationships: Many-to-one with Users

**NOTIFICATIONS**
- Primary Key: id
- Attributes: type, title, message, related_model_type, related_model_id, is_read, read_at, timestamps
- Foreign Keys: user_id → Users
- Relationships: Many-to-one with Users

*These entities can be drawn in Canva using standard ERD notation with relationships. Use crow's foot notation for one-to-many relationships.*

---

# APPENDIX C: SECURITY AND AUDIT CONTROLS

## Security Controls

### Authentication Security
- **Password Policy**: Minimum 8 characters, must contain uppercase, lowercase, number
- **Password Hashing**: bcrypt with cost factor 12
- **Session Management**: Token-based with expiration
- **Multi-Factor Authentication**: Optional (future enhancement)
- **Account Lockout**: Lock account after 5 failed login attempts for 30 minutes

### Authorization Security
- **Role-Based Access Control (RBAC)**: Enforced at middleware level
- **Institution-Based Access**: Users can only access applications from their institution
- **Resource-Level Permissions**: Fine-grained permissions for each resource
- **Principle of Least Privilege**: Users granted minimum required permissions

### Data Security
- **Encryption at Rest**: Database encryption for sensitive fields
- **Encryption in Transit**: HTTPS/TLS 1.3 for all communications
- **SQL Injection Prevention**: Parameterized queries (Laravel Eloquent)
- **XSS Prevention**: Output escaping and Content Security Policy
- **CSRF Protection**: Laravel CSRF tokens for all forms

### File Security
- **File Type Validation**: Whitelist of allowed file types
- **File Size Limits**: Maximum 10MB per file
- **Virus Scanning**: Scan all uploaded files
- **Secure File Storage**: Files stored outside web root
- **Access Control**: Files accessible only through authenticated endpoints

### Network Security
- **HTTPS Enforcement**: Redirect all HTTP to HTTPS
- **CORS Configuration**: Restrict cross-origin requests
- **Rate Limiting**: Prevent brute force attacks
- **IP Whitelisting**: Optional for sensitive endpoints
- **Firewall Rules**: Configure server firewall

## Audit Controls

### Audit Logging Requirements
- **Automatic Logging**: All create, update, delete operations
- **User Actions**: Login, logout, password changes
- **Application Actions**: Create, update, approve, deny
- **Vetting Actions**: Submit, update vetting records
- **Document Actions**: Upload, download, delete documents
- **User Management**: Create, update, delete users

### Audit Log Data
- **User Information**: User ID, username, role, institution
- **Action Details**: Action type, model affected, record ID
- **Change Tracking**: Old values, new values (for updates)
- **Context Information**: IP address, user agent, timestamp
- **Request Information**: HTTP method, endpoint, request parameters

### Audit Log Management
- **Immutable Logs**: Audit logs cannot be modified or deleted
- **Retention Period**: 10 years for audit logs
- **Access Control**: Only administrators can view audit logs
- **Export Capability**: Export audit logs for external audits
- **Search and Filter**: Search audit logs by user, action, date range

### Compliance
- **Internal Audits**: Support regular internal audits
- **External Audits**: Provide audit trail for external auditors
- **Regulatory Compliance**: Meet government data retention requirements
- **Data Privacy**: Comply with data protection regulations

## Security Best Practices

1. **Regular Security Updates**: Keep Laravel and dependencies updated
2. **Security Headers**: Implement security headers (CSP, HSTS, etc.)
3. **Input Validation**: Validate all user input server-side
4. **Output Encoding**: Encode all output to prevent XSS
5. **Error Handling**: Don't expose sensitive information in errors
6. **Logging**: Log security events for monitoring
7. **Backup Security**: Encrypt database backups
8. **Access Reviews**: Regular review of user access and permissions

---

# APPENDIX D: DEVELOPER HANDOVER PACK

## Technology Stack

### Backend
- **Framework**: Laravel 10.x or later
- **PHP Version**: PHP 8.1 or later
- **Database**: MySQL 8.0 or later
- **Authentication**: Laravel Sanctum
- **File Storage**: Local filesystem (configurable for S3/cloud storage)

### Frontend (Recommended)
- **Framework**: Vue.js 3.x or React (optional, can use Blade templates)
- **UI Library**: Bootstrap 5 or Tailwind CSS
- **Build Tool**: Vite (Laravel default)

### Development Tools
- **Version Control**: Git
- **Package Manager**: Composer (PHP), NPM (JavaScript)
- **Testing**: PHPUnit, Pest (optional)

## Project Structure

```
cnmis/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ApplicationController.php
│   │   │   │   ├── VettingController.php
│   │   │   │   ├── DocumentController.php
│   │   │   │   ├── DecisionController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   └── NotificationController.php
│   │   │   └── Web/ (for web routes if needed)
│   │   ├── Middleware/
│   │   │   ├── RoleMiddleware.php
│   │   │   └── InstitutionMiddleware.php
│   │   └── Requests/
│   │       ├── ApplicationRequest.php
│   │       ├── VettingRequest.php
│   │       └── ...
│   ├── Models/
│   │   ├── User.php
│   │   ├── Application.php
│   │   ├── VettingRecord.php
│   │   ├── Document.php
│   │   ├── Decision.php
│   │   ├── AuditLog.php
│   │   └── Notification.php
│   ├── Services/
│   │   ├── ApplicationService.php
│   │   ├── VettingService.php
│   │   ├── NotificationService.php
│   │   └── AuditService.php
│   └── Enums/
│       ├── ApplicationStatus.php
│       ├── UserRole.php
│       ├── Institution.php
│       └── VettingType.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_000001_create_users_table.php
│   │   ├── 2024_01_01_000002_create_applications_table.php
│   │   ├── 2024_01_01_000003_create_vetting_records_table.php
│   │   ├── 2024_01_01_000004_create_documents_table.php
│   │   ├── 2024_01_01_000005_create_decisions_table.php
│   │   ├── 2024_01_01_000006_create_audit_logs_table.php
│   │   └── 2024_01_01_000007_create_notifications_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── UserSeeder.php
│       └── RolePermissionSeeder.php
├── routes/
│   ├── api.php
│   └── web.php
└── tests/
    ├── Unit/
    ├── Feature/
    └── TestCase.php
```

## Core Database Tables

1. **users** - User accounts and authentication
2. **applications** - Change of name applications
3. **vetting_records** - Police and NIS vetting records
4. **documents** - Uploaded documents and files
5. **decisions** - Approval/rejection decisions
6. **audit_logs** - System audit trail
7. **notifications** - User notifications

## Permissions Matrix (Detailed)

### Administrator (admin)
- Full system access
- Manage all users
- View all applications
- View audit logs
- Generate all reports
- System configuration

### OPC Data Entry Officer (opc_data_entry)
- Create applications
- Update applications (before vetting starts)
- Upload supporting documents
- View assigned applications
- Search and filter applications

### OPC Approving Officer (opc_approver)
- View all applications
- Review vetting results
- Approve or reject applications
- View decision history
- Generate approval reports

### Police Officer (police_officer)
- View assigned applications
- Conduct police vetting
- Upload police vetting reports
- Update vetting status
- View own vetting history

### NIS Officer (nis_officer)
- View assigned applications
- Conduct NIS vetting
- Upload NIS vetting reports
- Update vetting status
- View own vetting history

## Key Laravel Features to Implement

### 1. Models with Relationships
```php
// Application Model
class Application extends Model
{
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function documents() { return $this->hasMany(Document::class); }
    public function vettingRecords() { return $this->hasMany(VettingRecord::class); }
    public function decisions() { return $this->hasMany(Decision::class); }
}
```

### 2. Policies for Authorization
```php
// ApplicationPolicy
- viewAny, view, create, update, delete methods
- Role-based checks
- Institution-based checks
```

### 3. Form Requests for Validation
```php
// ApplicationRequest
- Validation rules
- Authorization checks
- Custom error messages
```

### 4. Service Classes for Business Logic
```php
// ApplicationService
- Application creation logic
- Status transition logic
- Assignment logic
```

### 5. Events and Listeners
```php
// Events
- ApplicationCreated
- VettingCompleted
- ApplicationApproved
- ApplicationDenied

// Listeners
- SendNotification
- CreateAuditLog
- UpdateApplicationStatus
```

### 6. Jobs and Queues
```php
// Jobs
- SendEmailNotification
- GenerateReport
- ProcessFileUpload
```

## Implementation Checklist

### Phase 1: Foundation
- [ ] Set up Laravel project
- [ ] Configure database connection
- [ ] Create database migrations
- [ ] Create models with relationships
- [ ] Set up authentication (Sanctum)
- [ ] Create middleware for roles
- [ ] Set up file storage

### Phase 2: Core Features
- [ ] Implement user management
- [ ] Implement application CRUD
- [ ] Implement document upload
- [ ] Implement vetting workflow
- [ ] Implement decision workflow
- [ ] Implement notification system

### Phase 3: Advanced Features
- [ ] Implement search and filtering
- [ ] Implement reporting
- [ ] Implement audit logging
- [ ] Implement email notifications
- [ ] Implement dashboard statistics

### Phase 4: Testing and Deployment
- [ ] Write unit tests
- [ ] Write feature tests
- [ ] Perform security testing
- [ ] Deploy to staging
- [ ] User acceptance testing
- [ ] Deploy to production

## Important Notes

1. **Security**: Always validate and sanitize user input, use prepared statements (Laravel does this automatically), implement CSRF protection
2. **Performance**: Use eager loading for relationships, implement caching where appropriate, optimize database queries
3. **Audit Trail**: Log all critical actions (create, update, delete, approve, deny)
4. **File Security**: Validate file types, scan for viruses, store securely, control access
5. **Error Handling**: Use Laravel's exception handling, log errors, provide user-friendly messages

## Migration from Current System

See Section 19 for detailed migration strategy. Key points:
- Map existing `clients` table to `applications` table
- Map existing `users` table to new `users` table structure
- Migrate `decisions` table with enhanced structure
- Preserve all file uploads
- Create audit logs from existing data

*This handover pack provides developers with sufficient detail to begin implementation using Laravel.*

---

# APPENDIX E: CURRENT SYSTEM ANALYSIS

## Existing System Overview

The current system is a PHP-based web application using procedural/object-oriented PHP with MySQL database.

### Current Technology Stack
- **Backend**: PHP (procedural/OOP)
- **Database**: MySQL
- **Authentication**: Session-based
- **File Storage**: Local filesystem (`uploads/` directory)
- **Frontend**: HTML, CSS, Bootstrap, JavaScript

### Current Database Schema

#### users Table
```sql
- id (INT, PRIMARY KEY)
- username (VARCHAR, UNIQUE)
- password (VARCHAR) - bcrypt hashed
- role (VARCHAR) - Values: 'admin', 'vetter'
- profile_picture (VARCHAR, NULLABLE)
- created_at (TIMESTAMP)
```

#### clients Table (to be renamed to 'applications')
```sql
- id (INT, PRIMARY KEY)
- full_name (VARCHAR)
- national_id (VARCHAR, NULLABLE)
- current_name (VARCHAR, NULLABLE)
- requested_name (VARCHAR)
- reason (TEXT)
- document_path (VARCHAR, NULLABLE)
- status (VARCHAR) - Values: 'pending', 'approved', 'denied'
- created_by (INT, FK -> users.id)
- assigned_vetter_id (INT, FK -> users.id, NULLABLE)
- created_at (TIMESTAMP)
```

#### decisions Table
```sql
- id (INT, PRIMARY KEY)
- client_id (INT, FK -> clients.id)
- vetter_id (INT, FK -> users.id)
- decision (VARCHAR) - Values: 'approved', 'denied'
- denial_reason (TEXT, NULLABLE)
- decided_at (TIMESTAMP)
```

### Current System Features

#### Implemented Features
1. **User Authentication**: Login/logout with session management
2. **User Management**: Admin can create/manage vetters
3. **Application Registration**: Create new change of name applications
4. **Document Upload**: Upload supporting documents (PDF, images)
5. **Vetting Workflow**: Vetters can approve/deny applications
6. **Status Tracking**: View pending, approved, and denied applications
7. **Dashboard**: Statistics overview (total, pending, approved, denied)
8. **Profile Management**: Users can upload profile pictures
9. **Pagination**: Paginated lists for applications

#### Current Roles
- **admin**: Can manage vetters, view dashboard, access all features
- **vetter**: Can view pending applications, approve/deny, add new clients

#### Current Workflow
1. Admin or Vetter creates application (status: 'pending')
2. Vetter reviews application
3. Vetter approves (status: 'approved') or denies (status: 'denied')
4. Decision is recorded in `decisions` table

### Gaps and Limitations

#### Missing Features (Compared to Requirements)
1. **Separate Police Vetting**: No separate police vetting interface
2. **Separate NIS Vetting**: No separate NIS vetting interface
3. **Multi-Stage Workflow**: Only simple pending → approved/denied flow
4. **Institution Management**: No institution field or separation
5. **Email Field**: Users don't have email addresses
6. **Audit Logging**: No comprehensive audit trail
7. **Notifications**: No notification system
8. **Search/Filter**: Limited search capabilities
9. **Reporting**: No reporting module
10. **Application Numbering**: No auto-generated application numbers
11. **Status History**: No tracking of status changes over time
12. **Multiple Documents**: Limited to single document per application
13. **Role Granularity**: Only 2 roles vs required 5 roles

#### Technical Limitations
1. **No API**: Only web-based, no REST API
2. **Session-Based Auth**: Not suitable for API or mobile apps
3. **No Validation Layer**: Validation scattered in PHP files
4. **No Service Layer**: Business logic mixed with presentation
5. **Limited Error Handling**: Basic error handling
6. **No Caching**: No caching strategy
7. **No Queue System**: Synchronous processing only
8. **Security Concerns**: Some SQL queries not using prepared statements everywhere

### Migration Mapping

#### Database Table Mapping
| Current Table | New Table | Changes Required |
|--------------|-----------|------------------|
| `users` | `users` | Add: email, institution, is_active, last_login_at, deleted_at |
| `clients` | `applications` | Add: application_number, status enum expansion, timestamps for workflow stages |
| `decisions` | `decisions` | Add: decision_type, conditions, enhance structure |
| N/A | `vetting_records` | New table for separate vetting tracking |
| N/A | `documents` | New table (currently single document_path in clients) |
| N/A | `audit_logs` | New table for audit trail |
| N/A | `notifications` | New table for notifications |

#### Role Mapping
| Current Role | New Role(s) | Notes |
|-------------|-------------|-------|
| `admin` | `admin` | Enhanced permissions |
| `vetter` | `opc_data_entry`, `opc_approver`, `police_officer`, `nis_officer` | Split into specific roles based on user assignment |

#### Status Mapping
| Current Status | New Status | Notes |
|---------------|------------|-------|
| `pending` | `pending` | Initial state |
| `approved` | `approved` | Final approved state |
| `denied` | `denied` | Final denied state |
| N/A | `police_vetting`, `police_completed`, `opc_review`, `nis_vetting`, `nis_completed`, `pending_approval`, `archived` | New workflow states |

### File Migration

#### Current File Structure
```
uploads/
├── {timestamp}_{filename}.pdf
├── {timestamp}_{filename}.jpg
└── profile_pics/
    ├── {timestamp}_{filename}.jpg
    └── default.jpg
```

#### New File Structure
```
storage/app/documents/
├── {year}/
│   └── {month}/
│       └── {application_id}/
│           ├── supporting_documents/
│           ├── police_vetting_reports/
│           └── nis_vetting_reports/
storage/app/public/profile_pics/
└── {user_id}/
    └── {timestamp}_{filename}.jpg
```

### Data Migration Scripts Required

1. **User Migration**: Add email, institution, is_active fields
2. **Application Migration**: Generate application numbers, expand status values
3. **Document Migration**: Move files to new structure, create document records
4. **Decision Migration**: Enhance decision records with new fields
5. **Audit Log Creation**: Create initial audit logs from existing data where possible

### Risk Assessment

#### Low Risk
- User data migration (straightforward mapping)
- File migration (file system operations)

#### Medium Risk
- Application status migration (need to determine current workflow state)
- Decision data enhancement (may need manual review)

#### High Risk
- Data integrity during migration
- File path updates in database
- User access during migration period

### Recommendations

1. **Parallel Running**: Run both systems in parallel during migration
2. **Data Validation**: Extensive validation after migration
3. **User Training**: Train users on new system before cutover
4. **Rollback Plan**: Maintain ability to rollback if issues occur
5. **Gradual Migration**: Consider migrating in phases (users first, then applications)

