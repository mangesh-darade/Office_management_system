<?php $this->load->view('partials/header', ['title' => 'Rewards']); ?>
<div class="container-fluid py-3">
<?php
$levelName = $level ? $level->name : ucfirst($summary->current_level_code);
$levelColor = $level ? $level->badge_color : '#6c757d';
$actions = '<a class="btn btn-outline-primary btn-sm" href="'.site_url('rewards/history').'">History</a> ';
if (function_exists('has_module_access') && (has_module_access('rewards_leaderboard') || has_module_access('rewards'))) {
  $actions .= '<a class="btn btn-outline-primary btn-sm" href="'.site_url('rewards/leaderboard').'">Leaderboard</a> ';
}
if (function_exists('has_module_access') && (has_module_access('rewards_submit') || has_module_access('rewards'))) {
  $actions .= '<a class="btn btn-primary btn-sm" href="'.site_url('rewards/cheer').'"><i class="bi bi-cup-hot me-1"></i>Send Cheer</a> ';
  $actions .= '<a class="btn btn-outline-primary btn-sm" href="'.site_url('rewards/submit-claim').'">Submit Claim</a> ';
  $actions .= '<a class="btn btn-outline-primary btn-sm" href="'.site_url('rewards/office-closing').'">Office Closing</a> ';
}
if (function_exists('has_module_access') && (has_module_access('rewards_approve') || has_module_access('rewards_admin'))) {
  $actions .= '<a class="btn btn-outline-warning btn-sm" href="'.site_url('rewards/approvals').'">Approvals</a> ';
}
$this->load->view('partials/oms_page_head', ['title' => 'Rewards & Recognition', 'icon' => 'bi-trophy', 'subtitle' => 'Your points and level', 'actions_html' => $actions]);
?>
<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card shadow-soft h-100"><div class="card-body text-center"><div class="display-6 fw-bold text-primary"><?php echo number_format((float)$summary->lifetime_points, 0); ?></div><div class="text-muted">Lifetime points</div></div></div></div>
  <div class="col-md-4"><div class="card shadow-soft h-100"><div class="card-body text-center"><div class="h3 fw-bold" style="color:<?php echo esc_view($levelColor); ?>"><?php echo esc_view($levelName); ?></div><div class="text-muted">Current level</div></div></div></div>
  <div class="col-md-4"><div class="card shadow-soft h-100"><div class="card-body text-center"><div class="display-6 fw-bold"><?php echo number_format((float)$summary->month_points, 0); ?></div><div class="text-muted">This month</div></div></div></div>
</div>
<div class="card shadow-soft"><div class="card-header bg-white fw-semibold">Recent activity</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Date</th><th>Rule</th><th>Points</th><th>Reference</th></tr></thead><tbody>
<?php if (empty($recent)): ?><tr><td colspan="4" class="text-muted text-center">No points yet — complete tasks, check in on time, or share knowledge!</td></tr><?php else: foreach ($recent as $t): ?>
<tr><td><?php echo esc_view($t->created_at); ?></td><td><?php echo esc_view($t->rule_name ?: $t->source_event); ?></td><td class="<?php echo ((float)$t->points>=0)?'text-success':'text-danger'; ?>"><?php echo ((float)$t->points>=0?'+':'').number_format((float)$t->points,0); ?></td><td><?php echo esc_view($t->reference_label ?: '—'); ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
