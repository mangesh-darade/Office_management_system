<?php $this->load->view('partials/header', ['title' => 'Project Details']); ?>

<div class="container-fluid p-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?php echo site_url('projects'); ?>">Projects</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($project->code); ?></li>
            </ol>
        </nav>
        <h1 class="h3 mb-0"><?php echo htmlspecialchars($project->name); ?></h1>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('projects'); ?>">Back</a>
      <?php if(function_exists('has_module_access') && has_module_access('projects_edit')): ?>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('projects/'.$project->id.'/edit'); ?>">Edit Project</a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && has_module_access('projects_delete')): ?>
      <form method="post" action="<?php echo site_url('projects/'.$project->id.'/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this project?');">
        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats Row -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted small text-uppercase">Project Progress</h6>
                <div class="d-flex align-items-center mt-3">
                    <div class="flex-grow-1">
                        <div class="h2 mb-0"><?php echo $progress; ?>%</div>
                        <div class="small text-muted">Completed</div>
                    </div>
                    <div class="ms-3">
                        <div class="progress" style="height: 60px; width: 60px; border-radius: 50%; background: conic-gradient(var(--bs-success) <?php echo $progress; ?>%, #e9ecef 0);">
                            <div class="d-flex align-items-center justify-content-center h-100 w-100 bg-white rounded-circle m-1" style="width: calc(100% - 8px); height: calc(100% - 8px);">
                                <i class="bi bi-graph-up text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted small text-uppercase">Task Overview</h6>
                <div class="d-flex flex-column gap-2 mt-3">
                    <div class="d-flex justify-content-between small">
                        <span>Pending</span>
                        <span class="badge bg-secondary rounded-pill"><?php echo $stats['pending']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>In Progress</span>
                        <span class="badge bg-info rounded-pill"><?php echo $stats['in_progress']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>Blocked</span>
                        <span class="badge bg-danger rounded-pill"><?php echo $stats['blocked']; ?></span>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span>Completed</span>
                        <span class="badge bg-success rounded-pill"><?php echo $stats['completed']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted small text-uppercase">Dates & Status</h6>
                <div class="mt-3">
                    <div class="mb-2">
                        <span class="badge bg-<?php echo ($project->status==='completed'?'success':($project->status==='active'?'primary':'secondary')); ?> w-100 py-2 text-uppercase">
                            <?php echo htmlspecialchars($project->status); ?>
                        </span>
                    </div>
                    <div class="small text-muted mb-1"><i class="bi bi-calendar-event me-2"></i>Start: <?php echo $project->start_date ? date('M j, Y', strtotime($project->start_date)) : '-'; ?></div>
                    <div class="small text-muted"><i class="bi bi-calendar-check me-2"></i>End: <?php echo $project->end_date ? date('M j, Y', strtotime($project->end_date)) : '-'; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted small text-uppercase">Team</h6>
                <div class="mt-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-group">
                            <?php $count = 0; foreach($members as $m): if($count > 3) break; ?>
                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center border border-white" title="<?php echo htmlspecialchars($m->name ?: $m->email); ?>" style="width: 32px; height: 32px; font-size: 12px; margin-left: -10px;">
                                <?php echo strtoupper(substr($m->name ?: $m->email, 0, 1)); ?>
                            </div>
                            <?php $count++; endforeach; ?>
                            <?php if(count($members) > 4): ?>
                            <div class="avatar-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center border border-white" style="width: 32px; height: 32px; font-size: 10px; margin-left: -10px;">
                                +<?php echo count($members) - 4; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if(function_exists('has_module_access') && (has_module_access('projects_edit') || has_module_access('projects') || is_admin_group())): ?>
                    <a href="<?php echo site_url('projects/'.$project->id.'/members'); ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">Manage Members</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Main Content Tabs -->
  <ul class="nav nav-tabs mb-4" id="projectTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab" aria-controls="tasks" aria-selected="true"><i class="bi bi-list-check me-2"></i>Tasks (<?php echo count($tasks); ?>)</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="requirements-tab" data-bs-toggle="tab" data-bs-target="#requirements" type="button" role="tab" aria-controls="requirements" aria-selected="false"><i class="bi bi-clipboard-check me-2"></i>Requirements (<?php echo count($requirements); ?>)</button>
    </li>
  </ul>

  <div class="tab-content" id="projectTabsContent">
    <!-- Tasks Tab -->
    <div class="tab-pane fade show active" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Project Tasks</h6>
                <?php if(function_exists('has_module_access') && has_module_access('tasks_add')): ?>
                <a href="<?php echo site_url('tasks/board?project_id='.$project->id); ?>" class="btn btn-outline-secondary btn-sm me-2"><i class="bi bi-kanban me-1"></i>Board View</a>
                <a href="<?php echo site_url('tasks/create?project_id='.$project->id); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Task</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="40%">Title</th>
                            <th width="15%">Status</th>
                            <th width="15%">Priority</th>
                            <th width="15%">Assignee</th>
                            <th width="10%">Due</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($tasks)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No tasks found for this project.</td></tr>
                        <?php else: foreach($tasks as $t): ?>
                        <tr>
                            <td><a href="<?php echo site_url('tasks/'.$t->id); ?>" class="text-decoration-none">#<?php echo $t->id; ?></a></td>
                            <td>
                                <a href="<?php echo site_url('tasks/'.$t->id); ?>" class="fw-medium text-dark text-decoration-none"><?php echo htmlspecialchars($t->title); ?></a>
                                <?php if($t->requirement_id): ?><br><small class="text-muted"><i class="bi bi-link-45deg"></i> Req #<?php echo $t->requirement_id; ?></small><?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $status_colors = ['pending'=>'secondary', 'in_progress'=>'info', 'completed'=>'success', 'blocked'=>'danger'];
                                $s_color = isset($status_colors[$t->status]) ? $status_colors[$t->status] : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $s_color; ?>"><?php echo ucfirst(str_replace('_',' ',$t->status)); ?></span>
                            </td>
                            <td>
                                <?php 
                                $p_colors = ['low'=>'success', 'medium'=>'warning', 'high'=>'danger', 'urgent'=>'dark'];
                                $p_color = isset($p_colors[$t->priority]) ? $p_colors[$t->priority] : 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $p_color; ?>"><?php echo ucfirst($t->priority); ?></span>
                            </td>
                            <td>
                                <?php if($t->assigned_to): ?>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-xs bg-light rounded-circle text-center me-2" style="width:24px;height:24px;line-height:24px;font-size:10px;">
                                        <?php echo strtoupper(substr($t->assignee_name ?: $t->assignee_email, 0, 1)); ?>
                                    </div>
                                    <span class="small"><?php echo htmlspecialchars($t->assignee_name ?: explode('@', $t->assignee_email)[0]); ?></span>
                                </div>
                                <?php else: ?><span class="text-muted small">-</span><?php endif; ?>
                            </td>
                            <td><small><?php echo $t->due_date ? date('M j', strtotime($t->due_date)) : '-'; ?></small></td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-link text-muted" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="<?php echo site_url('tasks/'.$t->id); ?>">View</a></li>
                                        <?php if(function_exists('has_module_access') && (has_module_access('tasks_edit') || has_module_access('tasks'))): ?>
                                        <li><a class="dropdown-item" href="<?php echo site_url('tasks/'.$t->id.'/edit'); ?>">Edit</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Requirements Tab -->
    <div class="tab-pane fade" id="requirements" role="tabpanel" aria-labelledby="requirements-tab">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Linked Requirements</h6>
                <?php if(function_exists('has_module_access') && has_module_access('requirements_add')): ?>
                <a href="<?php echo site_url('requirements/create?project_id='.$project->id); ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Requirement</a>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Number</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($requirements)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No requirements linked to this project.</td></tr>
                        <?php else: foreach($requirements as $r): ?>
                        <tr>
                            <td>#<?php echo $r->id; ?></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($r->req_number); ?></td>
                            <td><?php echo htmlspecialchars($r->title); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($r->status); ?></span></td>
                            <td class="text-end"><a href="<?php echo site_url('requirements/view/'.$r->id); ?>" class="btn btn-sm btn-light">View</a></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
