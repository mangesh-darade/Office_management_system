<?php $this->load->view('partials/header', ['title' => 'Salary Structure']); ?>
<div class="oms-form-compact">
<div class="container-fluid py-3">
<div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div>
    <h1 class="h4 mb-1 fw-bold"><i class="bi bi-calculator text-primary me-2"></i>Salary Structure</h1>
    <p class="text-muted small mb-0">Define pay components for an employee</p>
  </div>
  <a class="btn btn-outline-secondary btn-sm mt-2 mt-sm-0" href="<?php echo site_url('payroll/structures'); ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card shadow-soft oms-form-card">
  <div class="card-body">
    <form method="post" data-validate="true">
      <div class="row g-2 oms-form-grid">
        <div class="col-md-4">
          <label class="form-label">Employee <span class="text-danger">*</span></label>
          <select name="user_id" class="form-select" required <?php echo $user_id ? 'disabled' : ''; ?>>
            <option value="">Select employee</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int)$u['id']; ?>" <?php echo ($user_id && $user_id==(int)$u['id'])?'selected':''; ?>><?php echo esc_view($u['label']); ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($user_id): ?>
            <input type="hidden" name="user_id" value="<?php echo (int)$user_id; ?>" />
          <?php endif; ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Basic</label>
          <input type="number" step="0.01" name="basic" class="form-control" value="<?php echo isset($row->basic)?esc_view($row->basic):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">HRA</label>
          <input type="number" step="0.01" name="hra" class="form-control" value="<?php echo isset($row->hra)?esc_view($row->hra):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Conveyance Allowance</label>
          <input type="number" step="0.01" name="conveyance_allow" class="form-control" value="<?php echo isset($row->conveyance_allow)?esc_view($row->conveyance_allow):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Medical Allowance</label>
          <input type="number" step="0.01" name="medical_allow" class="form-control" value="<?php echo isset($row->medical_allow)?esc_view($row->medical_allow):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Educational Allowance</label>
          <input type="number" step="0.01" name="education_allow" class="form-control" value="<?php echo isset($row->education_allow)?esc_view($row->education_allow):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Special Allowance</label>
          <input type="number" step="0.01" name="special_allow" class="form-control" value="<?php echo isset($row->special_allow)?esc_view($row->special_allow):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Professional Tax</label>
          <input type="number" step="0.01" name="professional_tax" class="form-control" value="<?php echo isset($row->professional_tax)?esc_view($row->professional_tax):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">TDS</label>
          <input type="number" step="0.01" name="tds" class="form-control" value="<?php echo isset($row->tds)?esc_view($row->tds):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Other Allowances (Total)</label>
          <input type="number" step="0.01" name="allowances" class="form-control" value="<?php echo isset($row->allowances)?esc_view($row->allowances):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Other Deductions (Total)</label>
          <input type="number" step="0.01" name="deductions" class="form-control" value="<?php echo isset($row->deductions)?esc_view($row->deductions):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">PF Percentage (%)</label>
          <input type="number" step="0.01" name="pf_percent" class="form-control" value="<?php echo isset($row->pf_percent)?esc_view($row->pf_percent):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">ESI Percentage (%)</label>
          <input type="number" step="0.01" name="esi_percent" class="form-control" value="<?php echo isset($row->esi_percent)?esc_view($row->esi_percent):''; ?>" />
        </div>
      </div>
      <div class="oms-form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
