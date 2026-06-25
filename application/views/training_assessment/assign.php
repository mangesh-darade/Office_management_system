<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/header', array('title' => 'Assign — ' . $assessment->title));
$lastLink = $this->session->flashdata('ta_assign_link');
$lastEmailTo = $this->session->flashdata('ta_assign_email_to');
$lastEmailStatus = $this->session->flashdata('ta_assign_email');
$flashSuccess = $this->session->flashdata('success');
$flashError = $this->session->flashdata('error');
$qCount = isset($question_count) ? (int)$question_count : 0;
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<div class="container py-4">
  <h1 class="h4 mb-3">Assign assessment</h1>
  <p class="text-muted"><?php echo esc_view($assessment->title); ?></p>

  <?php if ($qCount < 1): ?>
    <div class="alert alert-danger">
      <strong>No questions yet.</strong> Add questions before assigning — assignees would see an error otherwise.
      <a href="<?php echo site_url('training-assessment/questions/' . (int)$assessment->id); ?>" class="alert-link">Manage questions</a>
    </div>
  <?php endif; ?>

  <?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?php echo esc_view($flashSuccess); ?></div>
  <?php endif; ?>
  <?php if ($flashError): ?>
    <div class="alert alert-danger"><?php echo esc_view($flashError); ?></div>
  <?php endif; ?>

  <?php if (!empty($lastLink)): ?>
  <div class="card border-primary shadow-sm mb-4">
    <div class="card-header bg-primary text-white py-2">
      <i class="bi bi-link-45deg me-1"></i> Assessment link (share or copy)
    </div>
    <div class="card-body">
      <?php if ($lastEmailStatus === 'sent' && !empty($lastEmailTo)): ?>
        <p class="small text-success mb-2"><i class="bi bi-envelope-check me-1"></i>Email sent to <strong><?php echo esc_view($lastEmailTo); ?></strong></p>
      <?php elseif ($lastEmailStatus === 'skipped'): ?>
        <p class="small text-warning mb-2"><i class="bi bi-envelope-exclamation me-1"></i>SMTP not configured — send this link manually or set up email in Settings.</p>
      <?php elseif ($lastEmailStatus === 'failed'): ?>
        <p class="small text-danger mb-2"><i class="bi bi-envelope-x me-1"></i>Email could not be sent — use the link below.</p>
      <?php elseif ($lastEmailStatus === 'no_address'): ?>
        <p class="small text-warning mb-2"><i class="bi bi-person-exclamation me-1"></i>No email on file — share the link below with the assignee.</p>
      <?php endif; ?>
      <p class="mb-2">
        <a class="btn btn-sm btn-outline-primary" href="<?php echo esc_view($lastLink); ?>" target="_blank" rel="noopener">
          <i class="bi bi-box-arrow-up-right me-1"></i>Open assessment
        </a>
      </p>
      <label class="form-label small text-muted mb-1">Copy link</label>
      <div class="input-group input-group-sm">
        <input type="text" class="form-control font-monospace small ta-copy-src" readonly value="<?php echo esc_view($lastLink); ?>" id="ta-last-link-input">
        <button type="button" class="btn btn-outline-secondary ta-copy-btn" data-ta-copy-target="ta-last-link-input" data-ta-copy-text="<?php echo esc_view($lastLink); ?>" title="Copy to clipboard" aria-label="Copy assessment link">
          <i class="bi bi-clipboard"></i> Copy
        </button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-one" data-bs-toggle="tab" data-bs-target="#pane-one" type="button" role="tab">One user</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-cand" data-bs-toggle="tab" data-bs-target="#pane-cand" type="button" role="tab">One candidate</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-dept" data-bs-toggle="tab" data-bs-target="#pane-dept" type="button" role="tab">Bulk by department</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-csv" data-bs-toggle="tab" data-bs-target="#pane-csv" type="button" role="tab">Bulk CSV (candidates)</button>
    </li>
  </ul>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="pane-one" role="tabpanel">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">Existing user account</div>
        <div class="card-body">
          <p class="small text-muted">Search by name or email. Lists every active <strong>user account</strong> (not only HR employee records). Duplicate assignments to the same user are blocked.</p>
          <?php echo form_open('training-assessment/assign/' . (int)$assessment->id); ?>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <input type="hidden" name="assign_mode" value="employee">
          <div class="mb-3">
            <label class="form-label" for="ta-emp-select">User</label>
            <select name="user_id" id="ta-emp-select" class="form-select" required <?php echo $qCount < 1 ? 'disabled' : ''; ?>>
              <option value="">— Select —</option>
              <?php
              $assign_list = isset($assign_users) ? $assign_users : array();
              foreach ($assign_list as $u):
                  $uid = (int) $u->id;
                  if ($uid < 1) {
                      continue;
                  }
                  $disp = '';
                  if (isset($u->name) && trim((string) $u->name) !== '') {
                      $disp = trim((string) $u->name);
                  } elseif ((isset($u->first_name) && trim((string) $u->first_name) !== '') || (isset($u->last_name) && trim((string) $u->last_name) !== '')) {
                      $disp = trim(trim((string) (isset($u->first_name) ? $u->first_name : '')) . ' ' . trim((string) (isset($u->last_name) ? $u->last_name : '')));
                  }
                  $em = isset($u->email) ? trim((string) $u->email) : '';
                  if ($disp === '') {
                      $disp = $em !== '' ? $em : ('User #' . $uid);
                  }
                  $line = $disp . ($em !== '' ? ' — ' . $em : '');
              ?>
                <option value="<?php echo $uid; ?>"><?php echo esc_view($line); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary" <?php echo $qCount < 1 ? 'disabled' : ''; ?>>Assign &amp; email link</button>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="pane-cand" role="tabpanel">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">External candidate</div>
        <div class="card-body">
          <p class="small text-muted">Invitation is sent to the email you enter. Duplicate email for this assessment is blocked.</p>
          <?php echo form_open('training-assessment/assign/' . (int)$assessment->id); ?>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <input type="hidden" name="assign_mode" value="candidate">
          <div class="mb-2">
            <label class="form-label">Full name</label>
            <input type="text" name="candidate_name" class="form-control" required maxlength="190" <?php echo $qCount < 1 ? 'disabled' : ''; ?>>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="candidate_email" class="form-control" required maxlength="190" <?php echo $qCount < 1 ? 'disabled' : ''; ?>>
          </div>
          <button type="submit" class="btn btn-primary" <?php echo $qCount < 1 ? 'disabled' : ''; ?>>Assign &amp; email link</button>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="pane-dept" role="tabpanel">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">Bulk assign employees by department</div>
        <div class="card-body">
          <p class="small text-muted">Creates one assignment per employee with a user account in that department. Skips duplicates. No bulk emails — copy links from the table below.</p>
          <?php if (empty($departments)): ?>
            <p class="text-muted small">No departments found on employee records.</p>
          <?php else: ?>
            <?php echo form_open('training-assessment/assign/' . (int)$assessment->id); ?>
            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
            <input type="hidden" name="assign_mode" value="bulk_department">
            <div class="mb-3">
              <label class="form-label">Department</label>
              <select name="department" class="form-select" required <?php echo $qCount < 1 ? 'disabled' : ''; ?>>
                <option value="">— Select —</option>
                <?php foreach ($departments as $d): ?>
                  <option value="<?php echo esc_view($d); ?>"><?php echo esc_view($d); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary" <?php echo $qCount < 1 ? 'disabled' : ''; ?>>Create assignments</button>
            <?php echo form_close(); ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="tab-pane fade" id="pane-csv" role="tabpanel">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white">Bulk external candidates</div>
        <div class="card-body">
          <p class="small text-muted">One candidate per line: <code>email</code> or <code>Name, email</code>. Skips duplicate emails for this assessment. No automatic emails — share links manually.</p>
          <?php echo form_open('training-assessment/assign/' . (int)$assessment->id); ?>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <input type="hidden" name="assign_mode" value="bulk_csv">
          <div class="mb-3">
            <label class="form-label">Lines</label>
            <textarea name="bulk_emails" class="form-control font-monospace" rows="8" placeholder="alice@example.com&#10;Bob Smith, bob@example.com" required <?php echo $qCount < 1 ? 'disabled' : ''; ?>></textarea>
          </div>
          <button type="submit" class="btn btn-primary" <?php echo $qCount < 1 ? 'disabled' : ''; ?>>Create assignments</button>
          <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($assignments)): ?>
  <h2 class="h6 mt-5 mb-2 text-muted">Recent assignments for this assessment</h2>
  <div class="card shadow-sm border-0">
    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Recipient</th>
            <th>Assigned</th>
            <th>Link</th>
            <th class="text-end">Copy</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($assignments as $as): ?>
          <?php
            $takeUrl = site_url('training-assessment/take/' . rawurlencode($as->access_token));
            if (!empty($as->user_id)) {
              $who = esc_view(trim($as->user_name . ' — ' . $as->user_email));
            } else {
              $who = esc_view(trim($as->candidate_name . ' — ' . $as->candidate_email)) . ' <span class="badge bg-secondary">Candidate</span>';
            }
          ?>
          <tr>
            <td><?php echo $who; ?></td>
            <td class="small text-muted"><?php echo esc_view($as->assigned_at); ?></td>
            <td class="small">
              <a href="<?php echo esc_view($takeUrl); ?>" target="_blank" rel="noopener" class="text-break"><?php echo esc_view($takeUrl); ?></a>
            </td>
            <td class="text-end text-nowrap">
              <button type="button" class="btn btn-outline-secondary btn-sm ta-copy-btn" data-ta-copy-text="<?php echo esc_view($takeUrl); ?>" title="Copy link" aria-label="Copy link">
                <i class="bi bi-clipboard"></i>
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <a href="<?php echo site_url('training-assessment'); ?>" class="btn btn-outline-secondary mt-4">Back to dashboard</a>
</div>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
(function() {
  function toast(msg) {
    var t = document.createElement('div');
    t.className = 'alert alert-success position-fixed bottom-0 end-0 m-3 py-2 px-3 shadow';
    t.setAttribute('role', 'status');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function() { t.remove(); }, 2000);
  }
  function copyText(text) {
    if (!text) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(function() { toast('Copied to clipboard'); }).catch(function() { copyExec(text); });
    } else {
      copyExec(text);
    }
  }
  function copyExec(text) {
    var tmp = document.createElement('textarea');
    tmp.value = text;
    tmp.style.position = 'fixed';
    tmp.style.left = '-9999px';
    document.body.appendChild(tmp);
    tmp.select();
    try {
      document.execCommand('copy');
      toast('Copied to clipboard');
    } catch (e) {
      toast('Could not copy — select the link manually');
    }
    tmp.remove();
  }
  function copyFromInput(id) {
    var el = document.getElementById(id);
    if (!el) return;
    var text = el.value || '';
    if (!text) return;
    copyText(text);
  }
  document.querySelectorAll('.ta-copy-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var direct = btn.getAttribute('data-ta-copy-text');
      if (direct) {
        copyText(direct);
        return;
      }
      var id = btn.getAttribute('data-ta-copy-target');
      if (id) copyFromInput(id);
    });
  });
  if (window.jQuery && jQuery.fn.select2) {
    jQuery('#ta-emp-select').select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Search user…' });
  }
})();
</script>
<?php $this->load->view('partials/footer'); ?>
