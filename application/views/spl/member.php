<?php
$this->load->view('partials/header', array(
    'title' => 'SPL — ' . $display_name,
    'extra_css' => array('assets/css/spl.css'),
));

$from = $this->input->get('from');
$from = ($from === 'groups') ? 'groups' : '';
$level_name = $level ? (string) $level->name : 'Starter';
$level_color = $level && !empty($level->badge_color) ? (string) $level->badge_color : '#6c757d';
$period_label = $reward_bounds['label'];
$reward_period_options = spl_reward_period_options();
$activity_table_ctx = spl_prepare_activity_table_context(!empty($activities) ? $activities : array());
$activity_payloads = $activity_table_ctx['payloads'];
$activities = $activity_table_ctx['activities'];
?>

<div class="container-fluid py-3 spl-page spl-member-page">
  <div class="spl-member-hero card border-0 shadow-sm">
    <div class="spl-member-hero-top">
      <a href="<?php echo esc_view($back_url, ENT_QUOTES, 'UTF-8'); ?>" class="spl-member-back">
        <i class="bi bi-arrow-left"></i> <?php echo esc_view($back_label, ENT_QUOTES, 'UTF-8'); ?>
      </a>
    </div>

    <div class="spl-member-hero-main">
      <div class="spl-member-identity">
        <?php if (!empty($avatar_url)): ?>
        <img src="<?php echo esc_view($avatar_url, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="spl-member-avatar-img">
        <?php else: ?>
        <span class="spl-member-avatar"><?php echo esc_view($initials, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <div>
          <h1 class="spl-member-name mb-1">
            <?php echo esc_view($display_name, ENT_QUOTES, 'UTF-8'); ?>
            <?php if (!empty($is_self)): ?>
            <span class="spl-member-you-tag">You</span>
            <?php endif; ?>
          </h1>
          <div class="spl-member-meta">
            <span class="spl-board-level-pill" style="--spl-level-color: <?php echo esc_view($level_color, ENT_QUOTES, 'UTF-8'); ?>;">
              <?php echo esc_view($level_name, ENT_QUOTES, 'UTF-8'); ?>
            </span>
            <?php if (!empty($member_groups)): ?>
            <span class="spl-member-groups">
              <?php
              $group_names = array();
              foreach ($member_groups as $g) {
                  $group_names[] = (string) $g->name;
              }
              echo esc_view(implode(' · ', $group_names), ENT_QUOTES, 'UTF-8');
              ?>
            </span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="spl-member-lifetime">
        <span class="spl-member-lifetime-label">Lifetime</span>
        <span class="spl-member-lifetime-value"><?php echo number_format((float) $summary->lifetime_points, 0); ?> pts</span>
      </div>
    </div>
  </div>

  <div class="spl-member-period-bar card border-0 shadow-sm mt-3">
    <div class="spl-unified-period-bar-inner">
      <div class="spl-period-filter-heading">
        <span class="spl-period-filter-heading__icon" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
        <div class="spl-period-filter-heading__text">
          <span class="spl-period-filter-heading__label">Activity period</span>
          <span class="spl-period-filter-heading__value"><?php echo esc_view($period_label, ENT_QUOTES, 'UTF-8'); ?></span>
          <span class="spl-period-filter-heading__note">Approved points count toward net score</span>
        </div>
      </div>
      <div class="spl-period-filter" role="group" aria-label="Activity period">
        <div class="spl-period-filter__track">
          <?php foreach ($reward_period_options as $periodKey => $periodOptionLabel): ?>
          <?php
          $period_params = array('reward_period' => $periodKey);
          if ($from !== '') {
              $period_params['from'] = $from;
          }
          ?>
          <a
            href="<?php echo esc_view(spl_member_url((int) $member->id, $period_params), ENT_QUOTES, 'UTF-8'); ?>"
            class="spl-period-filter__btn<?php echo $reward_period === $periodKey ? ' is-active' : ''; ?>"
            aria-pressed="<?php echo $reward_period === $periodKey ? 'true' : 'false'; ?>"
          ><?php echo esc_view($periodOptionLabel, ENT_QUOTES, 'UTF-8'); ?></a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="spl-member-summary card border-0 shadow-sm mt-3">
    <div class="spl-reward-calc-bar spl-reward-calc-bar--member">
      <div class="spl-reward-calc-item is-positive">
        <span class="spl-reward-calc-label"><i class="bi bi-plus-circle me-1"></i>Earned</span>
        <span class="spl-reward-calc-value">+<?php echo number_format((float) $reward_totals['positive'], 0); ?></span>
      </div>
      <div class="spl-reward-calc-item is-negative">
        <span class="spl-reward-calc-label"><i class="bi bi-dash-circle me-1"></i>Deducted</span>
        <span class="spl-reward-calc-value">-<?php echo number_format((float) $reward_totals['negative'], 0); ?></span>
      </div>
      <div class="spl-reward-calc-item is-net">
        <span class="spl-reward-calc-label"><i class="bi bi-calculator me-1"></i>Net (<?php echo esc_view($period_label, ENT_QUOTES, 'UTF-8'); ?>)</span>
        <span class="spl-reward-calc-value"><?php echo ((float) $reward_totals['net'] >= 0 ? '+' : '') . number_format((float) $reward_totals['net'], 0); ?></span>
      </div>
      <?php if ((float) $reward_totals['pending_points'] > 0): ?>
      <div class="spl-reward-calc-item is-pending">
        <span class="spl-reward-calc-label"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
        <span class="spl-reward-calc-value">+<?php echo number_format((float) $reward_totals['pending_points'], 0); ?></span>
      </div>
      <?php endif; ?>
    </div>
    <?php if (!empty($reward_totals['pending_count']) || !empty($reward_totals['approved_count']) || !empty($reward_totals['rejected_count'])): ?>
    <div class="spl-reward-calc-meta px-3 pb-3">
      <?php if (!empty($reward_totals['pending_count'])): ?>
      <span class="badge rounded-pill text-bg-warning"><?php echo (int) $reward_totals['pending_count']; ?> pending</span>
      <?php endif; ?>
      <?php if (!empty($reward_totals['approved_count'])): ?>
      <span class="badge rounded-pill text-bg-success"><?php echo (int) $reward_totals['approved_count']; ?> approved</span>
      <?php endif; ?>
      <?php if (!empty($reward_totals['rejected_count'])): ?>
      <span class="badge rounded-pill text-bg-secondary"><?php echo (int) $reward_totals['rejected_count']; ?> rejected</span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="spl-member-activities card border-0 shadow-sm mt-3">
    <div class="spl-panel-head px-3 pt-3 pb-2 border-bottom-0">
      <div>
        <h2 class="spl-panel-title mb-0">All Activities</h2>
        <p class="spl-panel-sub mb-0"><?php echo esc_view($period_label, ENT_QUOTES, 'UTF-8'); ?> · <?php echo count($activities); ?> record<?php echo count($activities) === 1 ? '' : 's'; ?></p>
      </div>
    </div>
    <div class="spl-panel-body px-3 pb-3 pt-0">
      <?php $this->load->view('spl/_activity_table', array(
          'activities' => $activities,
          'table_id' => 'splMemberActivityTable',
          'page_length' => 25,
          'empty_message' => 'No activity for ' . strtolower($period_label),
      )); ?>
    </div>
  </div>
</div>

<?php $this->load->view('spl/_activity_detail_modal'); ?>

<script src="<?php echo base_url('assets/js/spl.js?v=' . (is_file(FCPATH . 'assets/js/spl.js') ? filemtime(FCPATH . 'assets/js/spl.js') : '1')); ?>"></script>
<script>
window.SPL_ACTIVITY_PAYLOADS = <?php echo json_encode($activity_payloads, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
document.addEventListener('DOMContentLoaded', function() {
  if (window.initSplActivityTable) {
    window.initSplActivityTable(document);
  }
});
</script>

<?php $this->load->view('partials/footer'); ?>
