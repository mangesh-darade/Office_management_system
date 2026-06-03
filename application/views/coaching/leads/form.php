<?php $this->load->view('partials/header', ['title' => 'Lead']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="card shadow-soft"><div class="card-body"><?php echo form_open($row ? 'coaching-leads/edit/'.$row->id : 'coaching-leads/create'); ?>
<input name="full_name" class="form-control mb-2" placeholder="Name" required value="<?php echo $row?htmlspecialchars($row->full_name):''; ?>">
<input name="email" class="form-control mb-2" placeholder="Email" value="<?php echo $row?htmlspecialchars($row->email):''; ?>">
<input name="phone" class="form-control mb-2" placeholder="Phone" value="<?php echo $row?htmlspecialchars($row->phone):''; ?>">
<input name="source" class="form-control mb-2" placeholder="Source" value="<?php echo $row?htmlspecialchars($row->source):''; ?>">
<?php if ($row): ?><select name="status" class="form-select mb-2"><?php foreach (['new','contacted','qualified','converted','lost'] as $st): ?><option <?php echo $row->status===$st?'selected':''; ?>><?php echo $st; ?></option><?php endforeach; ?></select><?php endif; ?>
<textarea name="notes" class="form-control mb-2" rows="3"><?php echo $row?htmlspecialchars($row->notes):''; ?></textarea>
<button class="btn btn-primary">Save</button><?php echo form_close(); ?></div></div>
<?php $this->load->view('partials/footer'); ?>
