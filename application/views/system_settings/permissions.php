<?php $this->load->view('partials/header', ['title' => 'Permission Settings']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-2">
      <i class="bi bi-shield-lock me-2"></i>Permission Settings
    </h1>
    <p class="text-muted mb-0">Configure role-based permissions for system modules</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo site_url('system-settings'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-gear me-1"></i>General Settings
    </a>
    <a href="<?php echo site_url('system-settings/user-access'); ?>" class="btn btn-outline-info">
      <i class="bi bi-people me-1"></i>User Access
    </a>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-light">
        <h5 class="mb-0">
          <i class="bi bi-shield-check me-2"></i>Role-Based Permissions Matrix
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('system-settings/update_permissions'); ?>">
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="table-light">
                <tr>
                  <th rowspan="2" class="align-middle">Role</th>
                  <th colspan="<?php echo count($modules); ?>" class="text-center">Modules</th>
                </tr>
                <tr>
                  <?php foreach ($modules as $module): ?>
                    <th class="text-center" style="min-width: 120px;">
                      <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-<?php echo get_module_icon($module->module); ?> mb-1"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $module->module)); ?>
                      </div>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($roles as $role): ?>
                  <tr>
                    <td class="fw-semibold align-middle">
                      <div class="d-flex align-items-center">
                        <span class="badge bg-<?php echo get_role_color($role->id); ?> me-2">
                          <?php echo htmlspecialchars($role->name); ?>
                        </span>
                      </div>
                    </td>
                    
                    <?php foreach ($modules as $module): ?>
                      <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                          <?php 
                          $permissions = ['view', 'add', 'edit', 'delete', 'list'];
                          foreach ($permissions as $perm): 
                            $is_allowed = isset($permissions_matrix[$role->id][$module->module][$perm]) ? 
                                          $permissions_matrix[$role->id][$module->module][$perm] : false;
                          ?>
                          <div class="form-check form-check-inline">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   name="permissions[<?php echo $role->id; ?>][<?php echo $module->module; ?>][<?php echo $perm; ?>]" 
                                   value="1" 
                                   <?php echo $is_allowed ? 'checked' : ''; ?>
                                   title="<?php echo ucfirst($perm); ?>">
                            </div>
                          <?php endforeach; ?>
                        </div>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <div class="mt-3">
            <div class="row">
              <div class="col-md-6">
                <h6>Permission Legend:</h6>
                <div class="d-flex flex-wrap gap-2">
                  <span class="badge bg-secondary">view</span>
                  <span class="badge bg-secondary">add</span>
                  <span class="badge bg-secondary">edit</span>
                  <span class="badge bg-secondary">delete</span>
                  <span class="badge bg-secondary">list</span>
                </div>
              </div>
              <div class="col-md-6">
                <h6>Quick Actions:</h6>
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-success" onclick="selectAllPermissions()">
                    <i class="bi bi-check-all"></i> Select All
                  </button>
                  <button type="button" class="btn btn-outline-danger" onclick="clearAllPermissions()">
                    <i class="bi bi-x-square"></i> Clear All
                  </button>
                  <button type="button" class="btn btn-outline-primary" onclick="applyRoleTemplate()">
                    <i class="bi bi-brush"></i> Apply Template
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save me-1"></i>Save Permissions
            </button>
            <a href="<?php echo site_url('system-settings'); ?>" class="btn btn-outline-secondary">
              <i class="bi bi-x-lg me-1"></i>Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0">
          <i class="bi bi-info-circle me-2"></i>Permission Guidelines
        </h6>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <h6>Role Definitions:</h6>
            <ul class="small">
              <li><strong>Admin:</strong> Full access to all modules and settings</li>
              <li><strong>Manager:</strong> Can manage team, projects, and approve requests</li>
              <li><strong>Lead:</strong> Can manage tasks and team activities</li>
              <li><strong>Staff:</strong> Can view and manage own data only</li>
            </ul>
          </div>
          <div class="col-md-6">
            <h6>Permission Types:</h6>
            <ul class="small">
              <li><strong>view:</strong> View module content</li>
              <li><strong>add:</strong> Create new items</li>
              <li><strong>edit:</strong> Modify existing items</li>
              <li><strong>delete:</strong> Remove items</li>
              <li><strong>list:</strong> View list/index pages</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
function get_module_icon($module) {
    $icons = [
        'dashboard' => 'grid-3x3-gap-fill',
        'tasks' => 'list-check',
        'projects' => 'folder',
        'employees' => 'people',
        'attendance' => 'clock',
        'leave_requests' => 'calendar-check',
        'announcements' => 'megaphone',
        'reports' => 'graph-up',
        'timesheets' => 'clock-history',
        'payroll' => 'currency-dollar',
        'users' => 'person',
        'settings' => 'gear',
        'email_settings' => 'envelope',
        'system_settings' => 'gear-fill',
        'external_training' => 'play-btn',
    ];
    return isset($icons[$module]) ? $icons[$module] : 'grid';
}

function get_role_color($role_id) {
    $colors = [
        1 => 'danger',    // Admin
        2 => 'warning',  // Manager
        3 => 'info',     // Lead
        4 => 'primary'   // Staff
    ];
    return isset($colors[$role_id]) ? $colors[$role_id] : 'secondary';
}
?>

<script>
function selectAllPermissions() {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
}

function clearAllPermissions() {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
}

function applyRoleTemplate() {
    const role = prompt('Enter role ID to apply template (1=Admin, 2=Manager, 3=Lead, 4=Staff):');
    if (!role) return;
    
    const templates = {
        1: { // Admin - All permissions
            'dashboard': ['view', 'edit'],
            'tasks': ['view', 'add', 'edit', 'delete', 'list'],
            'projects': ['view', 'add', 'edit', 'delete', 'list'],
            'employees': ['view', 'add', 'edit', 'delete', 'list'],
            'attendance': ['view', 'add', 'edit', 'delete', 'list'],
            'leave_requests': ['view', 'add', 'edit', 'delete', 'list'],
            'announcements': ['view', 'add', 'edit', 'delete', 'list'],
            'reports': ['view', 'generate'],
            'timesheets': ['view', 'add', 'edit', 'delete', 'list'],
            'payroll': ['view', 'add', 'edit', 'delete', 'list'],
            'external_training': ['view', 'add', 'edit', 'delete', 'list']
        },
        2: { // Manager
            'dashboard': ['view', 'edit'],
            'tasks': ['view', 'add', 'edit', 'list'],
            'projects': ['view', 'add', 'edit', 'list'],
            'employees': ['view', 'list'],
            'attendance': ['view', 'add', 'list'],
            'leave_requests': ['view', 'list'],
            'announcements': ['view', 'list'],
            'reports': ['view', 'generate'],
            'timesheets': ['view', 'list'],
            'payroll': ['view', 'list'],
            'external_training': ['view', 'add', 'edit', 'delete', 'list']
        },
        3: { // Lead
            'dashboard': ['view'],
            'tasks': ['view', 'add', 'edit', 'list'],
            'projects': ['view', 'list'],
            'employees': ['view', 'list'],
            'attendance': ['view', 'add', 'list'],
            'leave_requests': ['view', 'list'],
            'announcements': ['view', 'list'],
            'timesheets': ['view', 'add', 'list'],
            'external_training': ['view', 'list', 'add', 'edit']
        },
        4: { // Staff
            'dashboard': ['view'],
            'tasks': ['view', 'list'],
            'projects': ['view', 'list'],
            'employees': ['view'],
            'attendance': ['view', 'add', 'list'],
            'leave_requests': ['view', 'add', 'list'],
            'announcements': ['view', 'list'],
            'timesheets': ['view', 'add', 'list'],
            'external_training': ['view', 'list']
        }
    };
    
    const template = templates[role];
    if (!template) {
        alert('Invalid role ID');
        return;
    }
    
    // Clear all checkboxes first
    clearAllPermissions();
    
    // Apply template permissions
    Object.keys(template).forEach(module => {
        template[module].forEach(permission => {
            const checkbox = document.querySelector(`input[name="permissions[${role}][${module}][${permission}]`);
            if (checkbox) checkbox.checked = true;
        });
    });
    
    alert(`Template applied for role ${role}`);
}
</script>

<?php $this->load->view('partials/footer'); ?>
