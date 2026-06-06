<?php
$this->load->view('partials/header', array('title' => 'Training & Assessment'));
$ta_can_create = isset($ta_can_create) ? (bool) $ta_can_create : false;
$ta_can_import = isset($ta_can_import) ? (bool) $ta_can_import : false;
$ta_can_manage_core = isset($ta_can_manage_core) ? (bool) $ta_can_manage_core : false;
$ta_can_status_filter = isset($ta_can_status_filter) ? (bool) $ta_can_status_filter : false;
?>
<style>
  .ta-assessment-actions {
    gap: 0.25rem;
  }
  .ta-assessment-actions .btn {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .ta-assessment-actions .btn i {
    margin: 0 !important;
    line-height: 1;
  }
  .ta-assessment-actions form {
    display: inline-flex;
    margin: 0;
  }
  @media (max-width: 991.98px) {
    .ta-assessment-table th.ta-col-actions,
    .ta-assessment-table td.ta-col-actions {
      position: sticky;
      right: 0;
      background: var(--bs-body-bg, #fff);
      box-shadow: -4px 0 8px rgba(0,0,0,0.06);
      z-index: 2;
    }
    .ta-assessment-table thead th.ta-col-actions {
      background: var(--bs-table-bg, #f8f9fa);
    }
    .table-hover > tbody > tr:hover > td.ta-col-actions {
      background-color: var(--bs-table-hover-bg);
    }
    .table-warning > td.ta-col-actions {
      background-color: var(--bs-warning-bg-subtle, #fff3cd) !important;
    }
  }
</style>
<div class="container-fluid py-4">
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div>
      <h1 class="h4 mb-1 fw-bold"><i class="bi bi-mortarboard text-primary me-2"></i>Assessments</h1>
      <p class="text-muted small mb-0"><?php echo ($ta_can_create || $ta_can_manage_core) ? 'Create timed tests, assign employees or candidates, review results.' : 'Assessments assigned to you. Use My assignments to take tests and view results.'; ?></p>
    </div>
    <?php if ($ta_can_import || $ta_can_create): ?>
    <div class="d-flex flex-wrap gap-2 mt-2 mt-sm-0">
      <?php if ($ta_can_import): ?>
      <a href="<?php echo site_url('training-assessment/import'); ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-arrow-up me-1"></i>Import CSV</a>
      <?php endif; ?>
      <?php if ($ta_can_create): ?>
      <a href="<?php echo site_url('training-assessment/create'); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New assessment</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if (!empty($dashboard_scope_limited)): ?>
    <div class="alert alert-info small py-2 mb-3">You see assessments <strong>assigned to your account</strong> only. Users with full Training &amp; Assessment admin access see the whole catalogue.</div>
  <?php endif; ?>

  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body py-3">
          <div class="text-muted small"><?php echo !empty($dashboard_scope_limited) ? 'Assignments (your assessments)' : 'Assignments (all assessments)'; ?></div>
          <div class="fs-4 fw-bold"><?php echo (int)$stats_total_assigned; ?></div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body py-3">
          <div class="text-muted small">Completed</div>
          <div class="fs-4 fw-bold text-success"><?php echo (int)$stats_total_completed; ?></div>
        </div>
      </div>
    </div>
    <div class="col-sm-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body py-3">
          <div class="text-muted small">Pending / in progress</div>
          <div class="fs-4 fw-bold text-warning"><?php echo (int)$stats_total_pending; ?></div>
        </div>
      </div>
    </div>
  </div>

  <form method="get" action="<?php echo site_url('training-assessment'); ?>" class="card shadow-sm border-0 mb-3">
    <div class="card-body py-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-<?php echo $ta_can_status_filter ? '4' : '6'; ?>">
          <label class="form-label small text-muted mb-0">Search title or description</label>
          <input type="search" name="q" class="form-control form-control-sm" placeholder="Search…" value="<?php echo htmlspecialchars((string)$filter_q); ?>">
        </div>
        <?php if ($ta_can_status_filter): ?>
        <div class="col-md-3">
          <label class="form-label small text-muted mb-0">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive only</option>
          </select>
        </div>
        <?php endif; ?>
        <div class="col-md-<?php echo $ta_can_status_filter ? '3' : '4'; ?>">
          <label class="form-label small text-muted mb-0">Sort</label>
          <select name="sort" class="form-select form-select-sm">
            <option value="created_desc" <?php echo $filter_sort === 'created_desc' ? 'selected' : ''; ?>>Newest first</option>
            <option value="created_asc" <?php echo $filter_sort === 'created_asc' ? 'selected' : ''; ?>>Oldest first</option>
            <option value="title_asc" <?php echo $filter_sort === 'title_asc' ? 'selected' : ''; ?>>Title A–Z</option>
            <option value="title_desc" <?php echo $filter_sort === 'title_desc' ? 'selected' : ''; ?>>Title Z–A</option>
            <option value="questions_desc" <?php echo $filter_sort === 'questions_desc' ? 'selected' : ''; ?>>Most questions</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
        </div>
      </div>
    </div>
  </form>

  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ta-assessment-table">
          <thead class="table-light">
            <tr>
              <th>Title</th>
              <th class="d-none d-md-table-cell">Questions</th>
              <th class="d-none d-lg-table-cell">Assigned</th>
              <th class="d-none d-lg-table-cell">Done</th>
              <th class="d-none d-lg-table-cell">Pending</th>
              <th class="d-none d-xl-table-cell">Timer</th>
              <th class="d-none d-xl-table-cell">Pass %</th>
              <th>Status</th>
              <th class="text-end ta-col-actions" style="width:1%; white-space:nowrap">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($assessments)): ?>
            <tr>
              <td colspan="9" class="text-center py-5 text-muted">No assessments match your filters.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($assessments as $a): ?>
            <?php
              $qc = (int)$a->question_count;
              $asg = (int)$a->assigned_count;
              $done = (int)$a->completed_count;
              $pend = max(0, $asg - $done);
            ?>
            <tr class="<?php echo $qc === 0 ? 'table-warning' : ''; ?>">
              <td>
                <strong><?php echo htmlspecialchars($a->title); ?></strong>
                <?php if ($qc === 0): ?>
                  <div class="small text-danger"><i class="bi bi-exclamation-triangle me-1"></i>No questions — add questions before assigning.</div>
                <?php endif; ?>
                <?php if (!empty($a->description)): ?>
                  <div class="small text-muted text-truncate" style="max-width:360px"><?php echo htmlspecialchars($a->description); ?></div>
                <?php endif; ?>
                <div class="small text-muted d-md-none mt-1">
                  <span class="me-2"><?php echo $qc; ?> Q</span>
                  <span class="me-2"><?php echo $asg; ?> asg</span>
                  <span class="me-2"><?php echo $done; ?> done</span>
                  <span class="me-2"><?php echo (int)$a->time_limit_minutes; ?> min</span>
                  <span><?php echo htmlspecialchars(number_format((float)$a->passing_marks, 0)); ?>% pass</span>
                </div>
              </td>
              <td class="d-none d-md-table-cell"><?php echo $qc; ?></td>
              <td class="d-none d-lg-table-cell"><?php echo $asg; ?></td>
              <td class="d-none d-lg-table-cell"><?php echo $done; ?></td>
              <td class="d-none d-lg-table-cell"><?php echo $pend; ?></td>
              <td class="d-none d-xl-table-cell"><?php echo (int)$a->time_limit_minutes; ?> min</td>
              <td class="d-none d-xl-table-cell"><?php echo htmlspecialchars(number_format((float)$a->passing_marks, 1)); ?>%</td>
              <td><span class="badge bg-<?php echo $a->status === 'active' ? 'success' : 'secondary'; ?>"><?php echo htmlspecialchars($a->status); ?></span></td>
              <td class="text-end ta-col-actions py-2">
                <?php $taTitleEsc = htmlspecialchars($a->title, ENT_QUOTES, 'UTF-8'); ?>
                <?php if ($ta_can_create || $ta_can_manage_core): ?>
                <div class="d-inline-flex flex-nowrap align-items-center justify-content-end ta-assessment-actions" role="group" aria-label="Actions for <?php echo $taTitleEsc; ?>">
                  <?php if ($ta_can_create): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('training-assessment/questions/' . (int)$a->id); ?>" title="Questions" aria-label="Questions: <?php echo $taTitleEsc; ?>"><i class="bi bi-list-ol"></i></a>
                  <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('training-assessment/preview/' . (int)$a->id); ?>" target="_blank" rel="noopener" title="Preview" aria-label="Preview: <?php echo $taTitleEsc; ?>"><i class="bi bi-eye"></i></a>
                  <?php endif; ?>
                  <?php if ($ta_can_manage_core): ?>
                  <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('training-assessment/assign/' . (int)$a->id); ?>" title="Assign" aria-label="Assign: <?php echo $taTitleEsc; ?>" <?php echo $qc === 0 ? 'onclick="return confirm(\'This assessment has no questions yet. Continue?\');"' : ''; ?>><i class="bi bi-person-plus"></i></a>
                  <?php endif; ?>
                  <?php if ($ta_can_create): ?>
                  <a class="btn btn-sm btn-outline-warning" href="<?php echo site_url('training-assessment/edit/' . (int)$a->id); ?>" title="Edit" aria-label="Edit: <?php echo $taTitleEsc; ?>"><i class="bi bi-pencil"></i></a>
                  <?php echo form_open('training-assessment/duplicate/' . (int)$a->id, array('class' => 'd-inline')); ?>
                  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                  <button type="submit" class="btn btn-sm btn-outline-info" title="Duplicate" aria-label="Duplicate: <?php echo $taTitleEsc; ?>"><i class="bi bi-files"></i></button>
                  <?php echo form_close(); ?>
                  <?php endif; ?>
                  <?php if ($ta_can_manage_core): ?>
                  <?php echo form_open('training-assessment/delete/' . (int)$a->id, array('class' => 'd-inline', 'onsubmit' => 'return confirm(\'Delete this assessment and all related data?\');')); ?>
                  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete: <?php echo $taTitleEsc; ?>"><i class="bi bi-trash"></i></button>
                  <?php echo form_close(); ?>
                  <?php endif; ?>
                </div>
                <?php else: ?>
                <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
