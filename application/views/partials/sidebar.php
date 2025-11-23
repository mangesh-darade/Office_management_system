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
      <?php if(function_exists('has_module_access') && has_module_access('mail')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='mail'?'active':''; ?>" href="<?php echo site_url('mail'); ?>"><i class="bi bi-envelope me-2"></i>Mail</a>
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
      $user_group_show = function_exists('has_module_access') && (
        has_module_access('users') ||
        has_module_access('attendance') ||
        has_module_access('departments') ||
        has_module_access('designations') ||
        has_module_access('permissions')
      );
      ?>
      <?php if($user_group_show): ?>
      <div class="nav-item" id="user-group">
        <div class="d-flex align-items-center justify-content-between">
          <a id="user-parent" class="nav-link sidebar-link flex-grow-1 <?php echo in_array($active, ['users','roles','attendance','departments','designations','leave']) ? 'active' : ''; ?>" href="#">
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
            <?php if(function_exists('has_module_access') && has_module_access('permissions')): ?>
            <a class="submenu-link <?php echo $active==='roles'?'active':''; ?>" href="<?php echo site_url('roles'); ?>"><i class="bi bi-person-gear me-2"></i>Roles</a>
            <?php endif; ?>
            <?php if(function_exists('has_module_access') && has_module_access('attendance')): ?>
            <a class="submenu-link <?php echo $active==='attendance'?'active':''; ?>" href="<?php echo site_url('attendance'); ?>"><i class="bi bi-calendar-check me-2"></i>Attendance</a>
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
          var open = (saved === '1') || <?php echo in_array($active, ['users','roles','attendance','departments','designations','leave']) ? 'true' : 'false'; ?>;
          setOpen(open);
          function toggle(){ setOpen(!(box.style.display !== 'none')); }
          btn.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
          parentLink.addEventListener('click', function(ev){ ev.preventDefault(); toggle(); });
        })();
      </script>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('leave_requests')): ?>
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
            <a class="submenu-link <?php echo ($seg1==='leave' && $seg2==='team') ? 'active' : ''; ?>" href="<?php echo site_url('leave/team'); ?>">Team Leaves</a>
            <a class="submenu-link <?php echo ($seg1==='leave' && $seg2==='calendar') ? 'active' : ''; ?>" href="<?php echo site_url('leave/calendar'); ?>">Leave Calendar</a>
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
      <?php if(function_exists('has_module_access') && has_module_access('announcements')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='announcements'?'active':''; ?>" href="<?php echo site_url('announcements'); ?>"><i class="bi bi-megaphone me-2"></i>Announcements</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('reports')): ?>
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
            <a class="submenu-link <?php echo ($seg1==='reports' && ($seg2==='' || $seg2===null))?'active':''; ?>" href="<?php echo site_url('reports'); ?>">Overview</a>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='requirements')?'active':''; ?>" href="<?php echo site_url('reports/requirements'); ?>">Requirements Report</a>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='tasks-assignment')?'active':''; ?>" href="<?php echo site_url('reports/tasks-assignment'); ?>">Task Assignment Report</a>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='projects-status')?'active':''; ?>" href="<?php echo site_url('reports/projects-status'); ?>">Projects by Status</a>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='leaves')?'active':''; ?>" href="<?php echo site_url('reports/leaves'); ?>">Leaves Report</a>
            <a class="submenu-link <?php echo ($seg1==='reports' && $seg2==='attendance')?'active':''; ?>" href="<?php echo site_url('reports/attendance'); ?>">Attendance Report</a>
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
      <?php // Admin section: show only to Admins (permissions module access)
      if(function_exists('has_module_access') && has_module_access('permissions')): ?>
      <hr class="my-2">
      <div class="text-uppercase text-muted small px-2">Admin</div>
      <?php
      $settings_group_show = function_exists('has_module_access') && (
        has_module_access('settings') ||
        has_module_access('permissions') ||
        has_module_access('db') ||
        has_module_access('reminders') ||
        has_module_access('activity')
      );
      ?>
      <?php if($settings_group_show): ?>
      <div class="nav-item" id="settings-group">
        <div class="d-flex align-items-center justify-content-between">
          <a id="settings-parent" class="nav-link sidebar-link flex-grow-1 <?php echo in_array($active, ['settings','permissions','db','reminders','activity']) ? 'active' : ''; ?>" href="#">
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
            <?php if(function_exists('has_module_access') && has_module_access('permissions')): ?>
            <a class="submenu-link <?php echo $active==='permissions'?'active':''; ?>" href="<?php echo site_url('permissions'); ?>"><i class="bi bi-shield-lock me-2"></i>Permissions</a>
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
            <a class="submenu-link <?php echo $active==='activity'?'active':''; ?>" href="<?php echo site_url('activity'); ?>"><i class="bi bi-activity me-2"></i>Activity</a>
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
          var open = (saved === '1') || <?php echo in_array($active, ['settings','permissions','db','reminders','activity']) ? 'true' : 'false'; ?>;
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

