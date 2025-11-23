<?php $this->load->view('partials/header', ['title' => 'Assets']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Assets</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('assets-mgmt/my'); ?>">My Assets</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('assets-mgmt/create'); ?>">Add Asset</a>
  </div>
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
            <th>#</th>
            <th>Name</th>
            <th>Category</th>
            <th>Brand / Model</th>
            <th>Serial / Tag</th>
            <th>RAM / HDD</th>
            <th>Status</th>
            <th>Assigned To</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted">No assets found.</td></tr>
          <?php else: $i=1; foreach ($rows as $r): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($r->name); ?></td>
              <td><?php echo htmlspecialchars(isset($r->category)?$r->category:''); ?></td>
              <td><?php echo htmlspecialchars(trim((isset($r->brand)?$r->brand:'').' '.(isset($r->model)?$r->model:''))); ?></td>
              <td><?php echo htmlspecialchars(trim((isset($r->serial_no)?$r->serial_no:'').' '.(isset($r->asset_tag)?'['.$r->asset_tag.']':''))); ?></td>
              <td><?php echo htmlspecialchars(trim((isset($r->ram)?$r->ram:'').' '.(isset($r->hdd)?$r->hdd:''))); ?></td>
              <td>
                <?php $st = isset($r->status)?$r->status:'in_stock'; ?>
                <span class="badge bg-<?php echo $st==='assigned'?'warning':'secondary'; ?> text-dark"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ', $st))); ?></span>
              </td>
              <td>
                <?php echo isset($r->email) && $r->email ? htmlspecialchars($r->email) : '<span class="text-muted">Unassigned</span>'; ?>
              </td>
              <td>
                <div class="btn-group btn-group-sm" role="group">
                  <a href="<?php echo site_url('assets-mgmt/edit/'.(int)$r->id); ?>" class="btn btn-outline-secondary">Edit</a>
                  <a href="<?php echo site_url('assets-mgmt/assign/'.(int)$r->id); ?>" class="btn btn-outline-primary">Assign</a>
                  <?php if (!empty($r->email)): ?>
                    <form method="post" action="<?php echo site_url('assets-mgmt/return_asset/'.(int)$r->id); ?>" onsubmit="return confirm('Mark asset as returned?');">
                      <button class="btn btn-outline-success">Return</button>
                    </form>
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
<?php $this->load->view('partials/footer'); ?>
