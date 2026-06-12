<?php
  $work_id = isset($work_id) ? (int) $work_id : 0;
  $attachments = isset($attachments) ? $attachments : array();
  if (empty($attachments)) {
    return;
  }
?>
<div class="mw-existing-attachments mt-2">
  <div class="small text-muted text-uppercase fw-semibold mb-2">Current attachments</div>
  <?php foreach ($attachments as $att): ?>
    <div class="mw-existing-att-item d-flex flex-wrap align-items-center gap-2 py-2 border-bottom">
      <i class="bi <?php echo htmlspecialchars($att['icon'], ENT_QUOTES, 'UTF-8'); ?> text-muted"></i>
      <div class="flex-grow-1 min-w-0">
        <div class="text-truncate small fw-medium" title="<?php echo htmlspecialchars($att['name'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo htmlspecialchars($att['name'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php if (!empty($att['size_label'])): ?>
          <div class="text-muted" style="font-size:0.72rem;"><?php echo htmlspecialchars($att['size_label'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
      </div>
      <div class="btn-group btn-group-sm">
        <?php if ($att['can_preview']): ?>
          <button type="button"
                  class="btn btn-outline-primary mw-media-play-btn"
                  data-preview-url="<?php echo htmlspecialchars($att['preview_url'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-media-kind="<?php echo htmlspecialchars($att['kind'], ENT_QUOTES, 'UTF-8'); ?>"
                  data-media-title="<?php echo htmlspecialchars($att['name'], ENT_QUOTES, 'UTF-8'); ?>"
                  title="Preview">
            <i class="bi bi-<?php echo $att['is_video'] ? 'play-fill' : ($att['kind'] === 'image' ? 'image' : 'volume-up-fill'); ?>"></i>
          </button>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($att['download_url'], ENT_QUOTES, 'UTF-8'); ?>" title="Download">
          <i class="bi bi-download"></i>
        </a>
      </div>
      <label class="mb-0 small text-danger ms-1">
        <input type="checkbox" name="remove_attachments[]" value="<?php echo (int) $att['id']; ?>"> Remove
      </label>
    </div>
  <?php endforeach; ?>
</div>
