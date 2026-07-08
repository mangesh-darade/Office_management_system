<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$variant = isset($variant) ? $variant : 'desktop';
$active = isset($active) ? strtolower((string) $active) : '';
$active_sub = isset($active_sub) ? strtolower((string) $active_sub) : '';

$has_subscription_builder = function_exists('has_module_access') && (
    has_module_access('subscription_builder') || has_module_access('subscription_builder_list')
);
$has_elintom_proposals = function_exists('has_module_access') && (
    has_module_access('elintom_proposals') || has_module_access('elintom_proposals_list')
);
$has_eba_platform = function_exists('has_module_access') && (
    has_module_access('eba_platform') || has_module_access('eba_platform_list')
);

if (!$has_subscription_builder && !$has_elintom_proposals && !$has_eba_platform) {
    return;
}

$sales_nav_active = in_array($active, array('subscription-builder', 'elintom-proposals', 'eba-platform'), true);
$sales_parent_href = site_url('subscription-builder');
if (!$has_subscription_builder && $has_elintom_proposals) {
    $sales_parent_href = site_url('elintom-proposals');
} elseif (!$has_subscription_builder && !$has_elintom_proposals && $has_eba_platform) {
    $sales_parent_href = site_url('eba-platform');
}

if ($variant === 'mobile'):
?>
<div class="nav-item">
  <a class="nav-link sidebar-link <?php echo $sales_nav_active ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-sales-submenu" role="button" aria-expanded="<?php echo $sales_nav_active ? 'true' : 'false'; ?>" aria-controls="mobile-sales-submenu">
    <i class="bi bi-cart3 me-2"></i>Sales <i class="bi bi-chevron-down float-end"></i>
  </a>
  <div class="collapse <?php echo $sales_nav_active ? 'show' : ''; ?>" id="mobile-sales-submenu">
    <div class="ps-4">
      <?php if ($has_subscription_builder): ?>
      <a class="nav-link sidebar-link small <?php echo $active === 'subscription-builder' ? 'active' : ''; ?>" href="<?php echo site_url('subscription-builder'); ?>"><i class="bi bi-sliders me-2"></i>Subscription Builder</a>
      <?php endif; ?>
      <?php if ($has_elintom_proposals): ?>
      <a class="nav-link sidebar-link small <?php echo $active === 'elintom-proposals' ? 'active' : ''; ?>" href="<?php echo site_url('elintom-proposals'); ?>"><i class="bi bi-file-earmark-text me-2"></i>ElintOm Proposals</a>
      <?php endif; ?>
      <?php if ($has_eba_platform): ?>
      <a class="nav-link sidebar-link small <?php echo $active === 'eba-platform' ? 'active' : ''; ?>" href="<?php echo site_url('eba-platform'); ?>"><i class="bi bi-graph-up-arrow me-2"></i>EBA Platform</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php else: ?>
<div class="nav-item" id="sales-group">
  <a id="sales-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo $sales_nav_active ? 'active' : ''; ?>" href="<?php echo $sales_parent_href; ?>">
    <span class="sidebar-group-row-inner">
      <span><i class="bi bi-cart3 me-2"></i>Sales</span>
      <span class="sidebar-group-chevron" id="sales-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
    </span>
  </a>
  <div class="sidebar-submenu" id="sales-submenu">
    <div class="submenu-list">
      <?php if ($has_subscription_builder): ?>
      <a class="submenu-link <?php echo $active === 'subscription-builder' ? 'active' : ''; ?>" href="<?php echo site_url('subscription-builder'); ?>"><i class="bi bi-sliders me-2"></i>Subscription Builder</a>
      <?php endif; ?>
      <?php if ($has_elintom_proposals): ?>
      <a class="submenu-link <?php echo $active === 'elintom-proposals' ? 'active' : ''; ?>" href="<?php echo site_url('elintom-proposals'); ?>"><i class="bi bi-file-earmark-text me-2"></i>ElintOm Proposals</a>
      <?php endif; ?>
      <?php if ($has_eba_platform): ?>
      <a class="submenu-link <?php echo $active === 'eba-platform' ? 'active' : ''; ?>" href="<?php echo site_url('eba-platform'); ?>"><i class="bi bi-graph-up-arrow me-2"></i>EBA Platform</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>initSidebarGroup('sales-group','sales-toggle','sales-parent','sales-submenu','sb_sales_open',<?php echo $sales_nav_active ? 'true' : 'false'; ?>);</script>
<?php endif; ?>
