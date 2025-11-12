<?php $this->load->view('partials/header', ['title' => (($action === 'edit') ? 'Edit' : 'Create').' Department']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo ($action === 'edit') ? 'Edit' : 'Create'; ?> Department</h1>
  <a class="btn btn-secondary btn-sm" href="<?php echo site_url('departments'); ?>">Back</a>
</div>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Code</label>
          <input class="form-control" name="dept_code" value="<?php echo htmlspecialchars(isset($row->dept_code)?$row->dept_code:''); ?>" required />
        </div>
        <div class="col-md-8">
          <label class="form-label">Name</label>
          <input class="form-control" name="dept_name" value="<?php echo htmlspecialchars(isset($row->dept_name)?$row->dept_name:''); ?>" required />
        </div>
        <div class="col-md-12">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars(isset($row->description)?$row->description:''); ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Manager (User)</label>
          <select class="form-select" name="manager_id">
            <option value="">-- Select --</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int)$u->id; ?>" <?php echo (isset($row->manager_id) && (int)$row->manager_id===(int)$u->id)?'selected':''; ?>><?php echo htmlspecialchars($u->email.(!empty($u->name)?' ('.$u->name.')':'')); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-primary"><?php echo ($action === 'edit') ? 'Update' : 'Create'; ?></button>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
