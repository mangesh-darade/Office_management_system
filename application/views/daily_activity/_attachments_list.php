<?php
  $attachments = isset($attachments) ? $attachments : array();
  if (empty($attachments)) {
    return;
  }
  $show_remove = !empty($show_remove);
?>
<div class="da-attachments mt-2">
  <?php if ($show_remove): ?>
    <div class="small text-muted text-uppercase fw-semibold mb-2">Current attachments</div>
  <?php endif; ?>
  <ul class="list-unstyled mb-0 d-flex flex-wrap gap-2">
    <?php foreach ($attachments as $att): ?>
      <li class="da-att-chip border rounded px-2 py-1 bg-light d-inline-flex align-items-center gap-2">
        <i class="bi <?php echo esc_view($att['icon']); ?> text-secondary"></i>
        <span class="small text-truncate" style="max-width: 10rem;" title="<?php echo esc_view($att['name']); ?>">
          <?php echo esc_view($att['name']); ?>
        </span>
        <?php if (!empty($att['can_preview'])): ?>
          <a class="btn btn-link btn-sm p-0" href="<?php echo esc_view($att['preview_url']); ?>" target="_blank" rel="noopener" title="Preview">
            <i class="bi bi-eye"></i>
          </a>
        <?php endif; ?>
        <a class="btn btn-link btn-sm p-0" href="<?php echo esc_view($att['download_url']); ?>" title="Download">
          <i class="bi bi-download"></i>
        </a>
        <?php if ($show_remove): ?>
          <label class="mb-0 small text-danger" title="Remove on save">
            <input type="checkbox" name="remove_attachments[]" value="<?php echo (int) $att['id']; ?>">
          </label>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
