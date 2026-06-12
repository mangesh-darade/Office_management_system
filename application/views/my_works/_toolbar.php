<?php

  $toolbar_mode = isset($view_mode) ? $view_mode : 'list';

  $listUrl = site_url('my-works?view=list');

  $boardUrl = site_url('my-works?view=board');

  $matrixUrl = site_url('my-works?view=matrix');

  $redirectBack = 'my-works';

  if (!empty($_SERVER['QUERY_STRING'])) {

    $qs = preg_replace('/(^|&)view=[^&]*/', '', (string) $_SERVER['QUERY_STRING']);

    $qs = ltrim($qs, '&');

    if ($qs !== '') {

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

          <a class="btn <?php echo $toolbar_mode === 'list' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo htmlspecialchars($listUrl); ?>">

            <i class="bi bi-list-ul me-1"></i><span class="d-none d-sm-inline">List</span>

          </a>

          <a class="btn <?php echo $toolbar_mode === 'board' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo htmlspecialchars($boardUrl); ?>">

            <i class="bi bi-kanban me-1"></i><span class="d-none d-sm-inline">Board</span>

          </a>

          <a class="btn <?php echo $toolbar_mode === 'matrix' ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo htmlspecialchars($matrixUrl); ?>" title="Eisenhower priority matrix">

            <i class="bi bi-grid-3x3-gap me-1"></i><span class="d-none d-sm-inline">Matrix</span>

          </a>

        </div>

        <?php if (!empty($can_add)): ?>

        <div class="mw-toolbar-cta">

          <a class="btn btn-sm mw-btn-quick-add" href="<?php echo htmlspecialchars($quickAddUrl, ENT_QUOTES, 'UTF-8'); ?>" title="Quick add — full screen with rich text and attachments">

            <i class="bi bi-lightning-charge-fill me-1"></i><span class="d-none d-sm-inline">Quick add</span><span class="d-inline d-sm-none">Quick</span>

          </a>

          <a class="btn btn-primary btn-sm mw-btn-new" href="<?php echo site_url('my-works/create'); ?>">

            <i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New work item</span><span class="d-inline d-sm-none">New</span>

          </a>

        </div>

        <?php endif; ?>

      </div>

    </div>

  </div>

</div>

