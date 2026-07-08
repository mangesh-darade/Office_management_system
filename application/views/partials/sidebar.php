<?php
// Sidebar partial for full-width pages
$active = strtolower($this->uri->segment(1) ?: 'dashboard');
$active_sub = strtolower($this->uri->segment(2) ?: '');
if ($active === 'my-works') {
  $active = 'my_works';
}
$coaching_nav_active = ($active === 'coaching' || strpos((string) $active, 'coaching-') === 0);
$role_id = (int) $this->session->userdata('role_id');
$is_superadmin = ($role_id === 1);
// Only render sidebar for authenticated users
if (!(int)$this->session->userdata('user_id')) {
  return; // do not output sidebar when not logged in
}
?>
<script>
/**
 * Shared sidebar submenu initialiser — single definition used by all submenu groups.
 * On page load only the current module expands (forceOpen); localStorage is not restored.
 * Opening one group collapses the others (accordion).
 */
window._sidebarGroups = window._sidebarGroups || [];
function initSidebarGroup(groupId, toggleId, parentId, submenuId, storageKey, forceOpen) {
    var group      = document.getElementById(groupId);
    var btn        = document.getElementById(toggleId);
    var parentLink = document.getElementById(parentId);
    var box        = document.getElementById(submenuId);
    if (!btn || !box) { return; }
    function setOpen(open) {
        box.style.display = open ? 'block' : 'none';
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        btn.classList.toggle('rot', open);
        if (group) { group.classList.toggle('open', open); }
        try { localStorage.setItem(storageKey, open ? '1' : '0'); } catch(e) {}
    }
    var controller = { setOpen: setOpen, forceOpen: !!forceOpen };
    window._sidebarGroups.push(controller);
    setOpen(false);
    function toggle() {
        var willOpen = box.style.display === 'none';
        if (willOpen) {
            window._sidebarGroups.forEach(function(entry) {
                if (entry !== controller) { entry.setOpen(false); }
            });
        }
        setOpen(willOpen);
    }
    btn.addEventListener('click', function(ev) { ev.preventDefault(); ev.stopPropagation(); toggle(); });
    if (parentLink) {
        parentLink.addEventListener('click', function(ev) { ev.preventDefault(); toggle(); });
    }
}
function initNestedSidebarGroup(toggleId, submenuId, forceOpen, parentId) {
    var btn = document.getElementById(toggleId);
    var box = document.getElementById(submenuId);
    var parentLink = parentId ? document.getElementById(parentId) : null;
    var group = btn ? btn.closest('.nav-item') : null;
    if (!btn || !box) { return; }
    function setOpen(open) {
        box.style.display = open ? 'block' : 'none';
        if (btn) {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('rot', open);
        }
        if (parentLink) {
            parentLink.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        if (group) {
            group.classList.toggle('open', open);
        }
    }
    function toggleNested() {
        setOpen(box.style.display === 'none');
    }
    setOpen(!!forceOpen);
    btn.addEventListener('click', function(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        toggleNested();
    });
    if (parentLink) {
        parentLink.addEventListener('click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            toggleNested();
        });
    }
}
function sidebarApplyInitialOpen() {
    var target = null;
    window._sidebarGroups.forEach(function(entry) {
        if (entry.forceOpen) { target = entry; }
    });
    if (target) { target.setOpen(true); }
}
function sidebarClearParentActiveWhenChildActive() {
    document.querySelectorAll('.sidebar-submenu .submenu-link.active').forEach(function(link) {
        var group = link.closest('.nav-item, .sidebar-group');
        if (!group) { return; }
        var parent = group.querySelector('[id$="-parent"]');
        if (parent) { parent.classList.remove('active'); }
    });
}
document.addEventListener('DOMContentLoaded', function() {
    sidebarApplyInitialOpen();
    sidebarClearParentActiveWhenChildActive();
});
(function() {
    try {
        if (localStorage.getItem('oms_sidebar_collapsed') === '1') {
            document.body.classList.add('sidebar-collapsed');
            document.documentElement.setAttribute('data-sidebar-collapsed', '1');
        }
    } catch (e) {}
})();
</script>
<div class="sidebar-shell d-none d-md-block col-md-3 col-lg-2" id="sidebarShell">
<aside class="sidebar-left h-100" id="appSidebar">
  <div class="sidebar-inner p-3">
    <nav class="nav flex-column gap-1 sidebar-nav">
      <a class="nav-link sidebar-link <?php echo $active==='dashboard'?'active':''; ?>" href="<?php echo site_url('dashboard'); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      <?php if(function_exists('has_module_access') && (has_module_access('my_works') || has_module_access('my_works_list'))): ?>
      <a class="nav-link sidebar-link <?php echo $active==='my_works'?'active':''; ?>" href="<?php echo site_url('my-works'); ?>"><i class="oms-icon-brain me-2" aria-hidden="true"></i>Second Brain</a>
      <?php endif; ?>
      <?php
      $this->load->helper('spl');
      if (function_exists('spl_can_access') && spl_can_access()):
      ?>
      <a class="nav-link sidebar-link <?php echo ($active==='spl')?'active':''; ?>" href="<?php echo site_url('spl/dashboard'); ?>"><i class="bi bi-trophy me-2"></i>SPL</a>
      <?php endif; ?>
      <?php $this->load->view('partials/sidebar_sales_group', array('variant' => 'desktop', 'active' => $active, 'active_sub' => $active_sub)); ?>
      <?php if(function_exists('has_module_access') && has_module_access('daily_activity')): ?>
      <div class="nav-item" id="daily-activity-group">
        <a id="daily-activity-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo ($active==='daily-activity'||$active==='daily_activity')?'active':''; ?>" href="<?php echo site_url('daily-activity'); ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-journal-check me-2"></i>Daily Activity</span>
            <span class="sidebar-group-chevron" id="daily-activity-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="daily-activity-submenu">
            <div class="submenu-list">
                <a class="submenu-link <?php echo ($active==='daily-activity' && $active_sub==='list')?'active':''; ?>" href="<?php echo site_url('daily-activity/list'); ?>"><i class="bi bi-list-ul me-1"></i>All Activities</a>
                <a class="submenu-link <?php echo ($active==='daily-activity' && $active_sub==='export')?'active':''; ?>" href="<?php echo site_url('daily-activity/export'); ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
            </div>
        </div>
      </div>
      <script>initSidebarGroup('daily-activity-group','daily-activity-toggle','daily-activity-parent','daily-activity-submenu','sb_daily_activity_open',<?php echo ($active==='daily-activity'||$active==='daily_activity')?'true':'false'; ?>);</script>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('clients')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='clients'?'active':''; ?>" href="<?php echo site_url('clients'); ?>"><i class="bi bi-briefcase me-2"></i>Clients</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('employees')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='employees'?'active':''; ?>" href="<?php echo site_url('employees'); ?>"><i class="bi bi-people me-2"></i>Employees</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('chats')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='chats'?'active':''; ?>" href="<?php echo site_url('chats/app'); ?>"><i class="bi bi-chat-dots me-2"></i>Chats</a>
      <?php endif; ?>

      <?php 
      $recruitment_show = (isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && (
          has_module_access('recruitment') || 
          has_module_access('recruitment_jobs') || 
          has_module_access('recruitment_candidates')
      ));
      ?>
      <?php if($recruitment_show): ?>
      <div class="nav-item" id="recruitment-group">
        <a id="recruitment-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo in_array($active, ['recruitment']) ? 'active' : ''; ?>" href="<?php echo site_url('recruitment'); ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-person-plus me-2"></i>Recruitment</span>
            <span class="sidebar-group-chevron" id="recruitment-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="recruitment-submenu">
            <div class="submenu-list">
                <a class="submenu-link <?php echo ($active==='recruitment' && (!$active_sub || $active_sub==='index'))?'active':''; ?>" href="<?php echo site_url('recruitment'); ?>"><i class="bi bi-briefcase me-1"></i>Job Openings</a>
                <a class="submenu-link <?php echo ($active==='recruitment' && $active_sub==='candidates')?'active':''; ?>" href="<?php echo site_url('recruitment/candidates'); ?>"><i class="bi bi-people me-1"></i>Candidates</a>
                <a class="submenu-link <?php echo ($active==='recruitment' && $active_sub==='export')?'active':''; ?>" href="<?php echo site_url('recruitment/export'); ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
            </div>
        </div>
      </div>
      <script>initSidebarGroup('recruitment-group','recruitment-toggle','recruitment-parent','recruitment-submenu','sb_recruitment_open',<?php echo $active==='recruitment'?'true':'false'; ?>);</script>
      <?php endif; ?>

      <?php if((isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && has_module_access('performance'))): ?>
      <div class="nav-item" id="performance-group">
        <a id="performance-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo $active==='performance'?'active':''; ?>" href="<?php echo site_url('performance'); ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-award me-2"></i>Performance</span>
            <span class="sidebar-group-chevron" id="performance-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="performance-submenu">
          <div class="submenu-list">
            <a class="submenu-link <?php echo ($active==='performance' && (!$active_sub||$active_sub==='index'))?'active':''; ?>" href="<?php echo site_url('performance'); ?>"><i class="bi bi-list-ul me-1"></i>All Appraisals</a>
            <a class="submenu-link <?php echo ($active==='performance'&&$active_sub==='self-assess')?'active':''; ?>" href="<?php echo site_url('performance/self-assess'); ?>"><i class="bi bi-person-check me-1"></i>Self-Assessment</a>
            <a class="submenu-link" href="<?php echo site_url('performance/export'); ?>"><i class="bi bi-download me-1"></i>Export CSV</a>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('performance-group','performance-toggle','performance-parent','performance-submenu','sb_performance_open',<?php echo $active==='performance'?'true':'false'; ?>);</script>
      <?php endif; ?>

      <?php
      $coaching_group_show = (isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && (
          has_module_access('coaching') ||
          has_module_access('coaching_coaches') ||
          has_module_access('coaching_clients') ||
          has_module_access('coaching_sessions') ||
          has_module_access('coaching_goals') ||
          has_module_access('coaching_leads') ||
          has_module_access('coaching_billing') ||
          has_module_access('coaching_reports') ||
          has_module_access('coaching_whatsapp_crm') ||
          has_module_access('coaching_resources') ||
          has_module_access('coaching_admin')
      ));
      ?>
      <?php if($coaching_group_show): ?>
      <div class="nav-item" id="coaching-group">
        <a id="coaching-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo $coaching_nav_active ? 'active' : ''; ?>" href="<?php echo site_url('coaching'); ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-person-hearts me-2"></i>Coaching</span>
            <span class="sidebar-group-chevron" id="coaching-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="coaching-submenu">
          <div class="submenu-list">
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching')): ?>
            <a class="submenu-link <?php echo ($active==='coaching'&&(!$active_sub||$active_sub==='index'))?'active':''; ?>" href="<?php echo site_url('coaching'); ?>"><i class="bi bi-grid me-1"></i>Dashboard</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_clients')): ?>
            <a class="submenu-link <?php echo $active==='coaching-clients'?'active':''; ?>" href="<?php echo site_url('coaching-clients'); ?>"><i class="bi bi-people me-1"></i>Clients</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_coaches')): ?>
            <a class="submenu-link <?php echo $active==='coaching-coaches'?'active':''; ?>" href="<?php echo site_url('coaching-coaches'); ?>"><i class="bi bi-person-workspace me-1"></i>Coaches</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_sessions')): ?>
            <a class="submenu-link <?php echo $active==='coaching-sessions'?'active':''; ?>" href="<?php echo site_url('coaching-sessions'); ?>"><i class="bi bi-calendar3 me-1"></i>Sessions</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_goals')): ?>
            <a class="submenu-link <?php echo $active==='coaching-goals'?'active':''; ?>" href="<?php echo site_url('coaching-goals'); ?>"><i class="bi bi-bullseye me-1"></i>Goals</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_leads')): ?>
            <a class="submenu-link <?php echo $active==='coaching-leads'?'active':''; ?>" href="<?php echo site_url('coaching-leads'); ?>"><i class="bi bi-funnel me-1"></i>Leads</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_billing')): ?>
            <a class="submenu-link <?php echo $active==='coaching-billing'?'active':''; ?>" href="<?php echo site_url('coaching-billing'); ?>"><i class="bi bi-currency-rupee me-1"></i>Billing</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_reports')): ?>
            <a class="submenu-link <?php echo $active==='coaching-reports'?'active':''; ?>" href="<?php echo site_url('coaching-reports'); ?>"><i class="bi bi-bar-chart me-1"></i>Reports</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_whatsapp_crm')): ?>
            <a class="submenu-link <?php echo ($active==='coaching-whatsapp'||$active==='coaching-whatsapp-crm')?'active':''; ?>" href="<?php echo site_url('coaching-whatsapp-crm'); ?>"><i class="bi bi-whatsapp me-1"></i>WhatsApp CRM</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_resources')): ?>
            <a class="submenu-link <?php echo $active==='coaching-resources'?'active':''; ?>" href="<?php echo site_url('coaching-resources'); ?>"><i class="bi bi-journal-text me-1"></i>Resources</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin)&&$is_superadmin)||has_module_access('coaching_admin')): ?>
            <a class="submenu-link <?php echo $active==='coaching-admin'?'active':''; ?>" href="<?php echo site_url('coaching-admin'); ?>"><i class="bi bi-gear me-1"></i>Admin</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('coaching-group','coaching-toggle','coaching-parent','coaching-submenu','sb_coaching_open',<?php echo $coaching_nav_active ? 'true' : 'false'; ?>);</script>
      <?php endif; ?>

      <?php
      $ta_gran_nav = function_exists('has_module_access') && (
          has_module_access('training_screen_ta_dashboard') ||
          has_module_access('training_screen_ta_create') ||
          has_module_access('training_screen_ta_import') ||
          has_module_access('training_screen_ta_report') ||
          has_module_access('training_screen_ta_submissions') ||
          has_module_access('training_screen_ta_team_progress')
      );
      $ta_show = (isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && (
          has_module_access('training_assessment') ||
          has_module_access('training_assessment_manage') ||
          has_module_access('training_assessment_take') ||
          has_module_access('training_screen_ta_my_tests') ||
          $ta_gran_nav
      ));
      $tl_show = (isset($is_superadmin) && $is_superadmin)
          || (function_exists('training_tl_learner_any') && training_tl_learner_any())
          || (function_exists('training_lms_admin_any') && training_lms_admin_any());
      $ext_train_show = (isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && (
          has_module_access('external_training')
          || has_module_access('external_training_watch')
          || has_module_access('external_training_list')
          || has_module_access('external_training_add')
          || has_module_access('external_training_edit')
          || has_module_access('external_training_delete')
      ));
      $ta_take_only = !(isset($is_superadmin) && $is_superadmin)
          && function_exists('has_module_access')
          && (has_module_access('training_assessment_take') || has_module_access('training_screen_ta_my_tests'))
          && !has_module_access('training_assessment')
          && !has_module_access('training_assessment_manage')
          && !$ta_gran_nav;
      if ((isset($is_superadmin) && $is_superadmin) || (function_exists('training_ta_has_any_admin_screen') && training_ta_has_any_admin_screen())) {
          $ta_parent_url = site_url('training-assessment');
      } elseif (function_exists('has_module_access') && (has_module_access('training_assessment_take') || has_module_access('training_screen_ta_my_tests'))) {
          $ta_parent_url = site_url('training-assessment');
      } elseif ($tl_show) {
          $ta_parent_url = site_url('training/my-training');
      } elseif (!empty($ext_train_show) && !$ta_show && !$tl_show) {
          $ta_parent_url = site_url('external-training');
      } else {
          $ta_parent_url = site_url('training-assessment');
      }
      ?>
      <?php if ($ta_show || $tl_show || $ext_train_show): ?>
      <div class="nav-item" id="training-assessment-group">
        <a id="training-assessment-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo ($active==='training-assessment'||$active==='training'||$active==='training-lms-admin'||$active==='external-training')?'active':''; ?>" href="<?php echo $ta_parent_url; ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-mortarboard me-2"></i>Training &amp; Assessment</span>
            <span class="sidebar-group-chevron" id="training-assessment-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="training-assessment-submenu">
          <div class="submenu-list">
            <?php if ((isset($is_superadmin) && $is_superadmin) || (function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_dashboard')) || (function_exists('has_module_access') && (has_module_access('training_assessment_take') || has_module_access('training_screen_ta_my_tests')))): ?>
            <a class="submenu-link <?php echo ($active==='training-assessment' && (!$active_sub || $active_sub==='dashboard'))?'active':''; ?>" href="<?php echo site_url('training-assessment'); ?>"><i class="bi bi-grid me-1"></i>Dashboard</a>
            <?php endif; ?>
            <?php if ((isset($is_superadmin) && $is_superadmin) || (function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_import'))): ?>
            <a class="submenu-link <?php echo ($active==='training-assessment' && strpos((string) $active_sub, 'import') === 0)?'active':''; ?>" href="<?php echo site_url('training-assessment/import'); ?>"><i class="bi bi-file-earmark-arrow-up me-1"></i>Import CSV</a>
            <?php endif; ?>
            <?php if ((isset($is_superadmin) && $is_superadmin) || (function_exists('training_can_import') && training_can_import())): ?>
            <a class="submenu-link <?php echo ($active==='training' && $active_sub==='import')?'active':''; ?>" href="<?php echo site_url('training/import'); ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Master CSV Import</a>
            <?php endif; ?>
            <?php if ((isset($is_superadmin) && $is_superadmin) || (function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_report'))): ?>
            <a class="submenu-link <?php echo ($active==='training-assessment' && strpos((string) $active_sub, 'report') === 0)?'active':''; ?>" href="<?php echo site_url('training-assessment/report'); ?>"><i class="bi bi-bar-chart me-1"></i>Report</a>
            <?php endif; ?>
            <?php if ((isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && has_module_access('training_screen_ta_submissions'))): ?>
            <a class="submenu-link <?php echo ($active==='training-assessment' && $active_sub==='submissions')?'active':''; ?>" href="<?php echo site_url('training-assessment/submissions'); ?>"><i class="bi bi-list-check me-1"></i>Assessment submissions</a>
            <?php endif; ?>
            <?php if ($tl_show && function_exists('training_tl_show_hub_nav') && training_tl_show_hub_nav()): ?>
            <a class="submenu-link <?php echo ($active==='training' && $active_sub==='my-training')?'active':''; ?>" href="<?php echo site_url('training/my-training'); ?>"><i class="bi bi-columns-gap me-1"></i>Training hub</a>
            <?php endif; ?>
            <?php if ($tl_show && function_exists('training_tl_show_module_nav') && training_tl_show_module_nav()): ?>
            <a class="submenu-link <?php echo ($active==='training' && $active_sub!=='my-training')?'active':''; ?>" href="<?php echo site_url('training'); ?>"><i class="bi bi-journal-richtext me-1"></i>Module</a>
            <?php endif; ?>
            <?php if ($ext_train_show): ?>
            <a class="submenu-link <?php echo ($active==='external-training' && (!$active_sub || in_array((string) $active_sub, array('create', 'edit'), true))) ? 'active' : ''; ?>" href="<?php echo site_url('external-training'); ?>"><i class="bi bi-collection-play me-1"></i>External trainings</a>
            <?php endif; ?>
            <?php if ((isset($is_superadmin) && $is_superadmin) || (function_exists('training_lms_admin_can_catalog') && training_lms_admin_can_catalog())): ?>
            <a class="submenu-link <?php echo ($active==='training-lms-admin' && $active_sub!=='assignment-submissions')?'active':''; ?>" href="<?php echo site_url('training-lms-admin'); ?>"><i class="bi bi-gear me-1"></i>LMS admin</a>
            <?php endif; ?>
            <?php if ((isset($is_superadmin) && $is_superadmin) || (function_exists('training_lms_admin_can_submissions') && training_lms_admin_can_submissions())): ?>
            <a class="submenu-link <?php echo ($active==='training-lms-admin' && $active_sub==='assignment-submissions')?'active':''; ?>" href="<?php echo site_url('training-lms-admin/assignment-submissions'); ?>"><i class="bi bi-table me-1"></i>Assignment submissions</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('training-assessment-group','training-assessment-toggle','training-assessment-parent','training-assessment-submenu','sb_training_assessment_open',<?php echo ($active==='training-assessment'||$active==='training'||$active==='training-lms-admin'||$active==='external-training')?'true':'false'; ?>);</script>
      <?php endif; ?>

      <?php
      $user_group_show = function_exists('has_module_access') && (
        has_module_access('users') ||
        has_module_access('users_list') ||
        has_module_access('users_add') ||
        has_module_access('users_edit') ||
        has_module_access('users_delete') ||
        has_module_access('attendance') ||
        has_module_access('attendance_list') ||
        has_module_access('attendance_add') ||
        has_module_access('attendance_edit') ||
        has_module_access('attendance_delete') ||
        has_module_access('departments') ||
        has_module_access('designations') ||
        has_module_access('assets') ||
        has_module_access('shifts') ||
        has_module_access('shifts_view') ||
        has_module_access('shifts_manage')
      );
      ?>
      <?php if($user_group_show): ?>
      <div class="nav-item" id="user-group">
        <a id="user-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo in_array($active, ['users','roles','permissions','attendance','departments','designations','leave','assets-mgmt','shifts'], true) ? 'active' : ''; ?>" href="#">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-person-lines-fill me-2"></i>User</span>
            <span class="sidebar-group-chevron" id="user-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="user-submenu">
          <div class="submenu-list">
            <?php if(function_exists('has_module_access') && (has_module_access('users') || has_module_access('users_list') || has_module_access('users_add') || has_module_access('users_edit') || has_module_access('users_delete'))): ?>
            <a class="submenu-link <?php echo $active==='users'?'active':''; ?>" href="<?php echo site_url('users'); ?>"><i class="bi bi-people me-2"></i>Users</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('roles') || has_module_access('permissions'))): ?>
            <a class="submenu-link <?php echo $active==='roles'?'active':''; ?>" href="<?php echo site_url('roles'); ?>"><i class="bi bi-person-gear me-2"></i>Roles</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('assets') || has_module_access('assets_mgmt'))): ?>
            <a class="submenu-link <?php echo $active==='assets-mgmt'?'active':''; ?>" href="<?php echo site_url('assets-mgmt'); ?>"><i class="bi bi-laptop me-2"></i>Assets</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('attendance') || has_module_access('attendance_list') || has_module_access('attendance_add') || has_module_access('attendance_edit') || has_module_access('attendance_delete'))): ?>
            <a class="submenu-link <?php echo $active==='attendance'?'active':''; ?>" href="<?php echo site_url('attendance'); ?>"><i class="bi bi-calendar-check me-2"></i>Attendance</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && (has_module_access('shifts') || has_module_access('shifts_view') || has_module_access('shifts_manage')))): ?>
            <a class="submenu-link <?php echo $active==='shifts'?'active':''; ?>" href="<?php echo site_url('shifts'); ?>"><i class="bi bi-clock me-2"></i>Shifts</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('departments')): ?>
            <a class="submenu-link <?php echo $active==='departments'?'active':''; ?>" href="<?php echo site_url('departments'); ?>"><i class="bi bi-diagram-3 me-2"></i>Department</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('designations')): ?>
            <a class="submenu-link <?php echo $active==='designations'?'active':''; ?>" href="<?php echo site_url('designations'); ?>"><i class="bi bi-person-badge me-2"></i>Designation</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('user-group','user-toggle','user-parent','user-submenu','sb_user_open',<?php echo in_array($active, ['users','roles','permissions','attendance','departments','designations','leave','assets-mgmt','shifts'], true)?'true':'false'; ?>);</script>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && (has_module_access('payroll') || has_module_access('payroll_view') || has_module_access('payroll_manage'))): ?>
      <div class="nav-item" id="payroll-group">
        <a id="payroll-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo ($active==='payroll' || ($active==='reports' && $active_sub==='payroll'))?'active':''; ?>" href="<?php echo site_url('payroll/payslips'); ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-cash-stack me-2"></i>Payroll</span>
            <span class="sidebar-group-chevron" id="payroll-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="payroll-submenu">
          <div class="submenu-list">
            <a class="submenu-link <?php echo ($active==='payroll'&&($active_sub===''||$active_sub==='payslips'))?'active':''; ?>" href="<?php echo site_url('payroll/payslips'); ?>"><i class="bi bi-file-earmark-text me-1"></i>Payslips</a>
            <a class="submenu-link <?php echo ($active==='payroll'&&$active_sub==='structures')?'active':''; ?>" href="<?php echo site_url('payroll/structures'); ?>"><i class="bi bi-diagram-3 me-1"></i>Pay Structures</a>
            <a class="submenu-link <?php echo ($active==='payroll'&&$active_sub==='generate')?'active':''; ?>" href="<?php echo site_url('payroll/generate'); ?>"><i class="bi bi-gear me-1"></i>Generate Payroll</a>
            <a class="submenu-link <?php echo ($active==='reports'&&$active_sub==='payroll')?'active':''; ?>" href="<?php echo site_url('reports/payroll'); ?>"><i class="bi bi-graph-up me-1"></i>Payroll Report</a>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('payroll-group','payroll-toggle','payroll-parent','payroll-submenu','sb_payroll_open',<?php echo ($active==='payroll' || ($active==='reports' && $active_sub==='payroll'))?'true':'false'; ?>);</script>
      <?php endif; ?>
      
      <?php
      $expenses_group_show = function_exists('has_module_access') && (
        has_module_access('expenses') ||
        has_module_access('expenses_add') ||
        has_module_access('expenses_edit') ||
        has_module_access('expenses_delete') ||
        has_module_access('expenses_approve') ||
        has_module_access('expenses_reimburse') ||
        has_module_access('expenses_reports') ||
        has_module_access('expenses_categories')
      );
      ?>
      <?php if($expenses_group_show): ?>
      <div class="nav-item" id="expense-group">
        <a id="expense-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo $active==='expenses'?'active':''; ?>" href="<?php echo site_url('expenses'); ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-wallet2 me-2"></i>Expenses</span>
            <span class="sidebar-group-chevron" id="expense-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="expense-submenu">
            <div class="submenu-list">
            <a class="submenu-link <?php echo ($active==='expenses' && (!$active_sub || $active_sub==='index'))?'active':''; ?>" href="<?php echo site_url('expenses'); ?>">My Expenses</a>
            <?php if(function_exists('has_module_access') && has_module_access('expenses_approve')): ?>
            <a class="submenu-link <?php echo ($active==='expenses' && $active_sub==='pending')?'active':''; ?>" href="<?php echo site_url('expenses/pending'); ?>">Approvals</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('expenses_categories')): ?>
            <a class="submenu-link <?php echo ($active==='expenses' && $active_sub==='categories')?'active':''; ?>" href="<?php echo site_url('expenses/categories'); ?>">Categories</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('expenses_reports')): ?>
            <a class="submenu-link <?php echo ($active==='expenses' && $active_sub==='report')?'active':''; ?>" href="<?php echo site_url('expenses/report'); ?>">Reports</a>
            <?php endif; ?>
            </div>
        </div>
      </div>
      <script>initSidebarGroup('expense-group','expense-toggle','expense-parent','expense-submenu','sb_expense_open',<?php echo $active==='expenses'?'true':'false'; ?>);</script>
      <?php endif; ?>

      <?php
      $leave_group_show = function_exists('has_module_access') && (
        has_module_access('leave_requests') ||
        has_module_access('leaves') ||
        has_module_access('leaves_list') ||
        has_module_access('leaves_add') ||
        has_module_access('leaves_edit') ||
        has_module_access('leaves_delete') ||
        has_module_access('leave_team') ||
        has_module_access('leave_calendar') ||
        has_module_access('leave_approve')
      );
      ?>
      <?php if($leave_group_show): ?>
      <div class="nav-item" id="leave-group">
        <a id="leave-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo $active==='leave' ? 'active' : ''; ?>" href="<?php echo site_url('leave/apply'); ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-airplane-engines me-2"></i>Leave</span>
            <span class="sidebar-group-chevron" id="leave-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="leave-submenu">
          <?php $seg1 = $this->uri ? $this->uri->segment(1) : ''; $seg2 = $this->uri ? $this->uri->segment(2) : ''; ?>
          <div class="submenu-list">
            <a class="submenu-link <?php echo ($seg1==='leave' && ($seg2==='' || $seg2===null || $seg2==='apply')) ? 'active' : ''; ?>" href="<?php echo site_url('leave/apply'); ?>">Apply Leave</a>
            <a class="submenu-link <?php echo ($seg1==='leave' && $seg2==='my') ? 'active' : ''; ?>" href="<?php echo site_url('leave/my'); ?>">My Leaves</a>
            <?php if((function_exists('is_admin_group') && is_admin_group()) || (function_exists('has_module_access') && has_module_access('leave_team'))): ?>
            <a class="submenu-link <?php echo ($seg1==='leave' && $seg2==='team') ? 'active' : ''; ?>" href="<?php echo site_url('leave/team'); ?>">Team Leaves</a>
            <?php endif; ?>
            <?php if((function_exists('is_admin_group') && is_admin_group()) || (function_exists('has_module_access') && has_module_access('leave_calendar'))): ?>
            <a class="submenu-link <?php echo ($seg1==='leave' && $seg2==='calendar') ? 'active' : ''; ?>" href="<?php echo site_url('leave/calendar'); ?>">Leave Calendar</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('leave-group','leave-toggle','leave-parent','leave-submenu','sb_leave_open',<?php echo $active==='leave'?'true':'false'; ?>);</script>
      <?php endif; ?>

      <?php
      $project_group_show = function_exists('has_module_access') && (
        has_module_access('projects') ||
        has_module_access('requirements') ||
        has_module_access('tasks') ||
        has_module_access('timesheets') ||
        has_module_access('releases') ||
        has_module_access('defects')
      );
      ?>
      <?php if($project_group_show): ?>
      <div class="nav-item" id="project-group">
        <a id="project-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo in_array($active, ['projects','requirements','tasks','timesheets','releases','defects']) ? 'active' : ''; ?>" href="#">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-kanban me-2"></i>Project</span>
            <span class="sidebar-group-chevron" id="project-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="project-submenu">
          <div class="submenu-list">
            <?php if(function_exists('has_module_access') && has_module_access('projects')): ?>
            <a class="submenu-link <?php echo ($active==='projects' && $active_sub!=='matrix')?'active':''; ?>" href="<?php echo site_url('projects'); ?>"><i class="bi bi-kanban me-2"></i>Projects</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('projects') || has_module_access('projects_list'))): ?>
            <a class="submenu-link <?php echo ($active==='projects' && $active_sub==='dashboard')?'active':''; ?>" href="<?php echo site_url('projects/dashboard'); ?>"><i class="bi bi-speedometer2 me-2"></i>Project Dashboard</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('tasks') || has_module_access('tasks_list'))): ?>
            <a class="submenu-link <?php echo ($active==='tasks' && $active_sub==='my-dashboard')?'active':''; ?>" href="<?php echo site_url('tasks/my-dashboard'); ?>"><i class="bi bi-people me-2"></i>Team Dashboard</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('projects_matrix') || has_module_access('projects') || has_module_access('projects_list'))): ?>
            <a class="submenu-link <?php echo ($active==='projects' && $active_sub==='matrix')?'active':''; ?>" href="<?php echo site_url('projects/matrix'); ?>"><i class="bi bi-grid-3x3-gap me-2"></i>Portfolio Matrix</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('requirements')): ?>
            <a class="submenu-link <?php echo $active==='requirements'?'active':''; ?>" href="<?php echo site_url('requirements'); ?>"><i class="bi bi-clipboard-check me-2"></i>Requirement</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('tasks')): ?>
            <a class="submenu-link <?php echo ($active==='tasks' && $active_sub==='board')?'active':''; ?>" href="<?php echo site_url('tasks/board'); ?>"><i class="bi bi-list-check me-2"></i>Task</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('timesheets')): ?>
            <a class="submenu-link <?php echo $active==='timesheets' && (!$active_sub || $active_sub==='index')?'active':''; ?>" href="<?php echo site_url('timesheets'); ?>"><i class="bi bi-calendar3 me-2"></i>Timesheet</a>
            <a class="submenu-link <?php echo ($active==='timesheets' && $active_sub==='report')?'active':''; ?>" href="<?php echo site_url('timesheets/report'); ?>"><i class="bi bi-bar-chart-line me-2"></i>Monthly Report</a>
            <?php if($is_superadmin || (defined('ROLE_MANAGER') && $role_id === ROLE_MANAGER) || (function_exists('is_admin_group') && is_admin_group())): ?>
            <a class="submenu-link <?php echo ($active==='timesheets' && $active_sub==='analytics')?'active':''; ?>" href="<?php echo site_url('timesheets/analytics'); ?>"><i class="bi bi-graph-up me-2"></i>Analytics</a>
            <?php endif; ?>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('releases')): ?>
            <a class="submenu-link <?php echo $active==='releases'?'active':''; ?>" href="<?php echo site_url('releases'); ?>"><i class="bi bi-rocket-takeoff me-2"></i>Releases</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('defects')): ?>
            <a class="submenu-link <?php echo $active==='defects'?'active':''; ?>" href="<?php echo site_url('defects'); ?>"><i class="bi bi-bug me-2"></i>Defects</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('project-group','project-toggle','project-parent','project-submenu','sb_project_open',<?php echo in_array($active,['projects','requirements','tasks','timesheets','releases','defects']) || ($active==='projects' && $active_sub==='matrix')?'true':'false'; ?>);</script>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && (has_module_access('ai') || has_module_access('ai_chat'))): ?>
      <a class="nav-link sidebar-link <?php echo $active==='ai_chat'?'active':''; ?>" href="<?php echo site_url('ai_chat'); ?>"><i class="bi bi-robot me-2"></i>AI Assistant</a>
      <?php endif; ?>
      <?php $this->load->view('partials/sidebar_meals_group', array('variant' => 'desktop', 'active' => $active, 'active_sub' => $active_sub)); ?>
      <?php if(function_exists('has_module_access') && has_module_access('announcements')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='announcements'?'active':''; ?>" href="<?php echo site_url('announcements'); ?>"><i class="bi bi-megaphone me-2"></i>Announcements</a>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('notifications')): ?>
      <?php $sidebar_unread_count = function_exists('get_unread_notification_count') ? (int) get_unread_notification_count() : 0; ?>
      <a class="nav-link sidebar-link <?php echo $active==='notifications'?'active':''; ?>" href="<?php echo site_url('notifications'); ?>">
        <i class="bi bi-bell me-2"></i>Notifications
        <?php if ($sidebar_unread_count > 0): ?>
        <span class="badge bg-danger ms-1"><?php echo $sidebar_unread_count > 99 ? '99+' : $sidebar_unread_count; ?></span>
        <?php endif; ?>
      </a>
      <?php endif; ?>
      <?php
      $comm_show = (function_exists('has_module_access') && has_module_access('mail'))
          || $is_superadmin
          || (function_exists('has_module_access') && has_module_access('whatsapp'));
      if ($comm_show) {
          if (function_exists('has_module_access') && has_module_access('mail')) {
              $comm_parent_url = site_url('mail');
          } elseif ($is_superadmin || (function_exists('has_module_access') && has_module_access('whatsapp'))) {
              $comm_parent_url = site_url('whatsapp');
          } else {
              $comm_parent_url = site_url('dashboard');
          }
          $comm_nav_active = in_array($active, array('mail', 'sendgrid', 'whatsapp'), true);
      }
      ?>
      <?php if (!empty($comm_show) && $comm_show): ?>
      <div class="nav-item" id="communication-group">
        <a id="communication-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo !empty($comm_nav_active) && $comm_nav_active ? 'active' : ''; ?>" href="<?php echo $comm_parent_url; ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-broadcast me-2"></i>Communication</span>
            <span class="sidebar-group-chevron" id="communication-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="communication-submenu">
          <div class="submenu-list">
            <?php if (function_exists('has_module_access') && has_module_access('mail')): ?>
            <a class="submenu-link <?php echo $active==='mail'?'active':''; ?>" href="<?php echo site_url('mail'); ?>"><i class="bi bi-envelope me-1"></i>Mail (SMTP)</a>
            <a class="submenu-link <?php echo $active==='sendgrid'?'active':''; ?>" href="<?php echo site_url('sendgrid'); ?>"><i class="bi bi-send me-1"></i>SendGrid (API)</a>
            <?php endif; ?>
            <?php if ($is_superadmin || (function_exists('has_module_access') && has_module_access('whatsapp'))): ?>
            <a class="submenu-link <?php echo $active==='whatsapp'?'active':''; ?>" href="<?php echo site_url('whatsapp'); ?>"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('communication-group','communication-toggle','communication-parent','communication-submenu','sb_communication_open',<?php echo !empty($comm_nav_active) && $comm_nav_active ? 'true' : 'false'; ?>);</script>
      <?php endif; ?>
      <?php
      $reports_group_show = function_exists('has_module_access') && (
        has_module_access('reports') ||
        has_module_access('analytics') ||
        has_module_access('reports_overview') ||
        has_module_access('reports_requirements') ||
        has_module_access('reports_tasks_assignment') ||
        has_module_access('reports_projects_status') ||
        has_module_access('reports_leaves') ||
        has_module_access('reports_attendance') ||
        has_module_access('reports_attendance_employee') ||
        has_module_access('reports_daily_activity') ||
        has_module_access('daily_activity_report') ||
        has_module_access('reports_payroll') ||
        has_module_access('reports_expenses') ||
        has_module_access('reports_performance')
      );
      ?>
      <?php if($reports_group_show): ?>
      <div class="nav-item" id="reports-group">
        <a id="reports-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo $active==='reports'?'active':''; ?>" href="<?php echo site_url('reports'); ?>">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-graph-up me-2"></i>Reports</span>
            <span class="sidebar-group-chevron" id="reports-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="reports-submenu">
          <?php $seg1 = $this->uri ? $this->uri->segment(1) : ''; $seg2 = $this->uri ? $this->uri->segment(2) : ''; ?>
          <div class="submenu-list">
            <?php if(function_exists('has_module_access') && has_module_access('analytics')): ?>
            <a class="submenu-link <?php echo $active==='analytics'?'active':''; ?>" href="<?php echo site_url('analytics'); ?>"><i class="bi bi-cpu me-2"></i>AI Analytics</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_overview')): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && ($seg2==='' || $seg2===null))?'active':''; ?>" href="<?php echo site_url('reports'); ?>">Overview</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_requirements')): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='requirements')?'active':''; ?>" href="<?php echo site_url('reports/requirements'); ?>">Requirements Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_tasks_assignment')): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='tasks-assignment')?'active':''; ?>" href="<?php echo site_url('reports/tasks-assignment'); ?>">Task Assignment Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_projects_status')): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='projects-status')?'active':''; ?>" href="<?php echo site_url('reports/projects-status'); ?>">Projects by Status</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_leaves')): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='leaves')?'active':''; ?>" href="<?php echo site_url('reports/leaves'); ?>">Leaves Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_attendance')): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='attendance')?'active':''; ?>" href="<?php echo site_url('reports/attendance'); ?>">Attendance Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_attendance_employee')): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='attendance-employee')?'active':''; ?>" href="<?php echo site_url('reports/attendance-employee'); ?>">Employee Attendance</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('daily_activity_report')||has_module_access('reports'))): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='daily-activity')?'active':''; ?>" href="<?php echo site_url('reports/daily-activity'); ?>">Daily Activity Log</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('reports_payroll')||has_module_access('reports')||has_module_access('payroll'))): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='payroll')?'active':''; ?>" href="<?php echo site_url('reports/payroll'); ?>"><i class="bi bi-cash-coin me-1"></i>Payroll Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('reports_expenses')||has_module_access('reports')||has_module_access('expenses'))): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='expenses')?'active':''; ?>" href="<?php echo site_url('reports/expenses'); ?>"><i class="bi bi-receipt me-1"></i>Expenses Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('reports_performance')||has_module_access('reports')||has_module_access('performance'))): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='performance')?'active':''; ?>" href="<?php echo site_url('reports/performance'); ?>"><i class="bi bi-award me-1"></i>Performance Report</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('reports-group','reports-toggle','reports-parent','reports-submenu','sb_reports_open',<?php echo ($active==='reports'||($this->uri&&$this->uri->segment(1)==='reports'))?'true':'false'; ?>);</script>
      <?php endif; ?>
      <?php
      // Admin section: admin-group users with any settings-area permission (not only Permission Manager)
      $settings_group_show = function_exists('has_module_access') && (
        has_module_access('settings') ||
        has_module_access('system_settings') ||
        has_module_access('permissions') ||
        has_module_access('email_settings') ||
        has_module_access('db') ||
        has_module_access('reminders') ||
        has_module_access('activity') ||
        has_module_access('departments') ||
        has_module_access('designations') ||
        has_module_access('admin') ||
        has_module_access('statuses') ||
        has_module_access('types') ||
        has_module_access('subscription_builder') ||
        has_module_access('approvals') ||
        has_module_access('lead_mapping') ||
        ((isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && (has_module_access('shifts') || has_module_access('shifts_manage'))))
      );
      ?>
      <?php if(function_exists('is_admin_group') && is_admin_group() && $settings_group_show): ?>
      <hr class="my-2">
      <div class="text-uppercase text-muted small px-2">Admin</div>
      <div class="nav-item" id="settings-group">
        <a id="settings-parent" class="nav-link sidebar-link sidebar-group-parent <?php echo in_array($active, ['settings','permissions','email-settings','db','reminders','activity','departments','designations','statuses','shifts','lead-mapping','system-settings','api-integrations','approvals'], true) || ($active==='settings' && in_array($active_sub, ['types','leave-types','holidays','attendance-manage','subscription-builder'], true)) || ($active==='settings' && strpos(uri_string(), 'subscription-builder') !== false) ? 'active' : ''; ?>" href="#">
          <span class="sidebar-group-row-inner">
            <span><i class="bi bi-gear me-2"></i>Settings</span>
            <span class="sidebar-group-chevron" id="settings-toggle" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
          </span>
        </a>
        <div class="sidebar-submenu" id="settings-submenu">
          <div class="submenu-list">
            <?php if(function_exists('has_module_access') && has_module_access('settings')): ?>
            <a class="submenu-link <?php echo $active==='settings'?'active':''; ?>" href="<?php echo site_url('settings'); ?>"><i class="bi bi-gear me-2"></i>System Settings</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('system_settings')): ?>
            <a class="submenu-link <?php echo ($active==='system-settings' && isset($active_sub) && $active_sub==='user-access')?'active':''; ?>" href="<?php echo site_url('system-settings/user-access'); ?>"><i class="bi bi-person-lines-fill me-2"></i>User Access</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('permissions')): ?>
            <a class="submenu-link <?php echo $active==='permissions'?'active':''; ?>" href="<?php echo site_url('permissions'); ?>"><i class="bi bi-shield-lock me-2"></i>Permission Manager</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('approvals')): ?>
            <a class="submenu-link <?php echo $active==='approvals'?'active':''; ?>" href="<?php echo site_url('approvals'); ?>"><i class="bi bi-diagram-2 me-2"></i>Approval Workflows</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('admin') || has_module_access('statuses'))): ?>
            <a class="submenu-link <?php echo $active==='statuses'?'active':''; ?>" href="<?php echo site_url('statuses'); ?>"><i class="bi bi-tags me-2"></i>Status Management</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('types') || has_module_access('settings') || has_module_access('admin'))): ?>
            <a class="submenu-link <?php echo ($active==='settings' && $active_sub==='types')?'active':''; ?>" href="<?php echo site_url('settings/types'); ?>"><i class="bi bi-ui-checks-grid me-2"></i>Module Types</a>
            <?php endif; ?>
            <?php $this->load->view('partials/sidebar_settings_leave_group', array('variant' => 'desktop', 'active' => $active)); ?>
            <?php if(function_exists('is_admin_group') && is_admin_group()): ?>
            <a class="submenu-link <?php echo ($active==='settings' && isset($active_sub) && $active_sub==='attendance-manage')?'active':''; ?>" href="<?php echo site_url('settings/attendance-manage'); ?>"><i class="bi bi-clock-history me-2"></i>Attendance Manage</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('email_settings')): ?>
            <a class="submenu-link <?php echo $active==='email-settings'?'active':''; ?>" href="<?php echo site_url('email-settings'); ?>"><i class="bi bi-envelope-gear me-2"></i>Email Settings</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('admin') || has_module_access('settings'))): ?>
            <a class="submenu-link <?php echo $active==='api-integrations'?'active':''; ?>" href="<?php echo site_url('api-integrations'); ?>"><i class="bi bi-plug me-2"></i>API Integrations</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('lead_mapping')): ?>
            <a class="submenu-link <?php echo $active==='lead-mapping'?'active':''; ?>" href="<?php echo site_url('lead-mapping'); ?>"><i class="bi bi-diagram-2 me-2"></i>Lead Mapping</a>
            <?php endif; ?>
            <?php $this->load->view('partials/sidebar_settings_database_group', array('variant' => 'desktop', 'active' => $active, 'active_sub' => $active_sub)); ?>
            <?php if(function_exists('has_module_access') && has_module_access('reminders')): ?>
            <a class="submenu-link <?php echo $active==='reminders'?'active':''; ?>" href="<?php echo site_url('reminders'); ?>"><i class="bi bi-bell me-2"></i>Reminders</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('activity')): ?>
            <a class="submenu-link <?php echo $active==='activity'?'active':''; ?>" href="<?php echo site_url('activity'); ?>"><i class="bi bi-activity me-2"></i>Activity Log</a>
            <?php endif; ?>
            <?php $this->load->view('partials/sidebar_settings_sales_group', array('variant' => 'desktop', 'active' => $active)); ?>
          </div>
        </div>
      </div>
      <script>initSidebarGroup('settings-group','settings-toggle','settings-parent','settings-submenu','sb_settings_open',<?php echo (in_array($active, ['settings','permissions','email-settings','db','reminders','activity','departments','designations','statuses','shifts','approvals','lead-mapping','system-settings','api-integrations'], true) || ($active==='settings' && in_array($active_sub, ['types','leave-types','holidays','attendance-manage','subscription-builder'], true)) || ($active==='settings' && strpos(uri_string(), 'subscription-builder') !== false))?'true':'false'; ?>);</script>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('superadmin')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='superadmin'?'active':''; ?>" href="<?php echo site_url('superadmin'); ?>"><i class="bi bi-shield-lock-fill me-2 text-danger"></i>Super Admin</a>
      <?php endif; ?>
      <?php $this->load->view('partials/sidebar_guide_nav', ['guide_nav_variant' => 'desktop']); ?>
      <hr class="my-2 border-secondary">
      <a class="nav-link sidebar-link text-danger" href="<?php echo site_url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
  </div>
</aside>
<div class="sidebar-collapse-rail">
  <button type="button" class="sidebar-collapse-toggle" id="sidebarCollapseToggle" aria-label="Toggle sidebar" aria-expanded="true" title="Collapse sidebar">
    <i class="bi bi-chevron-left"></i>
  </button>
</div>
</div>

