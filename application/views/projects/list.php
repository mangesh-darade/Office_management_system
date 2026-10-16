<?php $this->load->view('partials/header', array('title' => 'Projects', 'extra_css' => array('assets/css/projects.css'))); ?>
<div class="container-fluid py-2 proj-list-compact">
<?php $this->load->view('partials/import_errors'); ?>
<?php
$actions_html = '';
if (function_exists('has_module_access') && (has_module_access('projects_matrix') || has_module_access('projects') || has_module_access('projects_list'))) {
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" title="Portfolio Matrix" href="' . site_url('projects/matrix') . '"><i class="bi bi-grid-3x3-gap me-1"></i><span class="d-none d-sm-inline">Matrix</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('projects') || has_module_access('projects_list'))) {
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" title="Project Dashboard" href="' . site_url('projects/dashboard') . '"><i class="bi bi-speedometer2 me-1"></i><span class="d-none d-sm-inline">Dashboard</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('projects_add') || has_module_access('projects'))) {
    $actions_html .= '<a class="btn btn-primary btn-sm" title="Create" href="' . site_url('projects/create') . '"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New Project</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('projects_import') || has_module_access('projects'))) {
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" title="Import CSV" href="' . site_url('projects/import') . '"><i class="bi bi-upload me-1"></i><span class="d-none d-sm-inline">Import</span></a>';
}
if (function_exists('has_module_access') && (has_module_access('projects_import') || has_module_access('projects_list') || has_module_access('projects'))) {
    $actions_html .= '<a class="btn btn-outline-secondary btn-sm" title="Export CSV" href="' . site_url('projects/export') . '"><i class="bi bi-download me-1"></i><span class="d-none d-sm-inline">Export</span></a>';
}
$this->load->view('partials/oms_page_head', array(
    'title' => 'Projects',
    'icon' => 'bi-kanban',
    'subtitle' => 'Manage all your projects',
    'actions_html' => $actions_html,
    'mb' => 'mb-2',
));
?>

<?php if (!empty($show_department_column) && !empty($departments)): ?>
<div class="proj-list-filters">
  <label class="proj-filter-field" for="projDeptFilter">
    <span class="proj-filter-label">Department</span>
    <select id="projDeptFilter" class="form-select form-select-sm">
      <option value="">All</option>
      <?php foreach ($departments as $dept): ?>
        <?php $dept_id = isset($dept->id) ? (int) $dept->id : 0; ?>
        <?php if ($dept_id < 1) { continue; } ?>
        <option value="<?php echo esc_view(isset($dept->dept_name) ? $dept->dept_name : ''); ?>"><?php echo esc_view(isset($dept->dept_name) ? $dept->dept_name : ''); ?></option>
      <?php endforeach; ?>
    </select>
  </label>
</div>
<?php endif; ?>

<div class="card border-0 proj-table-card oms-datatable-grid">
  <div class="card-body">
    <div class="table-responsive">
      <table id="projectsTable" class="table table-hover table-sm align-middle mb-0 w-100 proj-list-table" data-order-col="1" data-order-dir="asc">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <?php if (!empty($show_department_column)): ?>
            <th class="proj-dept-col">Department</th>
            <?php endif; ?>
            <?php if (!empty($show_assignee_column)): ?>
            <th>Assigned To</th>
            <?php endif; ?>
            <th>Status</th>
            <th class="text-end">Estimate</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($projects)) foreach ($projects as $p): ?>
            <?php
              $st = isset($p->status) ? strtolower((string) $p->status) : '';
              $st_class = 'bg-light text-dark border';
              if ($st === 'in_progress' || $st === 'active') {
                  $st_class = 'bg-primary';
              } elseif ($st === 'completed' || $st === 'done' || $st === 'closed') {
                  $st_class = 'bg-success';
              } elseif ($st === 'on_hold' || $st === 'hold') {
                  $st_class = 'bg-warning text-dark';
              } elseif ($st === 'cancelled' || $st === 'canceled') {
                  $st_class = 'bg-danger';
              } elseif ($st === 'planned') {
                  $st_class = 'bg-secondary';
              }
              $st_label = $st === '' ? '—' : ucfirst(str_replace('_', ' ', $st));
              $pid = (int) $p->id;
            ?>
            <tr>
              <td class="text-muted"><?php echo $pid; ?></td>
              <td>
                <a class="proj-name-link" href="<?php echo site_url('projects/' . $pid); ?>" title="<?php echo esc_view($p->name); ?>"><?php echo esc_view($p->name); ?></a>
              </td>
              <?php if (!empty($show_department_column)): ?>
              <?php $dept_label = (isset($p->department_name) && $p->department_name !== '') ? $p->department_name : '—'; ?>
              <td class="text-truncate" style="max-width:10rem;" title="<?php echo esc_view($dept_label); ?>"><?php echo esc_view($dept_label); ?></td>
              <?php endif; ?>
              <?php if (!empty($show_assignee_column)): ?>
              <td class="text-truncate" style="max-width:10rem;" title="<?php echo esc_view(isset($p->assignee_label) ? $p->assignee_label : '—'); ?>"><?php echo esc_view(isset($p->assignee_label) ? $p->assignee_label : '—'); ?></td>
              <?php endif; ?>
              <td><span class="badge proj-badge <?php echo esc_view($st_class); ?>"><?php echo esc_view($st_label); ?></span></td>
              <td class="text-end text-nowrap" data-order="<?php echo isset($p->estimate_hours) && $p->estimate_hours !== null && $p->estimate_hours !== '' ? (float) $p->estimate_hours : -1; ?>">
                <?php echo function_exists('estimate_hours_display') ? esc_view(estimate_hours_display(isset($p->estimate_hours) ? $p->estimate_hours : null)) : '—'; ?>
              </td>
              <td class="text-end text-nowrap">
                <?php if (function_exists('has_module_access') && (has_module_access('projects_delete') || has_module_access('projects'))): ?>
                <form id="proj-del-<?php echo $pid; ?>" method="post" action="<?php echo site_url('projects/' . $pid . '/delete'); ?>" class="d-none" onsubmit="return confirm('Delete this project?');">
                  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                </form>
                <?php endif; ?>
                <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                  <a class="btn btn-outline-secondary" title="Dashboard" href="<?php echo site_url('projects/' . $pid . '/dashboard'); ?>"><i class="bi bi-speedometer2"></i></a>
                  <a class="btn btn-outline-secondary" title="View" href="<?php echo site_url('projects/' . $pid); ?>"><i class="bi bi-eye"></i></a>
                  <?php if (function_exists('has_module_access') && (has_module_access('projects_edit') || has_module_access('projects'))): ?>
                  <a class="btn btn-outline-primary" title="Edit" href="<?php echo site_url('projects/' . $pid . '/edit'); ?>"><i class="bi bi-pencil"></i></a>
                  <?php endif; ?>
                  <?php if (function_exists('has_module_access') && (has_module_access('projects_delete') || has_module_access('projects'))): ?>
                  <button type="submit" form="proj-del-<?php echo $pid; ?>" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
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
</div>
<?php $this->load->view('partials/footer'); ?>
