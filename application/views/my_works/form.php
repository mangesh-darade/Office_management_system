<?php
  $isEdit = ($action === 'edit' && !empty($item) && !empty($item->id));
  $title = $isEdit ? 'Edit Work Item' : 'New Work Item';
  $this->load->view('partials/header', array('title' => $title, 'extra_css' => array('assets/css/my-works.css')));
  $status = ($item && isset($item->status)) ? (string) $item->status : 'new';
  $statusLabels = my_works_status_labels();
  $tags = isset($tags) ? $tags : array();
  $tasks = isset($tasks) ? $tasks : array();
  $uid = (int) $this->session->userdata('user_id');
  $field = function ($key, $default = '') use ($item) {
    if ($item && isset($item->$key) && $item->$key !== null && $item->$key !== '') {
      return $item->$key;
    }
    return $default;
  };
  $curFor = $isEdit ? (int) $item->created_for : (int) $field('created_for', $uid);
?>
<div class="container-fluid py-3">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
    <div>
      <h1 class="h4 mb-0 fw-bold"><?php echo htmlspecialchars($title); ?></h1>
      <p class="text-muted small mb-0"><?php echo $isEdit ? 'Update details, flags, or assignment' : 'Add a work item for yourself or a team member'; ?></p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('my-works'); ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if (!empty($scope) && !empty($scope['message'])): ?>
    <?php $this->load->view('my_works/_scope_banner', array('scope' => $scope)); ?>
  <?php endif; ?>

  <div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
      <form method="post" enctype="multipart/form-data" action="<?php echo $isEdit ? site_url('my-works/' . (int) $item->id . '/edit') : site_url('my-works/create'); ?>">
        <?php $this->load->view('my_works/_csrf'); ?>
        <div class="row g-3">
          <div class="col-12 col-md-8">
            <label class="form-label">Task title <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control form-control-lg" required maxlength="255" value="<?php echo htmlspecialchars((string) $field('title')); ?>" placeholder="What needs to be done?" autofocus>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Tags</label>
            <input type="text" name="tag" class="form-control" maxlength="255" list="mw-form-tags" value="<?php echo htmlspecialchars((string) $field('tag')); ?>" placeholder="client, follow-up">
            <datalist id="mw-form-tags">
              <?php foreach ($tags as $t): ?>
                <option value="<?php echo htmlspecialchars($t); ?>">
              <?php endforeach; ?>
            </datalist>
            <div class="form-text">Comma-separated tags.</div>
          </div>
          <div class="col-12">
            <label class="form-label">Details</label>
            <textarea name="details" class="form-control" rows="6" placeholder="Notes, steps, context…"><?php echo htmlspecialchars((string) $field('details')); ?></textarea>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">URL</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
              <input type="text" name="url" class="form-control" value="<?php echo htmlspecialchars((string) $field('url')); ?>" placeholder="https://example.com or example.com">
            </div>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">Due date</label>
            <input type="date" name="due_date" class="form-control" value="<?php echo htmlspecialchars((string) $field('due_date')); ?>">
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <?php foreach ($statusLabels as $k => $lbl): ?>
                <option value="<?php echo $k; ?>" <?php echo $status === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Created for</label>
            <select name="created_for" class="form-select">
              <?php foreach ((array) $users as $u): ?>
                <option value="<?php echo (int) $u->id; ?>" <?php echo (int) $u->id === $curFor ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars(my_works_user_label($u->name, $u->email, $u->id)); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Who should work on this item.</div>
          </div>
          <?php if (!empty($tasks)): ?>
          <div class="col-12 col-md-6">
            <label class="form-label">Link to project task <span class="text-muted">(optional)</span></label>
            <select name="task_id" class="form-select">
              <option value="0">None</option>
              <?php foreach ($tasks as $t): ?>
                <option value="<?php echo (int) $t->id; ?>" <?php echo (int) $field('task_id', 0) === (int) $t->id ? 'selected' : ''; ?>>
                  #<?php echo (int) $t->id; ?> — <?php echo htmlspecialchars($t->title); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="col-12 col-md-6">
            <label class="form-label">Attachment</label>
            <input type="file" name="attachment" class="form-control" accept=".gif,.jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip,.rar,.csv">
            <div class="form-text">Max 10 MB. PDF, Office, images, zip.</div>
            <?php if ($isEdit && !empty($item->attachment_stored)): ?>
              <div class="alert alert-light border small py-2 mt-2 mb-0">
                <i class="bi bi-paperclip me-1"></i>
                <a href="<?php echo site_url('my-works/' . (int) $item->id . '/download'); ?>"><?php echo htmlspecialchars($item->attachment_original ? $item->attachment_original : 'Download'); ?></a>
                <label class="ms-2 mb-0"><input type="checkbox" name="remove_attachment" value="1"> Remove file</label>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label d-block">Priority flags</label>
            <div class="mw-flag-pills d-flex flex-wrap gap-2">
              <input type="checkbox" class="btn-check" name="is_urgent" value="1" id="isUrgent" <?php echo (int) $field('is_urgent', 0) === 1 ? 'checked' : ''; ?>>
              <label class="btn btn-outline-danger btn-sm" for="isUrgent"><i class="bi bi-exclamation-triangle me-1"></i>Urgent</label>
              <input type="checkbox" class="btn-check" name="is_important" value="1" id="isImportant" <?php echo (int) $field('is_important', 0) === 1 ? 'checked' : ''; ?>>
              <label class="btn btn-outline-warning btn-sm" for="isImportant"><i class="bi bi-star me-1"></i>Important</label>
            </div>
          </div>
          <div class="col-12">
            <div class="d-flex flex-column flex-sm-row gap-2 pt-2">
              <button type="submit" class="btn btn-primary"><?php echo $isEdit ? 'Save changes' : 'Create work item'; ?></button>
              <a class="btn btn-outline-secondary" href="<?php echo $isEdit ? site_url('my-works/' . (int) $item->id) : site_url('my-works'); ?>">Cancel</a>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
