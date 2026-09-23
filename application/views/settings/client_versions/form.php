<?php
  $is_edit = (isset($action) && $action === 'edit' && isset($version));
  $this->load->view('partials/header', array('title' => ($is_edit ? 'Edit Version' : 'Add Version')));
?>
<div class="oms-form-compact">
<div class="oms-form-page-head d-flex justify-content-between align-items-center mb-2">
  <h1 class="h4 mb-0"><?php echo $is_edit ? 'Edit Version' : 'Add Version'; ?></h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('settings/client-versions'); ?>">Back to Versions</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger py-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="post" action="<?php echo $is_edit ? site_url('settings/client-versions/' . (int) $version->id . '/edit') : site_url('settings/client-versions/create'); ?>">
      <div class="row g-2 oms-form-grid">
        <div class="col-md-6">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required value="<?php echo $is_edit ? esc_view($version->name) : ''; ?>" placeholder="e.g. 2.0">
        </div>
        <div class="col-md-6">
          <label class="form-label">Code <span class="text-danger">*</span></label>
          <input type="text" name="code" class="form-control" required value="<?php echo $is_edit ? esc_view($version->code) : ''; ?>" placeholder="e.g. 2.0">
          <div class="form-text">Stored on the client record (e.g. 1.0, 2.0)</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Display order</label>
          <input type="number" name="display_order" class="form-control" value="<?php echo $is_edit ? (int) $version->display_order : 0; ?>">
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="verActive" <?php echo (!$is_edit || (int) $version->is_active === 1) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="verActive">Active</label>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?php echo ($is_edit && $version->description) ? esc_view($version->description) : ''; ?></textarea>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Save changes' : 'Create version'; ?></button>
          <a class="btn btn-outline-secondary" href="<?php echo site_url('settings/client-versions'); ?>">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
