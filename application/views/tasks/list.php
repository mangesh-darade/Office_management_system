<?php $this->load->view('partials/header', ['title' => 'Tasks']); ?>
<div class="container-fluid py-4">
<?php $this->load->view('partials/import_errors'); ?>

<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2 mb-3" role="alert">
  <?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2 mb-3" role="alert">
  <?php echo esc_view((string) $this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php
$actions_html = '';
if (function_exists('has_module_access') && (has_module_access('tasks_add') || has_module_access('tasks'))) {
    $actions_html .= '<a class="btn btn-primary btn-sm" href="' . site_url('tasks/create') . '"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New Task</span><span class="d-inline d-sm-none">New</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('tasks_import') || has_module_access('tasks'))) {
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('tasks/import') . '"><i class="bi bi-upload me-1"></i><span class="d-none d-sm-inline">Import</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('tasks_import') || has_module_access('tasks_list') || has_module_access('tasks'))) {
    $export_qs = function_exists('safe_query_string') ? safe_query_string() : '';
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('tasks/export' . ($export_qs !== '' ? '?' . $export_qs : '')) . '"><i class="bi bi-download me-1"></i><span class="d-none d-sm-inline">Export</span></a>';
}
$actions_html .= '<a class="btn btn-outline-dark btn-sm" href="' . site_url('tasks/board') . '"><i class="bi bi-kanban me-1"></i><span class="d-none d-sm-inline">Board</span></a>';
$this->load->view('partials/oms_page_head', array(
    'title'        => 'Tasks',
    'icon'         => 'bi-list-check',
    'subtitle'     => 'Track and manage all project tasks',
    'actions_html' => $actions_html,
));
?>

<div class="card shadow-sm border-0 mb-3">
  <div class="card-body py-3">
    <form method="get" action="<?php echo site_url('tasks'); ?>" class="row g-2 align-items-end oms-filter-row" id="tasksFilterForm">
      <div class="col-12 col-sm-6 col-lg-3">
        <label class="form-label small text-muted mb-1">Project</label>
        <select name="project_id" class="form-select form-select-sm">
          <option value="">All Projects</option>
          <?php if (isset($projects) && is_array($projects)) foreach ($projects as $p): ?>
            <option value="<?php echo (int) $p->id; ?>" <?php echo (isset($filter_project_id) && (string) $filter_project_id === (string) $p->id) ? 'selected' : ''; ?>><?php echo esc_view(isset($p->name) ? $p->name : ('#' . (int) $p->id)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ((isset($is_admin) && $is_admin) || (isset($team_scope) && $team_scope)): ?>
      <div class="col-12 col-sm-6 col-lg-3">
        <label class="form-label small text-muted mb-1">Assignee</label>
        <select name="assigned_to" class="form-select form-select-sm">
          <option value="">All Assignees</option>
          <?php if (isset($assignees) && is_array($assignees)) foreach ($assignees as $u): ?>
            <?php
              $label = '';
              if (isset($u->emp_name) && $u->emp_name !== '') {
                  $label = $u->emp_name;
              } else if (isset($u->full_name) && $u->full_name !== '') {
                  $label = $u->full_name;
              } else if (isset($u->name) && $u->name !== '') {
                  $label = $u->name;
              } else if (isset($u->email) && $u->email !== '') {
                  $label = $u->email;
              }
            ?>
            <option value="<?php echo (int) $u->id; ?>" <?php echo (isset($filter_assigned_to) && (string) $filter_assigned_to === (string) $u->id) ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-6 col-sm-4 col-lg-2">
        <label class="form-label small text-muted mb-1">Status</label>
        <select name="status" class="form-select form-select-sm">
          <?php foreach (array('', 'pending', 'in_progress', 'completed', 'blocked') as $st): ?>
            <option value="<?php echo esc_view($st); ?>" <?php echo (isset($filter_status) && (string) $filter_status === (string) $st) ? 'selected' : ''; ?>><?php echo $st === '' ? 'All' : ucfirst(str_replace('_', ' ', $st)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-sm-4 col-lg-2">
        <label class="form-label small text-muted mb-1">Priority</label>
        <select name="priority" class="form-select form-select-sm">
          <?php foreach (array('', 'low', 'medium', 'high', 'urgent') as $pr): ?>
            <option value="<?php echo esc_view($pr); ?>" <?php echo (isset($filter_priority) && (string) $filter_priority === (string) $pr) ? 'selected' : ''; ?>><?php echo $pr === '' ? 'All' : ucfirst($pr); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-sm-4 col-lg-2">
        <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-funnel me-1"></i>Apply</button>
      </div>
    </form>
  </div>
</div>

<?php
  $task_total = isset($tasks) && is_array($tasks) ? count($tasks) : 0;
  $tasks_priority_col = (isset($is_admin) && $is_admin) ? 6 : 5;
  $tasks_actions_col = (isset($is_admin) && $is_admin) ? 8 : 7;
  $tasks_priority_rank = array('urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4);
  $tasks_priority_badge = array(
    'urgent' => 'danger',
    'high'   => 'danger',
    'medium' => 'warning text-dark',
    'low'    => 'success',
  );
  $tasks_status_badge = array(
    'pending'     => 'secondary',
    'in_progress' => 'primary',
    'completed'   => 'success',
    'blocked'     => 'danger',
  );
?>

<p class="text-muted small mb-2"><?php echo (int) $task_total; ?> task(s)</p>

<?php if ($task_total < 1): ?>
<div class="card shadow-sm border-0">
  <div class="card-body empty-state">
    <div class="empty-icon"><i class="bi bi-inbox"></i></div>
    <h5>No tasks found</h5>
    <p class="mb-3">Try adjusting your filters or create a new task.</p>
    <?php if (function_exists('has_module_access') && (has_module_access('tasks_add') || has_module_access('tasks'))): ?>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('tasks/create'); ?>"><i class="bi bi-plus-lg me-1"></i>New Task</a>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>

<div class="d-md-none oms-mobile-list mb-3">
  <?php foreach ($tasks as $t): ?>
    <?php $this->load->view('tasks/_mobile_card', array(
      't' => $t,
      'is_admin' => !empty($is_admin),
      'tasks_priority_badge' => $tasks_priority_badge,
      'tasks_status_badge' => $tasks_status_badge,
    )); ?>
  <?php endforeach; ?>
</div>

<div class="card shadow-sm border-0 d-none d-md-block tasks-datatable-grid">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table id="tasksTable" class="table table-hover align-middle mb-0 w-100" data-order-col="<?php echo (int) $tasks_priority_col; ?>" data-order-dir="asc" data-order-disable-cols="<?php echo (int) $tasks_actions_col; ?>">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Project</th>
            <th>Title</th>
            <th class="no-sort d-none d-xl-table-cell">Description</th>
            <?php if (isset($is_admin) && $is_admin): ?>
            <th class="d-none d-lg-table-cell">Assignee</th>
            <?php endif; ?>
            <th>Status</th>
            <th>Priority</th>
            <th class="text-end">Estimate</th>
            <th class="text-end" style="min-width: 130px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tasks as $t): ?>
            <?php
              $task_priority = isset($t->priority) ? strtolower((string) $t->priority) : 'medium';
              $task_priority_sort = isset($tasks_priority_rank[$task_priority]) ? (int) $tasks_priority_rank[$task_priority] : 5;
              $task_priority_badge_class = isset($tasks_priority_badge[$task_priority]) ? $tasks_priority_badge[$task_priority] : 'secondary';
              $task_status = isset($t->status) ? (string) $t->status : 'pending';
              $task_status_badge_class = isset($tasks_status_badge[$task_status]) ? $tasks_status_badge[$task_status] : 'info';
              $assignee = '';
              if (isset($t->emp_name) && $t->emp_name !== '') {
                  $assignee = $t->emp_name;
              } else if (isset($t->full_name) && $t->full_name !== '') {
                  $assignee = $t->full_name;
              } else if (isset($t->name) && $t->name !== '') {
                  $assignee = $t->name;
              } else if (isset($t->assignee_email) && $t->assignee_email !== '') {
                  $assignee = $t->assignee_email;
              }
              if (isset($assignee_names_map[(int) $t->id]) && is_array($assignee_names_map[(int) $t->id])) {
                  $assignee = multi_assignees_format_label($assignee, $assignee_names_map[(int) $t->id]);
              }
              $desc_plain = isset($t->description) ? trim(strip_tags((string) $t->description)) : '';
            ?>
            <tr>
              <td><?php echo (int) $t->id; ?></td>
              <td class="text-nowrap"><?php echo esc_view(isset($t->project_name) && $t->project_name !== '' ? $t->project_name : ('#' . (int) $t->project_id)); ?></td>
              <td>
                <a href="<?php echo site_url('tasks/' . (int) $t->id); ?>" class="text-decoration-none fw-semibold"><?php echo esc_view($t->title); ?></a>
              </td>
              <td class="small text-muted d-none d-xl-table-cell">
                <div class="text-truncate" style="max-width: 280px;" title="<?php echo esc_view($desc_plain, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($desc_plain); ?>
                </div>
              </td>
              <?php if (isset($is_admin) && $is_admin): ?>
              <td class="d-none d-lg-table-cell"><?php echo esc_view($assignee !== '' ? $assignee : '—'); ?></td>
              <?php endif; ?>
              <td data-order="<?php echo esc_view($task_status, ENT_QUOTES, 'UTF-8'); ?>">
                <span class="badge bg-<?php echo esc_view($task_status_badge_class); ?>"><?php echo esc_view(ucfirst(str_replace('_', ' ', $task_status))); ?></span>
              </td>
              <td data-order="<?php echo $task_priority_sort; ?>">
                <span class="badge bg-<?php echo esc_view($task_priority_badge_class); ?>"><?php echo esc_view(ucfirst($task_priority !== '' ? $task_priority : 'medium')); ?></span>
              </td>
              <td class="text-end text-nowrap" data-order="<?php echo isset($t->estimate_hours) && $t->estimate_hours !== null && $t->estimate_hours !== '' ? (float) $t->estimate_hours : -1; ?>">
                <?php echo function_exists('estimate_hours_display') ? esc_view(estimate_hours_display(isset($t->estimate_hours) ? $t->estimate_hours : null)) : '—'; ?>
              </td>
              <td class="text-end text-nowrap table-actions">
                <div class="d-inline-flex align-items-center justify-content-end gap-1 flex-nowrap">
                  <a class="btn btn-light btn-sm" title="View" href="<?php echo site_url('tasks/' . (int) $t->id); ?>"><i class="bi bi-eye"></i></a>
                  <?php if (function_exists('has_module_access') && (has_module_access('tasks_edit') || has_module_access('tasks'))): ?>
                  <a class="btn btn-primary btn-sm" title="Edit" href="<?php echo site_url('tasks/' . (int) $t->id . '/edit'); ?>"><i class="bi bi-pencil"></i></a>
                  <?php endif; ?>
                  <?php if (function_exists('has_module_access') && (has_module_access('tasks_delete') || has_module_access('tasks'))): ?>
                  <form method="post" action="<?php echo site_url('tasks/' . (int) $t->id . '/delete'); ?>" class="d-inline m-0" onsubmit="return confirm('Delete this task?');">
                    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                    <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>
</div>
<?php $this->load->view('partials/footer'); ?>
