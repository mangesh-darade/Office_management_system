<?php
  $today_summary = isset($today_summary) && is_array($today_summary) ? $today_summary : array(
    'visible' => false,
    'date_label' => '',
    'tasks' => array(),
    'my_works' => array(),
    'templates' => array(),
    'html' => '',
    'counts' => array('tasks' => 0, 'my_works' => 0, 'templates' => 0),
  );
  $sum_visible = !empty($today_summary['visible']);
  $date_label = isset($today_summary['date_label']) ? (string) $today_summary['date_label'] : '';
  $task_items = isset($today_summary['tasks']) && is_array($today_summary['tasks']) ? $today_summary['tasks'] : array();
  $mw_items = isset($today_summary['my_works']) && is_array($today_summary['my_works']) ? $today_summary['my_works'] : array();
  $tpl_lines = isset($today_summary['templates']) && is_array($today_summary['templates']) ? $today_summary['templates'] : array();
  $show_insert = !isset($show_insert_btn) || !empty($show_insert_btn);
?>
<div id="daTaskSummary"
     class="da-summary<?php echo $sum_visible ? ' is-filled' : ' is-empty'; ?>"
     aria-live="polite"
     aria-atomic="true">
  <div class="da-summary-head">
    <div class="da-summary-label">
      Today's assigned work
      <?php if ($date_label !== ''): ?>
        <span class="da-summary-hint">— <?php echo esc_view($date_label); ?></span>
      <?php endif; ?>
    </div>
    <?php if ($sum_visible && $show_insert): ?>
      <button type="button" class="btn btn-outline-primary btn-sm da-summary-insert" id="daInsertSummaryBtn">
        <i class="bi bi-clipboard-plus me-1"></i>Add to description
      </button>
    <?php endif; ?>
  </div>

  <?php if (!$sum_visible): ?>
    <p class="da-summary-empty mb-0">
      No assigned Tasks / My Works due today (or overdue). Today's comments and history will show under each item when available.
    </p>
  <?php else: ?>
    <div class="da-summary-body">
      <?php if (!empty($task_items)): ?>
        <div class="da-summary-section">
          <div class="da-summary-section-title"><i class="bi bi-list-task" aria-hidden="true"></i> Tasks (<?php echo count($task_items); ?>)</div>
          <ul class="da-summary-items mb-0">
            <?php foreach ($task_items as $item): ?>
              <li class="da-summary-item">
                <div class="da-summary-item-title">
                  <?php echo esc_view(isset($item['label']) ? $item['label'] : ''); ?>
                  <?php if (!empty($item['due_note'])): ?>
                    <span class="da-summary-due"><?php echo esc_view($item['due_note']); ?></span>
                  <?php endif; ?>
                </div>
                <?php $this->load->view('daily_activity/_summary_history', array(
                  'history' => isset($item['history']) ? $item['history'] : array(),
                )); ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if (!empty($mw_items)): ?>
        <div class="da-summary-section">
          <div class="da-summary-section-title"><i class="bi bi-briefcase" aria-hidden="true"></i> My Works (<?php echo count($mw_items); ?>)</div>
          <ul class="da-summary-items mb-0">
            <?php foreach ($mw_items as $item): ?>
              <li class="da-summary-item">
                <div class="da-summary-item-title">
                  <?php echo esc_view(isset($item['label']) ? $item['label'] : ''); ?>
                  <?php if (!empty($item['due_note'])): ?>
                    <span class="da-summary-due"><?php echo esc_view($item['due_note']); ?></span>
                  <?php endif; ?>
                </div>
                <?php $this->load->view('daily_activity/_summary_history', array(
                  'history' => isset($item['history']) ? $item['history'] : array(),
                )); ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if (!empty($tpl_lines)): ?>
        <div class="da-summary-section">
          <div class="da-summary-section-title"><i class="bi bi-layout-text-window" aria-hidden="true"></i> Template tasks (<?php echo count($tpl_lines); ?>)</div>
          <ul class="da-summary-list mb-0">
            <?php foreach ($tpl_lines as $line): ?>
              <li><?php echo esc_view($line); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>
