<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('has_module_access') || !has_module_access('db')) {
    return;
}

$variant = isset($variant) ? $variant : 'desktop';
$active = isset($active) ? strtolower((string) $active) : '';
$active_sub = isset($active_sub) ? strtolower((string) $active_sub) : '';
$on_db_manager = ($active === 'db' && $active_sub === '');
$on_db_clients = ($active === 'db' && $active_sub === 'clients');
$on_db_migrations = ($active === 'db' && $active_sub === 'client-migrations');
$settings_db_open = $on_db_manager || $on_db_clients || $on_db_migrations;

if ($variant === 'mobile'):
?>
<div class="nav-item">
  <a class="nav-link sidebar-link small <?php echo $settings_db_open ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-settings-db-submenu" role="button" aria-expanded="<?php echo $settings_db_open ? 'true' : 'false'; ?>" aria-controls="mobile-settings-db-submenu">
    <i class="bi bi-database me-2"></i>Database <i class="bi bi-chevron-down float-end"></i>
  </a>
  <div class="collapse <?php echo $settings_db_open ? 'show' : ''; ?>" id="mobile-settings-db-submenu">
    <div class="ps-4">
      <a class="nav-link sidebar-link small <?php echo $on_db_manager ? 'active' : ''; ?>" href="<?php echo site_url('db'); ?>"><i class="bi bi-database me-2"></i>Database Manager</a>
      <a class="nav-link sidebar-link small <?php echo $on_db_clients ? 'active' : ''; ?>" href="<?php echo site_url('db/clients'); ?>"><i class="bi bi-diagram-3 me-2"></i>Client DB Panel</a>
      <a class="nav-link sidebar-link small <?php echo $on_db_migrations ? 'active' : ''; ?>" href="<?php echo site_url('db/client-migrations'); ?>"><i class="bi bi-clock-history me-2"></i>Client DB Migrations</a>
    </div>
  </div>
</div>
<?php else: ?>
<div class="nav-item settings-nested-menu<?php echo $settings_db_open ? ' open' : ''; ?>" id="settings-db-group">
  <a id="settings-db-parent" class="submenu-link settings-nested-parent-link" href="#" role="button" aria-expanded="<?php echo $settings_db_open ? 'true' : 'false'; ?>" aria-controls="settings-db-submenu">
    <span class="settings-nested-row-inner">
      <span><i class="bi bi-database me-2"></i>Database</span>
      <span class="settings-nested-chevron<?php echo $settings_db_open ? ' rot' : ''; ?>" id="settings-db-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
    </span>
  </a>
  <div class="sidebar-submenu" id="settings-db-submenu" style="display:<?php echo $settings_db_open ? 'block' : 'none'; ?>;">
    <div class="submenu-list">
      <a class="submenu-link <?php echo $on_db_manager ? 'active' : ''; ?>" href="<?php echo site_url('db'); ?>"><i class="bi bi-database me-2"></i>Database Manager</a>
      <a class="submenu-link <?php echo $on_db_clients ? 'active' : ''; ?>" href="<?php echo site_url('db/clients'); ?>"><i class="bi bi-diagram-3 me-2"></i>Client DB Panel</a>
      <a class="submenu-link <?php echo $on_db_migrations ? 'active' : ''; ?>" href="<?php echo site_url('db/client-migrations'); ?>"><i class="bi bi-clock-history me-2"></i>Client DB Migrations</a>
    </div>
  </div>
</div>
<script>initNestedSidebarGroup('settings-db-toggle','settings-db-submenu',<?php echo $settings_db_open ? 'true' : 'false'; ?>,'settings-db-parent');</script>
<?php endif; ?>
