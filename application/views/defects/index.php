<?php
$clients = isset($clients) ? $clients : array();
$projects = isset($projects) ? $projects : array();
$members = isset($members) ? $members : array();
$filters = isset($filters) ? $filters : array();
$rows = isset($rows) ? $rows : array();
$total = isset($total) ? (int) $total : 0;
$can_add = function_exists('has_module_access') && (has_module_access('defects_add') || has_module_access('defects'));
$can_export = function_exists('has_module_access') && (has_module_access('defects_export') || has_module_access('defects_list') || has_module_access('defects'));
$can_edit = function_exists('has_module_access') && (has_module_access('defects_edit') || has_module_access('defects'));
$embed = (bool) $this->input->get('embed');
$parent_tab = trim((string) $this->input->get('parent_tab'));

$sev_class = function ($s) {
    $s = strtolower((string) $s);
    if ($s === 'critical') {
        return 'defect-pill defect-pill--critical';
    }
    if ($s === 'high') {
        return 'defect-pill defect-pill--high';
    }
    if ($s === 'medium') {
        return 'defect-pill defect-pill--medium';
    }
    return 'defect-pill defect-pill--low';
};
$status_class = function ($s) {
    $s = strtolower((string) $s);
    if (in_array($s, array('open'), true)) {
        return 'defect-pill defect-pill--open';
    }
    if (in_array($s, array('in_progress'), true)) {
        return 'defect-pill defect-pill--progress';
    }
    if (in_array($s, array('fixed', 'verified'), true)) {
        return 'defect-pill defect-pill--fixed';
    }
    if ($s === 'closed') {
        return 'defect-pill defect-pill--closed';
    }
    return 'defect-pill defect-pill--muted';
};

$create_url = site_url('defects/create');
if ($embed) {
    $create_url .= '?redirect=' . rawurlencode('my-works?tab=defects');
}

if (!$embed) {
    $this->load->view('partials/header', array(
        'title' => 'Defects',
        'extra_css' => array('assets/css/defects-form.css'),
    ));
}
?>
<div class="container-fluid defect-list-page<?php echo $embed ? ' mw-defect-embed p-0' : ' py-3'; ?>">
<?php
ob_start();
if ($can_export) {
    $export_q = safe_query_string();
    echo '<a class="btn btn-outline-secondary" href="' . site_url('defects/export' . ($export_q ? '?' . $export_q : '')) . '" title="Export CSV"><i class="bi bi-download me-1"></i>Export</a>';
}
if ($can_add) {
    echo '<a class="btn btn-outline-secondary" href="' . site_url('defects/import') . '" title="Import CSV"><i class="bi bi-upload me-1"></i>Import</a>';
    echo '<a class="btn btn-primary" href="' . esc_view($create_url) . '" title="Log Defect"><i class="bi bi-plus-lg me-1"></i>Log Defect</a>';
}
$actions = ob_get_clean();
if (!$embed) {
    $this->load->view('partials/oms_page_head', array(
        'title' => 'Defect Tracking',
        'icon' => 'bi-bug',
        'subtitle' => 'Filter by client, project, or assignee — then open and resolve bugs',
        'actions_html' => '',
    ));
}
?>

<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success border-0 shadow-sm py-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger border-0 shadow-sm py-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>

<div class="defect-list-bar card">
  <div class="card-body">
    <div class="defect-list-actions-section">
      <p class="defect-list-count mb-0"><?php echo $total; ?> defect<?php echo $total === 1 ? '' : 's'; ?></p>
      <?php if (trim($actions) !== ''): ?>
        <div class="defect-list-toolbar btn-group btn-group-sm" role="group" aria-label="Defect actions">
          <?php echo $actions; ?>
        </div>
      <?php endif; ?>
    </div>

    <form class="defect-filter-form" method="get" action="<?php echo site_url('defects'); ?>" id="defectListFilters">
      <?php if ($embed): ?>
        <input type="hidden" name="embed" value="1">
        <?php if ($parent_tab !== ''): ?>
          <input type="hidden" name="parent_tab" value="<?php echo esc_view($parent_tab); ?>">
        <?php endif; ?>
      <?php endif; ?>

        <label class="defect-filter-field defect-filter-search">
          <span class="defect-filter-label">Search</span>
          <input type="search" name="q" id="fltQ" class="form-control form-control-sm" placeholder="ID or title…" value="<?php echo esc_view($filters['q'] ?? ''); ?>" autocomplete="off">
        </label>

        <label class="defect-filter-field">
          <span class="defect-filter-label">Client</span>
          <select name="client_id" id="fltClient" class="form-select form-select-sm">
            <option value="0">All clients</option>
            <?php foreach ($clients as $c): ?>
              <option value="<?php echo (int) $c->id; ?>" <?php echo (!empty($filters['client_id']) && (int) $filters['client_id'] === (int) $c->id) ? 'selected' : ''; ?>>
                <?php echo esc_view($c->company_name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="defect-filter-field">
          <span class="defect-filter-label">Project</span>
          <select name="project_id" id="fltProject" class="form-select form-select-sm">
            <option value="0">All projects</option>
            <?php foreach ($projects as $p): ?>
              <?php $pcid = isset($p->client_id) ? (int) $p->client_id : 0; ?>
              <option value="<?php echo (int) $p->id; ?>"
                      data-client-id="<?php echo $pcid; ?>"
                      <?php echo (!empty($filters['project_id']) && (int) $filters['project_id'] === (int) $p->id) ? 'selected' : ''; ?>>
                <?php echo esc_view($p->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="defect-filter-field defect-filter-narrow">
          <span class="defect-filter-label">Status</span>
          <select name="status" id="fltStatus" class="form-select form-select-sm">
            <option value="">All</option>
            <?php foreach (array('open', 'in_progress', 'fixed', 'verified', 'closed', 'rejected') as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo (!empty($filters['status']) && $filters['status'] === $s) ? 'selected' : ''; ?>>
                <?php echo ucfirst(str_replace('_', ' ', $s)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="defect-filter-field defect-filter-narrow">
          <span class="defect-filter-label">Severity</span>
          <select name="severity" id="fltSeverity" class="form-select form-select-sm">
            <option value="">All</option>
            <?php foreach (array('low', 'medium', 'high', 'critical') as $s): ?>
              <option value="<?php echo $s; ?>" <?php echo (!empty($filters['severity']) && $filters['severity'] === $s) ? 'selected' : ''; ?>>
                <?php echo ucfirst($s); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="defect-filter-field">
          <span class="defect-filter-label">Assignee</span>
          <select name="assigned_to" id="fltAssignee" class="form-select form-select-sm">
            <option value="0">All</option>
            <?php foreach ($members as $m): ?>
              <option value="<?php echo (int) $m->id; ?>" <?php echo (!empty($filters['assigned_to']) && (int) $filters['assigned_to'] === (int) $m->id) ? 'selected' : ''; ?>>
                <?php echo esc_view($m->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="defect-filter-overdue">
          <input class="form-check-input" type="checkbox" name="overdue" value="1" id="fltOverdue" <?php echo !empty($filters['overdue']) ? 'checked' : ''; ?>>
          <span>Overdue</span>
        </label>

        <div class="defect-filter-actions">
          <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </div>
      </form>
  </div>
</div>

<?php if (empty($rows)): ?>
  <div class="defect-list-empty card border-0 shadow-sm">
    <div class="card-body text-center py-5">
      <div class="defect-list-empty-icon mb-2"><i class="bi bi-bug"></i></div>
      <h2 class="h6 mb-1">No defects found</h2>
      <p class="text-muted small mb-3">Try clearing filters, or log a new defect.</p>
      <?php if ($can_add): ?>
        <a class="btn btn-primary btn-sm" href="<?php echo esc_view($create_url); ?>"><i class="bi bi-plus-lg me-1"></i>Log Defect</a>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>

  <!-- Mobile cards -->
  <div class="defect-mobile-list d-md-none">
    <?php foreach ($rows as $r): ?>
      <?php
        $overdue = function_exists('defect_is_overdue') && defect_is_overdue($r);
        $client_name = isset($r->client_name) && $r->client_name !== '' ? $r->client_name : '—';
      ?>
      <article class="defect-mobile-card<?php echo $overdue ? ' is-overdue' : ''; ?>">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
          <a class="defect-mobile-id" href="<?php echo site_url('defects/view/' . (int) $r->id); ?>"><?php echo esc_view($r->defect_number); ?></a>
          <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
            <?php if ($can_edit): ?>
              <a href="<?php echo site_url('defects/edit/' . (int) $r->id); ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
          </div>
        </div>
        <div class="defect-mobile-title"><?php echo esc_view($r->title); ?><?php if ($overdue): ?> <span class="badge bg-danger">Overdue</span><?php endif; ?></div>
        <div class="defect-mobile-meta">
          <span><i class="bi bi-building"></i> <?php echo esc_view($client_name); ?></span>
          <span><i class="bi bi-folder"></i> <?php echo esc_view($r->project_name ?: '—'); ?></span>
        </div>
        <div class="d-flex flex-wrap gap-1 mt-2">
          <span class="<?php echo esc_view($sev_class($r->severity)); ?>"><?php echo esc_view(ucfirst((string) $r->severity)); ?></span>
          <span class="<?php echo esc_view($status_class($r->status)); ?>"><?php echo esc_view(ucfirst(str_replace('_', ' ', (string) $r->status))); ?></span>
          <span class="defect-pill defect-pill--muted"><?php echo esc_view($r->assignee_name ?: 'Unassigned'); ?></span>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Desktop table -->
  <div class="card border-0 shadow-sm defect-list-table-card d-none d-md-block">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 defect-list-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Client</th>
            <th>Project</th>
            <th>Severity</th>
            <th>Status</th>
            <th>Due</th>
            <th>Assignee</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php
              $overdue = function_exists('defect_is_overdue') && defect_is_overdue($r);
              $client_name = isset($r->client_name) && $r->client_name !== '' ? $r->client_name : '—';
            ?>
            <tr class="<?php echo $overdue ? 'defect-row-overdue' : ''; ?>">
              <td>
                <a class="defect-id-link" href="<?php echo site_url('defects/view/' . (int) $r->id); ?>"><?php echo esc_view($r->defect_number); ?></a>
              </td>
              <td>
                <div class="defect-title-cell">
                  <?php echo esc_view($r->title); ?>
                  <?php if ($overdue): ?><span class="badge bg-danger ms-1">Overdue</span><?php endif; ?>
                </div>
              </td>
              <td><span class="text-muted"><?php echo esc_view($client_name); ?></span></td>
              <td><?php echo esc_view($r->project_name ?: '—'); ?></td>
              <td><span class="<?php echo esc_view($sev_class($r->severity)); ?>"><?php echo esc_view(ucfirst((string) $r->severity)); ?></span></td>
              <td><span class="<?php echo esc_view($status_class($r->status)); ?>"><?php echo esc_view(ucfirst(str_replace('_', ' ', (string) $r->status))); ?></span></td>
              <td class="text-nowrap"><?php echo esc_view(!empty($r->due_date) ? $r->due_date : '—'); ?></td>
              <td><?php echo esc_view($r->assignee_name ?: '—'); ?></td>
              <td class="text-end text-nowrap">
                <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                  <?php if ($can_edit): ?>
                    <a href="<?php echo site_url('defects/edit/' . (int) $r->id); ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($pagination_links)): ?>
      <div class="card-footer bg-white border-top-0"><?php echo $pagination_links; ?></div>
    <?php endif; ?>
  </div>

  <?php if (!empty($pagination_links)): ?>
    <div class="d-md-none mt-3"><?php echo $pagination_links; ?></div>
  <?php endif; ?>
<?php endif; ?>
</div>
<script>
(function () {
  var clientSel = document.getElementById('fltClient');
  var projectSel = document.getElementById('fltProject');
  if (!clientSel || !projectSel) { return; }
  var all = [];
  for (var i = 0; i < projectSel.options.length; i++) {
    var opt = projectSel.options[i];
    all.push({
      value: opt.value,
      text: opt.textContent,
      clientId: opt.getAttribute('data-client-id') || '0',
      selected: opt.selected
    });
  }
  function filterProjects() {
    var cid = String(clientSel.value || '0');
    var keep = projectSel.value;
    projectSel.innerHTML = '';
    all.forEach(function (p) {
      if (p.value !== '0' && p.value !== '' && cid !== '0' && String(p.clientId) !== String(cid)) {
        return;
      }
      var o = document.createElement('option');
      o.value = p.value;
      o.textContent = p.text;
      o.setAttribute('data-client-id', p.clientId);
      if (String(p.value) === String(keep)) { o.selected = true; }
      projectSel.appendChild(o);
    });
  }
  clientSel.addEventListener('change', filterProjects);
  filterProjects();
})();
</script>
<?php if (!$embed) {
    $this->load->view('partials/footer');
} ?>
