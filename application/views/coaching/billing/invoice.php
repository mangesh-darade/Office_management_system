<?php $this->load->view('partials/header', ['title' => $invoice->invoice_no]); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<h1 class="h5"><?php echo esc_view($invoice->invoice_no); ?> — <?php echo esc_view($client->full_name); ?></h1>
<p>Total: ₹<?php echo number_format($invoice->total_amount,2); ?> · Paid: ₹<?php echo number_format($invoice->paid_amount,2); ?></p>
<table class="table table-sm card shadow-soft"><thead><tr><th>#</th><th>Due</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($installments as $inst): ?><tr><td><?php echo (int)$inst->installment_no; ?></td><td><?php echo esc_view($inst->due_date); ?></td><td>₹<?php echo number_format($inst->amount,2); ?></td><td><?php echo esc_view($inst->status); ?></td><td><?php if ($inst->status==='pending'): ?><a href="<?php echo site_url('coaching-billing/mark-paid/'.$inst->id.'?redirect='.urlencode(current_url())); ?>">Mark paid</a> · <a href="<?php echo site_url('coaching-payments/pay/'.$inst->id); ?>">Pay online</a><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table>
<?php $this->load->view('partials/footer'); ?>
