<?php
$this->load->view('partials/header', ['title' => 'Edit Daily Activity']);
$activity_title_value = isset($activity_title_value) ? (string) $activity_title_value : '';
$description_html = sanitize_html_output($log->description);
?>

<style>
.note-editor.note-frame {
    border-radius: 0.375rem;
    border-color: #dee2e6;
    box-shadow: none;
}
.note-editor.note-frame .note-toolbar {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
    padding: 5px;
}
.note-editor .note-toolbar .note-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
    color: #495057;
    background: transparent;
    border: none;
}
.note-editor .note-toolbar .note-btn:hover {
    background-color: #e9ecef;
    border-radius: 0.2rem;
}
@media (max-width: 768px) {
    .note-editor .note-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
    }
}
</style>

<div class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Activity Log</h4>
      <p class="text-muted mb-0 small">Update your work activity entry</p>
    </div>
    <a href="<?php echo site_url('daily-activity?date=' . esc_view($log->work_date)); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      <?php echo esc_view($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <?php echo form_open('daily-activity/edit/' . (int) $log->id); ?>

            <div class="mb-3">
              <label class="form-label small fw-bold text-uppercase text-muted">Work Date <span class="text-danger">*</span></label>
              <input type="date" name="work_date" class="form-control"
                     value="<?php echo esc_view($log->work_date); ?>" required
                     max="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="mb-3">
              <label class="form-label small fw-bold text-uppercase text-muted">Activity / Task</label>
              <input class="form-control" list="taskOptions" name="activity_title" id="activityTitleInput"
                     value="<?php echo esc_view($activity_title_value); ?>"
                     placeholder="Search or type activity..." autocomplete="off">
              <datalist id="taskOptions">
                <?php foreach ($tasks as $t): ?>
                  <option data-id="<?php echo (int) $t->id; ?>" value="<?php echo esc_view($t->title); ?>"></option>
                <?php endforeach; ?>
              </datalist>
              <input type="hidden" name="task_id" id="taskIdInput" value="<?php echo (int) $log->task_id; ?>">
            </div>

            <div class="mb-4">
              <label class="form-label small fw-bold text-uppercase text-muted">Description <span class="text-danger">*</span></label>
              <textarea class="form-control" name="description" id="summernote" required></textarea>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i>Save Changes
              </button>
              <a href="<?php echo site_url('daily-activity?date=' . esc_view($log->work_date)); ?>" class="btn btn-outline-secondary">
                Cancel
              </a>
            </div>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script>
$(document).ready(function() {
  $('#summernote').summernote({
    placeholder: 'Type your updates here...',
    tabsize: 2,
    height: 220,
    toolbar: [
      ['font', ['bold', 'underline', 'clear']],
      ['para', ['ul', 'ol']],
      ['insert', ['link']],
      ['view', ['fullscreen']]
    ],
    disableDragAndDrop: true
  });
  $('#summernote').summernote('code', <?php echo json_encode($description_html, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);

  $('#activityTitleInput').on('input', function() {
    var val = $(this).val();
    var id = $('#taskOptions option').filter(function() {
      return $(this).val() === val;
    }).first().data('id');
    $('#taskIdInput').val(id ? id : '');
  });
});
</script>

<?php $this->load->view('partials/footer'); ?>
