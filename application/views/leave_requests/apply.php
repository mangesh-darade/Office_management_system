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
    <form method="post" action="<?php echo site_url('leave/apply'); ?>">
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Leave Type</label>
          <select class="form-select" name="type_id" id="type_id" required>
            <option value="">Select</option>
            <?php foreach ($types as $t): $tid=(int)$t->id; ?>
              <option value="<?php echo $tid; ?>" data-balance="<?php echo isset($balances[$tid]) ? (float)$balances[$tid] : 0; ?>">
                <?php echo htmlspecialchars($t->name); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Available: <span id="balance">0</span> days</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">Start Date</label>
          <input type="date" class="form-control" name="start_date" id="start_date" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">End Date</label>
          <input type="date" class="form-control" name="end_date" id="end_date" required />
        </div>
        <div class="col-md-3">
          <label class="form-label">Duration</label>
          <select class="form-select" name="duration_type" id="duration_type">
            <option value="full">Full Day</option>
            <option value="half">Half Day</option>
          </select>
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
  function setBalance(){
    var sel = document.getElementById('type_id');
    var opt = sel.options[sel.selectedIndex];
    var bal = opt ? (opt.getAttribute('data-balance') || '0') : '0';
    document.getElementById('balance').innerText = bal;
  }
  function isWeekend(d){ var day = d.getDay(); return day === 0 || day === 6; }
  function parseDate(id){ var v=document.getElementById(id).value; return v? new Date(v+'T00:00:00') : null; }
  function calcDays(){
    var s = parseDate('start_date'); var e = parseDate('end_date');
    var total = 0;
    if (s && e && e >= s){
      var diffMs = e.getTime() - s.getTime();
      var oneDayMs = 1000 * 60 * 60 * 24;
      total = Math.floor(diffMs / oneDayMs) + 1; // inclusive: 22 to 27 => 6 days
    }
    var duration = document.getElementById('duration_type');
    if (duration && s && e && e >= s && duration.value === 'half'){
      if (s.getTime() === e.getTime() && total > 0){
        total = 0.5;
      }
    }
    document.getElementById('total_days').innerText = total;
  }
  document.getElementById('type_id').addEventListener('change', setBalance);
  document.getElementById('start_date').addEventListener('change', calcDays);
  document.getElementById('end_date').addEventListener('change', calcDays);
  var durEl = document.getElementById('duration_type');
  if (durEl){ durEl.addEventListener('change', calcDays); }
  setBalance();
  calcDays();
})();
</script>

<?php $this->load->view('partials/footer'); ?>
