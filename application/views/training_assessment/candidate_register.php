<?php $this->load->view('partials/header', array('title' => 'Start assessment', 'with_sidebar' => false)); ?>
<div class="container py-5" style="max-width:480px">
  <h1 class="h4 mb-3">Before you begin</h1>
  <p class="text-muted small">Enter your details to continue.</p>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php echo form_open('training-assessment/candidate-profile'); ?>
  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
  <input type="hidden" name="access_token" value="<?php echo esc_view($token); ?>">
  <div class="mb-3">
    <label class="form-label">Full name</label>
    <input type="text" name="candidate_name" class="form-control" required maxlength="190">
  </div>
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="candidate_email" class="form-control" required maxlength="190">
  </div>
  <button type="submit" class="btn btn-primary w-100">Continue</button>
  <?php echo form_close(); ?>
</div>
<?php $this->load->view('partials/footer'); ?>
