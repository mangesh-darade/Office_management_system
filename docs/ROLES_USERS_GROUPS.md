# Roles, Group Type ani Users — Business + Technical Logic

Bhasha: Roman Marathi. Source verified: `Roles.php`, `Users.php`, `Auth.php`, `Permissions.php`,
`permission_helper.php`, `hierarchy_filter_helper.php`, `data_scope_helper.php`, `org_schema_helper.php`.

---

## 1. Role = padavi

**Business:** Company madhli padavi / kaam cha prakar. Role ne thartey ki vyakti **kon screens vaparu shakto** (Permissions matrix) ani **kiti data baghto** (group type + hierarchy).

**Table `roles`:** `id`, `name`, `group_type` (`admin`|`user`), `is_active`, `sort_order`.

**Default seed** (`org_schema_ensure_roles`, jar table rikame asel):

| id | name | group_type |
|---|---|---|
| 1 | Admin | admin |
| 2 | Manager | admin |
| 3 | Lead | admin |
| 4 | Staff | user |

Constants (`config/constants.php`): `ROLE_ADMIN=1`, `ROLE_MANAGER=2`, `ROLE_LEAD=3`, `ROLE_STAFF=4`, `ROLE_USER=ROLE_STAFF`.

**Role management (`Roles.php`):**
- **Create (`store`)**: permission `roles` kinva `permissions` lagte. Name required, naav unique. `group_type` fakta `admin`/`user`, kahi dusre dile tar `user`. `sort_order` = max+1, `is_active=1`. Change tracker madhe log.
- **Update**: fakta **name + group_type** badalta yete. Naav unique (swatala vagalun).
- **Delete**: POST only. Je role var kontahi user assigned aahe to delete hot nahi (user chi naave dakhvun block).
- Role chi **permissions** `Permissions` screen madhun thartat (tabel `permissions`: `role_id`, `module`, `can_access`, UNIQUE(role_id, module)). Save var upsert hoto, transaction madhe, fakta `permissions` module access aslelach karu shakto.

**Special rule:** Role id **1** la `has_module_access()` nehami true det (lock-out pasun sanrakshan). Matrix madhe kahihi asla tari.

**Role che don vegle upyog:**
1. **Screen access** → `has_module_access($module)` (matrix madhun).
2. **Data visibility** → `get_accessible_hierarchy_user_ids()` (role naav/id + group_type madhun).

---

## 2. Group type = role cha tier (Admin tier / User tier)

**Business:** Ek switch jo thartvto ki role "sagla org data baghnara" aahe ki "fakta swatacha/team cha".

| group_type | Arth | Parinam |
|---|---|---|
| `admin` | Admin tier | `hierarchy_filter_sees_all_records()` = true → **sagle records**, koni filter nahi (`get_accessible_hierarchy_user_ids` = `[]`) |
| `user` | User tier | Scope **designation key** pramane: **own** = swatache + assign kelele, **all** = department cha data (section 2b) |

**Functions:**
- `is_admin_group()` → `roles.group_type == 'admin'`. Fallback (jar `group_type` column nasel): role id **1,2,3**.
- `is_user_group()` → `group_type == 'user'`. Fallback: role id **4**.
- `hierarchy_filter_sees_all_records($role_id)` → true jar: `ROLE_ADMIN`, kinva role naav `admin`, kinva `group_type='admin'`. Fallback madhe Manager/Lead **ghet nahi**.
- `data_scope_sees_all_org_data()` → upper function cha wrapper (session role var).
- `data_scope_apply_created_by_user_group()` / `data_scope_apply_user_id_column()` → fakta **user-group** la `WHERE created_by = me` lavtat.

**Mahatvache parinam:**
- Default madhe **Manager ani Lead pan `admin` tier madhe** → te org-wide data baghtat. Tyanna department/own scope pahije asel tar `group_type=user` + `data_scope` set kara.
- **Custom role + `group_type=admin`** = Admin sarkha data visibility (naav kahihi asle tari). Navin role banavtana he sarvat risky nirnay.
- Group type **screen access deto nahi**. Screen access fakta matrix madhun.

**Privilege-escalation guard (`Users::roles()`):** Jar logged-in user `is_user_group()` asel tar user-create/edit dropdown madhe fakta `group_type='user'` che roles disatat — mhanje user-tier manus admin-tier role koni la dein shakat nahi.

---

## 2b. Data scope (own / all) — NAVIN LOGIC

**Rule:** Admin group = sagla data. User group = user cha **designation key** (hardcoded, `designations.designation_key`): `manager`/`lead`/`hr` = **all**, `developer`/`intern` = **own**. Display names multiple (Elintom Lead, Srujen Lead -> key `lead`). Map: `designation_keys()` in `hierarchy_filter_helper.php`. Roles var data_scope nahi. Access departments nasel tar user cha swatacha employee department vaprala jato.

(Juna table, role-based:)

| data_scope | Records kay distat |
|---|---|
| `own` (default) | Swatane **create kelele** + konitari tyala **assign kele** (assigned_to / assignees / manager / member) |
| `all` | Tyala **assigned departments** madhle **sagle users** che records (created ya assigned) + swatache |

- Manager dept / Lead team (`lead_user_mapping`) / SPL group expansion **kadhun takla**. Te pahije asel tar role la `data_scope=all` + departments assign kara.
- **Role var setting:** Roles screen → "Data scope" dropdown (fakta User Group la lagu; Admin group la nehami sagle).
- **Departments assign:** Users create/edit → "Access departments" multi-select (fakta admin group disato/badalto). Table `user_departments` (`user_id`, `department_id`, UNIQUE pair). Department madhle users = `employees.department` == `departments.dept_name`.
- **Schema** auto banto (`org_schema_ensure_roles` -> `roles.data_scope`, `org_schema_ensure_user_departments`; lazy via `hierarchy_ensure_scope_schema()`).

**Code:**
- `hierarchy_filter_helper.php`: `data_scope_role_mode()` (org|all|own), `get_user_department_ids()`, `get_department_member_user_ids()`, `get_accessible_hierarchy_user_ids()` (org => `[]`, all => dept members + self, own => `[self]`).
- `data_scope_helper.php`: `data_scope_visible_user_ids()` (null = no limit), `data_scope_apply_owner_or_assignee($db, $cols, $junctions)`, `data_scope_row_visible($row, $fields, $extra)`, `data_scope_apply_project_scope()`, `data_scope_project_visible()`.

**Modules (created OR assigned):**

| Module | Columns |
|---|---|
| Tasks | `created_by`, `assigned_to`, `task_assignees` (`multi_assignees_apply_hierarchy_filter(..., created_col)`); edit guard `_task_in_scope` |
| Requirements | `created_by`, `assigned_to`, `owner_id`, `requirement_assignees` (`Requirement_model::apply_scope`) |
| Defects | `reported_by`, `assigned_to` (`Defect_model`) |
| Clients | `created_by`, `account_manager_id` (`Client_model::enable_scope()` fakta Clients controller madhe); client chya tasks/requirements/defects/projects pan scoped |
| Projects | `created_by`, `manager_id`, `project_members` (`Project_model::all`, `_user_can_access_project`) |
| Releases | Visible projects var (`defects_releases_scoped_project_ids`) |
| My Works (ad hoc) | `created_by`, `created_for`, `my_works_assignees` (`my_works_apply_list_scope`); Overview tab personal (swatahcha) rahila |
| Users list | hierarchy ids (own = swatah, all = dept members) jar `users_view_all` |

**Legacy areas (aata scoped):** Daily Pulse (clients/requirements/defects = created OR assigned), Reports (`Report_model`, `Reports`, `Reports_projects`: tasks/requirements/projects = created OR assigned; projects via `data_scope_apply_project_scope`), My Works project dropdowns, Chats user list (`visible_ids`). Person-based data (Attendance, Leaves, Payroll, Expenses, Timesheets, HR reports) = own/department user ids via `apply_role_hierarchy_filter` (assigned concept nahi). Dashboard cards personal/announcements, scope lagat nahi.

## 3. User = vyakti (ek user → ek role)

**Table `users`:** `id`, `email` (unique), `password_hash`, `role_id`, `status` (active/inactive), `name`, `phone`, `avatar`, `is_verified`, `last_login_at`. **Ek user = ek `role_id`** (many-to-many nahi).

**`employees` table:** `user_id` ne link; `department`, `designation`, `reporting_to`, shift. Manager cha "department peers" ithun yetat. User save kelyavar `_sync_linked_employee()` ne name/email/department/designation employee var sync hoto.

### User cha lifecycle

1. **Create (`Users::store`)**, permission `users_add` kinva `users`:
   - Name, email required; email valid + unique; phone 10-digit + unique.
   - `role_id` required ani **active roles list madhla** asava (`roles()` che filter lagte).
   - `status` required.
   - Avatar upload, change-tracker log, employee sync.
   - `users.role` (string) pan derive hoto: role naav lowercase, space → `_`.
2. **Public registration (`Auth`)**: email OTP verify, **nehami `ROLE_STAFF`**, status `active`. Posted `role_id` ignore.
3. **Login (`Auth`)**:
   - `status != 'active'` asel tar login block ("inactive/suspended, contact administrator").
   - Login jhalyavar session madhe `user_id` ani **`role_id`** set hoto. Pudhche sagle checks he `role_id` session var avlambun astat.
   - Attempt limit: 5 chukichya prayatnanantar 15 min lock.
4. **Edit (`Users::update`)**, permission `users_edit` kinva `users`:
   - **Hierarchy check:** `get_accessible_hierarchy_user_ids()`; list rikami nasel ani target user tyat nasel tar 403. Mhanje Manager/Lead (user tier) fakta apalya scope cha user edit karu shakto.
   - Same validations. Password dilyas strength validation (blank = badalat nahi).
5. **Delete**: `delete`/`destroy`, POST only.

### Role badalla tar kay hote
User cha `role_id` badalla ki **pudhchya request pasun** matrix ani data scope navin role pramane lagto. Session madhe role_id jooni asu shakto tyamule user ne punha login karne safe.

---

## 4. Sagle ekatra — ek udaharan

> Rahul = Staff (role 4, user tier). Tyala `projects_list` permission aahe, `projects_add` nahi.
> - Project list ughadto ✔ (matrix).
> - Fakta jithe to member/manager/creator aahe te projects disatat (data scope).
> - "Add project" button disat nahi ✔.
> - Tyala Admin ne role "Manager" kela → Manager hi default madhe admin tier aahe → ata **sagle projects** disatat, pan matrix madhe `projects_add` nasel tar add karta yet nahi.

---

## 5. Known gaps / risks (code madhun)

1. **Manager/Lead default `admin` tier** → org-wide data. Business-intent asel tar thik, nahitar group_type `user` + `data_scope` kara.
2. **Per-user override nahi:** `user_module_access` table aahe pan `has_module_access` te vaparat nahi. Permission fakta per-role.
3. `is_admin_group()` fallback (1,2,3) ani `hierarchy_filter_sees_all_records()` fallback (fakta Admin) **vegle** — `group_type` column nasel tar behaviour ekmekanshi jat nahi.
4. `is_admin_group()` ani `is_user_group()` pratyek call la DB query karto (cache nahi).
5. (Fixed) Projects access aata member/manager/creator + scope pramane.
6. Role delete fakta `users.role_id` baghto; `permissions` rows cleanup baddal alag check kelele nahi.

---

## 6. Quick reference

| Prashna | Kuthe baghayche |
|---|---|
| Role chya permissions | `permissions` table, `Permissions.php`, `permission_helper.php::has_module_access` |
| Role cha tier | `roles.group_type`, `is_admin_group`, `is_user_group` |
| Konache records disatat | `hierarchy_filter_helper.php::get_accessible_hierarchy_user_ids`, `apply_role_hierarchy_filter` |
| User create/edit rules | `controllers/Users.php` |
| Login ani session role | `controllers/Auth.php` |
| Role CRUD | `controllers/Roles.php` |
| Team / group sharing | `lead_user_mapping`, `spl_groups`, `spl_group_members` |
