<?php $this->load->view('partials/header', ['title' => $row ? 'Edit Client' : 'Add Client']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="card shadow-soft"><div class="card-body">
<?php echo form_open($row ? 'coaching-clients/edit/'.$row->id : 'coaching-clients/create'); ?>
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Full name *</label><input name="full_name" class="form-control" required value="<?php echo $row ? htmlspecialchars($row->full_name) : ''; ?>"></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo $row ? htmlspecialchars($row->email) : ''; ?>"></div>
<div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="<?php echo $row ? htmlspecialchars($row->phone) : ''; ?>"></div>
<div class="col-md-6"><label class="form-label">Company</label><input name="company" class="form-control" value="<?php echo $row ? htmlspecialchars($row->company) : ''; ?>"></div>
<?php if (!empty($show_crm_picker)): ?>
<div class="col-md-6"><label class="form-label">Link to CRM client</label><select name="crm_client_id" class="form-select"><option value="">— None —</option><?php foreach ($crm_clients as $c): ?><option value="<?php echo (int) $c->id; ?>" <?php echo ($row && !empty($row->crm_client_id) && (int) $row->crm_client_id === (int) $c->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c->label); ?></option><?php endforeach; ?></select></div>
<?php endif; ?>
<div class="col-md-6"><label class="form-label">Primary coach</label><select name="primary_coach_id" class="form-select"><option value="">—</option><?php foreach ($coaches as $c): ?><option value="<?php echo (int)$c->id; ?>" <?php echo ($row && (int)$row->primary_coach_id===(int)$c->id)?'selected':''; ?>><?php echo htmlspecialchars($c->name); ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active">Active</option><option value="prospect" <?php echo ($row && $row->status==='prospect')?'selected':''; ?>>Prospect</option><option value="inactive" <?php echo ($row && $row->status==='inactive')?'selected':''; ?>>Inactive</option></select></div>
<div class="col-12"><label class="form-label">Also assign coaches</label><select name="coach_ids[]" class="form-select" multiple size="4"><?php foreach ($coaches as $c): ?><option value="<?php echo (int)$c->id; ?>" <?php echo in_array((int)$c->id, $assigned, true)?'selected':''; ?>><?php echo htmlspecialchars($c->name); ?></option><?php endforeach; ?></select></div>
<div class="col-12"><div class="form-check"><input type="checkbox" name="portal_enabled" value="1" class="form-check-input" id="portal" <?php echo ($row && $row->portal_enabled)?'checked':''; ?>><label class="form-check-label" for="portal">Enable client portal login (creates user if new)</label></div></div>
<div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="3"><?php echo $row ? htmlspecialchars($row->notes) : ''; ?></textarea></div>
</div>
<button type="submit" class="btn btn-primary mt-3">Save</button>
<?php echo form_close(); ?>
</div></div>
<?php $this->load->view('partials/footer'); ?>
