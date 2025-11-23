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
          <input type="date" class="form-control" name="start_date" id="start_date" required />
        </div>
        <div class="col-md-3 range-only">
          <label class="form-label">End Date</label>
          <input type="date" class="form-control" name="end_date" id="end_date" required />
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
  function setBalance(){
    var sel = document.getElementById('type_id');
    var opt = sel.options[sel.selectedIndex];
    var bal = opt ? (opt.getAttribute('data-balance') || '0') : '0';
    document.getElementById('balance').innerText = bal;
  }
  function isWeekend(d){ var day = d.getDay(); return day === 0 || day === 6; }
  function parseDate(id){ var el=document.getElementById(id); var v=el?el.value:''; return v? new Date(v+'T00:00:00') : null; }

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
    input.addEventListener('change', calcDays);
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-danger btn-sm btn-remove-date';
    btn.innerHTML = '<i class="bi bi-x"></i>';
    btn.addEventListener('click', function(){ row.remove(); calcDays(); });
    row.appendChild(input);
    row.appendChild(btn);
    wrap.appendChild(row);
  }
  document.getElementById('type_id').addEventListener('change', setBalance);
  var sEl = document.getElementById('start_date');
  var eEl = document.getElementById('end_date');
  if (sEl) sEl.addEventListener('change', calcDays);
  if (eEl) eEl.addEventListener('change', calcDays);
  var durEl = document.getElementById('duration_type');
  if (durEl){ durEl.addEventListener('change', calcDays); }

  var modeRange = document.getElementById('mode_range');
  var modeSpecific = document.getElementById('mode_specific');
  if (modeRange) modeRange.addEventListener('change', updateMode);
  if (modeSpecific) modeSpecific.addEventListener('change', updateMode);

  // Wire existing specific-date row
  document.querySelectorAll('.specific-date-input').forEach(function(inp){
    inp.addEventListener('change', calcDays);
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

  setBalance();
  updateMode();
})();
</script>

<?php $this->load->view('partials/footer'); ?>
