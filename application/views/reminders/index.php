<?php $this->load->view('partials/header', ['title' => 'Reminders']); ?>
<div class="container-fluid py-3">
<div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div>
    <h1 class="h4 mb-1 fw-bold"><i class="bi bi-bell text-primary me-2"></i>Reminders</h1>
    <p class="text-muted small mb-0">Manage notification reminders</p>
  </div>
  <div class="d-flex gap-1 flex-wrap mt-2 mt-sm-0">
    <?php $can_manage_reminders = function_exists('has_module_access') && (has_module_access('reminders') || is_admin_group()); ?>
    <?php if($can_manage_reminders): ?>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('reminders/schedules'); ?>"><i class="bi bi-calendar-event me-1"></i><span class="d-none d-md-inline">Schedules</span></a>
    <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('reminders/send'); ?>"><i class="bi bi-send me-1"></i><span class="d-none d-md-inline">Send</span></a>
    <a class="btn btn-outline-info btn-sm" href="<?php echo site_url('reminders/templates'); ?>"><i class="bi bi-file-text me-1"></i><span class="d-none d-md-inline">Templates</span></a>
    <a class="btn btn-outline-warning btn-sm" href="<?php echo site_url('reminders/announce'); ?>"><i class="bi bi-megaphone me-1"></i><span class="d-none d-md-inline">Announce</span></a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reminders/import'); ?>"><i class="bi bi-upload me-1"></i><span class="d-none d-md-inline">Import</span></a>
    <button type="button" class="btn btn-success btn-sm" onclick="submitSelected()"><i class="bi bi-send-check me-1"></i><span class="d-none d-lg-inline">Send Selected</span></button>
    <button type="button" class="btn btn-danger btn-sm" onclick="deleteSelected()"><i class="bi bi-trash me-1"></i><span class="d-none d-lg-inline">Delete</span></button>
    <?php endif; ?>
  </div>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo esc_view($this->session->flashdata('error')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo esc_view($this->session->flashdata('success')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php
$send_preview = $this->session->flashdata('send_preview');
if (is_array($send_preview)):
  $sample = isset($send_preview['sample']) && is_array($send_preview['sample']) ? $send_preview['sample'] : null;
?>
  <div class="card border-success shadow-sm mb-3">
    <div class="card-header bg-success-subtle py-2">
      <strong><i class="bi bi-envelope-check me-1"></i>Sent preview</strong>
      <span class="text-muted small ms-2">
        <?php echo esc_view(isset($send_preview['delivery']) ? $send_preview['delivery'] : ''); ?>
        · <?php echo esc_view(isset($send_preview['audience']) ? $send_preview['audience'] : ''); ?>
        · <?php echo (int) (isset($send_preview['count']) ? $send_preview['count'] : 0); ?> sent
        <?php if (!empty($send_preview['failed'])): ?>
          · <?php echo (int) $send_preview['failed']; ?> failed
        <?php endif; ?>
      </span>
    </div>
    <div class="card-body py-3">
      <?php if ($sample): ?>
        <div class="small text-muted mb-2">Example of what one recipient received:</div>
        <div class="mb-1"><span class="text-muted">To:</span> <strong><?php echo esc_view($sample['to_name']); ?></strong> &lt;<?php echo esc_view($sample['to_email']); ?>&gt;</div>
        <div class="mb-2"><span class="text-muted">Subject:</span> <strong><?php echo esc_view($sample['subject']); ?></strong></div>
        <div class="border rounded p-3 bg-light small" style="white-space:pre-wrap;"><?php echo esc_view($sample['body']); ?></div>
      <?php else: ?>
        <div class="text-muted small">No sample body available.</div>
      <?php endif; ?>
      <?php if (!empty($send_preview['when'])): ?>
        <div class="text-muted small mt-2">When: <?php echo esc_view($send_preview['when']); ?></div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('reminders/cron/send-selected'); ?>" id="bulkForm">
      <div class="row g-2 align-items-end mb-2">
        <div class="col-md-2">
          <label class="form-label small mb-1">Status</label>
          <select id="filterStatus" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="queued">Queued</option>
            <option value="sent">Sent</option>
            <option value="error">Error</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small mb-1">Type</label>
          <select id="filterType" class="form-select form-select-sm">
            <option value="">All</option>
            <option value="daily_morning">daily_morning</option>
            <option value="daily_night">daily_night</option>
            <option value="manual">manual</option>
            <option value="schedule">schedule</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small mb-1">Search</label>
          <input id="filterSearch" type="text" class="form-control form-select-sm" placeholder="Search email, name, subject">
        </div>
        <div class="col-md-4 text-md-end small text-muted">
          <div>Tip: Select rows and use 🚀 Send Selected or 🗑️ Delete Selected.</div>
        </div>
      </div>
      <div class="row g-2 align-items-end mb-2">
        <div class="col-md-4 col-lg-3">
          <label class="form-label small mb-1">Template for Send Selected</label>
          <select name="tpl_code" id="tpl_code" class="form-select form-select-sm">
            <option value="">Use existing subject &amp; message</option>
            <option value="daily_morning">Morning Template</option>
            <option value="daily_night">Night Template</option>
            <option value="bulk_manual">Bulk Mail Template</option>
          </select>
        </div>
      </div>
      <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th style="width:36px"><input type="checkbox" id="check_all" onclick="toggleAll(this)"></th>
            <th>#</th>
            <th>User</th>
            <th>Email</th>
            <th>Type</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Send At</th>
            <th>Sent At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="text-center text-muted">No reminders queued.</td></tr>
          <?php else: foreach ($rows as $r): ?>
          <tr data-status="<?php echo esc_view(isset($r->status)?$r->status:''); ?>" data-type="<?php echo esc_view(isset($r->type)?$r->type:''); ?>" data-text="<?php
              $label=''; if (isset($r->full_label) && $r->full_label!=='') { $label=$r->full_label; }
              else if (isset($r->full_name) && $r->full_name!=='') { $label=$r->full_name; }
              else if (isset($r->name) && $r->name!=='') { $label=$r->name; }
              $txt = trim(($label).' '.(isset($r->email)?$r->email:(isset($r->user_email)?$r->user_email:''))).' '.(isset($r->subject)?$r->subject:'');
              echo esc_view(strtolower($txt)); ?>">
            <td>
              <?php $st = isset($r->status)?$r->status:'queued'; if ($st!=='sent'): ?>
              <input type="checkbox" name="ids[]" value="<?php echo (int)$r->id; ?>">
              <?php endif; ?>
            </td>
            <td><?php echo (int)$r->id; ?></td>
            <td>
              <?php
                $label = '';
                if (isset($r->full_label) && $r->full_label!=='') { $label = $r->full_label; }
                else if (isset($r->full_name) && $r->full_name!=='') { $label = $r->full_name; }
                else if (isset($r->name) && $r->name!=='') { $label = $r->name; }
                echo esc_view($label);
              ?>
            </td>
            <td><?php echo esc_view(isset($r->email)?$r->email:(isset($r->user_email)?$r->user_email:'')); ?></td>
            <td>
              <?php
                $t = isset($r->type)?$r->type:'';
                $cls = 'bg-secondary';
                $label = $t;
                if ($t==='daily_morning') { $cls='bg-info'; $label='daily_morning'; }
                else if ($t==='daily_night') { $cls='bg-warning'; $label='daily_night'; }
                else if ($t==='manual') { $cls='bg-primary'; $label='manual'; }
                else if ($t==='bulk_manual') { $label=''; }
                else if ($t==='schedule') { $cls='bg-secondary'; $label='schedule'; }
              ?>
              <?php if ($label !== ''): ?>
                <span class="badge text-dark <?php echo $cls; ?> border"><?php echo esc_view($label); ?></span>
              <?php else: ?>
                <span class="text-muted">&mdash;</span>
              <?php endif; ?>
            </td>
            <td class="small"><?php echo esc_view(isset($r->subject)?$r->subject:''); ?></td>
            <td>
              <?php $st = isset($r->status)?$r->status:'queued'; $scls='bg-light';
                if ($st==='sent') $scls='bg-success'; else if ($st==='error') $scls='bg-danger'; else $scls='bg-secondary';
              ?>
              <span class="badge text-white <?php echo $scls; ?>"><?php echo esc_view($st); ?></span>
            </td>
            <td><?php echo esc_view(isset($r->send_at)?$r->send_at:''); ?></td>
            <td><?php echo esc_view(isset($r->sent_at)?$r->sent_at:''); ?></td>
            <td>
              <?php if($can_manage_reminders): ?>
              <form method="post" action="<?php echo site_url('reminders/delete/'.(int)$r->id); ?>" class="d-inline" onsubmit="return confirm('Delete this reminder?');">
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
      </div>
    </form>
  </div>
</div>
<script>
function toggleAll(src){
  var boxes = document.querySelectorAll('input[name="ids[]"]');
  for (var i=0;i<boxes.length;i++){ boxes[i].checked = src.checked; }
}
function selectedCount(){
  return document.querySelectorAll('input[name="ids[]"]:checked').length;
}
function toolbarAction(type){
  var count = selectedCount();
  if (count > 0){
    // If user clicked Queue Morning with selection, still only send selected
    if (type === 'morning') { submitSelected(); return; }
  }
  if (type === 'morning'){
    window.location.href = '<?php echo site_url('reminders/cron/morning'); ?>';
  }
}
function submitSelected(){
  var count = selectedCount();
  if (count === 0){ alert('Please select at least one reminder.'); return; }
  var form = document.getElementById('bulkForm');
  form.action = '<?php echo site_url('reminders/cron/send-selected'); ?>';
  form.submit();
}
function deleteSelected(){
  var count = selectedCount();
  if (count === 0){ alert('Please select at least one reminder.'); return; }
  if (!confirm('Delete selected reminders?')){ return; }
  var form = document.getElementById('bulkForm');
  form.action = '<?php echo site_url('reminders/delete-selected'); ?>';
  form.submit();
}
// Filters
function applyFilters(){
  var st = document.getElementById('filterStatus').value;
  var tp = document.getElementById('filterType').value;
  var q = document.getElementById('filterSearch').value.toLowerCase();
  var rows = document.querySelectorAll('table tbody tr');
  for (var i=0;i<rows.length;i++){
    var r = rows[i];
    if (!r.hasAttribute('data-status')){ continue; }
    var ok = true;
    if (st && r.getAttribute('data-status') !== st) ok = false;
    if (tp && r.getAttribute('data-type') !== tp) ok = false;
    if (q){
      var txt = r.getAttribute('data-text') || '';
      if (txt.indexOf(q) === -1) ok = false;
    }
    r.style.display = ok ? '' : 'none';
  }
}
document.getElementById('filterStatus').addEventListener('change', applyFilters);
document.getElementById('filterType').addEventListener('change', applyFilters);
document.getElementById('filterSearch').addEventListener('input', applyFilters);
</script>
</div>
<?php $this->load->view('partials/footer'); ?>
