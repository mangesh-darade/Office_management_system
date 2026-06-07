<?php $this->load->view('partials/header', ['title' => 'Meal Settings', 'active' => 'meals']); ?>
<div class="container-fluid py-3 oms-fluid-pad">
<?php
$this->load->view('partials/oms_page_head', [
    'title' => 'Meal Settings',
    'icon' => 'bi-gear',
    'subtitle' => 'Cut-off times, weekly menu template, and apply to calendar',
]);
$this->load->view('meals/_nav', ['active_sub' => 'settings']);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div><?php endif; ?>

<form method="post" id="meal-settings-form">
<?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<input type="hidden" name="action" value="save_all" id="meal-settings-action">

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card shadow-soft h-100">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-clock me-1"></i>Order cut-off times</div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Breakfast cut-off</label>
          <input type="time" name="breakfast_cutoff" class="form-control" value="<?php echo htmlspecialchars(substr($settings['breakfast_cutoff'], 0, 5)); ?>" required>
          <div class="form-text">Default <strong>08:30</strong> — after this, breakfast plates lock each day.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Lunch cut-off</label>
          <input type="time" name="lunch_cutoff" class="form-control" value="<?php echo htmlspecialchars(substr($settings['lunch_cutoff'], 0, 5)); ?>" required>
          <div class="form-text">Default <strong>11:00</strong> — after this, lunch tiffin choice locks each day.</div>
        </div>
        <div class="mb-3">
          <label class="form-label">Max breakfast plates per person</label>
          <select name="max_breakfast_plates" class="form-select">
            <?php for ($p = 1; $p <= 3; $p++): ?>
            <option value="<?php echo $p; ?>" <?php echo ((int)($settings['max_breakfast_plates'] ?? 3) === $p) ? 'selected' : ''; ?>><?php echo $p; ?> plate<?php echo $p > 1 ? 's' : ''; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="mb-0 pt-3 border-top">
          <label class="form-label" for="provider_contact"><i class="bi bi-telephone me-1"></i>Provider contact number</label>
          <input type="text" name="provider_contact" id="provider_contact" class="form-control" maxlength="50" placeholder="e.g. +91 98765 43210" value="<?php echo htmlspecialchars(isset($settings['provider_contact']) ? (string) $settings['provider_contact'] : '', ENT_QUOTES, 'UTF-8'); ?>">
          <div class="form-text">Shown on <strong>My Orders</strong> under today&apos;s date as <em>Contact us</em> for help after meals lock.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card shadow-soft h-100">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-sliders me-1"></i>Automation options</div>
      <div class="card-body">
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="auto_publish_announcements" value="1" id="auto_ann" <?php echo !empty($settings['auto_publish_announcements']) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="auto_ann">Auto-publish meals to Announcements module</label>
          <div class="form-text">When calendar changes, create/update the <strong>[Meals] Today &amp; Tomorrow</strong> record in Announcements.</div>
        </div>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="show_dashboard_announcement" value="1" id="show_dash_ann" <?php echo !empty($settings['show_dashboard_announcement']) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="show_dash_ann">Show meals on Dashboard announcements</label>
          <div class="form-text">When enabled, the red Today/Tomorrow meals grid appears under <strong>Latest Announcements</strong> on the Dashboard. When off, it is hidden (meals ordering still works).</div>
        </div>
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox" name="skip_weekends_on_apply" value="1" id="skip_we" <?php echo !empty($settings['skip_weekends_on_apply']) ? 'checked' : ''; ?>>
          <label class="form-check-label" for="skip_we">Skip Saturday &amp; Sunday when applying weekly menu to calendar</label>
        </div>
        <p class="form-text mb-0 mt-3">Change requests send <strong>in-app notifications</strong> only (no email).</p>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card shadow-soft">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-journal-text me-1"></i>Weekly menu template (Mon–Sun)</span>
        <span class="text-muted small">Used when you apply menu to a calendar week</span>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Day</th>
              <th class="text-center">Breakfast</th>
              <th>Breakfast menu</th>
              <th class="text-center">Lunch</th>
              <th>Lunch menu</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($day_labels as $dow => $label):
            $row = isset($week_menu[$dow]) ? $week_menu[$dow] : null;
            $hasBf = $row && (int)$row->has_breakfast === 1;
            $hasLu = $row && (int)$row->has_lunch === 1;
          ?>
            <tr>
              <td class="fw-semibold"><?php echo htmlspecialchars($label); ?></td>
              <td class="text-center"><input type="checkbox" name="week_menu[<?php echo $dow; ?>][has_breakfast]" value="1" <?php echo $hasBf ? 'checked' : ''; ?>></td>
              <td><input type="text" class="form-control form-control-sm" name="week_menu[<?php echo $dow; ?>][breakfast_menu]" value="<?php echo $row ? htmlspecialchars((string)$row->breakfast_menu) : ''; ?>" placeholder="e.g. Poha, Tea"></td>
              <td class="text-center"><input type="checkbox" name="week_menu[<?php echo $dow; ?>][has_lunch]" value="1" <?php echo $hasLu ? 'checked' : ''; ?>></td>
              <td><input type="text" class="form-control form-control-sm" name="week_menu[<?php echo $dow; ?>][lunch_menu]" value="<?php echo $row ? htmlspecialchars((string)$row->lunch_menu) : ''; ?>" placeholder="e.g. Dal, Rice"></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-12">
    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save all settings</button>
  </div>
</div>
</form>

<?php if (!empty($can_apply_calendar)): ?>
<div class="card shadow-soft mt-3 border-primary border-opacity-25">
  <div class="card-header bg-white fw-semibold"><i class="bi bi-calendar-plus me-1"></i>Apply weekly menu to calendar</div>
  <div class="card-body">
    <p class="text-muted small mb-3">Pushes the template above onto real dates (Monday through Sunday). Employees can then order on <a href="<?php echo site_url('meals'); ?>">My Orders</a>.</p>
    <form method="post" class="row g-2 align-items-end">
      <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
      <input type="hidden" name="action" value="apply_week">
      <div class="col-auto">
        <label class="form-label">Week starting (Monday)</label>
        <input type="date" name="week_start" class="form-control" value="<?php echo htmlspecialchars($default_week_start); ?>" required>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Apply weekly menu to this calendar week? Existing dates will be updated.');"><i class="bi bi-arrow-repeat me-1"></i>Apply to calendar</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

</div>
<?php $this->load->view('partials/footer'); ?>
