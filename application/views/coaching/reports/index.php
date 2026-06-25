<?php $this->load->view('partials/header', ['title' => 'Coaching Reports']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="row g-3 mb-4"><?php foreach ([['Active clients',$stats['active_clients']],['Revenue', '₹'.number_format($stats['revenue_paid'],2)],['Open leads',$stats['open_leads']]] as $s): ?><div class="col-md-4"><div class="card shadow-soft"><div class="card-body"><div class="text-muted small"><?php echo $s[0]; ?></div><div class="h4"><?php echo $s[1]; ?></div></div></div></div><?php endforeach; ?>
<table class="table table-sm card shadow-soft"><thead><tr><th>Coach</th><th>Active clients</th></tr></thead><tbody><?php foreach ($by_coach as $r): ?><tr><td><?php echo esc_view($r->coach_name); ?></td><td><?php echo (int)$r->client_count; ?></td></tr><?php endforeach; ?></tbody></table>
<?php $this->load->view('partials/footer'); ?>
