<?php $this->load->view('partials/header', array('title' => 'Questions — ' . $assessment->title)); ?>
<?php $ta_can_question_import = isset($ta_can_question_import) ? (bool) $ta_can_question_import : false; ?>
<div class="container-fluid py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h4 mb-0"><?php echo esc_view($assessment->title); ?></h1>
      <p class="text-muted small mb-0">Drag rows to reorder. Manage MCQ, text, and coding items.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="<?php echo site_url('training-assessment/preview/' . (int)$assessment->id); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm" aria-label="Preview assessment as candidate"><i class="bi bi-eye me-1"></i>Preview</a>
      <a href="<?php echo site_url('training-assessment/question/add/' . (int)$assessment->id); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add question</a>
      <?php if ($ta_can_question_import): ?>
      <a href="<?php echo site_url('training-assessment/question/import/sample'); ?>" class="btn btn-outline-info btn-sm"><i class="bi bi-download me-1"></i>Sample CSV</a>
      <?php endif; ?>
      <a href="<?php echo site_url('training-assessment'); ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if ($ta_can_question_import): ?>
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <h2 class="h6 mb-2">Import Questions + Options (CSV)</h2>
      <p class="text-muted small mb-3">Upload CSV to create questions for this assessment. For MCQ, provide options and correct indexes (example: <code>1</code> or <code>1|3</code>).</p>
      <form method="post" action="<?php echo site_url('training-assessment/question/import/' . (int)$assessment->id); ?>" enctype="multipart/form-data" class="row g-2 align-items-end">
        <div class="col-md-8">
          <label class="form-label mb-1">CSV file</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-success w-100"><i class="bi bi-upload me-1"></i>Import CSV</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th class="text-muted" style="width:2.5rem" title="Drag to reorder"><i class="bi bi-grip-vertical"></i></th>
            <th>#</th>
            <th>Type</th>
            <th>Question</th>
            <th>Points</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody id="ta-question-sortable">
          <?php if (empty($questions)): ?>
          <tr><td colspan="6" class="text-center py-4 text-muted">No questions yet.</td></tr>
          <?php else: ?>
          <?php foreach ($questions as $i => $q): ?>
          <tr data-qid="<?php echo (int)$q->id; ?>">
            <td class="text-muted ta-drag-handle" style="cursor:grab" aria-hidden="true"><i class="bi bi-grip-vertical"></i></td>
            <td><?php echo (int)$q->sort_order ?: ($i + 1); ?></td>
            <td><span class="badge bg-info text-dark"><?php echo esc_view(strtoupper($q->question_type)); ?></span></td>
            <td><?php
              $snippet = strip_tags($q->question_text);
              if (function_exists('mb_strimwidth')) {
                echo esc_view(mb_strimwidth($snippet, 0, 120, '…'));
              } else {
                echo esc_view(strlen($snippet) > 120 ? substr($snippet, 0, 117) . '…' : $snippet);
              }
            ?></td>
            <td><?php echo esc_view(number_format((float)$q->points, 2)); ?></td>
            <td class="text-end text-nowrap">
              <a class="btn btn-sm btn-outline-warning" href="<?php echo site_url('training-assessment/question/edit/' . (int)$q->id); ?>" aria-label="Edit question"><i class="bi bi-pencil me-1"></i>Edit</a>
              <?php echo form_open('training-assessment/question/duplicate/' . (int)$q->id, array('class' => 'd-inline')); ?>
              <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
              <button type="submit" class="btn btn-sm btn-outline-info" aria-label="Duplicate question"><i class="bi bi-files me-1"></i>Copy</button>
              <?php echo form_close(); ?>
              <form method="post" action="<?php echo site_url('training-assessment/question/delete/' . (int)$q->id); ?>" class="d-inline" onsubmit="return confirm('Delete this question?');">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete question"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php if (!empty($questions)): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function() {
  var aid = <?php echo (int)$assessment->id; ?>;
  var tbody = document.getElementById('ta-question-sortable');
  if (!tbody || typeof Sortable === 'undefined') return;
  var reorderUrl = <?php echo json_encode(site_url('training-assessment/questions/reorder')); ?>;
  var saveTimer = null;
  new Sortable(tbody, {
    handle: '.ta-drag-handle',
    animation: 150,
    onEnd: function() {
      var ids = [];
      tbody.querySelectorAll('tr[data-qid]').forEach(function(tr) {
        ids.push(parseInt(tr.getAttribute('data-qid'), 10));
      });
      if (saveTimer) clearTimeout(saveTimer);
      saveTimer = setTimeout(function() {
        if (!window.jQuery) return;
        jQuery.post(reorderUrl, { assessment_id: aid, order_json: JSON.stringify(ids) }, function(j) {
          if (j && j.csrf) {
            jQuery('input[name="ci_csrf_token"]').val(j.csrf);
          }
        }, 'json');
      }, 400);
    }
  });
})();
</script>
<?php endif; ?>
<?php $this->load->view('partials/footer'); ?>
