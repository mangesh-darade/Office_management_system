<?php $this->load->view('partials/header', ['title' => ($action==='edit'?'Edit':'New').' Release']); ?>
<div class="oms-form-compact">
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3"><?php echo $action==='edit'?'Edit':'New'; ?> Release</h1>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div><?php endif; ?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div><?php endif; ?>
<div class="card shadow-soft mb-3"><div class="card-body">
<form method="post" id="releaseForm"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="row g-2 oms-form-grid">
  <div class="col-md-4"><label class="form-label">Project</label><select name="project_id" class="form-select" required><?php foreach ($projects as $p): ?><option value="<?php echo (int)$p->id; ?>" <?php echo ($item && (int)$item->project_id===(int)$p->id)?'selected':''; ?>><?php echo esc_view($p->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><label class="form-label">Version</label><input name="version" class="form-control" required value="<?php echo $item?esc_view($item->version):''; ?>"></div>
  <div class="col-md-3"><label class="form-label">Planned date</label><input type="date" name="planned_date" class="form-control" value="<?php echo $item?esc_view($item->planned_date):''; ?>"></div>
  <div class="col-md-3"><label class="form-label">Status</label><?php $this->load->view('partials/status_select', array(
    'field_name' => 'status',
    'module_type' => 'releases',
    'select_id' => 'releaseStatus',
    'current' => $item ? (string) $item->status : '',
    'default_code' => 'planned',
  )); ?></div>
  <div class="col-12"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?php echo $item?esc_view($item->title):''; ?>"></div>
  <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3" placeholder="Summary for stakeholders"><?php echo $item?esc_view($item->description):''; ?></textarea></div>
</div>

<div class="oms-form-actions">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <label class="form-label mb-0 fw-semibold">Release note points</label>
    <button type="button" class="btn btn-sm btn-outline-primary" id="addNotePoint"><i class="bi bi-plus-lg me-1"></i>Add point</button>
  </div>
  <p class="text-muted small mb-2">List what is included in this release. These points are emailed when you send release notes.</p>
  <div id="notePointsList" class="vstack gap-2">
    <?php
    $points = !empty($note_points) ? $note_points : array('');
    foreach ($points as $idx => $pt):
    ?>
    <div class="input-group note-point-row">
      <span class="input-group-text text-muted"><?php echo (int)$idx + 1; ?>.</span>
      <input type="text" name="note_points[]" class="form-control" maxlength="500" value="<?php echo esc_view($pt); ?>" placeholder="e.g. Fixed login timeout on mobile">
      <button type="button" class="btn btn-outline-secondary btn-remove-point" title="Remove"><i class="bi bi-x-lg"></i></button>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<?php if ($action === 'edit' && !empty($related_defects)): ?>
<div class="oms-form-actions">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <label class="form-label mb-0 fw-semibold">Related defects (linked to this release)</label>
    <?php if (!empty($fixed_defects)): ?>
    <form method="post" action="<?php echo site_url('releases/add-all-fixed/'.(int)$item->id); ?>" class="d-inline">
      <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
      <button type="submit" class="btn btn-sm btn-outline-success">Add all fixed defects to notes</button>
    </form>
    <?php endif; ?>
  </div>
  <p class="text-muted small">Click Add to copy a defect into release note points.</p>
  <ul class="list-group list-group-flush border rounded">
    <?php foreach ($related_defects as $d): ?>
    <?php
      $defectLine = trim((string)$d->defect_number . ': ' . (string)$d->title);
      if ($defectLine === ': ') { $defectLine = (string)$d->title; }
    ?>
    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
      <span>
        <span class="badge bg-light text-dark border me-1"><?php echo esc_view($d->status); ?></span>
        <?php echo esc_view($defectLine); ?>
      </span>
      <button type="button" class="btn btn-sm btn-outline-secondary btn-add-defect-point" data-text="<?php echo esc_view($defectLine); ?>">Add to notes</button>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php elseif ($action === 'edit'): ?>
<div class="oms-form-actions">
  <label class="form-label fw-semibold">Related defects</label>
  <p class="text-muted small mb-0">No defects linked to this release yet. Link defects on the Defects screen (Release field).</p>
</div>
<?php endif; ?>

<?php if ($action === 'edit' && !empty($can_send_notes)): ?>
<div class="mt-4 border-top pt-4">
  <label class="form-label fw-semibold"><i class="bi bi-envelope me-1"></i>Send release notes (via Reminders email)</label>
  <p class="text-muted small">Select team members to receive this release by email. Uses the same delivery pipeline as <a href="<?php echo site_url('reminders/send'); ?>">Reminders → Send</a>.</p>
  <?php if ($item && !empty($item->notes_sent_at)): ?>
  <p class="small text-success mb-2"><i class="bi bi-check-circle me-1"></i>Last sent: <?php echo esc_view(date('M j, Y g:i A', strtotime($item->notes_sent_at))); ?></p>
  <?php endif; ?>
  <div class="row g-2 oms-form-grid">
    <div class="col-12">
      <label class="form-label">Recipients</label>
      <select name="user_ids[]" id="releaseRecipients" class="form-select" multiple size="8">
        <?php if (!empty($users)) foreach ($users as $u): ?>
          <?php
            $label = '';
            if (isset($u->full_label) && $u->full_label !== '') { $label = $u->full_label; }
            elseif (isset($u->full_name) && $u->full_name !== '') { $label = $u->full_name; }
            elseif (isset($u->name) && $u->name !== '') { $label = $u->name; }
            else { $label = isset($u->email) ? $u->email : 'User'; }
          ?>
          <option value="<?php echo (int)$u->id; ?>"><?php echo esc_view($label); ?> (<?php echo esc_view(isset($u->email) ? $u->email : ''); ?>)</option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">Hold Ctrl (Windows) or Cmd (Mac) to select multiple users.</div>
    </div>
    <div class="col-md-6">
      <div class="form-check mt-1">
        <input class="form-check-input" type="checkbox" name="send_notes_after_save" id="sendNotesAfterSave" value="1">
        <label class="form-check-label" for="sendNotesAfterSave">Send to selected recipients when I save</label>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="oms-form-actions">
  <button class="btn btn-primary" type="submit">Save</button>
  <?php if ($action === 'edit'): ?>
  <a class="btn btn-outline-secondary" href="<?php echo site_url('releases/view/'.(int)$item->id); ?>">View</a>
  <?php endif; ?>
  <?php if ($action === 'edit' && !empty($can_send_notes)): ?>
  <button class="btn btn-success" type="button" id="btnSendNotesNow">Send notes now</button>
  <?php endif; ?>
  <?php if ($action === 'edit' && (function_exists('has_module_access') && (has_module_access('releases_edit') || has_module_access('releases')))): ?>
  <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteReleaseModal">Delete</button>
  <?php endif; ?>
  <a class="btn btn-outline-secondary" href="<?php echo site_url('releases'); ?>">Cancel</a>
</div>
</form>
</div></div>
</div>

<?php if ($action === 'edit' && !empty($can_send_notes)): ?>
<form method="post" id="sendNotesForm" action="<?php echo site_url('releases/send-notes/'.(int)$item->id); ?>" class="d-none">
  <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
  <div id="sendNotesUserIds"></div>
</form>
<?php endif; ?>

<?php if ($action === 'edit'): ?>
<div class="modal fade" id="deleteReleaseModal" tabindex="-1">
  <div class="modal-dialog modal-sm"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Delete release?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body small">This soft-deletes the release. Linked defects are kept.</div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
      <form method="post" action="<?php echo site_url('releases/delete/'.(int)$item->id); ?>">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
      </form>
    </div>
  </div></div>
</div>
<?php endif; ?>

<script>
(function(){
  var list = document.getElementById('notePointsList');
  if (!list) return;

  function renumber(){
    var rows = list.querySelectorAll('.note-point-row');
    rows.forEach(function(row, i){
      var badge = row.querySelector('.input-group-text');
      if (badge) badge.textContent = (i + 1) + '.';
    });
  }

  document.getElementById('addNotePoint').addEventListener('click', function(){
    var row = document.createElement('div');
    row.className = 'input-group note-point-row';
    row.innerHTML = '<span class="input-group-text text-muted">.</span>'
      + '<input type="text" name="note_points[]" class="form-control" maxlength="500" placeholder="Release note point">'
      + '<button type="button" class="btn btn-outline-secondary btn-remove-point" title="Remove"><i class="bi bi-x-lg"></i></button>';
    list.appendChild(row);
    renumber();
    row.querySelector('input').focus();
  });

  list.addEventListener('click', function(e){
    if (e.target.closest('.btn-remove-point')){
      var rows = list.querySelectorAll('.note-point-row');
      if (rows.length <= 1) {
        rows[0].querySelector('input').value = '';
        return;
      }
      e.target.closest('.note-point-row').remove();
      renumber();
    }
  });

  document.querySelectorAll('.btn-add-defect-point').forEach(function(btn){
    btn.addEventListener('click', function(){
      var text = btn.getAttribute('data-text') || '';
      var inputs = list.querySelectorAll('input[name="note_points[]"]');
      var placed = false;
      inputs.forEach(function(inp){
        if (!placed && inp.value.trim() === '') {
          inp.value = text;
          placed = true;
        }
      });
      if (!placed) {
        document.getElementById('addNotePoint').click();
        var last = list.querySelector('.note-point-row:last-child input');
        if (last) last.value = text;
      }
    });
  });

  var btnSend = document.getElementById('btnSendNotesNow');
  if (btnSend) {
    btnSend.addEventListener('click', function(){
      var sel = document.getElementById('releaseRecipients');
      var form = document.getElementById('sendNotesForm');
      var holder = document.getElementById('sendNotesUserIds');
      if (!sel || !form || !holder) return;
      var picked = Array.prototype.slice.call(sel.selectedOptions);
      if (!picked.length) {
        alert('Select at least one recipient.');
        return;
      }
      holder.innerHTML = '';
      picked.forEach(function(opt){
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'user_ids[]';
        inp.value = opt.value;
        holder.appendChild(inp);
      });
      document.querySelectorAll('#releaseForm input[name="note_points[]"]').forEach(function(inp){
        if (inp.value.trim() === '') return;
        var h = document.createElement('input');
        h.type = 'hidden';
        h.name = 'note_points[]';
        h.value = inp.value;
        holder.appendChild(h);
      });
      form.submit();
    });
  }
})();
</script>
</div>
<?php $this->load->view('partials/footer'); ?>
