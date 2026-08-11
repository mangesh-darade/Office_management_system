<?php
$item = isset($item) ? $item : null;
if (!$item) {
    show_404();
}
$history = isset($history) ? $history : array();
$attachments = isset($attachments) ? $attachments : array();
$is_overdue = !empty($is_overdue);
$can_edit = function_exists('has_module_access') && (has_module_access('defects_edit') || has_module_access('defects'));
$can_delete = function_exists('has_module_access') && (has_module_access('defects_delete') || has_module_access('defects'));
$can_note = function_exists('has_module_access') && (has_module_access('defects_view') || has_module_access('defects_list') || has_module_access('defects'));

$sev_class = function ($s) {
    $s = strtolower((string) $s);
    if ($s === 'critical') {
        return 'defect-pill defect-pill--critical';
    }
    if ($s === 'high') {
        return 'defect-pill defect-pill--high';
    }
    if ($s === 'medium') {
        return 'defect-pill defect-pill--medium';
    }
    return 'defect-pill defect-pill--low';
};
$status_class = function ($s) {
    $s = strtolower((string) $s);
    if ($s === 'open') {
        return 'defect-pill defect-pill--open';
    }
    if ($s === 'in_progress') {
        return 'defect-pill defect-pill--progress';
    }
    if (in_array($s, array('fixed', 'verified'), true)) {
        return 'defect-pill defect-pill--fixed';
    }
    if ($s === 'closed') {
        return 'defect-pill defect-pill--closed';
    }
    return 'defect-pill defect-pill--muted';
};

$action_label = function ($action) {
    $map = array(
        'created' => 'Created',
        'updated' => 'Updated',
        'status' => 'Status',
        'reassigned' => 'Reassigned',
        'attachment' => 'Attachment',
        'note' => 'Note',
        'comment' => 'Note',
        'deleted' => 'Deleted',
    );
    $key = strtolower((string) $action);
    return isset($map[$key]) ? $map[$key] : ucfirst(str_replace('_', ' ', (string) $action));
};

$allowed_html = '<p><br><strong><em><b><i><u><s><del><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span>';

$this->load->view('partials/header', array(
    'title' => $item->defect_number,
    'extra_css' => array('assets/css/defects-form.css'),
));
?>
<script>document.body.classList.add('defect-view-active');</script>

<div class="defect-view-simple">
  <div class="defect-view-toolbar mb-3">
    <a href="<?php echo site_url('defects'); ?>" class="btn btn-sm btn-outline-secondary defect-view-back" title="Back to list">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div class="defect-view-heading min-w-0">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="h5 mb-0 text-truncate" title="<?php echo esc_view($item->title); ?>">
          <?php echo esc_view($item->title !== '' ? $item->title : 'Untitled defect'); ?>
        </h1>
        <span class="<?php echo esc_view($status_class($item->status)); ?>"><?php echo esc_view(ucfirst(str_replace('_', ' ', (string) $item->status))); ?></span>
        <span class="<?php echo esc_view($sev_class($item->severity)); ?>"><?php echo esc_view(ucfirst((string) $item->severity)); ?></span>
        <?php if ($is_overdue): ?><span class="badge text-bg-danger">Overdue</span><?php endif; ?>
      </div>
      <div class="small text-muted mt-1">
        <span class="font-monospace fw-semibold text-body"><?php echo esc_view($item->defect_number); ?></span>
      </div>
    </div>
    <div class="defect-view-actions d-flex align-items-center gap-1 flex-shrink-0">
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('defects'); ?>" title="List"><i class="bi bi-list-ul"></i></a>
      <?php if ($can_edit): ?>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('defects/edit/' . (int) $item->id); ?>" title="Edit"><i class="bi bi-pencil"></i></a>
      <?php endif; ?>
      <?php if ($can_delete): ?>
        <form method="post" action="<?php echo site_url('defects/delete/' . (int) $item->id); ?>" class="d-inline" onsubmit="return confirm('Delete this defect?');">
          <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
          <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 mb-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 mb-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($is_overdue): ?>
    <div class="alert alert-warning py-2 mb-2"><i class="bi bi-exclamation-triangle me-1"></i>This defect is overdue.</div>
  <?php endif; ?>

  <div class="row g-2">
    <div class="col-12 col-lg-8">
      <div class="card mb-2">
        <div class="card-body py-2 px-3">
          <div class="small text-muted fw-semibold mb-1">Description</div>
          <div class="defect-rich-content small mb-0">
            <?php if (!empty($item->description)): ?>
              <?php echo strip_tags((string) $item->description, $allowed_html); ?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card mb-2">
        <div class="card-body py-2 px-3">
          <div class="small text-muted fw-semibold mb-1">Steps to reproduce</div>
          <div class="defect-rich-content small mb-0">
            <?php if (!empty($item->steps_to_reproduce)): ?>
              <?php echo strip_tags((string) $item->steps_to_reproduce, $allowed_html); ?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (!empty($attachments)): ?>
      <div class="card mb-2">
        <div class="card-body py-2 px-3">
          <div class="small text-muted fw-semibold mb-1">Attachments</div>
          <ul class="list-unstyled small mb-0">
            <?php foreach ($attachments as $a): ?>
              <li class="mb-1">
                <a href="<?php echo site_url('defects/attachment/' . (int) $item->id . '/' . (int) $a->id . '/download'); ?>">
                  <i class="bi bi-paperclip me-1"></i><?php echo esc_view($a->original_name); ?>
                </a>
                <span class="text-muted">(<?php echo number_format((int) $a->file_size / 1024, 1); ?> KB)</span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>

      <div class="card mb-2" id="history">
        <div class="card-body py-2 px-3">
          <?php if ($can_note): ?>
            <div class="small text-muted fw-semibold mb-1">Save note</div>
            <form method="post" action="<?php echo site_url('defects/add-comment/' . (int) $item->id); ?>" class="mb-3">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <textarea name="note" id="defectHistoryNote" class="form-control form-control-sm mb-2" rows="2" placeholder="Add a note to history…"></textarea>
              <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Save note</button>
            </form>
          <?php endif; ?>

          <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="small text-muted fw-semibold">History</div>
            <span class="text-muted small"><?php echo count($history); ?></span>
          </div>

          <?php if (empty($history)): ?>
            <p class="text-muted small mb-0">No history yet.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0 align-middle defect-history-grid">
                <thead class="table-light">
                  <tr>
                    <th class="text-start" style="width:9.5rem;">Date</th>
                    <th>Comments</th>
                    <th style="width:9rem;">Added By</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($history as $h): ?>
                    <?php
                      $who = ($h->user_name !== '') ? $h->user_name : 'System';
                      $changed = trim((string) $h->detail);
                      if ($changed === '') {
                          $changed = $action_label($h->action);
                      } elseif (strtolower((string) $h->action) !== 'note' && strtolower((string) $h->action) !== 'comment') {
                          if (strpos($changed, ':') === false && strpos($changed, '→') === false) {
                              $changed = $action_label($h->action) . ': ' . $changed;
                          }
                      }
                      $parts = preg_split('/\s*;\s*/', $changed);
                    ?>
                    <tr>
                      <td class="small text-nowrap text-muted text-start"><?php echo esc_view($h->created_at); ?></td>
                      <td class="small">
                        <?php if (count($parts) > 1): ?>
                          <ul class="mb-0 ps-3 defect-history-changes">
                            <?php foreach ($parts as $part): ?>
                              <?php if (trim($part) === '') { continue; } ?>
                              <li><?php echo esc_view(trim($part)); ?></li>
                            <?php endforeach; ?>
                          </ul>
                        <?php else: ?>
                          <?php echo nl2br(esc_view($changed)); ?>
                        <?php endif; ?>
                      </td>
                      <td class="small fw-semibold"><?php echo esc_view($who); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card mb-2">
        <div class="card-body py-2 px-3">
          <div class="small text-muted fw-semibold mb-2">Details</div>
          <table class="table table-sm mb-0 defect-simple-meta">
            <tbody>
              <tr><th>Client</th><td><?php echo esc_view(!empty($item->client_name) ? $item->client_name : '—'); ?></td></tr>
              <tr><th>Project</th><td><?php echo esc_view($item->project_name ?: '—'); ?></td></tr>
              <tr><th>Release</th><td><?php echo $item->release_version ? esc_view($item->release_version) : '—'; ?></td></tr>
              <tr><th>Task</th><td><?php echo !empty($item->task_title) ? esc_view($item->task_title) : '—'; ?></td></tr>
              <tr><th>Severity</th><td><?php echo esc_view(ucfirst((string) $item->severity)); ?></td></tr>
              <tr><th>Priority</th><td><?php echo esc_view(ucfirst((string) $item->priority)); ?></td></tr>
              <tr><th>Status</th><td><?php echo esc_view(ucfirst(str_replace('_', ' ', (string) $item->status))); ?></td></tr>
              <tr><th>Due</th><td><?php echo esc_view(!empty($item->due_date) ? $item->due_date : '—'); ?></td></tr>
              <tr><th>Reporter</th><td><?php echo esc_view($item->reporter_name ?: '—'); ?></td></tr>
              <tr><th>Assignee</th><td><?php echo esc_view($item->assignee_name ?: 'Unassigned'); ?></td></tr>
              <tr><th>Verified</th><td><?php echo esc_view(!empty($item->verifier_name) ? $item->verifier_name : '—'); ?></td></tr>
              <tr><th>Resolved</th><td><?php echo esc_view($item->resolved_at ?: '—'); ?></td></tr>
              <tr><th>Created</th><td><?php echo esc_view($item->created_at ?: '—'); ?></td></tr>
              <tr><th>Updated</th><td><?php echo esc_view(!empty($item->updated_at) ? $item->updated_at : '—'); ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
