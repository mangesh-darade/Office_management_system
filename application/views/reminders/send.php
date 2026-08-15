<?php
$this->load->view('partials/header', ['title' => 'Send Reminder']);
$users = isset($users) && is_array($users) ? $users : array();
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Send Reminder</h1>
    <a class="btn btn-light btn-sm" href="<?php echo site_url('reminders'); ?>">Back</a>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <div class="alert alert-info small">
    <strong>SMTP</strong> = Settings → Email mail only (same as Announce).
    <strong>Google Calendar</strong> = calendar reminder (not SMTP).
    Audience: selected people or all active users. Placeholders: <code>{name}</code>, <code>{email}</code>, <code>{date}</code>, <code>{time}</code>.
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="post" action="<?php echo site_url('reminders/send'); ?>" id="sendReminderForm">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

        <div class="row g-3">
          <div class="col-md-7">
            <div class="mb-3">
              <label class="form-label">Audience <span class="text-danger">*</span></label>
              <select name="audience" id="audience" class="form-select">
                <option value="selected">Selected users</option>
                <option value="all">All active users</option>
              </select>
              <div class="form-text">All active users = every user with an email (same as Announce).</div>
            </div>

            <div id="recipientsWrap">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label mb-0">Recipients</label>
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-secondary" id="btnSelectAll">Select all</button>
                  <button type="button" class="btn btn-outline-secondary" id="btnClearAll">Clear</button>
                </div>
              </div>
              <select name="user_ids[]" id="userIds" class="form-select" multiple size="12">
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
                        $label = $u->email;
                    }
                    if ($label === '' || empty($u->email)) {
                        continue;
                    }
                  ?>
                  <option value="<?php echo (int) $u->id; ?>"><?php echo esc_view($label . ' <' . $u->email . '>'); ?></option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Hold Ctrl (Windows) / Cmd (Mac) to select multiple people.</div>
            </div>
          </div>

          <div class="col-md-5">
            <div class="mb-3">
              <label class="form-label">Delivery <span class="text-danger">*</span></label>
              <select name="delivery_method" id="deliveryMethod" class="form-select">
                <option value="smtp_now" selected>Send now (SMTP mail only)</option>
                <option value="smtp_scheduled">Schedule (SMTP mail only)</option>
                <option value="google_now">Google Calendar (no SMTP)</option>
              </select>
              <div class="form-text" id="deliveryHint">Uses Settings → Email (configure_email_from_settings) and sends mail only.</div>
            </div>
            <div class="mb-3" id="sendAtWrap" style="display:none;">
              <label class="form-label">Send at <span class="text-danger">*</span></label>
              <input type="datetime-local" name="send_at" id="sendAt" class="form-control">
              <div class="form-text">Queued until this time, then sent by SMTP cron / send-queue.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Subject <span class="text-danger">*</span></label>
              <input type="text" name="subject" id="reminderSubject" class="form-control" required maxlength="200" placeholder="Reminder title">
            </div>
            <div class="mb-3">
              <label class="form-label">Message</label>
              <textarea name="body" id="reminderBody" rows="6" class="form-control" placeholder="Hello {name},&#10;&#10;Your reminder for {date} at {time}."></textarea>
              <div class="form-text">Each person gets their own email with placeholders filled.</div>
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

        <div class="card border mt-3 mb-0" id="livePreviewCard">
          <div class="card-header py-2 d-flex justify-content-between align-items-center">
            <strong class="small mb-0"><i class="bi bi-eye me-1"></i>Email preview</strong>
            <span class="text-muted small" id="previewToHint">Sample recipient</span>
          </div>
          <div class="card-body py-3">
            <div class="mb-1 small"><span class="text-muted">Subject:</span> <strong id="previewSubject">—</strong></div>
            <div class="border rounded p-3 bg-light small" id="previewBody" style="white-space:pre-wrap; min-height:4.5rem;">—</div>
          </div>
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
          <button type="button" class="btn btn-outline-secondary" id="btnPreviewReminder">
            <i class="bi bi-eye me-1"></i>Refresh preview
          </button>
          <button type="submit" class="btn btn-primary" id="btnSubmitReminder">
            <i class="bi bi-envelope me-1"></i>Send
          </button>
          <a class="btn btn-light" href="<?php echo site_url('reminders'); ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
(function () {
  var sel = document.getElementById('userIds');
  var audience = document.getElementById('audience');
  var recipientsWrap = document.getElementById('recipientsWrap');
  var delivery = document.getElementById('deliveryMethod');
  var deliveryHint = document.getElementById('deliveryHint');
  var sendAtWrap = document.getElementById('sendAtWrap');
  var sendAt = document.getElementById('sendAt');
  var form = document.getElementById('sendReminderForm');
  var btnSubmit = document.getElementById('btnSubmitReminder');
  var subjectEl = document.getElementById('reminderSubject');
  var bodyEl = document.getElementById('reminderBody');
  var previewSubject = document.getElementById('previewSubject');
  var previewBody = document.getElementById('previewBody');
  var previewToHint = document.getElementById('previewToHint');
  var btnPreview = document.getElementById('btnPreviewReminder');

  var hints = {
    smtp_now: 'SMTP mail only — Settings → Email, same helpers as Announce.',
    smtp_scheduled: 'Queues SMTP mail for cron / send-queue (no Google).',
    google_now: 'Google Calendar event only — no SMTP mail.'
  };

  function pad2(n) {
    return (n < 10 ? '0' : '') + n;
  }

  function nowParts() {
    var d = new Date();
    return {
      date: d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate()),
      time: pad2(d.getHours()) + ':' + pad2(d.getMinutes())
    };
  }

  function replaceVars(text, name, email) {
    var parts = nowParts();
    return String(text || '')
      .replace(/\{name\}/gi, name)
      .replace(/\{email\}/gi, email)
      .replace(/\{date\}/gi, parts.date)
      .replace(/\{time\}/gi, parts.time);
  }

  function sampleRecipient() {
    if (audience && audience.value === 'all') {
      return { name: 'All users (example)', email: 'user@example.com' };
    }
    if (sel && sel.selectedOptions && sel.selectedOptions.length > 0) {
      var opt = sel.selectedOptions[0];
      var label = (opt.textContent || '').trim();
      var m = label.match(/^(.*)\s*<([^>]+)>\s*$/);
      if (m) {
        return { name: m[1].trim(), email: m[2].trim() };
      }
      return { name: label || 'Recipient', email: 'user@example.com' };
    }
    return { name: 'Sample User', email: 'user@example.com' };
  }

  function updatePreview() {
    var sample = sampleRecipient();
    var subject = (subjectEl && subjectEl.value) ? subjectEl.value : '';
    var body = (bodyEl && bodyEl.value) ? bodyEl.value : '';
    if (!body) {
      body = 'Hello {name},\n\n' + (subject || 'Reminder');
    }
    if (previewToHint) {
      previewToHint.textContent = 'Example: ' + sample.name + ' <' + sample.email + '>';
    }
    if (previewSubject) {
      previewSubject.textContent = replaceVars(subject, sample.name, sample.email) || '—';
    }
    if (previewBody) {
      previewBody.textContent = replaceVars(body, sample.name, sample.email) || '—';
    }
  }

  function syncAudience() {
    var all = audience && audience.value === 'all';
    if (recipientsWrap) {
      recipientsWrap.style.opacity = all ? '0.55' : '1';
      recipientsWrap.style.pointerEvents = all ? 'none' : 'auto';
    }
    if (sel) {
      sel.required = !all;
    }
    updatePreview();
  }

  function syncDelivery() {
    var method = delivery ? delivery.value : 'smtp_now';
    var scheduled = method === 'smtp_scheduled';
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
    if (deliveryHint && hints[method]) {
      deliveryHint.textContent = hints[method];
    }
    if (btnSubmit) {
      if (method === 'smtp_scheduled') {
        btnSubmit.innerHTML = '<i class="bi bi-clock me-1"></i>Schedule';
      } else if (method === 'google_now') {
        btnSubmit.innerHTML = '<i class="bi bi-calendar-event me-1"></i>Create in Google';
      } else {
        btnSubmit.innerHTML = '<i class="bi bi-envelope me-1"></i>Send SMTP';
      }
    }
  }

  if (audience) {
    audience.addEventListener('change', syncAudience);
  }
  if (delivery) {
    delivery.addEventListener('change', syncDelivery);
  }
  if (sel) {
    sel.addEventListener('change', updatePreview);
  }
  if (subjectEl) {
    subjectEl.addEventListener('input', updatePreview);
  }
  if (bodyEl) {
    bodyEl.addEventListener('input', updatePreview);
  }
  if (btnPreview) {
    btnPreview.addEventListener('click', updatePreview);
  }
  syncAudience();
  syncDelivery();
  updatePreview();

  var btnAll = document.getElementById('btnSelectAll');
  var btnClear = document.getElementById('btnClearAll');
  if (btnAll && sel) {
    btnAll.addEventListener('click', function () {
      for (var i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = true;
      }
      updatePreview();
    });
  }
  if (btnClear && sel) {
    btnClear.addEventListener('click', function () {
      for (var i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = false;
      }
      updatePreview();
    });
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      var all = audience && audience.value === 'all';
      if (!all && (!sel || sel.selectedOptions.length < 1)) {
        e.preventDefault();
        alert('Select at least one recipient, or choose All active users.');
        return false;
      }
      return true;
    });
  }
})();
</script>
<?php $this->load->view('partials/footer'); ?>
