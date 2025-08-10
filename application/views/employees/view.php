<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Employee Details</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Employee #<?php echo (int)$employee->id; ?></h1>
    <div>
      <a class="btn btn-secondary" href="<?php echo site_url('employees'); ?>">Back</a>
      <a class="btn btn-primary" href="<?php echo site_url('employees/'.$employee->id.'/edit'); ?>">Edit</a>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-md-6">
      <div class="card h-100"><div class="card-body">
        <h5 class="card-title">Profile</h5>
        <p class="mb-1"><strong>Code:</strong> <?php echo htmlspecialchars($employee->emp_code); ?></p>
        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars(trim(($employee->first_name ?? '').' '.($employee->last_name ?? ''))); ?></p>
        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($employee->email ?? ''); ?></p>
        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($employee->phone ?? ''); ?></p>
        <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars($employee->department ?? ''); ?></p>
        <p class="mb-1"><strong>Designation:</strong> <?php echo htmlspecialchars($employee->designation ?? ''); ?></p>
        <p class="mb-1"><strong>Employment Type:</strong> <?php echo htmlspecialchars($employee->employment_type ?? ''); ?></p>
        <p class="mb-1"><strong>Join Date:</strong> <?php echo htmlspecialchars($employee->join_date ?? ''); ?></p>
      </div></div>
    </div>
    <div class="col-md-6">
      <div class="card h-100"><div class="card-body">
        <h5 class="card-title">Reporting</h5>
        <p class="mb-1"><strong>Reporting To (User ID):</strong> <?php echo htmlspecialchars($employee->reporting_to ?? ''); ?></p>
        <p class="mb-1"><strong>User:</strong> <?php echo htmlspecialchars(($employee->user_name ?? '').' (ID: '.($employee->user_id ?? '').')'); ?></p>
        <p class="text-muted">Add more sections (address, emergency contact, etc.) later.</p>
      </div></div>
    </div>
  </div>
</div>
</body>
</html>
