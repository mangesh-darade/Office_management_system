<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('has_module_access') || !has_module_access('subscription_builder')) {
    return;
}

$variant = isset($variant) ? $variant : 'desktop';
$active = isset($active) ? strtolower((string) $active) : '';
$uri = function_exists('uri_string') ? uri_string() : '';
$on_catalog = ($active === 'settings' && strpos($uri, 'subscription-builder') !== false && strpos($uri, 'included-order') === false);
$on_included_order = ($active === 'settings' && strpos($uri, 'subscription-builder/included-order') !== false);
$settings_sales_open = $on_catalog || $on_included_order;

if ($variant === 'mobile'):
?>
<div class="nav-item">
  <a class="nav-link sidebar-link small <?php echo $settings_sales_open ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-settings-sales-submenu" role="button" aria-expanded="<?php echo $settings_sales_open ? 'true' : 'false'; ?>" aria-controls="mobile-settings-sales-submenu">
    <i class="bi bi-cart3 me-2"></i>Sales <i class="bi bi-chevron-down float-end"></i>
  </a>
  <div class="collapse <?php echo $settings_sales_open ? 'show' : ''; ?>" id="mobile-settings-sales-submenu">
    <div class="ps-4">
      <a class="nav-link sidebar-link small <?php echo $on_catalog ? 'active' : ''; ?>" href="<?php echo site_url('settings/subscription-builder'); ?>"><i class="bi bi-sliders me-2"></i>Subscription Builder Catalog</a>
      <a class="nav-link sidebar-link small <?php echo $on_included_order ? 'active' : ''; ?>" href="<?php echo site_url('settings/subscription-builder/included-order'); ?>"><i class="bi bi-sort-down me-2"></i>Included Display Order</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="nav-item settings-nested-menu<?php echo $settings_sales_open ? ' open' : ''; ?>" id="settings-sales-group">
  <a id="settings-sales-parent" class="submenu-link settings-nested-parent-link" href="#" role="button" aria-expanded="<?php echo $settings_sales_open ? 'true' : 'false'; ?>" aria-controls="settings-sales-submenu">
    <span class="settings-nested-row-inner">
      <span><i class="bi bi-cart3 me-2"></i>Sales</span>
      <span class="settings-nested-chevron<?php echo $settings_sales_open ? ' rot' : ''; ?>" id="settings-sales-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
    </span>
  </a>
  <div class="sidebar-submenu" id="settings-sales-submenu" style="display:<?php echo $settings_sales_open ? 'block' : 'none'; ?>;">
    <div class="submenu-list">
      <a class="submenu-link <?php echo $on_catalog ? 'active' : ''; ?>" href="<?php echo site_url('settings/subscription-builder'); ?>"><i class="bi bi-sliders me-2"></i>Subscription Builder Catalog</a>
      <a class="submenu-link <?php echo $on_included_order ? 'active' : ''; ?>" href="<?php echo site_url('settings/subscription-builder/included-order'); ?>"><i class="bi bi-sort-down me-2"></i>Included Display Order</a>
    </div>
  </div>
</div>
<script>initNestedSidebarGroup('settings-sales-toggle','settings-sales-submenu',<?php echo $settings_sales_open ? 'true' : 'false'; ?>,'settings-sales-parent');</script>
<?php endif; ?>
