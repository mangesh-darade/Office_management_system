<?php $this->load->view('partials/header', ['title' => 'WhatsApp CRM']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="alert alert-info small">Meta WhatsApp webhook URL: <code><?php echo site_url('whatsapp/webhook'); ?></code></div>
<div class="row g-3">
<div class="col-md-6">
  <div class="card shadow-soft"><div class="card-header">Enquiries</div>
  <div class="card-body"><?php echo form_open('coaching-whatsapp-crm/save-enquiry'); ?>
    <input name="phone" class="form-control mb-2" placeholder="Phone" required>
    <input name="contact_name" class="form-control mb-2" placeholder="Name">
    <textarea name="message" class="form-control mb-2" rows="2"></textarea>
    <button class="btn btn-sm btn-primary">Add enquiry</button>
  <?php echo form_close(); ?>
  <hr><table class="table table-sm"><thead><tr><th>Phone</th><th>Status</th><th>Message</th></tr></thead><tbody>
  <?php foreach ($enquiries as $e): ?><tr><td><?php echo esc_view($e->phone); ?></td><td><?php echo esc_view($e->status); ?></td><td class="small"><?php echo esc_view(mb_substr($e->message,0,80)); ?></td></tr><?php endforeach; ?>
  </tbody></table></div></div>
</div>
<div class="col-md-6">
  <div class="card shadow-soft"><div class="card-header">Bulk broadcast</div>
  <div class="card-body"><?php echo form_open('coaching-whatsapp-crm/broadcast'); ?>
    <select name="template_name" class="form-select mb-2">
      <option value="">Text (24h window only)</option>
      <?php if (!empty($templates)): foreach ($templates as $tpl): ?>
        <option value="<?php echo esc_view($tpl['name']); ?>"><?php echo esc_view($tpl['name']); ?></option>
      <?php endforeach; endif; ?>
    </select>
    <textarea name="message" class="form-control mb-2" rows="3" placeholder="Optional text if not using a template"></textarea>
    <button class="btn btn-warning btn-sm">Send to client phones</button>
  <?php echo form_close(); ?></div></div></div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
