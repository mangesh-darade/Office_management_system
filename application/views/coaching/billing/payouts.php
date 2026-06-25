<?php $this->load->view('partials/header', ['title' => 'Coach Payouts']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="row g-3">
<div class="col-md-4">
  <div class="card shadow-soft"><div class="card-body">
    <?php echo form_open('coaching-billing/payouts'); ?>
    <select name="coach_id" class="form-select mb-2"><?php foreach ($coaches as $c): ?><option value="<?php echo (int)$c->id; ?>"><?php echo esc_view($c->name); ?></option><?php endforeach; ?></select>
    <input name="amount" type="number" step="0.01" class="form-control mb-2" placeholder="Amount" required>
    <input name="payout_date" type="date" class="form-control mb-2" value="<?php echo date('Y-m-d'); ?>">
    <button class="btn btn-primary btn-sm">Record payout</button>
    <?php echo form_close(); ?>
  </div></div>
</div>
<div class="col-md-8">
  <table class="table table-sm card shadow-soft"><thead><tr><th>Coach</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td><?php echo esc_view($r->coach_name); ?></td><td>₹<?php echo number_format($r->amount,2); ?></td><td><?php echo esc_view($r->payout_date); ?></td><td><?php echo esc_view($r->status); ?></td></tr><?php endforeach; ?>
  </tbody></table>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
