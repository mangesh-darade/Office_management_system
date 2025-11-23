<?php $this->load->view('partials/header', ['title' => 'My Assets']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">My Assets</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('assets-mgmt'); ?>">All Assets (Admin/HR)</a>
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
            <th>Allocated On</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted">No assets assigned to you.</td></tr>
          <?php else: $i=1; foreach ($rows as $r): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars($r->name); ?></td>
              <td><?php echo htmlspecialchars(isset($r->category)?$r->category:''); ?></td>
              <td><?php echo htmlspecialchars(trim((isset($r->brand)?$r->brand:'').' '.(isset($r->model)?$r->model:''))); ?></td>
              <td><?php echo htmlspecialchars(trim((isset($r->serial_no)?$r->serial_no:'').' '.(isset($r->asset_tag)?'['.$r->asset_tag.']':''))); ?></td>
              <td><?php echo htmlspecialchars(trim((isset($r->ram)?$r->ram:'').' '.(isset($r->hdd)?$r->hdd:''))); ?></td>
              <td><?php echo htmlspecialchars(isset($r->allocated_on)?$r->allocated_on:''); ?></td>
              <td><?php echo htmlspecialchars(isset($r->remarks)?$r->remarks:''); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
