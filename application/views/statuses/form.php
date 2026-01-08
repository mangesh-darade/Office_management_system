<?php $this->load->view('partials/header', ['title' => ($action === 'edit' ? 'Edit Status' : 'Create Status')]); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo $action === 'edit' ? 'Edit Status' : 'Create Status'; ?></h1>
  <div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('statuses'); ?>">Back to Statuses</a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo $action === 'edit' ? site_url('statuses/edit/'.$status->id) : site_url('statuses/create'); ?>" data-validate="true">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" value="<?php echo isset($status) ? htmlspecialchars($status->name) : ''; ?>" required placeholder="e.g. In Progress">
        </div>
        <div class="col-md-6">
          <label class="form-label">Code <span class="text-danger">*</span></label>
          <input type="text" name="code" class="form-control" value="<?php echo isset($status) ? htmlspecialchars($status->code) : ''; ?>" required placeholder="e.g. in_progress">
          <div class="form-text">Lowercase, use underscores (e.g., in_progress, on_hold)</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Type <span class="text-danger">*</span></label>
          <select name="type" class="form-select" required>
            <option value="">-- Select Type --</option>
            <?php foreach ($types as $type): ?>
              <option value="<?php echo htmlspecialchars($type); ?>" <?php echo (isset($status) && $status->type === $type) ? 'selected' : ''; ?>>
                <?php echo ucfirst($type); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Color</label>
          <div class="input-group">
            <input type="color" name="color" class="form-control form-control-color" value="<?php echo isset($status) && $status->color ? htmlspecialchars($status->color) : '#6c757d'; ?>" title="Choose color">
            <input type="text" name="color_text" class="form-control" value="<?php echo isset($status) && $status->color ? htmlspecialchars($status->color) : '#6c757d'; ?>" id="color-text-input" placeholder="#6c757d">
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Icon (Bootstrap Icons)</label>
          <select name="icon" id="icon-select" class="form-select">
            <option value="">-- No Icon --</option>
            <?php
            // Comprehensive list of commonly used Bootstrap Icons
            $icons = [
              // Status & Action Icons
              'check-circle', 'check-circle-fill', 'check', 'check-square', 'check-square-fill',
              'x-circle', 'x-circle-fill', 'x', 'x-square', 'x-square-fill',
              'exclamation-circle', 'exclamation-triangle', 'exclamation-triangle-fill',
              'question-circle', 'question-circle-fill', 'info-circle', 'info-circle-fill',
              
              // Status Indicators
              'circle', 'circle-fill', 'dot', 'record-circle', 'record-circle-fill',
              'stop-circle', 'stop-circle-fill', 'pause-circle', 'pause-circle-fill',
              'play-circle', 'play-circle-fill', 'play-fill', 'pause-fill', 'stop-fill',
              
              // Progress & Workflow
              'arrow-right-circle', 'arrow-left-circle', 'arrow-up-circle', 'arrow-down-circle',
              'arrow-repeat', 'arrow-clockwise', 'arrow-counterclockwise',
              'hourglass', 'hourglass-split', 'hourglass-bottom', 'hourglass-top',
              
              // Time & Schedule
              'clock', 'clock-fill', 'calendar', 'calendar-check', 'calendar-event',
              'calendar-x', 'calendar-plus', 'calendar-minus', 'calendar-range',
              
              // Communication
              'envelope', 'envelope-fill', 'envelope-open', 'envelope-open-fill',
              'chat', 'chat-dots', 'chat-fill', 'chat-dots-fill',
              'bell', 'bell-fill', 'bell-slash', 'bell-slash-fill',
              
              // Document & File
              'file', 'file-earmark', 'file-earmark-text', 'file-earmark-pdf',
              'file-earmark-word', 'file-earmark-excel', 'file-earmark-image',
              'folder', 'folder-fill', 'folder-open', 'folder-open-fill',
              'file-plus', 'file-minus', 'file-check', 'file-x',
              
              // User & People
              'person', 'person-fill', 'people', 'people-fill',
              'person-check', 'person-x', 'person-plus', 'person-dash',
              
              // Navigation & Location
              'house', 'house-fill', 'geo-alt', 'geo-alt-fill',
              'map', 'map-fill', 'compass', 'compass-fill',
              
              // Tools & Settings
              'gear', 'gear-fill', 'sliders', 'tools', 'wrench',
              'wrench-adjustable', 'wrench-adjustable-circle',
              
              // Shopping & Commerce
              'cart', 'cart-fill', 'bag', 'bag-fill', 'credit-card',
              'credit-card-fill', 'currency-dollar', 'currency-euro',
              
              // Media
              'image', 'image-fill', 'camera', 'camera-fill',
              'film', 'play-btn', 'pause-btn', 'stop-btn',
              
              // Security & Lock
              'lock', 'lock-fill', 'unlock', 'unlock-fill',
              'shield', 'shield-fill', 'shield-check', 'shield-exclamation',
              
              // Warning & Danger
              'fire', 'lightning', 'lightning-charge', 'lightning-charge-fill',
              'bug', 'bug-fill', 'shield-exclamation',
              
              // Success & Positive
              'star', 'star-fill', 'heart', 'heart-fill',
              'trophy', 'award', 'badge', 'badge-check',
              
              // Lists & Organization
              'list', 'list-check', 'list-task', 'list-ul',
              'grid', 'grid-fill', 'grid-3x3', 'grid-3x3-gap',
              
              // Arrows & Direction
              'arrow-up', 'arrow-down', 'arrow-left', 'arrow-right',
              'chevron-up', 'chevron-down', 'chevron-left', 'chevron-right',
              'caret-up', 'caret-down', 'caret-left', 'caret-right',
              
              // Miscellaneous
              'bookmark', 'bookmark-fill', 'tag', 'tag-fill',
              'flag', 'flag-fill', 'pencil', 'pencil-fill',
              'trash', 'trash-fill', 'plus-circle', 'plus-circle-fill',
              'dash-circle', 'dash-circle-fill', 'x-lg', 'plus-lg',
              'search', 'funnel', 'funnel-fill', 'filter',
              'download', 'upload', 'cloud', 'cloud-fill',
              'wifi', 'wifi-off', 'battery-full', 'battery-half',
            ];
            
            $currentIcon = isset($status) ? $status->icon : '';
            foreach ($icons as $icon):
              $selected = ($currentIcon === $icon) ? 'selected' : '';
              ?>
              <option value="<?php echo htmlspecialchars($icon); ?>" <?php echo $selected; ?>>
                <?php echo htmlspecialchars($icon); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">
            <span id="icon-preview">
              <?php if ($currentIcon): ?>
                <i class="bi bi-<?php echo htmlspecialchars($currentIcon); ?> me-1"></i>
                Preview: <code><?php echo htmlspecialchars($currentIcon); ?></code>
              <?php else: ?>
                Select an icon to preview
              <?php endif; ?>
            </span>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Display Order</label>
          <input type="number" name="display_order" class="form-control" value="<?php echo isset($status) ? (int)$status->display_order : 0; ?>" min="0">
          <div class="form-text">Lower numbers appear first in dropdowns</div>
        </div>
        <div class="col-md-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Optional description"><?php echo isset($status) ? htmlspecialchars($status->description) : ''; ?></textarea>
        </div>
        <div class="col-md-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" <?php echo (isset($status) && $status->is_active) || !isset($status) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_active">
              Active (only active statuses appear in dropdowns)
            </label>
          </div>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?php echo $action === 'edit' ? 'Save Changes' : 'Create Status'; ?></button>
        <a class="btn btn-light" href="<?php echo site_url('statuses'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<script>
  $(document).ready(function() {
    // Icon preview on change
    $('#icon-select').on('change', function() {
      var selectedIcon = $(this).val();
      var preview = $('#icon-preview');
      
      if (selectedIcon) {
        preview.html('<i class="bi bi-' + selectedIcon + ' me-1"></i>Preview: <code>' + selectedIcon + '</code>');
      } else {
        preview.html('Select an icon to preview');
      }
    });
    
    // Color picker sync
    $('input[type="color"]').on('change', function() {
      $('#color-text-input').val($(this).val());
    });
    
    $('#color-text-input').on('input', function() {
      var colorValue = $(this).val();
      if (/^#[0-9A-F]{6}$/i.test(colorValue)) {
        $('input[type="color"]').val(colorValue);
      }
    });
  });
</script>

<?php $this->load->view('partials/footer'); ?>

