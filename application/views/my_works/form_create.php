<?php
  $this->load->view('partials/header', array('title' => 'New Work Item', 'extra_css' => array('assets/css/my-works.css')));

  $tags = isset($tags) ? $tags : array();
  $clients = isset($clients) ? $clients : array();
  $projects = isset($projects) ? $projects : array();
  $projects_have_client = !empty($projects_have_client);
  $uid = (int) $this->session->userdata('user_id');

  $field = function ($key, $default = '') use ($item) {
    if ($item && isset($item->$key) && $item->$key !== null && $item->$key !== '') {
      return $item->$key;
    }
    return $default;
  };

  $curClient = (int) $field('client_id', 0);
  $curProject = (int) $field('project_id', 0);
  $curFor = (int) $field('created_for', $uid);
?>

<div class="container-fluid py-3 mw-form-page mw-form-create-page">

  <nav aria-label="breadcrumb" class="small mb-2 d-none d-md-block mw-breadcrumb">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('my-works'); ?>">My Works</a></li>
      <li class="breadcrumb-item active" aria-current="page">Create</li>
    </ol>
  </nav>

  <div class="mw-form-page-head d-flex justify-content-between align-items-start gap-2 mb-3">
    <div class="min-w-0">
      <h1 class="h4 mb-0 fw-bold text-dark mw-form-page-title">New Work Item</h1>
      <p class="text-muted small mb-0 d-none d-sm-block">Status will be set to <strong>New</strong> automatically</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm flex-shrink-0" href="<?php echo site_url('my-works'); ?>" title="Back">
      <i class="bi bi-arrow-left"></i><span class="d-none d-sm-inline ms-1">Back</span>
    </a>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2"><?php echo htmlspecialchars((string) $this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <?php if (!empty($scope) && !empty($scope['message'])): ?>
    <?php $this->load->view('my_works/_scope_banner', array('scope' => $scope)); ?>
  <?php endif; ?>

  <div class="card shadow-sm border-0 mw-form-card">
    <div class="card-body p-3 p-md-4">
      <form method="post" enctype="multipart/form-data" action="<?php echo site_url('my-works/create'); ?>" id="mw-work-form" class="mw-upload-form" data-tinymce-id="mw-form-details">
        <?php $this->load->view('my_works/_csrf'); ?>
        <input type="hidden" name="status" value="new">

        <div class="mw-form-section mw-form-create-section">
          <div class="mw-form-section-head">
            <span class="icon"><i class="bi bi-info-circle"></i></span>
            <div>
              <h2>Basic info</h2>
            </div>
          </div>

          <div class="mw-create-grid">
            <div class="mw-create-row mw-create-row-3">
              <div class="mw-create-field">
                <label class="form-label mw-create-label" for="mw-form-title">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="mw-form-title" class="form-control mw-create-control" required maxlength="255" value="<?php echo htmlspecialchars((string) $field('title'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="e.g. Follow up with client on proposal" autofocus>
              </div>
              <div class="mw-create-field">
                <label class="form-label mw-create-label" for="mw-form-created-for">Assigned to <span class="text-danger">*</span></label>
                <select name="created_for" id="mw-form-created-for" class="form-select mw-create-control" required>
                  <?php foreach ((array) $users as $u): ?>
                    <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $u->id === $curFor ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars(my_works_user_label($u->name, $u->email, $u->id), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mw-create-field mw-form-priority-field">
                <label class="form-label mw-create-label">Priority</label>
                <div class="mw-flag-pills d-flex gap-2">
                  <input type="checkbox" class="btn-check" name="is_urgent" value="1" id="isUrgent" <?php echo (int) $field('is_urgent', 0) === 1 ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-danger btn-sm mw-create-priority-btn" for="isUrgent" title="Urgent"><i class="bi bi-exclamation-triangle"></i><span class="mw-create-priority-text">Urgent</span></label>
                  <input type="checkbox" class="btn-check" name="is_important" value="1" id="isImportant" <?php echo (int) $field('is_important', 0) === 1 ? 'checked' : ''; ?>>
                  <label class="btn btn-outline-warning btn-sm mw-create-priority-btn" for="isImportant" title="Important"><i class="bi bi-star"></i><span class="mw-create-priority-text">Important</span></label>
                </div>
              </div>
            </div>

            <div class="mw-create-row mw-create-row-3">
              <div class="mw-create-field">
                <label class="form-label mw-create-label" for="mw-form-due-date">Due date <span class="text-danger">*</span></label>
                <input type="date" name="due_date" id="mw-form-due-date" class="form-control mw-create-control" required value="<?php echo htmlspecialchars((string) $field('due_date'), ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="mw-create-field">
                <label class="form-label mw-create-label" for="mw-client-select">Client</label>
                <select name="client_id" id="mw-client-select" class="form-select mw-create-control" <?php echo empty($clients) ? 'disabled' : ''; ?>>
                  <option value="0">— Select client —</option>
                  <?php foreach ($clients as $c): ?>
                    <option value="<?php echo (int) $c->id; ?>" <?php echo $curClient === (int) $c->id ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($c->company_name, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mw-create-field">
                <label class="form-label mw-create-label" for="mw-project-select">Project</label>
                <select name="project_id" id="mw-project-select" class="form-select mw-create-control" <?php echo empty($projects) ? 'disabled' : ''; ?>>
                  <option value="0">— Select project —</option>
                  <?php foreach ($projects as $p): ?>
                    <option value="<?php echo (int) $p->id; ?>"
                            data-client-id="<?php echo ($projects_have_client && isset($p->client_id)) ? (int) $p->client_id : 0; ?>"
                            <?php echo $curProject === (int) $p->id ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($p->name ? $p->name : ('Project #' . (int) $p->id), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="mw-create-row mw-create-row-3">
              <div class="mw-create-field">
                <label class="form-label mw-create-label" for="mw-form-tag">Tag</label>
                <input type="text" name="tag" id="mw-form-tag" class="form-control mw-create-control" maxlength="255" list="mw-form-tags" value="<?php echo htmlspecialchars((string) $field('tag'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="follow-up, demo">
                <datalist id="mw-form-tags">
                  <?php foreach ($tags as $t): ?>
                    <option value="<?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php endforeach; ?>
                </datalist>
              </div>
              <div class="mw-create-field">
                <label class="form-label mw-create-label" for="mw-form-url">Ref URL</label>
                <div class="input-group mw-ref-url-group">
                  <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                  <input type="text" name="url" id="mw-form-url" class="form-control mw-create-control" value="<?php echo htmlspecialchars((string) $field('url'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://example.com">
                </div>
              </div>
              <div class="mw-create-field">
                <label class="form-label mw-create-label" for="mw-form-attachment">Attach file</label>
                <?php $this->load->view('my_works/_attachment_field', array(
                  'input_id' => 'mw-form-attachment',
                  'show_help' => false,
                  'inline_row' => true,
                )); ?>
              </div>
            </div>

            <div class="mw-create-field mw-create-field--full">
              <label class="form-label mw-create-label" for="mw-form-details">Description</label>
              <textarea id="mw-form-details" name="details" class="form-control" rows="6" placeholder="Notes, steps, background, or context…"><?php echo htmlspecialchars((string) $field('details'), ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
          </div>
        </div>

        <div class="mw-form-actions mw-form-create-actions d-flex flex-column flex-sm-row gap-2">
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i>Create work item
          </button>
          <a class="btn btn-outline-secondary" href="<?php echo site_url('my-works'); ?>">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php if (!empty($clients) && !empty($projects)): ?>
<script>
(function () {
  var clientSelect = document.getElementById('mw-client-select');
  var projectSelect = document.getElementById('mw-project-select');
  if (!clientSelect || !projectSelect) { return; }
  var allOptions = [];
  for (var i = 0; i < projectSelect.options.length; i++) {
    var opt = projectSelect.options[i];
    allOptions.push({ value: opt.value, text: opt.textContent, clientId: opt.getAttribute('data-client-id') || '0', selected: opt.selected });
  }
  function filterProjects() {
    var clientId = String(clientSelect.value || '0');
    var current = projectSelect.value;
    projectSelect.innerHTML = '';
    var none = document.createElement('option');
    none.value = '0';
    none.textContent = '— None —';
    projectSelect.appendChild(none);
    var hasMatch = false;
    for (var j = 0; j < allOptions.length; j++) {
      var row = allOptions[j];
      if (row.value === '0') { continue; }
      if (clientId === '0' || row.clientId === '0' || row.clientId === clientId) {
        var o = document.createElement('option');
        o.value = row.value;
        o.textContent = row.text;
        o.setAttribute('data-client-id', row.clientId);
        if (row.value === current) { o.selected = true; hasMatch = true; }
        projectSelect.appendChild(o);
      }
    }
    if (!hasMatch) { projectSelect.value = '0'; }
  }
  <?php if ($projects_have_client): ?>
  clientSelect.addEventListener('change', filterProjects);
  if (clientSelect.value && clientSelect.value !== '0') { filterProjects(); }
  <?php endif; ?>
})();
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
  if (!document.getElementById('mw-form-details')) { return; }
  tinymce.init({
    selector: '#mw-form-details',
    menubar: false,
    statusbar: true,
    plugins: 'lists link autolink code wordcount',
    toolbar: 'undo redo | bold italic underline strikethrough | bullist numlist | link | removeformat',
    branding: false,
    height: 280,
    width: '100%',
    convert_urls: false,
    default_link_target: '_blank'
  });
  var form = document.getElementById('mw-work-form');
  if (form) {
    form.addEventListener('submit', function () {
      if (window.tinymce && tinymce.get('mw-form-details')) {
        tinymce.get('mw-form-details').save();
      }
    });
  }
})();
</script>
<script src="<?php echo base_url('assets/js/my-works-attachment.js'); ?>"></script>
<?php $this->load->view('my_works/_media_preview_modal'); ?>
<?php $this->load->view('my_works/_media_preview_scripts'); ?>
<?php $this->load->view('partials/footer'); ?>
