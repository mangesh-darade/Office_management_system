<?php $this->load->view('partials/header', array('title' => ($action === 'edit' ? 'Edit Type' : 'Create Type'))); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo $action === 'edit' ? 'Edit Type' : 'Create Type'; ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('settings/types'); ?>">Back to Types</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="post" action="<?php echo $action === 'edit' ? site_url('settings/types/' . (int) $type->id . '/edit') : site_url('settings/types/create'); ?>">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required value="<?php echo isset($type) ? htmlspecialchars($type->name) : ''; ?>" placeholder="e.g. Partner">
        </div>
        <div class="col-md-6">
          <label class="form-label">Code <span class="text-danger">*</span></label>
          <input type="text" name="code" class="form-control" required value="<?php echo isset($type) ? htmlspecialchars($type->code) : ''; ?>" placeholder="e.g. partner">
          <div class="form-text">Lowercase with underscores (e.g. srujan_client)</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Module <span class="text-danger">*</span></label>
          <select name="module" class="form-select" required>
            <option value="">— Select module —</option>
            <?php foreach ($modules as $key => $label): ?>
              <option value="<?php echo htmlspecialchars($key); ?>" <?php echo (isset($type) && $type->module === $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Display order</label>
          <input type="number" name="display_order" class="form-control" value="<?php echo isset($type) ? (int) $type->display_order : 0; ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="typeActive" <?php echo (!isset($type) || (int) $type->is_active === 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="typeActive">Active</label>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?php echo isset($type) && $type->description ? htmlspecialchars($type->description) : ''; ?></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary"><?php echo $action === 'edit' ? 'Save changes' : 'Create type'; ?></button>
          <a class="btn btn-outline-secondary" href="<?php echo site_url('settings/types'); ?>">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
