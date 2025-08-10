<?php $this->load->view('partials/header', ['title' => 'Send Email', 'active' => 'mail']); ?>
<div class="row justify-content-center">
  <div class="col-12 col-md-8 col-lg-6">
    <?php if($this->session->flashdata('success')): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
    <?php endif; ?>
    <?php if($this->session->flashdata('error')): ?>
      <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Send Email</span>
        <a href="<?php echo site_url('mail/test'); ?>" class="btn btn-sm btn-outline-primary">Send Test</a>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('mail/send'); ?>" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">To</label>
            <input type="email" class="form-control" name="to" placeholder="recipient@example.com" value="<?php echo htmlspecialchars($this->session->userdata('email') ?: ''); ?>" required>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">CC <span class="text-muted small">(optional, comma separated)</span></label>
              <input type="text" class="form-control" name="cc" placeholder="cc1@example.com, cc2@example.com">
            </div>
            <div class="col-md-6">
              <label class="form-label">BCC <span class="text-muted small">(optional, comma separated)</span></label>
              <input type="text" class="form-control" name="bcc" placeholder="bcc1@example.com, bcc2@example.com">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Subject</label>
            <input type="text" class="form-control" name="subject" placeholder="Subject" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Message</label>
            <textarea class="form-control" name="message" rows="6" placeholder="Write your message..." required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">Attachment <span class="text-muted small">(optional)</span></label>
            <input type="file" class="form-control" name="attachment" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
          </div>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send</button>
          </div>
        </form>
      </div>
      <div class="card-footer small text-muted">
        From: <code><?php echo htmlspecialchars($this->config->item('smtp_user') ?: 'sateri.mangesh@gmail.com'); ?></code> via Gmail SMTP. Configure password with <code>SMTP_PASS</code> env or in <code>application/config/email.php</code>.
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
