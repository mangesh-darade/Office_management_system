<?php
  $row = isset($r) ? $r : null;
  $map = isset($attachments_map) ? $attachments_map : null;
  $attachments = my_works_row_attachments($row, $map);
  if (empty($attachments)) {
    return;
  }
  $summary = my_works_attachments_summary($attachments);
  if (!$summary) {
    return;
  }
?>
<?php if ($summary['count'] === 1): ?>
  <?php $att = $attachments[0]; ?>
  <span class="mw-att-badge mw-att-badge-<?php echo htmlspecialchars($att['kind'], ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($att['label'] . ': ' . $att['name'], ENT_QUOTES, 'UTF-8'); ?>">
    <i class="bi <?php echo htmlspecialchars($att['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
    <span class="mw-att-badge-text"><?php echo htmlspecialchars($att['label'], ENT_QUOTES, 'UTF-8'); ?></span>
  </span>
<?php else: ?>
  <span class="mw-att-badge mw-att-badge-multi" title="<?php echo (int) $summary['count']; ?> attachments">
    <i class="bi bi-paperclip"></i>
    <span class="mw-att-badge-text"><?php echo (int) $summary['count']; ?> files</span>
  </span>
  <?php foreach ($summary['kinds'] as $kind): ?>
    <span class="mw-att-kind-chip mw-att-kind-<?php echo htmlspecialchars($kind, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars(my_works_attachment_badge_label($kind), ENT_QUOTES, 'UTF-8'); ?>">
      <i class="bi <?php echo htmlspecialchars(my_works_attachment_icon_class($kind), ENT_QUOTES, 'UTF-8'); ?>"></i>
    </span>
  <?php endforeach; ?>
<?php endif; ?>
