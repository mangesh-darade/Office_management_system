<?php $this->load->view('partials/header', ['title' => (($action === 'edit') ? 'Edit' : 'Create').' Employee']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0"><?php echo ($action === 'edit') ? 'Edit' : 'Create'; ?> Employee</h1>
    <a class="btn btn-secondary" href="<?php echo site_url('employees'); ?>">Back</a>
  </div>
  <div class="card shadow-soft">
    <div class="card-body">
  
  
  <form method="post">
    <div class="row g-3">
      <?php if ($action === 'create'): ?>
      <div class="col-12 col-md-6">
        <label class="form-label">User</label>
        <select name="user_id" class="form-select" required>
          <option value="">-- Select user --</option>
          <?php if (!empty($users)) : foreach ($users as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['label']); ?> (ID: <?php echo (int)$u['id']; ?>)</option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-12 col-md-6">
        <label class="form-label">Employee Code</label>
        <input type="text" name="emp_code" class="form-control" value="<?php echo htmlspecialchars(isset($employee->emp_code) ? $employee->emp_code : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars(isset($employee->first_name) ? $employee->first_name : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars(isset($employee->last_name) ? $employee->last_name : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Department</label>
        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars(isset($employee->department) ? $employee->department : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Designation</label>
        <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars(isset($employee->designation) ? $employee->designation : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Reporting To</label>
        <select name="reporting_to" class="form-select">
          <option value="">-- None --</option>
          <?php $rt = isset($employee->reporting_to) ? (int)$employee->reporting_to : 0; ?>
          <?php if (!empty($users)) : foreach ($users as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>" <?php echo ($rt === (int)$u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['label']); ?> (ID: <?php echo (int)$u['id']; ?>)</option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Employment Type</label>
        <select name="employment_type" class="form-select">
          <?php $et = isset($employee->employment_type) ? $employee->employment_type : 'full_time'; ?>
          <option value="full_time" <?php echo $et==='full_time'?'selected':''; ?>>Full time</option>
          <option value="part_time" <?php echo $et==='part_time'?'selected':''; ?>>Part time</option>
          <option value="contract" <?php echo $et==='contract'?'selected':''; ?>>Contract</option>
          <option value="intern" <?php echo $et==='intern'?'selected':''; ?>>Intern</option>
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Join Date</label>
        <input type="date" name="join_date" class="form-control" value="<?php echo htmlspecialchars(isset($employee->join_date) ? $employee->join_date : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars(isset($employee->phone) ? $employee->phone : ''); ?>">
      </div>
    </div>
    <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
      <button class="btn btn-primary w-100 w-sm-auto" type="submit"><?php echo ($action === 'edit') ? 'Update' : 'Create'; ?></button>
      <a class="btn btn-secondary w-100 w-sm-auto" href="<?php echo site_url('employees'); ?>">Cancel</a>
    </div>
  </form>
  
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
