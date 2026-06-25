<?php $this->load->view('partials/header', ['title' => 'Tasks']); ?>
<div class="container-fluid py-3">
<?php $this->load->view('partials/import_errors'); ?>
<div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div>
    <h1 class="h4 mb-1 fw-bold"><i class="bi bi-list-check text-primary me-2"></i>Tasks</h1>
    <p class="text-muted small mb-0">Track and manage all tasks</p>
  </div>
  <div class="d-flex gap-2 mt-2 mt-sm-0">
    <?php if(function_exists('has_module_access') && (has_module_access('tasks_add') || has_module_access('tasks'))): ?>
    <a class="btn btn-primary btn-sm" title="Create" href="<?php echo site_url('tasks/create'); ?>"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New Task</span></a>
    <?php endif; ?>
    <?php if(function_exists('has_module_access') && (has_module_access('tasks_import') || has_module_access('tasks'))): ?>
    <a class="btn btn-outline-secondary btn-sm" title="Import CSV" href="<?php echo site_url('tasks/import'); ?>"><i class="bi bi-upload me-1"></i><span class="d-none d-sm-inline">Import</span></a>
    <?php endif; ?>
    <a class="btn btn-outline-dark btn-sm" title="Board View" href="<?php echo site_url('tasks/board'); ?>"><i class="bi bi-kanban me-1"></i><span class="d-none d-sm-inline">Board</span></a>
  </div>
</div>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" action="<?php echo site_url('tasks'); ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Project</label>
        <select name="project_id" class="form-select">
          <option value="">All</option>
          <?php if (isset($projects) && is_array($projects)) foreach ($projects as $p): ?>
            <option value="<?php echo (int)$p->id; ?>" <?php echo (isset($filter_project_id) && (string)$filter_project_id === (string)$p->id) ? 'selected' : ''; ?>><?php echo esc_view(isset($p->name) ? $p->name : ('#'.(int)$p->id)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (isset($is_admin) && $is_admin): ?>
      <div class="col-md-3">
        <label class="form-label">Assignee</label>
        <select name="assigned_to" class="form-select">
          <option value="">All</option>
          <?php if (isset($assignees) && is_array($assignees)) foreach ($assignees as $u): ?>
            <?php 
              $label = '';
              if (isset($u->emp_name) && $u->emp_name !== '') { $label = $u->emp_name; }
              else if (isset($u->full_name) && $u->full_name !== '') { $label = $u->full_name; }
              else if (isset($u->name) && $u->name !== '') { $label = $u->name; }
              else if (isset($u->email) && $u->email !== '') { $label = $u->email; }
            ?>
            <option value="<?php echo (int)$u->id; ?>" <?php echo (isset($filter_assigned_to) && (string)$filter_assigned_to === (string)$u->id) ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-md-2">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <?php $statuses = array('', 'pending','in_progress','completed','blocked');
          foreach ($statuses as $st): ?>
            <option value="<?php echo esc_view($st); ?>" <?php echo (isset($filter_status) && (string)$filter_status === (string)$st) ? 'selected' : ''; ?>><?php echo $st === '' ? 'All' : ucfirst(str_replace('_',' ',$st)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Priority</label>
        <select name="priority" class="form-select">
          <?php $priorities = array('', 'low','medium','high','urgent');
          foreach ($priorities as $pr): ?>
            <option value="<?php echo esc_view($pr); ?>" <?php echo (isset($filter_priority) && (string)$filter_priority === (string)$pr) ? 'selected' : ''; ?>><?php echo $pr === '' ? 'All' : ucfirst($pr); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
      </div>
    </form>
  </div>
</div>

<?php
  $tasks_priority_col = (isset($is_admin) && $is_admin) ? 6 : 5;
  $tasks_priority_rank = array('urgent' => 1, 'high' => 2, 'medium' => 3, 'low' => 4);
  $tasks_priority_badge = array(
    'urgent' => 'danger',
    'high'   => 'danger',
    'medium' => 'warning text-dark',
    'low'    => 'success',
  );
?>
<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle datatable" data-order-col="<?php echo (int) $tasks_priority_col; ?>" data-order-dir="asc">
        <thead>
          <tr>
            <th>#</th>
            <th>Project</th>
            <th>Title</th>
            <th>Description</th>
            <?php if (isset($is_admin) && $is_admin): ?>
            <th>Assignee</th>
            <?php endif; ?>
            <th>Status</th>
            <th>Priority</th>
            <th class="text-end" style="min-width: 130px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!empty($tasks)) foreach($tasks as $t): ?>
            <tr>
              <td><?php echo (int)$t->id; ?></td>
              <td><?php echo esc_view(isset($t->project_name) && $t->project_name !== '' ? $t->project_name : ('#'.(int)$t->project_id)); ?></td>
              <td><?php echo esc_view($t->title); ?></td>
              <td class="small">
                <div class="text-muted text-truncate-2" style="max-width: 420px;">
                  <?php 
                    $allowed = '<p><br><strong><em><b><i><ul><ol><li><a>';
                    $desc = isset($t->description) ? strip_tags($t->description, $allowed) : '';
                    echo $desc;
                  ?>
                </div>
              </td>
              <?php if (isset($is_admin) && $is_admin): ?>
              <td>
                <?php 
                  $assignee = '';
                  if (isset($t->emp_name) && $t->emp_name !== '') { $assignee = $t->emp_name; }
                  else if (isset($t->full_name) && $t->full_name !== '') { $assignee = $t->full_name; }
                  else if (isset($t->name) && $t->name !== '') { $assignee = $t->name; }
                  else if (isset($t->assignee_email) && $t->assignee_email !== '') { $assignee = $t->assignee_email; }
                  echo esc_view($assignee !== '' ? $assignee : '—');
                ?>
              </td>
              <?php endif; ?>
              <td><span class="badge bg-info text-dark"><?php echo esc_view($t->status); ?></span></td>
              <?php
                $task_priority = isset($t->priority) ? strtolower((string) $t->priority) : 'medium';
                $task_priority_sort = isset($tasks_priority_rank[$task_priority]) ? (int) $tasks_priority_rank[$task_priority] : 5;
              ?>
              <?php $task_priority_badge = isset($tasks_priority_badge[$task_priority]) ? $tasks_priority_badge[$task_priority] : 'secondary'; ?>
              <td data-order="<?php echo $task_priority_sort; ?>"><span class="badge bg-<?php echo $task_priority_badge; ?>"><?php echo esc_view(ucfirst($task_priority !== '' ? $task_priority : 'medium')); ?></span></td>
              <td class="text-end text-nowrap table-actions">
                <div class="d-inline-flex align-items-center justify-content-end gap-1 flex-nowrap">
                  <a class="btn btn-light btn-sm" title="View" href="<?php echo site_url('tasks/'.$t->id); ?>"><i class="bi bi-eye"></i></a>
                  <?php if(function_exists('has_module_access') && (has_module_access('tasks_edit') || has_module_access('tasks'))): ?>
                  <a class="btn btn-primary btn-sm" title="Edit" href="<?php echo site_url('tasks/'.$t->id.'/edit'); ?>"><i class="bi bi-pencil"></i></a>
                  <?php endif; ?>
                  <?php if(function_exists('has_module_access') && (has_module_access('tasks_delete') || has_module_access('tasks'))): ?>
                  <form method="post" action="<?php echo site_url('tasks/'.$t->id.'/delete'); ?>" class="d-inline m-0" onsubmit="return confirm('Delete this task?');">
                    <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                    <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>

