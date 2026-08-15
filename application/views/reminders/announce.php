<?php
$this->load->view('partials/header', ['title' => 'Announce']);
$ci =& get_instance();
if (!isset($ci->settings)) {
  $ci->load->model('Setting_model', 'settings');
}
$default_from_email = $ci->settings->get_setting('email_smtp_user', '');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Announce (Send to all users)</h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('reminders'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="alert alert-info small mb-3">
  This sends a <strong>separate email to every active user</strong> using Settings → Email (SMTP).
  Make sure SMTP host / user / password are saved before you queue.
</div>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('reminders/announce'); ?>" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">From Email (optional)</label>
          <input type="email" name="from_email" class="form-control" placeholder="e.g. user@domain.com" value="<?php echo esc_view($default_from_email); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">From Name (optional)</label>
          <input type="text" name="from_name" class="form-control" placeholder="Your name">
        </div>
        <div class="col-md-12">
          <label class="form-label">Message</label>
          <textarea name="body" rows="6" class="form-control" placeholder="Message content for all users"></textarea>
        </div>
      </div>
      <div>
        <button class="btn btn-primary" type="submit">Send to all users</button>
        <a class="btn btn-light" href="<?php echo site_url('reminders'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
