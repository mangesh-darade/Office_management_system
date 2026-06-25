<?php
  $attachments = isset($attachments) ? $attachments : array();
  $title = isset($title) ? (string) $title : 'Attachments';
  if (empty($attachments)) {
    return;
  }
?>
<div class="mw-attachments-panel">
  <div class="small text-muted text-uppercase fw-bold mb-2">
    <i class="bi bi-paperclip me-1"></i>Attachments (<?php echo (int) count($attachments); ?>)
  </div>
  <div class="list-group list-group-flush mw-att-list">
    <?php foreach ($attachments as $att): ?>
      <div class="list-group-item px-0 py-2 border-0 border-bottom mw-att-list-item">
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="mw-att-list-icon">
            <i class="bi <?php echo esc_view($att['icon']); ?>"></i>
          </span>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-medium text-truncate" title="<?php echo esc_view($att['name']); ?>">
              <?php echo esc_view($att['name']); ?>
            </div>
            <div class="small text-muted">
              <?php echo esc_view($att['label']); ?>
              <?php if (!empty($att['size_label'])): ?>
                <span class="mx-1">&middot;</span><?php echo esc_view($att['size_label']); ?>
              <?php endif; ?>
            </div>
          </div>
          <div class="btn-group btn-group-sm">
            <?php if ($att['can_preview']): ?>
              <button type="button"
                      class="btn btn-outline-primary mw-media-play-btn"
                      data-preview-url="<?php echo esc_view($att['preview_url']); ?>"
                      data-media-kind="<?php echo esc_view($att['kind']); ?>"
                      data-media-title="<?php echo esc_view($att['name']); ?>"
                      <?php if (!empty($att['size_label'])): ?>data-media-size="<?php echo esc_view($att['size_label']); ?>"<?php endif; ?>
                      title="Preview">
                <i class="bi bi-<?php echo $att['is_video'] ? 'play-fill' : ($att['kind'] === 'image' ? 'image' : 'volume-up-fill'); ?>"></i>
              </button>
            <?php endif; ?>
            <a class="btn btn-outline-secondary" href="<?php echo esc_view($att['download_url']); ?>" title="Download">
              <i class="bi bi-download"></i>
            </a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
