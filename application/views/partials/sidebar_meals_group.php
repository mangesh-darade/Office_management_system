<?php
defined('BASEPATH') OR exit('No direct script access allowed');
if (!function_exists('meal_has_any_access')) {
    $CI =& get_instance();
    $CI->load->helper('meal');
}
if (!meal_has_any_access()) {
    return;
}

$variant = isset($variant) ? $variant : 'desktop';
$active = isset($active) ? strtolower((string) $active) : '';
$active_sub = isset($active_sub) ? strtolower((string) $active_sub) : '';
$meal_screens = meal_nav_screens();
if (empty($meal_screens)) {
    return;
}

$meals_group_open = ($active === 'meals');
$meal_active_key = meal_nav_active_key($active, $active_sub);
$meal_parent_href = meal_default_route();

if ($variant === 'mobile'):
?>
<a class="nav-link sidebar-link" data-bs-toggle="collapse" href="#mobile-meals-submenu" role="button" aria-expanded="<?php echo $meals_group_open ? 'true' : 'false'; ?>">
  <i class="bi bi-cup-hot me-2"></i>Office Meals
</a>
<div class="collapse <?php echo $meals_group_open ? 'show' : ''; ?>" id="mobile-meals-submenu">
  <div class="ps-3">
    <?php foreach ($meal_screens as $screen): ?>
    <a class="nav-link sidebar-link small <?php echo $meal_active_key === $screen['key'] ? 'active' : ''; ?>" href="<?php echo site_url($screen['href']); ?>">
      <i class="bi <?php echo esc_view($screen['icon']); ?> me-2"></i><?php echo esc_view($screen['label']); ?>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php else: ?>
<div class="nav-item" id="meals-group">
  <a id="meals-parent" class="nav-link sidebar-link sidebar-group-parent" href="<?php echo site_url($meal_parent_href); ?>">
    <span class="sidebar-group-row-inner">
      <span><i class="bi bi-cup-hot me-2"></i>Office Meals</span>
      <span class="sidebar-group-chevron" id="meals-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
    </span>
  </a>
  <div class="sidebar-submenu" id="meals-submenu">
    <div class="submenu-list">
      <?php foreach ($meal_screens as $screen): ?>
      <a class="submenu-link <?php echo $meal_active_key === $screen['key'] ? 'active' : ''; ?>" href="<?php echo site_url($screen['href']); ?>">
        <i class="bi <?php echo esc_view($screen['icon']); ?> me-1"></i><?php echo esc_view($screen['label']); ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<script>initSidebarGroup('meals-group','meals-toggle','meals-parent','meals-submenu','sb_meals_open',<?php echo $meals_group_open ? 'true' : 'false'; ?>);</script>
<?php endif; ?>
