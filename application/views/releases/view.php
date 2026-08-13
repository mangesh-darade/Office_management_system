<?php $this->load->view('partials/header', ['title' => $item->version . ' — ' . $item->title]); ?>
<div class="container-fluid py-3">
<?php
$can_edit = function_exists('has_module_access') && (has_module_access('releases_edit') || has_module_access('releases'));
$actions = '';
if ($can_edit) {
    $actions .= '<a class="btn btn-primary btn-sm" href="'.site_url('releases/edit/'.$item->id).'"><i class="bi bi-pencil me-1"></i>Edit</a> ';
}
$actions .= '<a class="btn btn-outline-secondary btn-sm" href="'.site_url('releases').'"><i class="bi bi-arrow-left me-1"></i>All releases</a>';
$this->load->view('partials/oms_page_head', ['title' => esc_view($item->version), 'icon' => 'bi-rocket-takeoff', 'subtitle' => esc_view($item->title), 'actions_html' => $actions]);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div><?php endif; ?>
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

    <?php
      $history = isset($history) && is_array($history) ? $history : array();
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
    <div class="card shadow-soft mb-3" id="history">
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
        <?php if (empty($history)): ?>
          <p class="text-muted small mb-0">No history yet.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0 align-middle">
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
                        $changed = $release_action_label($h->action);
                    } elseif (strtolower((string) $h->action) !== 'note' && strtolower((string) $h->action) !== 'comment') {
                        if (strpos($changed, ':') === false && strpos($changed, '→') === false) {
                            $changed = $release_action_label($h->action) . ': ' . $changed;
                        }
                    }
                    $parts = preg_split('/\s*;\s*/', $changed);
                  ?>
                  <tr>
                    <td class="small text-nowrap text-muted text-start"><?php echo esc_view($h->created_at); ?></td>
                    <td class="small">
                      <?php if (count($parts) > 1): ?>
                        <ul class="mb-0 ps-3">
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
<?php $this->load->view('partials/footer'); ?>
