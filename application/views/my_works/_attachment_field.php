<?php
  $input_id = isset($input_id) ? (string) $input_id : 'mw-attachment';
  $input_name = isset($input_name) ? (string) $input_name : 'attachments[]';
  $max_mb = isset($max_mb) ? (int) $max_mb : my_works_upload_max_mb();
  $max_bytes = isset($max_bytes) ? (int) $max_bytes : my_works_upload_max_bytes();
  $max_files = isset($max_files) ? (int) $max_files : my_works_max_attachments_per_submit();
?>
<div class="mw-attachment-widget" data-max-bytes="<?php echo (int) $max_bytes; ?>" data-max-files="<?php echo (int) $max_files; ?>">
  <div class="mw-attachment-picker-row">
    <label class="mw-attachment-choose" for="<?php echo htmlspecialchars($input_id, ENT_QUOTES, 'UTF-8'); ?>" title="Choose files to attach">
      <i class="bi bi-paperclip mw-attachment-choose-icon"></i>
      <span class="mw-attachment-choose-text">Attach files</span>
    </label>
    <input type="file"
           class="mw-attachment-input"
           id="<?php echo htmlspecialchars($input_id, ENT_QUOTES, 'UTF-8'); ?>"
           name="<?php echo htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8'); ?>"
           multiple>
  </div>

  <ul class="mw-attachment-file-list list-unstyled mb-0 mt-2" hidden></ul>

  <div class="mw-attachment-progress" hidden>
    <div class="d-flex justify-content-between align-items-center small mb-1">
      <span class="mw-attachment-progress-label text-muted">Uploading…</span>
      <span class="mw-attachment-progress-pct fw-semibold">0%</span>
    </div>
    <div class="progress mw-attachment-progress-bar">
      <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
  </div>

  <div class="form-text mt-2">Up to <?php echo (int) $max_files; ?> files, max <?php echo (int) $max_mb; ?> MB each — images, PDF, Office, video, audio, zip, and more.</div>
  <div class="small text-danger mw-attachment-error mt-1" hidden></div>
</div>
