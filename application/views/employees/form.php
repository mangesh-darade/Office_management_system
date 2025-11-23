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
        <label class="form-label">User <span class="text-danger">*</span></label>
        <select name="user_id" class="form-select" required>
          <option value="">-- Select user --</option>
          <?php if (!empty($users)) : foreach ($users as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['label']); ?> (ID: <?php echo (int)$u['id']; ?>)</option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-12 col-md-6">
        <label class="form-label">Employee Code <span class="text-danger">*</span></label>
        <input type="text" name="emp_code" class="form-control" value="<?php echo htmlspecialchars(isset($employee->emp_code) ? $employee->emp_code : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars(isset($employee->first_name) ? $employee->first_name : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars(isset($employee->last_name) ? $employee->last_name : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
        <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars(isset($employee->dob) ? $employee->dob : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Personal Email <span class="text-danger">*</span></label>
        <input type="email" name="personal_email" class="form-control" value="<?php echo htmlspecialchars(isset($employee->personal_email) ? $employee->personal_email : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Department <span class="text-danger">*</span></label>
        <select name="department_id" class="form-select" required>
          <option value="">-- Select department --</option>
          <?php
            $currentDeptName = isset($employee->department) ? (string)$employee->department : '';
            $currentDeptId = isset($employee->department_id) ? (int)$employee->department_id : 0;
          ?>
          <?php if (isset($departments) && !empty($departments)) : foreach ($departments as $d): ?>
            <?php
              $sel = '';
              if ($currentDeptId && $currentDeptId === (int)$d->id) {
                $sel = 'selected';
              } elseif (!$currentDeptId && $currentDeptName !== '' && $currentDeptName === (string)$d->dept_name) {
                $sel = 'selected';
              }
            ?>
            <option value="<?php echo (int)$d->id; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($d->dept_name); ?></option>
          <?php endforeach; endif; ?>
        </select>
        <input type="hidden" name="department" value="<?php echo htmlspecialchars(isset($employee->department) ? $employee->department : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Designation <span class="text-danger">*</span></label>
        <select name="designation_id" class="form-select" required>
          <option value="">-- Select designation --</option>
          <?php
            $currentDesgName = isset($employee->designation) ? (string)$employee->designation : '';
            $currentDesgId = isset($employee->designation_id) ? (int)$employee->designation_id : 0;
          ?>
          <?php if (isset($designations) && !empty($designations)) : foreach ($designations as $dg): ?>
            <?php
              $sel = '';
              if ($currentDesgId && $currentDesgId === (int)$dg->id) {
                $sel = 'selected';
              } elseif (!$currentDesgId && $currentDesgName !== '' && $currentDesgName === (string)$dg->designation_name) {
                $sel = 'selected';
              }
            ?>
            <option value="<?php echo (int)$dg->id; ?>" data-department-id="<?php echo isset($dg->department_id) ? (int)$dg->department_id : 0; ?>" <?php echo $sel; ?>>
              <?php echo htmlspecialchars($dg->designation_name); ?>
            </option>
          <?php endforeach; endif; ?>
        </select>
        <input type="hidden" name="designation" value="<?php echo htmlspecialchars(isset($employee->designation) ? $employee->designation : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Reporting To <span class="text-danger">*</span></label>
        <select name="reporting_to" class="form-select" required>
          <option value="">-- None --</option>
          <?php $rt = isset($employee->reporting_to) ? (int)$employee->reporting_to : 0; ?>
          <?php if (!empty($users)) : foreach ($users as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>" <?php echo ($rt === (int)$u['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($u['label']); ?> (ID: <?php echo (int)$u['id']; ?>)</option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Employment Type <span class="text-danger">*</span></label>
        <select name="employment_type" class="form-select" required>
          <?php $et = isset($employee->employment_type) ? $employee->employment_type : 'full_time'; ?>
          <option value="full_time" <?php echo $et==='full_time'?'selected':''; ?>>Full time</option>
          <option value="part_time" <?php echo $et==='part_time'?'selected':''; ?>>Part time</option>
          <option value="contract" <?php echo $et==='contract'?'selected':''; ?>>Contract</option>
          <option value="intern" <?php echo $et==='intern'?'selected':''; ?>>Intern</option>
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Join Date <span class="text-danger">*</span></label>
        <input type="date" name="join_date" class="form-control" value="<?php echo htmlspecialchars(isset($employee->join_date) ? $employee->join_date : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Location <span class="text-danger">*</span></label>
        <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars(isset($employee->location) ? $employee->location : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Phone <span class="text-danger">*</span></label>
        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars(isset($employee->phone) ? $employee->phone : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Address <span class="text-danger">*</span></label>
        <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars(isset($employee->address) ? $employee->address : ''); ?></textarea>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">City <span class="text-danger">*</span></label>
        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars(isset($employee->city) ? $employee->city : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">State <span class="text-danger">*</span></label>
        <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars(isset($employee->state) ? $employee->state : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Country <span class="text-danger">*</span></label>
        <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars(isset($employee->country) ? $employee->country : 'India'); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Pincode <span class="text-danger">*</span></label>
        <input type="text" name="zipcode" class="form-control" value="<?php echo htmlspecialchars(isset($employee->zipcode) ? $employee->zipcode : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Bank Name <span class="text-danger">*</span></label>
        <input type="text" name="bank_name" class="form-control" value="<?php echo htmlspecialchars(isset($employee->bank_name) ? $employee->bank_name : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Bank A/C No <span class="text-danger">*</span></label>
        <input type="text" name="bank_ac_no" class="form-control" value="<?php echo htmlspecialchars(isset($employee->bank_ac_no) ? $employee->bank_ac_no : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">PAN No <span class="text-danger">*</span></label>
        <input type="text" name="pan_no" class="form-control" value="<?php echo htmlspecialchars(isset($employee->pan_no) ? $employee->pan_no : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Salary (CTC) <span class="text-danger">*</span></label>
        <input type="number" step="0.01" name="salary_ctc" class="form-control" value="<?php echo htmlspecialchars(isset($employee->salary_ctc) ? $employee->salary_ctc : ''); ?>" required>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Emergency Contact Name</label>
        <input type="text" name="emergency_contact_name" class="form-control" value="<?php echo htmlspecialchars(isset($employee->emergency_contact_name) ? $employee->emergency_contact_name : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Emergency Contact Phone</label>
        <input type="text" name="emergency_contact_phone" class="form-control" value="<?php echo htmlspecialchars(isset($employee->emergency_contact_phone) ? $employee->emergency_contact_phone : ''); ?>">
      </div>
    </div>
    <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
      <button class="btn btn-primary w-100 w-sm-auto" type="submit"><?php echo ($action === 'edit') ? 'Update' : 'Create'; ?></button>
      <a class="btn btn-secondary w-100 w-sm-auto" href="<?php echo site_url('employees'); ?>">Cancel</a>
    </div>
  </form>
  
    </div>
  </div>
<script>
  (function(){
    var userSelect = document.querySelector('select[name="user_id"]');
    if (!userSelect) { return; }

    var firstNameInput = document.querySelector('input[name="first_name"]');
    var lastNameInput = document.querySelector('input[name="last_name"]');
    var phoneInput = document.querySelector('input[name="phone"]');
    var deptInput = document.querySelector('input[name="department"]');
    var desgInput = document.querySelector('input[name="designation"]');
    var deptSelect = document.querySelector('select[name="department_id"]');
    var desgSelect = document.querySelector('select[name="designation_id"]');

    function fill(el, value) {
      if (!el) { return; }
      if (value !== null && value !== undefined && value !== '') {
        el.value = value;
      }
    }

    userSelect.addEventListener('change', function(){
      var uid = this.value;
      if (!uid) { return; }
      var url = '<?php echo site_url('employees/user_meta'); ?>/' + encodeURIComponent(uid);
      fetch(url, { headers: { 'Accept': 'application/json' } })
        .then(function(resp){ return resp.json(); })
        .then(function(json){
          if (!json || !json.success || !json.data) { return; }
          var d = json.data;
          fill(firstNameInput, d.first_name);
          fill(lastNameInput, d.last_name);
          fill(phoneInput, d.phone);
          fill(deptInput, d.department);
          fill(desgInput, d.designation);
        })
        .catch(function(err){
          if (window.console && console.error) {
            console.error(err);
          }
        });
    });

    if (deptSelect) {
      deptSelect.addEventListener('change', function(){
        var opt = deptSelect.options[deptSelect.selectedIndex];
        if (opt && deptInput) {
          deptInput.value = opt.textContent || '';
        }
      });
    }

    if (desgSelect) {
      desgSelect.addEventListener('change', function(){
        var opt = desgSelect.options[desgSelect.selectedIndex];
        if (opt) {
          var depId = opt.getAttribute('data-department-id');
          if (depId && deptSelect && deptSelect.value !== depId) {
            deptSelect.value = depId;
            var depOpt = deptSelect.options[deptSelect.selectedIndex];
            if (depOpt && deptInput) {
              deptInput.value = depOpt.textContent || '';
            }
          }
          if (desgInput) {
            desgInput.value = opt.textContent || '';
          }
        }
      });
    }
  })();
</script>
<?php $this->load->view('partials/footer'); ?>
