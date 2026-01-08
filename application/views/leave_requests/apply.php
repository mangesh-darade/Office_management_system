<?php $this->load->view('partials/header', ['title' => 'Apply Leave']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Apply Leave</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('leave/my'); ?>">My Leaves</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('leave/apply'); ?>" data-validate="true">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Leave Type</label>
          <select class="form-select" name="type_id" id="type_id" required>
            <option value="">Select</option>
            <?php foreach ($types as $t): $tid=(int)$t->id; ?>
              <option value="<?php echo $tid; ?>" data-balance="<?php echo isset($balances[$tid]) ? (float)$balances[$tid] : 0; ?>" data-name="<?php echo htmlspecialchars(strtolower($t->name)); ?>">
                <?php echo htmlspecialchars($t->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Available: <span id="balance">0</span> days</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Leave Mode</label>
          <div class="btn-group w-100" role="group" aria-label="Leave mode">
            <input type="radio" class="btn-check" name="mode" id="mode_range" value="range" autocomplete="off" checked>
            <label class="btn btn-outline-primary btn-sm flex-fill" for="mode_range">Date range</label>
            <input type="radio" class="btn-check" name="mode" id="mode_specific" value="specific" autocomplete="off">
            <label class="btn btn-outline-primary btn-sm flex-fill" for="mode_specific">Specific dates</label>
          </div>
        </div>
        <div class="col-md-3 range-only">
          <label class="form-label">Start Date</label>
          <input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo isset($today_date) ? htmlspecialchars($today_date) : date('Y-m-d'); ?>" required />
        </div>
        <div class="col-md-3 range-only">
          <label class="form-label">End Date</label>
          <input type="date" class="form-control" name="end_date" id="end_date" value="<?php echo isset($today_date) ? htmlspecialchars($today_date) : date('Y-m-d'); ?>" required />
        </div>
        <div class="col-md-3 range-only">
          <label class="form-label">Duration</label>
          <select class="form-select" name="duration_type" id="duration_type">
            <option value="full">Full Day</option>
            <option value="half">Half Day</option>
          </select>
        </div>
        <div class="col-md-6 specific-only d-none">
          <label class="form-label">Specific Dates</label>
          <div id="specificDatesWrapper" class="d-grid gap-2">
            <div class="input-group specific-date-row">
              <input type="date" name="dates[]" class="form-control specific-date-input" />
              <button type="button" class="btn btn-outline-danger btn-sm btn-remove-date" title="Remove date">
                <i class="bi bi-x"></i>
              </button>
            </div>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="btnAddDate">
            <i class="bi bi-plus-lg me-1"></i>Add Date
          </button>
          <div class="form-text">Use this mode for separate days like 3, 8, 15 of the month.</div>
        </div>
        <div class="col-md-12">
          <label class="form-label">Reason</label>
          <textarea class="form-control" name="reason" rows="3" placeholder="Reason"></textarea>
        </div>
        <div class="col-md-12">
          <div class="text-muted small">Total Working Days</div>
          <div class="fw-semibold" id="total_days">0</div>
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary">Submit Request</button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  // Get holidays and weekend days from PHP
  var holidays = <?php echo json_encode(isset($holidays) ? $holidays : []); ?>;
  var weekendDays = <?php echo json_encode(isset($weekend_days) ? $weekend_days : [0, 6]); ?>;
  
  // Get today's date from server (Asia/Kolkata timezone)
  var todayStr = <?php echo json_encode(isset($today_date) ? $today_date : date('Y-m-d')); ?>;
  var today = new Date(todayStr + 'T00:00:00');
  today.setHours(0, 0, 0, 0);

  function setBalance(){
    var sel = document.getElementById('type_id');
    var opt = sel.options[sel.selectedIndex];
    var bal = opt ? (opt.getAttribute('data-balance') || '0') : '0';
    document.getElementById('balance').innerText = bal;
  }
  
  function isWeekend(d){ 
    var day = d.getDay();
    return weekendDays.indexOf(day) !== -1;
  }
  
  function isHoliday(dateStr) {
    return holidays.indexOf(dateStr) !== -1;
  }
  
  function isPastDate(dateStr) {
    return dateStr < todayStr;
  }
  
  function isDisabledDate(dateStr) {
    if (isPastDate(dateStr)) return true;
    var d = new Date(dateStr + 'T00:00:00');
    if (isWeekend(d)) return true;
    if (isHoliday(dateStr)) return true;
    return false;
  }
  
  function parseDate(id){ var el=document.getElementById(id); var v=el?el.value:''; return v? new Date(v+'T00:00:00') : null; }
  
  function setupDateInput(input) {
    if (!input) return;
    
    // Set min date to today
    input.setAttribute('min', todayStr);
    
    // Add change event to validate
    input.addEventListener('change', function() {
      var selectedDate = this.value;
      if (selectedDate && isDisabledDate(selectedDate)) {
        alert('Selected date is either a past date, weekend, or holiday. Please select a valid working day.');
        this.value = '';
        calcDays();
      } else {
        calcDays();
      }
    });
  }

  function calcDays(){
    var total = 0;
    var modeSpecific = document.getElementById('mode_specific') && document.getElementById('mode_specific').checked;

    if (!modeSpecific) {
      var s = parseDate('start_date'); var e = parseDate('end_date');
      if (s && e && e >= s){
        var diffMs = e.getTime() - s.getTime();
        var oneDayMs = 1000 * 60 * 60 * 24;
        total = Math.floor(diffMs / oneDayMs) + 1; // inclusive
      }
      var duration = document.getElementById('duration_type');
      if (duration && s && e && e >= s && duration.value === 'half'){
        if (s.getTime() === e.getTime() && total > 0){
          total = 0.5;
        }
      }
    } else {
      var seen = {};
      var inputs = document.querySelectorAll('.specific-date-input');
      inputs.forEach(function(inp){
        var v = (inp.value || '').trim();
        if (!v || seen[v]) return;
        var d = new Date(v+'T00:00:00');
        if (!d || isNaN(d.getTime()) || isWeekend(d)) return;
        seen[v] = true;
        total += 1;
      });
    }
    var totalEl = document.getElementById('total_days');
    if (totalEl) totalEl.innerText = total;
  }

  function updateMode(){
    var specific = document.getElementById('mode_specific') && document.getElementById('mode_specific').checked;
    var rangeEls = document.querySelectorAll('.range-only');
    var specEls = document.querySelectorAll('.specific-only');
    rangeEls.forEach(function(el){ el.classList.toggle('d-none', specific); });
    specEls.forEach(function(el){ el.classList.toggle('d-none', !specific); });

    var s = document.getElementById('start_date');
    var e = document.getElementById('end_date');
    if (s) s.required = !specific;
    if (e) e.required = !specific;
    document.querySelectorAll('.specific-date-input').forEach(function(inp){ inp.required = specific; });

    calcDays();
  }

  function addSpecificDateRow(){
    var wrap = document.getElementById('specificDatesWrapper');
    if (!wrap) return;
    var row = document.createElement('div');
    row.className = 'input-group specific-date-row';
    var input = document.createElement('input');
    input.type = 'date';
    input.name = 'dates[]';
    input.className = 'form-control specific-date-input';
    setupDateInput(input);
    input.addEventListener('change', function() {
      var selectedDate = this.value;
      if (selectedDate && isDisabledDate(selectedDate)) {
        alert('Selected date is either a past date, weekend, or holiday. Please select a valid working day.');
        this.value = '';
      }
      calcDays();
    });
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-danger btn-sm btn-remove-date';
    btn.innerHTML = '<i class="bi bi-x"></i>';
    btn.addEventListener('click', function(){ row.remove(); calcDays(); });
    row.appendChild(input);
    row.appendChild(btn);
    wrap.appendChild(row);
  }
  // Check if selected leave type is "Work From Home" and update balance display
  var typeIdSelect = document.getElementById('type_id');
  
  function checkWFHType() {
    if (typeIdSelect && typeIdSelect.selectedIndex > 0) {
      var selectedOption = typeIdSelect.options[typeIdSelect.selectedIndex];
      var typeName = selectedOption.getAttribute('data-name') || '';
      var isWFH = typeName.indexOf('work from home') !== -1;
      
      if (isWFH) {
        // WFH type selected - show N/A for balance
        document.getElementById('balance').innerText = 'N/A (WFH - No balance deduction)';
      } else {
        // Regular leave type - show balance
        setBalance();
      }
    } else {
      setBalance();
    }
  }
  
  if (typeIdSelect) {
    typeIdSelect.addEventListener('change', function() {
      setBalance();
      checkWFHType();
    });
  }
  
  // Initial check
  checkWFHType();
  var sEl = document.getElementById('start_date');
  var eEl = document.getElementById('end_date');
  setupDateInput(sEl);
  setupDateInput(eEl);
  if (sEl) sEl.addEventListener('change', function() {
    var selectedDate = this.value;
    if (selectedDate && isDisabledDate(selectedDate)) {
      alert('Start date cannot be a past date, weekend, or holiday. Please select a valid working day.');
      this.value = '';
      return;
    }
    calcDays();
  });
  if (eEl) eEl.addEventListener('change', function() {
    var selectedDate = this.value;
    if (selectedDate && isDisabledDate(selectedDate)) {
      alert('End date cannot be a past date, weekend, or holiday. Please select a valid working day.');
      this.value = '';
      return;
    }
    calcDays();
  });
  var durEl = document.getElementById('duration_type');
  if (durEl){ durEl.addEventListener('change', calcDays); }

  var modeRange = document.getElementById('mode_range');
  var modeSpecific = document.getElementById('mode_specific');
  if (modeRange) modeRange.addEventListener('change', updateMode);
  if (modeSpecific) modeSpecific.addEventListener('change', updateMode);

  // Wire existing specific-date row
  document.querySelectorAll('.specific-date-input').forEach(function(inp){
    setupDateInput(inp);
    inp.addEventListener('change', function() {
      var selectedDate = this.value;
      if (selectedDate && isDisabledDate(selectedDate)) {
        alert('Selected date is either a past date, weekend, or holiday. Please select a valid working day.');
        this.value = '';
        return;
      }
      calcDays();
    });
  });
  document.querySelectorAll('.btn-remove-date').forEach(function(btn){
    btn.addEventListener('click', function(){
      var row = this.closest('.specific-date-row');
      if (row) row.remove();
      calcDays();
    });
  });
  var addBtn = document.getElementById('btnAddDate');
  if (addBtn) addBtn.addEventListener('click', function(){ addSpecificDateRow(); });

  // Function to get next valid working day
  function getNextValidDate(startDate) {
    var date = new Date(startDate);
    date.setHours(0, 0, 0, 0);
    var maxAttempts = 30; // Prevent infinite loop
    var attempts = 0;
    
    while (attempts < maxAttempts) {
      var dateStr = date.toISOString().split('T')[0];
      if (!isDisabledDate(dateStr)) {
        return dateStr;
      }
      date.setDate(date.getDate() + 1);
      attempts++;
    }
    return startDate; // Fallback to original date
  }
  
  // Set default dates on page load - ensure they are valid working days
  function setDefaultDates() {
    var startEl = document.getElementById('start_date');
    var endEl = document.getElementById('end_date');
    
    if (startEl) {
      var currentValue = startEl.value || todayStr;
      // If current value is invalid, find next valid date
      if (isDisabledDate(currentValue)) {
        currentValue = getNextValidDate(today);
      }
      startEl.value = currentValue;
    }
    
    if (endEl) {
      var currentValue = endEl.value || todayStr;
      // If current value is invalid, find next valid date
      if (isDisabledDate(currentValue)) {
        currentValue = getNextValidDate(today);
      }
      endEl.value = currentValue;
    }
    
    // Recalculate days after setting defaults
    calcDays();
  }
  
  setBalance();
  updateMode();
  // Set defaults after a short delay to ensure DOM is ready
  setTimeout(setDefaultDates, 100);
  
  // Add form submission validation for duplicate dates
  var leaveForm = document.querySelector('form[method="post"]');
  if (leaveForm) {
    leaveForm.addEventListener('submit', function(e) {
      // Validate Leave Type
      if (!typeIdSelect || !typeIdSelect.value) {
        e.preventDefault();
        alert('Please select a leave type.');
        return false;
      }
      
      var modeSpecific = document.getElementById('mode_specific') && document.getElementById('mode_specific').checked;
      
      if (modeSpecific) {
        // Check for duplicate dates in specific dates mode
        var dateInputs = document.querySelectorAll('.specific-date-input');
        var selectedDates = [];
        var duplicates = [];
        
        dateInputs.forEach(function(input) {
          var value = input.value.trim();
          if (value) {
            if (selectedDates.includes(value)) {
              duplicates.push(value);
            } else {
              selectedDates.push(value);
            }
          }
        });
        
        if (duplicates.length > 0) {
          e.preventDefault();
          alert('You have selected duplicate dates: ' + duplicates.join(', ') + '. Please remove duplicates and try again.');
          return false;
        }
      } else {
        // Check for date validation in range mode
        var startDate = document.getElementById('start_date').value;
        var endDate = document.getElementById('end_date').value;
        
        if (startDate && endDate && startDate === endDate) {
          var duration = document.getElementById('duration_type').value;
          if (duration === 'half') {
            // Half-day is allowed for same date
            return true;
          }
        }
      }
      
      return true;
    });
  }
})();
</script>

<?php $this->load->view('partials/footer'); ?>
