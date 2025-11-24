<?php $this->load->view('partials/header', ['title' => 'Employee #'.(int)$employee->id]); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Employee #<?php echo (int)$employee->id; ?></h1>
    <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
      <a class="btn btn-secondary w-100 w-sm-auto" href="<?php echo site_url('employees'); ?>">Back</a>
      <a class="btn btn-outline-secondary w-100 w-sm-auto" href="<?php echo site_url('employees/'.$employee->id.'/documents'); ?>">Documents</a>
      <a class="btn btn-primary w-100 w-sm-auto" href="<?php echo site_url('employees/'.$employee->id.'/edit'); ?>">Edit</a>
    </div>
  </div>
  <div class="row g-3">
    <div class="col-12 col-md-6">
      <div class="card h-100 shadow-soft"><div class="card-body">
        <h5 class="card-title">Profile</h5>
        <p class="mb-1"><strong>Code:</strong> <?php echo htmlspecialchars($employee->emp_code); ?></p>
        <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars(trim((isset($employee->first_name) ? $employee->first_name : '').' '.(isset($employee->last_name) ? $employee->last_name : ''))); ?></p>
        <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars(isset($employee->email) ? $employee->email : ''); ?></p>
        <p class="mb-1"><strong>Personal Email:</strong> <?php echo htmlspecialchars(isset($employee->personal_email) ? $employee->personal_email : ''); ?></p>
        <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars(isset($employee->phone) ? $employee->phone : ''); ?></p>
        <p class="mb-1"><strong>Location:</strong> <?php echo htmlspecialchars(isset($employee->location) ? $employee->location : ''); ?></p>
        <p class="mb-1"><strong>Address:</strong> <?php echo htmlspecialchars(isset($employee->address) ? $employee->address : ''); ?></p>
        <p class="mb-1"><strong>City:</strong> <?php echo htmlspecialchars(isset($employee->city) ? $employee->city : ''); ?></p>
        <p class="mb-1"><strong>State:</strong> <?php echo htmlspecialchars(isset($employee->state) ? $employee->state : ''); ?></p>
        <p class="mb-1"><strong>Country:</strong> <?php echo htmlspecialchars(isset($employee->country) ? $employee->country : ''); ?></p>
        <p class="mb-1"><strong>Pincode:</strong> <?php echo htmlspecialchars(isset($employee->zipcode) ? $employee->zipcode : ''); ?></p>
        <p class="mb-1"><strong>Department:</strong> <?php echo htmlspecialchars(isset($employee->department) ? $employee->department : ''); ?></p>
        <p class="mb-1"><strong>Designation:</strong> <?php echo htmlspecialchars(isset($employee->designation) ? $employee->designation : ''); ?></p>
        <p class="mb-1"><strong>Employment Type:</strong> <?php echo htmlspecialchars(isset($employee->employment_type) ? $employee->employment_type : ''); ?></p>
        <p class="mb-1"><strong>Date of Birth:</strong> <?php echo htmlspecialchars(isset($employee->dob) ? $employee->dob : ''); ?></p>
        <p class="mb-1"><strong>Join Date:</strong> <?php echo htmlspecialchars(isset($employee->join_date) ? $employee->join_date : ''); ?></p>
        <p class="mb-1"><strong>Salary (CTC):</strong> <?php echo htmlspecialchars(isset($employee->salary_ctc) ? $employee->salary_ctc : ''); ?></p>
        <p class="mb-1"><strong>Emergency Contact:</strong> <?php echo htmlspecialchars(isset($employee->emergency_contact_name) ? $employee->emergency_contact_name : ''); ?> (<?php echo htmlspecialchars(isset($employee->emergency_contact_phone) ? $employee->emergency_contact_phone : ''); ?>)</p>
      </div></div>
    </div>
    <div class="col-md-6">
      <div class="card h-100 shadow-soft"><div class="card-body">
        <h5 class="card-title">Reporting</h5>
        <p class="mb-1"><strong>Reporting To (User ID):</strong> <?php echo htmlspecialchars(isset($employee->reporting_to) ? $employee->reporting_to : ''); ?></p>
        <p class="mb-1"><strong>User:</strong> <?php echo htmlspecialchars((isset($employee->user_name) ? $employee->user_name : '').' (ID: '.(isset($employee->user_id) ? $employee->user_id : '').')'); ?></p>
        <p class="text-muted">Add more sections (address, emergency contact, etc.) later.</p>
      </div></div>
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
