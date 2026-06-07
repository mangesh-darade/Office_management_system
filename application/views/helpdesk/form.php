<?php $this->load->view('partials/header', ['title' => 'Ticket']); ?>
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3"><?php echo $action==='edit'?htmlspecialchars($item->ticket_number):'New Ticket'; ?></h1>
<div class="card shadow-soft"><div class="card-body"><form method="post"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="mb-3"><label class="form-label">Subject</label><input name="subject" class="form-control" required value="<?php echo $item?htmlspecialchars($item->subject):''; ?>"></div>
<div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="5" required><?php echo $item?htmlspecialchars($item->description):''; ?></textarea></div>
<?php if ($action==='create'): ?><div class="mb-3"><label class="form-label">Priority</label><select name="priority" class="form-select"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="critical">Critical</option></select></div><?php else: ?>
<div class="row g-3 mb-3"><div class="col-md-4"><label class="form-label">Priority</label><select name="priority" class="form-select"><?php foreach (['low','medium','high','critical'] as $p): ?><option value="<?php echo $p; ?>" <?php echo ($item->priority===$p)?'selected':''; ?>><?php echo $p; ?></option><?php endforeach; ?></select></div>
<?php if (!empty($can_manage)): ?><div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (['open','in_progress','resolved','closed'] as $s): ?><option value="<?php echo $s; ?>" <?php echo ($item->status===$s)?'selected':''; ?>><?php echo $s; ?></option><?php endforeach; ?></select></div>
<div class="col-md-4"><label class="form-label">Assign to</label><select name="assigned_to" class="form-select"><option value="">—</option><?php foreach ($assignees as $u): ?><option value="<?php echo (int)$u->id; ?>" <?php echo ($item->assigned_to==$u->id)?'selected':''; ?>><?php echo htmlspecialchars($u->name); ?></option><?php endforeach; ?></select></div><?php endif; ?></div><?php endif; ?>
<button class="btn btn-primary">Save</button> <a href="<?php echo site_url('helpdesk'); ?>" class="btn btn-outline-secondary">Cancel</a>
</form></div></div></div>
<?php $this->load->view('partials/footer'); ?>
