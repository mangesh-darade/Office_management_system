<?php
$embed = (bool) $this->input->get('embed');
$create_url = site_url('requirements/create');
if ($embed) {
  $create_url .= '?redirect=' . rawurlencode('my-works?tab=requirements');
}
if (!$embed) {
  $this->load->view('partials/header', [
    'title' => 'Requirements',
    'extra_css' => ['assets/css/my-works.css'],
  ]);
}
?>
<div class="container-fluid req-list-compact<?php echo $embed ? ' mw-req-embed p-0' : ' py-2'; ?>">
<?php if (!$embed) {
  $this->load->view('partials/import_errors');
} ?>
<?php
ob_start();
?>
<?php if (function_exists('has_module_access') && (has_module_access('requirements_add') || has_module_access('requirements'))): ?>
<a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('requirements/import'); ?>" title="Import requirements"><i class="bi bi-upload me-1"></i>Import</a>
<?php endif; ?>
<?php if (function_exists('has_module_access') && (has_module_access('requirements_export') || has_module_access('requirements') || is_admin_group())): ?>
<a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('requirements/export') . '?' . http_build_query(array_filter(array_intersect_key($this->input->get() ?: array(), array_flip(array('status', 'priority', 'requirement_type', 'client_id', 'project_id', 'assigned_to', 'q'))), function ($v) { return $v !== '' && $v !== null; })); ?>" title="Export CSV"><i class="bi bi-download me-1"></i>Export</a>
<?php endif;
if (!$embed) {
  $this->load->view('partials/oms_page_head', [
    'title' => 'Requirements',
    'subtitle' => 'Track client and internal requirements',
    'icon' => 'bi-list-check',
    'actions_html' => ob_get_clean(),
  ]);
} else {
  $req_embed_actions = ob_get_clean();
  if (trim($req_embed_actions) !== '') {
    echo '<div class="req-list-toolbar d-flex flex-wrap justify-content-end gap-1 mb-2">' . $req_embed_actions . '</div>';
  }
}
?>

<div class="card border-0 shadow-sm mb-2 req-filter-card">
  <div class="card-body">
    <form method="get" action="<?php echo site_url('requirements'); ?>" class="req-filter-form">
      <?php if ($embed): ?>
      <input type="hidden" name="embed" value="1">
      <?php if ($this->input->get('parent_tab')): ?>
      <input type="hidden" name="parent_tab" value="<?php echo esc_view($this->input->get('parent_tab'), ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <?php endif; ?>
      <label class="req-filter-field">
        <span class="req-filter-label">Status</span>
        <?php $fs = isset($filters['status']) ? (string)$filters['status'] : ''; ?>
        <select name="status" class="form-select form-select-sm">
          <?php $statuses = array('', 'received','under_review','approved','in_progress','completed','on_hold','rejected','cancelled');
          foreach ($statuses as $st): ?>
            <option value="<?php echo esc_view($st); ?>" <?php echo ($fs===$st)?'selected':''; ?>><?php echo $st===''?'All':ucfirst(str_replace('_',' ',$st)); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="req-filter-field">
        <span class="req-filter-label">Priority</span>
        <?php $fp = isset($filters['priority']) ? (string)$filters['priority'] : ''; ?>
        <select name="priority" class="form-select form-select-sm">
          <?php $priorities = array('', 'low','medium','high','critical');
          foreach ($priorities as $pr): ?>
            <option value="<?php echo esc_view($pr); ?>" <?php echo ($fp===$pr)?'selected':''; ?>><?php echo $pr===''?'All':ucfirst($pr); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="req-filter-field">
        <span class="req-filter-label">Type</span>
        <?php $ft = isset($filters['requirement_type']) ? (string)$filters['requirement_type'] : ''; ?>
        <select name="requirement_type" class="form-select form-select-sm">
          <option value="">All</option>
          <?php if (isset($requirement_types) && is_array($requirement_types)): foreach ($requirement_types as $code => $label): ?>
            <option value="<?php echo esc_view($code); ?>" <?php echo ($ft === (string)$code) ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
          <?php endforeach; endif; ?>
        </select>
      </label>
      <label class="req-filter-field">
        <span class="req-filter-label">Client</span>
        <?php $fc = isset($filters['client_id']) ? (string)$filters['client_id'] : ''; ?>
        <select name="client_id" class="form-select form-select-sm">
          <option value="">All</option>
          <?php if (isset($clients) && is_array($clients)) foreach ($clients as $c): ?>
            <option value="<?php echo (int)$c->id; ?>" <?php echo ($fc===(string)$c->id)?'selected':''; ?>><?php echo esc_view($c->company_name); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="req-filter-field">
        <span class="req-filter-label">Assigned To</span>
        <?php $fa = isset($filters['assigned_to']) ? (string)$filters['assigned_to'] : ''; ?>
        <select name="assigned_to" class="form-select form-select-sm">
          <option value="">All</option>
          <?php if (isset($members) && is_array($members)) foreach ($members as $m): ?>
            <?php $label = '';
              if (isset($m->full_label) && $m->full_label!=='') { $label = $m->full_label; }
              else if (isset($m->full_name) && $m->full_name!=='') { $label = $m->full_name; }
              else if (isset($m->name) && $m->name!=='') { $label = $m->name; }
              else if (isset($m->email)) { $label = $m->email; }
            ?>
            <option value="<?php echo (int)$m->id; ?>" <?php echo ($fa===(string)$m->id)?'selected':''; ?>><?php echo esc_view($label); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="req-filter-field req-filter-search">
        <span class="req-filter-label">Search</span>
        <input type="search" name="q" value="<?php echo esc_view(isset($filters['search'])?$filters['search']:''); ?>" class="form-control form-control-sm" placeholder="Req#, title" autocomplete="off" aria-label="Search requirements">
      </label>
    </form>
  </div>
</div>

<?php
$req_can_add = function_exists('has_module_access') && (has_module_access('requirements_add') || has_module_access('requirements'));
$req_can_edit = function_exists('has_module_access') && (has_module_access('requirements_edit') || has_module_access('requirements'));
$inline_clients = isset($inline_clients) && is_array($inline_clients) ? $inline_clients : array();
$inline_members = isset($inline_members) && is_array($inline_members) ? $inline_members : array();
?>
<style>
.req-list-table { width: 100% !important; }
.req-list-table th, .req-list-table td { text-align: left; }
.req-list-table th.dt-type-numeric, .req-list-table td.dt-type-numeric,
.req-list-table th.dt-right, .req-list-table td.dt-right { text-align: left !important; }
.req-list-table thead th:last-child, .req-list-table tbody td:last-child { text-align: right; }
.req-table-card .dt-container .dt-layout-row { margin: 0; padding: 0.35rem 0.6rem; }
</style>
<div class="card border-0 shadow-sm req-table-card">
  <div class="card-body p-0">
    <?php if ($req_can_add): ?>
    <div class="req-add-bar px-2 py-1 border-bottom d-flex justify-content-end">
      <button type="button" class="btn btn-sm btn-outline-primary req-inline-add py-0 px-2" title="Add requirement"><i class="bi bi-plus-lg"></i></button>
    </div>
    <?php endif; ?>
    <div class="table-responsive">
      <table class="table table-hover table-sm align-middle mb-0 req-list-table" data-dt-manual>
        <thead>
          <tr>
            <th>Req#</th>
            <th>Client</th>
            <th>Title</th>
            <th>Assigned</th>
            <th>Created by</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Expected</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="text-center text-muted py-3">No requirements found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
          <?php
            $st = isset($r->status) ? (string) $r->status : 'received';
            $pr = isset($r->priority) ? (string) $r->priority : 'medium';
            $pr_class = 'secondary';
            if ($pr === 'critical') { $pr_class = 'danger'; }
            elseif ($pr === 'high') { $pr_class = 'warning text-dark'; }
            elseif ($pr === 'low') { $pr_class = 'light text-dark border'; }
            $assignee = isset($r->assigned_to_name) ? trim((string) $r->assigned_to_name) : '';
            if (function_exists('multi_assignees_format_label') && isset($assignee_names_map[(int) $r->id]) && is_array($assignee_names_map[(int) $r->id])) {
              $assignee = multi_assignees_format_label($assignee, $assignee_names_map[(int) $r->id]);
            }
          ?>
          <tr class="req-row" data-id="<?php echo (int) $r->id; ?>" data-client-id="<?php echo (int) (isset($r->client_id) ? $r->client_id : 0); ?>" data-assigned-id="<?php echo (int) (isset($r->assigned_to) ? $r->assigned_to : 0); ?>" data-status="<?php echo esc_view($st); ?>" data-priority="<?php echo esc_view($pr); ?>" data-expected="<?php echo esc_view(isset($r->expected_delivery_date) ? (string) $r->expected_delivery_date : ''); ?>">
            <td class="text-nowrap fw-semibold" data-order="<?php echo (int) $r->id; ?>"><?php echo esc_view(isset($r->req_number)?$r->req_number:''); ?></td>
            <td class="text-truncate" style="max-width:10rem;" title="<?php echo esc_view(isset($r->client_name)?$r->client_name:''); ?>"><?php echo esc_view(isset($r->client_name)?$r->client_name:''); ?></td>
            <td>
              <a href="<?php echo site_url('requirements/view/'.(int)$r->id); ?>" class="req-title-link" title="<?php echo esc_view($r->title); ?>">
                <?php echo esc_view($r->title); ?>
              </a>
            </td>
            <td class="text-truncate" style="max-width:9rem;" title="<?php echo esc_view($assignee !== '' ? $assignee : '—'); ?>"><?php echo esc_view($assignee !== '' ? $assignee : '—'); ?></td>
            <td class="text-truncate" style="max-width:9rem;" title="<?php echo esc_view(isset($r->created_by_name) ? $r->created_by_name : ''); ?>"><?php echo esc_view(!empty($r->created_by_name) ? $r->created_by_name : '—'); ?></td>
            <td><span class="badge bg-light text-dark border req-badge"><?php echo esc_view(ucfirst(str_replace('_', ' ', $st))); ?></span></td>
            <td><span class="badge bg-<?php echo $pr_class; ?> req-badge"><?php echo esc_view(ucfirst($pr)); ?></span></td>
            <td class="text-nowrap text-muted"><?php echo esc_view(isset($r->expected_delivery_date)?$r->expected_delivery_date:''); ?></td>
            <td class="text-end text-nowrap">
              <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                <a class="btn btn-outline-secondary" href="<?php echo site_url('requirements/view/'.(int)$r->id); ?>" title="View"><i class="bi bi-eye"></i></a>
                <?php if ($req_can_edit): ?>
                <button type="button" class="btn btn-outline-primary req-inline-edit" title="Edit row"><i class="bi bi-pencil"></i></button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<script>
(function () {
  var table = document.querySelector('.req-list-table');
  if (!table) { return; }
  var saveUrl = <?php echo json_encode(site_url('requirements/inline-save')); ?>;
  var viewBase = <?php echo json_encode(site_url('requirements/view/')); ?>;
  var csrfName = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var clients = <?php echo json_encode(array_map(function ($c) { return array('id' => (int) $c->id, 'name' => (string) $c->company_name); }, $inline_clients)); ?>;
  var members = <?php echo json_encode(array_map(function ($m) {
      $n = '';
      foreach (array('full_label', 'full_name', 'name', 'email') as $k) { if (!empty($m->$k)) { $n = (string) $m->$k; break; } }
      return array('id' => (int) $m->id, 'name' => $n);
  }, $inline_members)); ?>;
  var statuses = ['received', 'under_review', 'approved', 'in_progress', 'completed', 'on_hold', 'rejected', 'cancelled'];
  var priorities = ['low', 'medium', 'high', 'critical'];

  var dt = null;
  (function initGrid() {
    if (!window.DataTable || table.dataset.dtInited === '1' || !table.tBodies[0].querySelector('tr.req-row')) { return; }
    try {
      dt = new DataTable(table, {
        paging: true, pageLength: 25, lengthMenu: [10, 25, 50, 100], lengthChange: true,
        searching: false, info: true, ordering: true, order: [[0, 'desc']], responsive: false, autoWidth: false,
        columnDefs: [{ orderable: false, targets: [8] }],
        language: { emptyTable: 'No requirements found.' }
      });
      table.dataset.dtInited = '1';
    } catch (e) { dt = null; }
  })();
  function refreshRow(row) { if (dt && !row.classList.contains('req-new')) { try { dt.row(row).invalidate().draw(false); } catch (e) {} } }

  function esc(v) { var d = document.createElement('div'); d.textContent = v == null ? '' : String(v); return d.innerHTML; }
  function label(v) { v = String(v || ''); return v.charAt(0).toUpperCase() + v.slice(1).replace(/_/g, ' '); }
  var csrfHash = <?php echo json_encode($this->security->get_csrf_hash()); ?>;
  function csrf() { return csrfHash; }
  function opts(list, sel, blank) {
    var h = blank ? '<option value="">' + esc(blank) + '</option>' : '';
    list.forEach(function (o) {
      var v = (typeof o === 'object') ? o.id : o;
      var t = (typeof o === 'object') ? o.name : label(o);
      h += '<option value="' + esc(v) + '"' + (String(v) === String(sel) ? ' selected' : '') + '>' + esc(t) + '</option>';
    });
    return h;
  }
  function badgeStatus(s) { return '<span class="badge bg-light text-dark border req-badge">' + esc(label(s)) + '</span>'; }
  function badgePriority(p) {
    var c = 'secondary';
    if (p === 'critical') { c = 'danger'; } else if (p === 'high') { c = 'warning text-dark'; } else if (p === 'low') { c = 'light text-dark border'; }
    return '<span class="badge bg-' + c + ' req-badge">' + esc(label(p)) + '</span>';
  }

  function buildEditCells(row, d) {
    var cells = row.cells;
    cells[1].innerHTML = '<select class="form-select form-select-sm req-f-client">' + opts(clients, d.client_id, '— Client —') + '</select>';
    cells[2].innerHTML = '<input type="text" class="form-control form-control-sm req-f-title" maxlength="500" placeholder="Title" value="' + esc(d.title) + '">';
    cells[3].innerHTML = '<select class="form-select form-select-sm req-f-assigned">' + opts(members, d.assigned_id, 'Unassigned') + '</select>';
    cells[5].innerHTML = '<select class="form-select form-select-sm req-f-status">' + opts(statuses, d.status) + '</select>';
    cells[6].innerHTML = '<select class="form-select form-select-sm req-f-priority">' + opts(priorities, d.priority) + '</select>';
    cells[7].innerHTML = '<input type="date" class="form-control form-control-sm req-f-expected" value="' + esc(d.expected) + '">';
    cells[8].innerHTML = '<span class="req-inline-state text-muted small me-1"></span>'
      + '<div class="btn-group btn-group-sm"><button type="button" class="btn btn-primary req-inline-save" title="Save"><i class="bi bi-check-lg"></i></button>'
      + '<button type="button" class="btn btn-outline-secondary req-inline-cancel" title="Cancel"><i class="bi bi-x-lg"></i></button></div>';
  }

  function viewCells(row, d) {
    var cells = row.cells;
    cells[0].textContent = d.req_number || '';
    cells[0].setAttribute('data-order', d.id);
    cells[1].textContent = d.client_name || '';
    cells[2].innerHTML = '<a href="' + esc(d.view_url) + '" class="req-title-link">' + esc(d.title) + '</a>';
    cells[3].textContent = d.assigned_name || '—';
    cells[4].textContent = d.created_by_name || '—';
    cells[5].innerHTML = badgeStatus(d.status);
    cells[6].innerHTML = badgePriority(d.priority);
    cells[7].textContent = d.expected || '';
    cells[8].innerHTML = '<div class="btn-group btn-group-sm" role="group">'
      + '<a class="btn btn-outline-secondary" href="' + esc(d.view_url) + '" title="View"><i class="bi bi-eye"></i></a>'
      + '<button type="button" class="btn btn-outline-primary req-inline-edit" title="Edit row"><i class="bi bi-pencil"></i></button></div>';
    row.dataset.id = d.id;
    row.dataset.clientId = d.client_id || 0;
    row.dataset.assignedId = d.assigned_id || 0;
    row.dataset.status = d.status;
    row.dataset.priority = d.priority;
    row.dataset.expected = d.expected || '';
  }

  function rowData(row) {
    return {
      id: row.dataset.id || '0',
      req_number: row.cells[0].textContent.trim(),
      client_id: row.dataset.clientId || 0,
      client_name: row.cells[1].textContent.trim(),
      title: (row.cells[2].textContent || '').trim(),
      assigned_id: row.dataset.assignedId || 0,
      assigned_name: row.cells[3].textContent.trim(),
      created_by_name: row.cells[4].textContent.trim(),
      status: row.dataset.status || 'received',
      priority: row.dataset.priority || 'medium',
      expected: row.dataset.expected || '',
      view_url: viewBase + (row.dataset.id || '')
    };
  }

  function setState(row, text, isErr) {
    var el = row.querySelector('.req-inline-state');
    if (!el) { return; }
    el.textContent = text || '';
    el.classList.toggle('text-danger', !!isErr);
  }

  function startEdit(row) {
    if (row.classList.contains('req-editing')) { return; }
    row.__orig = row.innerHTML;
    row.__origData = rowData(row);
    row.classList.add('req-editing');
    buildEditCells(row, row.__origData);
    var t = row.querySelector('.req-f-title');
    if (t) { t.focus(); t.select(); }
  }

  function cancelEdit(row) {
    if (row.dataset.id === '0' || !row.dataset.id) { row.remove(); return; }
    row.innerHTML = row.__orig;
    row.classList.remove('req-editing');
    refreshRow(row);
  }

  function save(row) {
    if (row.dataset.saving === '1') { return; }
    var title = row.querySelector('.req-f-title').value.trim();
    if (!title) { setState(row, 'Title required', true); return; }
    var body = new URLSearchParams();
    body.append('id', row.dataset.id || '0');
    body.append('title', title);
    body.append('client_id', row.querySelector('.req-f-client').value);
    body.append('assigned_to', row.querySelector('.req-f-assigned').value);
    body.append('status', row.querySelector('.req-f-status').value);
    body.append('priority', row.querySelector('.req-f-priority').value);
    body.append('expected_delivery_date', row.querySelector('.req-f-expected').value);
    body.append(csrfName, csrf());
    row.dataset.saving = '1';
    setState(row, 'Saving…');
    fetch(saveUrl, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString(), credentials: 'same-origin' })
      .then(function (res) { return res.json().then(function (j) { return { ok: res.ok, j: j }; }); })
      .then(function (r) {
        if (!r.j || !r.j.ok) { throw new Error((r.j && r.j.error) || 'Save failed'); }
        var d = {
          id: r.j.id, req_number: r.j.req_number, client_id: body.get('client_id'), client_name: r.j.client_name,
          title: title, assigned_id: body.get('assigned_to'), assigned_name: r.j.assigned_name || '',
          created_by_name: r.j.created_by_name, status: r.j.status, priority: r.j.priority, expected: r.j.expected, view_url: r.j.view_url
        };
        if (!body.get('assigned_to') || body.get('assigned_to') === '0') { d.assigned_name = ''; }
        var wasNew = row.classList.contains('req-new');
        viewCells(row, d);
        row.classList.remove('req-editing');
        if (wasNew) {
          row.classList.remove('req-new');
          if (dt) { row.remove(); dt.row.add(row).draw(false); }
        } else {
          refreshRow(row);
        }
      })
      .catch(function (e) { setState(row, e.message || 'Error', true); })
      .finally(function () { row.removeAttribute('data-saving'); });
  }

  (table.closest('.req-table-card') || table).addEventListener('click', function (e) {
    var row = e.target.closest('tr.req-row');
    if (e.target.closest('.req-inline-edit') && row) { startEdit(row); return; }
    if (e.target.closest('.req-inline-cancel') && row) { cancelEdit(row); return; }
    if (e.target.closest('.req-inline-save') && row) { save(row); return; }
    if (e.target.closest('.req-inline-add')) {
      var tbody = table.tBodies[0];
      var empty = tbody.querySelector('td[colspan]');
      if (empty && !tbody.querySelector('tr.req-row')) { empty.closest('tr').remove(); }
      var existing = tbody.querySelector('tr.req-new');
      if (existing) { existing.querySelector('.req-f-title').focus(); return; }
      var tr = document.createElement('tr');
      tr.className = 'req-row req-new req-editing';
      tr.dataset.id = '0';
      for (var i = 0; i < 9; i++) { tr.appendChild(document.createElement('td')); }
      tbody.insertBefore(tr, tbody.firstChild);
      tr.cells[0].innerHTML = '<span class="text-muted small">New</span>';
      tr.cells[4].textContent = <?php echo json_encode((string) $this->session->userdata('name')); ?> || '—';
      buildEditCells(tr, { client_id: 0, title: '', assigned_id: 0, status: 'received', priority: 'medium', expected: '' });
      tr.querySelector('.req-f-title').focus();
    }
  });

  table.addEventListener('keydown', function (e) {
    var row = e.target.closest('tr.req-editing');
    if (!row) { return; }
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') { e.preventDefault(); save(row); }
    if (e.key === 'Escape') { cancelEdit(row); }
  });
})();
</script>
<script>
(function () {
  var form = document.querySelector('.req-filter-form');
  if (!form) {
    return;
  }
  form.querySelectorAll('select').forEach(function (select) {
    select.addEventListener('change', function () {
      form.submit();
    });
  });
  var input = form.querySelector('input[name="q"]');
  if (!input) {
    return;
  }
  var timer = null;
  function go() {
    form.submit();
  }
  input.addEventListener('input', function () {
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(go, 350);
  });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (timer) {
        clearTimeout(timer);
      }
      go();
    }
  });
})();
</script>
<?php if (!$embed) {
  $this->load->view('partials/footer');
} ?>
