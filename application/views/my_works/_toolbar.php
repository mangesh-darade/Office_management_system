<?php

  $toolbar_mode = isset($view_mode) ? $view_mode : 'list';

  $overviewUrl = site_url('my-works?view=overview');

  $listUrl = site_url('my-works?view=list');

  $boardUrl = site_url('my-works?view=board');

  $matrixUrl = site_url('my-works?view=matrix');

  $show_project_dashboard = function_exists('has_module_access') && (has_module_access('projects') || has_module_access('projects_list'));
  $show_task_dashboard = function_exists('has_module_access') && (has_module_access('tasks') || has_module_access('tasks_list'));
  $projectDashboardUrl = site_url('projects/dashboard');
  $taskDashboardUrl = site_url('tasks/my-dashboard');

  $redirectBack = 'my-works';

  if (!empty($_SERVER['QUERY_STRING'])) {

    $qs = preg_replace('/(^|&)view=[^&]*/', '', (string) $_SERVER['QUERY_STRING']);

    $qs = ltrim($qs, '&');

    if ($qs !== '') {

      $overviewUrl .= '&' . $qs;

      $listUrl .= '&' . $qs;

      $boardUrl .= '&' . $qs;

      $matrixUrl .= '&' . $qs;

    }

    $redirectBack .= '?' . (string) $_SERVER['QUERY_STRING'];

  }

  $quickAddUrl = site_url('my-works/quick-add') . '?redirect=' . rawurlencode($redirectBack);

?>

<div class="mw-toolbar card border-0 shadow-sm mb-3">

  <div class="card-body py-3">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-stretch align-items-lg-center gap-3">

      <div class="mw-toolbar-title">

        <nav aria-label="breadcrumb" class="mw-breadcrumb small mb-1">

          <ol class="breadcrumb mb-0">

            <li class="breadcrumb-item"><a href="<?php echo site_url('dashboard'); ?>">Dashboard</a></li>

            <li class="breadcrumb-item active" aria-current="page">My Works</li>

          </ol>

        </nav>

        <h1 class="h4 mb-1 fw-bold text-dark">

          <span class="mw-toolbar-icon"><i class="bi bi-clipboard2-check"></i></span>

          My Works

        </h1>

        <p class="text-muted small mb-0">Track tasks, clients, projects, links, and files in one place</p>

      </div>

      <div class="mw-toolbar-actions">

        <div class="btn-group btn-group-sm mw-view-toggle" role="group" aria-label="View mode">

          <a class="btn <?php echo $toolbar_mode === 'overview' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo esc_view($overviewUrl); ?>" title="My Work Overview">

            <i class="bi bi-grid-1x2 me-1"></i><span class="d-none d-sm-inline">Overview</span>

          </a>

          <?php if ($show_project_dashboard): ?>
          <a class="btn btn-outline-primary" href="<?php echo esc_view($projectDashboardUrl); ?>" title="Project Dashboard">

            <i class="bi bi-speedometer2 me-1"></i><span class="d-none d-sm-inline">Project Dashboard</span>

          </a>
          <?php endif; ?>

          <?php if ($show_task_dashboard): ?>
          <a class="btn btn-outline-primary" href="<?php echo esc_view($taskDashboardUrl); ?>" title="Team Dashboard">

            <i class="bi bi-people me-1"></i><span class="d-none d-sm-inline">Team Dashboard</span>

          </a>
          <?php endif; ?>

          <a class="btn <?php echo $toolbar_mode === 'list' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo esc_view($listUrl); ?>">

            <i class="bi bi-list-ul me-1"></i><span class="d-none d-sm-inline">List</span>

          </a>

          <a class="btn <?php echo $toolbar_mode === 'board' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo esc_view($boardUrl); ?>">

            <i class="bi bi-kanban me-1"></i><span class="d-none d-sm-inline">Board</span>

          </a>

          <a class="btn <?php echo $toolbar_mode === 'matrix' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo esc_view($matrixUrl); ?>" title="Eisenhower priority matrix">

            <i class="bi bi-grid-3x3-gap me-1"></i><span class="d-none d-sm-inline">Matrix</span>

          </a>

        </div>

        <?php if (!empty($can_add)): ?>

        <div class="mw-toolbar-cta">

          <a class="btn btn-sm mw-btn-quick-add" href="<?php echo esc_view($quickAddUrl); ?>" title="Quick add — full screen with rich text and attachments">

            <i class="bi bi-lightning-charge-fill me-1"></i><span class="d-none d-sm-inline">Quick add</span><span class="d-inline d-sm-none">Quick</span>

          </a>

          <a class="btn btn-primary btn-sm mw-btn-new" href="<?php echo site_url('my-works/create'); ?>">

            <i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New work item</span><span class="d-inline d-sm-none">New</span>

          </a>

          <a class="btn btn-outline-primary btn-sm mw-btn-template" href="<?php echo site_url('my-works/template-tasks'); ?>">

            <i class="bi bi-collection me-1"></i><span class="d-none d-sm-inline">Template Task</span><span class="d-inline d-sm-none">Template</span>

          </a>

        </div>

        <?php endif; ?>

      </div>

    </div>

  </div>

</div>

