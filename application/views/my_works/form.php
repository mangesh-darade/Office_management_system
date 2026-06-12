<?php

  $isEdit = ($action === 'edit' && !empty($item) && !empty($item->id));

  $title = $isEdit ? 'Edit Work Item' : 'New Work Item';

  $this->load->view('partials/header', array('title' => $title, 'extra_css' => array('assets/css/my-works.css')));

  $status = ($item && isset($item->status)) ? (string) $item->status : 'new';

  $this->load->helper('my_works_status');
  $statusRecords = my_works_status_records();
  $statusLabels = my_works_status_labels();

  $typeLabels = my_works_type_labels();

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

  $curType = (string) $field('work_type', '');

  $curClient = (int) $field('client_id', 0);

  $curProject = (int) $field('project_id', 0);

  $curFor = $isEdit ? (int) $item->created_for : (int) $field('created_for', $uid);

?>

<div class="container-fluid py-3 mw-form-page">

  <nav aria-label="breadcrumb" class="small mb-2 d-none d-md-block mw-breadcrumb">

    <ol class="breadcrumb mb-0">

      <li class="breadcrumb-item"><a href="<?php echo site_url('my-works'); ?>">My Works</a></li>

      <li class="breadcrumb-item active" aria-current="page"><?php echo $isEdit ? 'Edit' : 'Create'; ?></li>

    </ol>

  </nav>



  <div class="mw-form-page-head d-flex justify-content-between align-items-start gap-2 mb-3">

    <div class="min-w-0">

      <h1 class="h4 mb-0 fw-bold text-dark mw-form-page-title"><?php echo htmlspecialchars($title); ?></h1>

      <p class="text-muted small mb-0 d-none d-sm-block"><?php echo $isEdit ? 'Update details, assignment, or priority' : 'Capture what needs to be done and who owns it'; ?></p>

    </div>

    <div class="d-flex flex-shrink-0 gap-2 mw-form-page-actions">
      <?php if (!$isEdit && function_exists('my_works_can_add') && my_works_can_add()): ?>
        <a class="btn btn-sm mw-btn-quick-add d-none d-sm-inline-flex" href="<?php echo htmlspecialchars(site_url('my-works/quick-add') . '?redirect=' . rawurlencode('my-works/create'), ENT_QUOTES, 'UTF-8'); ?>" title="Quick add with rich text and attachments">
          <i class="bi bi-lightning-charge-fill me-1"></i>Quick add
        </a>
      <?php endif; ?>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo $isEdit ? site_url('my-works/' . (int) $item->id) : site_url('my-works'); ?>" title="Back">
        <i class="bi bi-arrow-left"></i><span class="d-none d-sm-inline ms-1">Back</span>
      </a>
    </div>

  </div>



  <?php if ($this->session->flashdata('error')): ?>

    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>

  <?php endif; ?>



  <?php if (!empty($scope) && !empty($scope['message'])): ?>

    <?php $this->load->view('my_works/_scope_banner', array('scope' => $scope)); ?>

  <?php endif; ?>



  <div class="card shadow-sm border-0 mw-form-card">

    <div class="card-body p-3 p-md-4">

      <form method="post" enctype="multipart/form-data" action="<?php echo $isEdit ? site_url('my-works/' . (int) $item->id . '/edit') : site_url('my-works/create'); ?>" id="mw-work-form" class="mw-upload-form" data-tinymce-id="mw-form-details">

        <?php $this->load->view('my_works/_csrf'); ?>



        <div class="mw-form-section">

          <div class="mw-form-section-head">

            <span class="icon"><i class="bi bi-person-check"></i></span>

            <div>

              <h2>Assignment &amp; task</h2>

              <p>Who owns this item, what it is, and how it is classified</p>

            </div>

          </div>

          <div class="row g-3 mw-form-assignment-row">

            <div class="col-12 col-lg-4 mw-form-field">

              <label class="form-label fw-semibold" for="mw-form-title">Task title <span class="text-danger">*</span></label>

              <input type="text" name="title" id="mw-form-title" class="form-control" required maxlength="255" value="<?php echo htmlspecialchars((string) $field('title')); ?>" placeholder="e.g. Follow up with client on proposal" autofocus>

            </div>

            <div class="col-12 col-lg-4 mw-form-field">

              <label class="form-label fw-semibold" for="mw-form-created-for">Assigned to <span class="text-danger">*</span></label>

              <select name="created_for" id="mw-form-created-for" class="form-select" required>

                <?php foreach ((array) $users as $u): ?>

                  <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $u->id === $curFor ? 'selected' : ''; ?>>

                    <?php echo htmlspecialchars(my_works_user_label($u->name, $u->email, $u->id)); ?>

                  </option>

                <?php endforeach; ?>

              </select>

              <div class="form-text">The person responsible for completing this item.</div>

            </div>

            <div class="col-12 col-lg-4 mw-form-field">

              <label class="form-label fw-semibold" for="work_type">Type</label>

              <?php $this->load->view('partials/module_type_select', array(

                'field_name' => 'work_type',

                'options' => $typeLabels,

                'current' => $curType,

                'placeholder' => '— Select type —',

              )); ?>

            </div>

          </div>

        </div>



        <div class="mw-form-section mw-schedule-priority-section">

          <div class="mw-form-section-head">

            <span class="icon"><i class="bi bi-calendar-check"></i></span>

            <div>

              <h2>Schedule &amp; priority</h2>

              <p>Due date, priority, and current status</p>

            </div>

          </div>

          <div class="row g-3 align-items-end">

            <div class="col-12 col-md-4">

              <label class="form-label fw-semibold">Due date <span class="text-danger">*</span></label>

              <input type="date" name="due_date" class="form-control" required value="<?php echo htmlspecialchars((string) $field('due_date')); ?>">

            </div>

            <div class="col-12 col-md-4">

              <label class="form-label fw-semibold d-block">Priority</label>

              <div class="mw-flag-pills d-flex flex-wrap gap-2">

                <input type="checkbox" class="btn-check" name="is_urgent" value="1" id="isUrgent" <?php echo (int) $field('is_urgent', 0) === 1 ? 'checked' : ''; ?>>

                <label class="btn btn-outline-danger btn-sm" for="isUrgent"><i class="bi bi-exclamation-triangle me-1"></i>Urgent</label>

                <input type="checkbox" class="btn-check" name="is_important" value="1" id="isImportant" <?php echo (int) $field('is_important', 0) === 1 ? 'checked' : ''; ?>>

                <label class="btn btn-outline-warning btn-sm" for="isImportant"><i class="bi bi-star me-1"></i>Important</label>

              </div>

            </div>

            <div class="col-12 col-md-4">

              <label class="form-label fw-semibold">Status</label>

              <select name="status" class="form-select">

                <?php if (!empty($statusRecords)): ?>
                  <?php foreach ($statusRecords as $st): ?>
                    <option value="<?php echo htmlspecialchars((string) $st->code, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $status === (string) $st->code ? 'selected' : ''; ?>><?php echo htmlspecialchars((string) $st->name, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <?php foreach ($statusLabels as $k => $lbl): ?>
                    <option value="<?php echo $k; ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>

              </select>

            </div>

          </div>

        </div>



        <div class="mw-form-section">

          <div class="mw-form-section-head">

            <span class="icon"><i class="bi bi-card-text"></i></span>

            <div>

              <h2>Description</h2>

              <p>Notes, steps, background, or context for the assignee</p>

            </div>

          </div>

          <div class="row g-3">

            <div class="col-12 mw-form-description-block">

              <label class="form-label fw-semibold">Description</label>

              <textarea id="mw-form-details" name="details" class="form-control" rows="8" placeholder="Notes, steps, background, or context for the assignee…"><?php echo htmlspecialchars((string) $field('details')); ?></textarea>

              <div class="form-text">Use the toolbar for bold, italic, underline, lists, links, and more.</div>

            </div>

          </div>

        </div>



        <div class="mw-form-section">

          <div class="mw-form-section-head">

            <span class="icon"><i class="bi bi-diagram-3"></i></span>

            <div>

              <h2>Context &amp; attachments</h2>

              <p>Client, project, tags, reference link, and files</p>

            </div>

          </div>

          <div class="row g-3">

            <div class="col-12 col-md-6">

              <label class="form-label fw-semibold">Client</label>

              <select name="client_id" id="mw-client-select" class="form-select" <?php echo empty($clients) ? 'disabled' : ''; ?>>

                <option value="0">— Select client —</option>

                <?php foreach ($clients as $c): ?>

                  <option value="<?php echo (int) $c->id; ?>" <?php echo $curClient === (int) $c->id ? 'selected' : ''; ?>>

                    <?php echo htmlspecialchars($c->company_name); ?>

                  </option>

                <?php endforeach; ?>

              </select>

              <?php if (empty($clients)): ?><div class="form-text">No clients available.</div><?php endif; ?>

            </div>

            <div class="col-12 col-md-6">

              <label class="form-label fw-semibold">Project</label>

              <select name="project_id" id="mw-project-select" class="form-select" <?php echo empty($projects) ? 'disabled' : ''; ?>>

                <option value="0">— Select project —</option>

                <?php foreach ($projects as $p): ?>

                  <option value="<?php echo (int) $p->id; ?>"

                          data-client-id="<?php echo ($projects_have_client && isset($p->client_id)) ? (int) $p->client_id : 0; ?>"

                          <?php echo $curProject === (int) $p->id ? 'selected' : ''; ?>>

                    <?php echo htmlspecialchars($p->name ? $p->name : ('Project #' . (int) $p->id)); ?>

                  </option>

                <?php endforeach; ?>

              </select>

              <?php if ($projects_have_client && !empty($projects)): ?>

                <div class="form-text">Filters by client</div>

              <?php endif; ?>

            </div>

            <div class="col-12 col-md-6">

              <label class="form-label fw-semibold">Tags</label>

              <input type="text" name="tag" class="form-control" maxlength="255" list="mw-form-tags" value="<?php echo htmlspecialchars((string) $field('tag')); ?>" placeholder="follow-up, demo, urgent">

              <datalist id="mw-form-tags">

                <?php foreach ($tags as $t): ?>

                  <option value="<?php echo htmlspecialchars($t); ?>">

                <?php endforeach; ?>

              </datalist>

              <div class="form-text">Comma-separated</div>

            </div>

            <div class="col-12 col-md-6">

              <label class="form-label fw-semibold">Reference URL</label>

              <div class="input-group">

                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>

                <input type="text" name="url" class="form-control" value="<?php echo htmlspecialchars((string) $field('url')); ?>" placeholder="https://example.com">

              </div>

            </div>

            <div class="col-12">

              <?php if ($isEdit): ?>
                <label class="form-label fw-semibold">Attachments</label>
              <?php endif; ?>

              <?php $this->load->view('my_works/_attachment_field', array('input_id' => 'mw-form-attachment')); ?>

              <?php if ($isEdit && !empty($attachments)): ?>
                <?php $this->load->view('my_works/_attachments_existing', array(
                  'work_id' => (int) $item->id,
                  'attachments' => $attachments,
                )); ?>
              <?php endif; ?>

            </div>

            <?php if ($isEdit): ?>

            <div class="col-12">

              <label class="form-label fw-semibold">Closing comment</label>

              <textarea name="closing_comment" class="form-control" rows="2" placeholder="Wrap-up notes when closing or completing this item…"><?php echo htmlspecialchars((string) $field('closing_comment')); ?></textarea>

            </div>

            <?php endif; ?>

          </div>

        </div>



        <div class="mw-form-actions d-flex flex-column flex-sm-row gap-2">

          <button type="submit" class="btn btn-primary px-4">

            <i class="bi bi-check-lg me-1"></i><?php echo $isEdit ? 'Save changes' : 'Create work item'; ?>

          </button>

          <a class="btn btn-outline-secondary" href="<?php echo $isEdit ? site_url('my-works/' . (int) $item->id) : site_url('my-works'); ?>">Cancel</a>

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

  if (!clientSelect || !projectSelect) return;



  var allOptions = [];

  for (var i = 0; i < projectSelect.options.length; i++) {

    var opt = projectSelect.options[i];

    allOptions.push({

      value: opt.value,

      text: opt.textContent,

      clientId: opt.getAttribute('data-client-id') || '0',

      selected: opt.selected

    });

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

      if (row.value === '0') continue;

      if (clientId === '0' || row.clientId === '0' || row.clientId === clientId) {

        var o = document.createElement('option');

        o.value = row.value;

        o.textContent = row.text;

        o.setAttribute('data-client-id', row.clientId);

        if (row.value === current) {

          o.selected = true;

          hasMatch = true;

        }

        projectSelect.appendChild(o);

      }

    }

    if (!hasMatch) {

      projectSelect.value = '0';

    }

  }



  <?php if ($projects_have_client): ?>

  clientSelect.addEventListener('change', filterProjects);

  if (clientSelect.value && clientSelect.value !== '0') {

    filterProjects();

  }

  <?php endif; ?>

})();

</script>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
  if (!document.getElementById('mw-form-details')) {
    return;
  }
  tinymce.init({
    selector: '#mw-form-details',
    menubar: false,
    statusbar: true,
    plugins: 'lists link autolink code wordcount',
    toolbar: 'undo redo | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | removeformat | code',
    branding: false,
    height: 320,
    width: '100%',
    convert_urls: false,
    default_link_target: '_blank',
    content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }'
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

