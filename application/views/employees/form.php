<?php $this->load->view('partials/header', ['title' => (($action === 'edit') ? 'Edit' : 'Create').' Employee']); ?>
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0"><?php echo ($action === 'edit') ? 'Edit' : 'Create'; ?> Employee</h1>
    <a class="btn btn-secondary" href="<?php echo site_url('employees'); ?>">Back</a>
  </div>
  <div class="card shadow-soft">
    <div class="card-body">
  
  
  <form method="post" data-validate="true">
    <div class="row g-3">
      <?php if ($action === 'create'): ?>
      <div class="col-12 col-md-6">
        <label class="form-label" for="user_id">User <span class="text-danger">*</span></label>
        <select name="user_id" id="user_id" class="form-select" data-mandatory="true" required>
          <option value="">-- Select user --</option>
          <?php if (!empty($users)) : foreach ($users as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>"><?php echo esc_view($u['label']); ?> (ID: <?php echo (int)$u['id']; ?>)</option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="col-12 col-md-6">
        <label class="form-label" for="emp_code">Employee Code</label>
        <?php if ($action === 'create'): ?>
        <input type="text" name="emp_code" id="emp_code" class="form-control" value="<?php echo esc_view(isset($generated_emp_code) ? $generated_emp_code : ''); ?>">
        <small class="form-text text-muted">Employee Code is auto-generated. You can edit it if needed.</small>
        <?php else: ?>
        <input type="text" name="emp_code" id="emp_code" class="form-control" value="<?php echo esc_view(isset($employee->emp_code) ? $employee->emp_code : ''); ?>">
        <?php endif; ?>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="first_name">First Name <span class="text-danger">*</span></label>
        <input type="text" name="first_name" id="first_name" class="form-control" data-mandatory="true" data-min-length="2" data-max-length="100" required value="<?php echo esc_view(isset($employee->first_name) ? $employee->first_name : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="last_name">Last Name <span class="text-danger">*</span></label>
        <input type="text" name="last_name" id="last_name" class="form-control" data-mandatory="true" data-min-length="2" data-max-length="100" required value="<?php echo esc_view(isset($employee->last_name) ? $employee->last_name : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="dob">Date of Birth <span class="text-danger">*</span></label>
        <input type="date" name="dob" id="dob" class="form-control" data-mandatory="true" required value="<?php echo esc_view(isset($employee->dob) ? $employee->dob : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="personal_email">Personal Email</label>
        <input type="email" name="personal_email" id="personal_email" class="form-control" value="<?php echo esc_view(isset($employee->personal_email) ? $employee->personal_email : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="department_id">Department <span class="text-danger">*</span></label>
        <select name="department_id" id="department_id" class="form-select" data-mandatory="true" required>
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
            <option value="<?php echo (int)$d->id; ?>" <?php echo $sel; ?>><?php echo esc_view($d->dept_name); ?></option>
          <?php endforeach; endif; ?>
        </select>
        <input type="hidden" name="department" value="<?php echo esc_view(isset($employee->department) ? $employee->department : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="designation_id">Designation <span class="text-danger">*</span></label>
        <select name="designation_id" id="designation_id" class="form-select" data-mandatory="true" required>
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
              <?php echo esc_view($dg->designation_name); ?>
            </option>
          <?php endforeach; endif; ?>
        </select>
        <input type="hidden" name="designation" value="<?php echo esc_view(isset($employee->designation) ? $employee->designation : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="reporting_to">Reporting To <span class="text-danger">*</span></label>
        <select name="reporting_to" id="reporting_to" class="form-select" data-mandatory="true" required>
          <option value="">-- None --</option>
          <?php $rt = isset($employee->reporting_to) ? (int)$employee->reporting_to : 0; ?>
          <?php if (!empty($users)) : foreach ($users as $u): ?>
            <option value="<?php echo (int)$u['id']; ?>" <?php echo ($rt === (int)$u['id']) ? 'selected' : ''; ?>><?php echo esc_view($u['label']); ?> (ID: <?php echo (int)$u['id']; ?>)</option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="employment_type">Employment Type <span class="text-danger">*</span></label>
        <?php $et = (isset($employee) && isset($employee->employment_type)) ? (string) $employee->employment_type : 'full_time'; ?>
        <?php $this->load->view('partials/module_type_select', array(
          'field_name' => 'employment_type',
          'options' => isset($employment_types) ? $employment_types : array(),
          'current' => $et,
          'required' => true,
          'select_class' => 'form-select',
          'placeholder' => '— Select type —',
        )); ?>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="shift_id">Shift <span class="text-danger">*</span></label>
        <select name="shift_id" id="shift_id" class="form-select" data-mandatory="true" required>
          <?php 
            $currentShift = isset($employee->shift_id) ? (int)$employee->shift_id : 1;
            if (empty($shifts)) { echo '<option value="">No shifts available</option>'; } 
            else {
                foreach ($shifts as $s) {
                    $sel = ($currentShift === (int)$s->id) ? 'selected' : '';
                    echo '<option value="'.(int)$s->id.'" '.$sel.'>'.esc_view($s->name) . ' (' . date('h:i A', strtotime($s->start_time)) . ' - ' . date('h:i A', strtotime($s->end_time)) . ')</option>';
                }
            } 
          ?>
        </select>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="join_date">Join Date <span class="text-danger">*</span></label>
        <input type="date" name="join_date" id="join_date" class="form-control" data-mandatory="true" required value="<?php echo esc_view(isset($employee->join_date) ? $employee->join_date : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="location">Location</label>
        <input type="text" name="location" id="location" class="form-control" value="<?php echo esc_view(isset($employee->location) ? $employee->location : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="phone">Phone</label>
        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo esc_view(isset($employee->phone) ? $employee->phone : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="address">Address</label>
        <textarea name="address" id="address" class="form-control" rows="2"><?php echo esc_view(isset($employee->address) ? $employee->address : ''); ?></textarea>
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="city">City</label>
        <input type="text" name="city" id="city" class="form-control" value="<?php echo esc_view(isset($employee->city) ? $employee->city : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="state">State</label>
        <input type="text" name="state" id="state" class="form-control" value="<?php echo esc_view(isset($employee->state) ? $employee->state : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="country">Country</label>
        <input type="text" name="country" id="country" class="form-control" value="<?php echo esc_view(isset($employee->country) ? $employee->country : 'India'); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="zipcode">Pincode</label>
        <input type="text" name="zipcode" id="zipcode" class="form-control" value="<?php echo esc_view(isset($employee->zipcode) ? $employee->zipcode : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="bank_name">Bank Name</label>
        <input type="text" name="bank_name" id="bank_name" class="form-control" value="<?php echo esc_view(isset($employee->bank_name) ? $employee->bank_name : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="bank_ac_no">Bank A/C No</label>
        <input type="text" name="bank_ac_no" id="bank_ac_no" class="form-control" value="<?php echo esc_view(isset($employee->bank_ac_no) ? $employee->bank_ac_no : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="pan_no">PAN No</label>
        <input type="text" name="pan_no" id="pan_no" class="form-control" value="<?php echo esc_view(isset($employee->pan_no) ? $employee->pan_no : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label" for="salary_ctc">Salary (CTC)</label>
        <input type="number" step="0.01" name="salary_ctc" id="salary_ctc" class="form-control" value="<?php echo esc_view(isset($employee->salary_ctc) ? $employee->salary_ctc : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Emergency Contact Name</label>
        <input type="text" name="emergency_contact_name" class="form-control" value="<?php echo esc_view(isset($employee->emergency_contact_name) ? $employee->emergency_contact_name : ''); ?>">
      </div>
      <div class="col-12 col-md-6">
        <label class="form-label">Emergency Contact Phone</label>
        <input type="text" name="emergency_contact_phone" class="form-control" value="<?php echo esc_view(isset($employee->emergency_contact_phone) ? $employee->emergency_contact_phone : ''); ?>">
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
