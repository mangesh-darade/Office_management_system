<?php
$req = isset($req) ? $req : null;
if (!$req) {
    show_404();
}
$history = isset($history) ? $history : array();
$attachments = isset($attachments) ? $attachments : array();
$can_edit = function_exists('has_module_access') && (has_module_access('requirements_edit') || has_module_access('requirements'));
$can_note = function_exists('has_module_access') && (has_module_access('requirements_view') || has_module_access('requirements_list') || has_module_access('requirements'));

$action_label = function ($action) {
    $map = array(
        'created' => 'Created',
        'updated' => 'Updated',
        'status' => 'Status',
        'reassigned' => 'Reassigned',
        'attachment' => 'Attachment',
        'note' => 'Note',
        'comment' => 'Note',
    );
    $key = strtolower((string) $action);
    return isset($map[$key]) ? $map[$key] : ucfirst($key);
};

$status_pill = function ($s) {
    $s = strtolower((string) $s);
    if ($s === 'received') {
        return 'defect-pill defect-pill--open';
    }
    if (in_array($s, array('in_progress', 'in-progress', 'working'), true)) {
        return 'defect-pill defect-pill--progress';
    }
    if (in_array($s, array('completed', 'done', 'delivered', 'closed'), true)) {
        return 'defect-pill defect-pill--fixed';
    }
    if (in_array($s, array('rejected', 'cancelled', 'on_hold'), true)) {
        return 'defect-pill defect-pill--closed';
    }
    return 'defect-pill defect-pill--muted';
};

$priority_pill = function ($p) {
    $p = strtolower((string) $p);
    if ($p === 'urgent' || $p === 'high') {
        return 'defect-pill defect-pill--high';
    }
    if ($p === 'medium') {
        return 'defect-pill defect-pill--medium';
    }
    if ($p === 'low') {
        return 'defect-pill defect-pill--low';
    }
    return 'defect-pill defect-pill--muted';
};

$assigneeLabel = isset($req->assigned_to_name) ? trim((string) $req->assigned_to_name) : '';
if ($assigneeLabel === '' && !empty($req->assigned_to)) {
    $assigneeLabel = 'User #' . (int) $req->assigned_to;
}
if (!empty($assignee_names) && is_array($assignee_names)) {
    $parts = array();
    $seen = array();
    if ($assigneeLabel !== '') {
        $parts[] = $assigneeLabel;
        $seen[strtolower($assigneeLabel)] = true;
    }
    foreach ($assignee_names as $n) {
        $n = trim((string) $n);
        if ($n === '' || isset($seen[strtolower($n)])) {
            continue;
        }
        $seen[strtolower($n)] = true;
        $parts[] = $n;
    }
    if (!empty($parts)) {
        $assigneeLabel = implode(', ', $parts);
    }
}
if ($assigneeLabel === '') {
    $assigneeLabel = 'Unassigned';
}

$linked_tasks = array();
if ($this->db->table_exists('tasks') && schema_table_has_column($this->db, 'tasks', 'requirement_id')) {
    $this->db->select('t.id, t.title, t.status, t.priority, t.due_date, p.name AS project_name');
    $this->db->from('tasks t');
    $this->db->join('projects p', 'p.id = t.project_id', 'left');
    $this->db->where('t.requirement_id', (int) $req->id);
    $this->db->order_by('t.id', 'DESC');
    $linked_tasks = $this->db->get()->result();
}

$req_number = isset($req->req_number) && $req->req_number !== '' ? $req->req_number : ('#' . (int) $req->id);
$req_title = isset($req->title) ? (string) $req->title : '';
$req_status = isset($req->status) ? (string) $req->status : 'received';
$req_priority = isset($req->priority) ? (string) $req->priority : 'medium';
$type_label = '';
if (!empty($req->requirement_type)) {
    $type_label = function_exists('module_type_label')
        ? module_type_label($req->requirement_type, 'requirements')
        : (string) $req->requirement_type;
}

$this->load->view('partials/header', array(
    'title' => $req_number,
    'extra_css' => array('assets/css/defects-form.css'),
));
?>
<script>document.body.classList.add('defect-view-active');</script>

<div class="defect-view-simple">
  <div class="defect-view-toolbar mb-3">
    <a href="<?php echo site_url('requirements'); ?>" class="btn btn-sm btn-outline-secondary defect-view-back" title="Back to list">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div class="defect-view-heading min-w-0">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <h1 class="h5 mb-0 text-truncate" title="<?php echo esc_view($req_title); ?>">
          <?php echo esc_view($req_title !== '' ? $req_title : 'Untitled requirement'); ?>
        </h1>
        <span class="<?php echo esc_view($status_pill($req_status)); ?>"><?php echo esc_view(ucwords(str_replace('_', ' ', $req_status))); ?></span>
        <span class="<?php echo esc_view($priority_pill($req_priority)); ?>"><?php echo esc_view(ucfirst($req_priority)); ?></span>
        <?php if ($type_label !== ''): ?>
          <span class="defect-pill defect-pill--muted"><?php echo esc_view($type_label); ?></span>
        <?php endif; ?>
      </div>
      <div class="small text-muted mt-1">
        <span class="font-monospace fw-semibold text-body"><?php echo esc_view($req_number); ?></span>
      </div>
    </div>
    <div class="defect-view-actions d-flex align-items-center gap-1 flex-shrink-0">
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('requirements'); ?>" title="List"><i class="bi bi-list-ul"></i></a>
      <?php if ($can_edit): ?>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('requirements/edit/' . (int) $req->id); ?>" title="Edit"><i class="bi bi-pencil"></i></a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 mb-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 mb-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="row g-2">
    <div class="col-12 col-lg-8">
      <div class="card mb-2">
        <div class="card-body py-2 px-3">
          <div class="small text-muted fw-semibold mb-1">Description</div>
          <div class="defect-rich-content small mb-0">
            <?php if (!empty($req->description)): ?>
              <?php echo sanitize_html_output($req->description); ?>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </div>
          <?php if (!empty($req->reference_url)): ?>
            <div class="mt-2 pt-2 border-top">
              <?php $this->load->view('partials/reference_url_display', array('reference_url' => $req->reference_url, 'wrapper_class' => 'mb-0')); ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($attachments)): ?>
      <div class="card mb-2">
        <div class="card-body py-2 px-3">
          <div class="small text-muted fw-semibold mb-1">Attachments</div>
          <ul class="list-unstyled small mb-0">
            <?php foreach ($attachments as $a): ?>
              <li class="mb-1">
                <a href="<?php echo base_url($a->file_path); ?>" download>
                  <i class="bi bi-paperclip me-1"></i><?php echo esc_view(isset($a->original_name) ? $a->original_name : $a->file_name); ?>
                </a>
                <?php if (isset($a->file_size)): ?>
                  <span class="text-muted">(<?php echo (int) $a->file_size; ?> KB)</span>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>

      <div class="card mb-2">
        <div class="card-body py-2 px-3">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <div class="small text-muted fw-semibold">Linked Tasks<?php echo !empty($linked_tasks) ? ' (' . count($linked_tasks) . ')' : ''; ?></div>
            <a href="<?php echo site_url('tasks/create?requirement_id=' . (int) $req->id); ?>" class="btn btn-sm btn-outline-primary" title="Create task">
              <i class="bi bi-plus-lg me-1"></i>Create task
            </a>
          </div>
          <?php if (empty($linked_tasks)): ?>
            <p class="text-muted small mb-0">No linked tasks yet.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm table-bordered mb-0 align-middle">
                <thead class="table-light">
                  <tr>
                    <th style="width:4rem;">ID</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Due</th>
                    <th class="text-end" style="width:4rem;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($linked_tasks as $lt): ?>
                    <tr>
                      <td class="small text-muted">#<?php echo (int) $lt->id; ?></td>
                      <td class="small"><?php echo esc_view($lt->title); ?></td>
                      <td class="small"><?php echo esc_view(ucwords(str_replace('_', ' ', (string) $lt->status))); ?></td>
                      <td class="small text-nowrap"><?php echo !empty($lt->due_date) ? esc_view($lt->due_date) : '—'; ?></td>
                      <td class="text-end text-nowrap">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                          <a href="<?php echo site_url('tasks/' . (int) $lt->id); ?>" class="btn btn-outline-secondary" title="View"><i class="bi bi-eye"></i></a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-2" id="history">
        <div class="card-body py-2 px-3">
          <?php if ($can_note): ?>
            <div class="small text-muted fw-semibold mb-1">Save note</div>
            <form method="post" action="<?php echo site_url('requirements/add-comment/' . (int) $req->id); ?>" class="mb-3">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
              <textarea name="note" id="requirementHistoryNote" class="form-control form-control-sm mb-2" rows="2" placeholder="Add a note to history…"></textarea>
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
              <tr><th>Client</th><td><?php echo esc_view(!empty($req->client_name) ? $req->client_name : '—'); ?></td></tr>
              <tr><th>Project</th><td><?php echo esc_view(!empty($req->project_name) ? $req->project_name : '—'); ?></td></tr>
              <tr><th>Type</th><td><?php echo esc_view($type_label !== '' ? $type_label : '—'); ?></td></tr>
              <tr><th>Priority</th><td><?php echo esc_view(ucfirst($req_priority)); ?></td></tr>
              <tr><th>Status</th><td><?php echo esc_view(ucwords(str_replace('_', ' ', $req_status))); ?></td></tr>
              <tr><th>Expected</th><td><?php echo esc_view(!empty($req->expected_delivery_date) ? $req->expected_delivery_date : '—'); ?></td></tr>
              <tr><th>Received</th><td><?php echo esc_view(!empty($req->received_date) ? $req->received_date : '—'); ?></td></tr>
              <tr><th>Owner</th><td><?php echo esc_view(!empty($req->owner_name) ? $req->owner_name : 'Unassigned'); ?></td></tr>
              <tr><th>Assignee</th><td><?php echo esc_view($assigneeLabel); ?></td></tr>
              <tr><th>Created</th><td><?php echo esc_view(!empty($req->created_at) ? $req->created_at : '—'); ?></td></tr>
              <tr><th>Updated</th><td><?php echo esc_view(!empty($req->updated_at) ? $req->updated_at : '—'); ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
