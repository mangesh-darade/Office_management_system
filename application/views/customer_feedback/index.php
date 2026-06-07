<?php $this->load->view('partials/header', ['title' => 'Customer Feedback']); ?>
<div class="container-fluid py-3">
<?php
$can_add = function_exists('has_module_access') && has_module_access('customer_feedback');
$actions = $can_add ? '<a class="btn btn-primary btn-sm" href="'.site_url('customer-feedback/create').'"><i class="bi bi-plus-lg me-1"></i>Log Feedback</a>' : '';
$this->load->view('partials/oms_page_head', ['title' => 'Customer Feedback', 'icon' => 'bi-chat-heart', 'subtitle' => 'Structured client feedback log', 'actions_html' => $actions]);
?>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Customer</th><th>Rating</th><th>Project</th><th>By</th><th>Date</th></tr></thead><tbody>
<?php if (empty($rows)): ?><tr><td colspan="5" class="text-muted text-center">No feedback logged.</td></tr><?php else: foreach ($rows as $r): ?>
<tr><td><?php echo htmlspecialchars($r->customer_name ?: '—'); ?></td><td><?php echo (int)$r->rating; ?>/5</td><td><?php echo htmlspecialchars($r->project_name ?: '—'); ?></td><td><?php echo htmlspecialchars($r->submitter_name); ?></td><td><?php echo htmlspecialchars($r->created_at); ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
