<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('user_guide_modules')) {
    $this->load->helper('user_guide');
}

$guide_modules = user_guide_modules();
$guide_nav_active = (isset($active) && $active === 'guide');
$guide_sub = isset($active_sub) ? (string) $active_sub : '';
$guide_variant = isset($guide_nav_variant) ? (string) $guide_nav_variant : 'desktop';
$guide_show = (!function_exists('has_module_access') || has_module_access('guide')) && !empty($guide_modules);

if (!$guide_show) {
    return;
}

if ($guide_variant === 'mobile'): ?>
<div class="nav-item">
  <a class="nav-link sidebar-link <?php echo $guide_nav_active ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-guide-submenu" role="button" aria-expanded="<?php echo $guide_nav_active ? 'true' : 'false'; ?>">
    <i class="bi bi-book me-2"></i>User Guide <i class="bi bi-chevron-down float-end"></i>
  </a>
  <div class="collapse <?php echo $guide_nav_active ? 'show' : ''; ?>" id="mobile-guide-submenu">
    <div class="ps-4">
      <a class="nav-link sidebar-link small <?php echo ($guide_nav_active && $guide_sub === '') ? 'active' : ''; ?>" href="<?php echo site_url('guide'); ?>"><i class="bi bi-grid me-2"></i>All Modules</a>
      <?php foreach ($guide_modules as $gm): ?>
      <a class="nav-link sidebar-link small <?php echo ($guide_nav_active && $guide_sub === $gm['slug']) ? 'active' : ''; ?>" href="<?php echo site_url('guide/' . $gm['slug']); ?>">
        <i class="bi <?php echo esc_view($gm['icon']); ?> me-2"></i><?php echo esc_view($gm['id'] . '. ' . $gm['title']); ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php else: ?>
<div class="nav-item" id="guide-group">
  <div class="d-flex align-items-center justify-content-between">
    <a id="guide-parent" class="nav-link sidebar-link flex-grow-1 <?php echo $guide_nav_active ? 'active' : ''; ?>" href="<?php echo site_url('guide'); ?>">
      <i class="bi bi-book me-2"></i>User Guide
    </a>
    <button id="guide-toggle" class="btn btn-sm text-muted" type="button" aria-expanded="false" aria-controls="guide-submenu" title="Toggle">
      <i class="bi bi-chevron-right"></i>
    </button>
  </div>
  <div class="ps-3 sidebar-submenu" id="guide-submenu">
    <div class="submenu-list">
      <a class="submenu-link <?php echo ($guide_nav_active && $guide_sub === '') ? 'active' : ''; ?>" href="<?php echo site_url('guide'); ?>"><i class="bi bi-grid me-1"></i>All Modules</a>
      <?php foreach ($guide_modules as $gm): ?>
      <a class="submenu-link <?php echo ($guide_nav_active && $guide_sub === $gm['slug']) ? 'active' : ''; ?>" href="<?php echo site_url('guide/' . $gm['slug']); ?>">
        <i class="bi <?php echo esc_view($gm['icon']); ?> me-1"></i><?php echo esc_view($gm['id'] . '. ' . $gm['title']); ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>initSidebarGroup('guide-group','guide-toggle','guide-parent','guide-submenu','sb_guide_open',<?php echo $guide_nav_active ? 'true' : 'false'; ?>);</script>
<?php endif; ?>
