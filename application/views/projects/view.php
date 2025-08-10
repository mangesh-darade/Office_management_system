<?php $this->load->view('partials/header', ['title' => 'Project Details']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Project #<?php echo (int)$project->id; ?></h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('projects'); ?>">Back</a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('projects/'.$project->id.'/edit'); ?>">Edit</a>
    <a class="btn btn-danger btn-sm" onclick="return confirm('Delete this project?')" href="<?php echo site_url('projects/'.$project->id.'/delete'); ?>">Delete</a>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-4"><div class="text-muted small">Code</div><div class="fw-semibold"><?php echo htmlspecialchars($project->code); ?></div></div>
      <div class="col-md-8"><div class="text-muted small">Name</div><div class="fw-semibold"><?php echo htmlspecialchars($project->name); ?></div></div>
      <div class="col-md-4"><div class="text-muted small">Status</div><div><span class="badge bg-info text-dark"><?php echo htmlspecialchars($project->status); ?></span></div></div>
      <div class="col-md-4"><div class="text-muted small">Start</div><div class="fw-semibold"><?php echo htmlspecialchars($project->start_date); ?></div></div>
      <div class="col-md-4"><div class="text-muted small">End</div><div class="fw-semibold"><?php echo htmlspecialchars($project->end_date); ?></div></div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
