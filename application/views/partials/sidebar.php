<?php
// Sidebar partial for full-width pages
$active = strtolower($this->uri->segment(1) ?: 'dashboard');
?>
<aside class="col-12 col-md-3 col-lg-2 sidebar-left">
  <div class="sidebar-inner p-3">
    <nav class="nav flex-column gap-1 sidebar-nav">
      <a class="nav-link sidebar-link <?php echo $active==='dashboard'?'active':''; ?>" href="<?php echo site_url('dashboard'); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
      <a class="nav-link sidebar-link <?php echo $active==='users'?'active':''; ?>" href="<?php echo site_url('users'); ?>"><i class="bi bi-people me-2"></i>Users</a>
      <a class="nav-link sidebar-link <?php echo $active==='mail'?'active':''; ?>" href="<?php echo site_url('mail'); ?>"><i class="bi bi-envelope me-2"></i>Mail</a>
      <a class="nav-link sidebar-link <?php echo $active==='projects'?'active':''; ?>" href="<?php echo site_url('projects'); ?>"><i class="bi bi-kanban me-2"></i>Projects</a>
      <a class="nav-link sidebar-link <?php echo $active==='employees'?'active':''; ?>" href="<?php echo site_url('employees'); ?>"><i class="bi bi-people me-2"></i>Employees</a>
      <a class="nav-link sidebar-link <?php echo $active==='tasks'?'active':''; ?>" href="<?php echo site_url('tasks/board'); ?>"><i class="bi bi-list-check me-2"></i>Tasks</a>
      <?php if(function_exists('has_module_access') && has_module_access('chats')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='chats'?'active':''; ?>" href="<?php echo site_url('chats/app'); ?>"><i class="bi bi-chat-dots me-2"></i>Chats</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('attendance')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='attendance'?'active':''; ?>" href="<?php echo site_url('attendance'); ?>"><i class="bi bi-calendar-check me-2"></i>Attendance</a>
      <?php endif; ?>
      <a class="nav-link sidebar-link <?php echo $active==='reports'?'active':''; ?>" href="<?php echo site_url('reports'); ?>"><i class="bi bi-graph-up me-2"></i>Reports</a>
      <hr class="my-2 border-secondary">
      <a class="nav-link sidebar-link text-danger" href="<?php echo site_url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
  </div>
</aside>
