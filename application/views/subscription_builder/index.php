<?php
$plans_json = json_encode(array_values($plans), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$industries_json = json_encode(array_values($industries), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$countries_json = json_encode(array_values($countries), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$country_options_json = json_encode(array_values($country_options ?? array()), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$default_country_meta_json = json_encode($default_country_meta ?? array(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<?php $this->load->view('partials/header', [
  'title' => 'Subscription Builder',
  'extra_css' => ['assets/css/subscription-builder.css'],
]); ?>
<div class="container-fluid sb-page sb-proposal-only px-2 px-md-3 py-1">

<?php if ((int) $total_rows === 0): ?>
<div class="alert alert-info py-2 mb-2 small">
  <i class="bi bi-info-circle me-2"></i>
  No catalog data loaded yet. Import <code>database/subscription_builder_seed.sql</code> to enable the builder.
</div>
<?php endif; ?>
<?php if ($this->session->flashdata('warning')): ?>
<div class="alert alert-warning py-2 mb-2 small">
  <i class="bi bi-exclamation-triangle me-2"></i>
  <?php echo esc_view($this->session->flashdata('warning')); ?>
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
          <span class="sb-industry-note-default">Features update based on the selected industry and plan.</span>
          <span class="sb-industry-note-pricing sb-payment-only">Features and pricing update based on the selected industry and plan.</span>
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
              <span class="sb-included-count-badge d-none" id="sb-included-count-badge"></span>
            </span>
          </span>
        </button>
      </div>
      <div id="sb-included-body" class="sb-included-body">
        <div id="sb-loading" class="sb-loading d-none"><span class="spinner-border spinner-border-sm me-2"></span>Loading…</div>
        <div id="sb-included-wrap" class="sb-included-panel"></div>
      </div>
    </section>

    <section class="sb-section sb-section-addons sb-section-collapsed" id="sb-addons-section">
      <div class="sb-section-head sb-section-head-toggle-wrap">
        <button type="button" class="sb-section-head-toggle" id="sb-addons-toggle" aria-expanded="false" aria-controls="sb-addons-body">
          <span class="sb-step">4</span>
          <span class="sb-section-chevron" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          <span class="sb-section-title mb-0">Chargeable Add-ons</span>
        </button>
      </div>
      <div id="sb-addons-body" class="sb-addons-body sb-section-body d-none">
        <div class="sb-addons-toolbar">
          <input type="search" id="sb-addon-search" class="form-control form-control-sm sb-search-input" placeholder="Search module or feature…">
          <div class="sb-addons-country-wrap">
            <label class="sb-addons-country-label" for="sb-country-select">Country</label>
            <select id="sb-country-select" class="form-select form-select-sm sb-country-select" aria-label="Country for add-on pricing"></select>
            <div class="sb-addons-currency sb-payment-only" id="sb-currency-display" aria-live="polite"></div>
          </div>
        </div>
        <div class="table-responsive sb-addons-table-wrap">
          <table class="table table-sm sb-addons-table mb-0">
            <colgroup>
              <col class="sb-col-num">
              <col class="sb-col-module">
              <col class="sb-col-feature">
              <col class="sb-col-qty">
              <col class="sb-col-unit">
              <col class="sb-col-per-item sb-payment-only">
              <col class="sb-col-monthly sb-payment-only">
              <col class="sb-col-setup sb-payment-only">
              <col class="sb-col-total-setup sb-payment-only">
              <col class="sb-col-total-monthly sb-payment-only">
            </colgroup>
            <thead class="sticky-top">
              <tr>
                <th>#</th>
                <th>Module</th>
                <th>Feature / Item</th>
                <th class="text-center sb-col-qty-head">Qty</th>
                <th class="sb-addon-col-unit d-none d-lg-table-cell">Unit</th>
                <th class="text-end d-none d-xl-table-cell sb-th-multiline sb-payment-only">Per Item<br>Setup</th>
                <th class="text-end d-none d-md-table-cell sb-th-multiline sb-payment-only">Per Item<br>Per Month</th>
                <th class="text-end d-none d-md-table-cell sb-th-multiline sb-payment-only">One Time<br>Setup</th>
                <th class="text-end sb-payment-only">Total <br>Setup</th>
                <th class="text-end sb-payment-only">Total <br>Monthly</th>
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
    <div class="sb-summary-card d-flex flex-column">
      <div class="sb-summary-header">
        <h2 id="sb-summary-title">FEATURE SUMMARY</h2>
        <div class="sb-summary-meta-row">
          <div class="sb-summary-context">
            <span id="sb-summary-industry">—</span> · <span id="sb-summary-plan">—</span><span class="sb-payment-only"> · <span id="sb-summary-country">—</span> · <span id="sb-summary-currency">—</span></span>
          </div>
          <?php if (function_exists('has_module_access') && (has_module_access('elintom_proposals') || has_module_access('elintom_proposals_list'))): ?>
          <a href="<?php echo site_url('elintom-proposals'); ?>" class="btn btn-outline-light btn-sm sb-summary-saved-btn" title="View Saved Proposals">
            <i class="bi bi-file-earmark-text"></i>
            <span>Saved</span>
          </a>
          <?php endif; ?>
        </div>
        <div class="sb-summary-mode-toggle">
          <label class="sb-proposal-toggle sb-proposal-toggle-in-card" for="sb-show-payment-toggle">
            <span class="sb-proposal-toggle-switch">
              <input class="sb-proposal-toggle-input" type="checkbox" id="sb-show-payment-toggle" role="switch" aria-controls="sb-addons-section sb-summary-payment">
              <span class="sb-proposal-toggle-track" aria-hidden="true">
                <span class="sb-proposal-toggle-thumb"></span>
              </span>
            </span>
            <span class="sb-view-mode-option sb-view-mode-option-proposal" data-mode="proposal">
              <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
              <span>Proposal Summary</span>
            </span>
          </label>
        </div>
      </div>
      <div class="sb-summary-client-fields">
        <div class="mb-2">
          <label class="form-label small mb-1" for="sb-client-name">Client Name</label>
          <input type="text" class="form-control form-control-sm" id="sb-client-name" placeholder="Client full name">
        </div>
        <div class="mb-2">
          <label class="form-label small mb-1" for="sb-client-business">Client Business Name</label>
          <input type="text" class="form-control form-control-sm" id="sb-client-business" placeholder="Business / company name">
        </div>
      </div>
      <div class="sb-summary-body flex-grow-1" id="sb-summary-payment">
        <div class="sb-proposal-only-hint">
          Included features and selected add-on quantities. Turn on <strong>Proposal Summary</strong> to show charges, discounts, and tax.
        </div>
        <div class="sb-summary-block sb-feature-only" id="sb-included-summary-block">
          <div class="sb-summary-block-title">Included Features</div>
          <div id="sb-included-summary-text"></div>
        </div>
        <div class="sb-summary-block sb-feature-only">
          <div class="sb-summary-block-title">Chargeable Add-ons</div>
          <div id="sb-feature-addon-lines" class="sb-feature-addon-list"></div>
        </div>
        <div class="sb-summary-block sb-payment-only">
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
            <span>Tax</span>
            <span id="sb-gst-setup">₹ 0</span>
          </div>
          <div class="sb-summary-line sb-summary-total">
            <span>Net Setup</span>
            <span id="sb-total-setup">₹ 0</span>
          </div>
          <div class="sb-summary-block-adjustments">
            <div class="row g-2">
              <div class="col-7">
                <label class="form-label small mb-1" for="sb-setup-discount-input">Discount</label>
                <input type="text" class="form-control form-control-sm" id="sb-setup-discount-input" value="" placeholder="e.g. 10 or 10%" aria-label="Setup discount (flat amount or percentage with %)">
              </div>
              <div class="col-5">
                <label class="form-label small mb-1" for="sb-setup-gst-percent">Tax (%)</label>
                <select class="form-select form-select-sm" id="sb-setup-gst-percent">
                  <option value="0">0%</option>
                  <option value="5">5%</option>
                  <option value="18" selected>18%</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="sb-summary-block sb-payment-only">
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
            <span>Tax</span>
            <span id="sb-gst-monthly">₹ 0</span>
          </div>
          <div class="sb-summary-line sb-summary-total">
            <span>Net Monthly</span>
            <span id="sb-total-monthly">₹ 0</span>
          </div>
          <div class="sb-summary-block-adjustments">
            <div class="row g-2">
              <div class="col-7">
                <label class="form-label small mb-1" for="sb-monthly-discount-input">Discount</label>
                <input type="text" class="form-control form-control-sm" id="sb-monthly-discount-input" value="" placeholder="e.g. 10 or 10%" aria-label="Monthly discount (flat amount or percentage with %)">
              </div>
              <div class="col-5">
                <label class="form-label small mb-1" for="sb-monthly-gst-percent">Tax (%)</label>
                <select class="form-select form-select-sm" id="sb-monthly-gst-percent">
                  <option value="0">0%</option>
                  <option value="5">5%</option>
                  <option value="18" selected>18%</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="sb-net-total mb-2 sb-payment-only">
          <div class="label">Net Setup Payable</div>
          <div class="amount" id="sb-net-setup">₹ 0</div>
        </div>
        <div class="sb-net-total sb-payment-only">
          <div class="label">Net Monthly Payable</div>
          <div class="amount" id="sb-net-monthly">₹ 0</div>
        </div>
      </div>
      <div class="sb-summary-footer">
        <p class="sb-summary-note mb-2 sb-payment-only">Setup and monthly charges each have their own discount and tax. Use flat amount (e.g. 10) or add % (e.g. 10%). Tax is calculated after discount.</p>
        <div class="sb-summary-actions">
          <button type="button" class="btn btn-outline-primary btn-sm sb-summary-action-btn" id="sb-preview-quote" title="Preview Proposal">
            <i class="bi bi-eye"></i>
            <span>Preview</span>
          </button>
          <div class="sb-export-wrap sb-summary-action-btn-wrap">
            <button type="button" class="btn btn-success btn-sm sb-summary-action-btn sb-export-toggle" id="sb-export-toggle" aria-expanded="false" aria-controls="sb-export-menu" title="Export proposal">
              <i class="bi bi-download"></i>
              <span>Export</span>
              <i class="bi bi-chevron-down sb-export-chevron" aria-hidden="true"></i>
            </button>
            <div class="sb-export-menu d-none" id="sb-export-menu" role="menu" aria-label="Export format options">
              <button type="button" class="sb-export-option" data-format="pdf" role="menuitem">
                <i class="bi bi-file-earmark-pdf"></i>
                <span>PDF</span>
              </button>
              <button type="button" class="sb-export-option" data-format="excel" role="menuitem">
                <i class="bi bi-file-earmark-excel"></i>
                <span>Excel</span>
              </button>
              <button type="button" class="sb-export-option" data-format="doc" role="menuitem">
                <i class="bi bi-file-earmark-word"></i>
                <span>Word (DOC)</span>
              </button>
            </div>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm sb-summary-action-btn" id="sb-clear-all" title="Clear All">
            <i class="bi bi-arrow-counterclockwise"></i>
            <span>Clear</span>
          </button>
        </div>
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
  downloadExcelUrl: <?php echo json_encode(site_url('subscription-builder/quote-excel'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  downloadDocUrl: <?php echo json_encode(site_url('subscription-builder/quote-doc'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  defaultPlan: <?php echo json_encode($default_plan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  defaultIndustry: <?php echo json_encode($default_industry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  defaultCountry: <?php echo json_encode($default_country, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  defaultCountryMeta: <?php echo $default_country_meta_json; ?>,
  plans: <?php echo $plans_json; ?>,
  industries: <?php echo $industries_json; ?>,
  countries: <?php echo $countries_json; ?>,
  countryOptions: <?php echo $country_options_json; ?>
};
</script>
<script src="<?php echo base_url('assets/js/subscription-builder.js'); ?>"></script>
<?php $this->load->view('partials/footer'); ?>
