<?php $this->load->view('partials/header', array('title' => $type->name)); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo htmlspecialchars($type->name); ?></h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('types/edit/' . (int) $type->id); ?>">Edit</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('types'); ?>">Back</a>
  </div>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3">Code</dt>
      <dd class="col-sm-9"><code><?php echo htmlspecialchars($type->code); ?></code></dd>
      <dt class="col-sm-3">Module</dt>
      <dd class="col-sm-9"><?php echo htmlspecialchars(isset($modules[$type->module]) ? $modules[$type->module] : $type->module); ?></dd>
      <dt class="col-sm-3">Display order</dt>
      <dd class="col-sm-9"><?php echo (int) $type->display_order; ?></dd>
      <dt class="col-sm-3">Active</dt>
      <dd class="col-sm-9"><?php echo (int) $type->is_active === 1 ? 'Yes' : 'No'; ?></dd>
      <?php if (!empty($type->description)): ?>
      <dt class="col-sm-3">Description</dt>
      <dd class="col-sm-9"><?php echo nl2br(htmlspecialchars($type->description)); ?></dd>
      <?php endif; ?>
    </dl>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
