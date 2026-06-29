<?php $this->load->view('partials/header', ['title' => 'Lead']); ?>
<div class="oms-form-compact">
<?php $this->load->view('coaching/_subnav'); ?>
<div class="card shadow-soft oms-form-card"><div class="card-body"><?php echo form_open($row ? 'coaching-leads/edit/'.$row->id : 'coaching-leads/create'); ?>
<input name="full_name" class="form-control mb-2" placeholder="Name" required value="<?php echo $row?esc_view($row->full_name):''; ?>">
<input name="email" class="form-control mb-2" placeholder="Email" value="<?php echo $row?esc_view($row->email):''; ?>">
<input name="phone" class="form-control mb-2" placeholder="Phone" value="<?php echo $row?esc_view($row->phone):''; ?>">
<input name="source" class="form-control mb-2" placeholder="Source" value="<?php echo $row?esc_view($row->source):''; ?>">
<?php if ($row): ?><select name="status" class="form-select mb-2"><?php foreach (['new','contacted','qualified','converted','lost'] as $st): ?><option <?php echo $row->status===$st?'selected':''; ?>><?php echo $st; ?></option><?php endforeach; ?></select><?php endif; ?>
<textarea name="notes" class="form-control mb-2" rows="3"><?php echo $row?esc_view($row->notes):''; ?></textarea>
<button class="btn btn-primary">Save</button><?php echo form_close(); ?></div></div>
</div>
<?php $this->load->view('partials/footer'); ?>
