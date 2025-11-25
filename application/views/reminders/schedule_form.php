<?php
$is_edit = isset($schedule);
$oneTimeVal = '';
if ($is_edit && isset($schedule->one_time_at) && $schedule->one_time_at){
  $ts = strtotime($schedule->one_time_at);
  if ($ts) { $oneTimeVal = date('Y-m-d\TH:i', $ts); }
}
$this->load->view('partials/header', ['title' => $is_edit ? 'Edit Reminder Schedule' : 'New Reminder Schedule']);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0"><?php echo $is_edit ? 'Edit Reminder Schedule' : 'New Reminder Schedule'; ?></h1>
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
    <form method="post" action="<?php echo isset($form_action) ? $form_action : site_url('reminders/schedules/create'); ?>" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Schedule Name</label>
          <input type="text" name="name" class="form-control" required value="<?php echo isset($schedule->name)?htmlspecialchars($schedule->name):''; ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Audience</label>
          <select name="audience" class="form-select" onchange="toggleUserSelect(this.value)">
            <option value="user"<?php echo (!$is_edit || (isset($schedule->audience) && $schedule->audience==='user')) ? ' selected' : ''; ?>>User</option>
            <option value="all"<?php echo ($is_edit && isset($schedule->audience) && $schedule->audience==='all') ? ' selected' : ''; ?>>All Users</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Schedule Type</label>
          <select name="schedule_type" class="form-select" onchange="toggleScheduleType(this.value)">
            <?php $curType = ($is_edit && isset($schedule->schedule_type)) ? $schedule->schedule_type : 'weekly'; ?>
            <option value="weekly"<?php echo ($curType !== 'once' ? ' selected' : ''); ?>>Weekly</option>
            <option value="once"<?php echo ($curType === 'once' ? ' selected' : ''); ?>>One Time</option>
          </select>
        </div>
        <div class="col-md-3" id="userSelectWrap"<?php if ($is_edit && isset($schedule->audience) && $schedule->audience==='all') echo ' style="display:none"'; ?>>
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
              <option value="<?php echo (int)$u->id; ?>"<?php echo ($is_edit && isset($schedule->user_id) && (int)$schedule->user_id === (int)$u->id) ? ' selected' : ''; ?>><?php echo htmlspecialchars($label); ?> (<?php echo htmlspecialchars(isset($u->email)?$u->email:''); ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6" id="weekdaysWrap">
          <label class="form-label">Weekdays</label>
          <div class="d-flex flex-wrap gap-2">
            <?php 
              $wd = array(
                0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'
              );
              $selectedDays = array();
              if ($is_edit && isset($schedule->weekdays) && $schedule->weekdays!==''){
                $selectedDays = explode(',', $schedule->weekdays);
              }
              foreach ($wd as $k=>$v): ?>
              <label class="form-check form-check-inline">
                <input type="checkbox" class="form-check-input" name="w[]" value="<?php echo (int)$k; ?>"<?php echo (in_array((string)$k, $selectedDays, true) ? ' checked' : ''); ?>>
                <span class="form-check-label"><?php echo $v; ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="weekdays" id="weekdaysField" value="<?php echo ($is_edit && isset($schedule->weekdays)) ? htmlspecialchars($schedule->weekdays) : ''; ?>">
        </div>
        <div class="col-md-3" id="sendTimeWrap">
          <label class="form-label">Send Time</label>
          <input type="time" name="send_time" class="form-control" required value="<?php echo ($is_edit && isset($schedule->send_time)) ? htmlspecialchars($schedule->send_time) : ''; ?>">
        </div>
        <div class="col-md-3" id="oneTimeWrap"<?php
          $curType = ($is_edit && isset($schedule->schedule_type)) ? $schedule->schedule_type : 'weekly';
          if ($curType !== 'once') { echo ' style="display:none"'; }
        ?>>
          <label class="form-label">Send At (Date &amp; Time)</label>
          <input type="datetime-local" name="one_time_at" class="form-control" value="<?php echo htmlspecialchars($oneTimeVal); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" required value="<?php echo ($is_edit && isset($schedule->subject)) ? htmlspecialchars($schedule->subject) : ''; ?>">
        </div>
        <div class="col-md-12">
          <label class="form-label">Message</label>
          <textarea name="body" rows="4" class="form-control" placeholder="Optional message body. If empty, subject will be used."><?php echo ($is_edit && isset($schedule->body)) ? htmlspecialchars($schedule->body) : ''; ?></textarea>
        </div>
      </div>
      <div>
        <button class="btn btn-primary" onclick="return packWeekdays()"><?php echo $is_edit ? 'Update Schedule' : 'Create Schedule'; ?></button>
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
function toggleScheduleType(val){
  var wd = document.getElementById('weekdaysWrap');
  var st = document.getElementById('sendTimeWrap');
  var ot = document.getElementById('oneTimeWrap');
  if (val === 'once'){
    if (wd) wd.style.display = 'none';
    if (st) st.style.display = 'none';
    if (ot) ot.style.display = 'block';
  } else {
    if (wd) wd.style.display = 'block';
    if (st) st.style.display = 'block';
    if (ot) ot.style.display = 'none';
  }
}
document.addEventListener('DOMContentLoaded', function(){
  var typeSel = document.querySelector('select[name="schedule_type"]');
  if (typeSel){ toggleScheduleType(typeSel.value); }
});
</script>
<?php $this->load->view('partials/footer'); ?>
