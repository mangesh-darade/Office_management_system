<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$pid = (int) $project->id;
$project_name_hint = esc_view($project->name);
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();

$import_modals = array();

if (function_exists('has_module_access') && (has_module_access('tasks_import') || has_module_access('tasks'))) {
    $import_modals[] = array(
        'id' => 'projectImportTasksModal',
        'title' => 'Import Tasks',
        'icon' => 'bi-list-check',
        'action' => site_url('tasks/import'),
        'return_to' => 'projects/' . $pid . '?tab=tasks',
        'sample' => base_url('assets/samples/tasks_import_sample.csv'),
        'columns' => 'project_name, title, description, assigned_to, status',
        'hint' => 'Use <strong>project_name</strong>: ' . $project_name_hint,
    );
}

if (function_exists('has_module_access') && (has_module_access('requirements_add') || has_module_access('requirements'))) {
    $import_modals[] = array(
        'id' => 'projectImportRequirementsModal',
        'title' => 'Import Requirements',
        'icon' => 'bi-clipboard-check',
        'action' => site_url('requirements/import'),
        'return_to' => 'projects/' . $pid . '?tab=requirements',
        'sample' => base_url('assets/samples/requirements_import_sample.csv'),
        'columns' => 'client_name, title, project_name, description, priority, status, requirement_type, received_date, expected_delivery_date',
        'hint' => 'Set <strong>project_name</strong> to ' . $project_name_hint . ' for this project.',
    );
}

if (function_exists('has_module_access') && (has_module_access('defects_add') || has_module_access('defects'))) {
    $import_modals[] = array(
        'id' => 'projectImportDefectsModal',
        'title' => 'Import Defects',
        'icon' => 'bi-bug',
        'action' => site_url('defects/import'),
        'return_to' => 'projects/' . $pid . '?tab=defects',
        'sample' => base_url('assets/samples/defects_import_sample.csv'),
        'columns' => 'project_name, title, description, steps_to_reproduce, severity, priority, status, assigned_to, due_date',
        'hint' => 'Use <strong>project_name</strong>: ' . $project_name_hint,
    );
}

if (function_exists('has_module_access') && (has_module_access('releases_add') || has_module_access('releases'))) {
    $import_modals[] = array(
        'id' => 'projectImportReleasesModal',
        'title' => 'Import Releases',
        'icon' => 'bi-rocket-takeoff',
        'action' => site_url('releases/import'),
        'return_to' => 'projects/' . $pid . '?tab=releases',
        'sample' => base_url('assets/samples/releases_import_sample.csv'),
        'columns' => 'project_name, version, title, description, status, planned_date',
        'hint' => 'Use <strong>project_name</strong>: ' . $project_name_hint,
    );
}
?>
<?php foreach ($import_modals as $modal): ?>
<div class="modal fade" id="<?php echo esc_view($modal['id']); ?>" tabindex="-1" aria-labelledby="<?php echo esc_view($modal['id']); ?>Label" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data" action="<?php echo esc_view($modal['action']); ?>">
        <div class="modal-header py-2">
          <h5 class="modal-title h6" id="<?php echo esc_view($modal['id']); ?>Label">
            <i class="bi <?php echo esc_view($modal['icon']); ?> me-1"></i><?php echo esc_view($modal['title']); ?>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-2">CSV columns: <code><?php echo esc_view($modal['columns']); ?></code></p>
          <p class="small mb-3"><?php echo $modal['hint']; ?></p>
          <a class="btn btn-outline-secondary btn-sm mb-3" href="<?php echo esc_view($modal['sample']); ?>" download>
            <i class="bi bi-download me-1"></i>Download sample CSV
          </a>
          <input type="hidden" name="<?php echo esc_view($csrf_name); ?>" value="<?php echo esc_view($csrf_hash); ?>">
          <input type="hidden" name="return_to" value="<?php echo esc_view($modal['return_to']); ?>">
          <label class="form-label small">Choose CSV file</label>
          <input type="file" name="file" accept=".csv" class="form-control form-control-sm" required>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i>Upload &amp; Import</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>
