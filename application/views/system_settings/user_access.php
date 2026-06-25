<?php $this->load->view('partials/header', ['title' => 'User Access Settings']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-2">
      <i class="bi bi-people-fill me-2"></i>User Access Settings
    </h1>
    <p class="text-muted mb-0">Configure individual user access to system modules</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?php echo site_url('system-settings'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-gear me-1"></i>General Settings
    </a>
    <a href="<?php echo site_url('system-settings/permissions'); ?>" class="btn btn-outline-primary">
      <i class="bi bi-shield-lock me-1"></i>Permissions
    </a>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-light">
        <h5 class="mb-0">
          <i class="bi bi-person-check me-2"></i>Individual User Module Access
        </h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('system-settings/update_user_access'); ?>">
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="table-light">
                <tr>
                  <th rowspan="2" class="align-middle">User</th>
                  <th colspan="<?php echo count($enabled_modules); ?>" class="text-center">Module Access</th>
                </tr>
                <tr>
                  <?php foreach ($enabled_modules as $module => $enabled): ?>
                    <?php if ($enabled): ?>
                      <th class="text-center" style="min-width: 100px;">
                        <div class="d-flex flex-column align-items-center">
                          <i class="bi bi-<?php echo get_module_icon($module); ?> mb-1"></i>
                          <?php echo ucfirst(str_replace('_', ' ', $module)); ?>
                        </div>
                      </th>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $user): ?>
                  <tr>
                    <td class="align-middle">
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm bg-primary text-white me-2">
                          <?php echo strtoupper(substr($user->full_name ?: $user->email, 0, 2)); ?>
                        </div>
                        <div>
                          <div class="fw-semibold"><?php echo esc_view($user->full_name ?: 'N/A'); ?></div>
                          <small class="text-muted"><?php echo esc_view($user->email); ?></small>
                          <br>
                          <span class="badge bg-<?php echo get_role_color($user->role_id); ?> me-1">
                            <?php echo esc_view($user->role_name); ?>
                          </span>
                        </div>
                      </div>
                    </td>
                    
                    <?php foreach ($enabled_modules as $module => $enabled): ?>
                      <?php if ($enabled): ?>
                        <td class="text-center">
                          <div class="form-check form-switch">
                            <input type="hidden" 
                                   name="user_access[<?php echo $user->id; ?>][<?php echo $module; ?>]" 
                                   value="0">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   name="user_access[<?php echo $user->id; ?>][<?php echo $module; ?>]" 
                                   value="1" 
                                   <?php echo (isset($user_access[$user->id][$module]) && $user_access[$user->id][$module]) ? 'checked' : ''; ?>>
                          </div>
                        </td>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <div class="mt-3">
            <div class="row">
              <div class="col-md-6">
                <h6>Quick Actions:</h6>
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-success" onclick="selectAllAccess()">
                    <i class="bi bi-check-all"></i> Select All
                  </button>
                  <button type="button" class="btn btn-outline-danger" onclick="clearAllAccess()">
                    <i class="bi bi-x-square"></i> Clear All
                  </button>
                  <button type="button" class="btn btn-outline-primary" onclick="applyRoleBasedAccess()">
                    <i class="bi bi-shield-check"></i> Apply Role Defaults
                  </button>
                </div>
              </div>
              <div class="col-md-6">
                <h6>Access Summary:</h6>
                <div class="d-flex gap-3">
                  <small><strong>Total Users:</strong> <?php echo count($users); ?></small>
                  <small><strong>Enabled Modules:</strong> <?php echo count(array_filter($enabled_modules)); ?></small>
                  <small><strong>Access Controls:</strong> Active</small>
                </div>
              </div>
            </div>
          </div>
          
          <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save me-1"></i>Save Access Settings
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
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0">
          <i class="bi bi-info-circle me-2"></i>Access Control Information
        </h6>
      </div>
      <div class="card-body">
        <h6>How Access Control Works:</h6>
        <ol class="small">
          <li><strong>Global Settings:</strong> Modules must be enabled globally</li>
          <li><strong>Role Permissions:</strong> Users get default access based on role</li>
          <li><strong>Individual Access:</strong> Override role defaults per user</li>
          <li><strong>Priority:</strong> Individual access > Role permissions > Global settings</li>
        </ol>
        
        <h6 class="mt-3">Access Levels:</h6>
        <ul class="small">
          <li><strong>Enabled:</strong> User can access the module</li>
          <li><strong>Disabled:</strong> User cannot see or access the module</li>
          <li><strong>Inherited:</strong> Uses role-based default permissions</li>
        </ul>
      </div>
    </div>
  </div>
  
  <div class="col-md-6">
    <div class="card">
      <div class="card-header bg-light">
        <h6 class="mb-0">
          <i class="bi bi-graph-up me-2"></i>Access Statistics
        </h6>
      </div>
      <div class="card-body">
        <?php
        $total_access = 0;
        $total_possible = count($users) * count(array_filter($enabled_modules));
        
        foreach ($users as $user) {
          foreach ($enabled_modules as $module => $enabled) {
            if ($enabled && isset($user_access[$user->id][$module]) && $user_access[$user->id][$module]) {
              $total_access++;
            }
          }
        }
        
        $access_percentage = $total_possible > 0 ? round(($total_access / $total_possible) * 100, 1) : 0;
        ?>
        
        <div class="mb-3">
          <div class="d-flex justify-content-between mb-1">
            <small>Overall Access Rate</small>
            <small><strong><?php echo $access_percentage; ?>%</strong></small>
          </div>
          <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-success" style="width: <?php echo $access_percentage; ?>%">
              <?php echo $total_access; ?> / <?php echo $total_possible; ?>
            </div>
          </div>
        </div>
        
        <div class="row text-center">
          <div class="col-4">
            <div class="border rounded p-2">
              <div class="h4 mb-0 text-primary"><?php echo count($users); ?></div>
              <small>Total Users</small>
            </div>
          </div>
          <div class="col-4">
            <div class="border rounded p-2">
              <div class="h4 mb-0 text-success"><?php echo count(array_filter($enabled_modules)); ?></div>
              <small>Active Modules</small>
            </div>
          </div>
          <div class="col-4">
            <div class="border rounded p-2">
              <div class="h4 mb-0 text-info"><?php echo $total_access; ?></div>
              <small>Access Granted</small>
            </div>
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
        'payroll' => 'currency-dollar'
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
function selectAllAccess() {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
}

function clearAllAccess() {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
}

function applyRoleBasedAccess() {
    // Apply role-based default access
    const roleDefaults = {
        1: { // Admin - All modules
            'dashboard': true,
            'tasks': true,
            'projects': true,
            'employees': true,
            'attendance': true,
            'leave_requests': true,
            'announcements': true,
            'reports': true,
            'timesheets': true,
            'payroll': true
        },
        2: { // Manager - Most modules
            'dashboard': true,
            'tasks': true,
            'projects': true,
            'employees': false,
            'attendance': true,
            'leave_requests': true,
            'announcements': true,
            'reports': true,
            'timesheets': true,
            'payroll': true
        },
        3: { // Lead - Core modules
            'dashboard': true,
            'tasks': true,
            'projects': true,
            'employees': false,
            'attendance': true,
            'leave_requests': true,
            'announcements': true,
            'reports': false,
            'timesheets': true,
            'payroll': false
        },
        4: { // Staff - Basic modules
            'dashboard': true,
            'tasks': true,
            'projects': true,
            'employees': false,
            'attendance': true,
            'leave_requests': true,
            'announcements': true,
            'reports': false,
            'timesheets': true,
            'payroll': false
        }
    };
    
    <?php foreach ($users as $user): ?>
        const userRole = <?php echo $user->role_id; ?>;
        const userDefaults = roleDefaults[userRole];
        
        if (userDefaults) {
            <?php foreach ($enabled_modules as $module => $enabled): ?>
                <?php if ($enabled): ?>
                    const checkbox = document.querySelector('input[name="user_access[<?php echo $user->id; ?>][<?php echo $module; ?>]");
                    if (checkbox) {
                        checkbox.checked = userDefaults['<?php echo $module; ?>'] || false;
                    }
                <?php endif; ?>
            <?php endforeach; ?>
        }
    <?php endforeach; ?>
    
    alert('Role-based access defaults applied');
}
</script>

<?php $this->load->view('partials/footer'); ?>
