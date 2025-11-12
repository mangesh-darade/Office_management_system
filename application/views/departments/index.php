<?php $this->load->view('partials/header', ['title' => 'Departments']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Departments</h1>
  <a class="btn btn-primary btn-sm" href="<?php echo site_url('departments/create'); ?>">Create Department</a>
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
            <th>Manager</th>
            <th>Status</th>
            <th style="width:180px"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="text-center text-muted">No departments found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars(isset($r->dept_code) ? $r->dept_code : ''); ?></td>
              <td><?php echo htmlspecialchars(isset($r->dept_name) ? $r->dept_name : ''); ?></td>
              <td><?php 
                if (!empty($r->manager_id) && isset($managers[(int)$r->manager_id])){ 
                  $m = $managers[(int)$r->manager_id]; echo htmlspecialchars($m->email.(!empty($m->name)?' ('.$m->name.')':''));
                } else { echo '<span class="text-muted">—</span>'; }
              ?></td>
              <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(isset($r->status) ? $r->status : 'active'); ?></span></td>
              <td>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('departments/'.(int)$r->id.'/edit'); ?>">Edit</a>
                <a class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this department?')" href="<?php echo site_url('departments/'.(int)$r->id.'/delete'); ?>">Delete</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
