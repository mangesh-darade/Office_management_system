<?php
$this->load->view('partials/header', ['title' => 'Google Calendar Reminders']);
?>
<div class="container-fluid py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-calendar-event me-2"></i>Google Calendar Reminders</h1>
    <div class="d-flex gap-2">
      <a href="<?php echo site_url('calendar-reminders/settings'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-gear me-1"></i>Settings
      </a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if (empty($configured)): ?>
    <div class="alert alert-warning">
      Google Client ID / Secret not set.
      <a href="<?php echo site_url('calendar-reminders/settings'); ?>">Open Settings</a>
    </div>
  <?php elseif (empty($connected)): ?>
    <div class="alert alert-info">
      Credentials saved, but Google is not connected yet.
      <a href="<?php echo site_url('calendar-reminders/connect'); ?>">Connect Google Calendar</a>
    </div>
  <?php else: ?>
    <div class="alert alert-success py-2">Google Calendar connected.</div>
  <?php endif; ?>

  <div class="card shadow-sm" style="max-width: 640px;">
    <div class="card-body">
      <p class="text-muted mb-3">Enter Gmail and date/time. Google will send an email reminder before the event.</p>
      <form method="post" action="<?php echo site_url('calendar-reminders/create'); ?>" autocomplete="off">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

        <div class="mb-3">
          <label class="form-label" for="email">Gmail / Email <span class="text-danger">*</span></label>
          <input type="email" class="form-control" id="email" name="email" required placeholder="you@gmail.com">
        </div>

        <div class="mb-3">
          <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="title" name="title" required maxlength="200" placeholder="Client call / Payment due">
        </div>

        <div class="row">
          <div class="col-md-7 mb-3">
            <label class="form-label" for="when">Date &amp; time <span class="text-danger">*</span></label>
            <input type="datetime-local" class="form-control" id="when" name="when" required>
          </div>
          <div class="col-md-5 mb-3">
            <label class="form-label" for="minutes">Remind before (minutes) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="minutes" name="minutes" value="30" min="0" step="1" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label" for="description">Note (optional)</label>
          <textarea class="form-control" id="description" name="description" rows="3" maxlength="2000" placeholder="Optional details"></textarea>
        </div>

        <button type="submit" class="btn btn-primary" <?php echo empty($connected) ? 'disabled' : ''; ?>>
          <i class="bi bi-bell me-1"></i>Create reminder
        </button>
      </form>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
