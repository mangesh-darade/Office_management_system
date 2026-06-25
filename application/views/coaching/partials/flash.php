<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success py-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger py-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
