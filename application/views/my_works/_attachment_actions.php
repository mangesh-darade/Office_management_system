<?php
  $row = isset($r) ? $r : null;
  $map = isset($attachments_map) ? $attachments_map : null;
  $attachments = my_works_row_attachments($row, $map);
  if (empty($attachments)) {
    return;
  }
  $title = ($row && isset($row->title)) ? (string) $row->title : 'Attachment';
?>
<?php if (count($attachments) === 1): ?>
  <?php $att = $attachments[0]; ?>
  <?php if ($att['is_video']): ?>
    <button type="button"
            class="btn btn-sm btn-outline-danger mw-media-play-btn"
            data-preview-url="<?php echo htmlspecialchars($att['preview_url'], ENT_QUOTES, 'UTF-8'); ?>"
            data-media-kind="video"
            data-media-title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
            <?php if (!empty($att['size_label'])): ?>data-media-size="<?php echo htmlspecialchars($att['size_label'], ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
            title="Play video">
      <i class="bi bi-play-fill me-1"></i><span class="d-none d-md-inline">Play</span>
    </button>
  <?php elseif ($att['kind'] === 'image'): ?>
    <button type="button"
            class="btn btn-sm btn-outline-info mw-media-play-btn"
            data-preview-url="<?php echo htmlspecialchars($att['preview_url'], ENT_QUOTES, 'UTF-8'); ?>"
            data-media-kind="image"
            data-media-title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
            title="View image">
      <i class="bi bi-image"></i>
    </button>
  <?php elseif ($att['kind'] === 'audio'): ?>
    <button type="button"
            class="btn btn-sm btn-outline-secondary mw-media-play-btn"
            data-preview-url="<?php echo htmlspecialchars($att['preview_url'], ENT_QUOTES, 'UTF-8'); ?>"
            data-media-kind="audio"
            data-media-title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
            title="Play audio">
      <i class="bi bi-volume-up-fill"></i>
    </button>
  <?php endif; ?>
  <a class="btn btn-sm btn-outline-secondary"
     href="<?php echo htmlspecialchars($att['download_url'], ENT_QUOTES, 'UTF-8'); ?>"
     title="Download <?php echo htmlspecialchars($att['label'], ENT_QUOTES, 'UTF-8'); ?>">
    <i class="bi bi-download"></i>
  </a>
<?php else: ?>
  <div class="btn-group btn-group-sm">
    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo (int) count($attachments); ?> attachments">
      <i class="bi bi-paperclip"></i><span class="d-none d-lg-inline ms-1"><?php echo (int) count($attachments); ?></span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end mw-att-dropdown">
      <?php foreach ($attachments as $att): ?>
        <li>
          <div class="dropdown-item-text mw-att-dropdown-item">
            <div class="mw-att-dropdown-name text-truncate" title="<?php echo htmlspecialchars($att['name'], ENT_QUOTES, 'UTF-8'); ?>">
              <i class="bi <?php echo htmlspecialchars($att['icon'], ENT_QUOTES, 'UTF-8'); ?> me-1"></i>
              <?php echo htmlspecialchars($att['name'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="mw-att-dropdown-actions btn-group btn-group-sm mt-1">
              <?php if ($att['can_preview']): ?>
                <button type="button"
                        class="btn btn-outline-primary mw-media-play-btn"
                        data-preview-url="<?php echo htmlspecialchars($att['preview_url'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-media-kind="<?php echo htmlspecialchars($att['kind'], ENT_QUOTES, 'UTF-8'); ?>"
                        data-media-title="<?php echo htmlspecialchars($att['name'], ENT_QUOTES, 'UTF-8'); ?>"
                        <?php if (!empty($att['size_label'])): ?>data-media-size="<?php echo htmlspecialchars($att['size_label'], ENT_QUOTES, 'UTF-8'); ?>"<?php endif; ?>
                        title="Preview">
                  <i class="bi bi-<?php echo $att['is_video'] ? 'play-fill' : ($att['kind'] === 'image' ? 'image' : 'volume-up-fill'); ?>"></i>
                </button>
              <?php endif; ?>
              <a class="btn btn-outline-secondary" href="<?php echo htmlspecialchars($att['download_url'], ENT_QUOTES, 'UTF-8'); ?>" title="Download">
                <i class="bi bi-download"></i>
              </a>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
