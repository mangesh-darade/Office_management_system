<?php
$this->load->view('partials/header', ['title' => 'Send Reminder']);
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Send Reminder</h1>
    <a class="btn btn-light btn-sm" href="<?php echo site_url('reminders/dashboard'); ?>">Back</a>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <div class="alert alert-info small">
    Reminders are sent via <strong>Google Calendar</strong> email alerts.
    Select one user or multiple users, then create immediately or schedule a time.
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" action="<?php echo site_url('reminders/send'); ?>" id="sendReminderForm">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

        <div class="row g-3">
          <div class="col-md-7">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label mb-0">Recipients <span class="text-danger">*</span></label>
              <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-secondary" id="btnSelectAll">Select all</button>
                <button type="button" class="btn btn-outline-secondary" id="btnClearAll">Clear</button>
              </div>
            </div>
            <select name="user_ids[]" id="userIds" class="form-select" multiple size="12" required>
              <?php if (isset($users) && is_array($users)): ?>
                <?php foreach ($users as $u): ?>
                  <?php
                    $label = '';
                    if (!empty($u->full_label)) {
                        $label = $u->full_label;
                    } elseif (!empty($u->full_name)) {
                        $label = $u->full_name;
                    } elseif (!empty($u->name)) {
                        $label = $u->name;
                    } elseif (!empty($u->email)) {
                        $label = $u->email; // fallback only if no name
                    }
                    if ($label === '' || empty($u->email)) {
                        continue;
                    }
                  ?>
                  <option value="<?php echo (int) $u->id; ?>"><?php echo esc_view($label); ?></option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
            <div class="form-text">Hold Ctrl (Windows) / Cmd (Mac) to select multiple people.</div>
          </div>

          <div class="col-md-5">
            <div class="mb-3">
              <label class="form-label">Delivery</label>
              <select name="delivery_method" id="deliveryMethod" class="form-select">
                <option value="immediate">Send now (Google Calendar)</option>
                <option value="scheduled">Schedule date &amp; time</option>
              </select>
            </div>
            <div class="mb-3" id="sendAtWrap" style="display:none;">
              <label class="form-label">Send at</label>
              <input type="datetime-local" name="send_at" id="sendAt" class="form-control">
            </div>
            <div class="mb-3">
              <label class="form-label">Subject <span class="text-danger">*</span></label>
              <input type="text" name="subject" class="form-control" required maxlength="200" placeholder="Reminder title">
            </div>
            <div class="mb-3">
              <label class="form-label">Message (optional)</label>
              <textarea name="body" rows="5" class="form-control" placeholder="Optional details. You can use {name}, {email}, {date}, {time}."></textarea>
            </div>
            <?php
              $role_id = (int) $this->session->userdata('role_id');
              $is_admin = ($role_id === 1) || (function_exists('is_admin_group') && is_admin_group());
            if ($is_admin): ?>
              <div class="mb-3">
                <label class="form-label">From email (optional)</label>
                <input type="email" name="from_email" class="form-control" placeholder="Optional override">
              </div>
              <div class="mb-3">
                <label class="form-label">From name (optional)</label>
                <input type="text" name="from_name" class="form-control" placeholder="Optional override">
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="mt-3 d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-bell me-1"></i>Create reminder(s)
          </button>
          <a class="btn btn-light" href="<?php echo site_url('reminders/dashboard'); ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
(function () {
  var sel = document.getElementById('userIds');
  var delivery = document.getElementById('deliveryMethod');
  var sendAtWrap = document.getElementById('sendAtWrap');
  var sendAt = document.getElementById('sendAt');
  var form = document.getElementById('sendReminderForm');

  function toggleSendAt() {
    var scheduled = delivery && delivery.value === 'scheduled';
    if (sendAtWrap) {
      sendAtWrap.style.display = scheduled ? 'block' : 'none';
    }
    if (sendAt) {
      if (scheduled) {
        sendAt.setAttribute('required', 'required');
      } else {
        sendAt.removeAttribute('required');
        sendAt.value = '';
      }
    }
  }

  if (delivery) {
    delivery.addEventListener('change', toggleSendAt);
    toggleSendAt();
  }

  var btnAll = document.getElementById('btnSelectAll');
  var btnClear = document.getElementById('btnClearAll');
  if (btnAll && sel) {
    btnAll.addEventListener('click', function () {
      for (var i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = true;
      }
    });
  }
  if (btnClear && sel) {
    btnClear.addEventListener('click', function () {
      for (var i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = false;
      }
    });
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      if (!sel || sel.selectedOptions.length < 1) {
        e.preventDefault();
        alert('Please select at least one recipient.');
        return false;
      }
      return true;
    });
  }
})();
</script>
<?php $this->load->view('partials/footer'); ?>
