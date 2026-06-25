<?php
$this->load->view('partials/header', [
    'title'    => 'User Guide',
    'extra_css'=> ['assets/css/guide.css'],
]);
?>

<div class="guide-page">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
      <h1 class="h3 mb-2"><i class="bi bi-book me-2 text-primary"></i>User Guide</h1>
      <p class="text-muted mb-0">Simple help with screenshots for modules you can access. Pick a topic below.</p>
    </div>
    <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
  </div>

  <?php if (empty($modules)): ?>
  <div class="alert alert-info">
    No guide topics match your current permissions. Ask an administrator to assign module access in Permission Manager.
  </div>
  <?php else: ?>
  <div class="row g-3">
    <?php foreach ($modules as $mod): ?>
    <div class="col-sm-6 col-lg-4">
      <a href="<?php echo site_url('guide/' . $mod['slug']); ?>" class="card guide-module-card h-100 text-decoration-none shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3 mb-2">
            <span class="guide-module-icon rounded-circle d-inline-flex align-items-center justify-content-center">
              <i class="bi <?php echo esc_view($mod['icon']); ?>"></i>
            </span>
            <div>
              <div class="text-muted small">Module <?php echo esc_view($mod['id']); ?></div>
              <h2 class="h6 mb-0 text-dark"><?php echo esc_view($mod['title']); ?></h2>
            </div>
          </div>
          <p class="small text-muted mb-0">Step-by-step instructions with screenshots.</p>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php $this->load->view('partials/footer'); ?>
