<?php $this->load->view('partials/header', ['title' => 'Clients']); ?>
<div class="container-fluid py-2 clients-list">

<?php
ob_start();
if (function_exists('has_module_access') && (has_module_access('clients_export') || has_module_access('clients') || is_admin_group())):
?>
<?php $export_q = function_exists('safe_query_string') ? safe_query_string() : ''; ?>
<a class="btn btn-outline-success btn-sm" href="<?php echo site_url('clients/export' . ($export_q !== '' ? '?' . $export_q : '')); ?>"><i class="bi bi-download me-1"></i>Export</a>
<?php endif; ?>
<?php if (function_exists('has_module_access') && (has_module_access('clients_add') || has_module_access('clients'))): ?>
<a class="btn btn-primary btn-sm" href="<?php echo site_url('clients/create'); ?>"><i class="bi bi-plus-lg me-1"></i>Add Client</a>
<?php endif;
$this->load->view('partials/oms_page_head', [
  'title' => 'Clients',
  'subtitle' => 'Client companies and credentials',
  'icon' => 'bi-briefcase',
  'actions_html' => ob_get_clean(),
  'mb' => 'mb-2',
]);
?>

<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2 mb-2">
  <i class="bi bi-check-circle-fill me-1"></i><?php echo esc_view($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2 mb-2">
  <i class="bi bi-exclamation-triangle-fill me-1"></i><?php echo esc_view($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php
$st = isset($filters['status']) ? (string) $filters['status'] : '';
$ct = isset($filters['client_type']) ? (string) $filters['client_type'] : '';
$q = isset($filters['search']) ? (string) $filters['search'] : '';
$sort = isset($filters['sort']) ? (string) $filters['sort'] : '';
$dir = isset($filters['dir']) ? strtolower((string) $filters['dir']) : 'asc';
if ($dir !== 'desc') {
  $dir = 'asc';
}
$row_count = is_array($rows) ? count($rows) : 0;

$clients_sort_url = function ($col) use ($st, $ct, $q, $sort, $dir) {
  $next_dir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
  $params = array();
  if ($st !== '') {
    $params['status'] = $st;
  }
  if ($ct !== '') {
    $params['client_type'] = $ct;
  }
  if ($q !== '') {
    $params['q'] = $q;
  }
  $params['sort'] = $col;
  $params['dir'] = $next_dir;
  return site_url('clients?' . http_build_query($params));
};
$type_sort_icon = 'bi-arrow-down-up';
if ($sort === 'client_type') {
  $type_sort_icon = $dir === 'desc' ? 'bi-sort-down' : 'bi-sort-up';
}
?>

<div class="card shadow-soft mb-2">
  <div class="card-body py-2 px-3">
    <form method="get" action="<?php echo site_url('clients'); ?>" class="row g-2 align-items-center oms-filter-row">
      <?php if ($sort !== ''): ?>
      <input type="hidden" name="sort" value="<?php echo esc_view($sort); ?>">
      <input type="hidden" name="dir" value="<?php echo esc_view($dir); ?>">
      <?php endif; ?>
      <div class="col-6 col-md-2">
        <select name="status" class="form-select form-select-sm" aria-label="Status">
          <option value="">All statuses</option>
          <option value="active" <?php echo $st === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="inactive" <?php echo $st === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
          <option value="blocked" <?php echo $st === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select name="client_type" class="form-select form-select-sm" aria-label="Type">
          <option value="">All types</option>
          <?php if (isset($client_types) && is_array($client_types)): foreach ($client_types as $code => $label): ?>
            <option value="<?php echo esc_view($code); ?>" <?php echo $ct === (string) $code ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <div class="col-12 col-md-5">
        <input type="text" name="q" value="<?php echo esc_view($q); ?>" class="form-control form-control-sm" placeholder="Search company, code, contact, email, URL…" aria-label="Search">
      </div>
      <div class="col-6 col-md-2">
        <button class="btn btn-outline-primary btn-sm w-100" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
      </div>
      <div class="col-6 col-md-1 text-md-end">
        <span class="text-muted small text-nowrap"><?php echo (int) $row_count; ?> total</span>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0 clients-list-table">
      <thead class="table-light">
        <tr>
          <th style="width:40px;"></th>
          <th>Client Name</th>
          <th style="width:110px;">
            <a href="<?php echo esc_view($clients_sort_url('client_type'), ENT_QUOTES, 'UTF-8'); ?>" class="text-decoration-none text-body d-inline-flex align-items-center gap-1" title="Sort by Type">
              Type <i class="bi <?php echo esc_view($type_sort_icon); ?>"></i>
            </a>
          </th>
          <th>Contact</th>
          <th style="width:120px;">Phone</th>
          <th style="width:88px;">Links</th>
          <th style="width:84px;">Status</th>
          <th class="text-end" style="width:108px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="text-center text-muted py-4">No clients found.</td></tr>
        <?php else: foreach ($rows as $c): ?>
        <?php
          $type_code = isset($c->client_type) ? (string) $c->client_type : 'company';
          $type_label = (isset($client_types) && is_array($client_types) && isset($client_types[$type_code]))
            ? $client_types[$type_code]
            : ucfirst(str_replace('_', ' ', $type_code));
          $status_val = isset($c->status) ? (string) $c->status : 'active';
          $status_badge = $status_val === 'active' ? 'success' : ($status_val === 'inactive' ? 'secondary' : 'danger');
        ?>
        <tr>
          <td>
            <?php if (!empty($c->logo)): ?>
              <button type="button"
                      class="btn p-0 border-0 bg-transparent js-client-logo-trigger clients-logo-btn"
                      data-bs-toggle="modal"
                      data-bs-target="#clientLogoModal"
                      data-logo-url="<?php echo esc_view(base_url($c->logo)); ?>"
                      data-client-name="<?php echo esc_view($c->company_name); ?>"
                      title="View logo">
                <img src="<?php echo esc_view(base_url($c->logo)); ?>" alt="" class="clients-logo-thumb">
              </button>
            <?php else: ?>
              <div class="clients-logo-placeholder"><i class="bi bi-building"></i></div>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?php echo site_url('clients/view/' . (int) $c->id); ?>" class="fw-semibold text-decoration-none text-body">
              <?php echo esc_view($c->company_name); ?>
            </a>
            <?php if (!empty($c->client_code)): ?>
            <div class="text-muted clients-code"><?php echo esc_view($c->client_code); ?></div>
            <?php endif; ?>
          </td>
          <td><span class="badge bg-light text-dark border fw-normal"><?php echo esc_view($type_label); ?></span></td>
          <td class="small">
            <?php echo esc_view(isset($c->contact_person) ? $c->contact_person : ''); ?>
            <?php if (!empty($c->email)): ?>
            <div class="text-muted text-truncate clients-email" title="<?php echo esc_view($c->email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($c->email); ?></div>
            <?php endif; ?>
          </td>
          <td class="small text-nowrap"><?php echo esc_view(isset($c->phone) ? $c->phone : ''); ?></td>
          <td>
            <div class="d-flex gap-1">
              <?php if (!empty($c->website)): ?>
              <a href="<?php echo esc_view($c->website); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm py-0 px-1" title="<?php echo esc_view($c->website, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-link-45deg"></i></a>
              <?php endif; ?>
              <?php if (!empty($c->demo_url)): ?>
              <a href="<?php echo esc_view($c->demo_url); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm py-0 px-1" title="Demo: <?php echo esc_view($c->demo_url, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-play-circle"></i></a>
              <?php endif; ?>
              <?php if (!empty($c->pos_url)): ?>
              <a href="<?php echo esc_view($c->pos_url); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm py-0 px-1" title="POS: <?php echo esc_view($c->pos_url, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-shop"></i></a>
              <?php endif; ?>
              <?php if (empty($c->website) && empty($c->demo_url) && empty($c->pos_url)): ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <span class="badge bg-<?php echo $status_badge; ?>-subtle text-<?php echo $status_badge; ?>-emphasis border border-<?php echo $status_badge; ?>-subtle">
              <?php echo esc_view(ucfirst($status_val)); ?>
            </span>
          </td>
          <td class="text-end text-nowrap">
            <div class="btn-group btn-group-sm" role="group">
              <a class="btn btn-outline-secondary" href="<?php echo site_url('clients/view/' . (int) $c->id); ?>" title="View"><i class="bi bi-eye"></i></a>
              <?php if (function_exists('has_module_access') && (has_module_access('clients_edit') || has_module_access('clients'))): ?>
              <a class="btn btn-outline-primary" href="<?php echo site_url('clients/edit/' . (int) $c->id); ?>" title="Edit"><i class="bi bi-pencil"></i></a>
              <?php endif; ?>
              <?php if (function_exists('has_module_access') && (has_module_access('clients_delete') || has_module_access('clients'))): ?>
              <button type="button" class="btn btn-outline-danger" title="Delete"
                      onclick="confirmDeleteClient(<?php echo (int) $c->id; ?>, '<?php echo esc_view(addslashes($c->company_name), ENT_QUOTES, 'UTF-8'); ?>')">
                <i class="bi bi-trash"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="clientLogoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title" id="clientLogoModalTitle">Client Logo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-3">
        <img id="clientLogoModalImg" src="" alt="Client Logo" class="img-fluid" style="max-height:400px;object-fit:contain;">
      </div>
      <div class="modal-footer py-2">
        <a id="clientLogoDownload" href="#" class="btn btn-outline-primary btn-sm" download>Download</a>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

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

<style>
.clients-list .clients-list-table > :not(caption) > * > * {
  padding: 0.4rem 0.65rem;
  vertical-align: middle;
}
.clients-list .clients-list-table thead th {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  color: #64748b;
  white-space: nowrap;
  border-bottom-width: 1px;
}
.clients-list .clients-list-table tbody td {
  font-size: 0.875rem;
}
.clients-list .clients-logo-thumb {
  width: 32px;
  height: 32px;
  object-fit: contain;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  background: #fff;
}
.clients-list .clients-logo-placeholder {
  width: 32px;
  height: 32px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  font-size: 0.85rem;
}
.clients-list .clients-code {
  font-size: 0.72rem;
  line-height: 1.2;
  margin-top: 1px;
}
.clients-list .clients-email {
  max-width: 180px;
  font-size: 0.72rem;
  line-height: 1.2;
  margin-top: 1px;
}
.clients-list .oms-page-head h1.h4 {
  font-size: 1.15rem;
  margin-bottom: 0.15rem !important;
}
.clients-list .oms-page-head .text-muted.small {
  font-size: 0.78rem;
}
</style>

<script>
  function confirmDeleteClient(id, name) {
    document.getElementById('deleteClientName').textContent = name;
    document.getElementById('deleteClientForm').action = '<?php echo site_url('clients/delete/'); ?>' + id;
    var modal = new bootstrap.Modal(document.getElementById('deleteClientModal'));
    modal.show();
  }

  document.addEventListener('DOMContentLoaded', function(){
    var imgEl = document.getElementById('clientLogoModalImg');
    var downloadEl = document.getElementById('clientLogoDownload');
    var titleEl = document.getElementById('clientLogoModalTitle');
    var triggers = document.querySelectorAll('.js-client-logo-trigger');
    triggers.forEach(function(btn){
      btn.addEventListener('click', function(){
        var url = this.getAttribute('data-logo-url') || '';
        var name = this.getAttribute('data-client-name') || '';
        imgEl.src = url;
        imgEl.alt = name || 'Client logo';
        if (titleEl){ titleEl.textContent = name ? (name + ' Logo') : 'Client Logo'; }
        if (downloadEl){ downloadEl.href = url; }
      });
    });
  });
</script>
</div>

<?php $this->load->view('partials/footer'); ?>
