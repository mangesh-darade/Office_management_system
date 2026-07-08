<?php $this->load->view('partials/header', array('title' => 'Business Assessment')); ?>
<div class="container-fluid px-0">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3 pt-3 pb-2">
    <div>
      <h1 class="h4 mb-0 fw-bold">Business Assessment</h1>
      <p class="text-muted small mb-0">Business ROI assessment (client-side tool — no server save)</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url('assets/eba/eba-platform.html'); ?>" target="_blank" rel="noopener">
      <i class="bi bi-box-arrow-up-right me-1"></i>Open full screen
    </a>
  </div>
  <div class="eba-platform-frame-wrap border-top bg-white">
    <iframe
      title="Business Assessment"
      src="<?php echo base_url('assets/eba/eba-platform.html'); ?>"
      class="eba-platform-frame"
      loading="eager"
    ></iframe>
  </div>
</div>
<style>
.eba-platform-frame-wrap {
  height: calc(100vh - 140px);
  min-height: 640px;
}
.eba-platform-frame {
  display: block;
  width: 100%;
  height: 100%;
  border: 0;
  background: #f4f6f9;
}
</style>
<?php $this->load->view('partials/footer'); ?>
