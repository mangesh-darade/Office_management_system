<?php $this->load->view('partials/header', ['title' => 'New Reminder Schedule']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">New Reminder Schedule</h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('reminders/schedules'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('reminders/schedules/create'); ?>" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Schedule Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Audience</label>
          <select name="audience" class="form-select" onchange="toggleUserSelect(this.value)">
            <option value="user" selected>User</option>
            <option value="all">All Users</option>
          </select>
        </div>
        <div class="col-md-3" id="userSelectWrap">
          <label class="form-label">User</label>
          <select name="user_id" class="form-select">
            <option value="">-- Select --</option>
            <?php if (isset($users) && is_array($users)) foreach ($users as $u): ?>
              <?php $label = '';
                if (isset($u->full_label) && $u->full_label!=='') { $label = $u->full_label; }
                else if (isset($u->full_name) && $u->full_name!=='') { $label = $u->full_name; }
                else if (isset($u->name) && $u->name!=='') { $label = $u->name; }
                else if (isset($u->email)) { $label = $u->email; }
              ?>
              <option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($label); ?> (<?php echo htmlspecialchars(isset($u->email)?$u->email:''); ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Weekdays</label>
          <div class="d-flex flex-wrap gap-2">
            <?php 
              $wd = array(
                0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'
              );
              foreach ($wd as $k=>$v): ?>
              <label class="form-check form-check-inline">
                <input type="checkbox" class="form-check-input" name="w[]" value="<?php echo (int)$k; ?>">
                <span class="form-check-label"><?php echo $v; ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="weekdays" id="weekdaysField">
        </div>
        <div class="col-md-3">
          <label class="form-label">Send Time (HH:MM 24h)</label>
          <input type="text" name="send_time" class="form-control" placeholder="09:30" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" required>
        </div>
        <div class="col-md-12">
          <label class="form-label">Message</label>
          <textarea name="body" rows="4" class="form-control" placeholder="Optional message body. If empty, subject will be used."></textarea>
        </div>
      </div>
      <div>
        <button class="btn btn-primary" onclick="return packWeekdays()">Create Schedule</button>
        <a class="btn btn-light" href="<?php echo site_url('reminders/schedules'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<script>
function toggleUserSelect(val){
  var wrap = document.getElementById('userSelectWrap');
  if (!wrap) return;
  wrap.style.display = (val === 'all') ? 'none' : 'block';
}
function packWeekdays(){
  var boxes = document.getElementsByName('w[]');
  var sel = [];
  for (var i=0;i<boxes.length;i++){ if (boxes[i].checked){ sel.push(boxes[i].value); } }
  document.getElementById('weekdaysField').value = sel.join(',');
  return true;
}
</script>
<?php $this->load->view('partials/footer'); ?>
