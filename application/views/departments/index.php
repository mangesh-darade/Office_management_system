<?php $this->load->view('partials/header', ['title' => 'Departments']); ?>
<div class="container-fluid py-3">
<?php ob_start();
if(function_exists('has_module_access') && (has_module_access('departments') || is_admin_group())): ?>
<a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('departments?show_deleted=1'); ?>">Deleted</a>
<a class="btn btn-primary btn-sm" href="<?php echo site_url('departments/create'); ?>"><i class="bi bi-plus-lg me-1"></i>Create</a>
<?php endif;
$this->load->view('partials/oms_page_head', [
  'title' => 'Departments',
  'subtitle' => 'Organize teams and reporting structure',
  'icon' => 'bi-diagram-3',
  'actions_html' => ob_get_clean(),
]); ?>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<?php if ($this->input->get('show_deleted')): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-2"></i>
  Showing deleted departments. You can restore them if needed.
</div>
<?php endif; ?>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Manager</th>
            <th>Status</th>
            <th>Deleted At</th>
            <th style="width:120px" class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $show_deleted = $this->input->get('show_deleted');
          $rows = $show_deleted ? $this->departments->deleted_only() : $this->departments->all();
          if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-muted">No departments found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="<?php echo (isset($r->status) && $r->status === 'inactive') ? 'table-warning' : ''; ?>">
              <td><?php echo esc_view(isset($r->dept_code) ? $r->dept_code : ''); ?></td>
              <td><?php echo esc_view(isset($r->dept_name) ? $r->dept_name : ''); ?></td>
              <td><?php 
                if (!empty($r->manager_id) && isset($managers[(int)$r->manager_id])){ 
                  $m = $managers[(int)$r->manager_id]; echo esc_view($m->email.(!empty($m->name)?' ('.$m->name.')':''));
                } else { echo '<span class="text-muted">—</span>'; }
              ?></td>
              <td><span class="badge bg-<?php echo (isset($r->status) && $r->status === 'inactive') ? 'danger' : 'light'; ?> text-<?php echo (isset($r->status) && $r->status === 'inactive') ? 'light' : 'dark'; ?> border"><?php echo esc_view(isset($r->status) ? $r->status : 'active'); ?></span></td>
              <td><?php echo isset($r->deleted_at) ? '<span class="text-muted small">'.date('M j, Y H:i', strtotime($r->deleted_at)).'</span>' : '<span class="text-muted">—</span>'; ?></td>
              <td>
                <div class="d-flex justify-content-center gap-1">
                <?php if(function_exists('has_module_access') && (has_module_access('departments') || is_admin_group())): ?>
                <?php if (isset($r->status) && $r->status === 'inactive'): ?>
                    <a class="btn btn-sm btn-outline-success btn-icon" 
                       onclick="return confirm('Restore this department?')" 
                       href="<?php echo site_url('departments/'.(int)$r->id.'/restore'); ?>"
                       data-bs-toggle="tooltip" title="Restore Department">
                      <i class="bi bi-arrow-clockwise"></i>
                    </a>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-primary btn-icon" 
                       href="<?php echo site_url('departments/'.(int)$r->id.'/edit'); ?>"
                       data-bs-toggle="tooltip" title="Edit Department">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="post" action="<?php echo site_url('departments/'.(int)$r->id.'/delete'); ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this department? It will be marked as inactive and can be restored later.');">
                      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" data-bs-toggle="tooltip" title="Remove Department">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                <?php endif; ?>
                <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<style>
.btn-icon {
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.btn-icon:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize tooltips
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });
});
</script>

</div>
<?php $this->load->view('partials/footer'); ?>
