<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/header', array(
  'title' => $row->name . ' — External training',
  'with_sidebar' => true,
  'meta_robots' => 'noindex,nofollow,noarchive',
));
$stage_h = 'min(78vh, calc(100vh - 200px))';
?>
<div class="container-fluid py-2 external-training-watch">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
    <div>
      <a href="<?php echo site_url('external-training'); ?>" class="btn btn-sm btn-outline-secondary">&larr; Back to list</a>
    </div>
    <h1 class="h5 mb-0 text-truncate flex-grow-1 text-md-end" style="max-width: 100%;"><?php echo esc_view($row->name); ?></h1>
  </div>

  <?php if (!empty($player) && $player === 'video'): ?>
    <div class="alert alert-success py-2 small mb-2">
      This file plays in the <strong>built-in player</strong>. Right-click copy and the default “save video” control are limited here.
      Determined users can still find the network address in browser tools — use signed or short-lived URLs on your server if you need stronger protection.
    </div>
  <?php endif; ?>

  <div id="et-watch-stage" class="et-watch-stage border rounded overflow-hidden bg-dark position-relative"
       style="height: <?php echo $stage_h; ?>; min-height: 360px; -webkit-touch-callout: none;">
    <?php if (!empty($player) && $player === 'video' && $embed_url !== ''): ?>
      <video id="et-watch-video"
             class="position-absolute top-0 start-0 w-100 h-100"
             style="object-fit: contain;"
             controls
             controlslist="nodownload noplaybackrate"
             disablepictureinpicture
             playsinline
             preload="metadata"
             src="<?php echo esc_view($embed_url); ?>"
             title="<?php echo esc_view($row->name); ?>">
        <p class="text-white p-3">Your browser cannot play this video format inline.</p>
      </video>
    <?php elseif (!empty($player) && $player === 'iframe' && $embed_url !== ''): ?>
      <iframe id="et-watch-iframe"
        class="position-absolute top-0 start-0 w-100 h-100"
        style="border: 0;"
        src="<?php echo esc_view($embed_url); ?>"
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen; web-share"
        allowfullscreen
        referrerpolicy="strict-origin-when-cross-origin"
        title="<?php echo esc_view($row->name); ?>"></iframe>
    <?php else: ?>
      <div class="et-embed-root position-absolute top-0 start-0 w-100 h-100 overflow-auto">
        <?php echo sanitize_embed_code($row->embed_code); ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<style>
.external-training-watch .et-embed-root iframe,
.external-training-watch .et-embed-root embed,
.external-training-watch .et-embed-root object {
  width: 100% !important;
  height: 100% !important;
  min-height: 360px;
  border: 0;
  display: block;
}
.external-training-watch video {
  background: #000;
}
</style>
<script>
(function() {
  var v = document.getElementById('et-watch-video');
  if (!v) return;
  v.addEventListener('contextmenu', function(e) { e.preventDefault(); });
  v.addEventListener('dragstart', function(e) { e.preventDefault(); });
})();
</script>
<?php $this->load->view('partials/footer'); ?>
