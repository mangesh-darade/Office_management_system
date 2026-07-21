<?php
  $input_id = isset($input_id) ? (string) $input_id : 'da-attachment';
  $input_name = isset($input_name) ? (string) $input_name : 'attachments[]';
  $max_files = isset($max_files) ? (int) $max_files : daily_activity_max_attachments_per_submit();
  $max_mb = 100;
  if (function_exists('my_works_upload_max_mb')) {
      $this->load->helper('my_works_form');
      $max_mb = (int) my_works_upload_max_mb();
  }
?>
<div class="da-attachment-widget">
  <label class="form-label small fw-bold text-uppercase text-muted mb-1" for="<?php echo esc_view($input_id); ?>">
    <i class="bi bi-paperclip me-1"></i>Files
  </label>
  <input type="file"
         class="form-control form-control-sm"
         id="<?php echo esc_view($input_id); ?>"
         name="<?php echo esc_view($input_name); ?>"
         multiple>
  <div class="form-text">PDF, Excel, images, zip… (max <?php echo (int) $max_files; ?>)</div>
</div>
