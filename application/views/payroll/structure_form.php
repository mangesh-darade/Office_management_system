<?php $this->load->view('partials/header', ['title' => 'Salary Structure']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Salary Structure</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('payroll/structures'); ?>">Back</a>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Employee <span class="text-danger">*</span></label>
          <select name="user_id" class="form-select" required <?php echo $user_id ? 'disabled' : ''; ?>>
            <option value="">Select employee</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int)$u['id']; ?>" <?php echo ($user_id && $user_id==(int)$u['id'])?'selected':''; ?>><?php echo htmlspecialchars($u['label']); ?></option>
            <?php endforeach; ?>
          </select>
          <?php if ($user_id): ?>
            <input type="hidden" name="user_id" value="<?php echo (int)$user_id; ?>" />
          <?php endif; ?>
        </div>
        <div class="col-md-3">
          <label class="form-label">Basic</label>
          <input type="number" step="0.01" name="basic" class="form-control" value="<?php echo isset($row->basic)?htmlspecialchars($row->basic):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">HRA</label>
          <input type="number" step="0.01" name="hra" class="form-control" value="<?php echo isset($row->hra)?htmlspecialchars($row->hra):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Conveyance Allowance</label>
          <input type="number" step="0.01" name="conveyance_allow" class="form-control" value="<?php echo isset($row->conveyance_allow)?htmlspecialchars($row->conveyance_allow):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Medical Allowance</label>
          <input type="number" step="0.01" name="medical_allow" class="form-control" value="<?php echo isset($row->medical_allow)?htmlspecialchars($row->medical_allow):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Educational Allowance</label>
          <input type="number" step="0.01" name="education_allow" class="form-control" value="<?php echo isset($row->education_allow)?htmlspecialchars($row->education_allow):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Special Allowance</label>
          <input type="number" step="0.01" name="special_allow" class="form-control" value="<?php echo isset($row->special_allow)?htmlspecialchars($row->special_allow):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Professional Tax</label>
          <input type="number" step="0.01" name="professional_tax" class="form-control" value="<?php echo isset($row->professional_tax)?htmlspecialchars($row->professional_tax):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">TDS</label>
          <input type="number" step="0.01" name="tds" class="form-control" value="<?php echo isset($row->tds)?htmlspecialchars($row->tds):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Other Allowances (Total)</label>
          <input type="number" step="0.01" name="allowances" class="form-control" value="<?php echo isset($row->allowances)?htmlspecialchars($row->allowances):''; ?>" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Other Deductions (Total)</label>
          <input type="number" step="0.01" name="deductions" class="form-control" value="<?php echo isset($row->deductions)?htmlspecialchars($row->deductions):''; ?>" />
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
