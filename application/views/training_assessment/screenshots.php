<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/header', array('title' => 'Assessment screenshots'));
?>
<div class="container-fluid py-4">
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2"><?php echo htmlspecialchars((string) $this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h1 class="h4 mb-1">Assessment screenshots</h1>
      <div class="small text-muted">
        <?php echo htmlspecialchars((string) $au->assessment_title); ?> -
        Attempt #<?php echo (int) $au->id; ?>
      </div>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('training-assessment/result/' . (int) $au->id); ?>">
      Back to result
    </a>
  </div>

  <?php if (empty($shots)): ?>
    <div class="alert alert-warning">No screenshots were captured for this assessment attempt.</div>
  <?php else: ?>
    <?php echo form_open('training-assessment/screenshots/' . (int) $au->id . '/delete-bulk', array('id' => 'ta-shot-bulk-form', 'class' => 'mb-3', 'onsubmit' => "return confirm('Delete selected screenshots?');")); ?>
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <label class="form-check mb-0">
          <input class="form-check-input" type="checkbox" id="ta-check-all">
          <span class="form-check-label small">Select all</span>
        </label>
        <button type="submit" class="btn btn-danger btn-sm">
          <i class="bi bi-trash me-1"></i>Delete selected
        </button>
      </div>
      <div class="row g-3">
        <?php foreach ($shots as $shot): ?>
          <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
            <div class="card h-100 shadow-sm border-0">
              <a href="<?php echo base_url($shot->capture_path); ?>" target="_blank" rel="noopener">
                <img src="<?php echo base_url($shot->capture_path); ?>" class="card-img-top" alt="Captured screenshot">
              </a>
              <div class="card-body py-2">
                <label class="form-check mb-2">
                  <input class="form-check-input ta-shot-check" type="checkbox" name="screenshot_ids[]" value="<?php echo (int) $shot->id; ?>">
                  <span class="form-check-label small">Select</span>
                </label>
                <div class="small fw-semibold"><?php echo htmlspecialchars((string) $shot->captured_at); ?></div>
                <?php if (!empty($shot->ip_address)): ?>
                  <div class="small text-muted">IP: <?php echo htmlspecialchars((string) $shot->ip_address); ?></div>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-outline-danger w-100 ta-delete-one" data-id="<?php echo (int) $shot->id; ?>">
                  <i class="bi bi-trash me-1"></i>Delete single
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php echo form_close(); ?>
    <script>
      (function() {
        var form = document.getElementById('ta-shot-bulk-form');
        var checkAll = document.getElementById('ta-check-all');
        var checks = document.querySelectorAll('.ta-shot-check');
        if (!checkAll || !checks.length || !form) return;
        checkAll.addEventListener('change', function() {
          for (var i = 0; i < checks.length; i++) {
            checks[i].checked = !!checkAll.checked;
          }
        });
        var oneBtns = document.querySelectorAll('.ta-delete-one');
        for (var b = 0; b < oneBtns.length; b++) {
          oneBtns[b].addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            for (var i = 0; i < checks.length; i++) {
              checks[i].checked = (checks[i].value === id);
            }
            if (checkAll) checkAll.checked = false;
            if (window.confirm('Delete this screenshot?')) {
              form.submit();
            }
          });
        }
      })();
    </script>
  <?php endif; ?>
</div>
<?php $this->load->view('partials/footer'); ?>
