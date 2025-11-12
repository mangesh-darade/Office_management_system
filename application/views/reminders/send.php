<?php $this->load->view('partials/header', ['title' => 'Send Reminder']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Send Reminder</h1>
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
    <form method="post" action="<?php echo site_url('reminders/send'); ?>" class="vstack gap-3" id="sendForm">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">User</label>
          <select name="user_id" class="form-select" required>
            <option value="">-- Select User --</option>
            <?php if (isset($users) && is_array($users)) foreach ($users as $u): ?>
              <?php $label = '';
                if (isset($u->full_label) && $u->full_label!=='') { $label = $u->full_label; }
                else if (isset($u->full_name) && $u->full_name!=='') { $label = $u->full_name; }
                else if (isset($u->name) && $u->name!=='') { $label = $u->name; }
                else if (isset($u->email)) { $label = $u->email; }
              ?>
              <option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($label); ?> (<?php echo htmlspecialchars(isset($u->email)?$u->email:''); ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">From Email (optional)</label>
          <input type="email" name="from_email" class="form-control" placeholder="e.g. sateri.mangesh@domain.com">
        </div>
        <div class="col-md-4">
          <label class="form-label">From Name (optional)</label>
          <input type="text" name="from_name" class="form-control" placeholder="Your name">
        </div>
        <div class="col-md-12">
          <label class="form-label">Message (optional)</label>
          <textarea name="body" rows="4" class="form-control" placeholder="If left blank, the subject will be sent as the message."></textarea>
        </div>
      </div>
      <div>
        <input type="hidden" name="send_now" id="send_now" value="0">
        <button class="btn btn-primary" type="submit">Queue Reminder</button>
        <button class="btn btn-success" type="button" onclick="document.getElementById('send_now').value='1'; document.getElementById('sendForm').submit();">Send Now</button>
        <a class="btn btn-light" href="<?php echo site_url('reminders'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
