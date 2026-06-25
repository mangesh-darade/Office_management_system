<?php $this->load->view('partials/header', ['title' => 'Edit Daily Activity']); ?>

<div class="container-fluid py-3">
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
          <form method="post" action="<?php echo site_url('daily-activity/edit/' . (int)$log->id); ?>">
            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

            <div class="mb-3">
              <label class="form-label fw-semibold">Work Date <span class="text-danger">*</span></label>
              <input type="date" name="work_date" class="form-control"
                     value="<?php echo esc_view($log->work_date); ?>" required
                     max="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Activity Title</label>
              <input type="text" name="activity_title" class="form-control"
                     value="<?php echo esc_view($log->activity_title ? $log->activity_title : ''); ?>"
                     placeholder="Brief title for this activity">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Linked Task <span class="text-muted small">(optional)</span></label>
              <select name="task_id" class="form-select">
                <option value="">— No linked task —</option>
                <?php foreach ($tasks as $t): ?>
                  <option value="<?php echo (int)$t->id; ?>"
                    <?php echo ((int)$log->task_id === (int)$t->id) ? 'selected' : ''; ?>>
                    #<?php echo (int)$t->id; ?> — <?php echo esc_view($t->title); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
              <textarea name="description" id="description" class="form-control" rows="6"
                        placeholder="Describe what you worked on..." required><?php echo esc_view(strip_tags($log->description)); ?></textarea>
              <div class="form-text">Plain text description of your work activity.</div>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i>Save Changes
              </button>
              <a href="<?php echo site_url('daily-activity?date=' . esc_view($log->work_date)); ?>" class="btn btn-outline-secondary">
                Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
