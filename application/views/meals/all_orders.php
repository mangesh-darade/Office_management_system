<?php $this->load->view('partials/header', ['title' => 'All Meal Orders', 'active' => 'meals']); ?>
<div class="container-fluid py-3 oms-fluid-pad">
<?php
$bfCut = substr($settings['breakfast_cutoff'], 0, 5);
$luCut = substr($settings['lunch_cutoff'], 0, 5);
$today = date('Y-m-d');
$cal = isset($calendar) ? $calendar : null;
$hasBfDay = $cal && (int) $cal->has_breakfast === 1;
$hasLuDay = $cal && (int) $cal->has_lunch === 1;
$bfLocked = meal_is_breakfast_locked($date, $settings) && $hasBfDay;
$luLocked = meal_is_lunch_locked($date, $settings) && $hasLuDay;

$actions = '';
if (function_exists('meal_can_export') && meal_can_export()) {
    $actions .= '<a class="btn btn-outline-primary btn-sm" href="' . site_url('meals/export?date=' . urlencode($date)) . '"><i class="bi bi-download me-1"></i>Export CSV</a>';
}
$this->load->view('partials/oms_page_head', [
    'title' => 'All Meal Orders',
    'icon' => 'bi-list-check',
    'subtitle' => 'Every employee\'s breakfast and lunch order for the selected date.',
    'actions_html' => $actions,
]);
$this->load->view('meals/_nav', ['active_sub' => 'all_orders']);
?>
<form method="get" class="row g-2 align-items-end mb-3">
  <div class="col-auto">
    <label class="form-label small mb-0">Date</label>
    <input type="date" name="date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($date); ?>">
  </div>
  <div class="col-auto">
    <div class="form-check mt-4">
      <input class="form-check-input" type="checkbox" name="only" value="1" id="onlyWithOrders" <?php echo !empty($only_with_orders) ? 'checked' : ''; ?>>
      <label class="form-check-label small" for="onlyWithOrders">Only employees with orders</label>
    </div>
  </div>
  <div class="col-auto">
    <button type="submit" class="btn btn-sm btn-primary">Show</button>
  </div>
  <?php if ($date !== $today): ?>
  <div class="col-auto">
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('meals/all_orders?date=' . urlencode($today) . (!empty($only_with_orders) ? '&only=1' : '')); ?>">Today</a>
  </div>
  <?php endif; ?>
</form>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="card shadow-soft h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Breakfast plates</div>
        <div class="fs-4 fw-bold"><?php echo (int) $counts['breakfast_plates']; ?></div>
        <div class="small text-muted"><?php echo (int) $counts['breakfast_orders']; ?> employee(s)</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-soft h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Lunch half tiffin</div>
        <div class="fs-4 fw-bold"><?php echo (int) $counts['lunch_half']; ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card shadow-soft h-100">
      <div class="card-body py-3">
        <div class="small text-muted">Lunch full tiffin</div>
        <div class="fs-4 fw-bold"><?php echo (int) $counts['lunch_full']; ?></div>
      </div>
    </div>
  </div>
</div>

<?php if ($cal): ?>
<div class="alert alert-info py-2 small mb-3">
  <?php echo date('l, d M Y', strtotime($date)); ?> —
  <?php if ($hasBfDay): ?>Breakfast<?php echo $cal->breakfast_note ? ': ' . htmlspecialchars($cal->breakfast_note) : ''; ?>. <?php else: ?>No breakfast scheduled. <?php endif; ?>
  <?php if ($hasLuDay): ?>Lunch<?php echo $cal->lunch_note ? ': ' . htmlspecialchars($cal->lunch_note) : ''; ?>.<?php else: ?>No lunch scheduled.<?php endif; ?>
  Cut-offs: breakfast <?php echo htmlspecialchars($bfCut); ?>, lunch <?php echo htmlspecialchars($luCut); ?>.
</div>
<?php else: ?>
<div class="alert alert-secondary py-2 small mb-3">No meal calendar entry for this date.</div>
<?php endif; ?>

<div class="card shadow-soft">
  <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span>Orders — <?php echo date('D, d M Y', strtotime($date)); ?></span>
    <span class="badge bg-secondary"><?php echo count($rows); ?> row(s)</span>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-hover mb-0 align-middle">
      <thead>
        <tr>
          <th>Employee</th>
          <th>Breakfast</th>
          <th>Lunch</th>
          <th>Last updated</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="4" class="text-muted text-center py-4">No records for this filter.</td></tr>
      <?php else: foreach ($rows as $row):
        $bf = meal_order_total_breakfast_plates($row);
        $ltMain = meal_order_lunch_tiffin($row);
        $ltAdd = meal_order_additional_lunch_tiffin($row);
        $hasOrder = $bf > 0 || $ltMain !== '' || $ltAdd !== '';
      ?>
        <tr class="<?php echo $hasOrder ? '' : 'text-muted'; ?>">
          <td><?php echo htmlspecialchars($row->user_name); ?></td>
          <td>
            <?php if ($bf > 0): ?>
              <span class="badge bg-warning text-dark"><?php echo htmlspecialchars(meal_order_breakfast_display($row)); ?></span>
              <?php if ($bfLocked && !empty($row->breakfast_locked_at)): ?><i class="bi bi-lock-fill small ms-1" title="Locked"></i><?php endif; ?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($ltMain !== '' || $ltAdd !== ''): ?>
              <span class="badge bg-primary"><?php echo htmlspecialchars(meal_order_lunch_display($row)); ?></span>
              <?php if ($luLocked && !empty($row->lunch_locked_at)): ?><i class="bi bi-lock-fill small ms-1" title="Locked"></i><?php endif; ?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted">
            <?php echo !empty($row->updated_at) ? date('g:i A', strtotime($row->updated_at)) : '—'; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
