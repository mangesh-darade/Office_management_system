<?php $this->load->view('partials/header', array('title' => 'Action Priority Matrix — Projects', 'extra_css' => array('assets/css/action-priority-matrix.css', 'assets/css/projects.css'))); ?>
<div class="container-fluid py-3">
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold"><i class="bi bi-grid-3x3-gap text-primary me-2"></i>Project Action Priority Matrix</h1>
      <p class="text-muted small mb-0">Effort × Impact — click a project name to open project details.</p>
    </div>
    <div class="d-flex flex-wrap gap-2 mt-2 mt-sm-0">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('projects'); ?>"><i class="bi bi-list-ul me-1"></i>List</a>
      <?php if (function_exists('has_module_access') && (has_module_access('projects_add') || has_module_access('projects'))): ?>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('projects/create'); ?>"><i class="bi bi-plus-lg me-1"></i>New Project</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-3">
      <form method="get" class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small mb-0">Search</label>
          <input type="text" name="search" class="form-control form-control-sm" value="<?php echo esc_view($filters['search']); ?>" placeholder="Project name or code">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label small mb-0">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            <?php if (!empty($status_rows)) foreach ($status_rows as $s): ?>
              <option value="<?php echo esc_view($s->code); ?>" <?php echo ($filters['status'] === (string) $s->code) ? 'selected' : ''; ?>><?php echo esc_view($s->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if (!empty($clients)): ?>
        <div class="col-6 col-md-3">
          <label class="form-label small mb-0">Client</label>
          <select name="client_id" class="form-select form-select-sm">
            <option value="0">All clients</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?php echo (int) $c->id; ?>" <?php echo (int) $filters['client_id'] === (int) $c->id ? 'selected' : ''; ?>><?php echo esc_view($c->company_name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="col-12 col-md-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
        </div>
      </form>
    </div>
  </div>

  <?php
    $this->load->view('partials/apm_matrix_grid', array(
      'apm_title'        => 'Action Priority Matrix for Projects',
      'apm_date'         => date('M j, Y'),
      'matrix_columns'   => isset($matrix_columns) ? $matrix_columns : array(),
      'apm_context'      => 'projects',
      'apm_draggable'    => false,
      'apm_extra'        => array(
        'status_map'     => isset($status_map) ? $status_map : array(),
        'can_view_works' => !empty($can_view_works),
        'can_view_tasks' => !empty($can_view_tasks),
      ),
    ));
  ?>

  <div class="small text-muted mt-2">
    <strong>Impact</strong> from status, open tasks/works, and timeline risk.
    <strong>Effort</strong> from remaining tasks, open works, duration, and active delivery.
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
