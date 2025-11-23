<?php $this->load->view('partials/header', ['title' => 'Generate Payslip']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Generate Payslip</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('payroll/payslips'); ?>">Back</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Employee <span class="text-danger">*</span></label>
          <select name="user_id" class="form-select" required>
            <option value="">Select employee</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Month (YYYY-MM) <span class="text-danger">*</span></label>
          <input type="text" name="period" class="form-control" placeholder="2025-04" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Pay Mode</label>
          <input type="text" name="pay_mode" class="form-control" placeholder="NEFT / Cash" />
        </div>
        <div class="col-md-6">
          <label class="form-label">Bank Name</label>
          <input type="text" name="bank_name" class="form-control" placeholder="HDFC Bank, Pune" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Bank A/C No</label>
          <input type="text" name="bank_ac_no" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">PAN No</label>
          <input type="text" name="pan_no" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Location</label>
          <input type="text" name="location" class="form-control" placeholder="Pune" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Payment Days</label>
          <input type="number" step="0.5" name="payment_days" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Present Days</label>
          <input type="number" step="0.5" name="present_days" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Paid Leaves</label>
          <input type="number" step="0.5" name="paid_leaves" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Leave Without Pay</label>
          <input type="number" step="0.5" name="leave_without_pay" class="form-control" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Balance Leaves</label>
          <input type="number" step="0.5" name="balance_leaves" class="form-control" />
        </div>
        <div class="col-md-12">
          <label class="form-label">Remarks</label>
          <textarea name="remarks" class="form-control" rows="3"></textarea>
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary">Generate</button>
      </div>
    </form>
  </div>
</div>
<script>
  (function(){
    var userSelect = document.querySelector('select[name="user_id"]');
    var periodInput = document.querySelector('input[name="period"]');
    if (!userSelect) { return; }

    function fillField(name, value){
      var el = document.querySelector('[name="' + name + '"]');
      if (!el) { return; }
      if (value !== null && value !== undefined && value !== ''){
        el.value = value;
      }
    }

    function buildMetaUrl(){
      var uid = userSelect.value;
      if (!uid) { return null; }
      var base = '<?php echo site_url('payroll/employee_meta'); ?>/' + encodeURIComponent(uid);
      if (!periodInput) {
        return base;
      }
      var period = periodInput.value || '';
      period = period.trim();
      if (period === '') {
        return base;
      }
      return base + '?period=' + encodeURIComponent(period);
    }

    function loadEmployeeMeta(){
      var url = buildMetaUrl();
      if (!url) { return; }
      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(function(resp){ return resp.json(); })
        .then(function(json){
          if (!json || !json.success || !json.data) { return; }
          var d = json.data;
          fillField('pay_mode', d.pay_mode);
          fillField('bank_name', d.bank_name);
          fillField('bank_ac_no', d.bank_ac_no);
          fillField('pan_no', d.pan_no);
          fillField('location', d.location);
          fillField('payment_days', d.payment_days);
          fillField('present_days', d.present_days);
          fillField('paid_leaves', d.paid_leaves);
          fillField('leave_without_pay', d.leave_without_pay);
          fillField('balance_leaves', d.balance_leaves);
        })
        .catch(function(err){
          if (window.console && console.error) {
            console.error(err);
          }
        });
    }

    userSelect.addEventListener('change', function(){
      loadEmployeeMeta();
    });

    if (periodInput) {
      periodInput.addEventListener('change', function(){
        if (!userSelect.value) { return; }
        loadEmployeeMeta();
      });
    }
  })();
</script>
<?php $this->load->view('partials/footer'); ?>
