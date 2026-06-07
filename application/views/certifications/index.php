<?php $this->load->view('partials/header', ['title' => 'Certifications']); ?>
<div class="container-fluid py-3">
<?php
$can_add = function_exists('has_module_access') && (has_module_access('certifications'));
$actions = $can_add ? '<a class="btn btn-primary btn-sm" href="'.site_url('certifications/create').'"><i class="bi bi-plus-lg me-1"></i>Submit</a>' : '';
$this->load->view('partials/oms_page_head', ['title' => 'Certifications', 'icon' => 'bi-patch-check', 'subtitle' => 'Submit and approve professional certifications', 'actions_html' => $actions]);
?>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Employee</th><th>Certification</th><th>Issuer</th><th>Status</th><th></th></tr></thead><tbody>
<?php if (empty($rows)): ?><tr><td colspan="5" class="text-muted text-center">No submissions.</td></tr><?php else: foreach ($rows as $r): ?>
<tr><td><?php echo htmlspecialchars($r->user_name); ?></td><td><?php echo htmlspecialchars($r->cert_name); ?></td><td><?php echo htmlspecialchars($r->issuer ?: '—'); ?></td><td><?php echo htmlspecialchars($r->status); ?></td><td>
<?php if (!empty($can_approve) && $r->status==='pending'): ?>
<form method="post" action="<?php echo site_url('certifications/approve/'.$r->id); ?>" class="d-inline"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?><button class="btn btn-sm btn-success">Approve</button></form>
<form method="post" action="<?php echo site_url('certifications/reject/'.$r->id); ?>" class="d-inline"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?><button class="btn btn-sm btn-outline-danger">Reject</button></form>
<?php else: ?>—<?php endif; ?>
</td></tr><?php endforeach; endif; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
