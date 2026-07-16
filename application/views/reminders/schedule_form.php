<?php
$is_edit = isset($schedule);
$oneTimeVal = '';
if ($is_edit && isset($schedule->one_time_at) && $schedule->one_time_at) {
    $ts = strtotime($schedule->one_time_at);
    if ($ts) {
        $oneTimeVal = date('Y-m-d\TH:i', $ts);
    }
}
$curType = ($is_edit && isset($schedule->schedule_type)) ? $schedule->schedule_type : 'weekly';
$this->load->view('partials/header', ['title' => $is_edit ? 'Edit Reminder Schedule' : 'New Reminder Schedule']);
?>
<div class="oms-form-compact">
<div class="oms-form-page-head d-flex justify-content-between align-items-center mb-2">
  <h1 class="h4 mb-0"><?php echo $is_edit ? 'Edit Reminder Schedule' : 'New Reminder Schedule'; ?></h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('reminders/schedules'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft oms-form-card">
  <div class="card-body">
    <form method="post" action="<?php echo isset($form_action) ? esc_view($form_action) : site_url('reminders/schedules/create'); ?>" class="vstack gap-3" id="reminderScheduleForm">
      <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
      <div class="alert alert-info small mb-0">
        Saving this schedule creates <strong>Google Calendar</strong> email reminders for the next occurrence
        (connect Google under Settings → Google Calendar Reminders first).
      </div>
      <div class="row g-2 oms-form-grid">
        <div class="col-md-6">
          <label class="form-label">Schedule Name</label>
          <input type="text" name="name" class="form-control" required value="<?php echo isset($schedule->name) ? esc_view($schedule->name) : ''; ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Audience</label>
          <select name="audience" id="audienceSelect" class="form-select">
            <option value="user"<?php echo (!$is_edit || (isset($schedule->audience) && $schedule->audience === 'user')) ? ' selected' : ''; ?>>User</option>
            <option value="all"<?php echo ($is_edit && isset($schedule->audience) && $schedule->audience === 'all') ? ' selected' : ''; ?>>All Users</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Schedule Type</label>
          <select name="schedule_type" id="scheduleTypeSelect" class="form-select">
            <option value="weekly"<?php echo ($curType !== 'once' ? ' selected' : ''); ?>>Weekly</option>
            <option value="once"<?php echo ($curType === 'once' ? ' selected' : ''); ?>>One Time</option>
          </select>
        </div>
        <div class="col-md-3" id="userSelectWrap"<?php if ($is_edit && isset($schedule->audience) && $schedule->audience === 'all') {
            echo ' style="display:none"';
        } ?>>
          <label class="form-label">User</label>
          <select name="user_id" id="userIdSelect" class="form-select">
            <option value="">-- Select --</option>
            <?php if (isset($users) && is_array($users)) {
                foreach ($users as $u): ?>
              <?php
                $label = '';
                if (isset($u->full_label) && $u->full_label !== '') {
                    $label = $u->full_label;
                } elseif (isset($u->full_name) && $u->full_name !== '') {
                    $label = $u->full_name;
                } elseif (isset($u->name) && $u->name !== '') {
                    $label = $u->name;
                } elseif (isset($u->email)) {
                    $label = $u->email;
                }
              ?>
              <option value="<?php echo (int) $u->id; ?>"<?php echo ($is_edit && isset($schedule->user_id) && (int) $schedule->user_id === (int) $u->id) ? ' selected' : ''; ?>><?php echo esc_view($label); ?></option>
            <?php endforeach;
            } ?>
          </select>
        </div>
        <div class="col-md-6" id="weekdaysWrap"<?php if ($curType === 'once') {
            echo ' style="display:none"';
        } ?>>
          <label class="form-label">Weekdays</label>
          <div class="d-flex flex-wrap gap-2">
            <?php
              $wd = array(0 => 'Sun', 1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat');
              $selectedDays = array();
            if ($is_edit && isset($schedule->weekdays) && $schedule->weekdays !== '') {
                $selectedDays = explode(',', $schedule->weekdays);
            }
            foreach ($wd as $k => $v): ?>
              <label class="form-check form-check-inline">
                <input type="checkbox" class="form-check-input weekday-check" name="w[]" value="<?php echo (int) $k; ?>"<?php echo (in_array((string) $k, $selectedDays, true) ? ' checked' : ''); ?>>
                <span class="form-check-label"><?php echo $v; ?></span>
              </label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="weekdays" id="weekdaysField" value="<?php echo ($is_edit && isset($schedule->weekdays)) ? esc_view($schedule->weekdays) : ''; ?>">
        </div>
        <div class="col-md-3" id="sendTimeWrap"<?php if ($curType === 'once') {
            echo ' style="display:none"';
        } ?>>
          <label class="form-label">Send Time</label>
          <input type="time" name="send_time" id="sendTimeInput" class="form-control" value="<?php echo ($is_edit && isset($schedule->send_time)) ? esc_view($schedule->send_time) : '09:00'; ?>">
        </div>
        <div class="col-md-3" id="oneTimeWrap"<?php if ($curType !== 'once') {
            echo ' style="display:none"';
        } ?>>
          <label class="form-label">Send At (Date &amp; Time)</label>
          <input type="datetime-local" name="one_time_at" id="oneTimeInput" class="form-control" value="<?php echo esc_view($oneTimeVal); ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Subject</label>
          <input type="text" name="subject" class="form-control" required value="<?php echo ($is_edit && isset($schedule->subject)) ? esc_view($schedule->subject) : ''; ?>">
        </div>
        <div class="col-md-12">
          <label class="form-label">Message</label>
          <textarea name="body" rows="4" class="form-control" placeholder="Optional message body. If empty, subject will be used."><?php echo ($is_edit && isset($schedule->body)) ? esc_view($schedule->body) : ''; ?></textarea>
        </div>
      </div>
      <div>
        <button type="submit" class="btn btn-primary" id="scheduleSubmitBtn"><?php echo $is_edit ? 'Update Schedule' : 'Create Schedule'; ?></button>
        <a class="btn btn-light" href="<?php echo site_url('reminders/schedules'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  function packWeekdays() {
    var boxes = document.querySelectorAll('.weekday-check');
    var sel = [];
    for (var i = 0; i < boxes.length; i++) {
      if (boxes[i].checked) {
        sel.push(boxes[i].value);
      }
    }
    var field = document.getElementById('weekdaysField');
    if (field) {
      field.value = sel.join(',');
    }
    return sel;
  }

  function toggleUserSelect(val) {
    var wrap = document.getElementById('userSelectWrap');
    var userSel = document.getElementById('userIdSelect');
    if (!wrap) {
      return;
    }
    var isAll = (val === 'all');
    wrap.style.display = isAll ? 'none' : 'block';
    if (userSel) {
      if (isAll) {
        userSel.removeAttribute('required');
        userSel.value = '';
      } else {
        userSel.setAttribute('required', 'required');
      }
    }
  }

  function toggleScheduleType(val) {
    var wd = document.getElementById('weekdaysWrap');
    var st = document.getElementById('sendTimeWrap');
    var ot = document.getElementById('oneTimeWrap');
    var sendTime = document.getElementById('sendTimeInput');
    var oneTime = document.getElementById('oneTimeInput');
    var isOnce = (val === 'once');

    if (wd) {
      wd.style.display = isOnce ? 'none' : 'block';
    }
    if (st) {
      st.style.display = isOnce ? 'none' : 'block';
    }
    if (ot) {
      ot.style.display = isOnce ? 'block' : 'none';
    }

    // Critical: hidden fields must not keep HTML5 required (blocks Create button)
    if (sendTime) {
      if (isOnce) {
        sendTime.removeAttribute('required');
      } else {
        sendTime.setAttribute('required', 'required');
      }
    }
    if (oneTime) {
      if (isOnce) {
        oneTime.setAttribute('required', 'required');
      } else {
        oneTime.removeAttribute('required');
      }
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var audience = document.getElementById('audienceSelect');
    var typeSel = document.getElementById('scheduleTypeSelect');
    var form = document.getElementById('reminderScheduleForm');

    if (audience) {
      toggleUserSelect(audience.value);
      audience.addEventListener('change', function () {
        toggleUserSelect(audience.value);
      });
    }
    if (typeSel) {
      toggleScheduleType(typeSel.value);
      typeSel.addEventListener('change', function () {
        toggleScheduleType(typeSel.value);
      });
    }
    if (form) {
      form.addEventListener('submit', function (e) {
        packWeekdays();
        var type = typeSel ? typeSel.value : 'weekly';
        if (type !== 'once') {
          var days = document.getElementById('weekdaysField');
          if (!days || !days.value) {
            e.preventDefault();
            alert('Please select at least one weekday.');
            return false;
          }
        }
        return true;
      });
    }
  });
})();
</script>
</div>
<?php $this->load->view('partials/footer'); ?>
