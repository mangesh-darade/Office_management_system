<?php $this->load->view('partials/header', ['title' => $row ? 'Edit Coach' : 'Add Coach']); ?>
<div class="oms-form-compact">
<?php $this->load->view('coaching/_subnav'); ?>
<div class="card shadow-soft oms-form-card"><div class="card-body">
<?php echo form_open($row ? 'coaching-coaches/edit/'.$row->id : 'coaching-coaches/create'); ?>
<div class="mb-3"><label class="form-label">User</label><select name="user_id" class="form-select" required><?php foreach ($users as $u): ?><option value="<?php echo (int)$u->id; ?>" <?php echo ($row && (int)$row->user_id===(int)$u->id)?'selected':''; ?>><?php echo esc_view($u->name.' — '.$u->email); ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="<?php echo $row ? esc_view($row->title) : ''; ?>"></div>
<div class="mb-3"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="3"><?php echo $row ? esc_view($row->bio) : ''; ?></textarea></div>
<div class="row"><div class="col-md-6 mb-3"><label class="form-label">Hourly rate (₹)</label><input type="number" step="0.01" name="hourly_rate" class="form-control" value="<?php echo $row ? $row->hourly_rate : 0; ?>"></div>
<div class="col-md-6 mb-3"><label class="form-label">Commission %</label><input type="number" step="0.1" name="commission_pct" class="form-control" value="<?php echo $row ? $row->commission_pct : 0; ?>"></div></div>
<div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?php echo (!$row || $row->status==='active')?'selected':''; ?>>Active</option><option value="inactive" <?php echo ($row && $row->status==='inactive')?'selected':''; ?>>Inactive</option></select></div>
<button type="submit" class="btn btn-primary">Save</button> <a href="<?php echo site_url('coaching-coaches'); ?>" class="btn btn-link">Cancel</a>
<?php echo form_close(); ?>
</div></div>
</div>
<?php $this->load->view('partials/footer'); ?>
