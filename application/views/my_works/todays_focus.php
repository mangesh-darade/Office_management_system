<?php $this->load->view('partials/header', array('title' => "Today's Focus", 'extra_css' => array('assets/css/my-works.css'), 'body_class' => 'mw-body-todays-focus')); ?>

<?php
  $dashboard_sections = isset($dashboard_sections) ? $dashboard_sections : array();
  $focus_count = isset($focus_count) ? (int) $focus_count : 0;
  $filterProjectId = isset($filters['project_id']) ? (int) $filters['project_id'] : 0;
  $filterAssignee = isset($filters['created_for']) ? (int) $filters['created_for'] : 0;
  $focusUrl = site_url('my-works/todays-focus');
  $baseQuery = array();
  foreach ($filters as $k => $v) {
    if ($k === 'current_user_id') {
      continue;
    }
    if ($v !== '' && $v !== 0 && $v !== '0') {
      $baseQuery[$k] = $v;
    }
  }
  $this->load->helper('my_works_status');
?>

<div class="mw-dash-page mw-dash-page-focus">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 mb-2" role="alert">
      <?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="mw-focus-topbar">
    <div class="mw-focus-topbar-main">
      <div class="mw-focus-brand">
        <h1 class="mw-dash-title mb-0">Today's Focus</h1>
        <p class="mw-dash-subtitle mb-0">Today's Plan &middot; <?php echo esc_view(date('l, M j, Y'), ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <form method="get" action="<?php echo esc_view($focusUrl, ENT_QUOTES, 'UTF-8'); ?>" class="mw-focus-search-form mw-dash-search-form">
        <?php foreach ($baseQuery as $qk => $qv): ?>
          <?php if ($qk === 'q') { continue; } ?>
          <input type="hidden" name="<?php echo esc_view($qk); ?>" value="<?php echo esc_view((string) $qv, ENT_QUOTES, 'UTF-8'); ?>">
        <?php endforeach; ?>
        <div class="mw-dash-search-wrap">
          <i class="bi bi-search"></i>
          <input type="search" name="q" class="mw-dash-search-input" placeholder="Search tasks, projects, clients…" value="<?php echo esc_view(isset($filters['q']) ? (string) $filters['q'] : '', ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </form>
      <?php if (!empty($can_add)): ?>
        <a class="btn btn-primary btn-sm mw-dash-create-btn mw-focus-create-btn" href="<?php echo site_url('my-works/create'); ?>">
          <i class="bi bi-plus-lg me-1"></i>Create Task
        </a>
        <a class="btn btn-outline-primary btn-sm mw-dash-template-btn" href="<?php echo site_url('my-works/template-tasks'); ?>">
          <i class="bi bi-collection me-1"></i>Template Task
        </a>
      <?php endif; ?>
    </div>

    <?php $this->load->view('my_works/_dash_view_tabs', array(
      'active_tab' => 'todays-focus',
      'tabs_class' => 'mw-dash-view-tabs mw-focus-view-tabs',
    )); ?>
  </div>

  <div class="mw-focus-filters-card">
    <form method="get" action="<?php echo esc_view($focusUrl, ENT_QUOTES, 'UTF-8'); ?>" class="mw-focus-filter-form" id="mwDashFilterForm">
      <?php if (!empty($filters['q'])): ?>
        <input type="hidden" name="q" value="<?php echo esc_view((string) $filters['q'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>
      <div class="mw-focus-filter-fields">
        <label class="mw-focus-filter-group">
          <span class="mw-focus-filter-label">Assigned To</span>
          <select name="created_for" class="form-select form-select-sm mw-dash-filter-select" <?php echo empty($can_filter_users) ? 'disabled' : ''; ?>>
            <option value="0">All Users</option>
            <?php foreach ((array) $users as $u): ?>
              <option value="<?php echo (int) $u->id; ?>" <?php echo $filterAssignee === (int) $u->id ? 'selected' : ''; ?>>
                <?php echo esc_view(my_works_user_label($u->name, $u->email, $u->id), ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="mw-focus-filter-group">
          <span class="mw-focus-filter-label">Project</span>
          <select name="project_id" class="form-select form-select-sm mw-dash-filter-select">
            <option value="0">All Projects</option>
            <?php foreach ((array) $projects as $p): ?>
              <option value="<?php echo (int) $p->id; ?>" <?php echo $filterProjectId === (int) $p->id ? 'selected' : ''; ?>>
                <?php echo esc_view($p->name ? (string) $p->name : ('Project #' . (int) $p->id), ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
    </form>
    <div class="mw-focus-legend">
      <?php foreach (my_works_status_records() as $st): ?>
        <span class="mw-dash-legend-item">
          <span class="mw-dash-status-dot mw-dash-dot-<?php echo esc_view(my_works_status_dashboard_dot_class($st->code), ENT_QUOTES, 'UTF-8'); ?>" style="background-color:<?php echo esc_view(my_works_status_hex_color($st->code), ENT_QUOTES, 'UTF-8'); ?>;"></span>
          <?php echo esc_view((string) $st->name, ENT_QUOTES, 'UTF-8'); ?>
        </span>
      <?php endforeach; ?>
    </div>
  </div>

  <section class="mw-dash-section mw-dash-section-focus">
    <?php $this->load->view('my_works/_dashboard_lanes', array(
      'section_key' => 'focus',
      'dashboard_sections' => $dashboard_sections,
      'lane_keys_filter' => array('todays_plan'),
      'single_lane_layout' => true,
      'fullscreen_lane' => true,
      'disable_lane_drag' => true,
      'hide_drag_column' => true,
      'force_show_date' => true,
      'can_view_all' => !empty($can_view_all),
    )); ?>
  </section>

</div>

<script>
(function () {
  var form = document.getElementById('mwDashFilterForm');
  if (form) {
    var selects = form.querySelectorAll('select');
    for (var i = 0; i < selects.length; i++) {
      selects[i].addEventListener('change', function () {
        form.submit();
      });
    }
  }
  var searchForm = document.querySelector('.mw-focus-search-form');
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

<?php $this->load->view('partials/footer'); ?>
