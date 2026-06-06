# Development Guidelines — Office Management System

## 1. Framework & Runtime

- **Framework:** CodeIgniter 3 — follow CI3 conventions exclusively
- **Primary runtime:** PHP 8.4 (WAMP)
- **Database:** MySQL via mysqli, Query Builder enabled
- **Do NOT introduce:** Laravel, Symfony, Composer packages, DI containers, ORM (Eloquent/Doctrine), PSR-7 middleware

---

## 2. Project Structure Rules

| Artifact | Location | Naming |
|----------|----------|--------|
| Controllers | `application/controllers/` | PascalCase file, lowercase URL via routes |
| Models | `application/models/` | `{Name}_model.php` |
| Views | `application/views/{module}/` | snake_case filenames |
| Helpers | `application/helpers/` | `{name}_helper.php` |
| Libraries | `application/libraries/` | PascalCase |
| Config | `application/config/` | lowercase |
| Routes | `application/config/routes.php` | Add explicit friendly URLs |

---

## 3. Controller Patterns

### Standard controller template
```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Example extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        require_module_access('example');
        $this->load->model('Example_model', 'example');
    }

    public function index()
    {
        $data['items'] = $this->example->get_all();
        $this->load->view('example/index', $data);
    }
}
```

### Coaching module controllers
- Extend `Coaching_Controller`, not `CI_Controller`
- Set `protected $coaching_permission = 'coaching_billing';` as needed
- Use `coaching_helper` functions for schema and notifications

### Rules
- Always call `parent::__construct()` first
- Load models in constructor, not in every method
- Use `require_module_access()` for RBAC
- Return views via `$this->load->view()`, not echo directly
- AJAX responses: set JSON header, `json_encode()`, `exit` or return

---

## 4. Model Patterns

```php
class Example_model extends CI_Model {

    public function get_all()
    {
        return $this->db->order_by('id', 'DESC')->get('examples')->result();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', (int)$id)->get('examples')->row();
    }
}
```

### Rules
- Extend `CI_Model`
- Use Query Builder for all user-input queries
- Cast IDs to `(int)` in where clauses
- Use `$this->db->trans_start()` / `trans_complete()` for multi-step writes
- New tables: prefer CI migration files over runtime `ensure_schema()` (legacy pattern exists but avoid extending it)
- Do NOT put HTTP/redirect logic in models

---

## 5. View Patterns

- Use layout partials: `partials/header`, `partials/sidebar`, `partials/footer`
- Escape output: `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`
- Include CSRF hidden field in forms: `<?= form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()) ?>`
- No business logic in views — only display logic
- Use existing Bootstrap/CSS classes from `assets/`

---

## 6. Helper Usage

### Autoloaded helpers (do NOT reload inline)
`url`, `form`, `download`, `permission`, `attendance`, `training`, `company`, `api_integration`, `error_handler`, `notification`, `hierarchy_filter`, `data_scope`, `coaching`

### Common functions
```php
require_module_access('tasks');        // Redirect if denied
has_module_access('reports');        // Boolean check
apply_role_hierarchy_filter($db, 'u.id');  // Scope queries
get_company_name();                  // Dynamic branding
send_notification($user_id, $msg);   // Notification helper
```

---

## 7. Routing

- Add routes to `application/config/routes.php` — do NOT rely on default CI routing for new modules
- Use kebab-case URLs: `$route['my-module/create'] = 'my_module/create';`
- Public routes must be added to `AuthHook` public URI whitelist

---

## 8. Security Requirements

| Rule | Implementation |
|------|----------------|
| SQL injection | Query Builder only; bind params for raw SQL |
| XSS | `htmlspecialchars()` on all user output |
| CSRF | Include token in forms; do NOT add new CSRF exclusions without review |
| Auth | All new controllers require AuthHook whitelist OR session auth |
| File uploads | Validate mime type, size, extension; store outside webroot if sensitive |
| Passwords | `password_hash()` / `password_verify()` only |
| Secrets | Store in `settings` or `api_integrations` table, not in code |

---

## 9. Coding Style

```php
// REQUIRED: Always use braces
if ($condition)
{
    do_something();
}

// REQUIRED: No nested ternary
$status = ($active) ? 'active' : 'inactive';

// REQUIRED: No single-line if without braces
if ($x) { return; }  // OK with braces

// Naming: snake_case for methods (CI convention)
public function get_by_id($id) { }

// Naming: snake_case for variables
$user_id = (int)$this->session->userdata('user_id');

// Constants: UPPER_SNAKE in constants.php
define('ROLE_ADMIN', 1);
```

---

## 10. Database Changes

1. **Never modify schema in production without approval**
2. Create migration file in `application/migrations/`
3. Provide rollback SQL in migration `down()` method
4. Update `DATABASE_DOCUMENTATION.md` when adding tables
5. Add permission seed if new module requires RBAC
6. Register in `schema_automation.php` only if lazy bootstrap is required

---

## 11. Adding a New Module Checklist

- [ ] Controller extending `CI_Controller`
- [ ] Model extending `CI_Model`
- [ ] Views in `application/views/{module}/`
- [ ] Routes in `routes.php`
- [ ] Permission key in `Permissions` controller module list
- [ ] `require_module_access('module_key')` in constructor
- [ ] AuthHook controller→module mapping (if not using require_module_access alone)
- [ ] Sidebar link in `partials/sidebar.php`
- [ ] Migration file for new tables
- [ ] Seed permissions SQL if needed

---

## 12. What NOT To Do

- Do NOT change business logic unless explicitly requested
- Do NOT modify API/JSON response shapes without approval
- Do NOT modify database structure without approval
- Do NOT modify UI behavior/layout without approval
- Do NOT create unnecessary helper methods or libraries
- Do NOT over-engineer (no service layers, repositories, event buses)
- Do NOT introduce Laravel/Symfony patterns
- Do NOT use PHP 8+ typed properties in application code (maintain CI3 compatibility)
- Do NOT add Composer dependencies without approval

---

## 13. PHP Version Notes

- **Production target:** PHP 8.4
- **system/ core:** Patched for PHP 8.4 (AllowDynamicProperties, E_STRICT, session.sid_length)
- **Application code:** Write CI3-compatible PHP without strict types, enums, or constructor property promotion
- Avoid deprecated functions: `each()`, `create_function()`, `FILTER_SANITIZE_STRING`

---

## 14. Debugging

- Logs: `application/logs/` (threshold=1 in config)
- Enable profiler: `$this->output->enable_profiler(TRUE)` in development
- Database debug: `$this->db->last_query()` after queries
- Use `log_message('error', 'message')` for production-safe logging
- Never leave `var_dump`/`print_r` in committed code
