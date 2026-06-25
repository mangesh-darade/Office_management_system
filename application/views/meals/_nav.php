<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/** @var string $active_sub meals|calendar|provider|settings|history|all_orders */
$active_sub = isset($active_sub) ? $active_sub : 'meals';
if (!function_exists('meal_has_any_access')) {
    $CI =& get_instance();
    $CI->load->helper('meal');
}
if (!meal_has_any_access()) {
    return;
}

$meal_tabs = array();
foreach (meal_nav_screens() as $screen) {
    $meal_tabs[] = array(
        'key' => $screen['key'],
        'href' => site_url($screen['href']),
        'icon' => $screen['icon'],
        'label' => $screen['label'],
    );
}
if (empty($meal_tabs)) {
    return;
}
?>
<nav class="oms-meals-subnav-wrap mb-3" aria-label="Meals section">
  <ul class="nav nav-pills oms-meals-subnav flex-nowrap">
    <?php foreach ($meal_tabs as $tab): ?>
    <li class="nav-item">
      <a class="nav-link <?php echo $active_sub === $tab['key'] ? 'active' : ''; ?>" href="<?php echo esc_view($tab['href']); ?>">
        <i class="bi <?php echo esc_view($tab['icon']); ?> me-1"></i><?php echo esc_view($tab['label']); ?>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
</nav>
