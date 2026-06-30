<?php
$active_tab = isset($active_tab) ? (string) $active_tab : '';
$tabs_class = isset($tabs_class) ? (string) $tabs_class : 'mw-dash-view-tabs';
$show_project_dashboard = function_exists('has_module_access') && (has_module_access('projects') || has_module_access('projects_list'));
$show_task_dashboard = function_exists('has_module_access') && (has_module_access('tasks') || has_module_access('tasks_list'));
?>
<div class="<?php echo esc_view($tabs_class, ENT_QUOTES, 'UTF-8'); ?>">
  <a class="mw-dash-view-tab<?php echo $active_tab === 'todays-focus' ? ' active' : ''; ?>" href="<?php echo site_url('my-works/todays-focus'); ?>">Today's Focus</a>
  <a class="mw-dash-view-tab<?php echo $active_tab === 'overview' ? ' active' : ''; ?>" href="<?php echo site_url('my-works?view=overview'); ?>">Overview</a>
  <?php if ($show_project_dashboard): ?>
  <a class="mw-dash-view-tab" href="<?php echo site_url('projects/dashboard'); ?>">Project Dashboard</a>
  <?php endif; ?>
  <?php if ($show_task_dashboard): ?>
  <a class="mw-dash-view-tab" href="<?php echo site_url('tasks/my-dashboard'); ?>">Task Dashboard</a>
  <?php endif; ?>
  <a class="mw-dash-view-tab<?php echo $active_tab === 'list' ? ' active' : ''; ?>" href="<?php echo site_url('my-works?view=list'); ?>">List</a>
  <a class="mw-dash-view-tab<?php echo $active_tab === 'board' ? ' active' : ''; ?>" href="<?php echo site_url('my-works?view=board'); ?>">Board</a>
  <a class="mw-dash-view-tab<?php echo $active_tab === 'matrix' ? ' active' : ''; ?>" href="<?php echo site_url('my-works?view=matrix'); ?>">Matrix</a>
</div>
