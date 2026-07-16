<?php
$manage_categories = isset($manage_categories) && is_array($manage_categories) ? $manage_categories : array();
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
?>
<div class="spl-panel-card">
  <div class="spl-panel-head spl-rules-panel-head">
    <div>
      <h2 class="spl-panel-title">Reward categories</h2>
      <p class="spl-panel-sub mb-0">Manage categories used by SPL rules and activity forms.</p>
    </div>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#splCategoryModal" data-mode="create">
      <i class="bi bi-plus-lg me-1"></i>Add category
    </button>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success py-2 mx-3 mt-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger py-2 mx-3 mt-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead>
        <tr>
          <th style="width:48px;">Icon</th>
          <th>Name</th>
          <th>Code</th>
          <th>Description</th>
          <th class="text-center" style="width:80px;">Order</th>
          <th style="width:90px;">Status</th>
          <th class="text-end" style="width:160px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($manage_categories)): ?>
        <tr>
          <td colspan="7" class="text-center text-muted py-4">No categories found.</td>
        </tr>
        <?php else: foreach ($manage_categories as $cat): ?>
        <?php $is_active = (int) $cat->is_active === 1; ?>
        <tr>
          <td><i class="<?php echo esc_view(!empty($cat->icon_class) ? $cat->icon_class : 'bi bi-star', ENT_QUOTES, 'UTF-8'); ?>"></i></td>
          <td class="fw-semibold"><?php echo esc_view($cat->name, ENT_QUOTES, 'UTF-8'); ?></td>
          <td><code><?php echo esc_view($cat->code, ENT_QUOTES, 'UTF-8'); ?></code></td>
          <td class="small text-muted"><?php echo esc_view(isset($cat->description) ? $cat->description : '', ENT_QUOTES, 'UTF-8'); ?></td>
          <td class="text-center"><?php echo (int) $cat->sort_order; ?></td>
          <td>
            <?php if ($is_active): ?>
            <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">Active</span>
            <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle">Inactive</span>
            <?php endif; ?>
          </td>
          <td class="text-end text-nowrap">
            <button type="button"
                    class="btn btn-sm btn-outline-primary spl-edit-category"
                    data-bs-toggle="modal"
                    data-bs-target="#splCategoryModal"
                    data-mode="edit"
                    data-id="<?php echo (int) $cat->id; ?>"
                    data-name="<?php echo esc_view($cat->name, ENT_QUOTES, 'UTF-8'); ?>"
                    data-code="<?php echo esc_view($cat->code, ENT_QUOTES, 'UTF-8'); ?>"
                    data-description="<?php echo esc_view(isset($cat->description) ? $cat->description : '', ENT_QUOTES, 'UTF-8'); ?>"
                    data-icon="<?php echo esc_view(!empty($cat->icon_class) ? $cat->icon_class : 'bi bi-star', ENT_QUOTES, 'UTF-8'); ?>"
                    data-sort="<?php echo (int) $cat->sort_order; ?>"
                    data-active="<?php echo $is_active ? '1' : '0'; ?>">
              Edit
            </button>
            <form method="post" action="<?php echo site_url('spl/categories/toggle/' . (int) $cat->id); ?>" class="d-inline">
              <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
              <button type="submit" class="btn btn-sm btn-outline-secondary"><?php echo $is_active ? 'Deactivate' : 'Activate'; ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="splCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?php echo site_url('spl/save-category'); ?>" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="splCategoryModalTitle">Add category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="id" id="splCatId" value="0">
        <div class="mb-3">
          <label class="form-label" for="splCatName">Name</label>
          <input type="text" class="form-control" name="name" id="splCatName" required maxlength="100">
        </div>
        <div class="mb-3">
          <label class="form-label" for="splCatCode">Code</label>
          <input type="text" class="form-control" name="code" id="splCatCode" maxlength="50" placeholder="auto from name if blank">
        </div>
        <div class="mb-3">
          <label class="form-label" for="splCatDescription">Description</label>
          <textarea class="form-control" name="description" id="splCatDescription" rows="2"></textarea>
        </div>
        <div class="row g-2">
          <div class="col-md-8">
            <label class="form-label" for="splCatIcon">Icon class</label>
            <input type="text" class="form-control" name="icon_class" id="splCatIcon" value="bi bi-star" maxlength="80">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="splCatSort">Sort order</label>
            <input type="number" class="form-control" name="sort_order" id="splCatSort" value="0">
          </div>
        </div>
        <div class="form-check mt-3">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" id="splCatActive" checked>
          <label class="form-check-label" for="splCatActive">Active</label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var modal = document.getElementById('splCategoryModal');
  if (!modal) { return; }
  modal.addEventListener('show.bs.modal', function (event) {
    var btn = event.relatedTarget;
    var mode = btn ? (btn.getAttribute('data-mode') || 'create') : 'create';
    var title = document.getElementById('splCategoryModalTitle');
    var idEl = document.getElementById('splCatId');
    var nameEl = document.getElementById('splCatName');
    var codeEl = document.getElementById('splCatCode');
    var descEl = document.getElementById('splCatDescription');
    var iconEl = document.getElementById('splCatIcon');
    var sortEl = document.getElementById('splCatSort');
    var activeEl = document.getElementById('splCatActive');
    if (mode === 'edit' && btn) {
      if (title) { title.textContent = 'Edit category'; }
      if (idEl) { idEl.value = btn.getAttribute('data-id') || '0'; }
      if (nameEl) { nameEl.value = btn.getAttribute('data-name') || ''; }
      if (codeEl) { codeEl.value = btn.getAttribute('data-code') || ''; }
      if (descEl) { descEl.value = btn.getAttribute('data-description') || ''; }
      if (iconEl) { iconEl.value = btn.getAttribute('data-icon') || 'bi bi-star'; }
      if (sortEl) { sortEl.value = btn.getAttribute('data-sort') || '0'; }
      if (activeEl) { activeEl.checked = btn.getAttribute('data-active') === '1'; }
    } else {
      if (title) { title.textContent = 'Add category'; }
      if (idEl) { idEl.value = '0'; }
      if (nameEl) { nameEl.value = ''; }
      if (codeEl) { codeEl.value = ''; }
      if (descEl) { descEl.value = ''; }
      if (iconEl) { iconEl.value = 'bi bi-star'; }
      if (sortEl) { sortEl.value = '0'; }
      if (activeEl) { activeEl.checked = true; }
    }
  });
})();
</script>
