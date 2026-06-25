<?php
/**
 * Locked-meal change request (employee).
 * @var string $meal_type breakfast|lunch
 * @var string $meal_date
 * @var object|null $request
 * @var int $current_plates
 * @var int $max_bf_plates
 * @var string $current_tiffin
 * @var int $additional_plates
 * @var string $additional_tiffin
 */
$meal_type = isset($meal_type) ? $meal_type : 'breakfast';
$meal_date = isset($meal_date) ? $meal_date : date('Y-m-d');
$request = isset($request) ? $request : null;
$current_plates = isset($current_plates) ? (int) $current_plates : 0;
$max_bf_plates = isset($max_bf_plates) ? (int) $max_bf_plates : 3;
$current_tiffin = isset($current_tiffin) ? (string) $current_tiffin : '';
$additional_plates = isset($additional_plates) ? (int) $additional_plates : 0;
$additional_tiffin = isset($additional_tiffin) ? (string) $additional_tiffin : '';
if ($max_bf_plates < 1) { $max_bf_plates = 1; }
if ($max_bf_plates > 3) { $max_bf_plates = 3; }

$currentRaw = $meal_type === 'breakfast'
    ? (string) $current_plates
    : meal_normalize_lunch_tiffin($current_tiffin);

$currentLabel = $meal_type === 'breakfast'
    ? meal_format_breakfast_order_line($current_plates, $additional_plates)
    : meal_format_lunch_order_line($current_tiffin, $additional_tiffin);

$pending = ($request && $request->status === 'pending');
$approved = ($request && $request->status === 'approved');
$rejected = ($request && $request->status === 'rejected');
$reqId = 'meal_req_' . $meal_type . '_' . preg_replace('/[^0-9]/', '', $meal_date);
$formId = $reqId . '_form';

$reqOptions = array();
$additionalOptions = array();
if ($meal_type === 'breakfast') {
    for ($p = 0; $p <= $max_bf_plates; $p++) {
        $val = (string) $p;
        if (meal_change_values_equal('breakfast', $val, $currentRaw)) {
            continue;
        }
        $reqOptions[] = array(
            'value' => $p,
            'label' => $p === 0 ? 'No breakfast' : ($p . ' plate' . ($p > 1 ? 's' : '')),
        );
    }
    if ($current_plates > 0 && $additional_plates < 1 && ($current_plates + 1) <= $max_bf_plates) {
        $additionalOptions[] = array(
            'value' => 'add_bf:1',
            'label' => 'Additional 1 plate (on top of current order)',
        );
    }
} else {
    foreach (array('' => 'No lunch', 'half' => 'Half tiffin', 'full' => 'Full tiffin') as $val => $lbl) {
        $reqOptions[] = array(
            'value' => $val,
            'label' => $lbl,
            'is_current' => meal_change_values_equal('lunch', $val, $currentRaw),
        );
    }
    if ($current_tiffin !== '' && $additional_tiffin === '') {
        $additionalOptions[] = array(
            'value' => 'add_lu:half',
            'label' => 'Additional half tiffin',
        );
        $additionalOptions[] = array(
            'value' => 'add_lu:full',
            'label' => 'Additional full tiffin',
        );
    }
}
$hasChangeOptions = ($meal_type === 'lunch')
    ? count(array_filter($reqOptions, function ($o) { return empty($o['is_current']); })) > 0
    : !empty($reqOptions);
$hasAdditionalOptions = !empty($additionalOptions);
$hasAnyOptions = $hasChangeOptions || $hasAdditionalOptions;
$radioName = $meal_type === 'breakfast' ? 'req_bf_' . $meal_date : 'req_lu_' . $meal_date;
?>
<div class="meal-change-request mt-2 pt-2 border-top" id="<?php echo esc_view($reqId); ?>" data-meal-type="<?php echo esc_view($meal_type); ?>" data-meal-date="<?php echo esc_view($meal_date); ?>" data-current-value="<?php echo esc_view($currentRaw); ?>">

  <?php if ($pending): ?>
  <div class="meal-change-status meal-change-status--pending">
    <div class="d-flex align-items-start gap-2">
      <i class="bi bi-hourglass-split fs-5 text-warning"></i>
      <div class="flex-grow-1">
        <div class="fw-semibold">Waiting for provider</div>
        <div class="small text-muted">Your change request is pending approval.</div>
        <div class="mt-2 p-2 rounded bg-white border">
          <span class="text-muted small">Requested change</span>
          <div class="fw-semibold"><?php echo esc_view(meal_format_change_summary($meal_type, $request->current_value, $request->requested_value)); ?></div>
        </div>
        <?php if (!empty($request->employee_note)): ?>
        <div class="small text-muted mt-1"><i class="bi bi-chat-left-text me-1"></i><?php echo esc_view($request->employee_note); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php elseif ($approved): ?>
  <div class="meal-change-status meal-change-status--approved">
    <div class="d-flex align-items-start gap-2">
      <i class="bi bi-check-circle-fill fs-5 text-success"></i>
      <div class="flex-grow-1">
        <div class="fw-semibold text-success">Change approved</div>
        <div class="small">Your order was updated by the provider.</div>
        <div class="mt-2 p-2 rounded bg-white border border-success-subtle">
          <span class="text-muted small">Approved change</span>
          <div class="fw-semibold text-success"><?php echo esc_view(meal_format_change_summary($meal_type, $request->current_value, $request->requested_value)); ?></div>
          <div class="small mt-1">Now: <strong><?php echo esc_view($currentLabel); ?></strong></div>
        </div>
        <?php if (!empty($request->review_note)): ?>
        <div class="small text-muted mt-1"><i class="bi bi-chat-left-text me-1"></i>Provider: <?php echo esc_view($request->review_note); ?></div>
        <?php endif; ?>
        <?php if (!empty($request->reviewed_at)): ?>
        <div class="small text-muted mt-1"><i class="bi bi-clock me-1"></i><?php echo date('g:i A', strtotime($request->reviewed_at)); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php if ($hasAnyOptions): ?>
  <button type="button" class="btn btn-link btn-sm px-0 mt-2 meal-req-toggle" data-target="<?php echo esc_view($formId); ?>">
    <i class="bi bi-arrow-repeat me-1"></i>Request another change
  </button>
  <?php endif; ?>

  <?php elseif ($rejected): ?>
  <div class="meal-change-status meal-change-status--rejected mb-2">
    <div class="d-flex align-items-start gap-2">
      <i class="bi bi-x-circle-fill fs-5 text-danger"></i>
      <div class="flex-grow-1">
        <div class="fw-semibold text-danger">Change not approved</div>
        <div class="small">Your order stays: <strong><?php echo esc_view($currentLabel); ?></strong></div>
        <?php if (!empty($request->current_value) || !empty($request->requested_value)): ?>
        <div class="small text-muted mt-1">Requested: <?php echo esc_view(meal_format_change_summary($meal_type, $request->current_value, $request->requested_value)); ?></div>
        <?php endif; ?>
        <?php if (!empty($request->review_note)): ?>
        <div class="small mt-1"><i class="bi bi-chat-left-text me-1"></i><?php echo esc_view($request->review_note); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <?php if (!$pending && $hasAnyOptions): ?>
  <div id="<?php echo esc_view($formId); ?>" class="<?php echo ($approved && !$rejected) ? 'd-none' : ''; ?>">
    <?php if (!$approved && !$rejected): ?>
    <div class="small fw-semibold text-secondary mb-2"><i class="bi bi-arrow-repeat me-1"></i>Need a different order?</div>
    <?php endif; ?>
    <div class="meal-current-pill mb-2">
      <span class="small text-muted">Your order now</span>
      <span class="badge bg-secondary ms-1"><?php echo esc_view($currentLabel); ?></span>
    </div>
    <form class="meal-change-request-form" data-meal-type="<?php echo esc_view($meal_type); ?>" data-meal-date="<?php echo esc_view($meal_date); ?>" data-current-value="<?php echo esc_view($currentRaw); ?>">
      <?php if ($hasChangeOptions): ?>
      <div class="small text-muted mb-1">Change to a new value:</div>
      <?php if ($meal_type === 'breakfast'): ?>
      <div class="d-grid gap-1 mb-2">
        <?php foreach ($reqOptions as $opt):
          $p = (int) $opt['value'];
        ?>
        <label class="btn btn-outline-warning btn-sm text-start meal-req-option">
          <input type="radio" class="form-check-input me-2 meal-req-bf" name="<?php echo esc_view($radioName); ?>" value="<?php echo $p; ?>">
          <?php echo esc_view($opt['label']); ?>
        </label>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="mb-2">
        <?php
        $noOpt = null;
        $tiffinOpts = array();
        foreach ($reqOptions as $opt) {
            if ($opt['value'] === '') {
                $noOpt = $opt;
            } else {
                $tiffinOpts[] = $opt;
            }
        }
        ?>
        <?php if ($noOpt): ?>
        <label class="btn btn-outline-secondary btn-sm text-start meal-req-option w-100 mb-2<?php echo !empty($noOpt['is_current']) ? ' disabled opacity-75' : ''; ?>">
          <input type="radio" class="form-check-input me-2 meal-req-lu" name="<?php echo esc_view($radioName); ?>" value="" <?php echo !empty($noOpt['is_current']) ? 'disabled' : ''; ?>>
          <?php echo esc_view($noOpt['label']); ?><?php echo !empty($noOpt['is_current']) ? ' (current)' : ''; ?>
        </label>
        <?php endif; ?>
        <div class="btn-group w-100" role="group">
          <?php foreach ($tiffinOpts as $opt):
            $idVal = $opt['value'] === '' ? 'none' : $opt['value'];
            $isCur = !empty($opt['is_current']);
          ?>
          <input type="radio" class="btn-check meal-req-lu" name="<?php echo esc_view($radioName); ?>" id="req_lu_<?php echo $idVal; ?>_<?php echo $meal_date; ?>_chg" value="<?php echo esc_view($opt['value']); ?>" <?php echo $isCur ? 'disabled' : ''; ?>>
          <label class="btn btn-outline-primary btn-sm<?php echo $isCur ? ' disabled opacity-75' : ''; ?>" for="req_lu_<?php echo $idVal; ?>_<?php echo $meal_date; ?>_chg">
            <?php echo $opt['value'] === 'half' ? 'Half tiffin' : 'Full tiffin'; ?><?php echo $isCur ? ' ✓' : ''; ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; ?>

      <?php if ($hasAdditionalOptions): ?>
      <div class="small text-muted mb-1 mt-2"><i class="bi bi-plus-circle me-1"></i>Request additional (keep current order):</div>
      <div class="d-grid gap-1 mb-2">
        <?php foreach ($additionalOptions as $opt): ?>
        <label class="btn btn-outline-success btn-sm text-start meal-req-option">
          <input type="radio" class="form-check-input me-2 <?php echo $meal_type === 'breakfast' ? 'meal-req-bf' : 'meal-req-lu'; ?>" name="<?php echo esc_view($radioName); ?>" value="<?php echo esc_view($opt['value']); ?>">
          <?php echo esc_view($opt['label']); ?>
        </label>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <textarea class="form-control form-control-sm mb-2" name="employee_note" rows="2" maxlength="255" placeholder="Optional note for provider"></textarea>
      <button type="submit" class="btn btn-sm btn-primary w-100 meal-req-submit">
        <i class="bi bi-send me-1"></i>Send request to provider
      </button>
    </form>
  </div>
  <?php elseif (!$pending && !$hasAnyOptions): ?>
  <div class="small text-muted mt-1"><i class="bi bi-info-circle me-1"></i>No other options available for this meal.</div>
  <?php endif; ?>
</div>
