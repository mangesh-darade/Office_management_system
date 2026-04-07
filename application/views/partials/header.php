<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0d6efd">
  <title><?php echo isset($title) ? htmlspecialchars($title) : 'Office Management'; ?></title>
  <?php if (!empty($meta_robots)): ?>
  <meta name="robots" content="<?php echo htmlspecialchars((string) $meta_robots, ENT_QUOTES, 'UTF-8'); ?>">
  <?php endif; ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.0.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.min.css" rel="stylesheet">
  <link rel="manifest" href="<?php echo base_url('assets/pwa/manifest.webmanifest'); ?>">
  <link href="<?php echo base_url('assets/css/app.css'); ?>" rel="stylesheet">
  <style>
    /* Show user avatar on mobile */
    @media (max-width: 576px) {
      .navbar-nav .dropdown .d-flex.align-items-center img {
        display: inline-block !important;
        width: 36px;
        height: 36px;
      }
      .navbar-nav .dropdown .d-flex.align-items-center span {
        display: none;
      }
    }
  </style>
  <?php
    if (isset($extra_css) && is_array($extra_css)) {
        foreach ($extra_css as $cssFile) {
            echo '<link href="'.htmlspecialchars(base_url($cssFile), ENT_QUOTES, 'UTF-8').'" rel="stylesheet">' . PHP_EOL;
        }
    }
  ?>
  <!-- jQuery must be loaded early so that inline view scripts relying on it (e.g., chats/app.php) can use $.ajax and delegated events -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <script>
  // Global CSRF token helper for AJAX requests
  (function() {
    window.getCsrfToken = function() {
      // Try to get from cookie
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var cookie = cookies[i].trim();
        if (cookie.indexOf('ci_csrf_token=') === 0) {
          return cookie.substring('ci_csrf_token='.length);
        }
      }
      // Fallback: try to get from form
      var csrfInput = document.querySelector('input[name="ci_csrf_token"]');
      if (csrfInput) {
        return csrfInput.value;
      }
      return '';
    };
    
    // Auto-include CSRF token in jQuery AJAX requests
    if (window.$ && $.ajaxSetup) {
      $.ajaxSetup({
        beforeSend: function(xhr, settings) {
          // Only add CSRF token for POST/PUT/DELETE requests
          if (settings.type && /^(POST|PUT|DELETE)$/i.test(settings.type)) {
            var token = window.getCsrfToken();
            if (token) {
              // Add to form data if it's FormData or URLSearchParams
              if (settings.data instanceof FormData) {
                settings.data.append('ci_csrf_token', token);
              } else if (settings.data instanceof URLSearchParams) {
                settings.data.append('ci_csrf_token', token);
              } else if (typeof settings.data === 'string') {
                // If it's a string, append to it
                var separator = settings.data.indexOf('=') !== -1 ? '&' : '';
                settings.data = settings.data + separator + 'ci_csrf_token=' + encodeURIComponent(token);
              } else if (typeof settings.data === 'object' && settings.data !== null) {
                // If it's an object, add the token
                settings.data.ci_csrf_token = token;
              }
            }
          }
        }
      });
    }
  })();
  </script>
</head>
<body>
<?php if (empty($hide_navbar)): ?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar shadow-sm">
  <div class="container-fluid px-3">
    <a class="navbar-brand" href="<?php echo site_url('dashboard'); ?>"><?php echo get_company_name(); ?></a>
    <?php if ((int)$this->session->userdata('user_id')): ?>
    <!-- Mobile sidebar toggle (single button on mobile) -->
    <button class="btn btn-outline-light d-inline-flex d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open menu">
      <i class="bi bi-list"></i>
    </button>
    <?php endif; ?>
    <div class="navbar-collapse">
      <div class="me-auto"></div>
      <div class="d-flex">
        <?php if($this->session->userdata('user_id')): ?>
          <?php 
            $emailStr = strtolower(trim((string)$this->session->userdata('email')));
            $hash = md5($emailStr);
            $avatar = 'https://www.gravatar.com/avatar/' . $hash . '?s=64&d=identicon';
          ?>
          <div class="dropdown">
            <a class="d-flex align-items-center text-decoration-none" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="<?php echo $avatar; ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32">
              <span class="d-none d-sm-inline small fw-semibold navbar-text"><?php echo htmlspecialchars($this->session->userdata('email')); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              <li><a class="dropdown-item" href="<?php echo site_url('profile'); ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?php echo site_url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-primary btn-sm" href="<?php echo site_url('login'); ?>">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>
<?php
// Render mobile offcanvas sidebar when user is logged in and sidebar is enabled
$__with_sidebar = array_key_exists('with_sidebar', get_defined_vars()) ? (bool)$with_sidebar : true;
if ((int)$this->session->userdata('user_id') && $__with_sidebar): ?>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="mobileSidebarLabel">Menu</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="nav flex-column gap-1 p-3 sidebar-nav">
      <?php 
      $active = strtolower($this->uri->segment(1) ?: 'dashboard');
      $active_sub = strtolower($this->uri->segment(2) ?: '');
      $role_id = (int) $this->session->userdata('role_id');
      $is_superadmin = ($role_id === 1);
      ?>
      <a class="nav-link sidebar-link <?php echo $active==='dashboard'?'active':''; ?>" href="<?php echo site_url('dashboard'); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      
      <?php if(function_exists('has_module_access') && has_module_access('daily_activity')): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo ($active==='daily-activity'||$active==='daily_activity') ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-daily-activity-submenu" role="button" aria-expanded="<?php echo ($active==='daily-activity'||$active==='daily_activity')?'true':'false'; ?>" aria-controls="mobile-daily-activity-submenu">
          <i class="bi bi-journal-check me-2"></i>Daily Activity <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse <?php echo ($active==='daily-activity'||$active==='daily_activity') ? 'show' : ''; ?>" id="mobile-daily-activity-submenu">
          <div class="ps-4">
             <a class="nav-link sidebar-link small" href="<?php echo site_url('daily-activity'); ?>"><i class="bi bi-plus-lg me-2"></i>Add Activity</a>
             <a class="nav-link sidebar-link small" href="<?php echo site_url('daily-activity/list'); ?>"><i class="bi bi-list-ul me-2"></i>All Activities</a>
             <a class="nav-link sidebar-link small" href="<?php echo site_url('daily-activity/export'); ?>"><i class="bi bi-download me-2"></i>Export CSV</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('superadmin')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='superadmin'?'active':''; ?>" href="<?php echo site_url('superadmin'); ?>"><i class="bi bi-shield-lock-fill me-2 text-danger"></i>Super Admin</a>
      <?php endif; ?>
      <?php
      $comm_show = (function_exists('has_module_access') && has_module_access('mail'))
          || $is_superadmin
          || (function_exists('has_module_access') && has_module_access('whatsapp'));
      if ($comm_show) {
          if (function_exists('has_module_access') && has_module_access('mail')) {
              $comm_mobile_open = in_array($active, array('mail', 'sendgrid', 'whatsapp'), true);
          } else {
              $comm_mobile_open = ($active === 'whatsapp');
          }
      } else {
          $comm_mobile_open = false;
      }
      ?>
      <?php if ($comm_show): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo $comm_mobile_open ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-communication-submenu" role="button" aria-expanded="<?php echo $comm_mobile_open ? 'true' : 'false'; ?>" aria-controls="mobile-communication-submenu">
          <i class="bi bi-broadcast me-2"></i>Communication <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse <?php echo $comm_mobile_open ? 'show' : ''; ?>" id="mobile-communication-submenu">
          <div class="ps-4">
            <?php if (function_exists('has_module_access') && has_module_access('mail')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='mail'?'active':''; ?>" href="<?php echo site_url('mail'); ?>"><i class="bi bi-envelope me-2"></i>Mail (SMTP)</a>
            <a class="nav-link sidebar-link small <?php echo $active==='sendgrid'?'active':''; ?>" href="<?php echo site_url('sendgrid'); ?>"><i class="bi bi-send me-2"></i>SendGrid (API)</a>
            <?php endif; ?>
            <?php if ($is_superadmin || (function_exists('has_module_access') && has_module_access('whatsapp'))): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='whatsapp'?'active':''; ?>" href="<?php echo site_url('whatsapp'); ?>"><i class="bi bi-whatsapp me-2"></i>WhatsApp</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
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
      <?php if(function_exists('has_module_access') && (has_module_access('ai') || has_module_access('ai_chat'))): ?>
      <a id="sidebarAiLink" class="nav-link sidebar-link <?php echo $active==='ai_chat'?'active':''; ?>" href="<?php echo site_url('ai_chat'); ?>"><i class="bi bi-robot me-2"></i>AI Assistant</a>
      <?php endif; ?>
      
      <?php
      $user_group_show = function_exists('has_module_access') && (
        has_module_access('users') ||
        has_module_access('users_add') ||
        has_module_access('attendance') ||
        has_module_access('departments') ||
        has_module_access('designations') ||
        has_module_access('permissions') ||
        has_module_access('assets') ||
        has_module_access('assets_mgmt')
      );
      ?>
      <?php if($user_group_show): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo in_array($active, ['users','roles','attendance','departments','designations','leave','assets-mgmt','shifts'], true) ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-user-submenu" role="button" aria-expanded="<?php echo in_array($active, ['users','roles','attendance','departments','designations','leave','assets-mgmt','shifts'], true)?'true':'false'; ?>">
          <i class="bi bi-person-lines-fill me-2"></i>User <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse <?php echo in_array($active, ['users','roles','attendance','departments','designations','leave','assets-mgmt','shifts'], true)?'show':''; ?>" id="mobile-user-submenu">
          <div class="ps-4">
            <?php if(function_exists('has_module_access') && has_module_access('users')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='users'?'active':''; ?>" href="<?php echo site_url('users'); ?>"><i class="bi bi-people me-2"></i>Users</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('users_add')): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('users/create'); ?>"><i class="bi bi-person-plus me-2"></i>Add User</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('permissions')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='roles'?'active':''; ?>" href="<?php echo site_url('roles'); ?>"><i class="bi bi-person-gear me-2"></i>Roles</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('assets') || has_module_access('assets_mgmt'))): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='assets-mgmt'?'active':''; ?>" href="<?php echo site_url('assets-mgmt'); ?>"><i class="bi bi-laptop me-2"></i>Assets</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('attendance')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='attendance'?'active':''; ?>" href="<?php echo site_url('attendance'); ?>"><i class="bi bi-calendar-check me-2"></i>Attendance</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('departments')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='departments'?'active':''; ?>" href="<?php echo site_url('departments'); ?>"><i class="bi bi-diagram-3 me-2"></i>Department</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('designations')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='designations'?'active':''; ?>" href="<?php echo site_url('designations'); ?>"><i class="bi bi-person-badge me-2"></i>Designation</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('payroll')): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo ($active==='payroll' || ($active==='reports' && $active_sub==='payroll'))?'active':''; ?>" data-bs-toggle="collapse" href="#mobile-payroll-submenu" role="button" aria-expanded="<?php echo ($active==='payroll' || ($active==='reports' && $active_sub==='payroll'))?'true':'false'; ?>">
          <i class="bi bi-cash-stack me-2"></i>Payroll <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse <?php echo ($active==='payroll' || ($active==='reports' && $active_sub==='payroll'))?'show':''; ?>" id="mobile-payroll-submenu">
          <div class="ps-4">
            <a class="nav-link sidebar-link small" href="<?php echo site_url('payroll/payslips'); ?>"><i class="bi bi-file-earmark-text me-2"></i>Payslips</a>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('payroll/structures'); ?>"><i class="bi bi-diagram-3 me-2"></i>Pay Structures</a>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('payroll/generate'); ?>"><i class="bi bi-gear me-2"></i>Generate Payroll</a>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('reports/payroll'); ?>"><i class="bi bi-graph-up me-2"></i>Payroll Report</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('performance')): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo $active==='performance'?'active':''; ?>" data-bs-toggle="collapse" href="#mobile-performance-submenu" role="button" aria-expanded="<?php echo $active==='performance'?'true':'false'; ?>">
          <i class="bi bi-award me-2"></i>Performance <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse <?php echo $active==='performance'?'show':''; ?>" id="mobile-performance-submenu">
          <div class="ps-4">
            <a class="nav-link sidebar-link small" href="<?php echo site_url('performance'); ?>"><i class="bi bi-list-ul me-2"></i>All Appraisals</a>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('performance/create'); ?>"><i class="bi bi-plus-lg me-2"></i>New Appraisal</a>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('performance/self-assess'); ?>"><i class="bi bi-person-check me-2"></i>Self-Assessment</a>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('performance/export'); ?>"><i class="bi bi-download me-2"></i>Export CSV</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php
      $__ta_gran_m = function_exists('has_module_access') && (
          has_module_access('training_screen_ta_dashboard') ||
          has_module_access('training_screen_ta_create') ||
          has_module_access('training_screen_ta_import') ||
          has_module_access('training_screen_ta_report') ||
          has_module_access('training_screen_ta_submissions')
      );
      $__ta_m = (int)$this->session->userdata('role_id') === 1 || (function_exists('has_module_access') && (
        has_module_access('training_assessment') ||
        has_module_access('training_assessment_manage') ||
        has_module_access('training_assessment_take') ||
        has_module_access('training_screen_ta_my_tests') ||
        $__ta_gran_m
      ));
      $__tl_m = (int)$this->session->userdata('role_id') === 1
        || (function_exists('training_tl_learner_any') && training_tl_learner_any())
        || (function_exists('training_lms_admin_any') && training_lms_admin_any());
      $__ext_nav = (int)$this->session->userdata('role_id') === 1 || (function_exists('has_module_access') && (
          has_module_access('external_training')
          || has_module_access('external_training_watch')
          || has_module_access('external_training_list')
          || has_module_access('external_training_add')
          || has_module_access('external_training_edit')
          || has_module_access('external_training_delete')
      ));
      $__ta_nav_open = ($active==='training-assessment' || $active==='training' || $active==='training-lms-admin' || $active==='external-training');
      ?>
      <?php if ($__ta_m || $__tl_m || $__ext_nav): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo $__ta_nav_open?'active':''; ?>" data-bs-toggle="collapse" href="#mobile-training-assessment-submenu" role="button" aria-expanded="<?php echo $__ta_nav_open?'true':'false'; ?>">
          <i class="bi bi-mortarboard me-2"></i>Training &amp; Assessment <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse <?php echo $__ta_nav_open?'show':''; ?>" id="mobile-training-assessment-submenu">
          <div class="ps-4">
            <?php if ((int)$this->session->userdata('role_id') === 1 || (function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_dashboard')) || (function_exists('has_module_access') && (has_module_access('training_assessment_take') || has_module_access('training_screen_ta_my_tests')))): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('training-assessment'); ?>"><i class="bi bi-grid me-2"></i>Dashboard</a>
            <?php endif; ?>
            <?php if ((int)$this->session->userdata('role_id') === 1 || (function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_create'))): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('training-assessment/create'); ?>"><i class="bi bi-plus-lg me-2"></i>New assessment</a>
            <?php endif; ?>
            <?php if ((int)$this->session->userdata('role_id') === 1 || (function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_import'))): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('training-assessment/import'); ?>"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import CSV</a>
            <?php endif; ?>
            <?php if ((int)$this->session->userdata('role_id') === 1 || (function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_report'))): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('training-assessment/report'); ?>"><i class="bi bi-bar-chart me-2"></i>Report</a>
            <?php endif; ?>
            <?php if ((int)$this->session->userdata('role_id') === 1 || (function_exists('training_ta_can_screen') && training_ta_can_screen('training_screen_ta_submissions'))): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('training-assessment/submissions'); ?>"><i class="bi bi-list-check me-2"></i>Assessment submissions</a>
            <?php endif; ?>
            <?php if ($__tl_m && function_exists('training_tl_show_hub_nav') && training_tl_show_hub_nav()): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('training/my-training'); ?>"><i class="bi bi-columns-gap me-2"></i>Training hub</a>
            <?php endif; ?>
            <?php if ($__tl_m && function_exists('training_tl_show_module_nav') && training_tl_show_module_nav()): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('training'); ?>"><i class="bi bi-journal-richtext me-2"></i>Module</a>
            <?php endif; ?>
            <?php if ((int)$this->session->userdata('role_id') === 1 || (function_exists('training_lms_admin_can_catalog') && training_lms_admin_can_catalog())): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('training-lms-admin'); ?>"><i class="bi bi-gear me-2"></i>LMS admin</a>
            <?php endif; ?>
            <?php if ((int)$this->session->userdata('role_id') === 1 || (function_exists('training_lms_admin_can_submissions') && training_lms_admin_can_submissions())): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('training-lms-admin/assignment-submissions'); ?>"><i class="bi bi-table me-2"></i>Assignment submissions</a>
            <?php endif; ?>
            <?php if ($__ext_nav): ?>
            <a class="nav-link sidebar-link small <?php echo ($active==='external-training' && (!$active_sub || in_array((string) $active_sub, array('create', 'edit'), true)))?'active':''; ?>" href="<?php echo site_url('external-training'); ?>"><i class="bi bi-collection-play me-2"></i>External trainings</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php
      $expenses_group_show = function_exists('has_module_access') && (
        has_module_access('expenses') ||
        has_module_access('expenses_add') ||
        has_module_access('expenses_approve') ||
        has_module_access('expenses_reports')
      );
      ?>
      <?php if($expenses_group_show): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo $active==='expenses'?'active':''; ?>" data-bs-toggle="collapse" href="#mobile-expenses-submenu" role="button">
          <i class="bi bi-receipt me-2"></i>Expenses <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse <?php echo $active==='expenses'?'show':''; ?>" id="mobile-expenses-submenu">
          <div class="ps-4">
            <?php if(function_exists('has_module_access') && has_module_access('expenses')): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('expenses'); ?>"><i class="bi bi-list-ul me-2"></i>All Expenses</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('expenses_add')): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('expenses/create'); ?>"><i class="bi bi-plus-lg me-2"></i>Add Expense</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('expenses_approve')): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('expenses/pending'); ?>"><i class="bi bi-check-circle me-2"></i>Pending Approval</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('expenses_reports')): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('expenses/reports'); ?>"><i class="bi bi-graph-up me-2"></i>Expense Reports</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php
      $recruitment_group_show = function_exists('has_module_access') && (
        has_module_access('recruitment') ||
        has_module_access('recruitment_jobs') ||
        has_module_access('recruitment_candidates')
      );
      ?>
      <?php if($recruitment_group_show): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo $active==='recruitment'?'active':''; ?>" data-bs-toggle="collapse" href="#mobile-recruitment-submenu" role="button">
          <i class="bi bi-person-plus me-2"></i>Recruitment <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse <?php echo $active==='recruitment'?'show':''; ?>" id="mobile-recruitment-submenu">
          <div class="ps-4">
            <a class="nav-link sidebar-link small" href="<?php echo site_url('recruitment'); ?>"><i class="bi bi-briefcase me-2"></i>Job Openings</a>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('recruitment/create-job'); ?>"><i class="bi bi-plus-lg me-2"></i>Post New Job</a>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('recruitment/candidates'); ?>"><i class="bi bi-people me-2"></i>Candidates</a>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('recruitment/export'); ?>"><i class="bi bi-download me-2"></i>Export CSV</a>
          </div>
        </div>
      </div>
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
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo $active==='leave' ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-leave-submenu" role="button">
          <i class="bi bi-airplane-engines me-2"></i>Leave <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse" id="mobile-leave-submenu">
          <div class="ps-4">
            <?php $seg1 = $this->uri ? $this->uri->segment(1) : ''; $seg2 = $this->uri ? $this->uri->segment(2) : ''; ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='leave' && ($seg2==='' || $seg2===null || $seg2==='apply')) ? 'active' : ''; ?>" href="<?php echo site_url('leave/apply'); ?>">Apply Leave</a>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='leave' && $seg2==='my') ? 'active' : ''; ?>" href="<?php echo site_url('leave/my'); ?>">My Leaves</a>
            <?php if(function_exists('is_admin_group') && is_admin_group()): ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='leave' && $seg2==='team') ? 'active' : ''; ?>" href="<?php echo site_url('leave/team'); ?>">Team Leaves</a>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='leave' && $seg2==='calendar') ? 'active' : ''; ?>" href="<?php echo site_url('leave/calendar'); ?>">Leave Calendar</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php
      $project_group_show = function_exists('has_module_access') && (
        has_module_access('projects') ||
        has_module_access('requirements') ||
        has_module_access('tasks') ||
        has_module_access('timesheets')
      );
      ?>
      <?php if($project_group_show): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo in_array($active, ['projects','requirements','tasks','timesheets']) ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-project-submenu" role="button">
          <i class="bi bi-kanban me-2"></i>Project <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse" id="mobile-project-submenu">
          <div class="ps-4">
            <?php if(function_exists('has_module_access') && has_module_access('projects')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='projects'?'active':''; ?>" href="<?php echo site_url('projects'); ?>"><i class="bi bi-kanban me-2"></i>Projects</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('projects_add')): ?>
            <a class="nav-link sidebar-link small" href="<?php echo site_url('projects/create'); ?>"><i class="bi bi-plus-square me-2"></i>Add Project</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('requirements')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='requirements'?'active':''; ?>" href="<?php echo site_url('requirements'); ?>"><i class="bi bi-clipboard-check me-2"></i>Requirement</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('tasks')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='tasks'?'active':''; ?>" href="<?php echo site_url('tasks/board'); ?>"><i class="bi bi-list-check me-2"></i>Task</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('timesheets')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='timesheets'?'active':''; ?>" href="<?php echo site_url('timesheets'); ?>"><i class="bi bi-calendar3 me-2"></i>Timesheet</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if(function_exists('has_module_access') && has_module_access('announcements')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='announcements'?'active':''; ?>" href="<?php echo site_url('announcements'); ?>"><i class="bi bi-megaphone me-2"></i>Announcements</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('notifications')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='notifications'?'active':''; ?>" href="<?php echo site_url('notifications'); ?>"><i class="bi bi-bell me-2"></i>Notifications</a>
      <?php endif; ?>
      
      <?php
      $reports_group_show = function_exists('has_module_access') && (
        has_module_access('reports') ||
        has_module_access('reports_overview') ||
        has_module_access('reports_requirements') ||
        has_module_access('reports_tasks_assignment') ||
        has_module_access('reports_projects_status') ||
        has_module_access('reports_leaves') ||
        has_module_access('reports_attendance') ||
        has_module_access('reports_attendance_employee')
      );
      ?>
      <?php if($reports_group_show): ?>
      <div class="nav-item">
        <a class="nav-link sidebar-link <?php echo $active==='reports'?'active':''; ?>" data-bs-toggle="collapse" href="#mobile-reports-submenu" role="button">
          <i class="bi bi-graph-up me-2"></i>Reports <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse" id="mobile-reports-submenu">
          <div class="ps-4">
            <?php $seg1 = $this->uri ? $this->uri->segment(1) : ''; $seg2 = $this->uri ? $this->uri->segment(2) : ''; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_overview')): ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && ($seg2==='' || $seg2===null))?'active':''; ?>" href="<?php echo site_url('reports'); ?>">Overview</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_requirements')): ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && $seg2==='requirements')?'active':''; ?>" href="<?php echo site_url('reports/requirements'); ?>">Requirements Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_tasks_assignment')): ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && $seg2==='tasks-assignment')?'active':''; ?>" href="<?php echo site_url('reports/tasks-assignment'); ?>">Task Assignment Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_projects_status')): ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && $seg2==='projects-status')?'active':''; ?>" href="<?php echo site_url('reports/projects-status'); ?>">Projects by Status</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_leaves')): ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && $seg2==='leaves')?'active':''; ?>" href="<?php echo site_url('reports/leaves'); ?>">Leaves Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_attendance')): ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && $seg2==='attendance')?'active':''; ?>" href="<?php echo site_url('reports/attendance'); ?>">Attendance Report</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reports_attendance_employee')): ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && $seg2==='attendance-employee')?'active':''; ?>" href="<?php echo site_url('reports/attendance-employee'); ?>">Employee Attendance</a>
            <?php endif; ?>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && $seg2==='daily-activity')?'active':''; ?>" href="<?php echo site_url('reports/daily-activity'); ?>"><i class="bi bi-journal-text me-1"></i>Daily Activity</a>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && $seg2==='payroll')?'active':''; ?>" href="<?php echo site_url('reports/payroll'); ?>"><i class="bi bi-cash-coin me-1"></i>Payroll Report</a>
            <a class="nav-link sidebar-link small <?php echo ($seg1==='reports' && $seg2==='expenses')?'active':''; ?>" href="<?php echo site_url('reports/expenses'); ?>"><i class="bi bi-receipt me-1"></i>Expenses Report</a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <?php
      $settings_group_show = function_exists('has_module_access') && (
        has_module_access('settings') ||
        has_module_access('system_settings') ||
        has_module_access('permissions') ||
        has_module_access('email_settings') ||
        has_module_access('approvals') ||
        has_module_access('db') ||
        has_module_access('reminders') ||
        has_module_access('activity') ||
        has_module_access('departments') ||
        has_module_access('designations') ||
        has_module_access('admin') ||
        has_module_access('statuses')
      );
      ?>
      <?php if(function_exists('is_admin_group') && is_admin_group() && $settings_group_show): ?>
      <hr class="my-2">
      <div class="text-uppercase text-muted small px-2">Admin</div>
      <div class="nav-item">
        <?php $mobile_settings_open = in_array($active, ['settings','permissions','email-settings','approvals','db','reminders','activity','departments','designations','statuses','api-integrations','system-settings','lead-mapping','shifts'], true); ?>
        <a class="nav-link sidebar-link <?php echo $mobile_settings_open ? 'active' : ''; ?>" data-bs-toggle="collapse" href="#mobile-settings-submenu" role="button" aria-expanded="<?php echo $mobile_settings_open ? 'true' : 'false'; ?>">
          <i class="bi bi-gear me-2"></i>Settings <i class="bi bi-chevron-down float-end"></i>
        </a>
        <div class="collapse <?php echo $mobile_settings_open ? 'show' : ''; ?>" id="mobile-settings-submenu">
          <div class="ps-4">
            <?php if(function_exists('has_module_access') && has_module_access('settings')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='settings'?'active':''; ?>" href="<?php echo site_url('settings'); ?>"><i class="bi bi-gear me-2"></i>System Settings</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('permissions')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='permissions'?'active':''; ?>" href="<?php echo site_url('permissions'); ?>"><i class="bi bi-shield-lock me-2"></i>Permission Manager</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('system_settings')): ?>
            <a class="nav-link sidebar-link small <?php echo ($active==='system-settings' && $active_sub==='user-access')?'active':''; ?>" href="<?php echo site_url('system-settings/user-access'); ?>"><i class="bi bi-person-lines-fill me-2"></i>User Access</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('email_settings')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='email-settings'?'active':''; ?>" href="<?php echo site_url('email-settings'); ?>"><i class="bi bi-envelope-gear me-2"></i>Email Settings</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('approvals')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='approvals'?'active':''; ?>" href="<?php echo site_url('approvals'); ?>"><i class="bi bi-diagram-2 me-2"></i>Approval Workflows</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('db')): ?>
            <a class="nav-link sidebar-link small <?php echo ($active==='db' && $active_sub==='')?'active':''; ?>" href="<?php echo site_url('db'); ?>"><i class="bi bi-database me-2"></i>Database Manager</a>
            <a class="nav-link sidebar-link small <?php echo ($active==='db' && $active_sub==='clients')?'active':''; ?>" href="<?php echo site_url('db/clients'); ?>"><i class="bi bi-diagram-3 me-2"></i>Client DB Panel</a>
            <a class="nav-link sidebar-link small <?php echo ($active==='db' && $active_sub==='client-migrations')?'active':''; ?>" href="<?php echo site_url('db/client-migrations'); ?>"><i class="bi bi-clock-history me-2"></i>Client DB Migrations</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reminders')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='reminders'?'active':''; ?>" href="<?php echo site_url('reminders'); ?>"><i class="bi bi-bell me-2"></i>Reminders</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('activity')): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='activity'?'active':''; ?>" href="<?php echo site_url('activity'); ?>"><i class="bi bi-activity me-2"></i>Activity Log</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('admin') || has_module_access('statuses'))): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='statuses'?'active':''; ?>" href="<?php echo site_url('statuses'); ?>"><i class="bi bi-tags me-2"></i>Status Management</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('admin') || has_module_access('settings'))): ?>
            <a class="nav-link sidebar-link small <?php echo $active==='api-integrations'?'active':''; ?>" href="<?php echo site_url('api-integrations'); ?>"><i class="bi bi-plug me-2"></i>API Integrations</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <hr class="my-2 border-secondary">
      <a class="nav-link sidebar-link text-danger" href="<?php echo site_url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
  </div>
  <div class="offcanvas-footer small text-muted px-3 pb-3"><?php echo get_company_name(); ?></div>
</div>
<?php endif; ?>
<!-- Global Incoming Call Modal -->
<div class="modal fade" id="incomingCallModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title"><i class="bi bi-telephone-inbound me-2"></i>Incoming Call</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
            <i class="bi bi-person-video3"></i>
          </div>
          <div>
            <div class="fw-semibold" id="incomingCallFrom">Someone is calling…</div>
            <div class="text-muted small">Conversation <span id="incomingConvId"></span></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" id="btnGlobalReject"><i class="bi bi-telephone-x me-1"></i>Reject</button>
        <a href="#" class="btn btn-success" id="btnGlobalAccept"><i class="bi bi-telephone-inbound me-1"></i>Accept</a>
      </div>
    </div>
  </div>
  <audio id="incomingRingAudio" loop>
    <source src="data:audio/mp3;base64,//uQZAAAAAAAAAAAAAAAAAAAA..." type="audio/mp3">
  </audio>
  <script>
  (function(){
    try{
      const site = '<?php echo rtrim(site_url(), "/"); ?>/';
      const me = <?php echo (int)$this->session->userdata('user_id'); ?>;
      if (!me) return; // not logged in
      // Persist last processed signal id across refresh to avoid replaying old offers
      var sinceKey = 'globalIncomingSinceId';
      var seenCallsKey = 'globalSeenCallIds';
      function loadSince(){ try { return parseInt(localStorage.getItem(sinceKey)||'0',10)||0; } catch(e){ return 0; } }
      function saveSince(v){ try { localStorage.setItem(sinceKey, String(v||0)); } catch(e){} }
      function loadSeen(){ try { var a = JSON.parse(localStorage.getItem(seenCallsKey)||'[]'); return Array.isArray(a)? new Set(a.slice(-50)) : new Set(); } catch(e){ return new Set(); } }
      function saveSeen(set){ try { localStorage.setItem(seenCallsKey, JSON.stringify(Array.from(set).slice(-50))); } catch(e){} }
      var sinceId = loadSince(); var globalTimer = null; var lastSignal = null; var seenCallIds = loadSeen();
      var modalEl = document.getElementById('incomingCallModal');
      var incomingConvIdEl = document.getElementById('incomingConvId');
      var incomingFromEl = document.getElementById('incomingCallFrom');
      var btnAccept = document.getElementById('btnGlobalAccept');
      var btnReject = document.getElementById('btnGlobalReject');
      var ringEl = document.getElementById('incomingRingAudio');
      var bsModal = null;
      function ensureModal(){
        try { if (!bsModal && window.bootstrap && window.bootstrap.Modal) { bsModal = new bootstrap.Modal(modalEl, { backdrop:'static', keyboard:false }); } } catch(e){}
      }
      function startRing(){ try { ringEl && ringEl.play && ringEl.play().catch(()=>{}); } catch(e){} }
      function stopRing(){ try { ringEl && ringEl.pause && (ringEl.currentTime=0); } catch(e){} }
      function showIncoming(sig){
        lastSignal = sig;
        incomingConvIdEl.textContent = String(sig.conversation_id || '');
        incomingFromEl.textContent = sig.from_email ? ('Incoming call from ' + sig.from_email) : 'Incoming call';
        var convId = sig.conversation_id || '';
        var callId = sig.call_id || '';
        btnAccept.href = site + 'chats/app?open=' + convId + (callId ? ('&call=' + callId + '&auto_accept=1') : '');
        ensureModal();
        try { if (bsModal) bsModal.show(); else modalEl.style.display='block'; } catch(e){}
        startRing();
      }
      function hideIncoming(){
        stopRing();
        try { if (bsModal) bsModal.hide(); else modalEl.style.display='none'; } catch(e){}
      }
      async function poll(){
        if (document.hidden) { /* still poll to be responsive */ }
        try{
          const url = new URL(site + 'calls/incoming-any');
          url.searchParams.set('since_id', sinceId);
          const r = await fetch(url);
          const j = await r.json();
          if (j && j.ok && j.signals && j.signals.length){
            j.signals.forEach(function(s){
              var sid = parseInt(s.id,10)||0; if (sid>sinceId) { sinceId=sid; saveSince(sinceId); }
              // Dedupe per call_id so popup doesn't repeat
              var cid = parseInt(s.call_id||0,10)||0;
              if (cid && seenCallIds.has(cid)) { return; }
              // only show one at a time
              if (!lastSignal) { showIncoming(s); }
            });
          }
        }catch(e){}
      }
      function ensurePolling(){
        try { if (globalTimer) clearInterval(globalTimer); globalTimer = setInterval(poll, 3000); } catch(e){}
      }
      ensurePolling(); poll();
        btnReject && btnReject.addEventListener('click', async function(){
        try{
          if (lastSignal && lastSignal.call_id){
            var _cm = document.cookie.match(/(?:^|;\s*)ci_csrf_token=([^;]*)/);
            var _cb = _cm ? '<?php echo $this->security->get_csrf_token_name(); ?>=' + encodeURIComponent(decodeURIComponent(_cm[1])) : '';
            await fetch(site + 'calls/end/' + lastSignal.call_id, {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: _cb
            });
          }
        }catch(e){}
        // Mark this call as seen so it won't pop again
        try { if (lastSignal && lastSignal.call_id){ seenCallIds.add(parseInt(lastSignal.call_id,10)); saveSeen(seenCallIds); } } catch(e){}
        hideIncoming(); lastSignal=null;
      });
      btnAccept && btnAccept.addEventListener('click', function(){
        // Mark as seen; Chats app will handle accept
        try { if (lastSignal && lastSignal.call_id){ seenCallIds.add(parseInt(lastSignal.call_id,10)); saveSeen(seenCallIds); } } catch(e){}
      });
      // If user navigates to Chats via Accept, the Chats app will handle actual accept signaling
      modalEl.addEventListener('hidden.bs.modal', function(){ stopRing(); });
    }catch(e){}
  })();
  </script>
</div>
<?php if (empty($hide_navbar)): ?>
<div id="toast-container">
  <?php if($this->session->flashdata('success')): ?>
    <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php
  // Global layout flags
  // Sidebar is ON by default; set $with_sidebar=false in a view to disable
  $__with_sidebar = array_key_exists('with_sidebar', get_defined_vars()) ? (bool)$with_sidebar : true;
  // When rendering with sidebar, we take over layout (full width)
  $__full_width = $__with_sidebar ? true : !empty($full_width);
?>
<?php if ($__with_sidebar): ?>
<div class="container-fluid px-0">
  <div class="row gx-0">
    <?php $this->load->view('partials/sidebar'); ?>
    <main class="col-12 col-md-9 col-lg-10 p-3 p-md-4">
<?php else: ?>
<main class="pt-1 pb-3">
  <?php if (!$__full_width): ?>
  <div class="container">
  <?php endif; ?>
<?php endif; ?>
