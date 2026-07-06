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
<?php $this->load->view('partials/footer'); ?>
