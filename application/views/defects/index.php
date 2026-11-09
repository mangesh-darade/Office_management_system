<?php
$clients = isset($clients) ? $clients : array();
$projects = isset($projects) ? $projects : array();
$members = isset($members) ? $members : array();
$filters = isset($filters) ? $filters : array();
$rows = isset($rows) ? $rows : array();
$total = isset($total) ? (int) $total : 0;
$sort = isset($sort) ? (string) $sort : (isset($filters['sort']) ? (string) $filters['sort'] : 'created_at');
$dir = isset($dir) ? (string) $dir : (isset($filters['dir']) ? (string) $filters['dir'] : 'desc');
if ($dir !== 'asc' && $dir !== 'desc') {
    $dir = 'desc';
}
$can_add = function_exists('has_module_access') && (has_module_access('defects_add') || has_module_access('defects'));
$can_export = function_exists('has_module_access') && (has_module_access('defects_export') || has_module_access('defects_list') || has_module_access('defects'));
$can_edit = function_exists('has_module_access') && (has_module_access('defects_edit') || has_module_access('defects'));
$embed = (bool) $this->input->get('embed');
$parent_tab = trim((string) $this->input->get('parent_tab'));

$defect_sort_url = function ($col) use ($filters, $sort, $dir, $embed, $parent_tab) {
    $next_dir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
    if ($sort !== $col) {
        $next_dir = ($col === 'created_at' || $col === 'due' || $col === 'id') ? 'desc' : 'asc';
    }
    $params = array(
        'status' => isset($filters['status']) ? $filters['status'] : '',
        'severity' => isset($filters['severity']) ? $filters['severity'] : '',
        'project_id' => !empty($filters['project_id']) ? (int) $filters['project_id'] : 0,
        'client_id' => !empty($filters['client_id']) ? (int) $filters['client_id'] : 0,
        'assigned_to' => !empty($filters['assigned_to']) ? (int) $filters['assigned_to'] : 0,
        'q' => isset($filters['q']) ? $filters['q'] : '',
        'sort' => $col,
        'dir' => $next_dir,
    );
    if (!empty($filters['overdue'])) {
        $params['overdue'] = '1';
    }
    if ($embed) {
        $params['embed'] = '1';
    }
    if ($parent_tab !== '') {
        $params['parent_tab'] = $parent_tab;
    }
    foreach ($params as $k => $v) {
        if ($v === '' || $v === 0 || $v === '0') {
            unset($params[$k]);
        }
    }
    return site_url('defects') . '?' . http_build_query($params);
};

$defect_sort_th = function ($col, $label, $extra_class = '') use ($defect_sort_url, $sort, $dir) {
    $icon = 'bi-arrow-down-up';
    $aria = 'Sort by ' . $label;
    if ($sort === $col) {
        $icon = ($dir === 'asc') ? 'bi-sort-up' : 'bi-sort-down';
        $aria = $label . ' sorted ' . $dir;
    }
    $cls = trim('defect-sort-th ' . $extra_class);
    echo '<th class="' . esc_view($cls, ENT_QUOTES, 'UTF-8') . '">';
    echo '<a href="' . esc_view($defect_sort_url($col), ENT_QUOTES, 'UTF-8') . '" class="defect-sort-link text-decoration-none text-body" title="' . esc_view($aria, ENT_QUOTES, 'UTF-8') . '" aria-label="' . esc_view($aria, ENT_QUOTES, 'UTF-8') . '">';
    echo esc_view($label);
    echo ' <i class="bi ' . esc_view($icon, ENT_QUOTES, 'UTF-8') . ' small text-muted"></i>';
    echo '</a></th>';
};

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
      <input type="hidden" name="sort" value="<?php echo esc_view($sort); ?>">
      <input type="hidden" name="dir" value="<?php echo esc_view($dir); ?>">

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
      <article class="defect-mobile-card<?php echo $overdue ? ' is-overdue' : ''; ?><?php echo empty($r->is_active) ? ' is-inactive' : ''; ?>" data-defect-id="<?php echo (int) $r->id; ?>">
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
        <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
          <span class="<?php echo esc_view($sev_class($r->severity)); ?>"><?php echo esc_view(ucfirst((string) $r->severity)); ?></span>
          <span class="<?php echo esc_view($status_class($r->status)); ?>"><?php echo esc_view(ucfirst(str_replace('_', ' ', (string) $r->status))); ?></span>
          <span class="defect-pill defect-pill--muted"><?php echo esc_view($r->assignee_name ?: 'Unassigned'); ?></span>
          <span class="defect-pill defect-pill--muted" title="Created by"><i class="bi bi-person"></i> <?php echo esc_view(!empty($r->reporter_name) ? $r->reporter_name : '—'); ?></span>
          <?php if (!empty($r->created_at)): ?>
            <span class="defect-pill defect-pill--muted" title="Created at"><i class="bi bi-clock"></i> <?php
              $ts = strtotime($r->created_at);
              echo esc_view($ts ? date('Y-m-d H:i', $ts) : $r->created_at);
            ?></span>
          <?php endif; ?>
          <?php if ($can_edit): ?>
            <div class="form-check form-switch defect-active-switch mb-0">
              <input class="form-check-input defect-active-toggle" type="checkbox" role="switch"
                     id="defectActiveM<?php echo (int) $r->id; ?>"
                     data-id="<?php echo (int) $r->id; ?>"
                     title="Active"
                     aria-label="Active"
                     <?php echo !empty($r->is_active) ? 'checked' : ''; ?>>
              <label class="form-check-label small" for="defectActiveM<?php echo (int) $r->id; ?>">Active</label>
            </div>
          <?php else: ?>
            <span class="defect-pill <?php echo !empty($r->is_active) ? 'defect-pill--fixed' : 'defect-pill--muted'; ?>">
              <?php echo !empty($r->is_active) ? 'Active' : 'Inactive'; ?>
            </span>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <!-- Desktop table -->
  <div class="card border-0 shadow-sm defect-list-table-card d-none d-md-block">
    <?php if ($can_add): ?>
    <div class="defect-add-bar px-2 py-1 border-bottom d-flex justify-content-end">
      <button type="button" class="btn btn-sm btn-outline-primary defect-inline-add py-0 px-2" title="Add defect"><i class="bi bi-plus-lg"></i></button>
    </div>
    <?php endif; ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 defect-list-table">
        <thead>
          <tr>
            <?php $defect_sort_th('id', 'ID'); ?>
            <?php $defect_sort_th('title', 'Title'); ?>
            <?php $defect_sort_th('client', 'Client'); ?>
            <?php $defect_sort_th('project', 'Project'); ?>
            <?php $defect_sort_th('severity', 'Severity'); ?>
            <?php $defect_sort_th('status', 'Status'); ?>
            <?php $defect_sort_th('active', 'Active'); ?>
            <?php $defect_sort_th('due', 'Due'); ?>
            <?php $defect_sort_th('assignee', 'Assignee'); ?>
            <?php $defect_sort_th('created_by', 'Created by'); ?>
            <?php $defect_sort_th('created_at', 'Created at'); ?>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <?php
              $overdue = function_exists('defect_is_overdue') && defect_is_overdue($r);
              $client_name = isset($r->client_name) && $r->client_name !== '' ? $r->client_name : '—';
            ?>
            <tr class="defect-row <?php echo $overdue ? 'defect-row-overdue' : ''; ?><?php echo empty($r->is_active) ? ' defect-row-inactive' : ''; ?>" data-defect-id="<?php echo (int) $r->id; ?>" data-project-id="<?php echo (int) $r->project_id; ?>" data-severity="<?php echo esc_view((string) $r->severity); ?>" data-status="<?php echo esc_view((string) $r->status); ?>" data-due="<?php echo esc_view((string) $r->due_date); ?>" data-assigned-id="<?php echo (int) $r->assigned_to; ?>">
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
              <td>
                <?php if ($can_edit): ?>
                  <div class="form-check form-switch defect-active-switch mb-0">
                    <input class="form-check-input defect-active-toggle" type="checkbox" role="switch"
                           id="defectActive<?php echo (int) $r->id; ?>"
                           data-id="<?php echo (int) $r->id; ?>"
                           title="Active"
                           aria-label="Active"
                           <?php echo !empty($r->is_active) ? 'checked' : ''; ?>>
                    <label class="form-check-label visually-hidden" for="defectActive<?php echo (int) $r->id; ?>">Active</label>
                  </div>
                <?php else: ?>
                  <span class="defect-pill <?php echo !empty($r->is_active) ? 'defect-pill--fixed' : 'defect-pill--muted'; ?>">
                    <?php echo !empty($r->is_active) ? 'Active' : 'Inactive'; ?>
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-nowrap"><?php echo esc_view(!empty($r->due_date) ? $r->due_date : '—'); ?></td>
              <td><?php echo esc_view($r->assignee_name ?: '—'); ?></td>
              <td><?php echo esc_view(!empty($r->reporter_name) ? $r->reporter_name : '—'); ?></td>
              <td class="text-nowrap"><?php
                if (!empty($r->created_at)) {
                  $ts = strtotime($r->created_at);
                  echo esc_view($ts ? date('Y-m-d H:i', $ts) : $r->created_at);
                } else {
                  echo '—';
                }
              ?></td>
              <td class="text-end text-nowrap">
                <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                  <?php if ($can_edit): ?>
                    <button type="button" class="btn btn-outline-primary defect-inline-edit" title="Edit row"><i class="bi bi-pencil"></i></button>
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
  var table = document.querySelector('.defect-list-table');
  if (!table) { return; }
  var card = table.closest('.defect-list-table-card') || table;
  var saveUrl = <?php echo json_encode(site_url('defects/inline-save')); ?>;
  var csrfName = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var csrfHash = <?php echo json_encode($this->security->get_csrf_hash()); ?>;
  var viewBase = <?php echo json_encode(site_url('defects/view/')); ?>;
  var projects = <?php echo json_encode(array_map(function ($p) { return array('id' => (int) $p->id, 'name' => (string) $p->name); }, $projects)); ?>;
  var members = <?php echo json_encode(array_map(function ($m) { return array('id' => (int) $m->id, 'name' => (string) $m->name); }, isset($inline_members) && is_array($inline_members) ? $inline_members : $members)); ?>;
  var severities = ['low', 'medium', 'high', 'critical'];
  var statuses = ['open', 'in_progress', 'fixed', 'verified', 'closed', 'rejected'];
  var userName = <?php echo json_encode((string) $this->session->userdata('name')); ?>;

  function esc(v) { var d = document.createElement('div'); d.textContent = v == null ? '' : String(v); return d.innerHTML; }
  function label(v) { v = String(v || ''); return v.charAt(0).toUpperCase() + v.slice(1).replace(/_/g, ' '); }
  function opts(list, sel, blank) {
    var h = blank ? '<option value="">' + esc(blank) + '</option>' : '';
    list.forEach(function (o) {
      var v = typeof o === 'object' ? o.id : o, t = typeof o === 'object' ? o.name : label(o);
      h += '<option value="' + esc(v) + '"' + (String(v) === String(sel) ? ' selected' : '') + '>' + esc(t) + '</option>';
    });
    return h;
  }
  var sevClass = <?php echo json_encode(array('low' => $sev_class('low'), 'medium' => $sev_class('medium'), 'high' => $sev_class('high'), 'critical' => $sev_class('critical'))); ?>;
  var stClass = <?php echo json_encode(array_combine(array('open', 'in_progress', 'fixed', 'verified', 'closed', 'rejected'), array_map($status_class, array('open', 'in_progress', 'fixed', 'verified', 'closed', 'rejected')))); ?>;

  function setState(row, text, isErr) {
    var el = row.querySelector('.defect-inline-state');
    if (!el) { return; }
    el.textContent = text || '';
    el.classList.toggle('text-danger', !!isErr);
  }

  function buildEdit(row, d) {
    var c = row.cells;
    c[1].innerHTML = '<input type="text" class="form-control form-control-sm def-f-title" maxlength="255" placeholder="Title" value="' + esc(d.title) + '">';
    c[3].innerHTML = '<select class="form-select form-select-sm def-f-project">' + opts(projects, d.project_id, '— Project —') + '</select>';
    c[4].innerHTML = '<select class="form-select form-select-sm def-f-severity">' + opts(severities, d.severity) + '</select>';
    c[5].innerHTML = '<select class="form-select form-select-sm def-f-status">' + opts(statuses, d.status) + '</select>';
    c[7].innerHTML = '<input type="date" class="form-control form-control-sm def-f-due" value="' + esc(d.due) + '">';
    c[8].innerHTML = '<select class="form-select form-select-sm def-f-assignee">' + opts(members, d.assigned_id, 'Unassigned') + '</select>';
    c[11].innerHTML = '<span class="defect-inline-state text-muted small me-1"></span><div class="btn-group btn-group-sm">'
      + '<button type="button" class="btn btn-primary defect-inline-save" title="Save"><i class="bi bi-check-lg"></i></button>'
      + '<button type="button" class="btn btn-outline-secondary defect-inline-cancel" title="Cancel"><i class="bi bi-x-lg"></i></button></div>';
  }

  function readData(row) {
    return {
      title: (row.cells[1].textContent || '').replace('Overdue', '').trim(),
      project_id: row.dataset.projectId || 0,
      severity: row.dataset.severity || 'medium',
      status: row.dataset.status || 'open',
      due: row.dataset.due || '',
      assigned_id: row.dataset.assignedId || 0
    };
  }

  function applyView(row, d) {
    var c = row.cells;
    c[0].innerHTML = '<a class="defect-id-link" href="' + esc(d.view_url) + '">' + esc(d.defect_number) + '</a>';
    c[1].innerHTML = '<div class="defect-title-cell">' + esc(d.title) + '</div>';
    c[2].innerHTML = '<span class="text-muted">' + esc(d.client_name || '—') + '</span>';
    c[3].textContent = d.project_name || '—';
    c[4].innerHTML = '<span class="' + esc(sevClass[d.severity] || '') + '">' + esc(label(d.severity)) + '</span>';
    c[5].innerHTML = '<span class="' + esc(stClass[d.status] || '') + '">' + esc(label(d.status)) + '</span>';
    c[7].textContent = d.due || '—';
    c[8].textContent = d.assignee_name || '—';
    c[9].textContent = d.created_by_name || '—';
    c[10].textContent = d.created_at || '—';
    c[11].innerHTML = '<div class="btn-group btn-group-sm" role="group"><button type="button" class="btn btn-outline-primary defect-inline-edit" title="Edit row"><i class="bi bi-pencil"></i></button></div>';
    row.dataset.defectId = d.id;
    row.dataset.projectId = d.project_id || 0;
    row.dataset.severity = d.severity;
    row.dataset.status = d.status;
    row.dataset.due = d.due || '';
    row.dataset.assignedId = d.assigned_id || 0;
  }

  function startEdit(row) {
    if (row.classList.contains('def-editing')) { return; }
    row.__orig = row.innerHTML;
    row.classList.add('def-editing');
    buildEdit(row, readData(row));
    var t = row.querySelector('.def-f-title');
    if (t) { t.focus(); t.select(); }
  }
  function cancelEdit(row) {
    if (!row.dataset.defectId || row.dataset.defectId === '0') { row.remove(); return; }
    row.innerHTML = row.__orig;
    row.classList.remove('def-editing');
  }

  function save(row) {
    if (row.dataset.saving === '1') { return; }
    var title = row.querySelector('.def-f-title').value.trim();
    if (!title) { setState(row, 'Title required', true); return; }
    var body = new URLSearchParams();
    body.append('id', row.dataset.defectId || '0');
    body.append('title', title);
    body.append('project_id', row.querySelector('.def-f-project').value);
    body.append('severity', row.querySelector('.def-f-severity').value);
    body.append('status', row.querySelector('.def-f-status').value);
    body.append('due_date', row.querySelector('.def-f-due').value);
    body.append('assigned_to', row.querySelector('.def-f-assignee').value);
    body.append(csrfName, csrfHash);
    row.dataset.saving = '1';
    setState(row, 'Saving…');
    fetch(saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString(), credentials: 'same-origin' })
      .then(function (res) { return res.json().then(function (j) { return { ok: res.ok, j: j }; }); })
      .then(function (r) {
        if (!r.j || !r.j.ok) { throw new Error((r.j && r.j.error) || 'Save failed'); }
        applyView(row, r.j);
        row.classList.remove('def-editing', 'def-new');
      })
      .catch(function (e) { setState(row, e.message || 'Error', true); })
      .finally(function () { row.removeAttribute('data-saving'); });
  }

  card.addEventListener('click', function (e) {
    var row = e.target.closest('tr.defect-row');
    if (e.target.closest('.defect-inline-edit') && row) { startEdit(row); return; }
    if (e.target.closest('.defect-inline-cancel') && row) { cancelEdit(row); return; }
    if (e.target.closest('.defect-inline-save') && row) { save(row); return; }
    if (e.target.closest('.defect-inline-add')) {
      var tbody = table.tBodies[0];
      var existing = tbody.querySelector('tr.def-new');
      if (existing) { existing.querySelector('.def-f-title').focus(); return; }
      var tr = document.createElement('tr');
      tr.className = 'defect-row def-new def-editing';
      tr.dataset.defectId = '0';
      for (var i = 0; i < 12; i++) { tr.appendChild(document.createElement('td')); }
      tbody.insertBefore(tr, tbody.firstChild);
      tr.cells[0].innerHTML = '<span class="text-muted small">New</span>';
      tr.cells[2].innerHTML = '<span class="text-muted">—</span>';
      tr.cells[6].innerHTML = '<span class="text-muted small">Active</span>';
      tr.cells[9].textContent = userName || '—';
      tr.cells[10].textContent = '—';
      buildEdit(tr, { title: '', project_id: 0, severity: 'medium', status: 'open', due: '', assigned_id: 0 });
      tr.querySelector('.def-f-title').focus();
    }
  });
  card.addEventListener('keydown', function (e) {
    var row = e.target.closest('tr.def-editing');
    if (!row) { return; }
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') { e.preventDefault(); save(row); }
    if (e.key === 'Escape') { cancelEdit(row); }
  });
})();
</script>
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
<?php if ($can_edit): ?>
<!-- List choice: show ALL non-deleted defects (active + inactive). Inactive rows are dimmed; is_active is independent of workflow status. -->
<script>
(function () {
  if (window.__defectActiveToggleBound) {
    return;
  }
  window.__defectActiveToggleBound = true;

  var csrfName = (typeof window.getCsrfName === 'function')
    ? window.getCsrfName()
    : <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var csrfHash = (typeof window.getCsrfToken === 'function')
    ? window.getCsrfToken()
    : <?php echo json_encode($this->security->get_csrf_hash()); ?>;

  function syncRowDim(id, isActive) {
    var nodes = document.querySelectorAll('[data-defect-id="' + id + '"]');
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      if (el.tagName === 'TR') {
        el.classList.toggle('defect-row-inactive', !isActive);
      } else {
        el.classList.toggle('is-inactive', !isActive);
      }
    }
    var toggles = document.querySelectorAll('.defect-active-toggle[data-id="' + id + '"]');
    for (var j = 0; j < toggles.length; j++) {
      toggles[j].checked = !!isActive;
    }
  }

  document.addEventListener('change', function (e) {
    var input = e.target;
    if (!input || !input.classList || !input.classList.contains('defect-active-toggle')) {
      return;
    }
    var id = parseInt(input.getAttribute('data-id'), 10) || 0;
    if (id < 1) {
      return;
    }
    var wanted = input.checked;
    input.disabled = true;

    var body = new FormData();
    body.append(csrfName, csrfHash);

    fetch(<?php echo json_encode(site_url('defects/toggle-active')); ?> + '/' + id, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (res) {
      return res.json().then(function (payload) {
        return { ok: res.ok, payload: payload };
      });
    }).then(function (result) {
      var payload = result.payload || {};
      if (!result.ok || payload.status !== 'success') {
        input.checked = !wanted;
        if (window.toastr) {
          toastr.error((payload && payload.message) ? payload.message : 'Could not update active state');
        }
        return;
      }
      var isActive = !!(payload.data && parseInt(payload.data.is_active, 10) === 1);
      syncRowDim(id, isActive);
      if (window.toastr) {
        toastr.success(payload.message || 'Updated');
      }
    }).catch(function () {
      input.checked = !wanted;
      if (window.toastr) {
        toastr.error('Could not update active state');
      }
    }).then(function () {
      input.disabled = false;
    });
  });
})();
</script>
<?php endif; ?>
<?php if (!$embed) {
    $this->load->view('partials/footer');
} ?>
