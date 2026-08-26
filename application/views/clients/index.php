<?php
$active_tab = (isset($active_tab) && in_array($active_tab, array('cart', 'urls'), true)) ? $active_tab : 'list';
$embed = !empty($embed) || (bool) $this->input->get('embed');
$parent_tab = trim((string) $this->input->get('parent_tab'));
$header_css = array('assets/css/clients.css');
if ($active_tab === 'cart') {
  $header_css[] = 'assets/css/project-dashboard.css';
}
if (!$embed) {
  $this->load->view('partials/header', array('title' => 'Clients', 'extra_css' => $header_css));
}
?>

<?php
$st = isset($filters['status']) ? (string) $filters['status'] : '';
$ct = isset($filters['client_type']) ? (string) $filters['client_type'] : '';
$q = isset($filters['search']) ? (string) $filters['search'] : '';
$sort = isset($filters['sort']) ? (string) $filters['sort'] : '';
$dir = isset($filters['dir']) ? strtolower((string) $filters['dir']) : 'asc';
if ($dir !== 'desc') {
  $dir = 'asc';
}
$rows = isset($rows) && is_array($rows) ? $rows : array();
$lanes = isset($lanes) && is_array($lanes) ? $lanes : array();
$show_lanes = !empty($show_lanes);
$client_types = (isset($client_types) && is_array($client_types)) ? $client_types : array();
$type_counts = (isset($type_counts) && is_array($type_counts)) ? $type_counts : array();
$status_counts = (isset($status_counts) && is_array($status_counts)) ? $status_counts : array();
$stats_total = isset($stats_total) ? (int) $stats_total : 0;
$pagination = (isset($pagination) && is_array($pagination)) ? $pagination : array('page' => 1, 'per_page' => 25, 'total' => 0, 'total_pages' => 1);
$page = (int) $pagination['page'];
$per_page = (int) $pagination['per_page'];
$total = (int) $pagination['total'];
$total_pages = max(1, (int) $pagination['total_pages']);
$from = $total > 0 ? (($page - 1) * $per_page) + 1 : 0;
$to = min($total, $page * $per_page);

if (!function_exists('module_status_records')) {
  $this->load->helper('module_status');
}
$this->load->helper('status_row');
$status_records = module_status_records('clients');
$status_colors = array();
foreach ($status_records as $sr) {
  $status_colors[(string) $sr->code] = !empty($sr->color) ? (string) $sr->color : '#6c757d';
}

$cl_url = function ($overrides = array()) use ($st, $ct, $q, $sort, $dir, $active_tab, $embed, $parent_tab) {
  $params = array();
  $status = array_key_exists('status', $overrides) ? $overrides['status'] : $st;
  $type = array_key_exists('client_type', $overrides) ? $overrides['client_type'] : $ct;
  $search = array_key_exists('q', $overrides) ? $overrides['q'] : $q;
  $sort_col = array_key_exists('sort', $overrides) ? $overrides['sort'] : $sort;
  $sort_dir = array_key_exists('dir', $overrides) ? $overrides['dir'] : $dir;
  $page_n = array_key_exists('page', $overrides) ? $overrides['page'] : null;
  $tab = array_key_exists('tab', $overrides) ? $overrides['tab'] : ($active_tab !== 'list' ? $active_tab : '');
  if ($status !== '' && $status !== null) {
    $params['status'] = $status;
  }
  if ($type !== '' && $type !== null) {
    $params['client_type'] = $type;
  }
  if ($search !== '' && $search !== null) {
    $params['q'] = $search;
  }
  if ($sort_col !== '' && $sort_col !== null) {
    $params['sort'] = $sort_col;
    $params['dir'] = ($sort_dir === 'desc') ? 'desc' : 'asc';
  }
  if ($page_n !== null && (int) $page_n > 1) {
    $params['page'] = (int) $page_n;
  }
  if ($tab !== '' && $tab !== null && $tab !== 'list') {
    $params['tab'] = $tab;
  }
  if ($embed) {
    $params['embed'] = 1;
    if ($parent_tab !== '') {
      $params['parent_tab'] = $parent_tab;
    }
  }
  return site_url('clients' . (!empty($params) ? ('?' . http_build_query($params)) : ''));
};

$cl_row_style = function ($status_code) use ($status_colors) {
  $code = trim((string) $status_code);
  if ($code === '') {
    $code = 'active';
  }
  $hex = isset($status_colors[$code]) ? $status_colors[$code] : '#6c757d';
  $bg = status_row_bg_from_hex($hex, 0.14);
  return 'background-color:' . $bg . ';--cl-row-status-color:' . esc_view($hex, ENT_QUOTES, 'UTF-8') . ';';
};

$sort_url = function ($col) use ($cl_url, $sort, $dir) {
  $next = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
  return $cl_url(array('sort' => $col, 'dir' => $next, 'page' => 1));
};

$can_edit = function_exists('has_module_access') && (has_module_access('clients_edit') || has_module_access('clients'));
$can_delete = function_exists('has_module_access') && (has_module_access('clients_delete') || has_module_access('clients'));
$can_add = function_exists('has_module_access') && (has_module_access('clients_add') || has_module_access('clients'));
$can_export = function_exists('has_module_access') && (has_module_access('clients_export') || has_module_access('clients') || (function_exists('is_admin_group') && is_admin_group()));
$can_import = function_exists('has_module_access') && (has_module_access('clients_import') || has_module_access('clients_add') || has_module_access('clients'));
$export_q = function_exists('safe_query_string') ? safe_query_string() : '';

$cl_initials = function ($name) {
  $name = trim(preg_replace('/\s+/', ' ', (string) $name));
  if ($name === '') {
    return '?';
  }
  $parts = explode(' ', $name);
  $a = strtoupper(substr($parts[0], 0, 1));
  $b = isset($parts[1]) ? strtoupper(substr($parts[1], 0, 1)) : '';
  return $a . $b;
};

$cl_type_class = function ($code) {
  $code = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', (string) $code));
  $known = array('company', 'individual', 'government', 'startup', 'other', 'elintpos', 'elintpos_client', 'elintom', 'elintom_client', 'prospect', 'premium', 'trial');
  if (in_array($code, $known, true)) {
    return 'cl-type-' . $code;
  }
  if (strpos($code, 'elintpos') !== false) {
    return 'cl-type-elintpos';
  }
  if (strpos($code, 'elintom') !== false) {
    return 'cl-type-elintom';
  }
  return 'cl-type-default';
};

$cl_link_count = function ($c) {
  $n = 0;
  if (!empty($c->website)) { $n++; }
  if (!empty($c->demo_url)) { $n++; }
  if (!empty($c->pos_url)) { $n++; }
  return $n;
};
?>

<div class="container-fluid <?php echo $embed ? 'py-1 px-0' : 'py-2'; ?> cl-page">

  <?php if (!$embed): ?>
  <div class="cl-top">
    <div>
      <h1><i class="bi bi-briefcase text-primary me-2"></i>Clients</h1>
    </div>
    <div class="cl-top-actions">
      <?php if ($can_import): ?>
      <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#clImportModal" title="Import" aria-label="Import">
        <i class="bi bi-upload"></i>
      </button>
      <?php endif; ?>
      <?php if ($can_export): ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('clients/export' . ($export_q !== '' ? '?' . $export_q : '')); ?>" title="Export" aria-label="Export">
        <i class="bi bi-download"></i>
      </a>
      <?php endif; ?>
      <?php if ($can_add): ?>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url($active_tab === 'urls' ? 'clients/create#client-urls' : 'clients/create'); ?>">
        <i class="bi bi-plus-lg me-1"></i>Add Client
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="cl-shell cl-view-tabs-shell mb-2">
    <nav class="cl-tabs" aria-label="Clients views">
      <a class="cl-tab <?php echo $active_tab === 'list' ? 'active' : ''; ?>" href="<?php echo esc_view($cl_url(array('tab' => 'list')), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="bi bi-list-ul"></i> List
        <?php if ($active_tab === 'list'): ?>
        <span class="cl-tab-count"><?php echo (int) $stats_total; ?></span>
        <?php endif; ?>
      </a>
      <a class="cl-tab <?php echo $active_tab === 'cart' ? 'active' : ''; ?>" href="<?php echo esc_view($cl_url(array('tab' => 'cart')), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="bi bi-columns-gap"></i> Client cart
        <?php if ($active_tab === 'cart'): ?>
        <span class="cl-tab-count"><?php echo isset($client_cards) ? count($client_cards) : 0; ?></span>
        <?php endif; ?>
      </a>
      <a class="cl-tab <?php echo $active_tab === 'urls' ? 'active' : ''; ?>" href="<?php echo esc_view($cl_url(array('tab' => 'urls')), ENT_QUOTES, 'UTF-8'); ?>">
        <i class="bi bi-link-45deg"></i> Client URLs
        <?php if ($active_tab === 'urls'): ?>
        <span class="cl-tab-count"><?php echo isset($url_rows) ? count($url_rows) : 0; ?></span>
        <?php endif; ?>
      </a>
    </nav>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show py-2 mb-3">
    <i class="bi bi-check-circle-fill me-1"></i><?php echo esc_view($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show py-2 mb-3">
    <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo esc_view($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <?php $this->load->view('partials/import_errors'); ?>

  <?php if ($active_tab === 'list'): ?>

  <div class="cl-stat-cards cl-stat-cards-status">
    <a class="cl-stat-card <?php echo $st === '' ? 'active' : ''; ?>" href="<?php echo esc_view($cl_url(array('status' => '', 'page' => 1)), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="cl-stat-value"><?php echo (int) $stats_total; ?></div>
      <div class="cl-stat-label">Clients</div>
    </a>
    <?php foreach ($status_records as $sr):
      $scode = (string) $sr->code;
      $slabel = (string) $sr->name;
      $scolor = isset($status_colors[$scode]) ? $status_colors[$scode] : '#6c757d';
      $scnt = isset($status_counts[$scode]) ? (int) $status_counts[$scode] : 0;
    ?>
    <a class="cl-stat-card <?php echo $st === $scode ? 'active' : ''; ?>" href="<?php echo esc_view($cl_url(array('status' => $scode, 'page' => 1)), ENT_QUOTES, 'UTF-8'); ?>" style="border-left: 3px solid <?php echo esc_view($scolor, ENT_QUOTES, 'UTF-8'); ?>;">
      <div class="cl-stat-value" style="color: <?php echo esc_view($scolor, ENT_QUOTES, 'UTF-8'); ?>;"><?php echo $scnt; ?></div>
      <div class="cl-stat-label"><?php echo esc_view($slabel); ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="cl-stat-cards cl-stat-cards-type">
    <?php
    $type_theme_i = 0;
    $type_themes = array('company', 'individual', 'government', 'startup', 'other');
    foreach ($client_types as $code => $label):
      $tcnt = isset($type_counts[$code]) ? (int) $type_counts[$code] : 0;
      $theme = in_array($code, $type_themes, true) ? $code : $type_themes[$type_theme_i % 5];
      $type_theme_i++;
      $type_href = ($ct === (string) $code)
        ? $cl_url(array('client_type' => '', 'page' => 1))
        : $cl_url(array('client_type' => (string) $code, 'page' => 1));
    ?>
    <a class="cl-stat-card cl-type-card-<?php echo esc_view($theme); ?> <?php echo $ct === (string) $code ? 'active' : ''; ?>"
       href="<?php echo esc_view($type_href, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="cl-stat-value"><?php echo $tcnt; ?></div>
      <div class="cl-stat-label"><?php echo esc_view($label); ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="cl-shell">
    <form method="get" action="<?php echo site_url('clients'); ?>" class="cl-toolbar" id="clFilterForm">
      <?php if ($embed): ?>
      <input type="hidden" name="embed" value="1">
      <?php if ($parent_tab !== ''): ?>
      <input type="hidden" name="parent_tab" value="<?php echo esc_view($parent_tab, ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <?php endif; ?>
      <?php if ($sort !== ''): ?>
      <input type="hidden" name="sort" value="<?php echo esc_view($sort); ?>">
      <input type="hidden" name="dir" value="<?php echo esc_view($dir); ?>">
      <?php endif; ?>
      <div class="cl-search">
        <i class="bi bi-search"></i>
        <input type="search" name="q" id="clSearchInput" value="<?php echo esc_view($q); ?>"
               placeholder="Search by Client Name, Client ID, Contact, Phone…"
               aria-label="Search clients" autocomplete="off">
      </div>
      <div class="cl-status-filter-wrap">
        <span class="cl-status-filter-dot" id="clStatusFilterDot" aria-hidden="true"></span>
        <select name="status" class="form-select form-select-sm cl-status-filter" aria-label="Status" id="clStatusFilter">
          <option value="" data-color="">All statuses</option>
          <?php foreach ($status_records as $sr): ?>
          <?php $scode = (string) $sr->code; $scolor = isset($status_colors[$scode]) ? $status_colors[$scode] : '#6c757d'; ?>
          <option value="<?php echo esc_view($scode); ?>" data-color="<?php echo esc_view($scolor, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $st === $scode ? 'selected' : ''; ?>><?php echo esc_view($sr->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($ct !== ''): ?>
      <input type="hidden" name="client_type" value="<?php echo esc_view($ct); ?>">
      <?php endif; ?>
      <?php if ($st !== '' || $ct !== '' || $q !== ''): ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo esc_view($cl_url(array('status' => '', 'client_type' => '', 'q' => '', 'sort' => '', 'dir' => 'asc', 'page' => 1, 'tab' => 'list')), ENT_QUOTES, 'UTF-8'); ?>">Reset Filters</a>
      <?php endif; ?>
    </form>

    <?php if (!$show_lanes): ?>
    <div class="cl-bulk-bar" id="clBulkBar">
      <strong id="clBulkCount">0</strong> selected
      <?php if ($can_export): ?>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="clBulkExport"><i class="bi bi-download me-1"></i>Export</button>
      <?php endif; ?>
      <?php if ($can_delete): ?>
      <button type="button" class="btn btn-outline-danger btn-sm" id="clBulkDelete"><i class="bi bi-trash me-1"></i>Delete</button>
      <?php endif; ?>
      <button type="button" class="btn btn-link btn-sm text-decoration-none" id="clBulkClear">Clear</button>
    </div>
    <?php endif; ?>

    <?php if ($show_lanes): ?>
      <?php if (empty($rows)): ?>
      <div class="cl-empty">
        <div class="cl-empty-icon"><i class="bi bi-folder2-open"></i></div>
        <div class="fw-semibold text-dark mb-1">No Clients Found</div>
        <div class="small mb-3">Create your first client or adjust filters.</div>
        <?php if ($can_add): ?>
        <a class="btn btn-primary btn-sm" href="<?php echo site_url('clients/create'); ?>"><i class="bi bi-plus-lg me-1"></i>Add Client</a>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="cl-lanes-wrap">
        <div class="cl-lanes">
          <?php
          $lane_theme_i = 0;
          $lane_themes = array('company', 'individual', 'government', 'startup', 'other');
          foreach ($client_types as $code => $label):
            $lane_items = isset($lanes[$code]) && is_array($lanes[$code]) ? $lanes[$code] : array();
            $theme = in_array($code, $lane_themes, true) ? $code : $lane_themes[$lane_theme_i % 5];
            $lane_theme_i++;
            $lane_url = $cl_url(array('client_type' => (string) $code, 'page' => 1));
          ?>
          <section class="cl-lane cl-lane-<?php echo esc_view($theme); ?>">
            <div class="cl-lane-head">
              <a href="<?php echo esc_view($lane_url, ENT_QUOTES, 'UTF-8'); ?>" class="cl-lane-title"><?php echo esc_view($label); ?></a>
              <span class="cl-lane-count"><?php echo count($lane_items); ?></span>
            </div>
            <div class="cl-lane-body">
              <table class="cl-lane-table">
                <thead>
                  <tr>
                    <th>Client</th>
                    <th>Contact</th>
                  </tr>
                </thead>
              </table>
              <div class="cl-lane-scroll">
                <?php if (empty($lane_items)): ?>
                <div class="cl-lane-empty">No clients in this type</div>
                <?php else: ?>
                <table class="cl-lane-table">
                  <tbody>
                    <?php foreach ($lane_items as $c): ?>
                    <?php
                      $status_val = isset($c->status) ? (string) $c->status : 'active';
                      $view_url = site_url('clients/view/' . (int) $c->id);
                      $contact = isset($c->contact_person) ? trim((string) $c->contact_person) : '';
                      $row_style = $cl_row_style($status_val);
                    ?>
                    <tr class="cl-lane-row cl-status-row" style="<?php echo $row_style; ?>" data-href="<?php echo esc_view($view_url, ENT_QUOTES, 'UTF-8'); ?>">
                      <td>
                        <a class="cl-lane-name" href="<?php echo $view_url; ?>"><?php echo esc_view($c->company_name); ?></a>
                        <?php if (!empty($c->client_code)): ?>
                        <div class="cl-lane-sub"><?php echo esc_view($c->client_code); ?></div>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($contact !== ''): ?>
                        <span class="cl-lane-contact" title="<?php echo esc_view($contact, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($contact); ?></span>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
                <?php endif; ?>
              </div>
            </div>
          </section>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    <?php else: ?>

    <?php if (empty($rows)): ?>
    <div class="cl-empty">
      <div class="cl-empty-icon"><i class="bi bi-folder2-open"></i></div>
      <div class="fw-semibold text-dark mb-1">No Clients Found</div>
      <div class="small mb-3">Create your first client or adjust filters.</div>
      <?php if ($can_add): ?>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('clients/create'); ?>"><i class="bi bi-plus-lg me-1"></i>Add Client</a>
      <?php endif; ?>
    </div>
    <?php else: ?>

    <div class="cl-table-wrap cl-desktop-only">
      <table class="table align-middle cl-table mb-0">
        <thead>
          <tr>
            <th style="width:36px;">
              <input type="checkbox" class="form-check-input" id="clSelectAll" title="Select all" aria-label="Select all">
            </th>
            <th class="text-start">
              <a class="cl-sort <?php echo $sort === 'company_name' ? 'is-active' : ''; ?>" href="<?php echo esc_view($sort_url('company_name'), ENT_QUOTES, 'UTF-8'); ?>">
                Client <i class="bi bi-arrow-down-up cl-sort-icon"></i>
              </a>
            </th>
            <th class="text-start">
              <a class="cl-sort <?php echo $sort === 'client_type' ? 'is-active' : ''; ?>" href="<?php echo esc_view($sort_url('client_type'), ENT_QUOTES, 'UTF-8'); ?>">
                Type <i class="bi bi-arrow-down-up cl-sort-icon"></i>
              </a>
            </th>
            <th class="text-start">Contact</th>
            <th class="text-start">Acc. Manager</th>
            <th class="text-start">Phone</th>
            <th class="text-start">Links</th>
            <th class="text-end text-nowrap" style="width:108px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $c): ?>
          <?php
            $type_code = isset($c->client_type) ? (string) $c->client_type : 'company';
            $type_label = isset($client_types[$type_code]) ? $client_types[$type_code] : ucwords(str_replace('_', ' ', $type_code));
            $status_val = isset($c->status) ? (string) $c->status : 'active';
            $view_url = site_url('clients/view/' . (int) $c->id);
            $links_n = $cl_link_count($c);
            $phone = isset($c->phone) ? trim((string) $c->phone) : '';
            $contact = isset($c->contact_person) ? trim((string) $c->contact_person) : '';
            $account_manager = isset($c->account_manager_name) ? trim((string) $c->account_manager_name) : '';
            $row_style = $cl_row_style($status_val);
          ?>
          <tr class="cl-status-row" style="<?php echo $row_style; ?>" data-href="<?php echo esc_view($view_url, ENT_QUOTES, 'UTF-8'); ?>">
            <td onclick="event.stopPropagation();">
              <input type="checkbox" class="form-check-input cl-row-check" value="<?php echo (int) $c->id; ?>" aria-label="Select client">
            </td>
            <td class="text-start">
              <a class="cl-name" href="<?php echo $view_url; ?>" onclick="event.stopPropagation();">
                <i class="bi bi-building"></i>
                <span><?php echo esc_view($c->company_name); ?></span>
              </a>
              <?php if (!empty($c->client_code)): ?>
              <div class="cl-id">Client ID · <?php echo esc_view($c->client_code); ?></div>
              <?php endif; ?>
            </td>
            <td class="text-start">
              <span class="cl-pill <?php echo $cl_type_class($type_code); ?>">
                <span class="cl-pill-dot"></span><?php echo esc_view($type_label); ?>
              </span>
            </td>
            <td class="text-start">
              <?php if ($contact !== ''): ?>
              <div class="cl-contact">
                <span class="cl-avatar"><?php echo esc_view($cl_initials($contact)); ?></span>
                <span class="text-truncate" style="max-width:160px;" title="<?php echo esc_view($contact, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($contact); ?></span>
              </div>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-start">
              <?php if ($account_manager !== ''): ?>
              <div class="cl-contact">
                <span class="cl-avatar" style="background:#f1f5f9;color:#64748b;border-color:#cbd5e1;"><?php echo esc_view($cl_initials($account_manager)); ?></span>
                <span class="text-truncate" style="max-width:160px;" title="<?php echo esc_view($account_manager, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($account_manager); ?></span>
              </div>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-start" onclick="event.stopPropagation();">
              <?php if ($phone !== ''): ?>
              <a class="cl-phone" href="tel:<?php echo esc_view(preg_replace('/\s+/', '', $phone), ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-telephone"></i><?php echo esc_view($phone); ?>
              </a>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-start cl-links-cell" onclick="event.stopPropagation();">
              <?php if ($links_n > 0): ?>
                <?php if (!empty($c->website)): ?><a href="<?php echo esc_view($c->website); ?>" target="_blank" rel="noopener">Website</a><?php if ($links_n > 1): ?> · <?php endif; endif; ?>
                <?php
                  $rest = array();
                  if (!empty($c->demo_url)) { $rest[] = '<a href="' . esc_view($c->demo_url) . '" target="_blank" rel="noopener">Demo</a>'; }
                  if (!empty($c->pos_url)) { $rest[] = '<a href="' . esc_view($c->pos_url) . '" target="_blank" rel="noopener">POS</a>'; }
                  echo implode(' · ', $rest);
                ?>
              <?php else: ?>
              <span class="text-muted">No Links</span>
              <?php endif; ?>
            </td>
            <td class="text-end text-nowrap" onclick="event.stopPropagation();">
              <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                <a class="btn btn-outline-secondary" href="<?php echo $view_url; ?>" title="View"><i class="bi bi-eye"></i></a>
                <?php if ($can_edit): ?>
                <a class="btn btn-outline-primary" href="<?php echo site_url('clients/edit/' . (int) $c->id); ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                <?php endif; ?>
                <?php if ($can_delete): ?>
                <button type="button" class="btn btn-outline-danger" title="Delete"
                        onclick="confirmDeleteClient(<?php echo (int) $c->id; ?>, '<?php echo esc_view(addslashes($c->company_name), ENT_QUOTES, 'UTF-8'); ?>')">
                  <i class="bi bi-trash"></i>
                </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="cl-mobile-cards cl-mobile-only">
      <?php foreach ($rows as $c): ?>
      <?php
        $type_code = isset($c->client_type) ? (string) $c->client_type : 'company';
        $type_label = isset($client_types[$type_code]) ? $client_types[$type_code] : ucwords(str_replace('_', ' ', $type_code));
        $status_val = isset($c->status) ? (string) $c->status : 'active';
        $view_url = site_url('clients/view/' . (int) $c->id);
        $contact = isset($c->contact_person) ? trim((string) $c->contact_person) : '';
        $account_manager = isset($c->account_manager_name) ? trim((string) $c->account_manager_name) : '';
        $phone = isset($c->phone) ? trim((string) $c->phone) : '';
        $card_style = $cl_row_style($status_val);
      ?>
      <article class="cl-mcard cl-status-row" style="<?php echo $card_style; ?>">
        <div class="d-flex justify-content-between gap-2 mb-2">
          <div class="min-w-0">
            <a class="cl-name" href="<?php echo $view_url; ?>"><i class="bi bi-building"></i><span><?php echo esc_view($c->company_name); ?></span></a>
            <?php if (!empty($c->client_code)): ?><div class="cl-id">Client ID · <?php echo esc_view($c->client_code); ?></div><?php endif; ?>
          </div>
        </div>
        <div class="mb-2"><span class="cl-pill <?php echo $cl_type_class($type_code); ?>"><span class="cl-pill-dot"></span><?php echo esc_view($type_label); ?></span></div>
        <?php if ($contact !== ''): ?>
        <div class="cl-contact mb-1"><span class="cl-avatar"><?php echo esc_view($cl_initials($contact)); ?></span><span class="text-muted ms-1 small">Contact:</span> <span class="fw-medium text-dark"><?php echo esc_view($contact); ?></span></div>
        <?php endif; ?>
        <?php if ($account_manager !== ''): ?>
        <div class="cl-contact mb-2"><span class="cl-avatar" style="background:#f1f5f9;color:#64748b;border-color:#cbd5e1;"><?php echo esc_view($cl_initials($account_manager)); ?></span><span class="text-muted ms-1 small">Manager:</span> <span class="fw-medium text-dark"><?php echo esc_view($account_manager); ?></span></div>
        <?php endif; ?>
        <?php if ($phone !== ''): ?>
        <div class="mb-2"><a class="cl-phone" href="tel:<?php echo esc_view(preg_replace('/\s+/', '', $phone), ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-telephone"></i><?php echo esc_view($phone); ?></a></div>
        <?php endif; ?>
        <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
          <a class="btn btn-outline-secondary" href="<?php echo $view_url; ?>" title="View"><i class="bi bi-eye"></i></a>
          <?php if ($can_edit): ?>
          <a class="btn btn-outline-primary" href="<?php echo site_url('clients/edit/' . (int) $c->id); ?>" title="Edit"><i class="bi bi-pencil"></i></a>
          <?php endif; ?>
          <?php if ($can_delete): ?>
          <button type="button" class="btn btn-outline-danger" title="Delete"
                  onclick="confirmDeleteClient(<?php echo (int) $c->id; ?>, '<?php echo esc_view(addslashes($c->company_name), ENT_QUOTES, 'UTF-8'); ?>')">
            <i class="bi bi-trash"></i>
          </button>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <div class="cl-pager">
      <div>Showing <?php echo (int) $from; ?>–<?php echo (int) $to; ?> of <?php echo (int) $total; ?></div>
      <nav aria-label="Clients pagination">
        <ul class="pagination pagination-sm mb-0">
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $page <= 1 ? '#' : esc_view($cl_url(array('page' => $page - 1)), ENT_QUOTES, 'UTF-8'); ?>">Previous</a>
          </li>
          <?php
            $start_p = max(1, $page - 2);
            $end_p = min($total_pages, $start_p + 4);
            $start_p = max(1, $end_p - 4);
            for ($p = $start_p; $p <= $end_p; $p++):
          ?>
          <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
            <a class="page-link" href="<?php echo esc_view($cl_url(array('page' => $p)), ENT_QUOTES, 'UTF-8'); ?>"><?php echo (int) $p; ?></a>
          </li>
          <?php endfor; ?>
          <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="<?php echo $page >= $total_pages ? '#' : esc_view($cl_url(array('page' => $page + 1)), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
          </li>
        </ul>
      </nav>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php elseif ($active_tab === 'urls'): ?>
  <?php $this->load->view('clients/_urls_panel', array(
    'url_rows' => isset($url_rows) ? $url_rows : array(),
    'clients_list' => isset($clients_list) ? $clients_list : array(),
    'url_types' => isset($url_types) ? $url_types : array(),
    'url_versions' => isset($url_versions) ? $url_versions : array(),
    'url_filters' => isset($url_filters) ? $url_filters : array(),
    'embed' => $embed,
    'parent_tab' => $parent_tab,
  )); ?>
  <?php else: ?>
  <div class="client-dash-page project-dash-compact">
    <?php
    $this->load->view('clients/_cart_filters', array(
      'filters'           => isset($filters) ? $filters : array(),
      'client_types'      => isset($client_types) ? $client_types : array(),
      'filter_projects'   => isset($filter_projects) ? $filter_projects : array(),
      'filter_project_id' => isset($filter_project_id) ? (int) $filter_project_id : 0,
      'embed'             => $embed,
      'form_action'       => site_url('clients'),
      'cart_tab'          => true,
    ));
    ?>
    <?php $this->load->view('clients/_cart_body'); ?>
  </div>
  <?php endif; ?>
</div>

<form id="clBulkDeleteForm" method="post" action="<?php echo site_url('clients/bulk-delete'); ?>" class="d-none">
  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
  <div id="clBulkDeleteIds"></div>
</form>

<?php if ($can_import): ?>
<div class="modal fade" id="clImportModal" tabindex="-1" aria-labelledby="clImportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data" action="<?php echo site_url('clients/import'); ?>">
        <div class="modal-header py-2">
          <h5 class="modal-title h6" id="clImportModalLabel"><i class="bi bi-upload me-1"></i>Import Clients</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-2">Required columns: <code>company_name</code>, <code>contact_person</code>, <code>phone</code>. Optional: client_code, email, status, client_type, and other export fields.</p>
          <a class="btn btn-outline-secondary btn-sm mb-3" href="<?php echo base_url('assets/samples/clients_import_sample.csv'); ?>" download>
            <i class="bi bi-download me-1"></i>Download sample CSV
          </a>
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <label class="form-label small" for="clImportFile">Choose CSV file</label>
          <input type="file" name="file" id="clImportFile" accept=".csv,text/csv" class="form-control form-control-sm" required>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-upload me-1"></i>Upload &amp; Import</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0 py-2">
        <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body py-2">
        <p class="mb-1">Are you sure you want to permanently delete:</p>
        <p class="fw-bold mb-1" id="deleteClientName"></p>
        <p class="text-muted small mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer border-0 pt-0 py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteClientForm" method="post" action="" class="d-inline">
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
function confirmDeleteClient(id, name) {
  document.getElementById('deleteClientName').textContent = name;
  document.getElementById('deleteClientForm').action = '<?php echo site_url('clients/delete/'); ?>' + id;
  new bootstrap.Modal(document.getElementById('deleteClientModal')).show();
}

(function () {
  var form = document.getElementById('clFilterForm');
  var search = document.getElementById('clSearchInput');
  var status = document.getElementById('clStatusFilter');
  var statusDot = document.getElementById('clStatusFilterDot');
  var timer = null;

  function updateStatusFilterDot() {
    if (!status || !statusDot) { return; }
    var opt = status.options[status.selectedIndex];
    var color = opt ? opt.getAttribute('data-color') : '';
    statusDot.style.backgroundColor = color || '#94a3b8';
    statusDot.style.opacity = color ? '1' : '0.4';
  }

  function submitFilters() {
    if (form) { form.submit(); }
  }

  if (search) {
    search.addEventListener('input', function () {
      clearTimeout(timer);
      timer = setTimeout(submitFilters, 450);
    });
  }
  if (status) {
    updateStatusFilterDot();
    status.addEventListener('change', function () {
      updateStatusFilterDot();
      submitFilters();
    });
  }

  document.querySelectorAll('.cl-table tbody tr[data-href], .cl-lane-row[data-href]').forEach(function (row) {
    row.addEventListener('click', function (e) {
      if (e.target.closest('a, button, input')) { return; }
      var href = row.getAttribute('data-href');
      if (href) { window.location = href; }
    });
  });

  var selectAll = document.getElementById('clSelectAll');
  var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('.cl-row-check')); };
  var bulkBar = document.getElementById('clBulkBar');
  var bulkCount = document.getElementById('clBulkCount');

  function selectedIds() {
    return checks().filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
  }

  function refreshBulk() {
    var ids = selectedIds();
    if (bulkBar) { bulkBar.classList.toggle('is-visible', ids.length > 0); }
    if (bulkCount) { bulkCount.textContent = String(ids.length); }
    if (selectAll) {
      var all = checks();
      selectAll.checked = all.length > 0 && ids.length === all.length;
      selectAll.indeterminate = ids.length > 0 && ids.length < all.length;
    }
  }

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checks().forEach(function (c) { c.checked = selectAll.checked; });
      refreshBulk();
    });
  }
  checks().forEach(function (c) { c.addEventListener('change', refreshBulk); });

  var clearBtn = document.getElementById('clBulkClear');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      checks().forEach(function (c) { c.checked = false; });
      if (selectAll) { selectAll.checked = false; selectAll.indeterminate = false; }
      refreshBulk();
    });
  }

  var exportBtn = document.getElementById('clBulkExport');
  if (exportBtn) {
    exportBtn.addEventListener('click', function () {
      var ids = selectedIds();
      if (!ids.length) { return; }
      window.location = '<?php echo site_url('clients/export'); ?>?ids=' + encodeURIComponent(ids.join(','));
    });
  }

  var deleteBtn = document.getElementById('clBulkDelete');
  if (deleteBtn) {
    deleteBtn.addEventListener('click', function () {
      var ids = selectedIds();
      if (!ids.length) { return; }
      if (!confirm('Delete ' + ids.length + ' selected client(s)? This cannot be undone.')) { return; }
      var wrap = document.getElementById('clBulkDeleteIds');
      wrap.innerHTML = '';
      ids.forEach(function (id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        wrap.appendChild(input);
      });
      document.getElementById('clBulkDeleteForm').submit();
    });
  }
})();
</script>

<?php if (!$embed) {
  $this->load->view('partials/footer');
} ?>
