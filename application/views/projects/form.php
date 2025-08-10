<?php $this->load->view('partials/header', ['title' => ($action === 'edit' ? 'Edit Project' : 'Create Project')]); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo $action === 'edit' ? 'Edit Project' : 'Create Project'; ?></h1>
  <div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('projects'); ?>">Back to Projects</a>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo $action === 'edit' ? site_url('projects/'.$project->id.'/edit') : site_url('projects/create'); ?>">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Code</label>
          <input type="text" name="code" class="form-control" value="<?php echo isset($project) ? htmlspecialchars($project->code) : ''; ?>" placeholder="PRJ-001">
        </div>
        <div class="col-md-8">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input required type="text" name="name" class="form-control" value="<?php echo isset($project) ? htmlspecialchars($project->name) : ''; ?>" placeholder="Website Redesign">
        </div>
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <?php $st = isset($project) ? (string)$project->status : 'planned'; ?>
          <select name="status" class="form-select">
            <option value="planned" <?php echo $st==='planned'?'selected':''; ?>>Planned</option>
            <option value="in_progress" <?php echo $st==='in_progress'?'selected':''; ?>>In Progress</option>
            <option value="completed" <?php echo $st==='completed'?'selected':''; ?>>Completed</option>
            <option value="on_hold" <?php echo $st==='on_hold'?'selected':''; ?>>On Hold</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Start Date</label>
          <input type="date" name="start_date" class="form-control" value="<?php echo isset($project) ? htmlspecialchars($project->start_date) : ''; ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">End Date</label>
          <input type="date" name="end_date" class="form-control" value="<?php echo isset($project) ? htmlspecialchars($project->end_date) : ''; ?>">
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit"><?php echo $action === 'edit' ? 'Save Changes' : 'Create Project'; ?></button>
        <a class="btn btn-light" href="<?php echo site_url('projects'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
