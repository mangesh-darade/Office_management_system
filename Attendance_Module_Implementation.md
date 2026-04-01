# Attendance Module Implementation Document

## 1) Module Details

- **Module Name**: Attendance Management (with Location Tracking + Face/Image Verification + Reporting)
- **Primary Area**: `application/controllers/Attendance.php`
- **Related Areas**:
  - Reports: `application/controllers/Reports.php`
  - Permissions: `application/controllers/Permissions.php`
  - Face registration dependency: `application/controllers/Users.php` + `application/models/Face_model.php`
  - Routes: `application/config/routes.php`
  - Role hierarchy: `application/helpers/hierarchy_filter_helper.php`

### 1.1 Screens / Pages

1. **Attendance Summary**
   - View: `application/views/attendance/index.php`
   - Route: `/attendance`
2. **Mark Attendance (Check-in / Check-out)**
   - View: `application/views/attendance/create.php`
   - Route: `/attendance/create`
3. **Edit Attendance**
   - View: `application/views/attendance/edit.php`
   - Route: `/attendance/{id}/edit`
4. **Attendance Report (Daily/Weekly/Monthly aggregate)**
   - View: `application/views/reports/attendance.php`
   - Route: `/reports/attendance`
5. **Employee Attendance Summary**
   - View: `application/views/reports/attendance_employee.php`
   - Route: `/reports/attendance-employee`
6. **Employee Attendance Detail**
   - View: `application/views/reports/attendance_employee_detail.php`
   - Route: `/reports/attendance-employee/{user_id}`

### 1.2 Fields by Screen

#### A) Mark Attendance (`attendance/create.php`)
- Hidden: `lat`, `lng`, `location_name`, `face_required`, `face_descriptor`
- Action: `action` (`in` / `out`)
- Notes: `notes`
- Optional attachment: `attachment` (file)
- Camera/face UI:
  - video `attFaceVideo`
  - canvas `attFaceCanvas`
  - capture/retake buttons
- Submit: `Mark Attendance`

#### B) Edit Attendance (`attendance/edit.php`)
- Hidden: `lat`, `lng`, `face_required`, `face_descriptor`
- Read-only display: date, check-in, check-out, location
- Editable: `notes`, optional new `attachment`
- Optional face verify UI for update action
- Submit: `Update Attendance`

#### C) Attendance Summary (`attendance/index.php`)
- Employee list grid with:
  - employee/user name + email
  - latest attendance date
  - total record count
- Modal filters:
  - `filter_type` (`month` / `date` / `year`)
  - `filter_value`
- Modal detail columns:
  - date, check-in, check-out, status, notes
  - check-in location, check-out location
  - action buttons (edit/delete if allowed)
- Bulk export selection:
  - select rows/users
  - export `excel` / `pdf`

#### D) Attendance Report (`reports/attendance.php`)
- Filters:
  - period (`daily`, `weekly`, `monthly`)
  - `start_date`, `end_date`
  - `department_id` (optional)
- Tabs:
  - daily table
  - weekly table
  - monthly table
- Actions:
  - search/sort/pagination (client-side)
  - export CSV
  - export PDF

#### E) Employee Attendance Summary (`reports/attendance_employee.php`)
- Filters:
  - period (`daily`/`weekly`/`monthly`)
  - `date` (daily/weekly)
  - `month` (monthly)
- Metrics per employee:
  - present, wfh, absent, on-time, late, leave, late hours, extra hours
- Actions:
  - open detailed view
  - single export (excel/pdf)
  - multi-select export (excel/pdf)

#### F) Employee Attendance Detail (`reports/attendance_employee_detail.php`)
- Filters:
  - hidden `period`
  - `date` or `month`
- Row columns:
  - date, status
  - check-in, check-out
  - check-in location, check-out location
  - late/on-time details
  - worked hours, extra hours
  - notes
- Actions:
  - export detail excel/pdf

## 2) Attendance Flow (End-to-End)

## 2.1 Step-by-step User Journey

1. User opens `/attendance/create`.
2. Frontend JS requests location via `navigator.geolocation.getCurrentPosition(...)`.
3. If face verification is enabled, frontend starts camera and captures a face descriptor.
4. User selects action (`in` or `out`) and submits form.
5. `Attendance::create()` runs permission + business validations.
6. Controller resolves current date/time using user timezone.
7. Controller checks holiday restriction (`holidays` table).
8. Controller validates action and attachment upload (if present).
9. Controller validates face descriptor (if required) against stored template (`user_faces`).
10. Controller validates location (`lat`/`lng` required), and optionally office radius.
11. Controller checks whether an attendance row already exists for user+today.
12. Branch:
    - **Check-in**: create or update row with check-in values and status.
    - **Check-out**: only if prior check-in exists and checkout is valid chronologically.
13. Controller updates location columns (legacy + new check-in/check-out location columns).
14. Controller sends notification email (regular or late-mark flow).
15. User is redirected with success/error flash message.

### 2.2 Business Logic and Conditions

- Permission gate:
  - Constructor: `require_module_access('attendance', true)`
  - Create: `require_module_access(['attendance_add', 'attendance'], true)`
  - Edit: `require_module_access(['attendance_edit', 'attendance'], true)` for non-owner edits
  - Delete: `require_module_access(['attendance_delete', 'attendance'], true)` for non-owner deletes
- Holiday block:
  - If `holidays.holiday_date = today` and status is `active`, mark attendance is blocked.
- Valid action:
  - Must be `in` or `out`.
- Check-out dependency:
  - Cannot check-out without existing same-day check-in.
  - Cannot check-out if already checked out.
- Time validation:
  - `is_valid_checkout_time()` ensures check-out is after check-in (datetime mode).
  - Time column mode also supports after-midnight pattern.
- Late / early-leave:
  - Late check-in if check-in > shift start + grace.
  - Early leave if check-out < shift end - early-exit grace.
- Duplicate/race handling:
  - Unique key protection (`user_id`, `att_date`) handled with retry/update fallback.
- Delete safety:
  - Delete endpoint is POST-only (`405` on non-POST).

## 3) Location Tracking

### 3.1 How Lat/Lng is Captured

- Frontend capture:
  - `navigator.geolocation.getCurrentPosition(...)`
  - Present in:
    - `application/views/attendance/create.php`
    - `application/views/attendance/edit.php`
- Captured values are written into hidden fields:
  - `lat`, `lng`
  - optional `location_name`

### 3.2 JavaScript APIs / Methods Used

- `navigator.geolocation.getCurrentPosition(success, error, options)`
  - options include:
    - `enableHighAccuracy: true`
    - `timeout: 8000`
    - `maximumAge: 0`

### 3.3 Accuracy / Validation

- Browser-side:
  - High accuracy flag requested.
- Server-side (mandatory):
  - Rejects submission if `lat` or `lng` missing.
- Optional strict geofence:
  - Setting key: `system_enable_location_strict = yes`
  - Office center: `system_office_latitude`, `system_office_longitude`
  - Radius: `system_attendance_radius_meters`
  - Distance computed with Haversine (`calculate_distance(...)`)
  - Reject when distance > allowed radius.

### 3.4 Database Storage (Location)

- Base SQL attendance columns:
  - `latitude` decimal(10,7)
  - `longitude` decimal(10,7)
- Runtime compatibility writes (if columns exist):
  - `latitude` / `longitude`
  - `lat` / `lng`
  - `geo_lat` / `geo_lng`
  - `location_name`
  - `checkin_lat`, `checkin_lng`, `checkin_location_name`
  - `checkout_lat`, `checkout_lng`, `checkout_location_name`

### 3.5 Reverse Geocoding

- Backend helper: `Attendance::reverse_geocode($lat, $lng)`
- External endpoint used:
  - `https://nominatim.openstreetmap.org/reverse`
- Address stored as `location_name` / checkin/checkout location name where applicable.

## 4) Image Capture / Face Verification

### 4.1 Capture Method

- Attendance module uses **camera capture for face verification** (not generic selfie upload).
- JS uses webcam stream and face descriptor extraction.
- Separate file upload exists for supporting `attachment` document/image.

### 4.2 JavaScript APIs Used

- Camera:
  - `navigator.mediaDevices.getUserMedia({ video: ..., audio: false })`
- Face recognition:
  - `@vladmandic/face-api` CDN
  - model loading from external weights URL
- Canvas capture:
  - draw video frame to `<canvas>`
  - descriptor serialized into hidden `face_descriptor`

### 4.3 Image Processing

- Attendance face flow:
  - No explicit resize/compression pipeline before submit.
  - Converts descriptor array to JSON string.
- Face registration (`Users::save_face`):
  - Accepts base64 image (`data:image/...;base64,...`)
  - Decodes and writes file to disk (`uploads/faces/...`)
  - Saves descriptor JSON + image path in DB (`user_faces`).

### 4.4 Storage Path + DB Column

- Attendance attachment upload:
  - Path: `uploads/attendance/`
  - DB column: `attendance.attachment_path`
- Registered face image:
  - Path: `uploads/faces/`
  - DB table/column: `user_faces.image_path`
- Face descriptor storage:
  - Table/column: `user_faces.descriptor` (longtext)

### 4.5 Validation Rules

- Face verification (attendance create):
  - If enabled (`attendance_face_verification_required`), descriptor is mandatory.
  - Descriptor compared to stored descriptor using Euclidean distance.
  - Threshold used: `0.6`.
- Attachment upload:
  - Allowed types: `jpg|jpeg|png|pdf|doc|docx`
  - Max size: `4096 KB` (4 MB)

## 5) Database Details

### 5.1 Database Name

- From `application/config/database.php`:
  - `official_internal_portel`

### 5.2 Core Tables (Attendance Context)

1. **attendance** (from SQL dump + runtime schema extensions)
   - SQL base:
     - `id` bigint unsigned PK
     - `user_id` bigint unsigned
     - `att_date` date
     - `punch_in` datetime
     - `punch_out` datetime
     - `notes` text
     - `attachment_path` varchar(255)
     - `latitude` decimal(10,7)
     - `longitude` decimal(10,7)
     - `ip_address` varchar(45)
     - `source` enum('manual','auto')
     - `total_hours` decimal(5,2)
     - `status` enum('present','absent','half_day','work_from_home')
     - timestamps
   - Keys:
     - `PRIMARY KEY (id)`
     - `UNIQUE uq_attendance (user_id, att_date)`
     - index on `(user_id, att_date)`
   - Runtime `Attendance_model::ensure_schema()` can add:
     - `location_name` varchar(255)
     - `checkin_lat` decimal(10,7)
     - `checkin_lng` decimal(10,7)
     - `checkin_location_name` varchar(255)
     - `checkout_lat` decimal(10,7)
     - `checkout_lng` decimal(10,7)
     - `checkout_location_name` varchar(255)
     - `shift_id` int(11)
     - status enum extension including `late` and `early_leave`
2. **attendance_logs**
   - event audit table (`punch_in`, `punch_out`, etc.)
3. **user_faces** (created by model if missing)
   - `id`, `user_id`, `descriptor` (longtext), `image_path`, `created_at`
4. **users**
   - user identity, role linkage, contact fields
5. **employees**
   - includes `shift_id` (used by app logic), `reporting_to`, department info
6. **leave_requests**
   - used by attendance report logic for leave overlays/metrics
7. **holidays**
   - referenced heavily by attendance/reporting logic (table expected by runtime checks)
8. **permissions**
   - role/module permission entries
9. **roles**
   - role master table
10. **lead_user_mapping**
   - created by migration `003_Create_lead_user_mapping_table.php`
   - used for hierarchy-based access filtering

### 5.3 Important Notes on Schema Variants

- Code supports fallback column names:
  - date: `att_date` or `date`
  - check-in: `punch_in` or `check_in`
  - check-out: `punch_out` or `check_out`
- This compatibility behavior is central to migration/reuse.

## 6) MVC Flow

### 6.1 Controllers (Key Functions)

#### `Attendance.php`
- `index()`:
  - user summary listing, hierarchy filtering, permissions flags for edit/delete
- `get_user_monthly_attendance()`:
  - popup data API with date/month/year filters + pagination
  - includes ownership-based action permissions
- `create()`:
  - main check-in/check-out transaction + validations
- `edit($id)`:
  - notes/location/attachment update + optional face verify
- `delete($id)`:
  - POST-only delete with ownership/permission check
- `export()`:
  - selected users export (excel/pdf)

#### `Reports.php` (attendance-related)
- `attendance()`:
  - daily/weekly/monthly aggregate report + csv/pdf export
- `attendance_employee($user_id = null)`:
  - summary mode (all users) or detail mode (single user)
- `export_attendance_employee()`:
  - exports summary or detail depending on selected users
- Internal export helpers (excel/pdf/detail)

#### `Users.php`
- `save_face()`:
  - stores user face descriptor + face image for attendance verification dependency

### 6.2 Models

- `Attendance_model.php`:
  - schema ensure, canonical column resolver, create/update/delete/find helpers
- `Face_model.php`:
  - ensures `user_faces` table and manages user face record upsert
- `Report_model.php`:
  - attendance summary aggregation helper (`get_attendance_summary`)

### 6.3 View + JS + API Flow

- UI in views triggers:
  - browser geolocation + camera capture
  - form submit to controller routes
- AJAX endpoint:
  - `POST /attendance/get-data` -> `Attendance::get_user_monthly_attendance()`
- Export endpoints:
  - `POST /attendance/export`
  - `GET /reports/export-attendance-employee`
  - `GET /reports/attendance?export=csv|pdf`

## 7) Reports

### 7.1 Report Names

- Attendance Report
- Employee Attendance Summary Report
- Employee Attendance Detail Report

### 7.2 Filters

- Attendance Report:
  - period, start_date, end_date, department_id
- Employee Summary:
  - period + date/month
- Employee Detail:
  - period + date/month (single user context)

### 7.3 Logic Highlights

- Dynamic attendance schema detection (user/date/status/checkin/out columns).
- Working-day calculations exclude weekends and holidays.
- Leave deduction uses approved leave requests.
- Late calculation uses office start + grace period.
- Extra hours computed from worked time vs standard hours.
- Notes and location fields included when present.

### 7.4 Export Capabilities

- Attendance report:
  - CSV
  - PDF-header HTML fallback
- Employee summary:
  - Excel-compatible CSV
  - PDF (Dompdf if installed; HTML fallback)
- Employee detail:
  - Excel (`.xls` HTML table format)
  - PDF (Dompdf if installed; HTML fallback)

## 8) Role-Based Access (Admin vs User)

### 8.1 Permission Keys (attendance-related)

- Attendance:
  - `attendance`, `attendance_list`, `attendance_add`, `attendance_edit`, `attendance_delete`, `attendance_bulk`
- Reports:
  - `reports_attendance`, `reports_attendance_employee`, plus `reports`

### 8.2 Visibility Rules

- Hierarchy helper (`get_accessible_hierarchy_user_ids`):
  - Admin: unrestricted
  - Lead: mapped users + self
  - Others: self only
- Query filtering:
  - `apply_role_hierarchy_filter(...)` is used in many attendance/report queries
- Ownership constraints:
  - Edit/delete attendance generally restricted to own records unless authorized role

## 9) Dependencies

### 9.1 JavaScript / Browser APIs

- `navigator.geolocation`
- `navigator.mediaDevices.getUserMedia`
- `fetch`
- Canvas API (frame capture)

### 9.2 Third-party Libraries / Services

- Face API:
  - CDN `@vladmandic/face-api`
  - weights from jsDelivr/GitHub-hosted weights
- Geocoding:
  - OpenStreetMap Nominatim reverse geocode API
- PDF:
  - Optional `\Dompdf\Dompdf` (if available)

### 9.3 Framework / Helper Dependencies

- CodeIgniter helpers: permission/group_filter/hierarchy_filter/date/email/notification/company
- CI Upload library for attachment handling

## 10) Security Review

### 10.1 Existing Controls

- Auth/session enforcement before attendance actions
- Module-level permission checks and role hierarchy filtering
- IDOR protection on attendance detail AJAX endpoint
- POST-only delete action
- Location mandatory validation and optional radius check
- Face descriptor match threshold for identity verification
- File upload restrictions (types + size)

### 10.2 Location Spoofing Checks

- Present:
  - Distance-from-office radius check (if strict mode enabled)
- Not present (gap):
  - No trusted-device attestation
  - No anti-mock-location SDK check
  - No signed GPS payload

### 10.3 Image Validation Checks

- Present:
  - Face descriptor comparison against registered face
  - File type/size restrictions on attachment uploads
- Gaps:
  - No liveness detection
  - No anti-replay challenge for webcam frame
  - Base64 face image trust relies on browser payload

## 11) Integration Plan (Migrate to Another Project)

### 11.1 Required Folder Structure

- Controllers:
  - `Attendance.php`
  - report methods from `Reports.php` (attendance-related)
  - `Users::save_face` (or equivalent endpoint)
- Models:
  - `Attendance_model.php`
  - `Face_model.php`
  - `Report_model.php` (attendance functions)
- Views:
  - `attendance/index.php`, `attendance/create.php`, `attendance/edit.php`
  - `reports/attendance.php`
  - `reports/attendance_employee.php`
  - `reports/attendance_employee_detail.php`
- Helpers:
  - `permission_helper.php`
  - `hierarchy_filter_helper.php`
  - `group_filter_helper.php`
- Routes:
  - attendance and report endpoints listed in this document
- Upload directories:
  - `uploads/attendance/`
  - `uploads/faces/`

### 11.2 Required DB Artifacts

1. Create/import `attendance` and `attendance_logs` tables.
2. Ensure `user_faces` table (via migration/model).
3. Ensure role/permission tables and seed attendance/report permission keys.
4. Ensure support tables exist:
   - `users`, `employees`, `leave_requests`, `holidays`, `roles`, `permissions`
5. Add lead mapping table (`lead_user_mapping`) if hierarchy model requires it.
6. Keep unique key on `(user_id, att_date)` for race-safe check-in.

### 11.3 Required Settings Keys

- `attendance_face_verification_required`
- `attendance_start_time`
- `attendance_grace_minutes`
- `attendance_standard_working_hours` (or fallback)
- `attendance_weekends`
- `system_enable_location_strict`
- `system_office_latitude`
- `system_office_longitude`
- `system_attendance_radius_meters`
- `attendance_late_mark_notification`

### 11.4 Integration Steps

1. Port models + migrations first (schema compatibility).
2. Port helpers for permission and hierarchy filtering.
3. Port attendance controller and routes.
4. Port attendance views and verify JS dependencies load.
5. Port report methods and report views.
6. Add face registration endpoint + UI path for descriptor enrollment.
7. Configure settings keys and default values.
8. Create upload directories and permission settings.
9. Test role-based access matrix (admin/lead/employee).
10. Test end-to-end:
   - check-in success
   - check-out success
   - strict geofence rejection
   - face mismatch rejection
   - report export paths

## 12) Risks and Solutions

1. **Schema drift across projects**
   - Risk: column names differ (`att_date` vs `date`, etc.)
   - Mitigation: retain canonical/fallback column resolution and schema checks.

2. **Missing optional libraries (Dompdf)**
   - Risk: PDF export behavior differs
   - Mitigation: install Dompdf in target project or accept HTML fallback.

3. **Location spoofing**
   - Risk: client-side geolocation can be faked
   - Mitigation: enforce strict radius + add device integrity / anti-mock controls.

4. **Face replay / no liveness**
   - Risk: static-image replay attacks
   - Mitigation: add liveness challenge and nonce-based capture workflow.

5. **Role-filter inconsistency in custom SQL blocks**
   - Risk: accidental over-exposure in reports
   - Mitigation: ensure all aggregate SQL paths apply hierarchy restriction consistently.

6. **External API dependency (Nominatim / model CDN)**
   - Risk: latency or outage impact
   - Mitigation: add timeout fallback, local model hosting, and optional geocode queueing.

## 13) Route Reference

- `/attendance` -> `Attendance::index`
- `/attendance/create` -> `Attendance::create`
- `/attendance/{id}/edit` -> `Attendance::edit`
- `/attendance/{id}/delete` -> `Attendance::delete`
- `/attendance/get-data` -> `Attendance::get_user_monthly_attendance`
- `/reports/attendance` -> `Reports::attendance`
- `/reports/attendance-employee` -> `Reports::attendance_employee`
- `/reports/attendance-employee/{id}` -> `Reports::attendance_employee`
- `/reports/export-attendance-employee` -> `Reports::export_attendance_employee`

## 14) Reuse Checklist

- [ ] DB tables migrated and verified
- [ ] Permission keys seeded
- [ ] Routes added
- [ ] Attendance + report controllers loaded
- [ ] Views copied and UI assets available
- [ ] Face registration flow enabled
- [ ] Upload folders writable
- [ ] Settings configured
- [ ] Role visibility validated
- [ ] Exports tested (CSV/Excel/PDF)

