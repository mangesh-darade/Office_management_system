<?php $this->load->view('partials/header', ['title' => 'Clients']); ?>
<div class="container-fluid py-3">

<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show d-flex align-items-center py-2 mb-3">
  <i class="bi bi-check-circle-fill me-2"></i>
  <span><?php echo htmlspecialchars($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?></span>
  <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center py-2 mb-3">
  <i class="bi bi-exclamation-triangle-fill me-2"></i>
  <span><?php echo htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></span>
  <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div>
    <h1 class="h4 mb-1 fw-bold"><i class="bi bi-briefcase text-primary me-2"></i>Clients</h1>
    <p class="text-muted small mb-0">Manage your client relationships</p>
  </div>
  <div class="d-flex gap-2 mt-2 mt-sm-0">
    <?php if(function_exists('has_module_access') && (has_module_access('clients_add') || has_module_access('clients'))): ?>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('clients/create'); ?>"><i class="bi bi-plus-lg me-1"></i>Add Client</a>
    <?php endif; ?>
    <?php if(function_exists('has_module_access') && (has_module_access('clients') || is_admin_group())): ?>
    <a class="btn btn-outline-success btn-sm" href="<?php echo site_url('clients/export'); ?>"><i class="bi bi-download me-1"></i>Export</a>
    <?php endif; ?>
  </div>
</div>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" action="<?php echo site_url('clients'); ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <?php $st = isset($filters['status']) ? (string)$filters['status'] : ''; ?>
        <select name="status" class="form-select">
          <option value="">All</option>
          <option value="active" <?php echo $st==='active'?'selected':''; ?>>Active</option>
          <option value="inactive" <?php echo $st==='inactive'?'selected':''; ?>>Inactive</option>
          <option value="blocked" <?php echo $st==='blocked'?'selected':''; ?>>Blocked</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Type</label>
        <?php $ct = isset($filters['client_type']) ? (string)$filters['client_type'] : ''; ?>
        <select name="client_type" class="form-select">
          <option value="">All</option>
          <option value="individual" <?php echo $ct==='individual'?'selected':''; ?>>Individual</option>
          <option value="company" <?php echo $ct==='company'?'selected':''; ?>>Company</option>
          <option value="government" <?php echo $ct==='government'?'selected':''; ?>>Government</option>
          <option value="startup" <?php echo $ct==='startup'?'selected':''; ?>>Startup</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Search</label>
        <input type="text" name="q" value="<?php echo htmlspecialchars(isset($filters['search'])?$filters['search']:''); ?>" class="form-control" placeholder="Company, code, contact, email...">
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:56px;">Logo</th>
            <th>Company</th>
            <th class="d-none d-md-table-cell">Code</th>
            <th class="d-none d-sm-table-cell">Contact</th>
            <th class="d-none d-sm-table-cell">Phone</th>
            <th class="d-none d-lg-table-cell">Demo / POS</th>
            <th>Status</th>
            <th class="text-end" style="min-width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No clients found.</td></tr>
          <?php else: foreach ($rows as $c): ?>
          <tr>
            <td>
              <?php if (!empty($c->logo)): ?>
                <button type="button"
                        class="btn p-0 border-0 bg-transparent js-client-logo-trigger"
                        data-bs-toggle="modal"
                        data-bs-target="#clientLogoModal"
                        data-logo-url="<?php echo htmlspecialchars(base_url($c->logo)); ?>"
                        data-client-name="<?php echo htmlspecialchars($c->company_name); ?>">
                  <img src="<?php echo htmlspecialchars(base_url($c->logo)); ?>" alt="Logo"
                       style="width:40px;height:40px;object-fit:contain;border:1px solid #dee2e6;border-radius:4px;">
                </button>
              <?php else: ?>
                <div style="width:40px;height:40px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;display:flex;align-items:center;justify-content:center;">
                  <i class="bi bi-building text-muted"></i>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?php echo htmlspecialchars($c->company_name); ?></div>
              <div class="small text-muted d-md-none"><?php echo htmlspecialchars($c->client_code); ?></div>
            </td>
            <td class="d-none d-md-table-cell small text-muted"><?php echo htmlspecialchars($c->client_code); ?></td>
            <td class="d-none d-sm-table-cell"><?php echo htmlspecialchars(isset($c->contact_person)?$c->contact_person:''); ?></td>
            <td class="d-none d-sm-table-cell"><?php echo htmlspecialchars(isset($c->phone)?$c->phone:''); ?></td>
            <td class="d-none d-lg-table-cell small">
              <?php if (!empty($c->demo_url)): ?>
                <a href="<?php echo htmlspecialchars($c->demo_url); ?>" target="_blank" rel="noopener" class="d-block text-truncate" style="max-width:160px;" title="<?php echo htmlspecialchars($c->demo_url); ?>">
                  <i class="bi bi-box-arrow-up-right me-1"></i>Demo
                </a>
              <?php endif; ?>
              <?php if (!empty($c->pos_url)): ?>
                <a href="<?php echo htmlspecialchars($c->pos_url); ?>" target="_blank" rel="noopener" class="d-block text-truncate" style="max-width:160px;" title="<?php echo htmlspecialchars($c->pos_url); ?>">
                  <i class="bi bi-box-arrow-up-right me-1"></i>POS
                </a>
              <?php endif; ?>
              <?php if (empty($c->demo_url) && empty($c->pos_url)): ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php
                $st = isset($c->status) ? $c->status : 'active';
                $badge = $st === 'active' ? 'success' : ($st === 'inactive' ? 'secondary' : 'danger');
              ?>
              <span class="badge bg-<?php echo $badge; ?>-subtle text-<?php echo $badge; ?>-emphasis border border-<?php echo $badge; ?>-subtle">
                <?php echo htmlspecialchars(ucfirst($st)); ?>
              </span>
            </td>
            <td class="text-end">
              <div class="d-flex gap-1 justify-content-end">
                <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('clients/view/'.(int)$c->id); ?>" title="View">
                  <i class="bi bi-eye"></i>
                </a>
                <?php if(function_exists('has_module_access') && (has_module_access('clients_edit') || has_module_access('clients'))): ?>
                <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('clients/edit/'.(int)$c->id); ?>" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                <?php endif; ?>
                <?php if(function_exists('has_module_access') && (has_module_access('clients_delete') || has_module_access('clients'))): ?>
                <button type="button" class="btn btn-outline-danger btn-sm" title="Delete"
                        onclick="confirmDeleteClient(<?php echo (int)$c->id; ?>, '<?php echo htmlspecialchars(addslashes($c->company_name), ENT_QUOTES, 'UTF-8'); ?>')">
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
</div>

<div class="modal fade" id="clientLogoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="clientLogoModalTitle">Client Logo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="clientLogoModalImg" src="" alt="Client Logo" class="img-fluid mb-3" style="max-height:400px;object-fit:contain;">
      </div>
      <div class="modal-footer">
        <a id="clientLogoDownload" href="#" class="btn btn-outline-primary" download>Download Logo</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Are you sure you want to permanently delete:</p>
        <p class="fw-bold fs-6" id="deleteClientName"></p>
        <p class="text-muted small mb-0">This action cannot be undone. All associated data will be removed.</p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteClientForm" method="post" action="" class="d-inline">
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Yes, Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

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
