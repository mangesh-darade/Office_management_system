<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pay installment</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:480px">
  <div class="card shadow">
    <div class="card-body">
      <h1 class="h5 mb-1">Pay ₹<?php echo number_format((float) $installment->amount, 2); ?></h1>
      <p class="text-muted small mb-3">Installment #<?php echo (int) $installment->installment_no; ?> · Due <?php echo htmlspecialchars($installment->due_date); ?></p>

      <?php if (!empty($razorpay_error)): ?>
        <div class="alert alert-danger small"><?php echo htmlspecialchars($razorpay_error); ?></div>
        <a class="btn btn-outline-primary" href="<?php echo site_url('coaching-payments/confirm-manual/' . (int) $installment->id); ?>">Record manual payment</a>
      <?php elseif ($settings && $settings->gateway === 'razorpay' && $settings->is_active && !empty($razorpay_order_id) && $settings->key_id): ?>
        <p class="small text-muted">Secure payment via Razorpay (UPI, cards, netbanking).</p>
        <button type="button" class="btn btn-primary w-100" id="rzp-pay-btn">Pay now</button>
        <div id="pay-msg" class="mt-2 small"></div>
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
        <script>
        document.getElementById('rzp-pay-btn').onclick = function() {
          var options = {
            key: <?php echo json_encode($settings->key_id); ?>,
            amount: <?php echo (int) round((float) $installment->amount * 100); ?>,
            currency: 'INR',
            name: <?php echo json_encode(get_company_name()); ?>,
            description: 'Coaching installment #<?php echo (int) $installment->installment_no; ?>',
            order_id: <?php echo json_encode($razorpay_order_id); ?>,
            handler: function (response) {
              var fd = new FormData();
              fd.append('razorpay_order_id', response.razorpay_order_id);
              fd.append('razorpay_payment_id', response.razorpay_payment_id);
              fd.append('razorpay_signature', response.razorpay_signature);
              fd.append('<?php echo $this->security->get_csrf_token_name(); ?>', '<?php echo $this->security->get_csrf_hash(); ?>');
              fetch(<?php echo json_encode(site_url('coaching-payments/verify')); ?>, { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                  if (data.success) { window.location = data.redirect || <?php echo json_encode(site_url('coaching-payments/success')); ?>; }
                  else { document.getElementById('pay-msg').textContent = data.message || 'Verification failed'; }
                })
                .catch(function() { document.getElementById('pay-msg').textContent = 'Network error'; });
            },
            theme: { color: '#0d6efd' }
          };
          new Razorpay(options).open();
        };
        </script>
      <?php else: ?>
        <p class="small">Online gateway not configured. Use manual confirmation or ask admin to set Razorpay keys under Coaching → Settings.</p>
        <a class="btn btn-primary" href="<?php echo site_url('coaching-payments/confirm-manual/' . (int) $installment->id); ?>">Confirm manual payment</a>
      <?php endif; ?>
      <a class="btn btn-link btn-sm d-block mt-2" href="<?php echo site_url('coaching-portal'); ?>">Back to portal</a>
    </div>
  </div>
</div>
</body>
</html>
