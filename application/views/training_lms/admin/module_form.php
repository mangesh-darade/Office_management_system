<?php
$ed = isset($row) && $row;
$this->load->view('partials/header', array('title' => $ed ? 'Edit module' : 'New module'));
?>
<div class="container py-4">
  <h1 class="h4 mb-3"><?php echo $ed ? 'Edit module' : 'New module'; ?></h1>
  <?php echo form_open('training-lms-admin/save-module'); ?>
  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
  <?php if ($ed): ?><input type="hidden" name="id" value="<?php echo (int) $row->id; ?>"><?php endif; ?>
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control" required maxlength="255" value="<?php echo $ed ? esc_view($row->title) : ''; ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?php echo $ed ? esc_view($row->description) : ''; ?></textarea>
      </div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Sort order</label>
          <input type="number" name="sort_order" class="form-control" value="<?php echo $ed ? (int) $row->sort_order : 0; ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="active" <?php echo (!$ed || $row->status === 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="inactive" <?php echo ($ed && $row->status === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card-footer bg-transparent">
      <button type="submit" class="btn btn-primary">Save</button>
      <a href="<?php echo site_url('training-lms-admin'); ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </div>
  <?php echo form_close(); ?>
</div>
<?php $this->load->view('partials/footer'); ?>
