<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo ($action === 'edit') ? 'Edit' : 'Create'; ?> Employee</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h1 class="h4 mb-3"><?php echo ($action === 'edit') ? 'Edit' : 'Create'; ?> Employee</h1>
  <form method="post">
    <div class="row g-3">
      <?php if ($action === 'create'): ?>
      <div class="col-md-6">
        <label class="form-label">User ID</label>
        <input type="number" name="user_id" class="form-control" required>
        <div class="form-text">Link to existing user (users.id)</div>
      </div>
      <?php endif; ?>
      <div class="col-md-6">
        <label class="form-label">Employee Code</label>
        <input type="text" name="emp_code" class="form-control" value="<?php echo htmlspecialchars($employee->emp_code ?? ''); ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">First Name</label>
        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($employee->first_name ?? ''); ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Last Name</label>
        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($employee->last_name ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Department</label>
        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($employee->department ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Designation</label>
        <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars($employee->designation ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Reporting To (User ID)</label>
        <input type="number" name="reporting_to" class="form-control" value="<?php echo htmlspecialchars($employee->reporting_to ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Employment Type</label>
        <select name="employment_type" class="form-select">
          <?php $et = $employee->employment_type ?? 'full_time'; ?>
          <option value="full_time" <?php echo $et==='full_time'?'selected':''; ?>>Full time</option>
          <option value="part_time" <?php echo $et==='part_time'?'selected':''; ?>>Part time</option>
          <option value="contract" <?php echo $et==='contract'?'selected':''; ?>>Contract</option>
          <option value="intern" <?php echo $et==='intern'?'selected':''; ?>>Intern</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Join Date</label>
        <input type="date" name="join_date" class="form-control" value="<?php echo htmlspecialchars($employee->join_date ?? ''); ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($employee->phone ?? ''); ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button class="btn btn-primary" type="submit"><?php echo ($action === 'edit') ? 'Update' : 'Create'; ?></button>
      <a class="btn btn-secondary" href="<?php echo site_url('employees'); ?>">Cancel</a>
    </div>
  </form>
</div>
</body>
</html>
