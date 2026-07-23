<?php
$this->load->view('partials/header', array('title' => 'Quick Add — My Works', 'extra_css' => array('assets/css/my-works.css')));
$uid = (int) $this->session->userdata('user_id');
$field = function ($key, $default = '') use ($item) {
    if ($item && isset($item->$key) && $item->$key !== null && $item->$key !== '') {
        return $item->$key;
    }
    return $default;
};
$curFor = (int) $field('created_for', $uid);
$curForIds = array();
$rawCurFor = $field('created_for', $uid);
if (is_array($rawCurFor)) {
    $curForIds = array_map('intval', $rawCurFor);
} elseif ((int) $rawCurFor > 0) {
    $curForIds = array((int) $rawCurFor);
} else {
    $curForIds = array($uid);
}
$redirect_path = isset($redirect) ? trim((string) $redirect) : 'my-works';
if ($redirect_path === '' || strpos($redirect_path, '://') !== false) {
    $redirect_path = 'my-works';
}
$back_url = site_url($redirect_path);
?>
<div class="oms-form-compact">
<div class="container-fluid py-2 mw-page mw-quick-add-page">
  <nav aria-label="breadcrumb" class="small mb-2 d-none d-md-block">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('my-works'); ?>">My Works</a></li>
      <li class="breadcrumb-item active" aria-current="page">Quick add</li>
    </ol>
  </nav>

  <div class="mw-quick-add-page-head mw-page-head-with-back">
    <div class="d-flex align-items-center gap-2">
      <?php $this->load->view('my_works/_back_btn', array(
        'back_url' => $back_url,
        'back_title' => 'Back to Second Brain',
      )); ?>
      <h1 class="h4 mb-0 fw-bold text-dark mw-quick-add-title">
        <span class="mw-toolbar-icon"><i class="bi bi-lightning-charge-fill"></i></span>
        Quick add work item
      </h1>
    </div>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
      <?php echo esc_view($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0 mw-quick-add-card">
    <div class="card-body p-3">
      <form method="post" enctype="multipart/form-data" action="<?php echo site_url('my-works/quick-add'); ?>" id="mw-quick-add-form" class="mw-upload-form" data-tinymce-id="mw-qa-details">
        <?php $this->load->view('my_works/_csrf'); ?>
        <input type="hidden" name="redirect" value="<?php echo esc_view($redirect_path); ?>">

        <div class="row g-2 oms-form-grid">
          <div class="col-12 col-lg-8">
            <div class="mw-form-section mw-quick-add-section">
              <div class="mw-form-section-head d-none d-lg-flex">
                <span class="icon"><i class="bi bi-card-text"></i></span>
                <div>
                  <h2>Work details</h2>
                  <p>Title and rich-text description</p>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold" for="mw-qa-title">Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-md-lg" id="mw-qa-title" name="title" required maxlength="255"
                       value="<?php echo esc_view((string) $field('title'), ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="What needs to be done?" autofocus>
              </div>

              <div class="mb-0">
                <label class="form-label fw-semibold" for="mw-qa-details">Description</label>
                <textarea id="mw-qa-details" name="details" class="form-control" rows="8" placeholder="Add notes with bold, italic, underline, lists, and links…"><?php echo esc_view((string) $field('details'), ENT_QUOTES, 'UTF-8'); ?></textarea>
                <div class="form-text d-none d-md-block">Use the toolbar for bold, italic, underline, colors, alignment, and lists.</div>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <div class="mw-form-section mw-quick-add-section">
              <div class="mw-form-section-head d-none d-lg-flex">
                <span class="icon"><i class="bi bi-person-check"></i></span>
                <div>
                  <h2>Assignment</h2>
                  <p>Who will complete this item</p>
                </div>
              </div>
              <label class="form-label fw-semibold" for="mw-qa-created-for">Assigned to <span class="text-danger">*</span></label>
              <select class="form-select oms-select2-multi" id="mw-qa-created-for" name="created_for[]" multiple style="width: 100%;" required>
                <?php foreach ((array) $users as $u): ?>
                  <option value="<?php echo (int) $u->id; ?>" <?php echo in_array((int) $u->id, $curForIds, true) ? 'selected' : ''; ?>>
                    <?php echo esc_view(my_works_user_label($u->name, $u->email, $u->id), ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text d-none d-md-block">Type to search. Click <strong>×</strong> on a tag to remove that user. First selected is primary.</div>

              <label class="form-label fw-semibold mt-3" for="mw-qa-estimate-hours">Estimate (hrs)</label>
              <input type="number" name="estimate_hours" id="mw-qa-estimate-hours" class="form-control" min="0" max="9999.99" step="0.25"
                     value="<?php
                       $est_val = $field('estimate_hours', '');
                       if ($est_val !== '' && $est_val !== null && function_exists('estimate_hours_display')) {
                           echo esc_view(estimate_hours_display($est_val), ENT_QUOTES, 'UTF-8');
                       } elseif ($est_val !== '' && $est_val !== null) {
                           echo esc_view((string) $est_val, ENT_QUOTES, 'UTF-8');
                       }
                     ?>"
                     placeholder="e.g. 2.5">
              <div class="form-text">Optional planned hours (0.25 steps).</div>
            </div>

            <div class="mw-form-section mw-quick-add-section">
              <div class="mw-form-section-head d-none d-lg-flex">
                <span class="icon"><i class="bi bi-paperclip"></i></span>
                <div>
                  <h2>Attachments</h2>
                  <p>Add one or more files — documents, images, video, and more</p>
                </div>
              </div>
              <label class="form-label fw-semibold d-lg-none" for="mw-qa-attachment">Attachments</label>
              <?php $this->load->view('my_works/_attachment_field', array(
                'input_id' => 'mw-qa-attachment',
              )); ?>
            </div>
          </div>
        </div>

        <div class="mw-form-actions mw-quick-add-actions d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 mt-2">
          <button type="submit" class="btn btn-primary px-3 px-sm-4 mw-quick-add-submit">
            <i class="bi bi-check-lg me-1"></i>Add work item
          </button>
          <div class="mw-quick-add-secondary-actions d-flex flex-row gap-2 ms-sm-auto">
            <a class="btn btn-outline-secondary mw-quick-add-cancel" href="<?php echo esc_view($back_url); ?>">Cancel</a>
            <a class="btn btn-outline-secondary mw-quick-add-full-form" href="<?php echo site_url('my-works/create'); ?>">
              <i class="bi bi-sliders me-1"></i><span class="d-none d-lg-inline">Full form (client, project, tags…)</span><span class="d-lg-none">Full form</span>
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
  function initQAEditor() {
    if (typeof tinymce === 'undefined') {
      setTimeout(initQAEditor, 50);
      return;
    }
    var isMobile = window.matchMedia('(max-width: 767.98px)').matches;
    tinymce.init({
      selector: '#mw-qa-details',
      menubar: false,
      statusbar: !isMobile,
      plugins: 'lists link autolink code wordcount',
      toolbar: isMobile
        ? 'bold italic underline | bullist numlist | link | removeformat'
        : 'undo redo | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | removeformat | code',
      toolbar_mode: isMobile ? 'scrolling' : 'wrap',
      branding: false,
      height: isMobile ? 220 : 320,
      width: '100%',
      resize: !isMobile,
      convert_urls: false,
      default_link_target: '_blank',
      content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
      setup: function (editor) {
        editor.on('init', function () {
          if (!isMobile) {
            editor.focus();
          }
        });
      }
    });
  }
  initQAEditor();
})();
</script>
<script src="<?php echo base_url('assets/js/my-works-attachment.js'); ?>"></script>
</div>
<?php $this->load->view('partials/footer'); ?>
<?php
  $this->load->view('partials/oms_select2_multi', array(
    'oms_select2_selectors' => array('#mw-qa-created-for'),
    'oms_select2_placeholder' => 'Select assignee(s)…',
  ));
?>
