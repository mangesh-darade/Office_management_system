<?php
$plans_json = json_encode(array_values($plans), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$industries_json = json_encode(array_values($industries), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<?php $this->load->view('partials/header', [
  'title' => 'Subscription Builder',
  'extra_css' => ['assets/css/subscription-builder.css'],
]); ?>
<div class="container-fluid sb-page px-2 px-md-3 py-1">

<?php if ((int) $total_rows === 0): ?>
<div class="alert alert-info py-2 mb-2 small">
  <i class="bi bi-info-circle me-2"></i>
  No catalog data loaded yet. Import <code>database/subscription_builder_seed.sql</code> to enable the builder.
</div>
<?php endif; ?>

<div class="sb-layout">
  <div class="sb-main">

    <section class="sb-section sb-section-plan" id="sb-plan-section">
      <div class="sb-section-head sb-section-head-toggle-wrap">
        <button type="button" class="sb-section-head-toggle" id="sb-plan-toggle" aria-expanded="true" aria-controls="sb-plan-body">
          <span class="sb-step">1</span>
          <span class="sb-section-chevron" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          <span class="sb-section-title mb-0">Select Plan</span>
        </button>
      </div>
      <div id="sb-plan-body" class="sb-section-body">
        <div id="sb-plan-grid" class="sb-plan-grid"></div>
      </div>
    </section>

    <section class="sb-section sb-section-industry" id="sb-industry-section">
      <div class="sb-section-head sb-section-head-toggle-wrap">
        <button type="button" class="sb-section-head-toggle" id="sb-industry-toggle" aria-expanded="true" aria-controls="sb-industry-body">
          <span class="sb-step">2</span>
          <span class="sb-section-chevron" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          <span class="sb-section-title mb-0">Select Industry</span>
        </button>
      </div>
      <div id="sb-industry-body" class="sb-section-body">
        <div id="sb-industry-row" class="sb-industry-row"></div>
        <div class="sb-info-banner">
          <i class="bi bi-check-circle me-1"></i>
          Features and pricing update based on the selected industry and plan.
        </div>
      </div>
    </section>

    <section class="sb-section sb-section-included" id="sb-included-section">
      <div class="sb-section-head sb-included-head sb-section-head-toggle-wrap justify-content-between flex-wrap gap-2">
        <button type="button" class="sb-section-head-toggle sb-included-head-toggle" id="sb-included-toggle" aria-expanded="true" aria-controls="sb-included-body">
          <span class="sb-step">3</span>
          <span class="sb-section-chevron sb-included-chevron" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          <span class="sb-included-head-text">
            <span class="sb-section-title mb-0" id="sb-included-title">Included in ElintOm Essential</span>
            <span class="sb-included-meta">
              <span class="sb-included-subtitle">No extra charges</span>
              <span class="sb-included-count-badge d-none" id="sb-included-count-badge"></span>
            </span>
          </span>
        </button>
        <button type="button" class="btn btn-link sb-included-view-all p-0 d-none" id="sb-included-view-all" aria-label="View all included features">View all</button>
      </div>
      <div id="sb-included-body" class="sb-included-body">
        <div id="sb-loading" class="sb-loading d-none"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
        <div id="sb-included-wrap" class="sb-included-panel"></div>
      </div>
    </section>

    <section class="sb-section sb-section-addons" id="sb-addons-section">
      <div class="sb-section-head sb-section-head-toggle-wrap">
        <button type="button" class="sb-section-head-toggle" id="sb-addons-toggle" aria-expanded="true" aria-controls="sb-addons-body">
          <span class="sb-step">4</span>
          <span class="sb-section-chevron" aria-hidden="true"><i class="bi bi-chevron-down"></i></span>
          <span class="sb-section-title mb-0">Chargeable Add-ons</span>
        </button>
      </div>
      <div id="sb-addons-body" class="sb-addons-body sb-section-body">
        <div class="sb-addons-toolbar">
          <input type="search" id="sb-addon-search" class="form-control form-control-sm sb-search-input" placeholder="Search module or feature…">
        </div>
        <div class="table-responsive sb-addons-table-wrap sb-scroll-area">
          <table class="table table-sm sb-addons-table mb-0">
            <colgroup>
              <col class="sb-col-num">
              <col class="sb-col-module">
              <col class="sb-col-feature">
              <col class="sb-col-per-item">
              <col class="sb-col-unit">
              <col class="sb-col-qty">
              <col class="sb-col-setup">
              <col class="sb-col-monthly">
              <col class="sb-col-total-setup">
              <col class="sb-col-total-monthly">
            </colgroup>
            <thead class="sticky-top">
              <tr>
                <th>#</th>
                <th>Module</th>
                <th>Feature / Item</th>
                <th class="text-end d-none d-xl-table-cell">Per Item</th>
                <th class="d-none d-lg-table-cell">Unit</th>
                <th>Qty</th>
                <th class="text-end d-none d-md-table-cell">Setup Fees</th>
                <th class="text-end d-none d-md-table-cell">Per Month</th>
                <th class="text-end">Total Setup</th>
                <th class="text-end">Total Mo.</th>
              </tr>
            </thead>
            <tbody id="sb-addons-rows">
              <tr><td colspan="10" class="text-center text-muted py-3">Select plan and industry to load add-ons.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </section>

  </div>

  <aside class="sb-summary-col">
    <div class="sb-summary-card h-100 d-flex flex-column">
      <div class="sb-summary-header">
        <h2>PROPOSAL SUMMARY</h2>
        <div class="sb-summary-context">
          <span id="sb-summary-industry">—</span> · <span id="sb-summary-plan">—</span>
        </div>
      </div>
      <div class="sb-summary-client-fields px-2 pt-2">
        <div class="mb-2">
          <label class="form-label small mb-1" for="sb-client-name">Client Name</label>
          <input type="text" class="form-control form-control-sm" id="sb-client-name" placeholder="Client full name">
        </div>
        <div class="mb-2">
          <label class="form-label small mb-1" for="sb-client-business">Client Business Name</label>
          <input type="text" class="form-control form-control-sm" id="sb-client-business" placeholder="Business / company name">
        </div>
        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label small mb-1" for="sb-discount-percent">Discount (%)</label>
            <input type="number" class="form-control form-control-sm" id="sb-discount-percent" min="0" max="100" step="0.01" value="0">
          </div>
          <div class="col-6">
            <label class="form-label small mb-1" for="sb-gst-percent">GST (%)</label>
            <select class="form-select form-select-sm" id="sb-gst-percent">
              <option value="0">0%</option>
              <option value="5">5%</option>
              <option value="18" selected>18%</option>
            </select>
          </div>
        </div>
      </div>
      <div class="sb-summary-body sb-scroll-area flex-grow-1">
        <div class="sb-summary-block">
          <div class="sb-summary-block-title">Setup Charges (One Time)</div>
          <div id="sb-setup-lines"></div>
          <div class="sb-summary-line">
            <span>Subtotal</span>
            <span id="sb-subtotal-setup">₹ 0</span>
          </div>
          <div class="sb-summary-line text-muted small">
            <span>Discount</span>
            <span id="sb-discount-setup">₹ 0</span>
          </div>
          <div class="sb-summary-line text-muted small">
            <span>GST</span>
            <span id="sb-gst-setup">₹ 0</span>
          </div>
          <div class="sb-summary-line sb-summary-total">
            <span>Net Setup</span>
            <span id="sb-total-setup">₹ 0</span>
          </div>
        </div>
        <div class="sb-summary-block">
          <div class="sb-summary-block-title">Monthly Charges</div>
          <div id="sb-monthly-lines"></div>
          <div class="sb-summary-line">
            <span>Subtotal</span>
            <span id="sb-subtotal-monthly">₹ 0</span>
          </div>
          <div class="sb-summary-line text-muted small">
            <span>Discount</span>
            <span id="sb-discount-monthly">₹ 0</span>
          </div>
          <div class="sb-summary-line text-muted small">
            <span>GST</span>
            <span id="sb-gst-monthly">₹ 0</span>
          </div>
          <div class="sb-summary-line sb-summary-total">
            <span>Net Monthly</span>
            <span id="sb-total-monthly">₹ 0</span>
          </div>
        </div>
        <div class="sb-net-total mb-2">
          <div class="label">Net Setup Payable</div>
          <div class="amount" id="sb-net-setup">₹ 0</div>
        </div>
        <div class="sb-net-total">
          <div class="label">Net Monthly Payable</div>
          <div class="amount" id="sb-net-monthly">₹ 0</div>
        </div>
        <p class="sb-summary-note mb-0">Discount applied on subtotal; GST calculated after discount.</p>
      </div>
      <div class="sb-summary-footer d-grid gap-2">
        <?php if (function_exists('has_module_access') && (has_module_access('elintom_proposals') || has_module_access('elintom_proposals_list'))): ?>
        <a href="<?php echo site_url('elintom-proposals'); ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-file-earmark-text me-1"></i>View Saved Proposals
        </a>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-primary btn-sm" id="sb-preview-quote">
          <i class="bi bi-eye me-1"></i>Preview Proposal
        </button>
        <button type="button" class="btn btn-success btn-sm" id="sb-download-quote">
          <i class="bi bi-file-earmark-pdf me-1"></i>Download Proposal PDF
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="sb-clear-all">Clear All</button>
      </div>
    </div>
  </aside>
</div>

</div>

<script>
window.SB_CONFIG = {
  catalogUrl: <?php echo json_encode($catalog_url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  previewQuoteUrl: <?php echo json_encode(site_url('subscription-builder/quote-preview'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  downloadQuoteUrl: <?php echo json_encode(site_url('subscription-builder/quote-pdf'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  defaultPlan: <?php echo json_encode($default_plan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  defaultIndustry: <?php echo json_encode($default_industry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  plans: <?php echo $plans_json; ?>,
  industries: <?php echo $industries_json; ?>
};
</script>
<script src="<?php echo base_url('assets/js/subscription-builder.js'); ?>"></script>
<?php $this->load->view('partials/footer'); ?>
