<?php
$editing = isset($editing) && $editing;
$job     = isset($job) ? $job : null;
$title   = $editing ? 'Edit Job Post' : 'Create Job Post';
$action  = $editing ? site_url('recruitment/edit-job/' . $job->id) : site_url('recruitment/create-job');
$this->load->view('partials/header', ['title' => $title]);
?>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-1 fw-bold">
        <i class="bi bi-<?php echo $editing ? 'pencil-square' : 'plus-circle'; ?> text-primary me-2"></i>
        <?php echo $title; ?>
      </h4>
    </div>
    <a href="<?php echo site_url('recruitment'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Back to Jobs
    </a>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-body p-4">
          <form method="post" action="<?php echo $action; ?>">
            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

            <div class="mb-3">
              <label class="form-label fw-semibold">Job Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" required
                     value="<?php echo $job ? esc_view($job->title) : ''; ?>"
                     placeholder="e.g. Senior Software Engineer">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Department</label>
                <input type="text" name="department" class="form-control"
                       value="<?php echo $job ? esc_view($job->department ? $job->department : '') : ''; ?>"
                       placeholder="e.g. Engineering, Sales">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Positions</label>
                <input type="number" name="positions" class="form-control" min="1" value="<?php echo $job ? (int)$job->positions : 1; ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                  <?php foreach (['open' => 'Open', 'draft' => 'Draft', 'closed' => 'Closed'] as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php echo ($job && $job->status === $val) ? 'selected' : (!$job && $val === 'open' ? 'selected' : ''); ?>>
                      <?php echo $label; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Experience Level</label>
              <select name="experience_level" class="form-select">
                <option value="">— Select —</option>
                <?php foreach (['Entry Level', 'Mid Level', 'Senior Level', 'Lead', 'Manager', 'Director'] as $level): ?>
                  <option value="<?php echo $level; ?>" <?php echo ($job && $job->experience_level === $level) ? 'selected' : ''; ?>>
                    <?php echo $level; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Job Description</label>
              <textarea name="description" class="form-control" rows="8"
                        placeholder="Describe the role, responsibilities, and requirements..."><?php echo $job ? esc_view($job->description ? $job->description : '') : ''; ?></textarea>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check-lg me-1"></i><?php echo $editing ? 'Save Changes' : 'Publish Job'; ?>
              </button>
              <a href="<?php echo site_url('recruitment'); ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
