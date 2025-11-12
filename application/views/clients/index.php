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
            <th>Code</th>
            <th>Company</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted">No clients found.</td></tr>
          <?php else: foreach ($rows as $c): ?>
          <tr>
            <td><?php echo htmlspecialchars($c->client_code); ?></td>
            <td><?php echo htmlspecialchars($c->company_name); ?></td>
            <td><?php echo htmlspecialchars(isset($c->contact_person)?$c->contact_person:''); ?></td>
            <td><?php echo htmlspecialchars(isset($c->email)?$c->email:''); ?></td>
            <td><?php echo htmlspecialchars(isset($c->phone)?$c->phone:''); ?></td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(isset($c->status)?$c->status:'active'); ?></span></td>
            <td class="text-end">
              <a class="btn btn-light btn-sm" href="<?php echo site_url('clients/view/'.(int)$c->id); ?>"><i class="bi bi-eye"></i></a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
