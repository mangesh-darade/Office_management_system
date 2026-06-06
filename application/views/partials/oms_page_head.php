<?php
/**
 * Standard list/detail page header (mobile-friendly).
 *
 * @var string      $title       Page title (required)
 * @var string|null $subtitle    Optional description
 * @var string|null $icon        Bootstrap icon class e.g. bi-people
 * @var string|null $actions_html Raw HTML for action buttons (right column)
 * @var string      $mb          Bottom margin class suffix, default mb-3
 */
defined('BASEPATH') OR exit('No direct script access allowed');
$mb = isset($mb) ? (string) $mb : 'mb-3';
?>
<div class="oms-page-head d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 <?php echo htmlspecialchars($mb, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="oms-page-head-text flex-grow-1 min-w-0">
    <h1 class="h4 mb-1 fw-bold text-truncate">
      <?php if (!empty($icon)): ?>
        <i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?> text-primary me-2"></i>
      <?php endif; ?>
      <?php echo htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8'); ?>
    </h1>
    <?php if (!empty($subtitle)): ?>
      <p class="text-muted small mb-0"><?php echo htmlspecialchars((string) $subtitle, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
  </div>
  <?php if (!empty($actions_html)): ?>
    <div class="oms-page-actions d-flex flex-wrap gap-2 w-100 w-md-auto">
      <?php echo $actions_html; ?>
    </div>
  <?php endif; ?>
</div>
