<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$isEdit = !empty($row);
$this->load->view('partials/header', array(
  'title' => $isEdit ? 'Edit External Training' : 'Add External Training',
  'with_sidebar' => true,
));
?>
<div class="container-fluid py-3">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?php echo $isEdit ? 'Edit External Training' : 'Add External Training'; ?></h1>
    <a href="<?php echo site_url('external-training'); ?>" class="btn btn-outline-secondary">
      &larr; Back to list
    </a>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo $this->session->flashdata('error'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm">
    <div class="card-body">
      <?php echo form_open('external-training/save'); ?>
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?php echo (int) $row->id; ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" id="name" class="form-control" required
                 value="<?php echo $isEdit ? esc_view($row->name) : ''; ?>">
        </div>

        <div class="mb-3">
          <label for="embed_code" class="form-label">Video link or embed <span class="text-danger">*</span></label>
          <textarea name="embed_code" id="embed_code" rows="5" class="form-control" required><?php
            echo $isEdit ? esc_view($row->embed_code) : '';
          ?></textarea>
          <div class="form-text">
            Paste either a direct <strong>https://…</strong> link (normal YouTube/Vimeo watch links are converted to an embed player in-app) or the full HTML embed snippet.
            Staff open the training inside this app via <strong>Open (signed in)</strong>; the list page does not show or copy the raw link.
            To limit “copy video URL” style menus, use a direct <strong>.mp4 / .webm</strong> file link (built-in player). YouTube/Vimeo embeds always show their own right-click menu — that cannot be removed from this app.
          </div>
        </div>

        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active"
                 value="1" <?php echo !$isEdit || (int) $row->is_active === 1 ? 'checked' : ''; ?>>
          <label class="form-check-label" for="is_active">Active</label>
        </div>

        <button type="submit" class="btn btn-primary">
          <?php echo $isEdit ? 'Save Changes' : 'Create External Training'; ?>
        </button>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>

