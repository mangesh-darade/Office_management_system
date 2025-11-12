<?php $this->load->view('partials/header', ['title' => 'Designations']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Designations</h1>
  <a class="btn btn-primary btn-sm" href="<?php echo site_url('designations/create'); ?>">Create Designation</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
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
            <th style="width:180px"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="text-center text-muted">No designations found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars(isset($r->designation_code) ? $r->designation_code : ''); ?></td>
              <td><?php echo htmlspecialchars(isset($r->designation_name) ? $r->designation_name : ''); ?></td>
              <td><?php 
                $deptId = (isset($r->department_id) ? (int)$r->department_id : 0);
                echo htmlspecialchars(isset($deptMap[$deptId]) ? $deptMap[$deptId] : '—'); 
              ?></td>
              <td><?php echo htmlspecialchars((string)(isset($r->level) ? $r->level : 1)); ?></td>
              <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(isset($r->status) ? $r->status : 'active'); ?></span></td>
              <td>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('designations/'.(int)$r->id.'/edit'); ?>">Edit</a>
                <a class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this designation?')" href="<?php echo site_url('designations/'.(int)$r->id.'/delete'); ?>">Delete</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>

