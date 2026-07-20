<?php
$this->load->view('partials/header', ['title' => 'Import Clients']);
$client_types = (isset($client_types) && is_array($client_types)) ? $client_types : array();
if (!function_exists('module_status_options')) {
    $this->load->helper('module_status');
}
$status_opts = module_status_options('clients');
?>
<div class="container-fluid py-2">
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0"><i class="bi bi-upload text-primary me-2"></i>Import Clients (CSV)</h1>
    <a class="btn btn-secondary btn-sm" href="<?php echo site_url('clients'); ?>">Back to Clients</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <p class="text-muted mb-2">Upload a UTF-8 CSV with headers matching the sample file. Required columns: <code>company_name</code>, <code>contact_person</code>, <code>phone</code>.</p>
      <div class="mb-3 d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('assets/samples/clients_import_sample.csv'); ?>" download>
          <i class="bi bi-download me-1"></i>Download sample file
        </a>
        <?php if (function_exists('has_module_access') && (has_module_access('clients_export') || has_module_access('clients'))): ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('clients/export'); ?>">
          <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export current clients
        </a>
        <?php endif; ?>
      </div>
      <ul class="small text-muted mb-3">
        <li>Leave <strong>client_code</strong> blank for new clients — a code is generated automatically.</li>
        <li>If <strong>client_code</strong> matches an existing client, that row updates the record (export → edit → re-import).</li>
        <li><strong>phone</strong> must be at least 10 digits; email and phone must be unique.</li>
        <?php if (!empty($client_types)): ?>
        <li><strong>client_type</strong> — <?php echo esc_view(implode(', ', array_keys($client_types))); ?> (default: company).</li>
        <?php endif; ?>
        <?php if (!empty($status_opts)): ?>
        <li><strong>status</strong> — <?php echo esc_view(implode(', ', array_keys($status_opts))); ?> (default: active).</li>
        <?php endif; ?>
        <li>Maximum 1000 data rows per upload.</li>
      </ul>
      <?php $this->load->view('partials/import_errors'); ?>
      <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-danger py-2 mb-3"><?php echo esc_view($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>
      <form method="post" enctype="multipart/form-data" action="<?php echo site_url('clients/import'); ?>">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <div class="row g-2 align-items-center">
          <div class="col-12 col-sm-8">
            <input type="file" name="file" accept=".csv,text/csv" class="form-control" required>
          </div>
          <div class="col-12 col-sm-auto">
            <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-1"></i>Upload &amp; Import</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
