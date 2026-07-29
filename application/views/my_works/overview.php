<?php 
$embed = (bool)$this->input->get('embed');
if (!$embed) {
  $this->load->view('partials/header', array(
    'title' => 'My Work Overview', 
    'extra_css' => array('assets/css/my-works.css'),
  ));
} ?>

<?php
  $view_mode = 'overview';
  $dashboard_sections = isset($dashboard_sections) ? $dashboard_sections : array();
  $dashboard_counts = isset($dashboard_counts) ? $dashboard_counts : array('ad_hoc' => 0, 'project' => 0, 'total' => 0);
  $filterProjectId = isset($filters['project_id']) ? (int) $filters['project_id'] : 0;
  $filterAssignee = isset($filters['created_for']) ? (int) $filters['created_for'] : 0;
  $filterSearch = isset($filters['q']) ? trim((string) $filters['q']) : '';
  $filters_active = ($filterAssignee > 0 || $filterProjectId > 0 || $filterSearch !== '');
  // When filters on: show empty carts after nonempty (do not hide).
  $hide_empty_lanes = false;
  $prioritize_nonempty = $filters_active;
  $section_order = array('ad_hoc', 'project');
  if ($prioritize_nonempty && function_exists('dashboard_sort_nonempty_first')) {
    $section_order = dashboard_sort_nonempty_first(
      $section_order,
      function ($key) use ($dashboard_counts) {
        return isset($dashboard_counts[$key]) ? (int) $dashboard_counts[$key] : 0;
      }
    );
  }
  $baseQuery = array('view' => 'overview');
  foreach ($filters as $k => $v) {
    if ($k === 'current_user_id') {
      continue;
    }
    if ($v !== '' && $v !== 0 && $v !== '0') {
      $baseQuery[$k] = $v;
    }
  }
?>

<div class="mw-dash-page<?php echo $embed ? ' mw-dash-page--embed' : ''; ?>"
     data-lane-update-url="<?php echo esc_view(site_url('my-works/update-lane'), ENT_QUOTES, 'UTF-8'); ?>">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 mx-3 mt-2 mb-0" role="alert">
      <?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if (!$embed): ?>
  <header class="mw-dash-header">
    <div class="mw-dash-header-left d-flex align-items-start gap-2">
      <?php $this->load->view('my_works/_back_btn', array(
        'back_url' => site_url('dashboard'),
        'back_title' => 'Back to Dashboard',
      )); ?>
      <div>
      <h1 class="mw-dash-title">My Work Overview</h1>
      <p class="mw-dash-subtitle">Organize, prioritize and track your work items efficiently.</p>
      </div>
    </div>
    <div class="mw-dash-header-center">
      <form method="get" action="<?php echo site_url('my-works'); ?>" class="mw-dash-search-form">
        <input type="hidden" name="view" value="overview">
        <?php foreach ($baseQuery as $qk => $qv): ?>
          <?php if ($qk === 'q' || $qk === 'view') { continue; } ?>
          <input type="hidden" name="<?php echo esc_view($qk); ?>" value="<?php echo esc_view((string) $qv, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
        <div class="mw-dash-search-wrap">
          <i class="bi bi-search"></i>
          <input type="search" name="q" class="mw-dash-search-input" placeholder="Search tasks, projects, clients…" value="<?php echo esc_view(isset($filters['q']) ? (string) $filters['q'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </form>
    </div>
    <div class="mw-dash-header-right">
      <?php if (!empty($can_add)): ?>
        <a class="btn btn-primary btn-sm mw-dash-create-btn" href="<?php echo site_url('my-works/create'); ?>">
          <i class="bi bi-plus-lg me-1"></i>Create Task
        </a>
        <a class="btn btn-outline-primary btn-sm mw-dash-template-btn" href="<?php echo site_url('my-works/template-tasks'); ?>">
          <i class="bi bi-collection me-1"></i>Template Task
        </a>
      <?php endif; ?>
      <div class="mw-dash-date-pill">
        <i class="bi bi-calendar3"></i>
        <span><?php echo esc_view(my_works_dashboard_week_range_label(), ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </div>
  </header>
  <?php endif; ?>

  <?php if (!$embed) {
    $this->load->view('my_works/_dash_view_tabs', array('active_tab' => 'overview'));
  } ?>

  <div class="mw-dash-filters<?php echo $embed ? ' mw-dash-filters--embed' : ''; ?>">
    <form method="get" action="<?php echo site_url('my-works'); ?>" class="mw-dash-filter-form flex-nowrap mb-0" id="mwDashFilterForm">
      <input type="hidden" name="view" value="overview">
      <?php if (!empty($filters['q'])): ?>
        <input type="hidden" name="q" value="<?php echo esc_view((string) $filters['q'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <label class="mw-dash-filter-label">
        Assigned To:
        <select name="created_for" class="form-select form-select-sm mw-dash-filter-select" <?php echo empty($can_filter_users) ? 'disabled' : ''; ?>>
          <option value="0">All Users</option>
          <?php foreach ((array) $users as $u): ?>
            <option value="<?php echo (int) $u->id; ?>" <?php echo $filterAssignee === (int) $u->id ? 'selected' : ''; ?>>
              <?php echo esc_view(my_works_user_label($u->name, $u->email, $u->id), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="mw-dash-filter-label">
        Project:
        <select name="project_id" class="form-select form-select-sm mw-dash-filter-select">
          <option value="0">All Projects</option>
          <?php foreach ((array) $projects as $p): ?>
            <option value="<?php echo (int) $p->id; ?>" <?php echo $filterProjectId === (int) $p->id ? 'selected' : ''; ?>>
              <?php echo esc_view($p->name ? (string) $p->name : ('Project #' . (int) $p->id), ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
    </form>
    <?php if ($embed): ?>
      <form method="get" action="<?php echo site_url('my-works'); ?>" class="mw-dash-search-form mb-0 mw-dash-search-form--embed">
        <input type="hidden" name="view" value="overview">
        <?php foreach ($baseQuery as $qk => $qv): ?>
          <?php if ($qk === 'q' || $qk === 'view') { continue; } ?>
          <input type="hidden" name="<?php echo esc_view($qk); ?>" value="<?php echo esc_view((string) $qv, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
        <div class="mw-dash-search-wrap mw-dash-search-wrap--compact">
          <i class="bi bi-search"></i>
          <input type="search" name="q" class="mw-dash-search-input" placeholder="Search..." value="<?php echo esc_view(isset($filters['q']) ? (string) $filters['q'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </form>
    <?php endif; ?>
  </div>

  <?php foreach ($section_order as $section_key): ?>
  <?php
    $section_title = ($section_key === 'project') ? 'Project Tasks' : 'Ad hoc tasks';
    $section_count = isset($dashboard_counts[$section_key]) ? (int) $dashboard_counts[$section_key] : 0;
  ?>
  <section class="mw-dash-section">
    <h2 class="mw-dash-section-title">
      <?php echo esc_view($section_title); ?>
      <span class="mw-dash-section-count">(Count: <?php echo $section_count; ?>)</span>
    </h2>
    <?php $this->load->view('my_works/_dashboard_lanes', array(
      'section_key' => $section_key,
      'dashboard_sections' => $dashboard_sections,
      'can_add' => !empty($can_add),
      'can_view_all' => !empty($can_view_all),
      'hide_empty_lanes' => $hide_empty_lanes,
      'prioritize_nonempty' => !empty($prioritize_nonempty),
      'disable_lane_drag' => false,
      'hide_drag_column' => false,
      'assignee_names_map' => isset($assignee_names_map) ? $assignee_names_map : array(),
      'task_assignee_names_map' => isset($task_assignee_names_map) ? $task_assignee_names_map : array(),
    )); ?>
  </section>
  <?php endforeach; ?>

  <?php if (!empty($prioritize_nonempty) && (int) $dashboard_counts['total'] < 1): ?>
  <div class="mw-dash-empty-filter alert alert-light border text-muted mx-3">
    No work items match the selected filters.
  </div>
  <?php endif; ?>

  <footer class="mw-dash-footer-note">
    <i class="bi bi-info-circle"></i>
    <strong>Yesterday</strong> shows work due yesterday, or with no due date when the task or its activity history was last updated yesterday. <strong>Need Discussion</strong> uses status <strong>Needs Discussion</strong>. <strong>Future Pipeline</strong> shows <strong>Postponed</strong> items and any work with a <strong>due date after today</strong>. <strong>Back Log</strong> and <strong>Future Pipeline</strong> never include <strong>Closed / Complete / Completed</strong> tasks. Other columns use due date, or last updated date when no due date is set. Drag a task row between columns to reschedule or update status.
  </footer>

</div>

<script>
(function () {
  var form = document.getElementById('mwDashFilterForm');
  if (!form) { return; }
  var selects = form.querySelectorAll('select');
  for (var i = 0; i < selects.length; i++) {
    selects[i].addEventListener('change', function () {
      form.submit();
    });
  }
  var searchForm = document.querySelector('.mw-dash-search-form');
  if (searchForm) {
    var si = searchForm.querySelector('.mw-dash-search-input');
    if (si) {
      var timer = null;
      si.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          searchForm.submit();
        }, 500);
      });
    }
  }
})();
</script>

<?php if (!$embed): ?>
<script>
  window.mwDashLaneUpdateUrl = <?php echo json_encode(site_url('my-works/update-lane')); ?>;
</script>
<script src="<?php echo base_url('assets/js/my-works-lane-dnd.js'); ?>"></script>
<?php endif; ?>

<?php if (!$embed) {
  $this->load->view('partials/footer');
} ?>
