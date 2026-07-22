<?php
$embed = (bool) $this->input->get('embed');
if (!$embed) {
  $this->load->view('partials/header', [
    'title' => 'Requirements',
    'extra_css' => ['assets/css/my-works.css'],
  ]);
}
?>
<div class="container-fluid req-list-compact<?php echo $embed ? ' mw-req-embed p-0' : ' py-2'; ?>">
<?php if (!$embed) {
  $this->load->view('partials/import_errors');
} ?>
<?php
ob_start();
if (function_exists('has_module_access') && (has_module_access('requirements_add') || has_module_access('requirements'))):
?>
<a class="btn btn-primary btn-sm" href="<?php echo site_url('requirements/create'); ?>" title="New requirement"><i class="bi bi-plus-lg me-1"></i>New</a>
<?php endif; ?>
<a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('requirements/board'); ?>" title="Board view"><i class="bi bi-columns me-1"></i>Board</a>
<a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('requirements/calendar'); ?>" title="Calendar view"><i class="bi bi-calendar3 me-1"></i>Calendar</a>
<?php if (function_exists('has_module_access') && (has_module_access('requirements_add') || has_module_access('requirements'))): ?>
<a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('requirements/import'); ?>" title="Import requirements"><i class="bi bi-upload me-1"></i>Import</a>
<?php endif; ?>
<?php if (function_exists('has_module_access') && (has_module_access('requirements_export') || has_module_access('requirements') || is_admin_group())): ?>
<a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('requirements/export'); ?>" title="Export CSV"><i class="bi bi-download me-1"></i>Export</a>
<?php endif;
if (!$embed) {
  $this->load->view('partials/oms_page_head', [
    'title' => 'Requirements',
    'subtitle' => 'Track client and internal requirements',
    'icon' => 'bi-list-check',
    'actions_html' => ob_get_clean(),
  ]);
} else {
  $req_embed_actions = ob_get_clean();
  if (trim($req_embed_actions) !== '') {
    echo '<div class="req-list-toolbar">' . $req_embed_actions . '</div>';
  }
}
?>

<div class="card border-0 shadow-sm mb-2 req-filter-card">
  <div class="card-body">
    <form method="get" action="<?php echo site_url('requirements'); ?>" class="req-filter-form">
      <?php if ($embed): ?>
      <input type="hidden" name="embed" value="1">
      <?php if ($this->input->get('parent_tab')): ?>
      <input type="hidden" name="parent_tab" value="<?php echo esc_view($this->input->get('parent_tab'), ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <?php endif; ?>
      <label class="req-filter-field">
        <span class="req-filter-label">Status</span>
        <?php $fs = isset($filters['status']) ? (string)$filters['status'] : ''; ?>
        <select name="status" class="form-select form-select-sm">
          <?php $statuses = array('', 'received','under_review','approved','in_progress','completed','on_hold','rejected','cancelled');
          foreach ($statuses as $st): ?>
            <option value="<?php echo esc_view($st); ?>" <?php echo ($fs===$st)?'selected':''; ?>><?php echo $st===''?'All':ucfirst(str_replace('_',' ',$st)); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="req-filter-field">
        <span class="req-filter-label">Priority</span>
        <?php $fp = isset($filters['priority']) ? (string)$filters['priority'] : ''; ?>
        <select name="priority" class="form-select form-select-sm">
          <?php $priorities = array('', 'low','medium','high','critical');
          foreach ($priorities as $pr): ?>
            <option value="<?php echo esc_view($pr); ?>" <?php echo ($fp===$pr)?'selected':''; ?>><?php echo $pr===''?'All':ucfirst($pr); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="req-filter-field">
        <span class="req-filter-label">Type</span>
        <?php $ft = isset($filters['requirement_type']) ? (string)$filters['requirement_type'] : ''; ?>
        <select name="requirement_type" class="form-select form-select-sm">
          <option value="">All</option>
          <?php if (isset($requirement_types) && is_array($requirement_types)): foreach ($requirement_types as $code => $label): ?>
            <option value="<?php echo esc_view($code); ?>" <?php echo ($ft === (string)$code) ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
          <?php endforeach; endif; ?>
        </select>
      </label>
      <label class="req-filter-field">
        <span class="req-filter-label">Client</span>
        <?php $fc = isset($filters['client_id']) ? (string)$filters['client_id'] : ''; ?>
        <select name="client_id" class="form-select form-select-sm">
          <option value="">All</option>
          <?php if (isset($clients) && is_array($clients)) foreach ($clients as $c): ?>
            <option value="<?php echo (int)$c->id; ?>" <?php echo ($fc===(string)$c->id)?'selected':''; ?>><?php echo esc_view($c->company_name); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="req-filter-field">
        <span class="req-filter-label">Assigned To</span>
        <?php $fa = isset($filters['assigned_to']) ? (string)$filters['assigned_to'] : ''; ?>
        <select name="assigned_to" class="form-select form-select-sm">
          <option value="">All</option>
          <?php if (isset($members) && is_array($members)) foreach ($members as $m): ?>
            <?php $label = '';
              if (isset($m->full_label) && $m->full_label!=='') { $label = $m->full_label; }
              else if (isset($m->full_name) && $m->full_name!=='') { $label = $m->full_name; }
              else if (isset($m->name) && $m->name!=='') { $label = $m->name; }
              else if (isset($m->email)) { $label = $m->email; }
            ?>
            <option value="<?php echo (int)$m->id; ?>" <?php echo ($fa===(string)$m->id)?'selected':''; ?>><?php echo esc_view($label); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="req-filter-field req-filter-search">
        <span class="req-filter-label">Search</span>
        <input type="search" name="q" value="<?php echo esc_view(isset($filters['search'])?$filters['search']:''); ?>" class="form-control form-control-sm" placeholder="Req#, title" autocomplete="off" aria-label="Search requirements">
      </label>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm req-table-card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover table-sm align-middle mb-0 req-list-table">
        <thead>
          <tr>
            <th>Req#</th>
            <th>Client</th>
            <th>Title</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Expected</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No requirements found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
          <?php
            $st = isset($r->status) ? (string) $r->status : 'received';
            $pr = isset($r->priority) ? (string) $r->priority : 'medium';
            $pr_class = 'secondary';
            if ($pr === 'critical') { $pr_class = 'danger'; }
            elseif ($pr === 'high') { $pr_class = 'warning text-dark'; }
            elseif ($pr === 'low') { $pr_class = 'light text-dark border'; }
          ?>
          <tr>
            <td class="text-nowrap fw-semibold"><?php echo esc_view(isset($r->req_number)?$r->req_number:''); ?></td>
            <td class="text-truncate" style="max-width:10rem;" title="<?php echo esc_view(isset($r->client_name)?$r->client_name:''); ?>"><?php echo esc_view(isset($r->client_name)?$r->client_name:''); ?></td>
            <td>
              <a href="<?php echo site_url('requirements/view/'.(int)$r->id); ?>" class="req-title-link" title="<?php echo esc_view($r->title); ?>">
                <?php echo esc_view($r->title); ?>
              </a>
            </td>
            <td><span class="badge bg-light text-dark border req-badge"><?php echo esc_view(ucfirst(str_replace('_', ' ', $st))); ?></span></td>
            <td><span class="badge bg-<?php echo $pr_class; ?> req-badge"><?php echo esc_view(ucfirst($pr)); ?></span></td>
            <td class="text-nowrap text-muted"><?php echo esc_view(isset($r->expected_delivery_date)?$r->expected_delivery_date:''); ?></td>
            <td class="text-end text-nowrap">
              <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                <a class="btn btn-outline-secondary" href="<?php echo site_url('requirements/view/'.(int)$r->id); ?>" title="View"><i class="bi bi-eye"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<script>
(function () {
  var form = document.querySelector('.req-filter-form');
  if (!form) {
    return;
  }
  form.querySelectorAll('select').forEach(function (select) {
    select.addEventListener('change', function () {
      form.submit();
    });
  });
  var input = form.querySelector('input[name="q"]');
  if (!input) {
    return;
  }
  var timer = null;
  function go() {
    form.submit();
  }
  input.addEventListener('input', function () {
    if (timer) {
      clearTimeout(timer);
    }
    timer = setTimeout(go, 350);
  });
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      if (timer) {
        clearTimeout(timer);
      }
      go();
    }
  });
})();
</script>
<?php if (!$embed) {
  $this->load->view('partials/footer');
} ?>
