<?php $this->load->view('partials/header', ['title' => 'Tasks']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Tasks</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" title="Create" href="<?php echo site_url('tasks/create'); ?>"><i class="bi bi-plus-lg"></i></a>
    <a class="btn btn-outline-secondary btn-sm" title="Import CSV" href="<?php echo site_url('tasks/import'); ?>"><i class="bi bi-upload"></i></a>
    <a class="btn btn-outline-dark btn-sm" title="Board" href="<?php echo site_url('tasks/board'); ?>"><i class="bi bi-kanban"></i></a>
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
            <th>Status</th>
            <th>Priority</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!empty($tasks)) foreach($tasks as $t): ?>
            <tr>
              <td><?php echo (int)$t->id; ?></td>
              <td><?php echo (int)$t->project_id; ?></td>
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
              <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($t->status); ?></span></td>
              <td><span class="badge bg-secondary"><?php echo htmlspecialchars($t->priority); ?></span></td>
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
