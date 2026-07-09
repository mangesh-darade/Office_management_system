<?php
$reward_period = isset($reward_period) ? $reward_period : 'week';
$reward_period_options = spl_reward_period_options();
?>
<div class="spl-period-filter" role="group" aria-label="Score period">
  <div class="spl-period-filter__track">
    <?php foreach ($reward_period_options as $periodKey => $periodLabel): ?>
    <button
      type="button"
      class="spl-period-filter__btn<?php echo $reward_period === $periodKey ? ' is-active' : ''; ?>"
      data-spl-period="<?php echo esc_view($periodKey, ENT_QUOTES, 'UTF-8'); ?>"
      aria-pressed="<?php echo $reward_period === $periodKey ? 'true' : 'false'; ?>"
    >
      <?php echo esc_view($periodLabel, ENT_QUOTES, 'UTF-8'); ?>
    </button>
    <?php endforeach; ?>
  </div>
</div>
