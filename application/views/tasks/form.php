<?php $this->load->view('partials/header', ['title' => ($action === 'edit' ? 'Edit Task' : 'Create Task')]); ?>
<div class="oms-form-compact">
<div class="oms-form-page-head d-flex justify-content-between align-items-center mb-2">
  <h1 class="h5 mb-0 fw-semibold"><?php echo $action === 'edit' ? 'Edit Task' : 'Create Task'; ?></h1>
  <div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('tasks'); ?>">Back to Tasks</a>
  </div>
</div>

<!-- Project Create Modal -->
<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="projectModalFrame" src="" style="border:0;width:100%;height:500px;"></iframe>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-soft oms-form-card">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" action="<?php echo $action === 'edit' ? site_url('tasks/'.$task->id.'/edit') : site_url('tasks/create'); ?>" data-validate="true">
      <div class="row g-2 oms-form-grid">
         <div class="col-md-4">
           <label class="form-label">
             <i class="bi bi-folder me-1"></i>Projects <span class="text-danger">*</span>
             <small class="text-muted">(Select one or more projects)</small>
           </label>
           <div class="d-flex align-items-start gap-2">
             <div class="flex-grow-1">
               <?php 
               $curProj = isset($task) ? (int)$task->project_id : 0;
               $curProjIds = [];
               if (isset($task) && isset($task->project_ids) && $task->project_ids) {
                   $decoded = json_decode($task->project_ids, true);
                   $curProjIds = $decoded ? $decoded : explode(',', $task->project_ids);
                   $curProjIds = array_map('intval', array_filter($curProjIds));
               } elseif ($curProj > 0) {
                   $curProjIds = [$curProj];
               }
               ?>
               <select name="project_ids[]" id="project-select" class="form-select" multiple required style="width: 100%;">
                 <?php if (!empty($projects)): ?>
                   <?php foreach ($projects as $p): ?>
                     <option value="<?php echo (int)$p->id; ?>" <?php echo in_array((int)$p->id, $curProjIds) ? 'selected' : ''; ?>>
                       <?php echo esc_view($p->name ?: 'Project #' . $p->id); ?>
                       <?php if (isset($p->code) && $p->code): ?> (<?php echo esc_view($p->code); ?>)<?php endif; ?>
                     </option>
                   <?php endforeach; ?>
                 <?php endif; ?>
               </select>
               <input type="hidden" name="project_id" id="project_id_hidden" value="<?php echo !empty($curProjIds) ? $curProjIds[0] : 0; ?>">
             </div>
             <button type="button" class="btn btn-outline-primary btn-sm" id="btnAddProject" title="Add New Project" style="flex-shrink: 0; margin-top: 0;">
               <i class="bi bi-plus-lg"></i>
             </button>
           </div>
           <div class="form-text mt-2">
             <span id="selected-projects-count"><?php echo count($curProjIds); ?></span> project(s) selected
           </div>
         </div>
        <div class="col-md-4">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input required type="text" name="title" class="form-control" value="<?php echo isset($task) ? esc_view($task->title) : ''; ?>" placeholder="Enter task title" id="task-title-input">
        </div>
        <?php if (isset($requirements) && is_array($requirements) && count($requirements) > 0): ?>
        <div class="col-md-4" id="requirement-container">
          <label class="form-label">
            <i class="bi bi-link-45deg me-1"></i>Link to Requirement (optional)
          </label>
          <?php 
          $curReq = 0;
          if (isset($task) && isset($task->requirement_id)) {
              $curReq = (int)$task->requirement_id;
          } elseif (isset($preselected_requirement)) {
              $curReq = (int)$preselected_requirement;
          }
          ?>
          <select name="requirement_id" class="form-select" id="requirement-select">
            <option value="">-- No requirement linked --</option>
            <?php foreach ($requirements as $r): ?>
              <option value="<?php echo (int)$r->id; ?>" 
                      data-project-id="<?php echo isset($r->project_id)?(int)$r->project_id:0; ?>" 
                      data-title="<?php echo esc_view($r->title); ?>"
                      <?php echo $curReq===(int)$r->id?'selected':''; ?>>
                <?php echo esc_view($r->title); ?> 
                <?php if (isset($r->req_number)): ?>(<?php echo esc_view($r->req_number); ?>)<?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">
            <i class="bi bi-info-circle me-1"></i>
            Linking a requirement will auto-fill the title if empty. You can still edit it.
          </div>
        </div>
        <?php endif; ?>
        <?php 
          // Show attachment input only if column exists to avoid DB issues
          $attachment_enabled = schema_table_has_column($this->db, 'tasks', 'attachment_path');
        ?>
        <div class="col-md-6">
          <label class="form-label">Assign To</label>
          <?php
            $curUsers = array();
            if (isset($assigned_user_ids) && is_array($assigned_user_ids)) {
                $curUsers = array_map('intval', $assigned_user_ids);
            } elseif (isset($task) && $task->assigned_to !== null) {
                $curUsers = array((int) $task->assigned_to);
            }
          ?>
          <select name="assigned_to[]" id="assigned-to-select" class="form-select oms-select2-multi" multiple style="width: 100%;">
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
              <option value="<?php echo (int)$u->id; ?>" <?php echo in_array((int)$u->id, $curUsers, true) ? 'selected' : ''; ?>>
                <?php echo esc_view($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Type to search. Tags = selected users (× to remove). First selected is primary.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">
            <i class="bi bi-flag me-1"></i>Priority
          </label>
          <?php $pr = isset($task) && isset($task->priority) ? (string)$task->priority : 'medium'; ?>
          <select name="priority" class="form-select">
            <option value="low" <?php echo $pr==='low'?'selected':''; ?>>🔵 Low</option>
            <option value="medium" <?php echo $pr==='medium'?'selected':''; ?>>🟡 Medium</option>
            <option value="high" <?php echo $pr==='high'?'selected':''; ?>>🟠 High</option>
            <option value="urgent" <?php echo $pr==='urgent'?'selected':''; ?>>🔴 Urgent</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">
            <i class="bi bi-arrow-right-circle me-1"></i>Status
          </label>
          <?php $current_status = isset($task) ? (string)$task->status : 'pending'; ?>
          <select name="status" class="form-select">
            <?php 
            if (isset($statuses) && is_array($statuses) && !empty($statuses)): 
              foreach ($statuses as $st): 
                $selected = ($current_status === $st->code) ? 'selected' : '';
                $icon = $st->icon ? '<i class="bi bi-'.$st->icon.'"></i> ' : '';
            ?>
              <option value="<?php echo esc_view($st->code); ?>" <?php echo $selected; ?>><?php echo $icon.esc_view($st->name); ?></option>
            <?php 
              endforeach; 
            else: 
              // Fallback to hardcoded statuses if database is not available
              $fallback_statuses = ['pending' => '⏳ Pending', 'in_progress' => '🔄 In Progress', 'completed' => '✅ Completed', 'blocked' => '🚫 Blocked'];
              foreach ($fallback_statuses as $code => $name):
                $selected = ($current_status === $code) ? 'selected' : '';
            ?>
              <option value="<?php echo esc_view($code); ?>" <?php echo $selected; ?>><?php echo esc_view($name); ?></option>
            <?php 
              endforeach; 
            endif; 
            ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">
            <i class="bi bi-calendar-event me-1"></i>Start Date
          </label>
          <input type="date" name="start_date" class="form-control" value="<?php echo isset($task) && isset($task->start_date) && $task->start_date ? date('Y-m-d', strtotime($task->start_date)) : ''; ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">
            <i class="bi bi-calendar-check me-1"></i>Due Date
          </label>
          <input type="date" name="due_date" class="form-control" value="<?php echo isset($task) && isset($task->due_date) && $task->due_date ? date('Y-m-d', strtotime($task->due_date)) : ''; ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">
            <i class="bi bi-hourglass-split me-1"></i>Estimate (hrs)
          </label>
          <input type="number" name="estimate_hours" class="form-control" min="0" max="9999.99" step="0.25"
                 value="<?php echo isset($task) && isset($task->estimate_hours) && $task->estimate_hours !== null && $task->estimate_hours !== '' ? esc_view(estimate_hours_display($task->estimate_hours)) : ''; ?>"
                 placeholder="e.g. 2.5">
        </div>
        <div class="col-md-6">
          <label class="form-label"><i class="bi bi-link-45deg me-1"></i>URL / Link</label>
          <input type="url" name="reference_url" class="form-control" value="<?php echo isset($task) && !empty($task->reference_url) ? esc_view($task->reference_url) : ''; ?>" placeholder="https://example.com/task-details">
        </div>
        <?php if($attachment_enabled): ?>
        <div class="col-md-12">
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
        <div class="col-12">
          <label class="form-label">
            <i class="bi bi-file-text me-1"></i>Description
          </label>
          <textarea id="task-description" name="description" rows="6" class="form-control" placeholder="Enter task description..."><?php echo isset($task) ? esc_view($task->description) : ''; ?></textarea>
        </div>
      </div>
      <div class="oms-form-actions">
        <button class="btn btn-primary" type="submit"><?php echo $action === 'edit' ? 'Save Changes' : 'Create Task'; ?></button>
        <a class="btn btn-light" href="<?php echo site_url('tasks'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

 <!-- Select2 chip multi-select (projects + assignees) -->
 <?php $this->load->view('partials/oms_select2_multi', array('oms_select2_selectors' => array())); ?>
 
 <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
 <style>
 .oms-form-compact .tox-tinymce {
   border-radius: 6px !important;
 }
 </style>
 <script>
   $(document).ready(function() {
     if (window.omsInitSelect2Multi) {
       window.omsInitSelect2Multi('#project-select', { placeholder: 'Select one or more projects...', allowClear: true });
       window.omsInitSelect2Multi('#assigned-to-select', { placeholder: 'Select assignee(s)...', allowClear: false });
     }
     
     // Update selected projects count
     function updateProjectCount() {
       const selected = $('#project-select').val();
       const count = selected ? selected.length : 0;
       $('#selected-projects-count').text(count);
       
       // Update hidden field with first selected project for backward compatibility
       const hiddenField = $('#project_id_hidden');
       if (hiddenField.length && selected && selected.length > 0) {
         hiddenField.val(selected[0]);
       } else {
         hiddenField.val('0');
       }
     }
     
     // Listen to Select2 change events
     $('#project-select').on('select2:select select2:unselect select2:clear', function() {
       updateProjectCount();
     });
     
     // Initial count
     updateProjectCount();

  // Enhanced TinyMCE with full formatting options like MS Office
  tinymce.init({
    selector: '#task-description',
    menubar: 'edit view insert format tools',
    statusbar: true,
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
      'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
      'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount',
      'textcolor', 'colorpicker', 'fontselect', 'fontsizeselect'
    ],
    toolbar: 'undo redo | formatselect | ' +
      'bold italic underline strikethrough | forecolor backcolor | ' +
      'alignleft aligncenter alignright alignjustify | ' +
      'bullist numlist outdent indent | ' +
      'removeformat | link image | code | fullscreen | help',
    branding: false,
    height: 280,
    width: '100%',
    convert_urls: false,
    default_link_target: '_blank',
    font_formats: 'Arial=arial,helvetica,sans-serif; Courier New=courier new,courier; Georgia=georgia,palatino; Helvetica=helvetica; Impact=impact,chicago; Tahoma=tahoma,arial,helvetica,sans-serif; Times New Roman=times new roman,times; Trebuchet MS=trebuchet ms,geneva; Verdana=verdana,geneva',
    fontsize_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt',
    formats: {
      bold: { inline: 'strong', classes: 'fw-bold' },
      italic: { inline: 'em', classes: 'fst-italic' },
      underline: { inline: 'u', classes: 'text-decoration-underline' },
      strikethrough: { inline: 'del' }
    },
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
    setup: function(editor) {
      editor.on('init', function() {
        // Set default font
        editor.execCommand('FontName', false, 'Arial');
        editor.execCommand('FontSize', false, '14pt');
      });
    }
  });
  // Ensure content sync on submit
  document.querySelector('form').addEventListener('submit', function(){
    if (tinymce.get('task-description')) tinymce.get('task-description').save();
  });
    // Requirement filtering and title autofill
    window.filterRequirements = function(){
      var reqSel = $('#requirement-select'); // Regular select
      var reqContainer = $('#requirement-container');
      
      if (!reqSel.length) return;
      
      // Show all requirements (no project filtering)
      reqSel.find('option').each(function(idx, opt) {
        $(opt).prop('hidden', false);
      });
      
      // Always show requirement container
      reqContainer.show();
    };
    
    function applyRequirementTitle(){
      var reqSel = $('#requirement-select');
      var titleInput = $('#task-title-input');
      var selectedOption = reqSel.find('option:selected');
      if (selectedOption.length && selectedOption.val()){
        var t = selectedOption.data('title') || '';
        if (t && titleInput.length && !titleInput.val().trim()) { 
          titleInput.val(t); 
          // Show a brief notification
          var notif = $('<div class="alert alert-info alert-dismissible fade show mt-2"><small>Title auto-filled from requirement. You can edit it.</small><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>');
          $('#requirement-container').append(notif);
          setTimeout(function(){ notif.fadeOut(function(){ $(this).remove(); }); }, 3000);
        }
      }
    }
    
    // Listen to requirement changes
    $('#requirement-select').on('change', applyRequirementTitle);
    
    // Initial filter on load to respect preselected project
    window.filterRequirements();
    // If requirement is preselected, apply its title
    if ($('#requirement-select').val()) {
        applyRequirementTitle();
    }
  });

  // Project Modal Functions
  function closeProjectModal(){
    var frame = document.getElementById('projectModalFrame');
    if (frame){ frame.src = 'about:blank'; }
    var modalEl = document.getElementById('projectModal');
    if (modalEl){
      if (window.bootstrap && window.bootstrap.Modal){
        var m = window.bootstrap.Modal.getOrCreateInstance(modalEl);
        m.hide();
      } else {
        modalEl.style.display = 'none';
      }
    }
  }
  window.closeProjectModal = closeProjectModal;

   // Called from embedded project create popup when a project is created
   window.onProjectCreated = function(id, name){
     var $select = $('#project-select');
     if (!$select.length) return;
     var val = String(id || '');
     if (!val) return;
     
     // Check if option exists
     var existingOption = $select.find('option[value="' + val + '"]');
     if (!existingOption.length) {
       // Add new option
       var newOption = new Option(name || ('Project #' + val), val, true, true);
       $select.append(newOption).trigger('change');
     } else {
       // Select existing option if not already selected
       var currentVal = $select.val() || [];
       if (currentVal.indexOf(val) === -1) {
         currentVal.push(val);
         $select.val(currentVal).trigger('change');
       }
     }
     
     // Update count
     if (typeof updateProjectCount === 'function') {
       updateProjectCount();
     }
     
     closeProjectModal();
   };

  // Add Project Button Click Handler
  (function(){
    var btn = document.getElementById('btnAddProject');
    if (!btn) return;
    btn.addEventListener('click', function(ev){
      ev.preventDefault();
      var frame = document.getElementById('projectModalFrame');
      if (frame) {
        frame.src = '<?php echo site_url('projects/create'); ?>?embed=1';
      }
      var modalEl = document.getElementById('projectModal');
      if (modalEl) {
        if (window.bootstrap && window.bootstrap.Modal) {
          var m = window.bootstrap.Modal.getOrCreateInstance(modalEl);
          m.show();
        } else {
          modalEl.style.display = 'block';
        }
      }
    });
  })();

</script>
</div>
<?php $this->load->view('partials/footer'); ?>
