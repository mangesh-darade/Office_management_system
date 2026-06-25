<?php $this->load->view('partials/header', ['title' => 'Lead Mapping']); ?>

<style>
  .lead-map-header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #fff; border-radius: 12px; }
  .lead-map-card { border: 1px solid #e5e7eb; border-radius: 12px; transition: box-shadow .2s ease; }
  .lead-map-card:hover { box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08); }
  .lead-map-select { min-height: 210px; }
  .badge-soft { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
  .lead-map-tools .form-control { font-size: 0.875rem; }
</style>

<div class="container-fluid py-3">
  <div class="lead-map-header p-4 mb-3">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
      <div>
        <h4 class="mb-1"><i class="bi bi-diagram-3 me-2"></i>Lead to User Mapping</h4>
        <p class="mb-0 opacity-75">Assign staff members under each lead to control hierarchy-based visibility.</p>
      </div>
      <div class="text-md-end">
        <div class="small opacity-75">Total Leads</div>
        <div class="fs-4 fw-bold"><?php echo (int)count($leads); ?></div>
      </div>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle me-2"></i><?php echo esc_view($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
      <?php if (empty($leads)): ?>
        <div class="text-center text-muted py-4">
          <i class="bi bi-info-circle me-1"></i>No lead users found.
        </div>
      <?php else: ?>
        <form method="post" action="<?php echo site_url('lead-mapping/save'); ?>">
          <div class="row g-3">
            <?php foreach ($leads as $lead): ?>
              <?php
                $selected = isset($mappings[(int)$lead->id]) ? $mappings[(int)$lead->id] : array();
                $leadName = trim((string)$lead->name) !== '' ? (string)$lead->name : ('Lead #' . (int)$lead->id);
              ?>
              <div class="col-12 col-xl-6">
                <div class="lead-map-card h-100 p-3 bg-white">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <div>
                      <h6 class="mb-1 fw-bold"><?php echo esc_view($leadName); ?></h6>
                      <div class="text-muted small"><?php echo esc_view((string)$lead->email, ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <span class="badge badge-soft"><?php echo (int)count($selected); ?> mapped</span>
                  </div>

                  <div class="lead-map-tools d-flex flex-column flex-md-row gap-2 mb-2">
                    <input
                      type="text"
                      class="form-control form-control-sm js-user-search"
                      data-target="lead-select-<?php echo (int)$lead->id; ?>"
                      placeholder="Search users..."
                    >
                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-outline-success btn-sm js-select-all" data-target="lead-select-<?php echo (int)$lead->id; ?>">Select All</button>
                      <button type="button" class="btn btn-outline-secondary btn-sm js-clear-all" data-target="lead-select-<?php echo (int)$lead->id; ?>">Clear</button>
                    </div>
                  </div>

                  <label class="form-label small text-muted mb-1">Select team members</label>
                  <select
                    id="lead-select-<?php echo (int)$lead->id; ?>"
                    class="form-select lead-map-select"
                    name="map[<?php echo (int)$lead->id; ?>][]"
                    multiple
                  >
                    <?php foreach ($users as $u): ?>
                      <?php
                        $userName = trim((string)$u->name) !== '' ? (string)$u->name : ('User #' . (int)$u->id);
                      ?>
                      <option value="<?php echo (int)$u->id; ?>" <?php echo in_array((int)$u->id, $selected, true) ? 'selected' : ''; ?>>
                        <?php echo esc_view($userName . ' (' . (string)$u->email . ')', ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="form-text">Tip: Hold <strong>Ctrl</strong> (or Cmd on Mac) to select multiple users.</div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary px-4">
              <i class="bi bi-save me-1"></i>Save Mapping
            </button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>

<script>
(function () {
  function getSelect(targetId) {
    return document.getElementById(targetId);
  }

  document.querySelectorAll('.js-select-all').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var select = getSelect(btn.getAttribute('data-target'));
      if (!select) { return; }
      Array.prototype.forEach.call(select.options, function (opt) {
        if (opt.style.display !== 'none') { opt.selected = true; }
      });
    });
  });

  document.querySelectorAll('.js-clear-all').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var select = getSelect(btn.getAttribute('data-target'));
      if (!select) { return; }
      Array.prototype.forEach.call(select.options, function (opt) {
        opt.selected = false;
      });
    });
  });

  document.querySelectorAll('.js-user-search').forEach(function (input) {
    input.addEventListener('input', function () {
      var q = (input.value || '').toLowerCase();
      var select = getSelect(input.getAttribute('data-target'));
      if (!select) { return; }
      Array.prototype.forEach.call(select.options, function (opt) {
        var text = (opt.text || '').toLowerCase();
        opt.style.display = (q === '' || text.indexOf(q) !== -1) ? '' : 'none';
      });
    });
  });
})();
</script>
