<?php $this->load->view('partials/header', ['title' => 'Departments']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Departments</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('departments?show_deleted=1'); ?>">Show Deleted</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('departments/create'); ?>">Create Department</a>
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
            <th style="width:180px"></th>
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
              <td><?php echo htmlspecialchars(isset($r->dept_code) ? $r->dept_code : ''); ?></td>
              <td><?php echo htmlspecialchars(isset($r->dept_name) ? $r->dept_name : ''); ?></td>
              <td><?php 
                if (!empty($r->manager_id) && isset($managers[(int)$r->manager_id])){ 
                  $m = $managers[(int)$r->manager_id]; echo htmlspecialchars($m->email.(!empty($m->name)?' ('.$m->name.')':''));
                } else { echo '<span class="text-muted">—</span>'; }
              ?></td>
              <td><span class="badge bg-<?php echo (isset($r->status) && $r->status === 'inactive') ? 'danger' : 'light'; ?> text-<?php echo (isset($r->status) && $r->status === 'inactive') ? 'light' : 'dark'; ?> border"><?php echo htmlspecialchars(isset($r->status) ? $r->status : 'active'); ?></span></td>
              <td><?php echo isset($r->deleted_at) ? '<span class="text-muted small">'.date('M j, Y H:i', strtotime($r->deleted_at)).'</span>' : '<span class="text-muted">—</span>'; ?></td>
              <td>
                <?php if (isset($r->status) && $r->status === 'inactive'): ?>
                  <a class="btn btn-outline-success btn-sm" onclick="return confirm('Restore this department?')" href="<?php echo site_url('departments/'.(int)$r->id.'/restore'); ?>">Restore</a>
                <?php else: ?>
                  <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('departments/'.(int)$r->id.'/edit'); ?>">Edit</a>
                  <a class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to remove this department? It will be marked as inactive and can be restored later.')" href="<?php echo site_url('departments/'.(int)$r->id.'/delete'); ?>">Remove</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
