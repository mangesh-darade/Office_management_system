<?php $this->load->view('partials/header', ['title' => 'Clients']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Clients</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('clients/create'); ?>"><i class="bi bi-plus-lg"></i> Add Client</a>
    <a class="btn btn-outline-success btn-sm" href="<?php echo site_url('clients/export'); ?>"><i class="bi bi-download"></i> Export</a>
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
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Logo</th>
            <th>Code</th>
            <th>Company</th>
            <th>Contact</th>
            <th>Phone</th>
            <th style="width:150px;">Demo URL</th>
            <th style="width:150px;">POS URL</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="text-center text-muted">No clients found.</td></tr>
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
                  <div style="width:64px;height:64px;border:1px solid #dee2e6;border-radius:4px;display:flex;align-items:center;justify-content:center;background:#fff;">
                    <img src="<?php echo htmlspecialchars(base_url($c->logo)); ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
                  </div>
                </button>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($c->client_code); ?></td>
            <td><?php echo htmlspecialchars($c->company_name); ?></td>
            <td><?php echo htmlspecialchars(isset($c->contact_person)?$c->contact_person:''); ?></td>
            <td><?php echo htmlspecialchars(isset($c->phone)?$c->phone:''); ?></td>
            <td>
              <?php if (!empty($c->demo_url)): ?>
                <div style="max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <a href="<?php echo htmlspecialchars($c->demo_url); ?>"
                     target="_blank" rel="noopener"
                     title="<?php echo htmlspecialchars($c->demo_url); ?>">
                    <?php echo htmlspecialchars($c->demo_url); ?>
                  </a>
                </div>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($c->pos_url)): ?>
                <div style="max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <a href="<?php echo htmlspecialchars($c->pos_url); ?>"
                     target="_blank" rel="noopener"
                     title="<?php echo htmlspecialchars($c->pos_url); ?>">
                    <?php echo htmlspecialchars($c->pos_url); ?>
                  </a>
                </div>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(isset($c->status)?$c->status:'active'); ?></span></td>
            <td class="text-end">
              <a class="btn btn-light btn-sm" href="<?php echo site_url('clients/view/'.(int)$c->id); ?>"><i class="bi bi-eye"></i></a>
              <a class="btn btn-primary btn-sm" href="<?php echo site_url('clients/edit/'.(int)$c->id); ?>"><i class="bi bi-pencil"></i></a>
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

<script>
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
        if (downloadEl){
          downloadEl.href = url;
        }
      });
    });
  });
</script>

<?php $this->load->view('partials/footer'); ?>
