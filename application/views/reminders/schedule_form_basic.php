<?php $this->load->view('partials/header', ['title' => 'Reminder Schedule']); ?>

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
      
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label">Schedule Name <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars(isset($schedule->name) ? $schedule->name : ''); ?>" required placeholder="e.g., Daily Morning Standup">
        </div>
        <div class="col-md-6">
          <label class="form-label">Audience <span class="text-danger">*</span></label>
          <select class="form-select" name="audience" required>
            <option value="">Select Audience</option>
            <option value="all" <?php echo (isset($schedule->audience) && $schedule->audience === 'all') ? 'selected' : ''; ?>>All Users</option>
            <option value="user" <?php echo (isset($schedule->audience) && $schedule->audience === 'user') ? 'selected' : ''; ?>>Specific User</option>
          </select>
        </div>
        
        <div class="col-md-6">
          <label class="form-label">Select User</label>
          <select class="form-select" name="user_id">
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

      <div class="row g-3 mb-4">
        <div class="col-md-12">
          <label class="form-label">Schedule Type <span class="text-danger">*</span></label>
          <div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="schedule_type" id="weekly" value="weekly" <?php echo (!isset($schedule->schedule_type) || $schedule->schedule_type === 'weekly') ? 'checked' : ''; ?>>
              <label class="form-check-label" for="weekly">Weekly</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="schedule_type" id="once" value="once" <?php echo (isset($schedule->schedule_type) && $schedule->schedule_type === 'once') ? 'checked' : ''; ?>>
              <label class="form-check-label" for="once">One-Time</label>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-12">
          <label class="form-label">Select Days (for weekly)</label>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="1" <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '1') !== false) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="day1">Mon</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="2" <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '2') !== false) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="day2">Tue</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="3" <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '3') !== false) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="day3">Wed</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="4" <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '4') !== false) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="day4">Thu</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="5" <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '5') !== false) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="day5">Fri</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="6" <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '6') !== false) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="day6">Sat</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="checkbox" name="weekdays[]" value="0" <?php echo (isset($schedule->weekdays) && strpos($schedule->weekdays, '0') !== false) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="day0">Sun</label>
          </div>
        </div>
        
        <div class="col-md-4">
          <label class="form-label">Send Time <span class="text-danger">*</span></label>
          <input type="time" class="form-control" name="send_time" value="<?php echo htmlspecialchars(isset($schedule->send_time) ? $schedule->send_time : '09:00'); ?>" required>
        </div>
        
        <div class="col-md-6">
          <label class="form-label">One-Time Date & Time</label>
          <input type="datetime-local" class="form-control" name="one_time_at" value="<?php echo htmlspecialchars(isset($schedule->one_time_at) ? str_replace(' ', 'T', substr($schedule->one_time_at, 0, 16)) : ''); ?>">
        </div>
      </div>

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

<?php $this->load->view('partials/footer'); ?>
