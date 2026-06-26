<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (empty($reference_url)) { return; } ?>
<?php
$url = (string) $reference_url;
$wrapper_class = isset($wrapper_class) ? (string) $wrapper_class : 'mt-2';
?>
<div class="reference-url-display d-flex align-items-start gap-2 <?php echo esc_view($wrapper_class); ?>">
  <i class="bi bi-link-45deg text-primary mt-1 flex-shrink-0"></i>
  <div class="min-w-0">
    <div class="text-muted small">URL / Link</div>
    <a href="<?php echo esc_view($url); ?>" target="_blank" rel="noopener noreferrer" class="text-break">
      <?php echo esc_view($url); ?>
    </a>
  </div>
</div>
