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
$redirect_path = isset($redirect) ? trim((string) $redirect) : 'my-works';
if ($redirect_path === '' || strpos($redirect_path, '://') !== false) {
    $redirect_path = 'my-works';
}
$back_url = site_url($redirect_path);
?>
<div class="container-fluid py-2 mw-page mw-quick-add-page">
  <nav aria-label="breadcrumb" class="small mb-2 d-none d-md-block">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('my-works'); ?>">My Works</a></li>
      <li class="breadcrumb-item active" aria-current="page">Quick add</li>
    </ol>
  </nav>

  <div class="mw-quick-add-page-head">
    <div class="d-flex justify-content-between align-items-center gap-2">
      <h1 class="h4 mb-0 fw-bold text-dark mw-quick-add-title">
        <span class="mw-toolbar-icon"><i class="bi bi-lightning-charge-fill"></i></span>
        Quick add work item
      </h1>
      <a class="btn btn-outline-secondary btn-sm flex-shrink-0" href="<?php echo htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8'); ?>" title="Back to My Works">
        <i class="bi bi-arrow-left"></i><span class="d-none d-sm-inline ms-1">Back</span>
      </a>
    </div>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
      <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card shadow-sm border-0 mw-quick-add-card">
    <div class="card-body p-3">
      <form method="post" enctype="multipart/form-data" action="<?php echo site_url('my-works/quick-add'); ?>" id="mw-quick-add-form" class="mw-upload-form" data-tinymce-id="mw-qa-details">
        <?php $this->load->view('my_works/_csrf'); ?>
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_path, ENT_QUOTES, 'UTF-8'); ?>">

        <div class="row g-3">
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
                       value="<?php echo htmlspecialchars((string) $field('title'), ENT_QUOTES, 'UTF-8'); ?>"
                       placeholder="What needs to be done?" autofocus>
              </div>

              <div class="mb-0">
                <label class="form-label fw-semibold" for="mw-qa-details">Description</label>
                <textarea id="mw-qa-details" name="details" class="form-control" rows="8" placeholder="Add notes with bold, italic, underline, lists, and links…"><?php echo htmlspecialchars((string) $field('details'), ENT_QUOTES, 'UTF-8'); ?></textarea>
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
              <select class="form-select" id="mw-qa-created-for" name="created_for" required>
                <?php foreach ((array) $users as $u): ?>
                  <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $u->id === $curFor ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(my_works_user_label($u->name, $u->email, $u->id), ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text d-none d-md-block">You are recorded as the creator when you save.</div>
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
            <a class="btn btn-outline-secondary mw-quick-add-cancel" href="<?php echo htmlspecialchars($back_url, ENT_QUOTES, 'UTF-8'); ?>">Cancel</a>
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
})();
</script>
<script src="<?php echo base_url('assets/js/my-works-attachment.js'); ?>"></script>
<?php $this->load->view('partials/footer'); ?>
