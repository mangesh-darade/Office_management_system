<?php $this->load->view('partials/header', ['title' => 'Meal Provider', 'active' => 'meals']); ?>
<div class="container-fluid py-3 oms-fluid-pad">
<?php
$bfCut = substr($settings['breakfast_cutoff'], 0, 5);
$luCut = substr($settings['lunch_cutoff'], 0, 5);
$today = date('Y-m-d');
$cal = $counts['calendar'];
$hasBf = ($cal && (int)$cal->has_breakfast === 1) || (int)$counts['breakfast_orders'] > 0;
$hasLu = ($cal && (int)$cal->has_lunch === 1) || (int)$counts['lunch_orders'] > 0;
$bfLocked = meal_is_breakfast_locked($date, $settings) && $hasBf;
$luLocked = meal_is_lunch_locked($date, $settings) && $hasLu;
$anyLocked = $bfLocked || $luLocked;

$actions = '';
if (function_exists('meal_can_export') && meal_can_export()) {
  $actions .= '<a class="btn btn-outline-primary btn-sm" href="'.site_url('meals/export?date='.urlencode($date)).'"><i class="bi bi-download me-1"></i>Export CSV</a>';
}
$this->load->view('partials/oms_page_head', [
  'title' => 'Meal Provider',
  'icon' => 'bi-truck',
  'subtitle' => 'Breakfast plate counts and lunch tiffin counts show after cut-off (locked).',
  'actions_html' => $actions,
]);
$this->load->view('meals/_nav', ['active_sub' => 'provider']);
$pending_requests = isset($pending_requests) ? $pending_requests : array();
$pending_map = isset($pending_map) ? $pending_map : array();
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div><?php endif; ?>
<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <form method="get" class="card shadow-soft"><div class="card-body">
      <label class="form-label">Select date</label>
      <input type="date" name="date" class="form-control mb-2" value="<?php echo esc_view($date); ?>" onchange="this.form.submit()">
      <input type="hidden" name="month" value="<?php echo esc_view($month); ?>">
    </div></form>
  </div>
  <div class="col-lg-8">
    <div class="row g-2">
      <div class="col-6 col-md-4">
        <div class="card shadow-soft text-center h-100">
          <div class="card-body">
            <?php if ($hasBf && $bfLocked): ?>
              <div class="h3 mb-0"><?php echo (int)$counts['breakfast_plates']; ?></div>
              <div class="text-muted small">Breakfast plates</div>
              <div class="badge bg-secondary mt-1">Locked</div>
            <?php elseif ($hasBf): ?>
              <div class="h3 mb-0 text-muted">—</div>
              <div class="text-muted small">Breakfast</div>
              <div class="badge bg-warning text-dark mt-1">Open until <?php echo esc_view($bfCut); ?></div>
            <?php else: ?>
              <div class="h3 mb-0 text-muted">—</div>
              <div class="text-muted small">No breakfast</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="card shadow-soft text-center h-100">
          <div class="card-body">
            <?php if ($hasLu && $luLocked): ?>
              <div class="h3 mb-0"><?php echo (int)$counts['lunch_half']; ?></div>
              <div class="text-muted small">Half tiffin</div>
              <div class="badge bg-secondary mt-1">Locked</div>
            <?php elseif ($hasLu): ?>
              <div class="h3 mb-0 text-muted">—</div>
              <div class="text-muted small">Half tiffin</div>
              <div class="badge bg-warning text-dark mt-1">Open until <?php echo esc_view($luCut); ?></div>
            <?php else: ?>
              <div class="h3 mb-0 text-muted">—</div>
              <div class="text-muted small">No lunch</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4">
        <div class="card shadow-soft text-center h-100">
          <div class="card-body">
            <?php if ($hasLu && $luLocked): ?>
              <div class="h3 mb-0"><?php echo (int)$counts['lunch_full']; ?></div>
              <div class="text-muted small">Full tiffin</div>
            <?php elseif ($hasLu): ?>
              <div class="h3 mb-0 text-muted">—</div>
              <div class="text-muted small">Full tiffin</div>
            <?php else: ?>
              <div class="h3 mb-0 text-muted">—</div>
              <div class="text-muted small">—</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <?php if ($date === $today && $anyLocked): ?>
      <div class="alert alert-success mt-2 mb-0 py-2 small"><i class="bi bi-lock-fill me-1"></i>Locked orders for today — counts below are final for closed meal slots.</div>
    <?php elseif ($date !== $today): ?>
      <div class="alert alert-secondary mt-2 mb-0 py-2 small">Viewing <strong><?php echo date('D, d M Y', strtotime($date)); ?></strong>. Counts appear after each meal cut-off on that date.</div>
    <?php endif; ?>
    <?php if (!$cal || ((int)$cal->has_breakfast === 0 && (int)$cal->has_lunch === 0)): ?>
      <?php if (!$hasBf && !$hasLu): ?>
      <div class="alert alert-warning mt-2 mb-0">No meals scheduled for <?php echo date('D, d M Y', strtotime($date)); ?>.</div>
      <?php endif; ?>
    <?php elseif ($cal): ?>
      <div class="alert alert-info mt-2 mb-0">
        <?php if ((int)$cal->has_breakfast): ?>Breakfast<?php echo $cal->breakfast_note ? ': '.esc_view($cal->breakfast_note) : ''; ?>. <?php endif; ?>
        <?php if ((int)$cal->has_lunch): ?>Lunch<?php echo $cal->lunch_note ? ': '.esc_view($cal->lunch_note) : ''; ?>.<?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card shadow-soft mb-4">
  <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
    <span><i class="bi bi-inbox me-1"></i>Change requests (after lock)</span>
    <?php if (!empty($pending_requests)): ?>
    <span class="badge bg-warning text-dark"><?php echo count($pending_requests); ?> pending</span>
    <?php endif; ?>
  </div>
  <?php if (!empty($pending_requests)): ?>
  <div class="table-responsive">
    <table class="table table-sm mb-0 align-middle">
      <thead>
        <tr>
          <th>Employee</th>
          <th>Meal</th>
          <th>Change (current → requested)</th>
          <th>Note</th>
          <th style="min-width:12rem">Approve / Reject</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($pending_requests as $req): ?>
        <tr>
          <td><?php echo esc_view($req->user_name); ?></td>
          <td><?php echo $req->meal_type === 'lunch' ? 'Lunch' : 'Breakfast'; ?></td>
          <td>
            <span class="badge bg-light text-dark border"><?php echo esc_view(meal_format_request_value($req->meal_type, $req->current_value)); ?></span>
            <i class="bi bi-arrow-right mx-1 text-primary"></i>
            <span class="badge bg-primary"><?php echo esc_view(meal_format_request_value($req->meal_type, $req->requested_value)); ?></span>
          </td>
          <td class="small text-muted"><?php echo !empty($req->employee_note) ? esc_view($req->employee_note) : '—'; ?></td>
          <td>
            <form method="post" action="<?php echo site_url('meals/review_request'); ?>" class="d-flex flex-column gap-1">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <input type="hidden" name="request_id" value="<?php echo (int) $req->id; ?>">
              <input type="hidden" name="date" value="<?php echo esc_view($date); ?>">
              <input type="hidden" name="month" value="<?php echo esc_view($month); ?>">
              <input type="text" name="review_note" class="form-control form-control-sm" maxlength="255" placeholder="Optional note to employee">
              <div class="btn-group btn-group-sm">
                <button type="submit" name="decision" value="approved" class="btn btn-success"><i class="bi bi-check-lg"></i> Approve</button>
                <button type="submit" name="decision" value="rejected" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i> Reject</button>
              </div>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="card-body py-3 small text-muted">
    <p class="mb-1">No pending change requests for <?php echo date('D, d M Y', strtotime($date)); ?>.</p>
    <p class="mb-0">After a meal is <strong>locked</strong>, employees can request a change from <a href="<?php echo site_url('meals'); ?>">My Orders</a>. Those requests appear here with <strong>Approve</strong> / <strong>Reject</strong> buttons.</p>
  </div>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card shadow-soft"><div class="card-header bg-white fw-semibold"><?php echo date('F Y', strtotime($month.'-01')); ?> — availability</div><div class="card-body p-2">
      <div class="d-flex flex-wrap gap-1">
      <?php
      $c = strtotime($month.'-01');
      $end = strtotime(date('Y-m-t', $c));
      $calByDate = array();
      foreach ($calendar as $x) { $calByDate[$x->meal_date] = $x; }
      while ($c <= $end):
        $d = date('Y-m-d', $c);
        $x = isset($calByDate[$d]) ? $calByDate[$d] : null;
        $has = $x && ((int)$x->has_breakfast || (int)$x->has_lunch);
        $cls = $has ? 'bg-success text-white' : 'bg-light text-muted';
        if ($d === $date) { $cls = 'bg-primary text-white'; }
      ?>
        <a href="<?php echo site_url('meals/provider?date='.$d.'&month='.$month); ?>" class="badge <?php echo $cls; ?> text-decoration-none p-2" style="min-width:2.2rem"><?php echo (int)date('j', $c); ?></a>
      <?php $c = strtotime('+1 day', $c); endwhile; ?>
      </div>
      <div class="small text-muted mt-2"><span class="badge bg-success">&nbsp;</span> Meals &nbsp; <span class="badge bg-light border">&nbsp;</span> None &nbsp; <span class="badge bg-primary">&nbsp;</span> Selected</div>
    </div></div>
  </div>
  <div class="col-lg-7">
    <div class="card shadow-soft"><div class="card-header bg-white fw-semibold">Orders for <?php echo date('D, d M Y', strtotime($date)); ?></div>
    <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Employee</th><th>Breakfast plates</th><th>Lunch tiffin</th></tr></thead><tbody>
    <?php if (!$anyLocked): ?>
      <tr><td colspan="3" class="text-muted text-center py-3">Orders still open for this date — counts show after breakfast (<?php echo esc_view($bfCut); ?>) and/or lunch (<?php echo esc_view($luCut); ?>) cut-off.</td></tr>
    <?php elseif (empty($counts['orders'])): ?>
      <tr><td colspan="3" class="text-muted text-center py-3">No orders recorded for locked meal slot(s) on this date.</td></tr>
    <?php else:
      $orderUserIds = array();
      foreach ($counts['orders'] as $o) { $orderUserIds[(int)$o->user_id] = true; }
      foreach ($pending_map as $puid => $pinfo) {
        if (!isset($orderUserIds[$puid])) {
          $counts['orders'][] = (object) array(
            'user_id' => $puid,
            'user_name' => isset($pinfo['user_name']) ? $pinfo['user_name'] : 'Employee',
            'breakfast_plates' => 0,
            'lunch_tiffin' => '',
            'want_breakfast' => 0,
            'lunch_plates' => 0,
          );
        }
      }
      foreach ($counts['orders'] as $o):
      $bf = meal_order_total_breakfast_plates($o);
      $ltMain = meal_order_lunch_tiffin($o);
      $ltAdd = meal_order_additional_lunch_tiffin($o);
      $uid = (int) $o->user_id;
      $bfPending = isset($pending_map[$uid]['breakfast']) ? $pending_map[$uid]['breakfast'] : null;
      $luPending = isset($pending_map[$uid]['lunch']) ? $pending_map[$uid]['lunch'] : null;
    ?>
      <tr>
        <td><?php echo esc_view($o->user_name); ?></td>
        <td><?php
          if (!$hasBf) { echo '—'; }
          elseif (!$bfLocked) { echo '<span class="text-muted" title="Cut-off not reached yet">Open</span>'; }
          elseif ($bfPending) {
            echo '<span class="text-muted">' . esc_view(meal_format_request_value('breakfast', $bfPending->current_value)) . '</span>';
            echo ' <i class="bi bi-arrow-right small text-primary"></i> ';
            echo '<strong class="text-primary">' . esc_view(meal_format_request_value('breakfast', $bfPending->requested_value)) . '</strong>';
            echo ' <span class="badge bg-warning text-dark ms-1">Pending</span>';
          }
          else { echo $bf > 0 ? esc_view(meal_order_breakfast_display($o)) : '0'; }
        ?></td>
        <td><?php
          if (!$hasLu) { echo '—'; }
          elseif (!$luLocked) { echo '<span class="text-muted" title="Cut-off not reached yet">Open</span>'; }
          elseif ($luPending) {
            echo '<span class="text-muted">' . esc_view(meal_format_request_value('lunch', $luPending->current_value)) . '</span>';
            echo ' <i class="bi bi-arrow-right small text-primary"></i> ';
            echo '<strong class="text-primary">' . esc_view(meal_format_request_value('lunch', $luPending->requested_value)) . '</strong>';
            echo ' <span class="badge bg-warning text-dark ms-1">Pending</span>';
          }
          else {
            echo ($ltMain !== '' || $ltAdd !== '') ? esc_view(meal_order_lunch_display($o)) : '—';
          }
        ?></td>
      </tr>
    <?php endforeach; endif; ?>
    </tbody></table></div></div>
  </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
