<?php $this->load->view('partials/header', ['title' => 'Tasks']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Tasks</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" title="Create" href="<?php echo site_url('tasks/create'); ?>"><i class="bi bi-plus-lg"></i></a>
    <a class="btn btn-outline-secondary btn-sm" title="Import CSV" href="<?php echo site_url('tasks/import'); ?>"><i class="bi bi-upload"></i></a>
    <a class="btn btn-outline-dark btn-sm" title="Board" href="<?php echo site_url('tasks/board'); ?>"><i class="bi bi-kanban"></i></a>
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
            <option value="<?php echo (int)$p->id; ?>" <?php echo (isset($filter_project_id) && (string)$filter_project_id === (string)$p->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars(isset($p->name) ? $p->name : ('#'.(int)$p->id)); ?></option>
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
            <option value="<?php echo (int)$u->id; ?>" <?php echo (isset($filter_assigned_to) && (string)$filter_assigned_to === (string)$u->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-md-2">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <?php $statuses = array('', 'pending','in_progress','completed','blocked');
          foreach ($statuses as $st): ?>
            <option value="<?php echo htmlspecialchars($st); ?>" <?php echo (isset($filter_status) && (string)$filter_status === (string)$st) ? 'selected' : ''; ?>><?php echo $st === '' ? 'All' : ucfirst(str_replace('_',' ',$st)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Priority</label>
        <select name="priority" class="form-select">
          <?php $priorities = array('', 'low','medium','high','urgent');
          foreach ($priorities as $pr): ?>
            <option value="<?php echo htmlspecialchars($pr); ?>" <?php echo (isset($filter_priority) && (string)$filter_priority === (string)$pr) ? 'selected' : ''; ?>><?php echo $pr === '' ? 'All' : ucfirst($pr); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle datatable" data-order-col="2" data-order-dir="asc">
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
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!empty($tasks)) foreach($tasks as $t): ?>
            <tr>
              <td><?php echo (int)$t->id; ?></td>
              <td><?php echo htmlspecialchars(isset($t->project_name) && $t->project_name !== '' ? $t->project_name : ('#'.(int)$t->project_id)); ?></td>
              <td><?php echo htmlspecialchars($t->title); ?></td>
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
                  echo htmlspecialchars($assignee !== '' ? $assignee : '—');
                ?>
              </td>
              <?php endif; ?>
              <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($t->status); ?></span></td>
              <td><span class="badge bg-secondary"><?php echo htmlspecialchars(isset($t->priority) ? $t->priority : ''); ?></span></td>
              <td class="text-end">
                <a class="btn btn-light btn-sm" title="View" href="<?php echo site_url('tasks/'.$t->id); ?>"><i class="bi bi-eye"></i></a>
                <a class="btn btn-primary btn-sm" title="Edit" href="<?php echo site_url('tasks/'.$t->id.'/edit'); ?>"><i class="bi bi-pencil"></i></a>
                <a class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this task?')" href="<?php echo site_url('tasks/'.$t->id.'/delete'); ?>"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>

