<?php $this->load->view('partials/header', ['title' => 'New Requirement']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">New Requirement</h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('requirements'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="" class="vstack gap-3" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Client</label>
          <select name="client_id" class="form-select" required>
            <option value="">-- Select Client --</option>
            <?php if (isset($clients) && is_array($clients)) foreach ($clients as $c): ?>
              <option value="<?php echo (int)$c->id; ?>"><?php echo htmlspecialchars($c->company_name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Project</label>
          <select name="project_id" class="form-select">
            <option value="">-- None --</option>
            <?php if (isset($projects) && is_array($projects)) foreach ($projects as $p): ?>
              <option value="<?php echo (int)$p->id; ?>"><?php echo htmlspecialchars($p->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Type</label>
          <select name="requirement_type" class="form-select">
            <?php $types = array('new_feature','enhancement','bug_fix','maintenance','consultation','other'); foreach ($types as $t): ?>
              <option value="<?php echo htmlspecialchars($t); ?>"><?php echo ucfirst(str_replace('_',' ',$t)); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Description</label>
          <textarea name="description" id="description" rows="6" class="form-control"></textarea>
        </div>
        <div class="col-md-3">
          <label class="form-label">Priority</label>
          <select name="priority" class="form-select">
            <?php $priorities = array('low','medium','high','critical'); foreach ($priorities as $pr): ?>
              <option value="<?php echo htmlspecialchars($pr); ?>"><?php echo ucfirst($pr); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Expected Delivery</label>
          <input type="date" name="expected_delivery_date" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Received Date</label>
          <input type="date" name="received_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Budget (<?php echo 'INR'; ?>)</label>
          <input type="number" step="0.01" name="budget_estimate" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Owner</label>
          <select name="owner_id" class="form-select">
            <option value="">-- None --</option>
            <?php if (isset($members) && is_array($members)) foreach ($members as $m): ?>
              <?php $label = '';
                if (isset($m->full_label) && $m->full_label!=='') { $label = $m->full_label; }
                else if (isset($m->full_name) && $m->full_name!=='') { $label = $m->full_name; }
                else if (isset($m->name) && $m->name!=='') { $label = $m->name; }
                else if (isset($m->email)) { $label = $m->email; }
              ?>
              <option value="<?php echo (int)$m->id; ?>"><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Attachments</label>
          <input type="file" name="attachments[]" class="form-control" multiple>
        </div>
      </div>
      <div>
        <button class="btn btn-primary">Create</button>
        <a class="btn btn-light" href="<?php echo site_url('requirements'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
<script src="https://cdn.ckeditor.com/4.21.0/standard-all/ckeditor.js"></script>
<script>
  if (window.CKEDITOR){
    CKEDITOR.replace('description', {
      extraPlugins: 'table,autogrow',
      autoGrow_minHeight: 200,
      removePlugins: 'elementspath',
      resize_enabled: true
    });
  }
</script>
