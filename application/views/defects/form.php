<?php $this->load->view('partials/header', ['title' => ($action==='edit'?'Edit':'Log').' Defect']); ?>
<div class="oms-form-compact">
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3"><?php echo $action==='edit'?'Edit':'Log'; ?> Defect</h1>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div><?php endif; ?>
<div class="card shadow-soft oms-form-card"><div class="card-body">
<form method="post" enctype="multipart/form-data" id="defectForm"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="row g-2 oms-form-grid">
  <div class="col-md-4"><label class="form-label">Project</label><select name="project_id" id="defectProjectId" class="form-select" required><?php foreach ($projects as $p): ?><option value="<?php echo (int)$p->id; ?>" <?php echo ($item && (int)$item->project_id===(int)$p->id)?'selected':''; ?>><?php echo esc_view($p->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><label class="form-label">Release (optional)</label><select name="release_id" id="defectReleaseId" class="form-select"><option value="">—</option><?php foreach ($releases as $rel): ?><option value="<?php echo (int)$rel->id; ?>" <?php echo ($item && (int)$item->release_id===(int)$rel->id)?'selected':''; ?>><?php echo esc_view($rel->version . ' — ' . $rel->title); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-4"><label class="form-label">Related task (optional)</label><select name="task_id" id="defectTaskId" class="form-select"><option value="">—</option><?php foreach ($tasks as $t): ?><option value="<?php echo (int)$t->id; ?>" <?php echo ($item && (int)$item->task_id===(int)$t->id)?'selected':''; ?>><?php echo esc_view($t->title); ?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label class="form-label">Title</label><input name="title" class="form-control" required value="<?php echo $item?esc_view($item->title):''; ?>"></div>
  <div class="col-md-3"><label class="form-label">Severity</label><select name="severity" class="form-select"><?php foreach (['low','medium','high','critical'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($item && $item->severity===$s)?'selected':(!$item && $s==='medium'?'selected':''); ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><label class="form-label">Priority</label><select name="priority" class="form-select"><?php foreach (['low','medium','high','critical'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($item && $item->priority===$s)?'selected':(!$item && $s==='medium'?'selected':''); ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><label class="form-label">Status</label><?php $this->load->view('partials/status_select', array(
    'field_name' => 'status',
    'module_type' => 'defects',
    'current' => $item ? (string) $item->status : '',
    'default_code' => 'open',
  )); ?></div>
  <div class="col-md-3"><label class="form-label">Due date (SLA)</label><input type="date" name="due_date" class="form-control" value="<?php echo ($item && !empty($item->due_date))?esc_view($item->due_date):''; ?>"></div>
  <div class="col-md-3"><label class="form-label">Assign to</label><select name="assigned_to" class="form-select"><option value="">Unassigned</option><?php foreach ($members as $m): ?><option value="<?php echo (int)$m->id; ?>" <?php echo ($item && (int)$item->assigned_to===(int)$m->id)?'selected':''; ?>><?php echo esc_view($m->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-12"><label class="form-label">Description</label><textarea id="defect-description" name="description" class="form-control" rows="6" placeholder="Describe the issue…"><?php
    if ($item && !empty($item->description)) {
        $allowed = '<p><br><strong><em><b><i><u><s><del><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span>';
        echo strip_tags((string) $item->description, $allowed);
    }
?></textarea></div>
  <div class="col-12"><label class="form-label">Steps to reproduce</label><textarea id="defect-steps" name="steps_to_reproduce" class="form-control" rows="6" placeholder="1. Go to…&#10;2. Click…&#10;3. See error…"><?php
    if ($item && !empty($item->steps_to_reproduce)) {
        $allowed = '<p><br><strong><em><b><i><u><s><del><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span>';
        echo strip_tags((string) $item->steps_to_reproduce, $allowed);
    }
?></textarea></div>
  <div class="col-12"><label class="form-label">Attachments (screenshots, logs)</label><input type="file" name="attachments[]" class="form-control" multiple accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.log,.zip"><div class="form-text">Max 5 MB each. JPG, PNG, PDF, TXT, LOG, ZIP.</div></div>
</div>
<div class="oms-form-actions"><button class="btn btn-primary">Save</button> <a class="btn btn-outline-secondary" href="<?php echo site_url($item ? 'defects/view/'.$item->id : 'defects'); ?>">Cancel</a></div>
</form></div></div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<style>
.oms-form-compact .tox-tinymce { border-radius: 6px !important; }
</style>
<script>
(function(){
  var defectEditorConfig = {
    menubar: 'edit view insert format',
    statusbar: true,
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
      'searchreplace', 'visualblocks', 'code', 'fullscreen',
      'insertdatetime', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link | code fullscreen',
    branding: false,
    width: '100%',
    convert_urls: false,
    default_link_target: '_blank',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
    formats: {
      bold: { inline: 'strong' },
      italic: { inline: 'em' },
      underline: { inline: 'u' },
      strikethrough: { inline: 'del' }
    }
  };

  tinymce.init(Object.assign({}, defectEditorConfig, {
    selector: '#defect-description',
    height: 220,
    placeholder: 'Describe the issue…'
  }));
  tinymce.init(Object.assign({}, defectEditorConfig, {
    selector: '#defect-steps',
    height: 260,
    placeholder: 'Numbered steps to reproduce the bug…'
  }));

  var form = document.getElementById('defectForm');
  if (form) {
    form.addEventListener('submit', function() {
      if (window.tinymce) {
        var desc = tinymce.get('defect-description');
        var steps = tinymce.get('defect-steps');
        if (desc) { desc.save(); }
        if (steps) { steps.save(); }
      }
    });
  }

  var projectSel = document.getElementById('defectProjectId');
  var releaseSel = document.getElementById('defectReleaseId');
  var taskSel = document.getElementById('defectTaskId');
  if (!projectSel || !releaseSel || !taskSel) return;
  var baseUrl = <?php echo json_encode(site_url('defects/ajax-options')); ?>;
  var currentRelease = releaseSel.value;
  var currentTask = taskSel.value;
  function fillSelect(sel, items, selected) {
    sel.innerHTML = '<option value="">—</option>';
    items.forEach(function(it) {
      var opt = document.createElement('option');
      opt.value = it.id;
      opt.textContent = it.label;
      if (String(it.id) === String(selected)) opt.selected = true;
      sel.appendChild(opt);
    });
  }
  function loadOptions() {
    var pid = projectSel.value;
    if (!pid) return;
    fetch(baseUrl + '/' + pid, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (!res || res.status !== 'success' || !res.data) return;
        fillSelect(releaseSel, res.data.releases || [], currentRelease);
        fillSelect(taskSel, res.data.tasks || [], currentTask);
        currentRelease = '';
        currentTask = '';
      });
  }
  projectSel.addEventListener('change', function() {
    currentRelease = '';
    currentTask = '';
    loadOptions();
  });
  if (projectSel.value) loadOptions();
})();
</script>
<?php $this->load->view('partials/footer'); ?>
