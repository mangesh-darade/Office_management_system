<?php
$import_errors = $this->session->flashdata('import_errors');
if (!empty($import_errors) && is_array($import_errors)):
?>
<div class="alert alert-warning py-2 mb-3">
  <div class="fw-semibold mb-1">Import row issues</div>
  <ul class="mb-0 small ps-3">
    <?php foreach ($import_errors as $err): ?>
      <li><?php echo esc_view((string) $err); ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
