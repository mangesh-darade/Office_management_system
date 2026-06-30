<?php $this->load->view('partials/header', ['title' => 'Project Details', 'extra_css' => ['assets/css/projects.css']]); ?>

<div class="container-fluid py-2 px-3 project-detail-page project-detail-compact">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2 project-detail-head">    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?php echo site_url('projects'); ?>">Projects</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo esc_view($project->code); ?></li>
            </ol>
        </nav>
        <h1 class="h5 mb-0 fw-bold"><?php echo esc_view($project->name); ?></h1>
        <?php if (!empty($project->reference_url)): ?>
        <?php $this->load->view('partials/reference_url_display', ['reference_url' => $project->reference_url, 'wrapper_class' => 'mt-2']); ?>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('projects/dashboard'); ?>">Dashboard</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('projects'); ?>">Back</a>
      <?php if(function_exists('has_module_access') && (has_module_access('projects_edit') || has_module_access('projects'))): ?>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('projects/'.$project->id.'/edit'); ?>">Edit Project</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && (has_module_access('projects_delete') || has_module_access('projects'))): ?>
      <form method="post" action="<?php echo site_url('projects/'.$project->id.'/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this project?');">
        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="project-detail-stats mb-2">
    <div class="project-detail-stat-card project-detail-stat-progress">
      <span class="project-detail-stat-label">Progress</span>
      <strong class="project-detail-stat-value"><?php echo (int) $progress; ?>%</strong>
      <div class="project-detail-progress-bar" role="progressbar" aria-valuenow="<?php echo (int) $progress; ?>" aria-valuemin="0" aria-valuemax="100">
        <span style="width:<?php echo (int) $progress; ?>%;"></span>
      </div>
    </div>
    <div class="project-detail-stat-card">
      <span class="project-detail-stat-label">Pending</span>
      <strong class="project-detail-stat-value text-secondary"><?php echo (int) $stats['pending']; ?></strong>
    </div>
    <div class="project-detail-stat-card">
      <span class="project-detail-stat-label">In Progress</span>
      <strong class="project-detail-stat-value text-info"><?php echo (int) $stats['in_progress']; ?></strong>
    </div>
    <div class="project-detail-stat-card">
      <span class="project-detail-stat-label">Blocked</span>
      <strong class="project-detail-stat-value text-danger"><?php echo (int) $stats['blocked']; ?></strong>
    </div>
    <div class="project-detail-stat-card">
      <span class="project-detail-stat-label">Completed</span>
      <strong class="project-detail-stat-value text-success"><?php echo (int) $stats['completed']; ?></strong>
    </div>
    <div class="project-detail-stat-card project-detail-stat-meta">
      <span class="project-detail-stat-label">Status</span>
      <span class="badge bg-<?php echo ($project->status === 'completed' ? 'success' : ($project->status === 'active' ? 'primary' : 'secondary')); ?> project-detail-status-badge"><?php echo esc_view($project->status); ?></span>
      <div class="project-detail-dates small text-muted">
        <span><i class="bi bi-calendar-event"></i> <?php echo $project->start_date ? date('M j, Y', strtotime($project->start_date)) : '—'; ?></span>
        <span><i class="bi bi-calendar-check"></i> <?php echo $project->end_date ? date('M j, Y', strtotime($project->end_date)) : '—'; ?></span>
      </div>
    </div>
    <div class="project-detail-stat-card project-detail-stat-team">
      <span class="project-detail-stat-label">Team</span>
      <div class="project-detail-team-row">
        <div class="project-detail-avatars">
          <?php $count = 0; foreach ($members as $m): if ($count > 3) { break; } ?>
          <span class="project-detail-avatar" title="<?php echo esc_view($m->name ?: $m->email); ?>"><?php echo strtoupper(substr($m->name ?: $m->email, 0, 1)); ?></span>
          <?php $count++; endforeach; ?>
          <?php if (count($members) > 4): ?>
          <span class="project-detail-avatar project-detail-avatar-more">+<?php echo count($members) - 4; ?></span>
          <?php endif; ?>
        </div>
        <?php if (function_exists('has_module_access') && (has_module_access('projects_edit') || has_module_access('projects') || (function_exists('is_admin_group') && is_admin_group()))): ?>
        <a href="<?php echo site_url('projects/' . $project->id . '/members'); ?>" class="btn btn-outline-primary btn-sm project-detail-members-btn">Members</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <!-- Main Content Tabs -->
  <?php
    $show_defects_tab = function_exists('has_module_access') && (has_module_access('defects') || has_module_access('defects_list'));
    $defects = isset($defects) ? $defects : array();
    $assignable_users = isset($assignable_users) ? $assignable_users : array();
    $can_manage_tasks = !empty($can_manage_tasks);
    $can_manage_requirements = !empty($can_manage_requirements);
    $can_manage_defects = !empty($can_manage_defects);
    $can_delete_tasks = !empty($can_delete_tasks);
    $can_delete_requirements = !empty($can_delete_requirements);
    $can_delete_defects = !empty($can_delete_defects);
    $active_tab = trim((string) $this->input->get('tab'));
    if ($active_tab === '' || !in_array($active_tab, array('tasks', 'requirements', 'defects'), true)) {
      $active_tab = 'tasks';
    }
    if ($active_tab === 'defects' && !$show_defects_tab) {
      $active_tab = 'tasks';
    }

    $task_statuses = array('pending', 'in_progress', 'completed', 'blocked');
    $req_statuses = array('received', 'under_review', 'approved', 'in_progress', 'completed', 'on_hold', 'rejected', 'cancelled');
    $defect_statuses = array('open', 'in_progress', 'fixed', 'verified', 'closed', 'rejected');
    $task_priorities = array('low', 'medium', 'high', 'urgent');
    $defect_priorities = array('low', 'medium', 'high', 'critical');

    $project_view_user_label = function ($u) {
      if (isset($u->emp_name) && trim((string) $u->emp_name) !== '') {
        return trim((string) $u->emp_name);
      }
      if (isset($u->full_name) && trim((string) $u->full_name) !== '') {
        return trim((string) $u->full_name);
      }
      if (isset($u->name) && trim((string) $u->name) !== '') {
        return trim((string) $u->name);
      }
      return isset($u->email) ? (string) $u->email : '';
    };

    $project_view_assignee_label = function ($row) {
      if (!empty($row->assignee_name)) {
        return (string) $row->assignee_name;
      }
      if (!empty($row->assignee_email)) {
        $parts = explode('@', (string) $row->assignee_email);
        return $parts[0];
      }
      return '—';
    };

    ob_start();
    foreach ($assignable_users as $u) {
      $uid = (int) $u->id;
      $ulabel = $project_view_user_label($u);
      echo '<option value="' . $uid . '">' . esc_view($ulabel) . '</option>';
    }
    $inline_user_options = ob_get_clean();
  ?>
  <ul class="nav nav-tabs nav-tabs-sm mb-2 project-detail-tabs" id="projectTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link<?php echo $active_tab === 'tasks' ? ' active' : ''; ?>" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab" aria-controls="tasks" aria-selected="<?php echo $active_tab === 'tasks' ? 'true' : 'false'; ?>"><i class="bi bi-list-check me-1"></i>Tasks <span class="badge rounded-pill bg-light text-dark border ms-1"><?php echo count($tasks); ?></span></button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link<?php echo $active_tab === 'requirements' ? ' active' : ''; ?>" id="requirements-tab" data-bs-toggle="tab" data-bs-target="#requirements" type="button" role="tab" aria-controls="requirements" aria-selected="<?php echo $active_tab === 'requirements' ? 'true' : 'false'; ?>"><i class="bi bi-clipboard-check me-1"></i>Requirements <span class="badge rounded-pill bg-light text-dark border ms-1"><?php echo count($requirements); ?></span></button>
    </li>
    <?php if ($show_defects_tab): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link<?php echo $active_tab === 'defects' ? ' active' : ''; ?>" id="defects-tab" data-bs-toggle="tab" data-bs-target="#defects" type="button" role="tab" aria-controls="defects" aria-selected="<?php echo $active_tab === 'defects' ? 'true' : 'false'; ?>"><i class="bi bi-bug me-1"></i>Defects <span class="badge rounded-pill bg-light text-dark border ms-1"><?php echo count($defects); ?></span></button>    </li>
    <?php endif; ?>
  </ul>

  <div class="tab-content" id="projectTabsContent">
    <!-- Tasks Tab -->
    <div class="tab-pane fade<?php echo $active_tab === 'tasks' ? ' show active' : ''; ?>" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
        <div class="card shadow-sm border-0 project-detail-panel">
            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 small fw-semibold text-uppercase text-muted">Tasks</h6>                <?php if(function_exists('has_module_access') && has_module_access('tasks_add')): ?>
                <a href="<?php echo site_url('projects/'.$project->id.'/dashboard'); ?>" class="btn btn-outline-secondary btn-sm py-0 me-1"><i class="bi bi-speedometer2"></i><span class="d-none d-md-inline ms-1">Dashboard</span></a>
                <a href="<?php echo site_url('tasks/board?project_id='.$project->id); ?>" class="btn btn-outline-secondary btn-sm py-0"><i class="bi bi-kanban"></i><span class="d-none d-md-inline ms-1">Board</span></a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 project-inline-table" data-inline-type="task" data-save-url="<?php echo site_url('projects/' . (int) $project->id . '/inline-save'); ?>" data-delete-url="<?php echo site_url('projects/' . (int) $project->id . '/inline-delete'); ?>" data-can-manage="<?php echo $can_manage_tasks ? '1' : '0'; ?>" data-can-delete="<?php echo $can_delete_tasks ? '1' : '0'; ?>">
                    <thead class="table-light">
                        <tr>
                            <th width="8%">#</th>
                            <th width="34%">Title</th>
                            <th width="16%">Status</th>
                            <th width="14%">Priority</th>
                            <th width="18%">Assignee</th>
                            <th width="10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tasks) && !$can_manage_tasks): ?>
                        <tr class="project-inline-empty"><td colspan="6" class="text-center py-3 text-muted small">No tasks found for this project.</td></tr>
                        <?php else: foreach ($tasks as $t): ?>
                        <?php
                          $t_priority = isset($t->priority) ? (string) $t->priority : 'medium';
                          $t_status = $t->status ?: 'pending';
                          $t_assigned = !empty($t->assigned_to) ? (int) $t->assigned_to : 0;
                        ?>
                        <tr class="project-inline-row" data-id="<?php echo (int) $t->id; ?>">
                            <td><a href="<?php echo site_url('tasks/' . (int) $t->id); ?>" class="text-decoration-none project-inline-id">#<?php echo (int) $t->id; ?></a></td>
                            <td>
                                <?php if ($can_manage_tasks): ?>
                                <input type="text" class="form-control form-control-sm project-inline-title" value="<?php echo esc_view($t->title, ENT_QUOTES, 'UTF-8'); ?>" maxlength="500">
                                <?php else: ?>
                                <a href="<?php echo site_url('tasks/' . (int) $t->id); ?>" class="fw-medium text-dark text-decoration-none"><?php echo esc_view($t->title); ?></a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_manage_tasks): ?>
                                <select class="form-select form-select-sm project-inline-status">
                                    <?php foreach ($task_statuses as $st): ?>
                                    <option value="<?php echo esc_view($st); ?>" <?php echo $t_status === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <?php
                                $status_colors = array('pending' => 'secondary', 'in_progress' => 'info', 'completed' => 'success', 'blocked' => 'danger');
                                $s_color = isset($status_colors[$t_status]) ? $status_colors[$t_status] : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $s_color; ?>"><?php echo ucfirst(str_replace('_', ' ', $t_status)); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_manage_tasks): ?>
                                <select class="form-select form-select-sm project-inline-priority">
                                    <?php foreach ($task_priorities as $pr): ?>
                                    <option value="<?php echo esc_view($pr); ?>" <?php echo $t_priority === $pr ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <?php
                                $p_colors = array('low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'urgent' => 'dark');
                                $p_color = isset($p_colors[$t_priority]) ? $p_colors[$t_priority] : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $p_color; ?>"><?php echo $t_priority ? ucfirst($t_priority) : '-'; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_manage_tasks): ?>
                                <select class="form-select form-select-sm project-inline-assignee">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($assignable_users as $u): ?>
                                    <option value="<?php echo (int) $u->id; ?>" <?php echo $t_assigned === (int) $u->id ? 'selected' : ''; ?>><?php echo esc_view($project_view_user_label($u)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <span class="small"><?php echo esc_view($t->assigned_to ? $project_view_assignee_label($t) : '-'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <span class="project-inline-state text-muted small me-1"></span>
                                <a href="<?php echo site_url('tasks/' . (int) $t->id); ?>" class="btn btn-sm btn-light" title="View"><i class="bi bi-box-arrow-up-right"></i></a>
                                <?php if ($can_delete_tasks): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if ($can_manage_tasks): ?>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="py-1 px-2">
                                <button type="button" class="btn btn-sm btn-outline-primary project-inline-add py-0 px-2" title="Add task">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <!-- Requirements Tab -->
    <div class="tab-pane fade<?php echo $active_tab === 'requirements' ? ' show active' : ''; ?>" id="requirements" role="tabpanel" aria-labelledby="requirements-tab">
        <div class="card shadow-sm border-0 project-detail-panel">
            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 small fw-semibold text-uppercase text-muted">Requirements</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 project-inline-table" data-inline-type="requirement" data-save-url="<?php echo site_url('projects/' . (int) $project->id . '/inline-save'); ?>" data-delete-url="<?php echo site_url('projects/' . (int) $project->id . '/inline-delete'); ?>" data-can-manage="<?php echo $can_manage_requirements ? '1' : '0'; ?>" data-can-delete="<?php echo $can_delete_requirements ? '1' : '0'; ?>">
                    <thead class="table-light">
                        <tr>
                            <th width="12%">#</th>
                            <th width="30%">Title</th>
                            <th width="16%">Status</th>
                            <th width="14%">Priority</th>
                            <th width="18%">Assignee</th>
                            <th width="10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requirements) && !$can_manage_requirements): ?>
                        <tr class="project-inline-empty"><td colspan="6" class="text-center py-3 text-muted small">No requirements linked to this project.</td></tr>
                        <?php else: foreach ($requirements as $r): ?>
                        <?php
                          $r_status = isset($r->status) ? (string) $r->status : 'received';
                          $r_priority = isset($r->priority) ? (string) $r->priority : 'medium';
                          $r_assigned = !empty($r->assigned_to) ? (int) $r->assigned_to : 0;
                        ?>
                        <tr class="project-inline-row" data-id="<?php echo (int) $r->id; ?>" data-ref="<?php echo esc_view($r->req_number, ENT_QUOTES, 'UTF-8'); ?>">
                            <td><a href="<?php echo site_url('requirements/view/' . (int) $r->id); ?>" class="text-decoration-none project-inline-id"><?php echo esc_view($r->req_number); ?></a></td>
                            <td>
                                <?php if ($can_manage_requirements): ?>
                                <input type="text" class="form-control form-control-sm project-inline-title" value="<?php echo esc_view($r->title, ENT_QUOTES, 'UTF-8'); ?>" maxlength="500">
                                <?php else: ?>
                                <?php echo esc_view($r->title); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_manage_requirements): ?>
                                <select class="form-select form-select-sm project-inline-status">
                                    <?php foreach ($req_statuses as $st): ?>
                                    <option value="<?php echo esc_view($st); ?>" <?php echo $r_status === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <span class="badge bg-secondary"><?php echo esc_view($r_status); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_manage_requirements): ?>
                                <select class="form-select form-select-sm project-inline-priority">
                                    <?php foreach ($task_priorities as $pr): ?>
                                    <option value="<?php echo esc_view($pr); ?>" <?php echo $r_priority === $pr ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <span class="badge bg-secondary"><?php echo esc_view($r_priority); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_manage_requirements): ?>
                                <select class="form-select form-select-sm project-inline-assignee">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($assignable_users as $u): ?>
                                    <option value="<?php echo (int) $u->id; ?>" <?php echo $r_assigned === (int) $u->id ? 'selected' : ''; ?>><?php echo esc_view($project_view_user_label($u)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <span class="small"><?php echo esc_view($r_assigned ? $project_view_assignee_label($r) : '-'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <span class="project-inline-state text-muted small me-1"></span>
                                <a href="<?php echo site_url('requirements/view/' . (int) $r->id); ?>" class="btn btn-sm btn-light" title="View"><i class="bi bi-box-arrow-up-right"></i></a>
                                <?php if ($can_delete_requirements): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if ($can_manage_requirements): ?>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="py-1 px-2">
                                <button type="button" class="btn btn-sm btn-outline-primary project-inline-add py-0 px-2" title="Add requirement">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <?php if ($show_defects_tab): ?>
    <!-- Defects Tab -->
    <div class="tab-pane fade<?php echo $active_tab === 'defects' ? ' show active' : ''; ?>" id="defects" role="tabpanel" aria-labelledby="defects-tab">
        <div class="card shadow-sm border-0 project-detail-panel">
            <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 small fw-semibold text-uppercase text-muted">Defects</h6>
                <div class="d-flex gap-1">
                    <a href="<?php echo site_url('defects?project_id=' . (int) $project->id); ?>" class="btn btn-outline-secondary btn-sm py-0"><i class="bi bi-list"></i><span class="d-none d-md-inline ms-1">All</span></a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0 project-inline-table" data-inline-type="defect" data-save-url="<?php echo site_url('projects/' . (int) $project->id . '/inline-save'); ?>" data-delete-url="<?php echo site_url('projects/' . (int) $project->id . '/inline-delete'); ?>" data-can-manage="<?php echo $can_manage_defects ? '1' : '0'; ?>" data-can-delete="<?php echo $can_delete_defects ? '1' : '0'; ?>">
                    <thead class="table-light">
                        <tr>
                            <th width="12%">#</th>
                            <th width="30%">Title</th>
                            <th width="16%">Status</th>
                            <th width="14%">Priority</th>
                            <th width="18%">Assignee</th>
                            <th width="10%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($defects) && !$can_manage_defects): ?>
                        <tr class="project-inline-empty"><td colspan="6" class="text-center py-3 text-muted small">No defects logged for this project.</td></tr>
                        <?php else: foreach ($defects as $d): ?>
                        <?php
                          $d_status = isset($d->status) ? (string) $d->status : 'open';
                          $d_priority = isset($d->priority) ? (string) $d->priority : 'medium';
                          $d_assigned = !empty($d->assigned_to) ? (int) $d->assigned_to : 0;
                        ?>
                        <tr class="project-inline-row" data-id="<?php echo (int) $d->id; ?>" data-ref="<?php echo esc_view($d->defect_number, ENT_QUOTES, 'UTF-8'); ?>">
                            <td><a href="<?php echo site_url('defects/view/' . (int) $d->id); ?>" class="text-decoration-none project-inline-id"><?php echo esc_view($d->defect_number); ?></a></td>
                            <td>
                                <?php if ($can_manage_defects): ?>
                                <input type="text" class="form-control form-control-sm project-inline-title" value="<?php echo esc_view($d->title, ENT_QUOTES, 'UTF-8'); ?>" maxlength="500">
                                <?php else: ?>
                                <?php echo esc_view($d->title); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_manage_defects): ?>
                                <select class="form-select form-select-sm project-inline-status">
                                    <?php foreach ($defect_statuses as $st): ?>
                                    <option value="<?php echo esc_view($st); ?>" <?php echo $d_status === $st ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <span class="badge bg-secondary"><?php echo esc_view(ucfirst(str_replace('_', ' ', $d_status))); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_manage_defects): ?>
                                <select class="form-select form-select-sm project-inline-priority">
                                    <?php foreach ($defect_priorities as $pr): ?>
                                    <option value="<?php echo esc_view($pr); ?>" <?php echo $d_priority === $pr ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <span class="badge bg-light text-dark border"><?php echo esc_view($d_priority); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($can_manage_defects): ?>
                                <select class="form-select form-select-sm project-inline-assignee">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($assignable_users as $u): ?>
                                    <option value="<?php echo (int) $u->id; ?>" <?php echo $d_assigned === (int) $u->id ? 'selected' : ''; ?>><?php echo esc_view($project_view_user_label($u)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <?php echo esc_view(!empty($d->assignee_name) ? $d->assignee_name : '—'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <span class="project-inline-state text-muted small me-1"></span>
                                <a href="<?php echo site_url('defects/view/' . (int) $d->id); ?>" class="btn btn-sm btn-light" title="View"><i class="bi bi-box-arrow-up-right"></i></a>
                                <?php if ($can_delete_defects): ?>
                                <button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if ($can_manage_defects): ?>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="py-1 px-2">
                                <button type="button" class="btn btn-sm btn-outline-primary project-inline-add py-0 px-2" title="Add defect">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php if ($can_manage_tasks || $can_manage_requirements || $can_manage_defects || $can_delete_tasks || $can_delete_requirements || $can_delete_defects): ?>
<template id="project-inline-row-template-task">
<tr class="project-inline-row project-inline-row-new" data-id="0">
  <td><span class="text-muted small project-inline-id">…</span></td>
  <td><input type="text" class="form-control form-control-sm project-inline-title" value="" maxlength="500" placeholder="Title"></td>
  <td><select class="form-select form-select-sm project-inline-status"><?php foreach ($task_statuses as $st): ?><option value="<?php echo esc_view($st); ?>"><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-priority"><?php foreach ($task_priorities as $pr): ?><option value="<?php echo esc_view($pr); ?>" <?php echo $pr === 'medium' ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-assignee"><option value="">Unassigned</option><?php echo $inline_user_options; ?></select></td>
  <td class="text-end text-nowrap"><span class="project-inline-state text-muted small me-1"></span><?php if ($can_delete_tasks): ?><button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button><?php endif; ?></td>
</tr>
</template>
<template id="project-inline-row-template-requirement">
<tr class="project-inline-row project-inline-row-new" data-id="0">
  <td><span class="text-muted small project-inline-id">…</span></td>
  <td><input type="text" class="form-control form-control-sm project-inline-title" value="" maxlength="500" placeholder="Title"></td>
  <td><select class="form-select form-select-sm project-inline-status"><?php foreach ($req_statuses as $st): ?><option value="<?php echo esc_view($st); ?>" <?php echo $st === 'received' ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-priority"><?php foreach ($task_priorities as $pr): ?><option value="<?php echo esc_view($pr); ?>" <?php echo $pr === 'medium' ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-assignee"><option value="">Unassigned</option><?php echo $inline_user_options; ?></select></td>
  <td class="text-end text-nowrap"><span class="project-inline-state text-muted small me-1"></span><?php if ($can_delete_requirements): ?><button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button><?php endif; ?></td>
</tr>
</template>
<template id="project-inline-row-template-defect">
<tr class="project-inline-row project-inline-row-new" data-id="0">
  <td><span class="text-muted small project-inline-id">…</span></td>
  <td><input type="text" class="form-control form-control-sm project-inline-title" value="" maxlength="500" placeholder="Title"></td>
  <td><select class="form-select form-select-sm project-inline-status"><?php foreach ($defect_statuses as $st): ?><option value="<?php echo esc_view($st); ?>" <?php echo $st === 'open' ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-priority"><?php foreach ($defect_priorities as $pr): ?><option value="<?php echo esc_view($pr); ?>" <?php echo $pr === 'medium' ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option><?php endforeach; ?></select></td>
  <td><select class="form-select form-select-sm project-inline-assignee"><option value="">Unassigned</option><?php echo $inline_user_options; ?></select></td>
  <td class="text-end text-nowrap"><span class="project-inline-state text-muted small me-1"></span><?php if ($can_delete_defects): ?><button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button><?php endif; ?></td>
</tr>
</template>
<script>
(function () {
  var canDelete = {
    task: <?php echo $can_delete_tasks ? 'true' : 'false'; ?>,
    requirement: <?php echo $can_delete_requirements ? 'true' : 'false'; ?>,
    defect: <?php echo $can_delete_defects ? 'true' : 'false'; ?>
  };
  var defaultTitles = { task: 'New task', requirement: 'New requirement', defect: 'New defect' };
  var deleteLabels = { task: 'task', requirement: 'requirement', defect: 'defect' };
  var viewUrls = {
    task: <?php echo json_encode(site_url('tasks/')); ?>,
    requirement: <?php echo json_encode(site_url('requirements/view/')); ?>,
    defect: <?php echo json_encode(site_url('defects/view/')); ?>
  };

  function buildActionsHtml(type, id) {
    var html = '<span class="project-inline-state text-muted small me-1"></span>';
    html += '<a href="' + viewUrls[type] + id + '" class="btn btn-sm btn-light" title="View"><i class="bi bi-box-arrow-up-right"></i></a>';
    if (canDelete[type]) {
      html += ' <button type="button" class="btn btn-sm btn-outline-danger project-inline-delete" title="Delete"><i class="bi bi-trash"></i></button>';
    }
    return html;
  }

  function maybeShowEmptyRow(table) {
    var tbody = table.querySelector('tbody');
    if (!tbody || tbody.querySelector('.project-inline-row')) {
      return;
    }
    var canManage = table.getAttribute('data-can-manage') === '1';
    if (!canManage) {
      var type = table.getAttribute('data-inline-type');
      var messages = {
        task: 'No tasks found for this project.',
        requirement: 'No requirements linked to this project.',
        defect: 'No defects logged for this project.'
      };
      var tr = document.createElement('tr');
      tr.className = 'project-inline-empty';
      tr.innerHTML = '<td colspan="6" class="text-center py-3 text-muted small">' + (messages[type] || 'No items found.') + '</td>';
      tbody.appendChild(tr);
    }
  }

  function rowPayload(row) {
    return {
      type: row.closest('.project-inline-table').getAttribute('data-inline-type'),
      id: row.getAttribute('data-id') || '0',
      title: (row.querySelector('.project-inline-title') || {}).value || '',
      status: (row.querySelector('.project-inline-status') || {}).value || '',
      priority: (row.querySelector('.project-inline-priority') || {}).value || '',
      assigned_to: (row.querySelector('.project-inline-assignee') || {}).value || ''
    };
  }

  function setRowState(row, text, isError) {
    var el = row.querySelector('.project-inline-state');
    if (!el) {
      return;
    }
    el.textContent = text || '';
    el.classList.toggle('text-danger', !!isError);
    el.classList.toggle('text-success', !isError && text === 'Saved');
  }

  function removeEmptyRow(table) {
    var empty = table.querySelector('tbody .project-inline-empty');
    if (empty) {
      empty.remove();
    }
  }

  function saveRow(row, forceCreate) {
    if (row.getAttribute('data-saving') === '1') {
      return;
    }
    var table = row.closest('.project-inline-table');
    if (!table || table.getAttribute('data-can-manage') !== '1') {
      return;
    }
    var payload = rowPayload(row);
    var isNew = !payload.id || payload.id === '0';
    if (isNew && !forceCreate && !payload.title.trim()) {
      return;
    }
    if (!isNew && !payload.title.trim()) {
      setRowState(row, 'Title required', true);
      return;
    }

    row.setAttribute('data-saving', '1');
    setRowState(row, 'Saving…');

    var body = new URLSearchParams();
    Object.keys(payload).forEach(function (key) {
      body.append(key, payload[key]);
    });

    fetch(table.getAttribute('data-save-url'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
      credentials: 'same-origin'
    })
      .then(function (res) { return res.json().then(function (json) { return { ok: res.ok, json: json }; }); })
      .then(function (result) {
        if (!result.json || !result.json.ok) {
          throw new Error((result.json && result.json.error) ? result.json.error : 'Save failed');
        }
        var data = result.json;
        row.setAttribute('data-id', String(data.id));
        row.classList.remove('project-inline-row-new');
        var idCell = row.querySelector('.project-inline-id');
        if (idCell) {
          var type = payload.type;
          var label = '#' + data.id;
          var href = viewUrls[type] + data.id;
          if (type === 'requirement' && data.req_number) {
            label = data.req_number;
          }
          if (type === 'defect' && data.defect_number) {
            label = data.defect_number;
          }
          idCell.innerHTML = '<a href="' + href + '" class="text-decoration-none">' + label + '</a>';
        }
        var actionsCell = row.querySelector('td:last-child');
        if (actionsCell) {
          actionsCell.innerHTML = buildActionsHtml(payload.type, data.id);
        }
        setRowState(row, 'Saved');
        setTimeout(function () { setRowState(row, ''); }, 1200);
      })
      .catch(function (err) {
        setRowState(row, err.message || 'Error', true);
      })
      .finally(function () {
        row.removeAttribute('data-saving');
      });
  }

  var debounceTimers = new WeakMap();
  function scheduleSave(row) {
    if (debounceTimers.has(row)) {
      clearTimeout(debounceTimers.get(row));
    }
    debounceTimers.set(row, setTimeout(function () {
      saveRow(row, false);
    }, 450));
  }

  document.querySelectorAll('.project-inline-table').forEach(function (table) {
    var type = table.getAttribute('data-inline-type');
    var canManage = table.getAttribute('data-can-manage') === '1';
    var canDeleteTable = table.getAttribute('data-can-delete') === '1';

    table.addEventListener('click', function (e) {
      var delBtn = e.target.closest('.project-inline-delete');
      if (delBtn) {
        if (!canDeleteTable) {
          return;
        }
        var row = delBtn.closest('.project-inline-row');
        if (!row) {
          return;
        }
        var rowId = row.getAttribute('data-id') || '0';
        if (!rowId || rowId === '0') {
          row.remove();
          maybeShowEmptyRow(table);
          return;
        }
        var label = deleteLabels[type] || 'item';
        if (!window.confirm('Delete this ' + label + '?')) {
          return;
        }
        row.setAttribute('data-saving', '1');
        setRowState(row, 'Deleting…');
        var body = new URLSearchParams();
        body.append('type', type);
        body.append('id', rowId);
        fetch(table.getAttribute('data-delete-url'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: body.toString(),
          credentials: 'same-origin'
        })
          .then(function (res) { return res.json().then(function (json) { return { ok: res.ok, json: json }; }); })
          .then(function (result) {
            if (!result.json || !result.json.ok) {
              throw new Error((result.json && result.json.error) ? result.json.error : 'Delete failed');
            }
            row.remove();
            maybeShowEmptyRow(table);
          })
          .catch(function (err) {
            row.removeAttribute('data-saving');
            setRowState(row, err.message || 'Error', true);
          });
        return;
      }

      if (!canManage) {
        return;
      }
      var btn = e.target.closest('.project-inline-add');
      if (!btn) {
        return;
      }
      var tpl = document.getElementById('project-inline-row-template-' + type);
      if (!tpl || !tpl.content) {
        return;
      }
      removeEmptyRow(table);
      var row = tpl.content.firstElementChild.cloneNode(true);
      table.querySelector('tbody').appendChild(row);
      var titleInput = row.querySelector('.project-inline-title');
      if (titleInput) {
        titleInput.value = defaultTitles[type] || 'New item';
      }
      saveRow(row, true);
      if (titleInput) {
        titleInput.focus();
        titleInput.select();
      }
    });

    if (!canManage) {
      return;
    }

    table.addEventListener('change', function (e) {
      var row = e.target.closest('.project-inline-row');
      if (!row) {
        return;
      }
      saveRow(row, false);
    });

    table.addEventListener('blur', function (e) {
      if (!e.target.classList.contains('project-inline-title')) {
        return;
      }
      var row = e.target.closest('.project-inline-row');
      if (!row) {
        return;
      }
      scheduleSave(row);
    }, true);

    table.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' || !e.target.classList.contains('project-inline-title')) {
        return;
      }
      e.preventDefault();
      var row = e.target.closest('.project-inline-row');
      if (row) {
        saveRow(row, false);
        e.target.blur();
      }
    });
  });
})();
</script>
<?php endif; ?>

<?php $this->load->view('partials/footer'); ?>
