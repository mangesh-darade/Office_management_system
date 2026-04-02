<?php $this->load->view('partials/header', array('title' => 'Assessment report')); ?>
<div class="container-fluid py-4">
  <h1 class="h4 mb-3"><i class="bi bi-bar-chart me-2"></i>Training assessment report</h1>
  <form method="get" action="<?php echo site_url('training-assessment/report'); ?>" class="row g-2 align-items-end mb-3">
    <div class="col-lg-3 col-md-6">
      <label class="form-label small text-muted mb-0">Assessment</label>
      <select name="assessment_id" class="form-select form-select-sm">
        <option value="0">All</option>
        <?php foreach ($assessments as $as): ?>
          <option value="<?php echo (int)$as->id; ?>" <?php echo ((int)$filter_assessment_id === (int)$as->id) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($as->title); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-2 col-md-6">
      <label class="form-label small text-muted mb-0">Assignee type</label>
      <select name="assignee_type" class="form-select form-select-sm">
        <option value="all" <?php echo ($filter_assignee_type === 'all') ? 'selected' : ''; ?>>All</option>
        <option value="employee" <?php echo ($filter_assignee_type === 'employee') ? 'selected' : ''; ?>>Employees only</option>
        <option value="candidate" <?php echo ($filter_assignee_type === 'candidate') ? 'selected' : ''; ?>>External candidates</option>
      </select>
    </div>
    <div class="col-lg-3 col-md-6">
      <label class="form-label small text-muted mb-0">Employee (when type allows)</label>
      <select name="employee_user_id" class="form-select form-select-sm">
        <option value="0">All</option>
        <?php foreach ($employees as $e): ?>
          <?php if (empty($e->user_id)) { continue; } ?>
          <option value="<?php echo (int)$e->user_id; ?>" <?php echo ((int)$filter_employee === (int)$e->user_id) ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars(trim($e->first_name . ' ' . $e->last_name)); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-lg-2 col-md-6">
      <label class="form-label small text-muted mb-0">From</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)$filter_from); ?>">
    </div>
    <div class="col-lg-2 col-md-6">
      <label class="form-label small text-muted mb-0">To</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars((string)$filter_to); ?>">
    </div>
    <div class="col-lg-12 col-md-6 d-flex gap-2 flex-wrap">
      <button type="submit" class="btn btn-primary btn-sm">Filter</button>
      <?php
        $exportUrl = site_url('training-assessment/report/export?' . http_build_query(array(
          'employee_user_id' => isset($filter_employee) ? (int)$filter_employee : 0,
          'date_from' => isset($filter_from) ? $filter_from : '',
          'date_to' => isset($filter_to) ? $filter_to : '',
          'assessment_id' => isset($filter_assessment_id) ? (int)$filter_assessment_id : 0,
          'assignee_type' => isset($filter_assignee_type) ? $filter_assignee_type : 'all',
        )));
      ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo htmlspecialchars($exportUrl); ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
      <?php
        $qBankUrl = site_url('training-assessment/office-export/questions?' . http_build_query(array(
          'assessment_id' => isset($filter_assessment_id) ? (int)$filter_assessment_id : 0,
        )));
        $attemptDetailUrl = site_url('training-assessment/office-export/attempt-detail?' . http_build_query(array(
          'assessment_id' => isset($filter_assessment_id) ? (int)$filter_assessment_id : 0,
          'date_from' => isset($filter_from) ? $filter_from : '',
          'date_to' => isset($filter_to) ? $filter_to : '',
        )));
      ?>
      <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($qBankUrl); ?>" title="Topic, question, answers, type (LMS topic when linked)"><i class="bi bi-journal-text me-1"></i>Question bank CSV</a>
      <a class="btn btn-outline-primary btn-sm" href="<?php echo htmlspecialchars($attemptDetailUrl); ?>" title="Per-question rows for completed attempts"><i class="bi bi-list-check me-1"></i>Attempt detail CSV</a>
    </div>
  </form>

  <div class="table-responsive card shadow-sm border-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Assessment</th>
          <th>Assignee</th>
          <th>Score %</th>
          <th>Pass</th>
          <th>Submitted</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center py-4 text-muted">No rows for this filter.</td></tr>
        <?php else: ?>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r->assessment_title); ?></td>
          <td>
            <?php
            if (!empty($r->user_name)) {
              echo htmlspecialchars($r->user_name);
            } elseif (!empty($r->candidate_name)) {
              echo htmlspecialchars($r->candidate_name) . ' <span class="text-muted">(candidate)</span>';
            } else {
              echo '—';
            }
            ?>
          </td>
          <td><?php echo $r->score_percent !== null ? htmlspecialchars(number_format((float)$r->score_percent, 1)) : '—'; ?></td>
          <td>
            <?php if ($r->passed === null): ?>
              <span class="badge bg-secondary">Pending</span>
            <?php elseif ((int)$r->passed === 1): ?>
              <span class="badge bg-success">Pass</span>
            <?php else: ?>
              <span class="badge bg-danger">Fail</span>
            <?php endif; ?>
          </td>
          <td><?php echo $r->submitted_at ? htmlspecialchars($r->submitted_at) : '—'; ?></td>
          <td class="text-end">
            <?php if (!empty($r->id)): ?>
            <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('training-assessment/result/' . (int)$r->id); ?>">View</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
