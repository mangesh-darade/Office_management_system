<?php
// Sidebar partial for full-width pages
$active = strtolower($this->uri->segment(1) ?: 'dashboard');
// Only render sidebar for authenticated users
if (!(int)$this->session->userdata('user_id')) {
  return; // do not output sidebar when not logged in
}
?>
<aside class="d-none d-md-block col-md-3 col-lg-2 sidebar-left">
  <div class="sidebar-inner p-3">
    <nav class="nav flex-column gap-1 sidebar-nav">
      <a class="nav-link sidebar-link <?php echo $active==='dashboard'?'active':''; ?>" href="<?php echo site_url('dashboard'); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      <a class="nav-link sidebar-link <?php echo $active==='users'?'active':''; ?>" href="<?php echo site_url('users'); ?>"><i class="bi bi-people me-2"></i>Users</a>
      <a class="nav-link sidebar-link <?php echo $active==='mail'?'active':''; ?>" href="<?php echo site_url('mail'); ?>"><i class="bi bi-envelope me-2"></i>Mail</a>
      <a class="nav-link sidebar-link <?php echo $active==='projects'?'active':''; ?>" href="<?php echo site_url('projects'); ?>"><i class="bi bi-kanban me-2"></i>Projects</a>
      <?php if(function_exists('has_module_access') && has_module_access('clients')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='clients'?'active':''; ?>" href="<?php echo site_url('clients'); ?>"><i class="bi bi-briefcase me-2"></i>Clients</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('requirements')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='requirements'?'active':''; ?>" href="<?php echo site_url('requirements'); ?>"><i class="bi bi-clipboard-check me-2"></i>Requirements</a>
      <?php endif; ?>
      <a class="nav-link sidebar-link <?php echo $active==='employees'?'active':''; ?>" href="<?php echo site_url('employees'); ?>"><i class="bi bi-people me-2"></i>Employees</a>
      <a class="nav-link sidebar-link <?php echo $active==='tasks'?'active':''; ?>" href="<?php echo site_url('tasks/board'); ?>"><i class="bi bi-list-check me-2"></i>Tasks</a>
      <?php if(function_exists('has_module_access') && has_module_access('departments')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='departments'?'active':''; ?>" href="<?php echo site_url('departments'); ?>"><i class="bi bi-diagram-3 me-2"></i>Departments</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('designations')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='designations'?'active':''; ?>" href="<?php echo site_url('designations'); ?>"><i class="bi bi-person-badge me-2"></i>Designations</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('chats')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='chats'?'active':''; ?>" href="<?php echo site_url('chats/app'); ?>"><i class="bi bi-chat-dots me-2"></i>Chats</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('attendance')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='attendance'?'active':''; ?>" href="<?php echo site_url('attendance'); ?>"><i class="bi bi-calendar-check me-2"></i>Attendance</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('timesheets')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='timesheets'?'active':''; ?>" href="<?php echo site_url('timesheets'); ?>"><i class="bi bi-calendar3 me-2"></i>Timesheets</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('announcements')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='announcements'?'active':''; ?>" href="<?php echo site_url('announcements'); ?>"><i class="bi bi-megaphone me-2"></i>Announcements</a>
      <?php endif; ?>
      <a class="nav-link sidebar-link <?php echo $active==='reports'?'active':''; ?>" href="<?php echo site_url('reports'); ?>"><i class="bi bi-graph-up me-2"></i>Reports</a>
      <?php if(function_exists('has_module_access') && has_module_access('reminders')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='reminders'?'active':''; ?>" href="<?php echo site_url('reminders'); ?>"><i class="bi bi-bell me-2"></i>Reminders</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('activity')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='activity'?'active':''; ?>" href="<?php echo site_url('activity'); ?>"><i class="bi bi-activity me-2"></i>Activity</a>
      <?php endif; ?>
      <?php // Admin section: show only to Admins (permissions module access)
      if(function_exists('has_module_access') && has_module_access('permissions')): ?>
      <hr class="my-2">
      <div class="text-uppercase text-muted small px-2">Admin</div>
      <?php if(has_module_access('db')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='db'?'active':''; ?>" href="<?php echo site_url('db'); ?>"><i class="bi bi-database me-2"></i>Database Manager</a>
      <?php endif; ?>
      <a class="nav-link sidebar-link <?php echo $active==='permissions'?'active':''; ?>" href="<?php echo site_url('permissions'); ?>"><i class="bi bi-shield-lock me-2"></i>Permissions</a>
      <?php if(has_module_access('settings')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='settings'?'active':''; ?>" href="<?php echo site_url('settings'); ?>"><i class="bi bi-gear me-2"></i>Settings</a>
      <?php endif; ?>
      <?php endif; ?>
      <hr class="my-2 border-secondary">
      <a class="nav-link sidebar-link text-danger" href="<?php echo site_url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
  </div>
</aside>

