# Module 02 — People & Organization

## What is this module for?

Manage staff accounts, HR records, departments, job titles, shifts, assets, and clients.

![Animated overview — People & Organization](videos/02-people/employees-tour-voiced.webm)


---

## Users

**Purpose:** Create login accounts and assign roles so people can access the system.

**Menu:** User Management → Users

![Video — Users](videos/02-people/users-crud-voiced.webm)


![View list — Users](images/02-people/users.png)


### Add (create new)

![Add screen — Users](images/02-people/users-add.png)


**Steps:**

1. Click Add User.
2. Enter email, name, phone, password, role.
3. Set Active → Save.

### Edit (update existing)

![Edit screen — Users](images/02-people/users-edit.png)


**Steps:**

1. Open user from list → Edit.
2. Change role, status, or contact → Save.

### Delete / remove

**How:**

1. Admin: open user → Delete / deactivate.
2. Prefer deactivate to block login without losing history.

---

## Employees

**Purpose:** Link users to HR data: code, department, designation, shift, documents.

**Menu:** Employees

![View list — Employees](images/02-people/employees.png)


### Add (create new)

![Add screen — Employees](images/02-people/employees-add.png)


**Steps:**

1. Click Create.
2. Select user, code, department, designation, shift.
3. Save.

### Edit (update existing)

![Edit screen — Employees](images/02-people/employees-edit.png)


**Steps:**

1. Open employee → Edit.
2. Update HR fields → Save.

### Delete / remove

**How:**

1. Admin delete from employee detail or list action.
2. Import CSV available for bulk add.

---

## Departments

**Purpose:** Organize staff into teams. Used in reports and leave approval scope.

**Menu:** User Management → Department

![Video — Departments](videos/02-people/departments-crud-voiced.webm)


![View list — Departments](images/02-people/departments.png)


### Add (create new)

![Add screen — Departments](images/02-people/departments-add.png)


**Steps:**

1. Click Create.
2. Unique code + name + optional manager.
3. Save.

### Edit (update existing)

![Edit screen — Departments](images/02-people/departments-edit.png)


**Steps:**

1. Click Edit on row.
2. Update fields → Save.

### Delete / remove

**How:**

1. Delete soft-hides the department.
2. Show Deleted → Restore to bring it back.

---

## Designations

**Purpose:** Define job titles and levels for employees.

**Menu:** User Management → Designation

![View list — Designations](images/02-people/designations.png)


### Add (create new)

![Add screen — Designations](images/02-people/designations-add.png)



### Edit (update existing)

![Edit screen — Designations](images/02-people/designations-edit.png)



### Delete / remove

**How:**

1. Soft delete like departments.
2. Restore from Show Deleted.

---

## Shifts

**Purpose:** Set work hours and grace period for attendance late rules.

**Menu:** User Management → Shifts

![View list — Shifts](images/02-people/shifts.png)


### Add (create new)

![Add screen — Shifts](images/02-people/shifts-add.png)



### Edit (update existing)

![Edit screen — Shifts](images/02-people/shifts-edit.png)



### Delete / remove

**How:**

1. Delete from list if no employees depend on the shift.

---

## Assets

**Purpose:** Track laptops, phones, and equipment assigned to staff.

**Menu:** User Management → Assets

![View list — Assets](images/02-people/assets.png)


### Add (create new)

![Add screen — Assets](images/02-people/assets-add.png)



### Edit (update existing)

![Edit screen — Assets](images/02-people/assets-edit.png)



### Delete / remove

**How:**

1. Mark returned instead of delete when possible.
2. Admin can remove asset record from edit screen.

---

## Clients

**Purpose:** Store customer companies linked to projects.

**Menu:** Clients

![View list — Clients](images/02-people/clients.png)


### Add (create new)

![Add screen — Clients](images/02-people/clients-add.png)



### Edit (update existing)

![Edit screen — Clients](images/02-people/clients-edit.png)



### Delete / remove

**How:**

1. Open Clients → open a client detail.
2. On the Overview tab, the Activity panel shows create/edit/status and URL/DB change history (same style as Task activity).
3. Delete from client list or detail.
4. Export list to CSV from list page.

---

## Roles

**Purpose:** Group permissions (Staff, Admin, etc.). Pair with Permission Manager.

**Menu:** User Management → Roles

![View list — Roles](images/02-people/roles.png)


### Add (create new)

**Steps:**

1. Add role name on roles page → Save.
2. Then grant modules in Permission Manager.

### Edit (update existing)

**Steps:**

1. Edit role name or description on roles page.

### Delete / remove

**How:**

1. Delete only if no users use the role.

---

**Next module:** [03 — Attendance & Leave](03-attendance-leave.md)
