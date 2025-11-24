<?php $this->load->view('partials/header', ['title' => 'Bulk Reminder']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Bulk Reminder</h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('reminders'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('reminders/bulk'); ?>" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">To Emails</label>
          <textarea name="to_emails" rows="4" class="form-control" placeholder="Enter multiple email addresses separated by commas, semicolons, or new lines"></textarea>
          <div class="form-text">Multiple recipients supported; invalid addresses will be ignored.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">From Email (optional)</label>
          <input type="email" name="from_email" class="form-control" placeholder="e.g. you@domain.com">
        </div>
        <div class="col-md-4">
          <label class="form-label">From Name (optional)</label>
          <input type="text" name="from_name" class="form-control" placeholder="Your name">
        </div>
        <div class="col-md-6">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" required value="<?php echo htmlspecialchars(isset($bulk_subject)?$bulk_subject:''); ?>">
        </div>
        <div class="col-12">
          <label class="form-label">Message</label>
          <textarea name="body" rows="6" class="form-control"><?php echo htmlspecialchars(isset($bulk_body)?$bulk_body:''); ?></textarea>
          <div class="form-text">You can use placeholders like <code>{name}</code>. When not known, the recipient's email will be used as the name.</div>
        </div>
      </div>
      <div>
        <button class="btn btn-primary" type="submit">Queue Bulk Emails</button>
        <a class="btn btn-light" href="<?php echo site_url('reminders'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
