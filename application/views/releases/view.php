<?php $this->load->view('partials/header', ['title' => $item->version . ' — ' . $item->title, 'extra_css' => ['assets/css/defects-form.css']]); ?>
<script>document.body.classList.add('defect-view-active');</script>
<div class="defect-view-simple release-detail">
<?php
$can_edit = function_exists('has_module_access') && (has_module_access('releases_edit') || has_module_access('releases'));
$rel_status_pill = function ($s) {
    $s = strtolower((string) $s);
    if ($s === 'released') { return 'defect-pill defect-pill--fixed'; }
    if ($s === 'in_progress') { return 'defect-pill defect-pill--progress'; }
    if ($s === 'cancelled') { return 'defect-pill defect-pill--critical'; }
    return 'defect-pill defect-pill--muted';
};
?>
<div class="defect-view-toolbar mb-3">
  <a href="<?php echo site_url('releases'); ?>" class="btn btn-sm btn-outline-secondary defect-view-back" title="Back to list">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div class="defect-view-heading min-w-0">
    <div class="d-flex flex-wrap align-items-center gap-2">
      <h1 class="h5 mb-0 text-truncate" title="<?php echo esc_view($item->title); ?>"><?php echo esc_view($item->title !== '' ? $item->title : 'Untitled release'); ?></h1>
    </div>
    <div class="small text-muted mt-1">
      <span class="font-monospace fw-semibold text-body"><?php echo esc_view($item->version); ?></span>
      <?php if (!empty($item->project_name)): ?>
        <span class="mx-1">·</span><?php echo esc_view($item->project_name); ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="oms-view-status d-flex flex-wrap align-items-center gap-2 ms-auto">
    <?php if ($can_edit): ?>
      <?php $this->load->view('partials/status_quick', array('module_type' => 'releases', 'item_id' => (int) $item->id, 'current' => (string) $item->status, 'url' => site_url('releases/update-status'))); ?>
    <?php else: ?>
      <span class="<?php echo esc_view($rel_status_pill($item->status)); ?>"><?php echo esc_view(ucwords(str_replace('_', ' ', (string) $item->status))); ?></span>
    <?php endif; ?>
  </div>
  <div class="defect-view-actions d-flex align-items-center gap-1 flex-shrink-0">
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('releases'); ?>" title="List"><i class="bi bi-list-ul"></i></a>
    <?php if ($can_edit): ?>
      <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('releases/edit/' . (int) $item->id); ?>" title="Edit"><i class="bi bi-pencil"></i></a>
    <?php endif; ?>
  </div>
</div>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div><?php endif; ?>
<?php
  $detail_tab = trim((string) $this->input->get('tab')) === 'history' ? 'history' : 'details';
  $history = isset($history) && is_array($history) ? $history : array();
  $this->load->view('partials/oms_detail_tabs', array(
    'active_tab' => $detail_tab,
    'history_count' => count($history),
  ));
?>
<div class="tab-content">
<div class="tab-pane fade<?php echo $detail_tab === 'details' ? ' show active' : ''; ?>" id="details" role="tabpanel" aria-labelledby="details-tab">
<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-soft mb-3"><div class="card-body">
      <h2 class="h6 text-muted">Description</h2>
      <p class="mb-0"><?php echo nl2br(esc_view($item->description ?: '—')); ?></p>
    </div></div>
    <div class="card shadow-soft mb-3"><div class="card-body">
      <h2 class="h6 text-muted">Release note points</h2>
      <?php if (empty($note_points)): ?><p class="text-muted mb-0">No release notes yet.</p><?php else: ?>
      <ol class="mb-0 ps-3">
        <?php foreach ($note_points as $n): ?>
        <li class="mb-1"><?php echo esc_view($n->point_text); ?></li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
    </div></div>
    <?php if (!empty($related_defects)): ?>
    <div class="card shadow-soft"><div class="card-body">
      <h2 class="h6 text-muted">Related defects</h2>
      <ul class="list-group list-group-flush border rounded">
        <?php foreach ($related_defects as $d): ?>
        <li class="list-group-item py-2 d-flex justify-content-between align-items-center">
          <span><span class="badge bg-light text-dark border me-1"><?php echo esc_view($d->status); ?></span><?php echo esc_view($d->defect_number . ': ' . $d->title); ?></span>
          <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('defects/view/'.$d->id); ?>">View</a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div></div>
    <?php endif; ?>

  </div>
  <div class="col-lg-4">
    <div class="card shadow-soft"><div class="card-body">
      <dl class="row mb-0 small">
        <dt class="col-5">Project</dt><dd class="col-7"><?php echo esc_view(isset($item->project_name) ? $item->project_name : '—'); ?></dd>
        <dt class="col-5">Status</dt><dd class="col-7"><?php echo esc_view(str_replace('_',' ',$item->status)); ?></dd>
        <dt class="col-5">Planned</dt><dd class="col-7"><?php echo esc_view($item->planned_date ?: '—'); ?></dd>
        <dt class="col-5">Released</dt><dd class="col-7"><?php echo esc_view($item->released_at ?: '—'); ?></dd>
        <dt class="col-5">Notes sent</dt><dd class="col-7"><?php echo !empty($item->notes_sent_at) ? esc_view(date('M j, Y g:i A', strtotime($item->notes_sent_at))) : '—'; ?></dd>
        <dt class="col-5">Created</dt><dd class="col-7"><?php echo esc_view($item->created_at ?: '—'); ?></dd>
      </dl>
    </div></div>
  </div>
</div>
</div>
<?php
  $can_note = function_exists('has_module_access') && (has_module_access('releases_view') || has_module_access('releases_list') || has_module_access('releases'));
  $release_action_label = function ($action) {
    $map = array(
      'created' => 'Created',
      'updated' => 'Updated',
      'note' => 'Note',
      'comment' => 'Note',
    );
    $key = strtolower((string) $action);
    return isset($map[$key]) ? $map[$key] : ucfirst(str_replace('_', ' ', (string) $action));
  };
?>
<div class="tab-pane fade<?php echo $detail_tab === 'history' ? ' show active' : ''; ?>" id="history" role="tabpanel" aria-labelledby="history-tab">
  <div class="card shadow-soft mb-0">
    <div class="card-body">
      <?php if ($can_note): ?>
        <div class="small text-muted fw-semibold mb-1">Save note</div>
        <form method="post" action="<?php echo site_url('releases/add-comment/' . (int) $item->id); ?>" class="mb-3">
          <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
          <textarea name="note" id="releaseHistoryNote" class="form-control form-control-sm mb-2" rows="2" placeholder="Add a note to history…"></textarea>
          <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Save note</button>
        </form>
      <?php endif; ?>
      <div class="d-flex align-items-center justify-content-between mb-1">
        <div class="small text-muted fw-semibold">History</div>
        <span class="text-muted small"><?php echo count($history); ?></span>
      </div>
      <?php
          $oms_history_rows = array();
          foreach ($history as $h) {
              $who = ($h->user_name !== '') ? $h->user_name : 'System';
              $changed = trim((string) $h->detail);
              $act = strtolower((string) $h->action);
              if ($changed === '') {
                  $changed = $release_action_label($h->action);
              } elseif ($act !== 'note' && $act !== 'comment' && $act !== 'commented') {
                  if (strpos($changed, ':') === false && strpos($changed, '→') === false) {
                      $changed = $release_action_label($h->action) . ': ' . $changed;
                  }
              }
              $oms_history_rows[] = array(
                  'date' => $h->created_at,
                  'who' => $who,
                  'parts' => preg_split('/\s*;\s*/', $changed),
                  'text' => $changed,
              );
          }
          $this->load->view('partials/history_table', array('history_rows' => $oms_history_rows));
        ?>

    </div>
  </div>
</div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
