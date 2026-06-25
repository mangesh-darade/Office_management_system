<?php $this->load->view('partials/header', ['title' => 'Submit Reward Claim']); ?>
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3">Submit reward claim</h1>
<p class="text-muted">Select activity and team members. Points go to the approval queue for Lead/Admin review.</p>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div><?php endif; ?>
<div class="card shadow-soft"><div class="card-body">
<form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="mb-3"><label class="form-label">Activity</label><select name="rule_code" class="form-select" required><option value="">— Select —</option><?php foreach ($rules as $r): ?><option value="<?php echo esc_view($r->code); ?>"><?php echo esc_view($r->name); ?> (<?php echo ((float)$r->points>=0?'+':'').number_format((float)$r->points,0); ?> pts)</option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label">Team member(s)</label><select name="user_ids[]" class="form-select" multiple size="8" required><?php foreach ($users as $u): ?><option value="<?php echo (int)$u->id; ?>"><?php echo esc_view($u->name); ?></option><?php endforeach; ?></select><div class="form-text">Hold Ctrl/Cmd to select multiple.</div></div>
<div class="mb-3"><label class="form-label">Reference / notes</label><textarea name="reference_label" class="form-control" rows="2" placeholder="Brief description for approver"></textarea></div>
<button class="btn btn-primary">Submit for approval</button> <a class="btn btn-outline-secondary" href="<?php echo site_url('rewards'); ?>">Cancel</a>
</form></div></div></div>
<?php $this->load->view('partials/footer'); ?>
