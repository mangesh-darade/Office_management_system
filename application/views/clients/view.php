<?php
$this->load->view('partials/header', ['title' => 'Client Details', 'extra_css' => ['assets/css/clients.css', 'assets/css/projects.css']]);

$client = isset($client) ? $client : null;
$requirements = isset($requirements) ? $requirements : array();
$tasks = isset($tasks) ? $tasks : array();
$defects = isset($defects) ? $defects : array();
$projects = isset($projects) ? $projects : array();
$assignable_users = isset($assignable_users) ? $assignable_users : array();
$can_manage_tasks = !empty($can_manage_tasks);
$can_manage_requirements = !empty($can_manage_requirements);
$can_manage_defects = !empty($can_manage_defects);
$can_delete_tasks = !empty($can_delete_tasks);
$can_delete_requirements = !empty($can_delete_requirements);
$can_delete_defects = !empty($can_delete_defects);

if (!$client) {
    show_404();
    return;
}

$client_id = (int) $client->id;
$company_name = isset($client->company_name) ? (string) $client->company_name : '';
$client_code = isset($client->client_code) ? (string) $client->client_code : '';
$client_status = isset($client->status) ? (string) $client->status : 'active';
$client_type = isset($client->client_type) ? (string) $client->client_type : '';

$history = isset($history) && is_array($history) ? $history : array();
$history_filters = isset($history_filters) && is_array($history_filters) ? $history_filters : array(
    'q' => '', 'action' => '', 'user_id' => 0, 'date_from' => '', 'date_to' => '',
);
$history_users = isset($history_users) && is_array($history_users) ? $history_users : array();
$can_edit = function_exists('has_module_access') && (has_module_access('clients_edit') || has_module_access('clients'));
$can_delete = function_exists('has_module_access') && (has_module_access('clients_delete') || has_module_access('clients'));
$can_note = function_exists('has_module_access') && (has_module_access('clients_view') || has_module_access('clients'));
$can_requirements = function_exists('has_module_access') && (has_module_access('requirements') || has_module_access('requirements_view'));
$can_tasks = function_exists('has_module_access') && (has_module_access('tasks') || has_module_access('tasks_view'));
$can_defects = function_exists('has_module_access') && (has_module_access('defects') || has_module_access('defects_list') || has_module_access('defects_view'));

$action_label = function ($action) {
    $map = array(
        'created' => 'Created',
        'updated' => 'Updated',
        'status_changed' => 'Status',
        'contact_changed' => 'Contact',
        'urls_changed' => 'URLs',
        'note' => 'Note',
        'comment' => 'Note',
        'commented' => 'Note',
    );
    $key = strtolower((string) $action);
    return isset($map[$key]) ? $map[$key] : ucfirst(str_replace('_', ' ', (string) $action));
};

$active_tab = trim((string) $this->input->get('tab'));
$allowed_tabs = array('overview', 'requirements', 'tasks', 'defects', 'history');
if ($active_tab === '' || !in_array($active_tab, $allowed_tabs, true)) {
    $active_tab = 'overview';
}

$task_statuses = array('pending', 'in_progress', 'completed', 'blocked');
$req_statuses = array('received', 'under_review', 'approved', 'in_progress', 'completed', 'on_hold', 'rejected', 'cancelled');
$defect_statuses = array('open', 'in_progress', 'fixed', 'verified', 'closed', 'rejected');
$task_priorities = array('low', 'medium', 'high', 'urgent');
$defect_priorities = array('low', 'medium', 'high', 'critical');

$client_view_user_label = function ($u) {
    if (isset($u->emp_name) && trim((string) $u->emp_name) !== '') {
        return trim((string) $u->emp_name);
    }
    if (isset($u->full_name) && trim((string) $u->full_name) !== '') {
        return trim((string) $u->full_name);
    }
    if (isset($u->name) && trim((string) $u->name) !== '') {
        return trim((string) $u->name);
    }
    return isset($u->email) ? (string) $u->email : '';
};

$assignee_label = function ($row) {
    if (!empty($row->assignee_name)) {
        return (string) $row->assignee_name;
    }
    if (!empty($row->assignee_email)) {
        $parts = explode('@', (string) $row->assignee_email);
        return $parts[0];
    }
    return '—';
};

ob_start();
foreach ($assignable_users as $u) {
    echo '<option value="' . (int) $u->id . '">' . esc_view($client_view_user_label($u)) . '</option>';
}
$inline_user_options = ob_get_clean();

ob_start();
echo '<option value="">No project</option>';
foreach ($projects as $p) {
    echo '<option value="' . (int) $p->id . '">' . esc_view($p->name) . '</option>';
}
$inline_project_options_optional = ob_get_clean();

ob_start();
echo '<option value="">Select project</option>';
foreach ($projects as $p) {
    echo '<option value="' . (int) $p->id . '">' . esc_view($p->name) . '</option>';
}
$inline_project_options_required = ob_get_clean();

$has_client_projects = !empty($projects);
$can_add_project = function_exists('has_module_access') && (has_module_access('projects_add') || has_module_access('projects'));
$create_project_url = site_url('projects/create?client_id=' . $client_id);

$status_class = 'secondary';
$st_lower = strtolower($client_status);
if ($st_lower === 'active') {
    $status_class = 'primary';
} elseif ($st_lower === 'prospect') {
    $status_class = 'info';
} elseif ($st_lower === 'inactive') {
    $status_class = 'secondary';
} elseif ($st_lower === 'blocked') {
    $status_class = 'danger';
}
?>

<div class="container-fluid py-1 px-2 client-detail-page client-detail-compact">
  <?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success py-1 px-2 mb-1 small"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger py-1 px-2 mb-1 small"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="client-detail-toolbar mb-1">
    <a class="client-detail-back" href="<?php echo site_url('clients'); ?>" title="Back to Clients">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div class="client-detail-title-row">
      <a class="client-detail-crumb" href="<?php echo site_url('clients'); ?>">Clients</a>
      <span class="client-detail-sep" aria-hidden="true">/</span>
      <?php if ($client_code !== ''): ?>
      <span class="client-detail-code"><?php echo esc_view($client_code); ?></span>
      <span class="client-detail-dot" aria-hidden="true">·</span>
      <?php endif; ?>
      <h1 class="client-detail-name" title="<?php echo esc_view($company_name); ?>"><?php echo esc_view($company_name); ?></h1>
      <?php if ($client_type !== ''): ?>
      <span class="badge client-detail-pill"><?php echo esc_view(ucfirst($client_type)); ?></span>
      <?php endif; ?>
      <span class="badge bg-<?php echo esc_view($status_class); ?>"><?php echo esc_view(ucfirst($client_status)); ?></span>
    </div>
    <div class="client-detail-actions">
      <?php if ($can_edit): ?>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('clients/edit/' . $client_id); ?>" title="Edit client"><i class="bi bi-pencil"></i></a>
      <?php endif; ?>
      <?php if ($can_delete): ?>
      <button type="button" class="btn btn-danger btn-sm" title="Delete client"
              onclick="confirmDeleteClient(<?php echo $client_id; ?>, <?php echo htmlspecialchars(json_encode($company_name), ENT_QUOTES, 'UTF-8'); ?>)">
        <i class="bi bi-trash"></i>
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="client-detail-stats mb-1">
    <div class="client-detail-stat-card">
      <span class="client-detail-stat-label">Requirements</span>
      <strong class="client-detail-stat-value" id="clientStatRequirements"><?php echo count($requirements); ?></strong>
    </div>
    <div class="client-detail-stat-card">
      <span class="client-detail-stat-label">Tasks</span>
      <strong class="client-detail-stat-value" id="clientStatTasks"><?php echo count($tasks); ?></strong>
    </div>
    <div class="client-detail-stat-card">
      <span class="client-detail-stat-label">Defects</span>
      <strong class="client-detail-stat-value" id="clientStatDefects"><?php echo count($defects); ?></strong>
    </div>
    <div class="client-detail-stat-card client-detail-stat-meta">
      <span class="client-detail-stat-label">Contact</span>
      <strong class="client-detail-stat-value client-detail-stat-value-sm text-truncate" title="<?php echo esc_view(isset($client->contact_person) ? $client->contact_person : ''); ?>"><?php echo esc_view(isset($client->contact_person) && $client->contact_person !== '' ? $client->contact_person : '—'); ?></strong>
      <div class="client-detail-dates text-muted">
        <span><i class="bi bi-telephone"></i> <?php echo esc_view(isset($client->phone) && $client->phone !== '' ? $client->phone : '—'); ?></span>
      </div>
    </div>
  </div>

  <ul class="nav nav-tabs nav-tabs-sm mb-1 client-detail-tabs" id="clientTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link<?php echo $active_tab === 'overview' ? ' active' : ''; ?>" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="<?php echo $active_tab === 'overview' ? 'true' : 'false'; ?>">
        Overview
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link<?php echo $active_tab === 'requirements' ? ' active' : ''; ?>" id="requirements-tab" data-bs-toggle="tab" data-bs-target="#requirements" type="button" role="tab" aria-controls="requirements" aria-selected="<?php echo $active_tab === 'requirements' ? 'true' : 'false'; ?>">
        Requirements <span class="badge rounded-pill bg-light text-dark border ms-1" id="clientBadgeRequirements"><?php echo count($requirements); ?></span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link<?php echo $active_tab === 'tasks' ? ' active' : ''; ?>" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab" aria-controls="tasks" aria-selected="<?php echo $active_tab === 'tasks' ? 'true' : 'false'; ?>">
        Tasks <span class="badge rounded-pill bg-light text-dark border ms-1" id="clientBadgeTasks"><?php echo count($tasks); ?></span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link<?php echo $active_tab === 'defects' ? ' active' : ''; ?>" id="defects-tab" data-bs-toggle="tab" data-bs-target="#defects" type="button" role="tab" aria-controls="defects" aria-selected="<?php echo $active_tab === 'defects' ? 'true' : 'false'; ?>">
        Defects <span class="badge rounded-pill bg-light text-dark border ms-1" id="clientBadgeDefects"><?php echo count($defects); ?></span>
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link<?php echo $active_tab === 'history' ? ' active' : ''; ?>" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="<?php echo $active_tab === 'history' ? 'true' : 'false'; ?>">
        History <span class="badge rounded-pill bg-light text-dark border ms-1" id="clientBadgeHistory"><?php echo count($history); ?></span>
      </button>
    </li>
  </ul>

  <div class="tab-content" id="clientTabsContent">
    <!-- Overview -->
    <div class="tab-pane fade<?php echo $active_tab === 'overview' ? ' show active' : ''; ?>" id="overview" role="tabpanel" aria-labelledby="overview-tab">
      <div class="row g-1">
        <div class="col-lg-8">
          <div class="card shadow-sm border-0 client-detail-panel mb-1">
            <div class="card-header bg-white">
              <h6 class="mb-0">Client details</h6>
            </div>
            <div class="card-body">
              <div class="client-detail-dl">
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Contact</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->contact_person) ? $client->contact_person : ''); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Email</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->email) ? $client->email : ''); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Phone</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->phone) ? $client->phone : ''); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Alt phone</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->alternate_phone) ? $client->alternate_phone : ''); ?></span>
                </div>
                <div class="client-detail-dl-item client-detail-dl-wide">
                  <span class="client-detail-dl-label">Address</span>
                  <span class="client-detail-dl-value"><?php echo nl2br(esc_view(isset($client->address) ? $client->address : '')); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">City</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->city) ? $client->city : ''); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">State</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->state) ? $client->state : ''); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Country</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->country) ? $client->country : ''); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">GSTIN</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->gstin) ? $client->gstin : ''); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">PAN</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->pan_number) ? $client->pan_number : ''); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Industry</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->industry) ? $client->industry : ''); ?></span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Website</span>
                  <span class="client-detail-dl-value">
                    <?php if (!empty($client->website)): ?>
                    <a href="<?php echo esc_view($client->website); ?>" target="_blank" rel="noopener"><?php echo esc_view($client->website); ?></a>
                    <?php else: ?>—<?php endif; ?>
                  </span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Demo URL</span>
                  <span class="client-detail-dl-value">
                    <?php if (!empty($client->demo_url)): ?>
                    <a href="<?php echo esc_view($client->demo_url); ?>" target="_blank" rel="noopener"><?php echo esc_view($client->demo_url); ?></a>
                    <?php else: ?>—<?php endif; ?>
                  </span>
                </div>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">POS URL</span>
                  <span class="client-detail-dl-value">
                    <?php if (!empty($client->pos_url)): ?>
                    <a href="<?php echo esc_view($client->pos_url); ?>" target="_blank" rel="noopener"><?php echo esc_view($client->pos_url); ?></a>
                    <?php else: ?>—<?php endif; ?>
                  </span>
                </div>
                <?php
                $can_add_url = function_exists('has_module_access') && (
                    has_module_access('clients_urls') || has_module_access('clients_add') || has_module_access('clients')
                );
                if ($can_add_url):
                ?>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Version URLs</span>
                  <span class="client-detail-dl-value">
                    <a href="<?php echo site_url('clients?tab=urls&client_id=' . $client_id); ?>">View URLs</a>
                    <span class="text-muted mx-1">·</span>
                    <a href="<?php echo site_url('clients/edit/' . $client_id . '#client-urls'); ?>">Add URL</a>
                  </span>
                </div>
                <?php endif; ?>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Onboarded</span>
                  <span class="client-detail-dl-value"><?php echo esc_view(isset($client->onboarding_date) ? $client->onboarding_date : ''); ?></span>
                </div>
                <?php if (!empty($client->logo)): ?>
                <div class="client-detail-dl-item">
                  <span class="client-detail-dl-label">Logo</span>
                  <span class="client-detail-dl-value">
                    <button type="button"
                            class="btn p-0 border-0 bg-transparent js-client-logo-trigger"
                            data-bs-toggle="modal"
                            data-bs-target="#clientLogoModal"
                            data-logo-url="<?php echo esc_view(base_url($client->logo)); ?>"
                            data-client-name="<?php echo esc_view($company_name); ?>">
                      <div class="client-detail-logo-thumb">
                        <img src="<?php echo esc_view(base_url($client->logo)); ?>" alt="Logo">
                      </div>
                    </button>
                  </span>
                </div>
                <?php endif; ?>
                <div class="client-detail-dl-item client-detail-dl-wide">
                  <span class="client-detail-dl-label">Notes</span>
                  <span class="client-detail-dl-value"><?php echo nl2br(esc_view(isset($client->notes) ? $client->notes : '')); ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="card shadow-sm border-0 client-detail-panel mb-1">
            <div class="card-header bg-white">
              <h6 class="mb-0">Meta</h6>
            </div>
            <div class="card-body client-detail-meta">
              <div><span>Created</span> <?php echo esc_view(isset($client->created_at) ? $client->created_at : ''); ?></div>
              <div><span>Updated</span> <?php echo esc_view(isset($client->updated_at) ? $client->updated_at : ''); ?></div>
              <div><span>DB name</span> <?php echo esc_view(isset($client->db_name) ? $client->db_name : ''); ?></div>
              <div><span>DB user</span> <?php echo esc_view(isset($client->db_username) ? $client->db_username : ''); ?></div>
              <div><span>DB pass</span> <?php echo esc_view(isset($client->db_password) ? $client->db_password : ''); ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Requirements -->
    <div class="tab-pane fade<?php echo $active_tab === 'requirements' ? ' show active' : ''; ?>" id="requirements" role="tabpanel" aria-labelledby="requirements-tab">
      <div class="card shadow-sm border-0 client-detail-panel project-detail-panel">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 small fw-semibold text-uppercase text-muted">Requirements</h6>
          <?php if ($can_requirements): ?>
          <a href="<?php echo site_url('requirements?client_id=' . $client_id); ?>" class="btn btn-outline-secondary btn-sm py-0"><i class="bi bi-list"></i><span class="d-none d-md-inline ms-1">All</span></a>
          <?php endif; ?>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0 project-inline-table" data-inline-type="requirement" data-save-url="<?php echo site_url('clients/' . $client_id . '/inline-save'); ?>" data-delete-url="<?php echo site_url('clients/' . $client_id . '/inline-delete'); ?>" data-can-manage="<?php echo $can_manage_requirements ? '1' : '0'; ?>" data-can-delete="<?php echo $can_delete_requirements ? '1' : '0'; ?>" data-requires-project="0">
            <thead class="table-light">
              <tr>
                <th width="30%">Title</th>
                <th width="18%">Project</th>
                <th width="15%">Status</th>
                <th width="13%">Priority</th>
                <th width="16%">Assignee</th>
                <th width="8%"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($requirements) && !$can_manage_requirements): ?>
              <tr class="project-inline-empty"><td colspan="6" class="text-center py-3 text-muted small">No requirements for this client.</td></tr>
              <?php else: foreach ($requirements as $r): ?>
              <?php
                $r_status = isset($r->status) ? (string) $r->status : 'received';
                $r_priority = isset($r->priority) ? (string) $r->priority : 'medium';
                $r_assigned = !empty($r->assigned_to) ? (int) $r->assigned_to : 0;
                $r_project = !empty($r->project_id) ? (int) $r->project_id : 0;
              ?>
              <tr class="project-inline-row" data-id="<?php echo (int) $r->id; ?>" data-ref="<?php echo esc_view(isset($r->req_number) ? $r->req_number : '', ENT_QUOTES, 'UTF-8'); ?>">
                <td>
                  <?php if ($can_manage_requirements): ?>
                  <input type="text" class="form-control form-control-sm project-inline-title" value="<?php echo esc_view($r->title, ENT_QUOTES, 'UTF-8'); ?>" maxlength="500">
                  <?php else: ?>
                  <?php echo esc_view($r->title); ?>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_requirements): ?>
                  <select class="form-select form-select-sm project-inline-project">
                    <option value="">No project</option>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?php echo (int) $p->id; ?>" <?php echo $r_project === (int) $p->id ? 'selected' : ''; ?>><?php echo esc_view($p->name); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="small text-muted"><?php echo esc_view(!empty($r->project_name) ? $r->project_name : '—'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_requirements): ?>
                  <select class="form-select form-select-sm project-inline-status">
                    <?php foreach ($req_statuses as $st): ?>
                    <option value="<?php echo esc_view($st); ?>" <?php echo $r_status === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="badge bg-secondary"><?php echo esc_view($r_status); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_requirements): ?>
                  <select class="form-select form-select-sm project-inline-priority">
                    <?php foreach ($task_priorities as $pr): ?>
                    <option value="<?php echo esc_view($pr); ?>" <?php echo $r_priority === $pr ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="badge bg-secondary"><?php echo esc_view($r_priority); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_requirements): ?>
                  <select class="form-select form-select-sm project-inline-assignee">
                    <option value="">Unassigned</option>
                    <?php foreach ($assignable_users as $u): ?>
                    <option value="<?php echo (int) $u->id; ?>" <?php echo $r_assigned === (int) $u->id ? 'selected' : ''; ?>><?php echo esc_view($client_view_user_label($u)); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="small"><?php echo esc_view($assignee_label($r)); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                  <span class="project-inline-state text-muted small me-1"></span>
                  <a href="<?php echo site_url('requirements/view/' . (int) $r->id); ?>" class="btn btn-sm btn-light" title="View"><i class="bi bi-box-arrow-up-right"></i></a>
                  <?php if ($can_delete_requirements): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
            <?php if ($can_manage_requirements): ?>
            <tfoot>
              <tr>
                <td colspan="6" class="py-1 px-2">
                  <button type="button" class="btn btn-sm btn-outline-primary project-inline-add py-0 px-2" title="Add requirement"><i class="bi bi-plus-lg"></i></button>
                </td>
              </tr>
            </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>

    <!-- Tasks -->
    <div class="tab-pane fade<?php echo $active_tab === 'tasks' ? ' show active' : ''; ?>" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
      <div class="card shadow-sm border-0 client-detail-panel project-detail-panel">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 small fw-semibold text-uppercase text-muted">Tasks</h6>
          <div class="d-flex gap-1">
            <?php if ($can_tasks): ?>
            <a href="<?php echo site_url('tasks'); ?>" class="btn btn-outline-secondary btn-sm py-0"><i class="bi bi-list"></i><span class="d-none d-md-inline ms-1">All</span></a>
            <?php endif; ?>
            <?php if ($can_manage_tasks && !$has_client_projects && $can_add_project): ?>
            <a href="<?php echo esc_view($create_project_url); ?>" class="btn btn-outline-primary btn-sm py-0" title="Create a project for this client"><i class="bi bi-folder-plus"></i><span class="d-none d-md-inline ms-1">New Project</span></a>
            <?php endif; ?>
            <?php if ($can_manage_tasks): ?>
            <button type="button" class="btn btn-primary btn-sm py-0 project-inline-add-trigger" data-inline-target="task" title="Add task"><i class="bi bi-plus-lg"></i><span class="d-none d-md-inline ms-1">Add</span></button>
            <?php endif; ?>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0 project-inline-table" data-inline-type="task" data-save-url="<?php echo site_url('clients/' . $client_id . '/inline-save'); ?>" data-delete-url="<?php echo site_url('clients/' . $client_id . '/inline-delete'); ?>" data-can-manage="<?php echo $can_manage_tasks ? '1' : '0'; ?>" data-can-delete="<?php echo $can_delete_tasks ? '1' : '0'; ?>" data-requires-project="1" data-create-project-url="<?php echo esc_view($create_project_url); ?>">
            <thead class="table-light">
              <tr>
                <th width="26%">Title</th>
                <th width="16%">Project</th>
                <th width="13%">Status</th>
                <th width="12%">Priority</th>
                <th width="14%">Assignee</th>
                <th class="text-end" width="9%">Est.hr</th>
                <th width="10%"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tasks) && !$can_manage_tasks): ?>
              <tr class="project-inline-empty"><td colspan="7" class="text-center py-3 text-muted small">No tasks linked via this client’s projects or requirements.</td></tr>
              <?php elseif (empty($tasks) && !$has_client_projects): ?>
              <tr class="project-inline-empty"><td colspan="7" class="text-center py-3 text-muted small">No project linked yet. Click <strong>Add</strong> after creating a project, or use <strong>New Project</strong>.</td></tr>
              <?php else: foreach ($tasks as $t): ?>
              <?php
                $t_priority = isset($t->priority) ? (string) $t->priority : 'medium';
                $t_status = $t->status ?: 'pending';
                $t_assigned = !empty($t->assigned_to) ? (int) $t->assigned_to : 0;
                $t_project = !empty($t->project_id) ? (int) $t->project_id : 0;
                if (!function_exists('estimate_hours_display')) {
                    $this->load->helper('estimate_hours');
                }
                $t_est_input = (isset($t->estimate_hours) && $t->estimate_hours !== null && $t->estimate_hours !== '')
                  ? estimate_hours_display($t->estimate_hours)
                  : '';
                $t_est_row = estimate_hours_row(isset($t->estimate_hours) ? $t->estimate_hours : null);
              ?>
              <tr class="project-inline-row" data-id="<?php echo (int) $t->id; ?>">
                <td>
                  <?php if ($can_manage_tasks): ?>
                  <input type="text" class="form-control form-control-sm project-inline-title" value="<?php echo esc_view($t->title, ENT_QUOTES, 'UTF-8'); ?>" maxlength="500">
                  <?php else: ?>
                  <a href="<?php echo site_url('tasks/' . (int) $t->id); ?>" class="fw-medium text-dark text-decoration-none"><?php echo esc_view($t->title); ?></a>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_tasks): ?>
                  <select class="form-select form-select-sm project-inline-project">
                    <option value="">Select project</option>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?php echo (int) $p->id; ?>" <?php echo $t_project === (int) $p->id ? 'selected' : ''; ?>><?php echo esc_view($p->name); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="small text-muted"><?php echo esc_view(!empty($t->project_name) ? $t->project_name : '—'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_tasks): ?>
                  <select class="form-select form-select-sm project-inline-status">
                    <?php foreach ($task_statuses as $st): ?>
                    <option value="<?php echo esc_view($st); ?>" <?php echo $t_status === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="badge bg-secondary"><?php echo esc_view(ucfirst(str_replace('_', ' ', $t_status))); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_tasks): ?>
                  <select class="form-select form-select-sm project-inline-priority">
                    <?php foreach ($task_priorities as $pr): ?>
                    <option value="<?php echo esc_view($pr); ?>" <?php echo $t_priority === $pr ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="badge bg-secondary"><?php echo esc_view($t_priority); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_tasks): ?>
                  <select class="form-select form-select-sm project-inline-assignee">
                    <option value="">Unassigned</option>
                    <?php foreach ($assignable_users as $u): ?>
                    <option value="<?php echo (int) $u->id; ?>" <?php echo $t_assigned === (int) $u->id ? 'selected' : ''; ?>><?php echo esc_view($client_view_user_label($u)); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="small"><?php echo esc_view($assignee_label($t)); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                  <?php if ($can_manage_tasks): ?>
                  <input type="number" class="form-control form-control-sm project-inline-estimate text-end" min="0" max="9" step="1" placeholder="—" title="Estimate (hrs)" value="<?php echo esc_view($t_est_input, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php else: ?>
                  <span class="small text-muted"><?php echo esc_view($t_est_row); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                  <span class="project-inline-state text-muted small me-1"></span>
                  <a href="<?php echo site_url('tasks/' . (int) $t->id); ?>" class="btn btn-sm btn-light" title="View"><i class="bi bi-box-arrow-up-right"></i></a>
                  <?php if ($can_delete_tasks): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
            <?php if ($can_manage_tasks): ?>
            <tfoot>
              <tr>
                <td colspan="7" class="py-1 px-2">
                  <button type="button" class="btn btn-sm btn-outline-primary project-inline-add py-0 px-2" title="Add task"><i class="bi bi-plus-lg"></i></button>
                </td>
              </tr>
            </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>

    <!-- Defects -->
    <div class="tab-pane fade<?php echo $active_tab === 'defects' ? ' show active' : ''; ?>" id="defects" role="tabpanel" aria-labelledby="defects-tab">
      <div class="card shadow-sm border-0 client-detail-panel project-detail-panel">
        <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0 small fw-semibold text-uppercase text-muted">Defects</h6>
          <div class="d-flex gap-1">
            <?php if ($can_defects): ?>
            <a href="<?php echo site_url('defects'); ?>" class="btn btn-outline-secondary btn-sm py-0"><i class="bi bi-list"></i><span class="d-none d-md-inline ms-1">All</span></a>
            <?php endif; ?>
            <?php if ($can_manage_defects && !$has_client_projects && $can_add_project): ?>
            <a href="<?php echo esc_view($create_project_url); ?>" class="btn btn-outline-primary btn-sm py-0" title="Create a project for this client"><i class="bi bi-folder-plus"></i><span class="d-none d-md-inline ms-1">New Project</span></a>
            <?php endif; ?>
            <?php if ($can_manage_defects): ?>
            <button type="button" class="btn btn-primary btn-sm py-0 project-inline-add-trigger" data-inline-target="defect" title="Add defect"><i class="bi bi-plus-lg"></i><span class="d-none d-md-inline ms-1">Add</span></button>
            <?php endif; ?>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover align-middle mb-0 project-inline-table" data-inline-type="defect" data-save-url="<?php echo site_url('clients/' . $client_id . '/inline-save'); ?>" data-delete-url="<?php echo site_url('clients/' . $client_id . '/inline-delete'); ?>" data-can-manage="<?php echo $can_manage_defects ? '1' : '0'; ?>" data-can-delete="<?php echo $can_delete_defects ? '1' : '0'; ?>" data-requires-project="1" data-create-project-url="<?php echo esc_view($create_project_url); ?>">
            <thead class="table-light">
              <tr>
                <th width="30%">Title</th>
                <th width="18%">Project</th>
                <th width="15%">Status</th>
                <th width="13%">Priority</th>
                <th width="16%">Assignee</th>
                <th width="8%"></th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($defects) && !$can_manage_defects): ?>
              <tr class="project-inline-empty"><td colspan="6" class="text-center py-3 text-muted small">No defects on this client’s projects.</td></tr>
              <?php elseif (empty($defects) && !$has_client_projects): ?>
              <tr class="project-inline-empty"><td colspan="6" class="text-center py-3 text-muted small">No project linked yet. Click <strong>Add</strong> after creating a project, or use <strong>New Project</strong>.</td></tr>
              <?php else: foreach ($defects as $d): ?>
              <?php
                $d_status = isset($d->status) ? (string) $d->status : 'open';
                $d_priority = isset($d->priority) ? (string) $d->priority : 'medium';
                $d_assigned = !empty($d->assigned_to) ? (int) $d->assigned_to : 0;
                $d_project = !empty($d->project_id) ? (int) $d->project_id : 0;
              ?>
              <tr class="project-inline-row" data-id="<?php echo (int) $d->id; ?>" data-ref="<?php echo esc_view(isset($d->defect_number) ? $d->defect_number : '', ENT_QUOTES, 'UTF-8'); ?>">
                <td>
                  <?php if ($can_manage_defects): ?>
                  <input type="text" class="form-control form-control-sm project-inline-title" value="<?php echo esc_view($d->title, ENT_QUOTES, 'UTF-8'); ?>" maxlength="500">
                  <?php else: ?>
                  <?php echo esc_view($d->title); ?>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_defects): ?>
                  <select class="form-select form-select-sm project-inline-project">
                    <option value="">Select project</option>
                    <?php foreach ($projects as $p): ?>
                    <option value="<?php echo (int) $p->id; ?>" <?php echo $d_project === (int) $p->id ? 'selected' : ''; ?>><?php echo esc_view($p->name); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="small text-muted"><?php echo esc_view(!empty($d->project_name) ? $d->project_name : '—'); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_defects): ?>
                  <select class="form-select form-select-sm project-inline-status">
                    <?php foreach ($defect_statuses as $st): ?>
                    <option value="<?php echo esc_view($st); ?>" <?php echo $d_status === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="badge bg-secondary"><?php echo esc_view(ucfirst(str_replace('_', ' ', $d_status))); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_defects): ?>
                  <select class="form-select form-select-sm project-inline-priority">
                    <?php foreach ($defect_priorities as $pr): ?>
                    <option value="<?php echo esc_view($pr); ?>" <?php echo $d_priority === $pr ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="badge bg-light text-dark border"><?php echo esc_view($d_priority); ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($can_manage_defects): ?>
                  <select class="form-select form-select-sm project-inline-assignee">
                    <option value="">Unassigned</option>
                    <?php foreach ($assignable_users as $u): ?>
                    <option value="<?php echo (int) $u->id; ?>" <?php echo $d_assigned === (int) $u->id ? 'selected' : ''; ?>><?php echo esc_view($client_view_user_label($u)); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <?php else: ?>
                  <span class="small"><?php echo esc_view($assignee_label($d)); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end text-nowrap">
                  <span class="project-inline-state text-muted small me-1"></span>
                  <a href="<?php echo site_url('defects/view/' . (int) $d->id); ?>" class="btn btn-sm btn-light" title="View"><i class="bi bi-box-arrow-up-right"></i></a>
                  <?php if ($can_delete_defects): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; endif; ?>
            </tbody>
            <?php if ($can_manage_defects): ?>
            <tfoot>
              <tr>
                <td colspan="6" class="py-1 px-2">
                  <button type="button" class="btn btn-sm btn-outline-primary project-inline-add py-0 px-2" title="Add defect"><i class="bi bi-plus-lg"></i></button>
                </td>
              </tr>
            </tfoot>
            <?php endif; ?>
          </table>
        </div>
      </div>
    </div>

    <!-- History -->
    <div class="tab-pane fade<?php echo $active_tab === 'history' ? ' show active' : ''; ?>" id="history" role="tabpanel" aria-labelledby="history-tab">
      <div class="card shadow-sm border-0 client-detail-panel mb-1">
        <div class="card-body py-2 px-3">
          <?php if ($can_note): ?>
            <div class="small text-muted fw-semibold mb-1">Add</div>
            <form method="post" action="<?php echo site_url('clients/add-comment/' . $client_id); ?>" enctype="multipart/form-data" class="mb-3" id="clientHistoryAddForm">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <textarea name="note" id="clientHistoryNote" class="form-control form-control-sm mb-2" rows="4" placeholder="Add a note to history…"></textarea>
              <div class="row g-2 align-items-end mb-2 mt-1">
                <div class="col-md-8">
                  <label class="form-label small text-muted mb-0" for="clientHistoryAttachments">Attachment</label>
                  <input type="file" name="attachments[]" id="clientHistoryAttachments" class="form-control form-control-sm" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                  <div class="form-text">Optional. Up to 5 files, 5 MB each.</div>
                </div>
                <div class="col-md-4 text-md-end">
                  <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add</button>
                </div>
              </div>
            </form>
          <?php endif; ?>

          <form method="get" action="<?php echo site_url('clients/view/' . $client_id); ?>" class="row g-2 align-items-end mb-3 client-history-filters">
            <input type="hidden" name="tab" value="history">
            <div class="col-md-3">
              <label class="form-label small text-muted mb-0">Search</label>
              <input type="search" name="q" value="<?php echo esc_view(isset($history_filters['q']) ? $history_filters['q'] : ''); ?>" class="form-control form-control-sm" placeholder="Comment, user, file…">
            </div>
            <div class="col-md-2">
              <label class="form-label small text-muted mb-0">Type</label>
              <select name="action" class="form-select form-select-sm">
                <option value="">All</option>
                <?php
                $hist_actions = array('note' => 'Note', 'created' => 'Created', 'updated' => 'Updated', 'status_changed' => 'Status', 'contact_changed' => 'Contact', 'urls_changed' => 'URLs');
                $sel_action = isset($history_filters['action']) ? (string) $history_filters['action'] : '';
                foreach ($hist_actions as $ak => $al):
                ?>
                <option value="<?php echo esc_view($ak); ?>" <?php echo $sel_action === $ak ? 'selected' : ''; ?>><?php echo esc_view($al); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label small text-muted mb-0">Added by</label>
              <select name="user_id" class="form-select form-select-sm">
                <option value="">All</option>
                <?php
                $sel_uid = isset($history_filters['user_id']) ? (int) $history_filters['user_id'] : 0;
                foreach ($history_users as $hu):
                  $hu_label = '';
                  if (!empty($hu->name)) {
                      $hu_label = (string) $hu->name;
                  } elseif (!empty($hu->full_name)) {
                      $hu_label = (string) $hu->full_name;
                  } else {
                      $hu_label = (string) $hu->email;
                  }
                ?>
                <option value="<?php echo (int) $hu->id; ?>" <?php echo $sel_uid === (int) $hu->id ? 'selected' : ''; ?>><?php echo esc_view($hu_label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label small text-muted mb-0">From</label>
              <input type="date" name="date_from" value="<?php echo esc_view(isset($history_filters['date_from']) ? $history_filters['date_from'] : ''); ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
              <label class="form-label small text-muted mb-0">To</label>
              <input type="date" name="date_to" value="<?php echo esc_view(isset($history_filters['date_to']) ? $history_filters['date_to'] : ''); ?>" class="form-control form-control-sm">
            </div>
            <div class="col-md-1 d-flex gap-1">
              <button type="submit" class="btn btn-sm btn-outline-secondary" title="Filter"><i class="bi bi-funnel"></i></button>
              <a href="<?php echo site_url('clients/view/' . $client_id . '?tab=history'); ?>" class="btn btn-sm btn-outline-secondary" title="Clear"><i class="bi bi-x-lg"></i></a>
            </div>
          </form>

          <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="small text-muted fw-semibold">History</div>
            <span class="text-muted small"><?php echo count($history); ?></span>
          </div>

          <?php if (empty($history)): ?>
            <p class="text-muted small mb-0">No history yet.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0 align-middle client-history-grid">
                <thead class="table-light">
                  <tr>
                    <th class="text-start" style="width:9.5rem;">Date</th>
                    <th>Comments</th>
                    <th style="width:12rem;">Attachment</th>
                    <th style="width:9rem;">Added By</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($history as $h): ?>
                    <?php
                      $who = ($h->user_name !== '') ? $h->user_name : 'System';
                      $changed = trim((string) $h->detail);
                      $action_key = strtolower((string) $h->action);
                      $is_note = ($action_key === 'note' || $action_key === 'comment' || $action_key === 'commented');
                      if ($changed === '') {
                          $changed = $action_label($h->action);
                      } elseif (!$is_note) {
                          if (strpos($changed, ':') === false && strpos($changed, '→') === false) {
                              $changed = $action_label($h->action) . ': ' . $changed;
                          }
                      }
                      $parts = $is_note ? array($changed) : preg_split('/\s*;\s*/', $changed);
                      $atts = !empty($h->attachments) && is_array($h->attachments) ? $h->attachments : array();
                    ?>
                    <tr>
                      <td class="small text-nowrap text-muted text-start"><?php echo esc_view($h->created_at); ?></td>
                      <td class="small client-history-comment">
                        <?php if ($is_note): ?>
                          <div class="client-history-rich"><?php echo sanitize_html_output($changed); ?></div>
                        <?php elseif (count($parts) > 1): ?>
                          <ul class="mb-0 ps-3 client-history-changes">
                            <?php foreach ($parts as $part): ?>
                              <?php if (trim($part) === '') { continue; } ?>
                              <li><?php echo esc_view(trim($part)); ?></li>
                            <?php endforeach; ?>
                          </ul>
                        <?php else: ?>
                          <?php echo nl2br(esc_view($changed)); ?>
                        <?php endif; ?>
                      </td>
                      <td class="small client-history-attachments">
                        <?php if (empty($atts)): ?>
                          <span class="text-muted">—</span>
                        <?php else: ?>
                          <ul class="list-unstyled mb-0">
                            <?php foreach ($atts as $att): ?>
                              <?php
                                $att_url = site_url('clients/history-attachment/' . $client_id . '/' . (int) $h->id . '/' . (int) $att->index);
                                $att_name = (string) $att->original_name;
                              ?>
                              <li class="d-flex align-items-center gap-1 mb-1">
                                <span class="text-truncate" title="<?php echo esc_view($att_name); ?>">
                                  <i class="bi bi-paperclip me-1"></i><?php echo esc_view($att_name); ?>
                                </span>
                                <?php if (!empty($att->size)): ?>
                                  <span class="text-muted text-nowrap">(<?php echo number_format((int) $att->size / 1024, 1); ?> KB)</span>
                                <?php endif; ?>
                                <div class="btn-group btn-group-sm ms-auto" role="group" aria-label="Attachment actions">
                                  <a href="<?php echo esc_view($att_url); ?>" class="btn btn-outline-secondary" title="Download" aria-label="Download">
                                    <i class="bi bi-download"></i>
                                  </a>
                                </div>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        <?php endif; ?>
                      </td>
                      <td class="small fw-semibold"><?php echo esc_view($who); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="clientLogoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="clientLogoModalTitle">Client Logo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="clientLogoModalImg" src="" alt="Client Logo" class="img-fluid mb-3" style="max-height:400px;object-fit:contain;">
      </div>
      <div class="modal-footer">
        <a id="clientLogoDownload" href="#" class="btn btn-outline-primary" download>Download Logo</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Are you sure you want to permanently delete:</p>
        <p class="fw-bold fs-6" id="deleteClientName"></p>
        <p class="text-muted small mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteClientForm" method="post" action="" class="d-inline">
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Yes, Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php if ($can_manage_tasks || $can_manage_requirements || $can_manage_defects || $can_delete_tasks || $can_delete_requirements || $can_delete_defects): ?>
<template id="project-inline-row-template-task">
<tr class="project-inline-row project-inline-row-new" data-id="0">
  <td><input type="text" class="form-control form-control-sm project-inline-title" value="" maxlength="500" placeholder="Title"></td>
  <td><select class="form-select form-select-sm project-inline-project"><?php echo $inline_project_options_required; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-status"><?php foreach ($task_statuses as $st): ?><option value="<?php echo esc_view($st); ?>"><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-priority"><?php foreach ($task_priorities as $pr): ?><option value="<?php echo esc_view($pr); ?>" <?php echo $pr === 'medium' ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-assignee"><option value="">Unassigned</option><?php echo $inline_user_options; ?></select></td>
  <td class="text-end"><input type="number" class="form-control form-control-sm project-inline-estimate text-end" min="0" max="9" step="1" placeholder="—" title="Estimate (hrs)" value=""></td>
  <td class="text-end text-nowrap"><span class="project-inline-state text-muted small me-1"></span><?php if ($can_delete_tasks): ?><button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button><?php endif; ?></td>
</tr>
</template>
<template id="project-inline-row-template-requirement">
<tr class="project-inline-row project-inline-row-new" data-id="0">
  <td><input type="text" class="form-control form-control-sm project-inline-title" value="" maxlength="500" placeholder="Title"></td>
  <td><select class="form-select form-select-sm project-inline-project"><?php echo $inline_project_options_optional; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-status"><?php foreach ($req_statuses as $st): ?><option value="<?php echo esc_view($st); ?>" <?php echo $st === 'received' ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-priority"><?php foreach ($task_priorities as $pr): ?><option value="<?php echo esc_view($pr); ?>" <?php echo $pr === 'medium' ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-assignee"><option value="">Unassigned</option><?php echo $inline_user_options; ?></select></td>
  <td class="text-end text-nowrap"><span class="project-inline-state text-muted small me-1"></span><?php if ($can_delete_requirements): ?><button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button><?php endif; ?></td>
</tr>
</template>
<template id="project-inline-row-template-defect">
<tr class="project-inline-row project-inline-row-new" data-id="0">
  <td><input type="text" class="form-control form-control-sm project-inline-title" value="" maxlength="500" placeholder="Title"></td>
  <td><select class="form-select form-select-sm project-inline-project"><?php echo $inline_project_options_required; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-status"><?php foreach ($defect_statuses as $st): ?><option value="<?php echo esc_view($st); ?>" <?php echo $st === 'open' ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-priority"><?php foreach ($defect_priorities as $pr): ?><option value="<?php echo esc_view($pr); ?>" <?php echo $pr === 'medium' ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-assignee"><option value="">Unassigned</option><?php echo $inline_user_options; ?></select></td>
  <td class="text-end text-nowrap"><span class="project-inline-state text-muted small me-1"></span><?php if ($can_delete_defects): ?><button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button><?php endif; ?></td>
</tr>
</template>
<script>
(function () {
  var canDelete = {
    task: <?php echo $can_delete_tasks ? 'true' : 'false'; ?>,
    requirement: <?php echo $can_delete_requirements ? 'true' : 'false'; ?>,
    defect: <?php echo $can_delete_defects ? 'true' : 'false'; ?>
  };
  var defaultTitles = { task: 'New task', requirement: 'New requirement', defect: 'New defect' };
  var deleteLabels = { task: 'task', requirement: 'requirement', defect: 'defect' };
  var viewUrls = {
    task: <?php echo json_encode(site_url('tasks/')); ?>,
    requirement: <?php echo json_encode(site_url('requirements/view/')); ?>,
    defect: <?php echo json_encode(site_url('defects/view/')); ?>
  };
  var defaultProjectId = <?php echo $has_client_projects ? (int) $projects[0]->id : 0; ?>;
  var hasProjects = <?php echo $has_client_projects ? 'true' : 'false'; ?>;
  var createProjectUrl = <?php echo json_encode($create_project_url); ?>;
  var countEls = {
    requirement: { badge: document.getElementById('clientBadgeRequirements'), stat: document.getElementById('clientStatRequirements') },
    task: { badge: document.getElementById('clientBadgeTasks'), stat: document.getElementById('clientStatTasks') },
    defect: { badge: document.getElementById('clientBadgeDefects'), stat: document.getElementById('clientStatDefects') }
  };

  function addInlineRow(table) {
    if (!table || table.getAttribute('data-can-manage') !== '1') {
      return;
    }
    var type = table.getAttribute('data-inline-type');
    if (table.getAttribute('data-requires-project') === '1' && !hasProjects) {
      var url = table.getAttribute('data-create-project-url') || createProjectUrl;
      if (url && window.confirm('This client has no project yet. Create a project first?')) {
        window.location.href = url;
      }
      return;
    }
    var tpl = document.getElementById('project-inline-row-template-' + type);
    if (!tpl || !tpl.content) {
      return;
    }
    removeEmptyRow(table);
    var row = tpl.content.firstElementChild.cloneNode(true);
    table.querySelector('tbody').appendChild(row);
    var projectSelect = row.querySelector('.project-inline-project');
    if (projectSelect && defaultProjectId && table.getAttribute('data-requires-project') === '1') {
      projectSelect.value = String(defaultProjectId);
    }
    var titleInput = row.querySelector('.project-inline-title');
    if (titleInput) {
      titleInput.value = defaultTitles[type] || 'New item';
    }
    saveRow(row, true);
    if (titleInput) {
      titleInput.focus();
      titleInput.select();
    }
  }

  function refreshCount(type) {
    var table = document.querySelector('.project-inline-table[data-inline-type="' + type + '"]');
    if (!table || !countEls[type]) {
      return;
    }
    var n = table.querySelectorAll('tbody .project-inline-row').length;
    if (countEls[type].badge) {
      countEls[type].badge.textContent = String(n);
    }
    if (countEls[type].stat) {
      countEls[type].stat.textContent = String(n);
    }
  }

  function buildActionsHtml(type, id) {
    var html = '<span class="project-inline-state text-muted small me-1"></span>';
    html += '<a href="' + viewUrls[type] + id + '" class="btn btn-sm btn-light" title="View"><i class="bi bi-box-arrow-up-right"></i></a>';
    if (canDelete[type]) {
      html += ' <button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button>';
    }
    return html;
  }

  function maybeShowEmptyRow(table) {
    var tbody = table.querySelector('tbody');
    if (!tbody || tbody.querySelector('.project-inline-row')) {
      return;
    }
    var canManage = table.getAttribute('data-can-manage') === '1';
    if (!canManage) {
      var type = table.getAttribute('data-inline-type');
      var messages = {
        task: 'No tasks linked via this client’s projects or requirements.',
        requirement: 'No requirements for this client.',
        defect: 'No defects on this client’s projects.'
      };
      var tr = document.createElement('tr');
      tr.className = 'project-inline-empty';
      var colCount = table.querySelectorAll('thead th').length || 6;
      tr.innerHTML = '<td colspan="' + colCount + '" class="text-center py-3 text-muted small">' + (messages[type] || 'No items found.') + '</td>';
      tbody.appendChild(tr);
    }
  }

  function rowPayload(row) {
    var projectEl = row.querySelector('.project-inline-project');
    var estimateEl = row.querySelector('.project-inline-estimate');
    return {
      type: row.closest('.project-inline-table').getAttribute('data-inline-type'),
      id: row.getAttribute('data-id') || '0',
      title: (row.querySelector('.project-inline-title') || {}).value || '',
      status: (row.querySelector('.project-inline-status') || {}).value || '',
      priority: (row.querySelector('.project-inline-priority') || {}).value || '',
      assigned_to: (row.querySelector('.project-inline-assignee') || {}).value || '',
      project_id: projectEl ? (projectEl.value || '') : '',
      estimate_hours: estimateEl ? (estimateEl.value || '') : ''
    };
  }

  function setRowState(row, text, isError) {
    var el = row.querySelector('.project-inline-state');
    if (!el) {
      return;
    }
    el.textContent = text || '';
    el.classList.toggle('text-danger', !!isError);
    el.classList.toggle('text-success', !isError && text === 'Saved');
  }

  function removeEmptyRow(table) {
    var empty = table.querySelector('tbody .project-inline-empty');
    if (empty) {
      empty.remove();
    }
  }

  function saveRow(row, forceCreate) {
    if (row.getAttribute('data-saving') === '1') {
      return;
    }
    var table = row.closest('.project-inline-table');
    if (!table || table.getAttribute('data-can-manage') !== '1') {
      return;
    }
    var payload = rowPayload(row);
    var isNew = !payload.id || payload.id === '0';
    var requiresProject = table.getAttribute('data-requires-project') === '1';
    if (requiresProject && !payload.project_id) {
      setRowState(row, 'Project required', true);
      return;
    }
    if (isNew && !forceCreate && !payload.title.trim()) {
      return;
    }
    if (!isNew && !payload.title.trim()) {
      setRowState(row, 'Title required', true);
      return;
    }
    if (payload.type === 'task') {
      var estRaw = String(payload.estimate_hours || '').trim();
      if (estRaw !== '' && (isNaN(Number(estRaw)) || !/^[0-9]$/.test(estRaw) || Number(estRaw) < 0 || Number(estRaw) > 9)) {
        setRowState(row, 'Estimate (hrs) must be 0–9', true);
        return;
      }
    }

    row.setAttribute('data-saving', '1');
    setRowState(row, 'Saving…');

    var body = new URLSearchParams();
    Object.keys(payload).forEach(function (key) {
      body.append(key, payload[key]);
    });

    fetch(table.getAttribute('data-save-url'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
      credentials: 'same-origin'
    })
      .then(function (res) { return res.json().then(function (json) { return { ok: res.ok, json: json }; }); })
      .then(function (result) {
        if (!result.json || !result.json.ok) {
          throw new Error((result.json && result.json.error) ? result.json.error : 'Save failed');
        }
        var data = result.json;
        var wasNew = isNew;
        row.setAttribute('data-id', String(data.id));
        row.classList.remove('project-inline-row-new');
        if (data.req_number) {
          row.setAttribute('data-ref', data.req_number);
        }
        if (data.defect_number) {
          row.setAttribute('data-ref', data.defect_number);
        }
        var actionsCell = row.querySelector('td:last-child');
        if (actionsCell) {
          actionsCell.innerHTML = buildActionsHtml(payload.type, data.id);
        }
        setRowState(row, 'Saved');
        if (wasNew) {
          refreshCount(payload.type);
        }
        setTimeout(function () { setRowState(row, ''); }, 1200);
      })
      .catch(function (err) {
        setRowState(row, err.message || 'Error', true);
      })
      .finally(function () {
        row.removeAttribute('data-saving');
      });
  }

  var debounceTimers = new WeakMap();
  function scheduleSave(row) {
    if (debounceTimers.has(row)) {
      clearTimeout(debounceTimers.get(row));
    }
    debounceTimers.set(row, setTimeout(function () {
      saveRow(row, false);
    }, 450));
  }

  document.querySelectorAll('.project-inline-table').forEach(function (table) {
    var type = table.getAttribute('data-inline-type');
    var canManage = table.getAttribute('data-can-manage') === '1';
    var canDeleteTable = table.getAttribute('data-can-delete') === '1';

    table.addEventListener('click', function (e) {
      var delBtn = e.target.closest('.project-inline-delete');
      if (delBtn) {
        if (!canDeleteTable) {
          return;
        }
        var row = delBtn.closest('.project-inline-row');
        if (!row) {
          return;
        }
        var rowId = row.getAttribute('data-id') || '0';
        if (!rowId || rowId === '0') {
          row.remove();
          maybeShowEmptyRow(table);
          refreshCount(type);
          return;
        }
        var label = deleteLabels[type] || 'item';
        if (!window.confirm('Delete this ' + label + '?')) {
          return;
        }
        row.setAttribute('data-saving', '1');
        setRowState(row, 'Deleting…');
        var body = new URLSearchParams();
        body.append('type', type);
        body.append('id', rowId);
        fetch(table.getAttribute('data-delete-url'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
          credentials: 'same-origin'
        })
          .then(function (res) { return res.json().then(function (json) { return { ok: res.ok, json: json }; }); })
          .then(function (result) {
            if (!result.json || !result.json.ok) {
              throw new Error((result.json && result.json.error) ? result.json.error : 'Delete failed');
            }
            row.remove();
            maybeShowEmptyRow(table);
            refreshCount(type);
          })
          .catch(function (err) {
            row.removeAttribute('data-saving');
            setRowState(row, err.message || 'Error', true);
          });
        return;
      }

      if (!canManage) {
        return;
      }
      var btn = e.target.closest('.project-inline-add');
      if (!btn) {
        return;
      }
      addInlineRow(table);
    });

    if (!canManage) {
      return;
    }

    table.addEventListener('change', function (e) {
      var row = e.target.closest('.project-inline-row');
      if (!row) {
        return;
      }
      saveRow(row, false);
    });

    table.addEventListener('blur', function (e) {
      if (!e.target.classList.contains('project-inline-title') && !e.target.classList.contains('project-inline-estimate')) {
        return;
      }
      var row = e.target.closest('.project-inline-row');
      if (!row) {
        return;
      }
      scheduleSave(row);
    }, true);

    table.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' || (!e.target.classList.contains('project-inline-title') && !e.target.classList.contains('project-inline-estimate'))) {
        return;
      }
      e.preventDefault();
      var row = e.target.closest('.project-inline-row');
      if (row) {
        saveRow(row, false);
        e.target.blur();
      }
    });
  });

  document.querySelectorAll('.project-inline-add-trigger').forEach(function (trigger) {
    trigger.addEventListener('click', function () {
      var type = trigger.getAttribute('data-inline-target');
      var table = document.querySelector('.project-inline-table[data-inline-type="' + type + '"]');
      addInlineRow(table);
    });
  });
})();
</script>
<?php endif; ?>

<?php if ($can_note): ?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var imgEl = document.getElementById('clientLogoModalImg');
  var downloadEl = document.getElementById('clientLogoDownload');
  var titleEl = document.getElementById('clientLogoModalTitle');
  document.querySelectorAll('.js-client-logo-trigger').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var url = this.getAttribute('data-logo-url') || '';
      var name = this.getAttribute('data-client-name') || '';
      if (imgEl) {
        imgEl.src = url;
        imgEl.alt = name || 'Client logo';
      }
      if (titleEl) {
        titleEl.textContent = name ? (name + ' Logo') : 'Client Logo';
      }
      if (downloadEl) {
        downloadEl.href = url;
      }
    });
  });

  var historyEditorReady = false;
  function initClientHistoryEditor() {
    if (historyEditorReady) {
      return;
    }
    if (typeof tinymce === 'undefined') {
      return;
    }
    if (!document.getElementById('clientHistoryNote')) {
      return;
    }
    if (tinymce.get('clientHistoryNote')) {
      historyEditorReady = true;
      return;
    }
    var isNarrow = window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
    tinymce.init({
      selector: '#clientHistoryNote',
      height: isNarrow ? 180 : 220,
      menubar: false,
      statusbar: true,
      branding: false,
      convert_urls: false,
      default_link_target: '_blank',
      placeholder: 'Add a note to history…',
      toolbar_mode: isNarrow ? 'scrolling' : 'wrap',
      plugins: [
        'advlist', 'autolink', 'lists', 'link', 'charmap',
        'searchreplace', 'visualblocks', 'code',
        'insertdatetime', 'table', 'wordcount'
      ],
      toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat',
      content_style: 'body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
      formats: {
        bold: { inline: 'strong' },
        italic: { inline: 'em' },
        underline: { inline: 'u' },
        strikethrough: { inline: 'del' }
      },
      setup: function (editor) {
        editor.on('init', function () {
          historyEditorReady = true;
        });
      }
    });
  }

  var historyForm = document.getElementById('clientHistoryAddForm');
  if (historyForm) {
    historyForm.addEventListener('submit', function () {
      if (window.tinymce && tinymce.get('clientHistoryNote')) {
        tinymce.get('clientHistoryNote').save();
      }
    });
  }

  var tabButtons = document.querySelectorAll('#clientTabs button[data-bs-toggle="tab"]');
  tabButtons.forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function (e) {
      var target = e.target.getAttribute('data-bs-target') || '';
      var tab = target.replace('#', '');
      if (!tab) {
        return;
      }
      if (tab === 'history') {
        initClientHistoryEditor();
      }
      try {
        var url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        if (tab !== 'history') {
          ['q', 'action', 'user_id', 'date_from', 'date_to'].forEach(function (k) {
            url.searchParams.delete(k);
          });
        }
        window.history.replaceState({}, '', url.toString());
      } catch (err) {}
    });
  });

  <?php if ($active_tab === 'history'): ?>
  initClientHistoryEditor();
  <?php endif; ?>
});

function confirmDeleteClient(id, name) {
  document.getElementById('deleteClientName').textContent = name || '';
  document.getElementById('deleteClientForm').action = '<?php echo site_url('clients/delete/'); ?>' + id;
  new bootstrap.Modal(document.getElementById('deleteClientModal')).show();
}
</script>

<?php $this->load->view('partials/footer'); ?>
