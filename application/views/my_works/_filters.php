<?php
  $embed = (bool)$this->input->get('embed');
  $statusLabels = my_works_status_labels();
  $statusColors = my_works_status_colors();
  $baseQuery = array();
  foreach ($filters as $k => $v) {
    if ($k === 'current_user_id') { continue; }
    if ($v !== '' && $v !== 0 && $v !== '0') {
      $baseQuery[$k] = $v;
    }
  }
  $buildUrl = function ($extra) use ($baseQuery, $embed) {
    $q = array_merge($baseQuery, $extra);
    unset($q['page']);
    if ($embed) {
      $q['embed'] = '1';
      $parent_tab = get_instance()->input->get('parent_tab');
      if ($parent_tab) {
        $q['parent_tab'] = $parent_tab;
      }
    }
    $qs = http_build_query($q);
    return site_url('my-works') . ($qs !== '' ? '?' . $qs : '');
  };
  $exportQs = $baseQuery;
  unset($exportQs['page']);
  $exportUrl = site_url('my-works/export') . (empty($exportQs) ? '' : '?' . http_build_query($exportQs));
  $typeLabels = my_works_type_labels();
  $filterWorkType = isset($filters['work_type']) ? (string) $filters['work_type'] : '';
  $filterClientId = isset($filters['client_id']) ? (int) $filters['client_id'] : 0;
  $filterProjectId = isset($filters['project_id']) ? (int) $filters['project_id'] : 0;
  $clients = isset($clients) ? $clients : array();
  $projects = isset($projects) ? $projects : array();
  $listViewMode = isset($view_mode) ? $view_mode : 'list';
?>
<div class="mw-kpi-grid mw-kpi-grid--compact">
  <a class="mw-kpi <?php echo $filters['status'] === '' ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => '', 'view' => $listViewMode)); ?>">
    <div class="lbl">Total</div><div class="val"><?php echo (int) $stats['total']; ?></div>
  </a>
  <a class="mw-kpi <?php echo $filters['status'] === 'new' ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => 'new', 'view' => $listViewMode)); ?>">
    <div class="lbl">New</div><div class="val text-secondary"><?php echo (int) $stats['new']; ?></div>
  </a>
  <a class="mw-kpi <?php echo $filters['status'] === 'in_progress' ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => 'in_progress', 'view' => $listViewMode)); ?>">
    <div class="lbl">In progress</div><div class="val text-primary"><?php echo (int) $stats['in_progress']; ?></div>
  </a>
  <a class="mw-kpi <?php echo $filters['status'] === 'closed' ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => 'closed', 'view' => $listViewMode)); ?>">
    <div class="lbl">Closed</div><div class="val text-success"><?php echo (int) $stats['closed']; ?></div>
  </a>
  <a class="mw-kpi <?php echo !empty($filters['urgent_only']) ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => $filters['status'], 'urgent_only' => empty($filters['urgent_only']) ? 1 : 0, 'view' => $listViewMode)); ?>">
    <div class="lbl">Open urgent</div><div class="val text-danger"><?php echo (int) $stats['urgent']; ?></div>
  </a>
  <?php if (isset($stats['overdue'])): ?>
  <a class="mw-kpi <?php echo !empty($filters['overdue_only']) ? 'active' : ''; ?>" href="<?php echo $buildUrl(array('status' => $filters['status'], 'overdue_only' => empty($filters['overdue_only']) ? 1 : 0, 'view' => $listViewMode)); ?>">
    <div class="lbl">Overdue</div><div class="val text-danger"><?php echo (int) $stats['overdue']; ?></div>
  </a>
  <?php endif; ?>
</div>

<div class="card border-0 shadow-sm mb-2 mw-filter-card">
  <button class="card-header mw-filter-header d-flex align-items-center justify-content-between w-100 border-0"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#mwFilterBody"
          aria-expanded="false"
          aria-controls="mwFilterBody">
    <span class="fw-semibold small text-uppercase"><i class="bi bi-funnel me-1"></i>Search &amp; filters</span>
    <span class="d-flex align-items-center gap-2">
      <span class="text-muted small d-none d-md-inline">Client, type, status, and more</span>
      <i class="bi bi-chevron-up mw-filter-chevron" aria-hidden="true"></i>
    </span>
  </button>
  <div class="collapse" id="mwFilterBody">
    <div class="card-body py-2 px-3">
      <form method="get" action="<?php echo site_url('my-works'); ?>" class="row g-2 align-items-end">
        <input type="hidden" name="view" value="<?php echo esc_view($listViewMode); ?>">
        <?php if ($embed): ?>
        <input type="hidden" name="embed" value="1">
        <?php if ($this->input->get('parent_tab')): ?>
        <input type="hidden" name="parent_tab" value="<?php echo esc_view($this->input->get('parent_tab'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>
        <?php endif; ?>
        <div class="col-12">
          <div class="mw-filter-section-title mb-0"><i class="bi bi-building me-1"></i>Client &amp; project</div>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Client</label>
          <select name="client_id" id="mw-filter-client" class="form-select form-select-sm">
            <option value="0">All clients</option>
            <?php foreach ((array) $clients as $c): ?>
              <option value="<?php echo (int) $c->id; ?>" <?php echo $filterClientId === (int) $c->id ? 'selected' : ''; ?>>
                <?php echo esc_view($c->company_name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Project</label>
          <select name="project_id" id="mw-filter-project" class="form-select form-select-sm">
            <option value="0">All projects</option>
            <?php foreach ((array) $projects as $p): ?>
              <option value="<?php echo (int) $p->id; ?>"
                      data-client-id="<?php echo (!empty($projects_have_client) && isset($p->client_id)) ? (int) $p->client_id : 0; ?>"
                      <?php echo $filterProjectId === (int) $p->id ? 'selected' : ''; ?>>
                <?php echo esc_view($p->name ? $p->name : ('Project #' . (int) $p->id)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Type</label>
          <?php $this->load->view('partials/module_type_select', array(
            'field_name' => 'work_type',
            'options' => $typeLabels,
            'current' => $filterWorkType,
            'placeholder' => 'All types',
            'select_class' => 'form-select form-select-sm',
          )); ?>
        </div>
        <div class="col-12">
          <div class="mw-filter-section-title mb-0 mt-1"><i class="bi bi-sliders me-1"></i>More filters</div>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Search</label>
          <input type="search" name="q" class="form-control form-control-sm" value="<?php echo esc_view($filters['q']); ?>" placeholder="Title, details, tag">
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All</option>
            <?php foreach ($statusLabels as $k => $lbl): ?>
              <option value="<?php echo $k; ?>" <?php echo $filters['status'] === $k ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Tag</label>
          <input type="text" name="tag" class="form-control form-control-sm" list="mw-tag-list" value="<?php echo esc_view($filters['tag']); ?>" placeholder="Tag">
          <datalist id="mw-tag-list">
            <?php foreach ((array) $tags as $t): ?>
              <option value="<?php echo esc_view($t); ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">My involvement</label>
          <select name="involvement" class="form-select form-select-sm">
            <option value="all" <?php echo $filters['involvement'] === 'all' ? 'selected' : ''; ?>>All mine</option>
            <option value="created" <?php echo $filters['involvement'] === 'created' ? 'selected' : ''; ?>>I created</option>
            <option value="assigned" <?php echo $filters['involvement'] === 'assigned' ? 'selected' : ''; ?>>Assigned to me</option>
          </select>
        </div>
        <?php if (!empty($can_filter_users)): ?>
        <div class="col-6 col-md-2">
          <label class="form-label">Created for</label>
          <select name="created_for" class="form-select form-select-sm">
            <option value="0">Anyone</option>
            <?php foreach ((array) $users as $u): ?>
              <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $filters['created_for'] === (int) $u->id ? 'selected' : ''; ?>>
                <?php echo esc_view(my_works_user_label($u->name, $u->email, $u->id)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-6 col-md-2">
          <label class="form-label">Created by</label>
          <select name="created_by" class="form-select form-select-sm">
            <option value="0">Anyone</option>
            <?php foreach ((array) $users as $u): ?>
              <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $filters['created_by'] === (int) $u->id ? 'selected' : ''; ?>>
                <?php echo esc_view(my_works_user_label($u->name, $u->email, $u->id)); ?>
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
          <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i>Apply</button>
        </div>
        <div class="col-6 col-md-2">
          <a class="btn btn-outline-secondary btn-sm w-100" href="<?php echo site_url('my-works?view=' . urlencode($listViewMode)); ?>">Reset</a>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="mw-list-toolbar">
  <div class="mw-list-toolbar-meta">
    <?php if (!empty($total_rows)): ?>
      <span class="mw-list-count">
        <strong><?php echo (int) $total_rows; ?></strong>
        item<?php echo (int) $total_rows === 1 ? '' : 's'; ?> found
        <?php if (!empty($list_capped)): ?>
          <span class="text-warning">(showing first <?php echo isset($list_shown_count) ? (int) $list_shown_count : 0; ?> — narrow filters or export for full set)</span>
        <?php endif; ?>
      </span>
    <?php else: ?>
      <span class="mw-list-count text-muted">No matching items</span>
    <?php endif; ?>
  </div>
  <?php if (!empty($can_export)): ?>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo esc_view($exportUrl); ?>" title="Export CSV">
    <i class="bi bi-download me-1"></i>Export CSV
  </a>
  <?php endif; ?>
</div>

<?php if (!empty($clients) && !empty($projects)): ?>
<script>
(function () {
  var clientSelect = document.getElementById('mw-filter-client');
  var projectSelect = document.getElementById('mw-filter-project');
  if (!clientSelect || !projectSelect) return;

  var allOptions = [];
  for (var i = 0; i < projectSelect.options.length; i++) {
    var opt = projectSelect.options[i];
    allOptions.push({
      value: opt.value,
      text: opt.textContent,
      clientId: opt.getAttribute('data-client-id') || '0',
      selected: opt.selected
    });
  }

  function filterProjects() {
    var clientId = String(clientSelect.value || '0');
    var current = projectSelect.value;
    projectSelect.innerHTML = '';
    var all = document.createElement('option');
    all.value = '0';
    all.textContent = 'All';
    projectSelect.appendChild(all);

    var hasMatch = false;
    for (var j = 0; j < allOptions.length; j++) {
      var row = allOptions[j];
      if (row.value === '0') continue;
      if (clientId === '0' || row.clientId === '0' || row.clientId === clientId) {
        var o = document.createElement('option');
        o.value = row.value;
        o.textContent = row.text;
        o.setAttribute('data-client-id', row.clientId);
        if (row.value === current) {
          o.selected = true;
          hasMatch = true;
        }
        projectSelect.appendChild(o);
      }
    }
    if (!hasMatch && current !== '0') {
      projectSelect.value = '0';
    }
  }

  clientSelect.addEventListener('change', filterProjects);
  <?php if (!empty($projects_have_client)): ?>
  filterProjects();
  <?php endif; ?>
})();
</script>
<?php endif; ?>

<script>
(function () {
  var form = document.getElementById('mwListSearchForm');
  if (!form) {
    return;
  }
  var input = form.querySelector('input[type="search"][name="q"]');
  if (!input) {
    return;
  }
  var timer = null;
  function submitSearch() {
    if (typeof form.requestSubmit === 'function') {
      form.requestSubmit();
    } else {
      form.submit();
    }
  }
  input.addEventListener('input', function () {
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(submitSearch, 350);
  });
  input.addEventListener('search', function () {
    if (timer) {
      clearTimeout(timer);
    }
    submitSearch();
  });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (timer) {
        clearTimeout(timer);
      }
      submitSearch();
    }
  });
})();
</script>
