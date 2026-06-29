<?php $this->load->view('partials/header', ['title' => 'Self-Assessment']); ?>
<div class="oms-form-compact">

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-person-check text-primary me-2"></i>Self-Assessment</h4>
      <p class="text-muted mb-0 small">Submit your self-evaluation for pending appraisals</p>
    </div>
    <a href="<?php echo site_url('performance'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <i class="bi bi-check-circle-fill me-2"></i><?php echo esc_view($this->session->flashdata('success')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo esc_view($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (empty($appraisals)): ?>
    <div class="card shadow-sm border-0 text-center p-5">
      <i class="bi bi-clipboard-check text-muted" style="font-size:3rem;"></i>
      <h5 class="mt-3 text-muted">No appraisals pending your self-assessment.</h5>
      <p class="text-muted small">Your manager will create an appraisal for you to review.</p>
    </div>
  <?php else: ?>
    <div class="row g-2 oms-form-grid">
      <?php foreach ($appraisals as $a): ?>
      <div class="col-12 col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
            <span class="fw-semibold">Period: <?php echo esc_view($a->period); ?></span>
            <?php
              $badge = ['draft' => 'secondary', 'submitted' => 'info', 'approved' => 'success'];
              $b = isset($badge[$a->status]) ? $badge[$a->status] : 'secondary';
            ?>
            <span class="badge bg-<?php echo $b; ?>"><?php echo ucfirst($a->status); ?></span>
          </div>
          <div class="card-body">
            <dl class="row small mb-3">
              <dt class="col-5 text-muted">Manager Rating</dt>
              <dd class="col-7">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="bi bi-star<?php echo ($i <= (int)$a->rating) ? '-fill text-warning' : ' text-muted'; ?>"></i>
                <?php endfor; ?>
                (<?php echo (int)$a->rating; ?>/5)
              </dd>
              <dt class="col-5 text-muted">KPI Score</dt>
              <dd class="col-7"><?php echo esc_view($a->kpi_score); ?></dd>
              <?php if ($a->comments): ?>
              <dt class="col-5 text-muted">Manager Notes</dt>
              <dd class="col-7"><?php echo esc_view($a->comments); ?></dd>
              <?php endif; ?>
            </dl>

            <?php if ($a->self_rating): ?>
              <div class="alert alert-info py-2 small mb-0">
                <strong>Your self-rating:</strong>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="bi bi-star<?php echo ($i <= (int)$a->self_rating) ? '-fill text-warning' : ' text-muted'; ?>"></i>
                <?php endfor; ?>
                <?php if ($a->self_comments): ?>
                  <br><em><?php echo esc_view($a->self_comments); ?></em>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <form method="post" action="<?php echo site_url('performance/self-assess'); ?>">
                <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                <input type="hidden" name="appraisal_id" value="<?php echo (int)$a->id; ?>">
                <div class="mb-2">
                  <label class="form-label fw-semibold small">Your Self-Rating</label>
                  <select name="self_rating" class="form-select form-select-sm" required>
                    <option value="">— Select —</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <option value="<?php echo $i; ?>"><?php echo $i; ?> Star<?php echo $i > 1 ? 's' : ''; ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="mb-2">
                  <label class="form-label fw-semibold small">Self-Assessment Comments</label>
                  <textarea name="self_comments" class="form-control form-control-sm" rows="3"
                            placeholder="Describe your achievements, challenges, and goals..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">
                  <i class="bi bi-send me-1"></i>Submit Self-Assessment
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</div>
<?php $this->load->view('partials/footer'); ?>
