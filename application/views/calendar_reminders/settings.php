<?php
$this->load->view('partials/header', ['title' => 'Calendar Reminder Settings']);
?>
<div class="container-fluid py-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-gear me-2"></i>Calendar Reminder Settings</h1>
    <a href="<?php echo site_url('calendar-reminders'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="alert alert-info small">
    <strong>Setup:</strong> Google Console → create <strong>Web application</strong> OAuth client →
    add the redirect URI below under <strong>Authorized redirect URIs</strong> → Save →
    paste Client ID/Secret here → Connect Google.
  </div>
  <div class="alert alert-secondary small mb-0">
    <strong>Also used for:</strong> Reminders, schedules, Today's Plan alerts, and attendance check-in/check-out alerts
    (enable under Settings → Attendance; per-user toggles on User form).
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h5">Google OAuth credentials</h2>

          <div class="mb-3">
            <label class="form-label fw-semibold">1) Add this Authorized redirect URI in Google Console</label>
            <div class="input-group">
              <input type="text" class="form-control font-monospace" id="gcal_redirect_uri" readonly value="<?php echo esc_view($redirect_uri); ?>" onclick="this.select()">
              <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('gcal_redirect_uri').value); this.textContent='Copied';">Copy</button>
            </div>
            <div class="form-text mt-2">
              Also add if you use 127.0.0.1:
              <br><code><?php echo esc_view(str_replace('://localhost', '://127.0.0.1', $redirect_uri)); ?></code>
              <br>Authorized JavaScript origins (optional): <code>http://localhost</code>
            </div>
          </div>

          <form method="post" action="<?php echo site_url('calendar-reminders/settings'); ?>">
            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

            <p class="fw-semibold mb-2">2) Save Client ID + Secret</p>

            <div class="mb-3">
              <label class="form-label" for="client_id">Client ID</label>
              <input type="text" class="form-control" id="client_id" name="client_id" placeholder="xxxx.apps.googleusercontent.com" autocomplete="off">
            </div>
            <div class="mb-3">
              <label class="form-label" for="client_secret">Client Secret</label>
              <input type="password" class="form-control" id="client_secret" name="client_secret" placeholder="GOCSPX-..." autocomplete="new-password">
            </div>

            <div class="mb-3">
              <label class="form-label" for="credentials_json">Or paste Web credentials.json</label>
              <textarea class="form-control font-monospace small" id="credentials_json" name="credentials_json" rows="6" placeholder='{"web":{"client_id":"...","client_secret":"..."}}'></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Save credentials</button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h2 class="h5">3) Connect</h2>
          <ul class="list-unstyled mb-3">
            <li>Credentials: <?php echo !empty($configured) ? '<span class="text-success">Saved</span>' : '<span class="text-danger">Missing</span>'; ?></li>
            <li>Google auth: <?php echo !empty($connected) ? '<span class="text-success">Connected</span>' : '<span class="text-warning">Not connected</span>'; ?></li>
          </ul>

          <?php if (!empty($configured)): ?>
            <a href="<?php echo site_url('calendar-reminders/connect'); ?>" class="btn btn-success mb-2">
              <i class="bi bi-google me-1"></i>Connect Google
            </a>
          <?php else: ?>
            <p class="text-muted small">Save Client ID and Secret first.</p>
          <?php endif; ?>

          <?php if (!empty($connected)): ?>
            <form method="post" action="<?php echo site_url('calendar-reminders/disconnect'); ?>" class="d-inline" onsubmit="return confirm('Disconnect Google Calendar?');">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <button type="submit" class="btn btn-outline-danger mb-2">Disconnect</button>
            </form>
          <?php endif; ?>

          <hr>
          <p class="small text-muted mb-0">
            If Google still shows <em>Access blocked / request is invalid</em>, the redirect URI in Console
            does not match the box above. Edit the Web client, add that URI, Save, then try again.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
