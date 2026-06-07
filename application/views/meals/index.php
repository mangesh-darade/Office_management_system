<?php $this->load->view('partials/header', ['title' => 'Meals', 'active' => 'meals']); ?>
<div class="container-fluid py-3 oms-fluid-pad">
<?php
$bfCut = substr($settings['breakfast_cutoff'], 0, 5);
$luCut = substr($settings['lunch_cutoff'], 0, 5);
$today = date('Y-m-d');
$tomorrow = isset($tomorrow) ? $tomorrow : date('Y-m-d', strtotime('+1 day'));
$hasAnyScheduled = false;
$hasTodayScheduled = false;
foreach ($cal_map as $dateKey => $c) {
    if ((int) $c->has_breakfast === 1 || (int) $c->has_lunch === 1) {
        $hasAnyScheduled = true;
        if ($dateKey === $today) {
            $hasTodayScheduled = true;
        }
    }
}
$this->load->view('partials/oms_page_head', [
    'title' => 'Office Meals',
    'icon' => 'bi-cup-hot',
    'subtitle' => meal_can_order()
        ? 'Tap Yes/No or choose options — saves automatically.'
        : 'Meals module (view only for your role).',
    'actions_html' => '',
]);
$this->load->view('meals/_nav', ['active_sub' => 'meals']);
$change_requests = isset($change_requests) ? $change_requests : array('breakfast' => null, 'lunch' => null);
$maxBfPlates = isset($settings['max_breakfast_plates']) ? (int) $settings['max_breakfast_plates'] : 3;
if ($maxBfPlates < 1) { $maxBfPlates = 1; }
if ($maxBfPlates > 3) { $maxBfPlates = 3; }
$providerContact = meal_provider_contact($settings);
$providerContactTel = meal_provider_contact_tel_href($providerContact);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div><?php endif; ?>
<div id="meal-save-toast" class="alert py-2 shadow-soft d-none mb-3" role="status"></div>

<div class="d-flex flex-wrap gap-2 mb-3">
  <span class="badge rounded-pill bg-warning text-dark"><i class="bi bi-sunrise me-1"></i>Breakfast locks after <?php echo htmlspecialchars($bfCut); ?></span>
  <span class="badge rounded-pill bg-warning text-dark"><i class="bi bi-egg-fried me-1"></i>Lunch locks after <?php echo htmlspecialchars($luCut); ?></span>
</div>

<?php if (!$hasAnyScheduled): ?>
<div class="alert alert-warning shadow-soft">
  <strong>No meals scheduled for today or tomorrow.</strong>
  <?php if (function_exists('meal_can_access') && meal_can_access('meals_settings')): ?>
    Open <a href="<?php echo site_url('meals/settings'); ?>" class="alert-link">Meal Settings</a>, set the weekly menu, and click <strong>Apply to calendar</strong>.
  <?php elseif (function_exists('meal_can_access') && meal_can_access('meals_calendar')): ?>
    Open <a href="<?php echo site_url('meals/calendar'); ?>" class="alert-link">Meal Calendar</a> and tick breakfast/lunch for the dates you need.
  <?php else: ?>
    Ask your admin to set the meal calendar for this week.
  <?php endif; ?>
</div>
<?php endif; ?>

<div id="meal-orders-panel" data-save-url="<?php echo htmlspecialchars(site_url('meals/save_order')); ?>" data-request-url="<?php echo htmlspecialchars(site_url('meals/submit_request')); ?>" data-meal-date="<?php echo htmlspecialchars($today); ?>">
<input type="hidden" id="meal_csrf_token" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
<div class="row g-3">
<?php
$cursor = strtotime($from);
$end = strtotime($to);
while ($cursor <= $end):
  $d = date('Y-m-d', $cursor);
  $cal = isset($cal_map[$d]) ? $cal_map[$d] : null;
  $ord = isset($orders[$d]) ? $orders[$d] : null;
  $hasBf = $cal && (int)$cal->has_breakfast === 1;
  $hasLu = $cal && (int)$cal->has_lunch === 1;
  $bfEdit = $hasBf && meal_is_breakfast_editable($d, $settings);
  $luEdit = $hasLu && meal_is_lunch_editable($d, $settings);
  $bfPlates = $ord ? meal_order_breakfast_plates($ord) : 0;
  $bfAdditional = $ord ? meal_order_additional_breakfast_plates($ord) : 0;
  $wantBf = $bfPlates > 0 ? 1 : 0;
  $luTiffin = $ord ? meal_order_lunch_tiffin($ord) : '';
  $luAdditional = $ord ? meal_order_additional_lunch_tiffin($ord) : '';
  $wantLu = $luTiffin !== '' ? 1 : 0;
  $bfReq = ($d === $today && isset($change_requests['breakfast'])) ? $change_requests['breakfast'] : null;
  $luReq = ($d === $today && isset($change_requests['lunch'])) ? $change_requests['lunch'] : null;
?>
  <div class="col-12 col-md-6">
    <div class="card shadow-soft h-100 <?php echo (!$hasBf && !$hasLu) ? 'border-secondary' : 'border-primary border-opacity-25'; ?>">
      <div class="card-header bg-white fw-semibold py-2">
        <div class="d-flex justify-content-between align-items-center">
          <span><?php echo date('D, d M', $cursor); ?></span>
          <?php if ($d === $today): ?><span class="badge bg-primary">Today</span><?php elseif ($d === $tomorrow): ?><span class="badge bg-info text-dark">Tomorrow</span><?php endif; ?>
        </div>
        <?php if ($d === $today && $providerContact !== ''): ?>
        <div class="mt-2 pt-2 border-top small fw-normal">
          <i class="bi bi-telephone-outbound me-1 text-primary"></i>
          <?php if ($providerContactTel !== ''): ?>
          <a href="<?php echo htmlspecialchars($providerContactTel, ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none">Contact us: <?php echo htmlspecialchars($providerContact, ENT_QUOTES, 'UTF-8'); ?></a>
          <?php else: ?>
          <span class="text-muted">Contact us: <?php echo htmlspecialchars($providerContact, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="card-body py-3">
        <?php if ($d === $tomorrow): ?>
        <div class="meal-day-flags">
          <div class="meal-flag-box <?php echo !$hasBf ? 'is-unavailable' : ''; ?>">
            <div class="meal-flag-title"><i class="bi bi-sunrise text-warning me-1"></i>Breakfast</div>
            <?php if ($hasBf): ?>
              <div class="meal-flag-menu"><?php echo !empty($cal->breakfast_note) ? htmlspecialchars($cal->breakfast_note) : '—'; ?></div>
              <div class="small text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Available</div>
            <?php else: ?>
              <div class="small text-muted"><i class="bi bi-x-circle me-1"></i>Not available</div>
            <?php endif; ?>
          </div>
          <div class="meal-flag-box <?php echo !$hasLu ? 'is-unavailable' : ''; ?>">
            <div class="meal-flag-title"><i class="bi bi-egg-fried text-primary me-1"></i>Lunch</div>
            <?php if ($hasLu): ?>
              <div class="meal-flag-menu"><?php echo !empty($cal->lunch_note) ? htmlspecialchars($cal->lunch_note) : '—'; ?></div>
              <div class="small text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Available</div>
            <?php else: ?>
              <div class="small text-muted"><i class="bi bi-x-circle me-1"></i>Not available</div>
            <?php endif; ?>
          </div>
        </div>
        <p class="text-muted small mb-0 mt-3"><i class="bi bi-eye me-1"></i>Preview only — you can order tomorrow's meals when that day is shown as Today.</p>
        <?php elseif (!$hasBf && !$hasLu): ?>
          <p class="text-muted small mb-0"><i class="bi bi-dash-circle me-1"></i>Not scheduled</p>
        <?php else: ?>
        <div class="meal-day-flags">
          <!-- Breakfast flag -->
          <div class="meal-flag-box <?php echo !$hasBf ? 'is-unavailable' : (!$bfEdit ? 'is-locked' : ''); ?>">
            <div class="meal-flag-title"><i class="bi bi-sunrise text-warning me-1"></i>Breakfast</div>
            <?php if ($hasBf): ?>
              <div class="meal-flag-menu"><?php echo !empty($cal->breakfast_note) ? htmlspecialchars($cal->breakfast_note) : '—'; ?></div>
              <?php if (!$bfEdit): ?>
              <div class="meal-order-locked small">
                <span class="text-secondary"><i class="bi bi-lock-fill me-1"></i>Locked (after <?php echo htmlspecialchars($bfCut); ?>)</span>
                <div class="fw-semibold mt-1">
                  <?php if ($bfPlates > 0 || $bfAdditional > 0): ?>
                    <i class="bi bi-check2-circle text-success me-1"></i><?php echo htmlspecialchars(meal_format_breakfast_order_line($bfPlates, $bfAdditional)); ?>
                  <?php else: ?>
                    <span class="text-muted">No breakfast order</span>
                  <?php endif; ?>
                  <?php if ($bfReq && $bfReq->status === 'approved'): ?>
                    <span class="badge bg-success ms-1">Updated</span>
                  <?php elseif ($bfReq && $bfReq->status === 'pending'): ?>
                    <span class="badge bg-warning text-dark ms-1">Change pending</span>
                  <?php endif; ?>
                </div>
              </div>
              <input type="hidden" id="bf_hidden_<?php echo $d; ?>" value="<?php echo (int)$bfPlates; ?>">
              <?php if ($d === $today): ?>
              <?php $this->load->view('meals/_change_request', array(
                'meal_type' => 'breakfast',
                'meal_date' => $d,
                'request' => $bfReq,
                'current_plates' => $bfPlates,
                'additional_plates' => $bfAdditional,
                'max_bf_plates' => $maxBfPlates,
                'current_tiffin' => '',
                'additional_tiffin' => '',
              )); ?>
              <?php endif; ?>
              <?php else: ?>
              <div class="form-check form-switch mb-1">
                <input class="form-check-input meal-bf-toggle" type="checkbox" id="want_bf_<?php echo $d; ?>" data-date="<?php echo $d; ?>" <?php echo $wantBf ? 'checked' : ''; ?>>
                <label class="form-check-label small" for="want_bf_<?php echo $d; ?>"><?php echo $wantBf ? 'Yes' : 'No'; ?></label>
              </div>
              <div class="meal-plates-row <?php echo $wantBf ? '' : 'd-none'; ?>" id="bf_plates_<?php echo $d; ?>">
                <div class="btn-group w-100" role="group">
                  <?php for ($p = 1; $p <= $maxBfPlates; $p++): ?>
                  <input type="radio" class="btn-check meal-bf-plate" name="bf_plate_<?php echo $d; ?>" id="bf_<?php echo $d.'_'.$p; ?>" value="<?php echo $p; ?>" data-date="<?php echo $d; ?>" <?php echo ($bfPlates === $p || ($wantBf && $bfPlates === 0 && $p === 1)) ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-warning btn-sm" for="bf_<?php echo $d.'_'.$p; ?>"><?php echo $p; ?> plate<?php echo $p > 1 ? 's' : ''; ?></label>
                  <?php endfor; ?>
                </div>
              </div>
              <input type="hidden" id="bf_hidden_<?php echo $d; ?>" value="<?php echo $wantBf ? max(1, (int)$bfPlates) : 0; ?>">
              <div class="meal-flag-cutoff">Editable until <?php echo htmlspecialchars($bfCut); ?></div>
              <?php endif; ?>
            <?php else: ?>
              <div class="meal-flag-menu text-muted">Not served</div>
            <?php endif; ?>
          </div>

          <!-- Lunch flag -->
          <div class="meal-flag-box <?php echo !$hasLu ? 'is-unavailable' : (!$luEdit ? 'is-locked' : ''); ?>" id="lunch_box_<?php echo $d; ?>">
            <div class="meal-flag-title"><i class="bi bi-egg-fried text-primary me-1"></i>Lunch</div>
            <?php if ($hasLu): ?>
              <div class="meal-flag-menu"><?php echo !empty($cal->lunch_note) ? htmlspecialchars($cal->lunch_note) : '—'; ?></div>
              <?php if (!$luEdit): ?>
              <div class="meal-order-locked small">
                <span class="text-secondary"><i class="bi bi-lock-fill me-1"></i>Locked (after <?php echo htmlspecialchars($luCut); ?>)</span>
                <div class="fw-semibold mt-1">
                  <?php if ($luTiffin !== '' || $luAdditional !== ''): ?>
                    <i class="bi bi-check2-circle text-success me-1"></i><?php echo htmlspecialchars(meal_format_lunch_order_line($luTiffin, $luAdditional)); ?>
                  <?php else: ?>
                    <span class="text-muted">No lunch order</span>
                  <?php endif; ?>
                  <?php if ($luReq && $luReq->status === 'approved'): ?>
                    <span class="badge bg-success ms-1">Updated</span>
                  <?php elseif ($luReq && $luReq->status === 'pending'): ?>
                    <span class="badge bg-warning text-dark ms-1">Change pending</span>
                  <?php endif; ?>
                </div>
              </div>
              <input type="hidden" id="lu_hidden_<?php echo $d; ?>" value="<?php echo htmlspecialchars($luTiffin); ?>">
              <?php if ($d === $today): ?>
              <?php $this->load->view('meals/_change_request', array(
                'meal_type' => 'lunch',
                'meal_date' => $d,
                'request' => $luReq,
                'current_plates' => 0,
                'additional_plates' => 0,
                'max_bf_plates' => 0,
                'current_tiffin' => $luTiffin,
                'additional_tiffin' => $luAdditional,
              )); ?>
              <?php endif; ?>
              <?php else: ?>
              <div class="form-check form-switch mb-1">
                <input class="form-check-input meal-lu-toggle" type="checkbox" id="want_lu_<?php echo $d; ?>" data-date="<?php echo $d; ?>" <?php echo $wantLu ? 'checked' : ''; ?>>
                <label class="form-check-label small" for="want_lu_<?php echo $d; ?>"><?php echo $wantLu ? 'Yes' : 'No'; ?></label>
              </div>
              <div class="meal-plates-row <?php echo $wantLu ? '' : 'd-none'; ?>" id="plates_<?php echo $d; ?>">
                <div class="btn-group w-100" role="group">
                  <input type="radio" class="btn-check meal-lu-tiffin" name="lu_tiffin_<?php echo $d; ?>" id="lu_<?php echo $d; ?>_half" value="half" data-date="<?php echo $d; ?>" <?php echo ($luTiffin === 'half' || ($wantLu && $luTiffin === '')) ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-primary btn-sm" for="lu_<?php echo $d; ?>_half">Half tiffin</label>
                  <input type="radio" class="btn-check meal-lu-tiffin" name="lu_tiffin_<?php echo $d; ?>" id="lu_<?php echo $d; ?>_full" value="full" data-date="<?php echo $d; ?>" <?php echo $luTiffin === 'full' ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-primary btn-sm" for="lu_<?php echo $d; ?>_full">Full tiffin</label>
                </div>
              </div>
              <input type="hidden" id="lu_hidden_<?php echo $d; ?>" value="<?php echo htmlspecialchars($wantLu ? ($luTiffin !== '' ? $luTiffin : 'half') : ''); ?>">
              <div class="meal-flag-cutoff mt-1">Editable until <?php echo htmlspecialchars($luCut); ?></div>
              <?php endif; ?>
            <?php else: ?>
              <div class="meal-flag-menu text-muted">Not served</div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php
  $cursor = strtotime('+1 day', $cursor);
endwhile;
?>
</div>
</div>
<script>
(function () {
  var panel = document.getElementById('meal-orders-panel');
  var toast = document.getElementById('meal-save-toast');
  var saveUrl = panel ? panel.getAttribute('data-save-url') : '';
  var requestUrl = panel ? panel.getAttribute('data-request-url') : '';
  var mealDate = panel ? panel.getAttribute('data-meal-date') : '';
  var csrfInput = document.getElementById('meal_csrf_token');
  var saveTimer = null;
  var saving = false;

  function showToast(msg, kind) {
    if (!toast) return;
    toast.textContent = msg;
    toast.className = 'alert py-2 shadow-soft mb-3 alert-' + (kind || 'success');
    toast.classList.remove('d-none');
    window.clearTimeout(showToast._t);
    showToast._t = window.setTimeout(function () { toast.classList.add('d-none'); }, 2800);
  }

  function selectOneOption(row, optionClass, chosen) {
    if (!row || !chosen) return chosen;
    row.querySelectorAll(optionClass).forEach(function (r) {
      r.checked = (r === chosen);
    });
    return chosen;
  }

  function syncBreakfast(date) {
    var toggle = document.getElementById('want_bf_' + date);
    var platesRow = document.getElementById('bf_plates_' + date);
    var hidden = document.getElementById('bf_hidden_' + date);
    if (!toggle || !platesRow || !hidden) return null;
    var on = toggle.checked;
    platesRow.classList.toggle('d-none', !on);
    var label = toggle.closest('.form-check').querySelector('.form-check-label');
    if (label) label.textContent = on ? 'Yes' : 'No';
    if (!on) {
      hidden.value = '0';
      platesRow.querySelectorAll('.meal-bf-plate').forEach(function (r) { r.checked = false; });
      return hidden.value;
    }
    var checked = platesRow.querySelector('.meal-bf-plate:checked');
    if (!checked) {
      checked = platesRow.querySelector('.meal-bf-plate[value="1"]') || platesRow.querySelector('.meal-bf-plate');
    }
    checked = selectOneOption(platesRow, '.meal-bf-plate', checked);
    hidden.value = checked ? checked.value : '1';
    return hidden.value;
  }

  function syncLunch(date) {
    var toggle = document.getElementById('want_lu_' + date);
    var tiffinRow = document.getElementById('plates_' + date);
    var hidden = document.getElementById('lu_hidden_' + date);
    if (!toggle || !tiffinRow || !hidden) return null;
    var on = toggle.checked;
    tiffinRow.classList.toggle('d-none', !on);
    var label = toggle.closest('.form-check').querySelector('.form-check-label');
    if (label) label.textContent = on ? 'Yes' : 'No';
    if (!on) {
      hidden.value = '';
      tiffinRow.querySelectorAll('.meal-lu-tiffin').forEach(function (r) { r.checked = false; });
      return hidden.value;
    }
    var checked = tiffinRow.querySelector('.meal-lu-tiffin:checked');
    if (!checked) {
      checked = tiffinRow.querySelector('.meal-lu-tiffin[value="half"]') || tiffinRow.querySelector('.meal-lu-tiffin');
    }
    checked = selectOneOption(tiffinRow, '.meal-lu-tiffin', checked);
    hidden.value = checked ? checked.value : 'half';
    return hidden.value;
  }

  function saveOrder() {
    if (!saveUrl || !mealDate || !csrfInput) return;
    var bfHidden = document.getElementById('bf_hidden_' + mealDate);
    var luHidden = document.getElementById('lu_hidden_' + mealDate);
    if (!bfHidden && !luHidden) return;

    var fd = new FormData();
    fd.append('meal_date', mealDate);
    fd.append('breakfast_plates', bfHidden ? bfHidden.value : '0');
    fd.append('lunch_tiffin', luHidden ? luHidden.value : '');
    fd.append(csrfInput.name, csrfInput.value);

    if (saving) return;
    saving = true;

    fetch(saveUrl, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function (r) {
        return r.json().catch(function () {
          return { ok: false, error: r.status === 403 ? 'Session expired — refresh the page.' : 'Could not save.' };
        });
      })
      .then(function (data) {
        saving = false;
        if (data && data.ok) {
          if (data.changed) {
            showToast(data.message || 'Saved.', 'success');
          }
        } else {
          showToast((data && data.error) ? data.error : 'Could not save.', 'danger');
        }
      })
      .catch(function () {
        saving = false;
        showToast('Could not save. Try again.', 'danger');
      });
  }

  function queueSave() {
    window.clearTimeout(saveTimer);
    saveTimer = window.setTimeout(saveOrder, 200);
  }

  document.querySelectorAll('.meal-lu-toggle').forEach(function (el) {
    el.addEventListener('change', function () {
      syncLunch(el.getAttribute('data-date'));
      queueSave();
    });
    syncLunch(el.getAttribute('data-date'));
  });
  document.querySelectorAll('.meal-lu-tiffin').forEach(function (el) {
    el.addEventListener('change', function () {
      if (!el.checked) return;
      var date = el.getAttribute('data-date');
      selectOneOption(document.getElementById('plates_' + date), '.meal-lu-tiffin', el);
      var hidden = document.getElementById('lu_hidden_' + date);
      if (hidden) hidden.value = el.value;
      queueSave();
    });
  });
  document.querySelectorAll('.meal-bf-toggle').forEach(function (el) {
    el.addEventListener('change', function () {
      syncBreakfast(el.getAttribute('data-date'));
      queueSave();
    });
    syncBreakfast(el.getAttribute('data-date'));
  });
  document.querySelectorAll('.meal-bf-plate').forEach(function (el) {
    el.addEventListener('change', function () {
      if (!el.checked) return;
      var date = el.getAttribute('data-date');
      selectOneOption(document.getElementById('bf_plates_' + date), '.meal-bf-plate', el);
      var hidden = document.getElementById('bf_hidden_' + date);
      if (hidden) hidden.value = el.value;
      queueSave();
    });
  });

  document.querySelectorAll('.meal-change-request-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!requestUrl || !csrfInput) return;

      var mealType = form.getAttribute('data-meal-type') || 'breakfast';
      var date = form.getAttribute('data-meal-date') || mealDate;
      var currentVal = form.getAttribute('data-current-value') || '';
      var requested = '';
      if (mealType === 'breakfast') {
        var bfSel = form.querySelector('.meal-req-bf:checked');
        if (!bfSel) {
          showToast('Choose a new breakfast option to request.', 'danger');
          return;
        }
        requested = bfSel.value;
      } else {
        var luSel = form.querySelector('.meal-req-lu:checked');
        if (!luSel) {
          showToast('Choose a new lunch option to request.', 'danger');
          return;
        }
        requested = luSel.value;
      }

      if (String(requested) === String(currentVal) && requested.indexOf('add_') !== 0) {
        showToast('Pick a value different from your current order.', 'danger');
        return;
      }

      var noteEl = form.querySelector('[name="employee_note"]');
      var note = noteEl ? noteEl.value.trim() : '';
      var btn = form.querySelector('.meal-req-submit');
      if (btn) btn.disabled = true;

      var fd = new FormData();
      fd.append('meal_date', date);
      fd.append('meal_type', mealType);
      fd.append('requested_value', requested);
      fd.append('employee_note', note);
      fd.append(csrfInput.name, csrfInput.value);

      fetch(requestUrl, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) {
          return r.json().catch(function () {
            return { ok: false, error: r.status === 403 ? 'Session expired — refresh the page.' : 'Could not send request.' };
          });
        })
        .then(function (data) {
          if (btn) btn.disabled = false;
          if (data && data.ok) {
            showToast(data.message || 'Request sent.', 'success');
            window.setTimeout(function () { window.location.reload(); }, 900);
          } else {
            showToast((data && data.error) ? data.error : 'Could not send request.', 'danger');
          }
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          showToast('Could not send request. Try again.', 'danger');
        });
    });
  });

  document.querySelectorAll('.meal-req-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-target');
      var el = targetId ? document.getElementById(targetId) : null;
      if (!el) return;
      el.classList.toggle('d-none');
      if (!el.classList.contains('d-none')) {
        el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  });
})();
</script>
<?php $this->load->view('partials/footer'); ?>
