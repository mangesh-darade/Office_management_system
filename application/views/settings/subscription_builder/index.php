<?php
$filters = isset($filters) ? $filters : array();
$page = isset($page) ? (int) $page : 1;
$total_pages = isset($total_pages) ? (int) $total_pages : 1;
$total = isset($total) ? (int) $total : 0;

function sb_catalog_query($filters, $page)
{
    $params = array();
    if (!empty($filters['plan'])) {
        $params['plan'] = $filters['plan'];
    }
    if (!empty($filters['industry'])) {
        $params['industry'] = $filters['industry'];
    }
    if (!empty($filters['country'])) {
        $params['country'] = $filters['country'];
    }
    if (!empty($filters['module'])) {
        $params['module'] = $filters['module'];
    }
    if (!empty($filters['search'])) {
        $params['q'] = $filters['search'];
    }
    if ($page > 1) {
        $params['page'] = $page;
    }
    $qs = http_build_query($params);
    return site_url('settings/subscription-builder' . ($qs ? '?' . $qs : ''));
}
?>
<?php $this->load->view('partials/header', ['title' => 'Subscription Builder Catalog']); ?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h1 class="h3 mb-0">
    <i class="bi bi-sliders me-2"></i>Subscription Builder Catalog
  </h1>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?php echo site_url('subscription-builder'); ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-box-arrow-up-right me-1"></i>Open Builder
    </a>
    <a href="<?php echo site_url('settings'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i> Back to Settings
    </a>
    <a href="<?php echo site_url('settings/subscription-builder/included-order'); ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-sort-down me-1"></i>Included Order
    </a>
    <a href="<?php echo site_url('settings/subscription-builder/create'); ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Add Row
    </a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
  <div class="card-header bg-light">
    <h5 class="card-title mb-0"><i class="bi bi-upload me-2"></i>Import Catalog</h5>
  </div>
  <div class="card-body">
    <form method="post" action="<?php echo site_url('settings/subscription-builder/import'); ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
      <div class="col-md-5">
        <label class="form-label fw-semibold">TSV / CSV / XLSX file</label>
        <input type="file" class="form-control form-control-sm" name="import_file" accept=".tsv,.txt,.csv,.xlsx" required>
        <div class="form-text">
          Columns: Plan, Industry, Country (optional), Module, Feature, Details, Per Item Set Up Charges, Item Unit, Common Set Up Fees, Per Item Per Month Maintenances.
          Legacy sheets may place Country in the last column without a header.
          <span class="d-inline-block ms-1">
            <a href="<?php echo site_url('settings/subscription-builder/sample-xlsx'); ?>"><i class="bi bi-download me-1"></i>Sample XLSX</a>
            <span class="text-muted mx-1">|</span>
            <a href="<?php echo site_url('settings/subscription-builder/sample-csv'); ?>"><i class="bi bi-download me-1"></i>Sample CSV</a>
          </span>
        </div>
      </div>
      <div class="col-md-4">
        <div class="form-check mt-4">
          <input class="form-check-input" type="checkbox" name="replace_all" value="1" id="sb-replace-all">
          <label class="form-check-label" for="sb-replace-all">Replace all existing rows before import</label>
        </div>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-success btn-sm w-100" onclick="return confirm('Import catalog rows from file?');">
          <i class="bi bi-file-earmark-arrow-up me-1"></i>Import
        </button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="get" action="<?php echo site_url('settings/subscription-builder'); ?>" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label small mb-1">Plan</label>
        <select name="plan" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach ($plans as $plan): ?>
          <option value="<?php echo esc_view($plan); ?>" <?php echo ($filters['plan'] ?? '') === $plan ? 'selected' : ''; ?>><?php echo esc_view($plan); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Industry</label>
        <select name="industry" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach ($industries as $industry): ?>
          <option value="<?php echo esc_view($industry); ?>" <?php echo ($filters['industry'] ?? '') === $industry ? 'selected' : ''; ?>><?php echo esc_view($industry); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Country</label>
        <select name="country" class="form-select form-select-sm">
          <option value="">All</option>
          <?php foreach (($countries ?? array('India')) as $country): ?>
          <option value="<?php echo esc_view($country); ?>" <?php echo ($filters['country'] ?? '') === $country ? 'selected' : ''; ?>><?php echo esc_view($country); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small mb-1">Module</label>
        <input type="text" class="form-control form-control-sm" name="module" value="<?php echo esc_view($filters['module'] ?? ''); ?>" placeholder="Module">
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Search</label>
        <input type="search" class="form-control form-control-sm" name="q" value="<?php echo esc_view($filters['search'] ?? ''); ?>" placeholder="Feature, module, details…">
      </div>
      <div class="col-md-1 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
        <a href="<?php echo site_url('settings/subscription-builder'); ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0"><i class="bi bi-list-ul me-2"></i>Catalog List</h5>
    <span class="badge text-bg-secondary"><?php echo (int) $total; ?> row(s)</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-striped mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Plan</th>
            <th>Industry</th>
            <th>Country</th>
            <th>Module</th>
            <th>Feature</th>
            <th class="text-end">Setup</th>
            <th>Unit</th>
            <th class="text-end">Monthly</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
            <tr>
              <td><?php echo (int) $row->id; ?></td>
              <td><?php echo esc_view($row->plan); ?></td>
              <td><?php echo esc_view($row->industry); ?></td>
              <td><?php echo esc_view(!empty($row->country) ? $row->country : 'India'); ?></td>
              <td><?php echo esc_view($row->module); ?></td>
              <td>
                <div><?php echo esc_view($row->feature); ?></div>
                <?php if (!empty($row->details)): ?>
                <div class="text-muted small"><?php echo esc_view($row->details); ?></div>
                <?php endif; ?>
              </td>
              <td class="text-end"><?php echo $row->common_set_up_fees !== null && $row->common_set_up_fees !== '' ? number_format((float) $row->common_set_up_fees, 0) : '—'; ?></td>
              <td><?php echo esc_view($row->item_unit ?: '—'); ?></td>
              <td class="text-end"><?php echo $row->per_item_per_month_maintenances !== null && $row->per_item_per_month_maintenances !== '' ? number_format((float) $row->per_item_per_month_maintenances, 0) : '—'; ?></td>
              <td class="text-nowrap">
                <a href="<?php echo site_url('settings/subscription-builder/' . (int) $row->id . '/edit'); ?>" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <form method="post" action="<?php echo site_url('settings/subscription-builder/' . (int) $row->id . '/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this catalog row?');">
                  <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="10" class="text-center text-muted py-4">No catalog rows found. Add a row or import a TSV file.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if ($total_pages > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <span class="small text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
    <div class="btn-group btn-group-sm">
      <?php if ($page > 1): ?>
      <a class="btn btn-outline-secondary" href="<?php echo esc_view(sb_catalog_query($filters, $page - 1)); ?>">Previous</a>
      <?php endif; ?>
      <?php if ($page < $total_pages): ?>
      <a class="btn btn-outline-secondary" href="<?php echo esc_view(sb_catalog_query($filters, $page + 1)); ?>">Next</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $this->load->view('partials/footer'); ?>
