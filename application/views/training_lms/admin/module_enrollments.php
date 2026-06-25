<?php $this->load->view('partials/header', array('title' => 'Enrollments — ' . $module->title)); ?>
<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-lms-admin'); ?>">LMS Admin</a></li>
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-lms-admin/topics/' . (int) $module->id); ?>"><?php echo esc_view($module->title); ?></a></li>
      <li class="breadcrumb-item active">Enrollments</li>
    </ol>
  </nav>
  <h1 class="h4 mb-3">Module enrollments</h1>
  <p class="text-muted small">Learners with at least one enrollment only see modules they are enrolled in. If nobody is enrolled, all users with LMS access still see every active module (legacy).</p>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Add by employee</div>
        <div class="card-body">
          <?php echo form_open('training-lms-admin/enrollment-save'); ?>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <input type="hidden" name="module_id" value="<?php echo (int) $module->id; ?>">
          <input type="hidden" name="enroll_mode" value="single">
          <div class="mb-3">
            <label class="form-label">Employee (linked user)</label>
            <select name="user_id" class="form-select" required>
              <option value="">— Select —</option>
              <?php foreach ($employees as $e): ?>
                <?php if (empty($e->user_id)) { continue; } ?>
                <option value="<?php echo (int) $e->user_id; ?>"><?php echo esc_view(trim($e->first_name . ' ' . $e->last_name) . ' — ' . (isset($e->user_name) ? $e->user_name : '')); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Enroll</button>
          <?php echo form_close(); ?>
        </div>
      </div>
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Bulk by department</div>
        <div class="card-body">
          <?php echo form_open('training-lms-admin/enrollment-save'); ?>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <input type="hidden" name="module_id" value="<?php echo (int) $module->id; ?>">
          <input type="hidden" name="enroll_mode" value="bulk_department">
          <div class="mb-3">
            <label class="form-label">Department</label>
            <select name="department" class="form-select" required>
              <option value="">— Select —</option>
              <?php foreach ($departments as $d): ?>
                <option value="<?php echo esc_view($d); ?>"><?php echo esc_view($d); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-outline-primary">Enroll department</button>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">Enrolled users</div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>User</th><th></th></tr></thead>
            <tbody>
              <?php if (empty($rows)): ?>
                <tr><td colspan="2" class="text-muted p-3">No enrollments yet.</td></tr>
              <?php else: ?>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td><?php echo esc_view($r->user_name ? $r->user_name : ('User #' . (int) $r->user_id)); ?><br><span class="small text-muted"><?php echo esc_view($r->user_email); ?></span></td>
                    <td class="text-end">
                      <?php echo form_open('training-lms-admin/enrollment-remove', array('class' => 'd-inline', 'onsubmit' => 'return confirm(\'Remove this enrollment?\');')); ?>
                      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                      <input type="hidden" name="module_id" value="<?php echo (int) $module->id; ?>">
                      <input type="hidden" name="user_id" value="<?php echo (int) $r->user_id; ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                      <?php echo form_close(); ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <a href="<?php echo site_url('training-lms-admin/topics/' . (int) $module->id); ?>" class="btn btn-outline-secondary mt-3">Back to topics</a>
</div>
<?php $this->load->view('partials/footer'); ?>
