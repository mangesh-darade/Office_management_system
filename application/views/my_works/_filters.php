<?php
  $statusLabels = my_works_status_labels();
  $statusColors = my_works_status_colors();
  $baseQuery = array();
  foreach ($filters as $k => $v) {
    if ($k === 'current_user_id') { continue; }
    if ($v !== '' && $v !== 0 && $v !== '0') {
      $baseQuery[$k] = $v;
    }
  }
  $buildUrl = function ($extra) use ($baseQuery) {
    $q = array_merge($baseQuery, $extra);
    unset($q['page']);
    $qs = http_build_query($q);
    return site_url('my-works') . ($qs !== '' ? '?' . $qs : '');
  };
  $exportQs = $baseQuery;
  unset($exportQs['page']);
  $exportUrl = site_url('my-works/export') . (empty($exportQs) ? '' : '?' . http_build_query($exportQs));
?>
<div class="mw-kpi-grid mb-3">
  <a class="mw-kpi <?php echo $filters['status'] === '' ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => '', 'view' => isset($view_mode) ? $view_mode : 'list')); ?>">
    <div class="lbl">Total</div><div class="val"><?php echo (int) $stats['total']; ?></div>
  </a>
  <a class="mw-kpi <?php echo $filters['status'] === 'new' ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => 'new', 'view' => isset($view_mode) ? $view_mode : 'list')); ?>">
    <div class="lbl">New</div><div class="val text-secondary"><?php echo (int) $stats['new']; ?></div>
  </a>
  <a class="mw-kpi <?php echo $filters['status'] === 'in_progress' ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => 'in_progress', 'view' => isset($view_mode) ? $view_mode : 'list')); ?>">
    <div class="lbl">In progress</div><div class="val text-primary"><?php echo (int) $stats['in_progress']; ?></div>
  </a>
  <a class="mw-kpi <?php echo $filters['status'] === 'closed' ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => 'closed', 'view' => isset($view_mode) ? $view_mode : 'list')); ?>">
    <div class="lbl">Closed</div><div class="val text-success"><?php echo (int) $stats['closed']; ?></div>
  </a>
  <a class="mw-kpi <?php echo !empty($filters['urgent_only']) ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => $filters['status'], 'urgent_only' => empty($filters['urgent_only']) ? 1 : 0, 'view' => isset($view_mode) ? $view_mode : 'list')); ?>">
    <div class="lbl">Open urgent</div><div class="val text-danger"><?php echo (int) $stats['urgent']; ?></div>
  </a>
  <?php if (isset($stats['overdue'])): ?>
  <a class="mw-kpi <?php echo !empty($filters['overdue_only']) ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => $filters['status'], 'overdue_only' => empty($filters['overdue_only']) ? 1 : 0, 'view' => isset($view_mode) ? $view_mode : 'list')); ?>">
    <div class="lbl">Overdue</div><div class="val text-danger"><?php echo (int) $stats['overdue']; ?></div>
  </a>
  <?php endif; ?>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
  <button class="btn btn-outline-secondary btn-sm mw-filter-toggle d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#mwFilterPanel" aria-expanded="false">
    <i class="bi bi-funnel me-1"></i>Filters &amp; search
  </button>
  <?php if (!empty($can_export)): ?>
  <a class="btn btn-outline-secondary btn-sm ms-md-auto" href="<?php echo htmlspecialchars($exportUrl); ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
  <?php endif; ?>
</div>

<div class="collapse mw-filter-panel d-md-block" id="mwFilterPanel">
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <form method="get" action="<?php echo site_url('my-works'); ?>" class="row g-2 align-items-end">
        <input type="hidden" name="view" value="<?php echo htmlspecialchars(isset($view_mode) ? $view_mode : 'list'); ?>">
        <div class="col-12 col-md-3">
          <label class="form-label small text-muted mb-0">Search</label>
          <input type="search" name="q" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filters['q']); ?>" placeholder="Title, details, tag">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small text-muted mb-0">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All</option>
            <?php foreach ($statusLabels as $k => $lbl): ?>
              <option value="<?php echo $k; ?>" <?php echo $filters['status'] === $k ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small text-muted mb-0">Tag</label>
          <input type="text" name="tag" class="form-control form-control-sm" list="mw-tag-list" value="<?php echo htmlspecialchars($filters['tag']); ?>" placeholder="Tag">
          <datalist id="mw-tag-list">
            <?php foreach ((array) $tags as $t): ?>
              <option value="<?php echo htmlspecialchars($t); ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small text-muted mb-0">My involvement</label>
          <select name="involvement" class="form-select form-select-sm">
            <option value="all" <?php echo $filters['involvement'] === 'all' ? 'selected' : ''; ?>>All mine</option>
            <option value="created" <?php echo $filters['involvement'] === 'created' ? 'selected' : ''; ?>>I created</option>
            <option value="assigned" <?php echo $filters['involvement'] === 'assigned' ? 'selected' : ''; ?>>Assigned to me</option>
          </select>
        </div>
        <?php if (!empty($can_filter_users)): ?>
        <div class="col-6 col-md-2">
          <label class="form-label small text-muted mb-0">Created for</label>
          <select name="created_for" class="form-select form-select-sm">
            <option value="0">Anyone</option>
            <?php foreach ((array) $users as $u): ?>
              <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $filters['created_for'] === (int) $u->id ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars(my_works_user_label($u->name, $u->email, $u->id)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label small text-muted mb-0">Created by</label>
          <select name="created_by" class="form-select form-select-sm">
            <option value="0">Anyone</option>
            <?php foreach ((array) $users as $u): ?>
              <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $filters['created_by'] === (int) $u->id ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars(my_works_user_label($u->name, $u->email, $u->id)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="col-12 col-md-4 d-flex flex-wrap gap-3 align-items-center">
          <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" name="urgent_only" value="1" id="fUrgent" <?php echo !empty($filters['urgent_only']) ? 'checked' : ''; ?>>
            <label class="form-check-label small" for="fUrgent">Urgent</label>
          </div>
          <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" name="important_only" value="1" id="fImportant" <?php echo !empty($filters['important_only']) ? 'checked' : ''; ?>>
            <label class="form-check-label small" for="fImportant">Important</label>
          </div>
          <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" name="overdue_only" value="1" id="fOverdue" <?php echo !empty($filters['overdue_only']) ? 'checked' : ''; ?>>
            <label class="form-check-label small" for="fOverdue">Overdue</label>
          </div>
        </div>
        <div class="col-6 col-md-2">
          <button type="submit" class="btn btn-sm btn-primary w-100">Apply</button>
        </div>
        <div class="col-6 col-md-2">
          <a class="btn btn-sm btn-outline-secondary w-100" href="<?php echo site_url('my-works?view=' . (isset($view_mode) ? urlencode($view_mode) : 'list')); ?>">Reset</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (!empty($total_rows)): ?>
  <p class="small text-muted mb-2">
    <?php echo (int) $total_rows; ?> item<?php echo (int) $total_rows === 1 ? '' : 's'; ?> found
    <?php if (!empty($list_capped)): ?>
      <span class="text-warning">(showing first <?php echo isset($list_shown_count) ? (int) $list_shown_count : 0; ?> — narrow filters or export for full set)</span>
    <?php endif; ?>
  </p>
<?php endif; ?>
