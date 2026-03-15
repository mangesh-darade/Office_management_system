<?php $this->load->view('partials/header', ['title' => 'Designations']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Designations</h1>
  <div class="d-flex gap-2">
    <?php if(function_exists('has_module_access') && (has_module_access('designations') || (function_exists('is_admin_group') && is_admin_group()))): ?>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('designations?show_deleted=1'); ?>">Show Deleted</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('designations/create'); ?>">Create Designation</a>
    <?php endif; ?>
  </div>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<?php if ($this->input->get('show_deleted')): ?>
<div class="alert alert-info">
  <i class="bi bi-info-circle me-2"></i>
  Showing deleted designations. You can restore them if needed.
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
            <th>Department</th>
            <th>Level</th>
            <th>Status</th>
            <th>Deleted At</th>
            <th style="width:120px" class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $show_deleted = $this->input->get('show_deleted');
          $rows = $show_deleted ? $this->designations->deleted_only() : $this->designations->all();
          if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted">No designations found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="<?php echo (isset($r->status) && $r->status === 'inactive') ? 'table-warning' : ''; ?>">
              <td><?php echo htmlspecialchars(isset($r->designation_code) ? $r->designation_code : ''); ?></td>
              <td><?php echo htmlspecialchars(isset($r->designation_name) ? $r->designation_name : ''); ?></td>
              <td><?php 
                $deptId = (isset($r->department_id) ? (int)$r->department_id : 0);
                echo htmlspecialchars(isset($deptMap[$deptId]) ? $deptMap[$deptId] : '—'); 
              ?></td>
              <td><?php echo htmlspecialchars((string)(isset($r->level) ? $r->level : 1)); ?></td>
              <td><span class="badge bg-<?php echo (isset($r->status) && $r->status === 'inactive') ? 'danger' : 'light'; ?> text-<?php echo (isset($r->status) && $r->status === 'inactive') ? 'light' : 'dark'; ?> border"><?php echo htmlspecialchars(isset($r->status) ? $r->status : 'active'); ?></span></td>
              <td><?php echo isset($r->deleted_at) ? '<span class="text-muted small">'.date('M j, Y H:i', strtotime($r->deleted_at)).'</span>' : '<span class="text-muted">—</span>'; ?></td>
              <td>
                <div class="d-flex justify-content-center gap-1">
                <?php if(function_exists('has_module_access') && (has_module_access('designations') || (function_exists('is_admin_group') && is_admin_group()))): ?>
                <?php if (isset($r->status) && $r->status === 'inactive'): ?>
                    <a class="btn btn-sm btn-outline-success btn-icon" 
                       onclick="return confirm('Restore this designation?')" 
                       href="<?php echo site_url('designations/'.(int)$r->id.'/restore'); ?>"
                       data-bs-toggle="tooltip" title="Restore Designation">
                      <i class="bi bi-arrow-clockwise"></i>
                    </a>
                <?php else: ?>
                    <a class="btn btn-sm btn-outline-primary btn-icon" 
                       href="<?php echo site_url('designations/'.(int)$r->id.'/edit'); ?>"
                       data-bs-toggle="tooltip" title="Edit Designation">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <form method="post" action="<?php echo site_url('designations/'.(int)$r->id.'/delete'); ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this designation? It will be marked as inactive and can be restored later.');">
                      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" data-bs-toggle="tooltip" title="Remove Designation">
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

<?php $this->load->view('partials/footer'); ?>

