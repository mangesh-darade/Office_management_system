<?php

  $isEdit = ($action === 'edit' && !empty($item) && !empty($item->id));

  $title = $isEdit ? 'Edit Work Item' : 'New Work Item';

  $this->load->view('partials/header', array('title' => $title, 'extra_css' => array('assets/css/my-works.css')));

  $status = ($item && isset($item->status)) ? (string) $item->status : 'new';

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

<div class="container-fluid py-3">

  <nav aria-label="breadcrumb" class="small mb-2">

    <ol class="breadcrumb mb-0">

      <li class="breadcrumb-item"><a href="<?php echo site_url('my-works'); ?>">My Works</a></li>

      <li class="breadcrumb-item active" aria-current="page"><?php echo $isEdit ? 'Edit' : 'Create'; ?></li>

    </ol>

  </nav>



  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">

    <div>

      <h1 class="h4 mb-0 fw-bold text-dark"><?php echo htmlspecialchars($title); ?></h1>

      <p class="text-muted small mb-0"><?php echo $isEdit ? 'Update details, assignment, or priority' : 'Capture what needs to be done and who owns it'; ?></p>

    </div>

    <a class="btn btn-outline-secondary btn-sm" href="<?php echo $isEdit ? site_url('my-works/' . (int) $item->id) : site_url('my-works'); ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>

  </div>



  <?php if ($this->session->flashdata('error')): ?>

    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>

  <?php endif; ?>



  <?php if (!empty($scope) && !empty($scope['message'])): ?>

    <?php $this->load->view('my_works/_scope_banner', array('scope' => $scope)); ?>

  <?php endif; ?>



  <div class="card shadow-sm border-0 mw-form-card">

    <div class="card-body p-3 p-md-4">

      <form method="post" enctype="multipart/form-data" action="<?php echo $isEdit ? site_url('my-works/' . (int) $item->id . '/edit') : site_url('my-works/create'); ?>">

        <?php $this->load->view('my_works/_csrf'); ?>



        <div class="mw-form-section">

          <div class="mw-form-section-head">

            <span class="icon"><i class="bi bi-card-text"></i></span>

            <div>

              <h2>Basic information</h2>

              <p>Title, type, and description of the work</p>

            </div>

          </div>

          <div class="row g-3">

            <div class="col-12 col-lg-8">

              <label class="form-label fw-semibold">Task title <span class="text-danger">*</span></label>

              <input type="text" name="title" class="form-control form-control-lg" required maxlength="255" value="<?php echo htmlspecialchars((string) $field('title')); ?>" placeholder="e.g. Follow up with client on proposal" autofocus>

            </div>

            <div class="col-12 col-lg-4">

              <label class="form-label fw-semibold">Type</label>

              <?php $this->load->view('partials/module_type_select', array(

                'field_name' => 'work_type',

                'options' => $typeLabels,

                'current' => $curType,

                'placeholder' => '— Select type —',

              )); ?>

            </div>

            <div class="col-12">

              <label class="form-label fw-semibold">Details</label>

              <textarea name="details" class="form-control" rows="5" placeholder="Notes, steps, background, or context for the assignee…"><?php echo htmlspecialchars((string) $field('details')); ?></textarea>

            </div>

            <div class="col-12 col-md-6">

              <label class="form-label fw-semibold">Tags</label>

              <input type="text" name="tag" class="form-control" maxlength="255" list="mw-form-tags" value="<?php echo htmlspecialchars((string) $field('tag')); ?>" placeholder="follow-up, demo, urgent">

              <datalist id="mw-form-tags">

                <?php foreach ($tags as $t): ?>

                  <option value="<?php echo htmlspecialchars($t); ?>">

                <?php endforeach; ?>

              </datalist>

              <div class="form-text">Comma-separated — helps with search and filters.</div>

            </div>

          </div>

        </div>



        <div class="mw-form-section mw-client-project-section">

          <div class="mw-form-section-head">

            <span class="icon"><i class="bi bi-building"></i></span>

            <div>

              <h2>Client, project &amp; link</h2>

              <p>Associate this work with a client or project and add a reference URL</p>

            </div>

          </div>

          <div class="row g-3">

            <div class="col-12 col-md-4">

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

            <div class="col-12 col-md-4">

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

                <div class="form-text">Projects filter by selected client.</div>

              <?php endif; ?>

            </div>

            <div class="col-12 col-md-4">

              <label class="form-label fw-semibold">URL</label>

              <div class="input-group">

                <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>

                <input type="text" name="url" class="form-control" value="<?php echo htmlspecialchars((string) $field('url')); ?>" placeholder="https://example.com">

              </div>

            </div>

          </div>

        </div>



        <div class="mw-form-section">

          <div class="mw-form-section-head">

            <span class="icon"><i class="bi bi-calendar-check"></i></span>

            <div>

              <h2>Schedule &amp; assignment</h2>

              <p>Due date, status, and who should complete this work</p>

            </div>

          </div>

          <div class="row g-3">

            <div class="col-6 col-md-3">

              <label class="form-label fw-semibold">Due date</label>

              <input type="date" name="due_date" class="form-control" value="<?php echo htmlspecialchars((string) $field('due_date')); ?>">

            </div>

            <div class="col-6 col-md-3">

              <label class="form-label fw-semibold">Status</label>

              <select name="status" class="form-select">

                <?php foreach ($statusLabels as $k => $lbl): ?>

                  <option value="<?php echo $k; ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>

                <?php endforeach; ?>

              </select>

            </div>

            <div class="col-12 col-md-6">

              <label class="form-label fw-semibold">Created for</label>

              <select name="created_for" class="form-select">

                <?php foreach ((array) $users as $u): ?>

                  <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $u->id === $curFor ? 'selected' : ''; ?>>

                    <?php echo htmlspecialchars(my_works_user_label($u->name, $u->email, $u->id)); ?>

                  </option>

                <?php endforeach; ?>

              </select>

              <div class="form-text">The person responsible for completing this item.</div>

            </div>

            <div class="col-12">

              <label class="form-label fw-semibold">Closing comment</label>

              <textarea name="closing_comment" class="form-control" rows="2" placeholder="Wrap-up notes when closing or completing this item…"><?php echo htmlspecialchars((string) $field('closing_comment')); ?></textarea>

            </div>

          </div>

        </div>



        <div class="mw-form-section">

          <div class="mw-form-section-head">

            <span class="icon"><i class="bi bi-paperclip"></i></span>

            <div>

              <h2>Priority &amp; files</h2>

              <p>Mark urgency and attach supporting documents</p>

            </div>

          </div>

          <div class="row g-3">

            <div class="col-12">

              <label class="form-label fw-semibold d-block">Priority flags</label>

              <div class="mw-flag-pills d-flex flex-wrap gap-2">

                <input type="checkbox" class="btn-check" name="is_urgent" value="1" id="isUrgent" <?php echo (int) $field('is_urgent', 0) === 1 ? 'checked' : ''; ?>>

                <label class="btn btn-outline-danger btn-sm" for="isUrgent"><i class="bi bi-exclamation-triangle me-1"></i>Urgent</label>

                <input type="checkbox" class="btn-check" name="is_important" value="1" id="isImportant" <?php echo (int) $field('is_important', 0) === 1 ? 'checked' : ''; ?>>

                <label class="btn btn-outline-warning btn-sm" for="isImportant"><i class="bi bi-star me-1"></i>Important</label>

              </div>

            </div>

            <div class="col-12">

              <label class="form-label fw-semibold">Attachment</label>

              <input type="file" name="attachment" class="form-control">

              <div class="form-text">Max 10 MB — PDF, Office, images, or zip.</div>

              <?php if ($isEdit && !empty($item->attachment_stored)): ?>

                <div class="alert alert-light border small py-2 mt-2 mb-0 d-flex flex-wrap align-items-center gap-2">

                  <i class="bi bi-paperclip text-muted"></i>

                  <a href="<?php echo site_url('my-works/' . (int) $item->id . '/download'); ?>"><?php echo htmlspecialchars($item->attachment_original ? $item->attachment_original : 'Download file'); ?></a>

                  <label class="mb-0 ms-auto"><input type="checkbox" name="remove_attachment" value="1"> Remove</label>

                </div>

              <?php endif; ?>

            </div>

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

<?php $this->load->view('partials/footer'); ?>

