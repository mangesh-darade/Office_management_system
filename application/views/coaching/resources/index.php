<?php $this->load->view('partials/header', ['title' => 'Resources']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="card shadow-soft mb-3"><div class="card-body"><?php echo form_open_multipart('coaching-resources/save'); ?>
<input name="title" class="form-control mb-2" placeholder="Title" required>
<select name="coaching_client_id" class="form-select mb-2"><option value="">All clients</option><?php foreach ($clients as $c): ?><option value="<?php echo (int)$c->id; ?>"><?php echo esc_view($c->full_name); ?></option><?php endforeach; ?></select>
<input name="external_url" class="form-control mb-2" placeholder="URL (optional)">
<input type="file" name="file" class="form-control mb-2">
<button class="btn btn-primary btn-sm">Upload</button><?php echo form_close(); ?></div></div>
<table class="table table-sm card shadow-soft"><tbody><?php foreach ($rows as $r): ?><tr><td><?php echo esc_view($r->title); ?></td><td><?php echo $r->coaching_client_id ? 'Client #'.$r->coaching_client_id : 'Shared'; ?></td></tr><?php endforeach; ?></tbody></table>
<?php $this->load->view('partials/footer'); ?>
