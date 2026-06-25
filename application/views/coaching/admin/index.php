<?php $this->load->view('partials/header', ['title' => 'Coaching Settings']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card shadow-soft border-info">
      <div class="card-header bg-info bg-opacity-10">Webhook URLs</div>
      <div class="card-body small">
        <p class="mb-1"><strong>Razorpay:</strong> <code><?php echo site_url('coaching-webhooks/razorpay'); ?></code></p>
        <p class="mb-1"><strong>Twilio WhatsApp inbound:</strong> <code><?php echo site_url('coaching-webhooks/whatsapp-inbound'); ?></code></p>
        <p class="mb-0 text-muted">Session reminders: <code><?php echo site_url('cron/coaching-session-reminders'); ?>?token=...</code></p>
      </div>
    </div>
  </div>
</div>
<div class="row g-3">
  <div class="col-md-4">
    <div class="card shadow-soft">
      <div class="card-header">Portal branding</div>
      <div class="card-body"><?php echo form_open('coaching-admin'); ?>
        <input type="hidden" name="action" value="branding">
        <input name="portal_title" class="form-control mb-2" value="<?php echo esc_view($branding->portal_title); ?>">
        <input name="primary_color" class="form-control mb-2" value="<?php echo esc_view($branding->primary_color); ?>">
        <textarea name="welcome_message" class="form-control mb-2" rows="2"><?php echo esc_view($branding->welcome_message); ?></textarea>
        <button class="btn btn-primary btn-sm">Save</button>
      <?php echo form_close(); ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-soft">
      <div class="card-header">Razorpay</div>
      <div class="card-body"><?php echo form_open('coaching-admin'); ?>
        <input type="hidden" name="action" value="payments">
        <select name="gateway" class="form-select mb-2"><option value="manual">Manual</option><option value="razorpay" <?php echo $payments->gateway==='razorpay'?'selected':''; ?>>Razorpay</option></select>
        <input name="key_id" class="form-control mb-2" value="<?php echo esc_view($payments->key_id); ?>">
        <input name="key_secret" type="password" class="form-control mb-2" placeholder="Key secret">
        <input name="webhook_secret" type="password" class="form-control mb-2" placeholder="Webhook secret">
        <div class="form-check mb-2"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="payActive" <?php echo $payments->is_active?'checked':''; ?>><label for="payActive">Enable payments</label></div>
        <button class="btn btn-primary btn-sm">Save</button>
      <?php echo form_close(); ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-soft">
      <div class="card-header">Automation rules</div>
      <div class="card-body small">
        <?php echo form_open('coaching-admin'); ?>
        <input type="hidden" name="action" value="automation">
        <input name="name" class="form-control form-control-sm mb-2" placeholder="Rule name" required>
        <select name="trigger_type" class="form-select form-select-sm mb-2">
          <option value="goal_stale_days">Goal not updated (days)</option>
        </select>
        <input name="trigger_days" type="number" class="form-control form-control-sm mb-2" value="7" min="1">
        <select name="action_type" class="form-select form-select-sm mb-2">
          <option value="log_reminder">Log only</option>
          <option value="email_coach">Email coach</option>
          <option value="email_client">Email client</option>
        </select>
        <button class="btn btn-primary btn-sm">Add rule</button>
        <?php echo form_close(); ?>
        <hr>
        <ul class="list-unstyled mb-2">
        <?php if (empty($rules)): ?>
          <li class="text-muted">No rules yet.</li>
        <?php else: foreach ($rules as $r): ?>
          <li class="mb-1"><strong><?php echo esc_view($r->name); ?></strong> — <?php echo esc_view($r->trigger_type); ?> / <?php echo esc_view($r->action_type); ?></li>
        <?php endforeach; endif; ?>
        </ul>
        <a class="btn btn-outline-primary btn-sm d-block" href="<?php echo site_url('coaching-admin/run-automation'); ?>">Run automation now</a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-soft">
      <div class="card-header">Backup</div>
      <div class="card-body">
        <a class="btn btn-outline-secondary btn-sm d-block mb-2" href="<?php echo site_url('coaching-admin/backup'); ?>">Download SQL backup</a>
        <p class="small text-muted mb-0">Cron: <code>cron/coaching_automation</code> and <code>cron/run_all</code></p>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
