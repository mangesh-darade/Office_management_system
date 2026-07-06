<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$has_leave_types = function_exists('has_module_access') && (
    has_module_access('leave_types') || has_module_access('settings') || has_module_access('admin')
);
$has_holidays = function_exists('has_module_access') && (
    has_module_access('holidays') || has_module_access('settings') || has_module_access('admin')
);

if (!$has_leave_types && !$has_holidays) {
    return;
}

$variant = isset($variant) ? $variant : 'desktop';
$active = isset($active) ? strtolower((string) $active) : '';
$uri = function_exists('uri_string') ? uri_string() : '';
$on_leave_types = ($active === 'settings' && strpos($uri, 'leave-types') !== false);
$on_holidays = ($active === 'settings' && strpos($uri, 'holidays') !== false);
$settings_leave_open = $on_leave_types || $on_holidays;

if ($variant === 'mobile'):
?>
<div class="nav-item">
  <a class="nav-link sidebar-link small <?php echo $settings_leave_open ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-settings-leave-submenu" role="button" aria-expanded="<?php echo $settings_leave_open ? 'true' : 'false'; ?>" aria-controls="mobile-settings-leave-submenu">
    <i class="bi bi-calendar2-range me-2"></i>Leave Setup <i class="bi bi-chevron-down float-end"></i>
  </a>
  <div class="collapse <?php echo $settings_leave_open ? 'show' : ''; ?>" id="mobile-settings-leave-submenu">
    <div class="ps-4">
      <?php if ($has_leave_types): ?>
      <a class="nav-link sidebar-link small <?php echo $on_leave_types ? 'active' : ''; ?>" href="<?php echo site_url('settings/leave-types'); ?>"><i class="bi bi-calendar-x me-2"></i>Leave Types</a>
      <?php endif; ?>
      <?php if ($has_holidays): ?>
      <a class="nav-link sidebar-link small <?php echo $on_holidays ? 'active' : ''; ?>" href="<?php echo site_url('settings/holidays'); ?>"><i class="bi bi-calendar-event me-2"></i>Holidays</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php else: ?>
<div class="nav-item settings-nested-menu<?php echo $settings_leave_open ? ' open' : ''; ?>" id="settings-leave-group">
  <a id="settings-leave-parent" class="submenu-link settings-nested-parent-link" href="#" role="button" aria-expanded="<?php echo $settings_leave_open ? 'true' : 'false'; ?>" aria-controls="settings-leave-submenu">
    <span class="settings-nested-row-inner">
      <span><i class="bi bi-calendar2-range me-2"></i>Leave Setup</span>
      <span class="settings-nested-chevron<?php echo $settings_leave_open ? ' rot' : ''; ?>" id="settings-leave-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
    </span>
  </a>
  <div class="sidebar-submenu" id="settings-leave-submenu" style="display:<?php echo $settings_leave_open ? 'block' : 'none'; ?>;">
    <div class="submenu-list">
      <?php if ($has_leave_types): ?>
      <a class="submenu-link <?php echo $on_leave_types ? 'active' : ''; ?>" href="<?php echo site_url('settings/leave-types'); ?>"><i class="bi bi-calendar-x me-2"></i>Leave Types</a>
      <?php endif; ?>
      <?php if ($has_holidays): ?>
      <a class="submenu-link <?php echo $on_holidays ? 'active' : ''; ?>" href="<?php echo site_url('settings/holidays'); ?>"><i class="bi bi-calendar-event me-2"></i>Holidays</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>initNestedSidebarGroup('settings-leave-toggle','settings-leave-submenu',<?php echo $settings_leave_open ? 'true' : 'false'; ?>,'settings-leave-parent');</script>
<?php endif; ?>
