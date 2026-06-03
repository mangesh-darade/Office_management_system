<?php
$subtitle = isset($subtitle) ? $subtitle : '';
$create_url = isset($create_url) ? $create_url : '';
$create_label = isset($create_label) ? $create_label : 'Add';
$extra_actions = isset($extra_actions) ? $extra_actions : '';
?>
<div class="container-fluid p-0">
<div class="row mb-3">
<div class="col-12">
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
<div>
<h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($title); ?></h5>
<?php if ($subtitle !== ''): ?><p class="text-muted mb-0 small"><?php echo htmlspecialchars($subtitle); ?></p><?php endif; ?>
</div>
<div class="d-flex flex-wrap gap-2">
<?php echo $extra_actions; ?>
<?php if ($create_url !== ''): ?>
<a href="<?php echo site_url($create_url); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i><?php echo htmlspecialchars($create_label); ?></a>
<?php endif; ?>
</div>
</div>
</div>
</div>
<?php $this->load->view('coaching/partials/flash'); ?>
