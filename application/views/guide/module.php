<?php
$this->load->view('partials/header', [
    'title'    => $title,
    'extra_css'=> ['assets/css/guide.css'],
]);
?>

<div class="guide-page">
  <div class="row g-4">
    <div class="col-lg-3 d-none d-lg-block">
      <nav class="guide-toc card shadow-sm sticky-top" style="top: 1rem;">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-list-ul me-2"></i>Modules</div>
        <div class="list-group list-group-flush">
          <a href="<?php echo site_url('guide'); ?>" class="list-group-item list-group-item-action small">All Modules</a>
          <?php foreach ($modules as $mod): ?>
          <a href="<?php echo site_url('guide/' . $mod['slug']); ?>" class="list-group-item list-group-item-action small <?php echo ($mod['slug'] === $module['slug']) ? 'active' : ''; ?>">
            <?php echo esc_view($mod['id'] . '. ' . $mod['title']); ?>
          </a>
          <?php endforeach; ?>
        </div>
      </nav>
    </div>
    <div class="col-lg-9">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
          <div class="text-muted small">Module <?php echo esc_view($module['id']); ?></div>
          <h1 class="h4 mb-0"><?php echo esc_view($module['title']); ?></h1>
        </div>
        <div class="d-flex gap-2">
          <a href="<?php echo site_url('guide'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-grid me-1"></i>All</a>
          <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-house me-1"></i>Home</a>
        </div>
      </div>

      <article class="guide-content card shadow-sm">
        <div class="card-body guide-body">
          <?php echo $content; ?>
        </div>
      </article>

      <?php
      $idx = array_search($module['slug'], array_column($modules, 'slug'), true);
      $prev = ($idx !== false && $idx > 0) ? $modules[$idx - 1] : null;
      $next = ($idx !== false && $idx < count($modules) - 1) ? $modules[$idx + 1] : null;
      ?>
      <?php if ($prev || $next): ?>
      <div class="d-flex justify-content-between mt-4 gap-2 flex-wrap">
        <?php if ($prev): ?>
        <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('guide/' . $prev['slug']); ?>">
          <i class="bi bi-arrow-left me-1"></i><?php echo esc_view($prev['title']); ?>
        </a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if ($next): ?>
        <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('guide/' . $next['slug']); ?>">
          <?php echo esc_view($next['title']); ?><i class="bi bi-arrow-right ms-1"></i>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
