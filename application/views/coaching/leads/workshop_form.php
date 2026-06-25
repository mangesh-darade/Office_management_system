<?php $this->load->view('partials/header', ['title' => 'Workshop']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="card shadow-soft"><div class="card-body"><?php echo form_open($row ? 'coaching-leads/workshop-form/'.$row->id : 'coaching-leads/workshop-form'); ?>
<input name="title" class="form-control mb-2" required placeholder="Title" value="<?php echo $row?esc_view($row->title):''; ?>">
<textarea name="description" class="form-control mb-2" rows="3"><?php echo $row?esc_view($row->description):''; ?></textarea>
<input type="datetime-local" name="workshop_date" class="form-control mb-2" value="<?php echo $row && $row->workshop_date ? date('Y-m-d\TH:i', strtotime($row->workshop_date)) : ''; ?>">
<input name="location" class="form-control mb-2" placeholder="Location" value="<?php echo $row?esc_view($row->location):''; ?>">
<select name="status" class="form-select mb-2"><option value="draft">Draft</option><option value="published" <?php echo ($row && $row->status==='published')?'selected':''; ?>>Published</option></select>
<button class="btn btn-primary">Save</button><?php echo form_close(); ?></div></div>
<?php $this->load->view('partials/footer'); ?>
