<div class="modal fade" id="omsActualHoursModal" tabindex="-1" aria-labelledby="omsActualHoursModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title h6" id="omsActualHoursModalLabel">
          <i class="bi bi-clock-history me-1"></i>Actual hours
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancel"></button>
      </div>
      <div class="modal-body py-3">
        <p class="small text-muted mb-2" id="omsActualHoursHint">Enter actual hours before marking complete.</p>
        <p class="small mb-2 d-none" id="omsActualHoursEstimateWrap">
          Estimate: <strong id="omsActualHoursEstimate">—</strong> hr
        </p>
        <label class="form-label small mb-1" for="omsActualHoursInput">Actual (hrs) <span class="text-danger">*</span></label>
        <input type="number" class="form-control form-control-sm" id="omsActualHoursInput"
               min="0" max="9999.99" step="0.25" required placeholder="e.g. 2.5" autocomplete="off">
        <div class="invalid-feedback">Enter a number between 0 and 9999.99.</div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" id="omsActualHoursCancel">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="omsActualHoursConfirm">
          <i class="bi bi-check-lg me-1"></i>Save &amp; complete
        </button>
      </div>
    </div>
  </div>
</div>
