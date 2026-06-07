<?php $this->load->view('partials/header', ['title' => 'Reward History']); ?>
<div class="container-fluid py-3"><h1 class="h4 fw-bold mb-3">Point history</h1>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Date</th><th>Event</th><th>Points</th><th>Status</th><th>Reference</th></tr></thead><tbody>
<?php foreach ($rows as $t): ?><tr><td><?php echo htmlspecialchars($t->created_at); ?></td><td><?php echo htmlspecialchars($t->rule_name ?: $t->source_event); ?></td><td><?php echo number_format((float)$t->points,0); ?></td><td><?php echo htmlspecialchars($t->status); ?></td><td><?php echo htmlspecialchars($t->reference_label ?: ''); ?></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
