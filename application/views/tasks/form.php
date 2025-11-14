<?php $this->load->view('partials/header', ['title' => ($action === 'edit' ? 'Edit Task' : 'Create Task')]); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo $action === 'edit' ? 'Edit Task' : 'Create Task'; ?></h1>
  <div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('tasks'); ?>">Back to Tasks</a>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" action="<?php echo $action === 'edit' ? site_url('tasks/'.$task->id.'/edit') : site_url('tasks/create'); ?>">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Project <span class="text-danger">*</span></label>
          <?php $curProj = isset($task) ? (int)$task->project_id : 0; ?>
          <select name="project_id" class="form-select" required>
            <option value="">-- Select project --</option>
            <?php if (!empty($projects)) foreach ($projects as $p): ?>
              <option value="<?php echo (int)$p->id; ?>" <?php echo $curProj===(int)$p->id?'selected':''; ?>>
                <?php echo htmlspecialchars($p->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if (isset($requirements) && is_array($requirements) && count($requirements) > 0): ?>
        <div class="col-md-8" id="requirement-container" style="display:none;">
          <label class="form-label">Requirement (optional)</label>
          <select name="requirement_id" class="form-select">
            <option value="">-- Select requirement --</option>
            <?php foreach ($requirements as $r): ?>
              <option value="<?php echo (int)$r->id; ?>" data-project-id="<?php echo isset($r->project_id)?(int)$r->project_id:0; ?>" data-title="<?php echo htmlspecialchars($r->title); ?>">
                <?php echo htmlspecialchars($r->title); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Selecting a requirement will set the task title to that requirement's title.</div>
        </div>
        <?php endif; ?>
        <?php if ($action === 'edit'): ?>
        <div class="col-md-8">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input required type="text" name="title" class="form-control" value="<?php echo isset($task) ? htmlspecialchars($task->title) : ''; ?>" placeholder="Design wireframes">
        </div>
        <?php else: ?>
          <input type="hidden" name="title" value="<?php echo isset($task) ? htmlspecialchars($task->title) : ''; ?>">
        <?php endif; ?>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea id="task-description" name="description" rows="6" class="form-control" placeholder="Details..."><?php echo isset($task) ? htmlspecialchars($task->description) : ''; ?></textarea>
          <div class="form-text">Use toolbar for bold, italic, lists, and links.</div>
        </div>
        <?php 
          // Show attachment input only if column exists to avoid DB issues
          $attachment_enabled = $this->db->field_exists('attachment_path', 'tasks');
        ?>
        <?php if($attachment_enabled): ?>
        <div class="col-12">
          <label class="form-label">Attachment (optional)</label>
          <?php if(isset($task) && !empty($task->attachment_path)): ?>
            <div class="mb-2">
              <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url($task->attachment_path); ?>" target="_blank"><i class="bi bi-paperclip"></i> Current file</a>
            </div>
          <?php endif; ?>
          <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
          <div class="form-text">Max 4MB. Allowed: JPG, PNG, PDF, DOC, DOCX</div>
        </div>
        <?php endif; ?>
        <div class="col-md-4">
          <label class="form-label">Assign To</label>
          <?php $curUser = isset($task) && $task->assigned_to !== null ? (int)$task->assigned_to : 0; ?>
          <select name="assigned_to" class="form-select">
            <option value="">-- Unassigned --</option>
            <?php if (!empty($users)) foreach ($users as $u): ?>
              <?php 
                // Prefer employee name if available
                if (isset($u->emp_name) && trim((string)$u->emp_name) !== '') {
                  $label = trim((string)$u->emp_name);
                } else {
                  $label = !empty($u->full_name) ? $u->full_name : (!empty($u->name) ? $u->name : $u->email);
                }
                $label = trim($label);
                $label = $label ? $label.' ('.$u->email.')' : $u->email;
              ?>
              <option value="<?php echo (int)$u->id; ?>" <?php echo $curUser===(int)$u->id?'selected':''; ?>>
                <?php echo htmlspecialchars($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <?php $st = isset($task) ? (string)$task->status : 'pending'; ?>
          <select name="status" class="form-select">
            <option value="pending" <?php echo $st==='pending'?'selected':''; ?>>Pending</option>
            <option value="in_progress" <?php echo $st==='in_progress'?'selected':''; ?>>In Progress</option>
            <option value="completed" <?php echo $st==='completed'?'selected':''; ?>>Completed</option>
            <option value="blocked" <?php echo $st==='blocked'?'selected':''; ?>>Blocked</option>
          </select>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?php echo $action === 'edit' ? 'Save Changes' : 'Create Task'; ?></button>
        <a class="btn btn-light" href="<?php echo site_url('tasks'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  tinymce.init({
    selector: '#task-description',
    menubar: false,
    statusbar: false,
    plugins: 'lists link autoresize',
    toolbar: 'bold italic | bullist numlist | link undo redo',
    branding: false,
    height: 260,
    convert_urls: false,
    default_link_target: '_blank'
  });
  // Ensure content sync on submit
  document.querySelector('form').addEventListener('submit', function(){
    if (tinymce.get('task-description')) tinymce.get('task-description').save();
  });
  // Requirement filtering and title autofill
  (function(){
    var projSel = document.querySelector('select[name="project_id"]');
    var reqSel = document.querySelector('select[name="requirement_id"]');
    var reqContainer = document.getElementById('requirement-container');
    var titleInput = document.querySelector('input[name="title"]');
    if (!projSel || !reqSel) return;
    function filterRequirements(){
      var pid = parseInt(projSel.value || '0', 10);
      var hasSelection = false;
      // Toggle container visibility
      if (reqContainer) { reqContainer.style.display = pid ? '' : 'none'; }
      Array.prototype.forEach.call(reqSel.options, function(opt, idx){
        if (idx === 0) { opt.hidden = false; return; }
        var opid = parseInt(opt.getAttribute('data-project-id') || '0', 10);
        var show = !pid || pid === opid;
        opt.hidden = !show;
        if (!show && opt.selected) { hasSelection = true; }
      });
      if (hasSelection) { reqSel.selectedIndex = 0; }
    }
    function applyRequirementTitle(){
      var opt = reqSel.options[reqSel.selectedIndex];
      if (opt && opt.value){
        var t = opt.getAttribute('data-title') || '';
        if (t && titleInput) { titleInput.value = t; }
      }
    }
    projSel.addEventListener('change', filterRequirements);
    reqSel.addEventListener('change', applyRequirementTitle);
    // Initial filter on load to respect preselected project
    filterRequirements();
  })();
</script>
<?php $this->load->view('partials/footer'); ?>
