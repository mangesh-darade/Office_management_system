<?php $this->load->view('partials/header', ['title' => 'Salary Structures']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Salary Structures</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('payroll/payslips'); ?>">Payslips</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('payroll/structure'); ?>">Add / Edit Structure</a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Employee</th>
            <th>Basic</th>
            <th>HRA</th>
            <th>Allowances</th>
            <th>Deductions</th>
            <th>Net (approx)</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted">No salary structures defined.</td></tr>
          <?php else: $i=1; foreach ($rows as $r): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><?php echo htmlspecialchars(trim((isset($r->name)?$r->name:'').' <'.(isset($r->email)?$r->email:'').'>')); ?></td>
              <td><?php echo number_format((float)$r->basic,2); ?></td>
              <td><?php echo number_format((float)$r->hra,2); ?></td>
              <td><?php echo number_format((float)$r->allowances,2); ?></td>
              <td><?php echo number_format((float)$r->deductions,2); ?></td>
              <td><?php $net = (float)$r->basic + (float)$r->hra + (float)$r->allowances - (float)$r->deductions; echo number_format(max($net,0),2); ?></td>
              <td>
                <a href="<?php echo site_url('payroll/structure/'.(int)$r->user_id); ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
