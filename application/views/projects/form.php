<?php $embed = isset($embed) && $embed; ?>
<?php
  $header_data = ['title' => ($action === 'edit' ? 'Edit Project' : 'Create Project')];
  if ($embed) {
    // Hide navbar and sidebar when embedded in popup, but keep CSS/JS
    $header_data['hide_navbar'] = true;
    $header_data['with_sidebar'] = false;
    $header_data['full_width'] = false; // use container for nicer centering
  }
  $this->load->view('partials/header', $header_data);
?>
<div class="oms-form-compact">
<?php if (!$embed): ?>
<div class="oms-form-page-head d-flex justify-content-between align-items-center mb-2">
  <h1 class="h4 mb-0"><?php echo $action === 'edit' ? 'Edit Project' : 'Create Project'; ?></h1>
  <div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('projects'); ?>">Back to Projects</a>
  </div>
</div>
<?php endif; ?>

<?php /* Flash error is handled globally by the Bootstrap toast in partials/header.php */ ?>

<div class="card shadow-soft my-3">
  <div class="card-body">
    <?php
      if ($action === 'edit') {
        $form_action = site_url('projects/'.$project->id.'/edit');
      } else {
        $form_action = site_url('projects/create'.($embed ? '?embed=1' : ''));
      }
    ?>
    <form method="post" action="<?php echo $form_action; ?>" data-validate="true">
      <div class="row g-2 oms-form-grid">
        <div class="col-md-4">
          <label class="form-label">Code</label>
          <input type="text" name="code" class="form-control" value="<?php echo isset($project) ? esc_view($project->code ?? '') : ''; ?>" placeholder="PRJ-001">
        </div>
        <div class="col-md-4">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input required type="text" name="name" class="form-control" value="<?php echo isset($project) ? esc_view($project->name ?? '') : ''; ?>" placeholder="Website Redesign">
        </div>
        <?php if (function_exists('schema_table_has_column') && schema_table_has_column($this->db, 'projects', 'manager_id')): ?>
        <div class="col-md-4">
          <label class="form-label">Assigned To</label>
          <?php $cur_manager = (isset($project) && !empty($project->manager_id)) ? (int) $project->manager_id : 0; ?>
          <select name="manager_id" class="form-select">
            <option value="">-- Unassigned --</option>
            <?php if (!empty($users)) foreach ($users as $u): ?>
              <?php
                if (isset($u->emp_name) && trim((string) $u->emp_name) !== '') {
                  $label = trim((string) $u->emp_name);
                } else {
                  $label = !empty($u->full_name) ? $u->full_name : (!empty($u->name) ? $u->name : $u->email);
                }
                $label = trim($label);
                $label = $label ? $label . ' (' . $u->email . ')' : $u->email;
              ?>
              <option value="<?php echo (int) $u->id; ?>" <?php echo $cur_manager === (int) $u->id ? 'selected' : ''; ?>>
                <?php echo esc_view($label); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <?php if (!empty($project_types) && function_exists('schema_table_has_column') && schema_table_has_column($this->db, 'projects', 'project_type')): ?>
        <div class="col-md-4">
          <label class="form-label">Type</label>
          <?php
            $curProjectType = (isset($project) && isset($project->project_type)) ? (string) $project->project_type : '';
            $this->load->view('partials/module_type_select', array(
              'field_name' => 'project_type',
              'options' => $project_types,
              'current' => $curProjectType,
            ));
          ?>
        </div>
        <?php endif; ?>
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <?php $current_status = isset($project) ? (string)$project->status : 'planned'; ?>
          <select name="status" class="form-select">
            <?php 
            if (isset($statuses) && is_array($statuses) && !empty($statuses)): 
              foreach ($statuses as $st): 
                $selected = ($current_status === $st->code) ? 'selected' : '';
            ?>
              <option value="<?php echo esc_view($st->code); ?>" <?php echo $selected; ?>><?php echo esc_view($st->name); ?></option>
            <?php 
              endforeach; 
            else: 
              // Fallback to hardcoded statuses if database is not available
              $fallback_statuses = ['planned' => 'Planned', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
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
        <div class="col-md-4">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo isset($project) ? esc_view($project->start_date ?? '') : ''; ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo isset($project) ? esc_view($project->end_date ?? '') : ''; ?>">
          <div class="form-text text-danger" id="date-error" style="display: none;">
            <small>End date must be on or after start date</small>
          </div>
        </div>
        <div class="col-md-12">
          <label class="form-label"><i class="bi bi-link-45deg me-1"></i>URL / Link</label>
          <input type="url" name="reference_url" class="form-control" value="<?php echo isset($project) && !empty($project->reference_url) ? esc_view($project->reference_url) : ''; ?>" placeholder="https://example.com/docs">
          <div class="form-text">Optional. Paste any web link (e.g. spec doc, Figma, Jira).</div>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?php echo $action === 'edit' ? 'Save Changes' : 'Create Project'; ?></button>
        <?php if ($embed): ?>
          <button type="button" class="btn btn-light" onclick="if (window.parent && typeof window.parent.closeProjectModal === 'function') { window.parent.closeProjectModal(); } else { window.close && window.close(); }">Cancel</button>
        <?php else: ?>
          <a class="btn btn-light" href="<?php echo site_url('projects'); ?>">Cancel</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<script>
  // Date validation: End date must be >= Start date
  document.addEventListener('DOMContentLoaded', function() {
    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');
    var dateError = document.getElementById('date-error');
    var form = document.querySelector('form');
    
    function validateDates() {
      if (startDateInput.value && endDateInput.value) {
        var startDate = new Date(startDateInput.value);
        var endDate = new Date(endDateInput.value);
        
        if (endDate < startDate) {
          dateError.style.display = 'block';
          endDateInput.classList.add('is-invalid');
          return false;
        } else {
          dateError.style.display = 'none';
          endDateInput.classList.remove('is-invalid');
          return true;
        }
      }
      dateError.style.display = 'none';
      endDateInput.classList.remove('is-invalid');
      return true;
    }
    
    if (startDateInput) startDateInput.addEventListener('change', validateDates);
    if (endDateInput) endDateInput.addEventListener('change', validateDates);
    
    if (form) {
      form.addEventListener('submit', function(e) {
        if (!validateDates()) {
          e.preventDefault();
          return false;
        }
      });
    }
  });
</script>

</div>
<?php $this->load->view('partials/footer'); ?>
