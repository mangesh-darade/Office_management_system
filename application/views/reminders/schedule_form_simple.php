<?php $this->load->view('partials/header', ['title' => 'Reminder Schedule']); ?>
<style>
.schedule-form .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.schedule-form .form-label { font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
.schedule-form .form-control, .schedule-form .form-select { border-radius: 8px; border: 1px solid #d1d5db; }
.schedule-form .form-control:focus, .schedule-form .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.schedule-form .btn { border-radius: 8px; font-weight: 500; }
.schedule-form .weekday-selector { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.schedule-form .weekday-btn { 
  width: 40px; 
  height: 40px; 
  border-radius: 8px; 
  border: 2px solid #e5e7eb; 
  background: white; 
  cursor: pointer; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  font-weight: 600; 
  transition: all 0.2s;
}
.schedule-form .weekday-btn:hover { border-color: #3b82f6; }
.schedule-form .weekday-btn.selected { background: #3b82f6; color: white; border-color: #3b82f6; }
.schedule-form .preview-section { background: #f8fafc; border-radius: 8px; padding: 1rem; border: 1px solid #e2e8f0; }
</style>

<div class="schedule-form">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo isset($schedule) ? '✏️ Edit Schedule' : '📅 Create Schedule'; ?></h1>
  <a class="btn btn-secondary btn-sm" href="<?php echo site_url('reminders/schedules'); ?>">
    <i class="bi bi-arrow-left me-1"></i>Back to Schedules
  </a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post" action="<?php echo isset($form_action) ? $form_action : site_url('reminders/schedules/create'); ?>">
      
      <!-- Basic Information -->
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label">Schedule Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars(isset($schedule->name) ? $schedule->name : ''); ?>" required placeholder="e.g., Daily Morning Standup">
        </div>
        <div class="col-md-6">
          <label class="form-label">Audience <span class="text-danger">*</span></label>
          <select class="form-select" name="audience" id="audience" required onchange="toggleUserSelection()">
            <option value="">Select Audience</option>
            <option value="all" <?php echo (isset($schedule->audience) && $schedule->audience === 'all') ? 'selected' : ''; ?>>👥 All Users</option>
            <option value="user" <?php echo (isset($schedule->audience) && $schedule->audience === 'user') ? 'selected' : ''; ?>>👤 Specific User</option>
          </select>
        </div>
        
        <div class="col-md-12" id="userSelection" style="<?php echo (isset($schedule->audience) && $schedule->audience === 'user') ? '' : 'display: none;'; ?>">
          <label class="form-label">Select User <span class="text-danger">*</span></label>
          <select class="form-select" name="user_id" id="user_id">
            <option value="">-- Select User --</option>
            <?php if (isset($users) && is_array($users)) foreach ($users as $u): ?>
              <?php 
              $label = '';
              if (isset($u->full_label) && $u->full_label!=='') { $label = $u->full_label; }
              else if (isset($u->full_name) && $u->full_name!=='') { $label = $u->full_name; }
              else if (isset($u->name) && $u->name!=='') { $label = $u->name; }
              else if (isset($u->email)) { $label = $u->email; }
              ?>
              <option value="<?php echo (int)$u->id; ?>" <?php echo (isset($schedule->user_id) && $schedule->user_id == $u->id) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($label); ?> (<?php echo htmlspecialchars(isset($u->email)?$u->email:''); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Schedule Type -->
      <div class="row g-3 mb-4">
        <div class="col-md-12">
          <label class="form-label">Schedule Type <span class="text-danger">*</span></label>
          <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="schedule_type" id="weekly" value="weekly" <?php echo (!isset($schedule->schedule_type) || $schedule->schedule_type === 'weekly') ? 'checked' : ''; ?> onchange="toggleScheduleType()">
            <label class="btn btn-outline-primary" for="weekly">
              <i class="bi bi-calendar-week me-2"></i>Weekly
            </label>
            
            <input type="radio" class="btn-check" name="schedule_type" id="once" value="once" <?php echo (isset($schedule->schedule_type) && $schedule->schedule_type === 'once') ? 'checked' : ''; ?> onchange="toggleScheduleType()">
            <label class="btn btn-outline-primary" for="once">
              <i class="bi bi-calendar-event me-2"></i>One-Time
            </label>
          </div>
        </div>
      </div>

      <!-- Weekly Schedule Options -->
      <div id="weeklyOptions" class="mb-4" style="<?php echo (!isset($schedule->schedule_type) || $schedule->schedule_type === 'weekly') ? '' : 'display: none;'; ?>">
        <div class="row g-3">
          <div class="col-md-12">
            <label class="form-label">Select Days <span class="text-danger">*</span></label>
            <div class="weekday-selector">
              <div class="weekday-btn <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '1') !== false) ? 'selected' : ''; ?>" onclick="toggleWeekday(this, '1')">Mon</div>
              <div class="weekday-btn <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '2') !== false) ? 'selected' : ''; ?>" onclick="toggleWeekday(this, '2')">Tue</div>
              <div class="weekday-btn <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '3') !== false) ? 'selected' : ''; ?>" onclick="toggleWeekday(this, '3')">Wed</div>
              <div class="weekday-btn <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '4') !== false) ? 'selected' : ''; ?>" onclick="toggleWeekday(this, '4')">Thu</div>
              <div class="weekday-btn <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '5') !== false) ? 'selected' : ''; ?>" onclick="toggleWeekday(this, '5')">Fri</div>
              <div class="weekday-btn <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '6') !== false) ? 'selected' : ''; ?>" onclick="toggleWeekday(this, '6')">Sat</div>
              <div class="weekday-btn <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '0') !== false) ? 'selected' : ''; ?>" onclick="toggleWeekday(this, '0')">Sun</div>
              <input type="hidden" name="weekdays" id="weekdays" value="<?php echo htmlspecialchars(isset($schedule->weekdays) ? $schedule->weekdays : ''); ?>">
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Send Time <span class="text-danger">*</span></label>
            <input type="time" class="form-control" name="send_time" value="<?php echo htmlspecialchars(isset($schedule->send_time) ? $schedule->send_time : '09:00'); ?>" required>
          </div>
        </div>
      </div>

      <!-- One-Time Schedule Options -->
      <div id="onceOptions" class="mb-4" style="<?php echo (isset($schedule->schedule_type) && $schedule->schedule_type === 'once') ? '' : 'display: none;'; ?>">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Date & Time <span class="text-danger">*</span></label>
            <input type="datetime-local" class="form-control" name="one_time_at" value="<?php echo htmlspecialchars(isset($schedule->one_time_at) ? str_replace(' ', 'T', substr($schedule->one_time_at, 0, 16)) : ''); ?>" required>
          </div>
        </div>
      </div>

      <!-- Message Content -->
      <div class="row g-3 mb-4">
        <div class="col-md-12">
          <label class="form-label">Subject <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="subject" value="<?php echo htmlspecialchars(isset($schedule->subject) ? $schedule->subject : ''); ?>" required placeholder="e.g., Daily Team Reminder">
        </div>
        <div class="col-md-12">
          <label class="form-label">Message</label>
          <textarea class="form-control" name="body" rows="4" placeholder="Hello {name},&#10;&#10;This is your scheduled reminder..."><?php echo htmlspecialchars(isset($schedule->body) ? $schedule->body : ''); ?></textarea>
          <small class="text-muted">Available variables: {name}, {date}, {time}</small>
        </div>
      </div>

      <!-- Actions -->
      <div class="row g-3">
        <div class="col-md-12">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-2"></i><?php echo isset($schedule) ? 'Update Schedule' : 'Create Schedule'; ?>
          </button>
          <a class="btn btn-outline-secondary" href="<?php echo site_url('reminders/schedules'); ?>">
            <i class="bi bi-x-circle me-2"></i>Cancel
          </a>
        </div>
      </div>
    </form>
  </div>
</div>
</div>

<script>
function toggleUserSelection() {
    const audience = document.getElementById('audience');
    const userSelection = document.getElementById('userSelection');
    
    if (audience.value === 'user') {
        userSelection.style.display = 'block';
    } else {
        userSelection.style.display = 'none';
        document.getElementById('user_id').value = '';
    }
}

function toggleScheduleType() {
    const weeklyRadio = document.getElementById('weekly');
    const weeklyOptions = document.getElementById('weeklyOptions');
    const onceOptions = document.getElementById('onceOptions');
    
    if (weeklyRadio.checked) {
        weeklyOptions.style.display = 'block';
        onceOptions.style.display = 'none';
    } else {
        weeklyOptions.style.display = 'none';
        onceOptions.style.display = 'block';
    }
}

function toggleWeekday(element, day) {
    element.classList.toggle('selected');
    updateWeekdaysInput();
}

function updateWeekdaysInput() {
    const selectedBtns = document.querySelectorAll('.weekday-btn.selected');
    const selectedDays = Array.from(selectedBtns).map(btn => btn.getAttribute('onclick').match(/'(\d)'/)[1]);
    document.getElementById('weekdays').value = selectedDays.join(',');
}

// Initialize form
document.addEventListener('DOMContentLoaded', function() {
    toggleUserSelection();
    toggleScheduleType();
});
</script>

<?php $this->load->view('partials/footer'); ?>
