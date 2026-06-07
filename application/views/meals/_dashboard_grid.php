<?php
/**
 * Office Meals — dashboard announcement item (Today + Tomorrow).
 * @var array $meal_dashboard from meal_dashboard_today_tomorrow()
 */
if (empty($meal_dashboard)) {
    return;
}
$can_order = isset($can_order) ? (bool) $can_order : (function_exists('meal_can_order') && meal_can_order());
$can_provider = isset($can_provider) ? (bool) $can_provider : (function_exists('meal_can_access') && meal_can_access('meals_provider'));
$mealsUrl = site_url('meals');
$providerUrl = site_url('meals/provider');
?>
<article class="announcement-item announcement-item--meals">
  <div class="announcement-item__icon announcement-item__icon--meals" aria-hidden="true">
    <i class="bi bi-cup-hot"></i>
  </div>
  <div class="announcement-item__body">
    <div class="announcement-item__head">
      <div class="min-w-0">
        <h6 class="announcement-item__title mb-0">Office Meals</h6>
        <span class="announcement-item__meta">Today &amp; tomorrow schedule</span>
      </div>
      <?php if ($can_order): ?>
      <a class="btn btn-danger btn-sm announcement-item__action" href="<?php echo htmlspecialchars($mealsUrl); ?>">
        <i class="bi bi-arrow-right-circle me-1"></i><span>Order meals</span>
      </a>
      <?php elseif ($can_provider): ?>
      <a class="btn btn-outline-danger btn-sm announcement-item__action" href="<?php echo htmlspecialchars($providerUrl); ?>">
        <i class="bi bi-truck me-1"></i><span>Provider</span>
      </a>
      <?php endif; ?>
    </div>
    <div class="meal-dashboard-days row g-2 g-sm-3">
      <?php foreach (array('today', 'tomorrow') as $slot):
        $c = $meal_dashboard[$slot];
        $isToday = ($slot === 'today');
      ?>
      <div class="col-12 col-sm-6">
        <div class="meal-dashboard-day h-100<?php echo $isToday ? ' is-today' : ' is-tomorrow'; ?>">
          <div class="meal-dashboard-day-head">
            <span class="meal-dashboard-day-label"><?php echo htmlspecialchars($c['day_label']); ?></span>
            <span class="meal-dashboard-day-date"><?php echo htmlspecialchars($c['date_display']); ?></span>
          </div>
          <?php if (!$c['has_any']): ?>
            <div class="meal-dashboard-none"><i class="bi bi-x-circle me-1"></i>No meals scheduled</div>
          <?php else: ?>
            <?php if ($c['has_breakfast']): ?>
            <div class="meal-dashboard-meal">
              <div class="meal-dashboard-meal-row">
                <i class="bi bi-sunrise text-warning"></i>
                <div>
                  <strong>Breakfast</strong>
                  <?php if ($c['breakfast_note'] !== ''): ?>
                    <span class="meal-dashboard-meal-note d-block"><?php echo htmlspecialchars($c['breakfast_note']); ?></span>
                  <?php endif; ?>
                  <span class="meal-dashboard-meal-cutoff">Order by <?php echo htmlspecialchars($c['bf_cutoff']); ?></span>
                </div>
              </div>
            </div>
            <?php endif; ?>
            <?php if ($c['has_lunch']): ?>
            <div class="meal-dashboard-meal">
              <div class="meal-dashboard-meal-row">
                <i class="bi bi-egg-fried text-primary"></i>
                <div>
                  <strong>Lunch</strong>
                  <?php if ($c['lunch_note'] !== ''): ?>
                    <span class="meal-dashboard-meal-note d-block"><?php echo htmlspecialchars($c['lunch_note']); ?></span>
                  <?php endif; ?>
                  <span class="meal-dashboard-meal-cutoff">Order by <?php echo htmlspecialchars($c['lu_cutoff']); ?></span>
                </div>
              </div>
            </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</article>
