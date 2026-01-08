<?php $this->load->view('partials/header', ['title' => ($action === 'edit' ? 'Edit Task' : 'Create Task')]); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo $action === 'edit' ? 'Edit Task' : 'Create Task'; ?></h1>
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

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" enctype="multipart/form-data" action="<?php echo $action === 'edit' ? site_url('tasks/'.$task->id.'/edit') : site_url('tasks/create'); ?>" data-validate="true">
      <div class="row g-3">
         <div class="col-md-12">
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
                       <?php echo htmlspecialchars($p->name ?: 'Project #' . $p->id); ?>
                       <?php if (isset($p->code) && $p->code): ?> (<?php echo htmlspecialchars($p->code); ?>)<?php endif; ?>
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
        <div class="col-md-8">
          <label class="form-label">Title <span class="text-danger">*</span></label>
          <input required type="text" name="title" class="form-control" value="<?php echo isset($task) ? htmlspecialchars($task->title) : ''; ?>" placeholder="Enter task title" id="task-title-input">
        </div>
        <?php if (isset($requirements) && is_array($requirements) && count($requirements) > 0): ?>
        <div class="col-md-12" id="requirement-container">
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
                      data-title="<?php echo htmlspecialchars($r->title); ?>"
                      <?php echo $curReq===(int)$r->id?'selected':''; ?>>
                <?php echo htmlspecialchars($r->title); ?> 
                <?php if (isset($r->req_number)): ?>(<?php echo htmlspecialchars($r->req_number); ?>)<?php endif; ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">
            <i class="bi bi-info-circle me-1"></i>
            Linking a requirement will auto-fill the title if empty. You can still edit it.
          </div>
        </div>
        <?php endif; ?>
        <div class="col-12">
          <label class="form-label">
            <i class="bi bi-file-text me-1"></i>Description
          </label>
          <textarea id="task-description" name="description" rows="10" class="form-control" placeholder="Enter task description..."><?php echo isset($task) ? htmlspecialchars($task->description) : ''; ?></textarea>
          <div class="form-text">
            <i class="bi bi-info-circle me-1"></i>
            Use the toolbar above to format text: <strong>Bold</strong>, <em>Italic</em>, <u>Underline</u>, colors, fonts, and more.
          </div>
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
        <div class="col-md-4">
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
              <option value="<?php echo htmlspecialchars($st->code); ?>" <?php echo $selected; ?>><?php echo $icon.htmlspecialchars($st->name); ?></option>
            <?php 
              endforeach; 
            else: 
              // Fallback to hardcoded statuses if database is not available
              $fallback_statuses = ['pending' => '⏳ Pending', 'in_progress' => '🔄 In Progress', 'completed' => '✅ Completed', 'blocked' => '🚫 Blocked'];
              foreach ($fallback_statuses as $code => $name):
                $selected = ($current_status === $code) ? 'selected' : '';
            ?>
              <option value="<?php echo htmlspecialchars($code); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($name); ?></option>
            <?php 
              endforeach; 
            endif; 
            ?>
          </select>
        </div>
        <div class="col-md-6">
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
      </div>
      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?php echo $action === 'edit' ? 'Save Changes' : 'Create Task'; ?></button>
        <a class="btn btn-light" href="<?php echo site_url('tasks'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

 <!-- Select2 CSS and JS for multi-select dropdown -->
 <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
 <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
 <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
 
 <script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
 <script>
   $(document).ready(function() {
     // Initialize Select2 for project multi-select
     $('#project-select').select2({
       theme: 'bootstrap-5',
       placeholder: 'Select one or more projects...',
       allowClear: true,
       width: '100%',
       closeOnSelect: false,
       tags: false,
       multiple: true,
       matcher: function(params, data) {
         // Check if this item is selected - only filter it out if it's actually selected
         if (data.element) {
           var $option = $(data.element);
           // Only hide if the option element exists and is actually selected
           if ($option.length > 0 && $option.prop('selected') === true) {
             return null; // Hide selected items completely from dropdown
           }
         }
         // If no search term, show all unselected items
         if (!params.term || params.term.trim() === '') {
           return data;
         }
         // If searching, do normal search matching on unselected items only
         var term = params.term.toLowerCase();
         if (data.text && typeof data.text === 'string' && data.text.toLowerCase().indexOf(term) > -1) {
           return data;
         }
         return null;
       },
       templateResult: function(data) {
         // Hide selected items from dropdown
         if (!data.id) {
           return data.text; // Loading message or no results
         }
         // Check if this item is selected
         if (data.element) {
           var $option = $(data.element);
           // Only hide if the option element exists and is actually selected
           if ($option.length > 0 && $option.prop('selected') === true) {
             return null; // Return null to completely hide selected items from dropdown
           }
         }
         return data.text; // Show unselected items
       }
     });
     
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
    height: 400,
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
<?php $this->load->view('partials/footer'); ?>
