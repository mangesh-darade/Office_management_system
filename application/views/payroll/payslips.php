<?php $this->load->view('partials/header', ['title' => 'Payslips']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Payslips</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('payroll/structures'); ?>">Salary Structures</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('payroll/generate'); ?>">Generate Payslip</a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Month (YYYY-MM)</label>
        <input type="text" name="period" class="form-control" placeholder="2025-04" value="<?php echo htmlspecialchars(isset($filters['period'])?$filters['period']:''); ?>" />
      </div>
      <div class="col-md-3">
        <label class="form-label">Employee</label>
        <select name="user_id" class="form-select">
          <option value="">All</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>" <?php echo (!empty($filters['user_id']) && (int)$filters['user_id']===(int)$u['id'])?'selected':''; ?>><?php echo htmlspecialchars($u['label']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-outline-secondary mt-3">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('payroll/send_payslips'); ?>" id="payslips-email-form">
      <div class="mb-2">
        <button type="submit" class="btn btn-outline-primary btn-sm">Send Email</button>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th><input type="checkbox" id="payslips-select-all" /></th>
              <th>#</th>
              <th>Employee</th>
              <th>Period</th>
              <th>Gross</th>
              <th>Net</th>
              <th>Generated At</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="8" class="text-center text-muted">No payslips found.</td></tr>
            <?php else: $i=1; foreach ($rows as $r): ?>
              <tr>
                <td><input type="checkbox" name="ids[]" value="<?php echo (int)$r->id; ?>" /></td>
                <td><?php echo $i++; ?></td>
                <td><?php echo htmlspecialchars(trim((isset($r->name)?$r->name:'').' <'.(isset($r->email)?$r->email:'').'>')); ?></td>
                <td><?php echo htmlspecialchars($r->period); ?></td>
                <td><?php echo number_format((float)$r->gross,2); ?></td>
                <td><?php echo number_format((float)$r->net,2); ?></td>
                <td><?php echo htmlspecialchars(isset($r->generated_at)?$r->generated_at:''); ?></td>
                <td>
                  <a href="<?php echo site_url('payroll/view/'.(int)$r->id); ?>" class="btn btn-outline-secondary btn-sm">View</a>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </form>
  </div>
</div>
<script>
  (function(){
    var form = document.getElementById('payslips-email-form');
    if (!form) { return; }
    var selectAll = document.getElementById('payslips-select-all');
    if (!selectAll) { return; }
    selectAll.addEventListener('change', function(){
      var boxes = form.querySelectorAll('input[name="ids[]"]');
      for (var i = 0; i < boxes.length; i++){
        boxes[i].checked = selectAll.checked;
      }
    });
  })();
</script>
<?php $this->load->view('partials/footer'); ?>
