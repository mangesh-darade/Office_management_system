<?php
$this->load->view('partials/header', array(
  'title' => 'Daily Activity Log',
  'extra_css' => array('assets/css/daily-activity.css'),
));
$log_count = isset($logs) && is_array($logs) ? count($logs) : 0;
$date_short = date('M j, Y', strtotime($date));
?>

<div class="da-page">

  <div class="da-page-head">
    <h1 class="da-page-title">
      <span class="da-page-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
      Daily Activity
    </h1>
    <div class="da-page-actions">
      <form method="get" class="da-date-pill mb-0" title="Change date">
        <i class="bi bi-calendar3 text-primary"></i>
        <input type="date" name="date" value="<?php echo esc_view($date); ?>" onchange="this.form.submit()" aria-label="Work date">
      </form>
      <a href="<?php echo site_url('daily-activity/list'); ?>" class="btn btn-outline-secondary btn-sm py-1 px-2">
        <i class="bi bi-list-ul"></i><span class="d-none d-sm-inline ms-1">History</span>
      </a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success border-0 d-flex align-items-center py-2 px-3 mb-2">
      <i class="bi bi-check-circle-fill me-2"></i><?php echo esc_view($this->session->flashdata('success')); ?>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger border-0 d-flex align-items-center py-2 px-3 mb-2">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo esc_view($this->session->flashdata('error')); ?>
    </div>
  <?php endif; ?>

  <div class="da-workspace">

    <section class="da-composer" aria-labelledby="da-composer-title">
      <div class="da-composer-head">
        <h2 id="da-composer-title"><i class="bi bi-plus-lg"></i> New log</h2>
        <div class="da-composer-meta"><?php echo esc_view($date_short); ?></div>
      </div>
      <div class="da-composer-body">
        <?php echo form_open_multipart('daily-activity/save', array('id' => 'da-log-form')); ?>
          <input type="hidden" name="work_date" value="<?php echo esc_view($date); ?>">

          <div class="da-composer-row">
            <label class="da-field-label" for="activityTitleInput">Activity</label>
            <input class="form-control form-control-sm" list="taskOptions" name="activity_title" id="activityTitleInput"
                   placeholder="Task or title…" autocomplete="off">
            <datalist id="taskOptions">
              <?php foreach ($tasks as $t): ?>
                <option data-id="<?php echo (int) $t->id; ?>" value="<?php echo esc_view($t->title); ?>"></option>
              <?php endforeach; ?>
            </datalist>
            <input type="hidden" name="task_id" id="taskIdInput">
          </div>

          <div class="da-composer-row">
            <label class="da-field-label" for="summernote">Description <span class="text-danger">*</span></label>
            <textarea class="form-control" name="description" id="summernote" required></textarea>
          </div>

          <div class="da-composer-row da-composer-files">
            <?php $this->load->view('daily_activity/_attachment_field', array('input_id' => 'da-create-attachments')); ?>
          </div>

          <div class="da-composer-actions">
            <button type="submit" class="btn btn-primary btn-sm da-btn-save">
              <i class="bi bi-check-lg me-1"></i>Save log
            </button>
          </div>
        <?php echo form_close(); ?>
      </div>
    </section>

    <section class="da-feed" aria-labelledby="da-feed-title">
      <div class="da-feed-head">
        <h2 id="da-feed-title">
          Today
          <span class="da-feed-count"><?php echo (int) $log_count; ?></span>
        </h2>
      </div>

      <div class="da-list">
        <?php if ($log_count < 1): ?>
          <div class="da-empty">
            <i class="bi bi-journal-plus" aria-hidden="true"></i>
            <span>No logs yet — add one on the left.</span>
          </div>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <?php
              $title = !empty($log->activity_title)
                ? (string) $log->activity_title
                : (!empty($log->task_title) ? (string) $log->task_title : 'General update');
              $atts = (!empty($attachments_map) && isset($attachments_map[(int) $log->id]))
                ? $attachments_map[(int) $log->id]
                : array();
            ?>
            <article class="da-row">
              <div class="da-row-main">
                <div class="da-row-top">
                  <h3 class="da-row-title" title="<?php echo esc_view($title); ?>"><?php echo esc_view($title); ?></h3>
                  <span class="da-row-time">
                    <i class="bi bi-clock"></i>
                    <?php echo date('g:i A', strtotime($log->created_at)); ?>
                  </span>
                </div>
                <div class="da-row-body activity-description">
                  <?php echo sanitize_html_output($log->description); ?>
                </div>
                <?php if (!empty($atts)): ?>
                  <div class="da-row-atts">
                    <?php $this->load->view('daily_activity/_attachments_list', array(
                      'attachments' => $atts,
                      'show_remove' => false,
                    )); ?>
                  </div>
                <?php endif; ?>
              </div>
              <div class="da-row-actions btn-group btn-group-sm" role="group" aria-label="Actions">
                <?php if (function_exists('has_module_access') && (has_module_access('daily_activity_edit') || has_module_access('daily_activity'))): ?>
                  <a href="<?php echo site_url('daily-activity/edit/' . (int) $log->id); ?>"
                     class="btn btn-outline-primary" title="Edit" aria-label="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                <?php endif; ?>
                <?php if (function_exists('has_module_access') && (has_module_access('daily_activity_delete') || has_module_access('daily_activity'))): ?>
                  <?php echo form_open('daily-activity/delete/' . (int) $log->id, array(
                    'onsubmit' => "return confirm('Delete this log?');",
                    'class' => 'd-inline m-0',
                  )); ?>
                    <button type="submit" class="btn btn-outline-danger" title="Delete" aria-label="Delete">
                      <i class="bi bi-trash"></i>
                    </button>
                  <?php echo form_close(); ?>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="<?php echo base_url('assets/js/daily-activity-editor.js'); ?>"></script>
<script>
$(document).ready(function() {
  var csrfName = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var csrfHash = <?php echo json_encode($this->security->get_csrf_hash()); ?>;
  var uploadUrl = <?php echo json_encode(site_url('daily-activity/upload-image')); ?>;
  var $note = window.daInitSummernote({
    selector: '#summernote',
    placeholder: 'What did you work on?',
    height: 120,
    onImageUpload: function(files) {
      if (!files || !files.length) { return; }
      var data = new FormData();
      data.append('file', files[0]);
      data.append(csrfName, csrfHash);
      $.ajax({
        url: uploadUrl,
        method: 'POST',
        data: data,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
          if (res && res.status === 'success' && res.url) {
            $note.summernote('insertImage', res.url);
          } else {
            alert((res && res.message) ? res.message : 'Image upload failed.');
          }
        },
        error: function() {
          alert('Image upload failed.');
        }
      });
    }
  });

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
