<?php $this->load->view('partials/header', ['title' => 'Reminder Templates']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Reminder Templates</h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('reminders'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="alert alert-info small">You can use placeholders: <code>{name}</code></div>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('reminders/templates'); ?>" class="vstack gap-4">
      <div>
        <h5 class="mb-3">Morning Template</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Subject</label>
            <input type="text" name="morning_subject" class="form-control" value="<?php echo htmlspecialchars(isset($morning_subject)?$morning_subject:''); ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Body</label>
            <textarea name="morning_body" rows="5" class="form-control"><?php echo htmlspecialchars(isset($morning_body)?$morning_body:''); ?></textarea>
          </div>
        </div>
      </div>
      <hr>
      <div>
        <h5 class="mb-3">Night Template</h5>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Subject</label>
            <input type="text" name="night_subject" class="form-control" value="<?php echo htmlspecialchars(isset($night_subject)?$night_subject:''); ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Body</label>
            <textarea name="night_body" rows="5" class="form-control"><?php echo htmlspecialchars(isset($night_body)?$night_body:''); ?></textarea>
          </div>
        </div>
      </div>
      <div>
        <button class="btn btn-primary" type="submit">Save Templates</button>
        <a class="btn btn-light" href="<?php echo site_url('reminders'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
