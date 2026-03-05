<?php
// Sidebar partial for full-width pages
$active = strtolower($this->uri->segment(1) ?: 'dashboard');
$active_sub = strtolower($this->uri->segment(2) ?: '');
// Only render sidebar for authenticated users
if (!(int)$this->session->userdata('user_id')) {
  return; // do not output sidebar when not logged in
}
?>
<aside class="d-none d-md-block col-md-3 col-lg-2 sidebar-left">
  <div class="sidebar-inner p-3">
    <nav class="nav flex-column gap-1 sidebar-nav">
      <a class="nav-link sidebar-link <?php echo $active==='dashboard'?'active':''; ?>" href="<?php echo site_url('dashboard'); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      <?php if(function_exists('has_module_access') && has_module_access('daily_activity')): ?>
      <div class="nav-item" id="daily-activity-group">
        <div class="d-flex align-items-center justify-content-between">
            <a id="daily-activity-parent" class="nav-link sidebar-link flex-grow-1 <?php echo $active==='daily_activity'?'active':''; ?>" href="#">
                <i class="bi bi-journal-check me-2"></i>Daily Activity
            </a>
            <button id="daily-activity-toggle" class="btn btn-sm text-muted" type="button" aria-expanded="false" aria-controls="daily-activity-submenu" title="Toggle">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <div class="ps-3 sidebar-submenu" id="daily-activity-submenu">
            <div class="submenu-list">
                <a class="submenu-link <?php echo (strtolower($this->uri->segment(1))==='daily_activity' && (!$this->uri->segment(2) || $this->uri->segment(2)==='index'))?'active':''; ?>" href="<?php echo site_url('daily_activity'); ?>">Add Activity</a>
                <a class="submenu-link <?php echo (strtolower($this->uri->segment(1))==='daily_activity' && $this->uri->segment(2)==='list_all')?'active':''; ?>" href="<?php echo site_url('daily_activity/list_all'); ?>">List Activity</a>
            </div>
        </div>
      </div>
      <script>
        (function(){
            var key = 'sb_daily_activity_open';
            var group = document.getElementById('daily-activity-group');
            var btn = document.getElementById('daily-activity-toggle');
            var parentLink = document.getElementById('daily-activity-parent');
            var box = document.getElementById('daily-activity-submenu');
            if(!btn || !box) return;
            function setOpen(open){
                box.style.display = open ? 'block' : 'none';
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                btn.classList.toggle('rot', open);
                if (group) { group.classList.toggle('open', open); }
                try { localStorage.setItem(key, open ? '1' : '0'); } catch(e){}
            }
            var saved = null;
            try { saved = localStorage.getItem(key); } catch(e){ saved = null; }
            var open = (saved === '1') || <?php echo $active==='daily_activity' ? 'true' : 'false'; ?>;
            setOpen(open);
            function toggle(){ setOpen(!(box.style.display !== 'none')); }
            btn.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
            parentLink.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
        })();
      </script>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('superadmin')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='superadmin'?'active':''; ?>" href="<?php echo site_url('superadmin'); ?>"><i class="bi bi-shield-lock-fill me-2 text-danger"></i>Super Admin</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('mail')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='mail'?'active':''; ?>" href="<?php echo site_url('mail'); ?>"><i class="bi bi-envelope me-2"></i>Mail (SMTP)</a>
      <a class="nav-link sidebar-link <?php echo $active==='sendgrid'?'active':''; ?>" href="<?php echo site_url('sendgrid'); ?>"><i class="bi bi-envelope me-2"></i>Send Grid (API)</a>
      <?php endif; ?>
      <?php 
      $role_id = (int)$this->session->userdata('role_id');
      // Super Admin (role_id 1) always sees WhatsApp; other roles rely on explicit permission
      $is_superadmin = ($role_id === 1);
      if ($is_superadmin || (function_exists('has_module_access') && has_module_access('whatsapp'))): ?>
      <a class="nav-link sidebar-link <?php echo $active==='whatsapp'?'active':''; ?>" href="<?php echo site_url('whatsapp'); ?>"><i class="bi bi-whatsapp me-2"></i>WhatsApp</a>
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
        <div class="d-flex align-items-center justify-content-between">
            <a id="recruitment-parent" class="nav-link sidebar-link flex-grow-1 <?php echo in_array($active, ['recruitment']) ? 'active' : ''; ?>" href="<?php echo site_url('recruitment'); ?>">
                <i class="bi bi-person-plus me-2"></i>Recruitment
            </a>
            <button id="recruitment-toggle" class="btn btn-sm text-muted" type="button" aria-expanded="false" aria-controls="recruitment-submenu" title="Toggle">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <div class="ps-3 sidebar-submenu" id="recruitment-submenu">
            <div class="submenu-list">
                <a class="submenu-link <?php echo ($active==='recruitment' && (!$active_sub || $active_sub==='jobs'))?'active':''; ?>" href="<?php echo site_url('recruitment'); ?>">Jobs</a>
                <a class="submenu-link <?php echo ($active==='recruitment' && $active_sub==='candidates')?'active':''; ?>" href="<?php echo site_url('recruitment/candidates'); ?>">Candidates</a>
            </div>
        </div>
      </div>
      <script>
        (function(){
            var key = 'sb_recruitment_open';
            var group = document.getElementById('recruitment-group');
            var btn = document.getElementById('recruitment-toggle');
            var parentLink = document.getElementById('recruitment-parent');
            var box = document.getElementById('recruitment-submenu');
            if(!btn || !box) return;
            function setOpen(open){
                box.style.display = open ? 'block' : 'none';
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                btn.classList.toggle('rot', open);
                if (group) { group.classList.toggle('open', open); }
                try { localStorage.setItem(key, open ? '1' : '0'); } catch(e){}
            }
            var saved = null;
            try { saved = localStorage.getItem(key); } catch(e){ saved = null; }
            var open = (saved === '1') || <?php echo $active==='recruitment' ? 'true' : 'false'; ?>;
            setOpen(open);
            function toggle(){ setOpen(!(box.style.display !== 'none')); }
            btn.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
            parentLink.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
        })();
      </script>
      <?php endif; ?>

      <?php if((isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && has_module_access('performance'))): ?>
      <a class="nav-link sidebar-link <?php echo $active==='performance'?'active':''; ?>" href="<?php echo site_url('performance'); ?>"><i class="bi bi-award me-2"></i>Performance</a>
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
        has_module_access('assets_mgmt') ||
        has_module_access('shifts') ||
        has_module_access('shifts_view') ||
        has_module_access('shifts_manage')
      );
      ?>
      <?php if($user_group_show): ?>
      <div class="nav-item" id="user-group">
        <div class="d-flex align-items-center justify-content-between">
          <a id="user-parent" class="nav-link sidebar-link flex-grow-1 <?php echo in_array($active, ['users','roles','attendance','departments','designations','leave','assets','shifts']) ? 'active' : ''; ?>" href="#">
            <i class="bi bi-person-lines-fill me-2"></i>User
          </a>
          <button id="user-toggle" class="btn btn-sm text-muted" type="button" aria-expanded="false" aria-controls="user-submenu" title="Toggle">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
        <div class="ps-3 sidebar-submenu" id="user-submenu">
          <div class="submenu-list">
            <?php if(function_exists('has_module_access') && has_module_access('users')): ?>
            <a class="submenu-link <?php echo $active==='users'?'active':''; ?>" href="<?php echo site_url('users'); ?>"><i class="bi bi-people me-2"></i>Users</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('users_add')): ?>
            <a class="submenu-link" href="<?php echo site_url('users/create'); ?>"><i class="bi bi-person-plus me-2"></i>Add User</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('roles') || has_module_access('permissions'))): ?>
            <a class="submenu-link <?php echo $active==='roles'?'active':''; ?>" href="<?php echo site_url('roles'); ?>"><i class="bi bi-person-gear me-2"></i>Roles</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('assets') || has_module_access('assets_mgmt'))): ?>
            <a class="submenu-link <?php echo $active==='assets'?'active':''; ?>" href="<?php echo site_url('assets-mgmt'); ?>"><i class="bi bi-laptop me-2"></i>Assets</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('attendance')): ?>
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
      <script>
        (function(){
          var key = 'sb_user_open';
          var group = document.getElementById('user-group');
          var btn = document.getElementById('user-toggle');
          var parentLink = document.getElementById('user-parent');
          var box = document.getElementById('user-submenu');
          if(!btn || !box) return;
          function setOpen(open){
            box.style.display = open ? 'block' : 'none';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('rot', open);
            if (group) { group.classList.toggle('open', open); }
            try { localStorage.setItem(key, open ? '1' : '0'); } catch(e){}
          }
          var saved = null;
          try { saved = localStorage.getItem(key); } catch(e){ saved = null; }
          var open = (saved === '1') || <?php echo in_array($active, ['users','roles','attendance','departments','designations','leave','shifts']) ? 'true' : 'false'; ?>;
          setOpen(open);
          function toggle(){ setOpen(!(box.style.display !== 'none')); }
          btn.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
          parentLink.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
        })();
      </script>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('payroll')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='payroll'?'active':''; ?>" href="<?php echo site_url('payroll/payslips'); ?>"><i class="bi bi-cash-stack me-2"></i>Payroll</a>
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
        <div class="d-flex align-items-center justify-content-between">
            <a id="expense-parent" class="nav-link sidebar-link flex-grow-1 <?php echo $active==='expenses'?'active':''; ?>" href="<?php echo site_url('expenses'); ?>">
            <i class="bi bi-wallet2 me-2"></i>Expenses
            </a>
            <button id="expense-toggle" class="btn btn-sm text-muted" type="button" aria-expanded="false" aria-controls="expense-submenu" title="Toggle">
            <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <div class="ps-3 sidebar-submenu" id="expense-submenu">
            <div class="submenu-list">
            <a class="submenu-link <?php echo ($active==='expenses' && (!$active_sub || $active_sub==='index'))?'active':''; ?>" href="<?php echo site_url('expenses'); ?>">My Expenses</a>
            <a class="submenu-link <?php echo ($active==='expenses' && $active_sub==='create')?'active':''; ?>" href="<?php echo site_url('expenses/create'); ?>">Create Request</a>
            <?php if(function_exists('has_module_access') && has_module_access('expenses_approve')): ?>
            <a class="submenu-link <?php echo ($active==='expenses' && $active_sub==='pending')?'active':''; ?>" href="<?php echo site_url('expenses/pending'); ?>">Approvals</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('expenses_reports')): ?>
            <a class="submenu-link <?php echo ($active==='expenses' && $active_sub==='report')?'active':''; ?>" href="<?php echo site_url('expenses/report'); ?>">Reports</a>
            <?php endif; ?>
            </div>
        </div>
      </div>
      <script>
        (function(){
            var key = 'sb_expense_open';
            var group = document.getElementById('expense-group');
            var btn = document.getElementById('expense-toggle');
            var parentLink = document.getElementById('expense-parent');
            var box = document.getElementById('expense-submenu');
            if(!btn || !box) return;
            function setOpen(open){
            box.style.display = open ? 'block' : 'none';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('rot', open);
            if (group) { group.classList.toggle('open', open); }
            try { localStorage.setItem(key, open ? '1' : '0'); } catch(e){}
            }
            var saved = null;
            try { saved = localStorage.getItem(key); } catch(e){ saved = null; }
            var open = (saved === '1') || <?php echo $active==='expenses' ? 'true' : 'false'; ?>;
            setOpen(open);
            function toggle(){ setOpen(!(box.style.display !== 'none')); }
            btn.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
            parentLink.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
        })();
      </script>
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
        <div class="d-flex align-items-center justify-content-between">
          <a id="leave-parent" class="nav-link sidebar-link flex-grow-1 <?php echo $active==='leave' ? 'active' : ''; ?>" href="<?php echo site_url('leave/apply'); ?>">
            <i class="bi bi-airplane-engines me-2"></i>Leave
          </a>
          <button id="leave-toggle" class="btn btn-sm text-muted" type="button" aria-expanded="false" aria-controls="leave-submenu" title="Toggle">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
        <div class="ps-3 sidebar-submenu" id="leave-submenu">
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
      <script>
        (function(){
          var key = 'sb_leave_open';
          var group = document.getElementById('leave-group');
          var btn = document.getElementById('leave-toggle');
          var parentLink = document.getElementById('leave-parent');
          var box = document.getElementById('leave-submenu');
          if(!btn || !box) return;
          function setOpen(open){
            box.style.display = open ? 'block' : 'none';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('rot', open);
            if (group) { group.classList.toggle('open', open); }
            try { localStorage.setItem(key, open ? '1' : '0'); } catch(e){}
          }
          var saved = null;
          try { saved = localStorage.getItem(key); } catch(e){ saved = null; }
          var open = (saved === '1') || <?php echo $active==='leave' ? 'true' : 'false'; ?>;
          setOpen(open);
          function toggle(){ setOpen(!(box.style.display !== 'none')); }
          btn.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
          parentLink.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
        })();
      </script>
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
      <div class="nav-item" id="project-group">
        <div class="d-flex align-items-center justify-content-between">
          <a id="project-parent" class="nav-link sidebar-link flex-grow-1 <?php echo in_array($active, ['projects','requirements','tasks','timesheets']) ? 'active' : ''; ?>" href="#">
            <i class="bi bi-kanban me-2"></i>Project
          </a>
          <button id="project-toggle" class="btn btn-sm text-muted" type="button" aria-expanded="false" aria-controls="project-submenu" title="Toggle">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
        <div class="ps-3 sidebar-submenu" id="project-submenu">
          <div class="submenu-list">
            <?php if(function_exists('has_module_access') && has_module_access('projects')): ?>
            <a class="submenu-link <?php echo $active==='projects'?'active':''; ?>" href="<?php echo site_url('projects'); ?>"><i class="bi bi-kanban me-2"></i>Projects</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('projects_add')): ?>
            <a class="submenu-link" href="<?php echo site_url('projects/create'); ?>"><i class="bi bi-plus-square me-2"></i>Add Project</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('requirements')): ?>
            <a class="submenu-link <?php echo $active==='requirements'?'active':''; ?>" href="<?php echo site_url('requirements'); ?>"><i class="bi bi-clipboard-check me-2"></i>Requirement</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('tasks')): ?>
            <a class="submenu-link <?php echo $active==='tasks'?'active':''; ?>" href="<?php echo site_url('tasks/board'); ?>"><i class="bi bi-list-check me-2"></i>Task</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('timesheets')): ?>
            <a class="submenu-link <?php echo $active==='timesheets'?'active':''; ?>" href="<?php echo site_url('timesheets'); ?>"><i class="bi bi-calendar3 me-2"></i>Timesheet</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>
        (function(){
          var key = 'sb_project_open';
          var group = document.getElementById('project-group');
          var btn = document.getElementById('project-toggle');
          var parentLink = document.getElementById('project-parent');
          var box = document.getElementById('project-submenu');
          if(!btn || !box) return;
          function setOpen(open){
            box.style.display = open ? 'block' : 'none';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('rot', open);
            if (group) { group.classList.toggle('open', open); }
            try { localStorage.setItem(key, open ? '1' : '0'); } catch(e){}
          }
          var saved = null;
          try { saved = localStorage.getItem(key); } catch(e){ saved = null; }
          var open = (saved === '1') || <?php echo in_array($active, ['requirements','tasks','timesheets']) ? 'true' : 'false'; ?>;
          setOpen(open);
          function toggle(){ setOpen(!(box.style.display !== 'none')); }
          btn.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
          parentLink.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
        })();
      </script>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && (has_module_access('ai') || has_module_access('ai_chat'))): ?>
      <a class="nav-link sidebar-link <?php echo $active==='ai_chat'?'active':''; ?>" href="<?php echo site_url('ai_chat'); ?>"><i class="bi bi-robot me-2"></i>AI Assistant</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('announcements')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='announcements'?'active':''; ?>" href="<?php echo site_url('announcements'); ?>"><i class="bi bi-megaphone me-2"></i>Announcements</a>
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
        has_module_access('daily_activity_report')
      );
      ?>
      <?php if($reports_group_show): ?>
      <div class="nav-item" id="reports-group">
        <div class="d-flex align-items-center justify-content-between">
          <a id="reports-parent" class="nav-link sidebar-link flex-grow-1 <?php echo $active==='reports'?'active':''; ?>" href="<?php echo site_url('reports'); ?>">
            <i class="bi bi-graph-up me-2"></i>Reports
          </a>
          <button id="reports-toggle" class="btn btn-sm text-muted" type="button" aria-expanded="false" aria-controls="reports-submenu" title="Toggle">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
        <div class="ps-3 sidebar-submenu" id="reports-submenu">
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
            <?php if(function_exists('has_module_access') && has_module_access('daily_activity_report')): ?>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='daily_activity')?'active':''; ?>" href="<?php echo site_url('reports/daily_activity'); ?>">Daily Activity Log</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>
        (function(){
          var key = 'sb_reports_open';
          var group = document.getElementById('reports-group');
          var btn = document.getElementById('reports-toggle');
          var parentLink = document.getElementById('reports-parent');
          var box = document.getElementById('reports-submenu');
          if(!btn || !box) return;
          function setOpen(open){
            box.style.display = open ? 'block' : 'none';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('rot', open);
            if (group) { group.classList.toggle('open', open); }
            try { localStorage.setItem(key, open ? '1' : '0'); } catch(e){}
          }
          var saved = null;
          try { saved = localStorage.getItem(key); } catch(e){ saved = null; }
          var open = (saved === '1') || <?php echo (($active==='reports') || ($this->uri && $this->uri->segment(1)==='reports')) ? 'true' : 'false'; ?>;
          setOpen(open);
          function toggle(){ setOpen(!(box.style.display !== 'none')); }
          btn.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
          parentLink.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
        })();
      </script>
      <?php endif; ?>
      <?php // Admin section: show only to Admin group (Admin, HR, Lead) with permissions module access
      if(function_exists('is_admin_group') && is_admin_group() && function_exists('has_module_access') && has_module_access('permissions')): ?>
      <hr class="my-2">
      <div class="text-uppercase text-muted small px-2">Admin</div>
      <?php
      $settings_group_show = function_exists('has_module_access') && (
        has_module_access('settings') ||
        has_module_access('permissions') ||
        has_module_access('email_settings') ||
        has_module_access('db') ||
        has_module_access('reminders') ||
        has_module_access('activity') ||
        has_module_access('departments') ||
        has_module_access('designations') ||
        has_module_access('admin') ||
        has_module_access('statuses')
      );
      ?>
      <?php if($settings_group_show): ?>
      <div class="nav-item" id="settings-group">
        <div class="d-flex align-items-center justify-content-between">
          <a id="settings-parent" class="nav-link sidebar-link flex-grow-1 <?php echo in_array($active, ['settings','permissions','email-settings','db','reminders','activity','departments','designations','statuses','shifts']) ? 'active' : ''; ?>" href="#">
            <i class="bi bi-gear me-2"></i>Settings
          </a>
          <button id="settings-toggle" class="btn btn-sm text-muted" type="button" aria-expanded="false" aria-controls="settings-submenu" title="Toggle">
            <i class="bi bi-chevron-right"></i>
          </button>
        </div>
        <div class="ps-3 sidebar-submenu" id="settings-submenu">
          <div class="submenu-list">
            <?php if(function_exists('has_module_access') && has_module_access('settings')): ?>
            <a class="submenu-link <?php echo $active==='settings'?'active':''; ?>" href="<?php echo site_url('settings'); ?>"><i class="bi bi-gear me-2"></i>System Settings</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('leave_types') || has_module_access('settings') || has_module_access('admin'))): ?>
            <a class="submenu-link <?php echo ($active==='settings' && strpos(uri_string(), 'leave-types') !== false)?'active':''; ?>" href="<?php echo site_url('settings/leave-types'); ?>"><i class="bi bi-calendar-x me-2"></i>Leave Types</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('holidays') || has_module_access('settings') || has_module_access('admin'))): ?>
            <a class="submenu-link <?php echo ($active==='settings' && strpos(uri_string(), 'holidays') !== false)?'active':''; ?>" href="<?php echo site_url('settings/holidays'); ?>"><i class="bi bi-calendar-event me-2"></i>Holidays</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('permissions')): ?>
            <a class="submenu-link <?php echo $active==='permissions'?'active':''; ?>" href="<?php echo site_url('permissions'); ?>"><i class="bi bi-shield-lock me-2"></i>Permission Manager</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('email_settings')): ?>
            <a class="submenu-link <?php echo $active==='email-settings'?'active':''; ?>" href="<?php echo site_url('email-settings'); ?>"><i class="bi bi-envelope-gear me-2"></i>Email Settings</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('approvals')): ?>
            <a class="submenu-link <?php echo $active==='approvals'?'active':''; ?>" href="<?php echo site_url('approvals'); ?>"><i class="bi bi-diagram-2 me-2"></i>Approval Workflows</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('db')): ?>
            <a class="submenu-link <?php echo ($active==='db' && $active_sub==='')?'active':''; ?>" href="<?php echo site_url('db'); ?>"><i class="bi bi-database me-2"></i>Database Manager</a>
            <a class="submenu-link <?php echo ($active==='db' && $active_sub==='clients')?'active':''; ?>" href="<?php echo site_url('db/clients'); ?>"><i class="bi bi-diagram-3 me-2"></i>Client DB Panel</a>
            <a class="submenu-link <?php echo ($active==='db' && $active_sub==='client-migrations')?'active':''; ?>" href="<?php echo site_url('db/client-migrations'); ?>"><i class="bi bi-clock-history me-2"></i>Client DB Migrations</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('reminders')): ?>
            <a class="submenu-link <?php echo $active==='reminders'?'active':''; ?>" href="<?php echo site_url('reminders'); ?>"><i class="bi bi-bell me-2"></i>Reminders</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('activity')): ?>
            <a class="submenu-link <?php echo $active==='activity'?'active':''; ?>" href="<?php echo site_url('activity'); ?>"><i class="bi bi-activity me-2"></i>Activity Log</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('admin') || has_module_access('statuses'))): ?>
            <a class="submenu-link <?php echo $active==='statuses'?'active':''; ?>" href="<?php echo site_url('statuses'); ?>"><i class="bi bi-tags me-2"></i>Status Management</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && (has_module_access('admin') || has_module_access('settings'))): ?>
            <a class="submenu-link <?php echo $active==='api-integrations'?'active':''; ?>" href="<?php echo site_url('api-integrations'); ?>"><i class="bi bi-plug me-2"></i>API Integrations</a>
            <?php endif; ?>
            <?php if((isset($is_superadmin) && $is_superadmin) || (function_exists('has_module_access') && (has_module_access('shifts') || has_module_access('shifts_manage')))): ?>
            <a class="submenu-link <?php echo $active==='shifts'?'active':''; ?>" href="<?php echo site_url('shifts'); ?>"><i class="bi bi-clock me-2"></i>Shifts</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <script>
        (function(){
          var key = 'sb_settings_open';
          var group = document.getElementById('settings-group');
          var btn = document.getElementById('settings-toggle');
          var parentLink = document.getElementById('settings-parent');
          var box = document.getElementById('settings-submenu');
          if(!btn || !box) return;
          function setOpen(open){
            box.style.display = open ? 'block' : 'none';
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            btn.classList.toggle('rot', open);
            if (group) { group.classList.toggle('open', open); }
            try { localStorage.setItem(key, open ? '1' : '0'); } catch(e){}
          }
          var saved = null;
          try { saved = localStorage.getItem(key); } catch(e){ saved = null; }
          var open = (saved === '1') || <?php echo in_array($active, ['settings','permissions','email-settings','db','reminders','activity','departments','designations','statuses','shifts','approvals']) ? 'true' : 'false'; ?>;
          setOpen(open);
          function toggle(){ setOpen(!(box.style.display !== 'none')); }
          btn.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
          if (parentLink) {
            parentLink.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
          }
        })();
      </script>
      <?php endif; ?>
      <?php endif; ?>
      <hr class="my-2 border-secondary">
      <a class="nav-link sidebar-link text-danger" href="<?php echo site_url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
  </div>
</aside>

