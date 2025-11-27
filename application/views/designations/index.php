<?php $this->load->view('partials/header', ['title' => 'Designations']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Designations</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('designations?show_deleted=1'); ?>">Show Deleted</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('designations/create'); ?>">Create Designation</a>
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
            <th style="width:180px"></th>
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
                <?php if (isset($r->status) && $r->status === 'inactive'): ?>
                  <a class="btn btn-outline-success btn-sm" onclick="return confirm('Restore this designation?')" href="<?php echo site_url('designations/'.(int)$r->id.'/restore'); ?>">Restore</a>
                <?php else: ?>
                  <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('designations/'.(int)$r->id.'/edit'); ?>">Edit</a>
                  <a class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to remove this designation? It will be marked as inactive and can be restored later.')" href="<?php echo site_url('designations/'.(int)$r->id.'/delete'); ?>">Remove</a>
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

