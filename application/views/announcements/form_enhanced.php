<?php $this->load->view('partials/header', ['title' => (($action === 'edit') ? 'Edit' : 'Create').' Announcement']); ?>
<style>
.announcement-form .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.announcement-form .form-label { font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
.announcement-form .form-control, .announcement-form .form-select { border-radius: 8px; border: 1px solid #d1d5db; }
.announcement-form .form-control:focus, .announcement-form .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.announcement-form .btn { border-radius: 8px; font-weight: 500; }
.announcement-form .nav-tabs .nav-link { border-radius: 8px 8px 0 0; font-weight: 500; }
.announcement-form .schedule-section { background: #f8fafc; border-radius: 8px; padding: 1rem; border: 1px solid #e2e8f0; }
.announcement-form .recurrence-option { padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 0.5rem; cursor: pointer; transition: all 0.2s; }
.announcement-form .recurrence-option:hover { background: #f1f5f9; }
.announcement-form .recurrence-option.active { background: #dbeafe; border-color: #3b82f6; }
</style>

<div class="announcement-form">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><?php echo ($action==='edit'?'Edit':'Create'); ?> Announcement</h1>
  <div>
    <a class="btn btn-outline-secondary btn-sm me-2" href="<?php echo site_url('announcements/templates'); ?>">
      <i class="bi bi-envelope me-1"></i>Email Templates
    </a>
    <a class="btn btn-secondary btn-sm" href="<?php echo site_url('announcements'); ?>">Back</a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <form method="post">
      <!-- Tabs Navigation -->
      <ul class="nav nav-tabs mb-4" id="announcementTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab">
            <i class="bi bi-file-text me-2"></i>Content
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="schedule-tab" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab">
            <i class="bi bi-clock me-2"></i>Schedule
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab">
            <i class="bi bi-envelope me-2"></i>Email Settings
          </button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content" id="announcementTabContent">
        <!-- Content Tab -->
        <div class="tab-pane fade show active" id="content" role="tabpanel">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input class="form-control" name="title" value="<?php echo htmlspecialchars(isset($row->title)?$row->title:''); ?>" required placeholder="Enter announcement title" />
            </div>
            <div class="col-md-4">
              <label class="form-label">Priority</label>
              <?php $priority = isset($row->priority)?$row->priority:'medium'; ?>
              <select class="form-select" name="priority">
                <option value="low" <?php echo $priority==='low'?'selected':''; ?>>🟢 Low</option>
                <option value="medium" <?php echo $priority==='medium'?'selected':''; ?>>🟡 Medium</option>
                <option value="high" <?php echo $priority==='high'?'selected':''; ?>>🔴 High</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Content <span class="text-danger">*</span></label>
              <textarea class="form-control" name="content" rows="8" required placeholder="Write your announcement content here..."><?php echo htmlspecialchars(isset($row->content)?$row->content:''); ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Target Audience</label>
              <select class="form-select" name="target_roles">
                <option value="all" <?php echo (isset($row->target_roles) && $row->target_roles==='all')?'selected':''; ?>>👥 All Users</option>
                <option value="1" <?php echo (isset($row->target_roles) && $row->target_roles==='1')?'selected':''; ?>>👔 Admin Only</option>
                <option value="2" <?php echo (isset($row->target_roles) && $row->target_roles==='2')?'selected':''; ?>>👨‍💼 Managers Only</option>
                <option value="1,2" <?php echo (isset($row->target_roles) && $row->target_roles==='1,2')?'selected':''; ?>>🎯 Admin & Managers</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Display Start Date</label>
              <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars(isset($row->start_date)?$row->start_date:''); ?>" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Display End Date</label>
              <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars(isset($row->end_date)?$row->end_date:''); ?>" />
            </div>
          </div>
        </div>

        <!-- Schedule Tab -->
        <div class="tab-pane fade" id="schedule" role="tabpanel">
          <div class="schedule-section mb-4">
            <h6 class="mb-3">📅 Publishing Schedule</h6>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Publish Date & Time</label>
                <input type="datetime-local" class="form-control" name="publish_at" value="<?php echo htmlspecialchars(isset($row->publish_at)?$row->publish_at:''); ?>" />
                <small class="text-muted">Leave empty to publish immediately</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">Expire Date & Time</label>
                <input type="datetime-local" class="form-control" name="expire_at" value="<?php echo htmlspecialchars(isset($row->expire_at)?$row->expire_at:''); ?>" />
                <small class="text-muted">When to automatically expire this announcement</small>
              </div>
            </div>
          </div>

          <div class="schedule-section">
            <h6 class="mb-3">🔄 Recurring Options</h6>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="is_recurring" id="is_recurring" value="1" <?php echo (isset($row->is_recurring) && $row->is_recurring)?'checked':''; ?>>
              <label class="form-check-label" for="is_recurring">
                Enable recurring announcements
              </label>
            </div>
            
            <div id="recurrenceOptions" style="<?php echo (isset($row->is_recurring) && $row->is_recurring)?'':'display:none;'; ?>">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Recurrence Pattern</label>
                  <select class="form-select" name="recurrence_pattern" id="recurrence_pattern">
                    <option value="">Select pattern</option>
                    <option value="daily" <?php echo (isset($row->recurrence_pattern) && $row->recurrence_pattern==='daily')?'selected':''; ?>>📅 Daily</option>
                    <option value="weekly" <?php echo (isset($row->recurrence_pattern) && $row->recurrence_pattern==='weekly')?'selected':''; ?>>📆 Weekly</option>
                    <option value="monthly" <?php echo (isset($row->recurrence_pattern) && $row->recurrence_pattern==='monthly')?'selected':''; ?>>📋 Monthly</option>
                    <option value="quarterly" <?php echo (isset($row->recurrence_pattern) && $row->recurrence_pattern==='quarterly')?'selected':''; ?>>📊 Quarterly</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Recurrence End Date</label>
                  <input type="date" class="form-control" name="recurrence_end" value="<?php echo htmlspecialchars(isset($row->recurrence_end)?$row->recurrence_end:''); ?>" />
                  <small class="text-muted">Last date to create recurrence</small>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Email Tab -->
        <div class="tab-pane fade" id="email" role="tabpanel">
          <div class="schedule-section mb-4">
            <h6 class="mb-3">📧 Email Delivery Options</h6>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" name="auto_send" id="auto_send" value="1" <?php echo (isset($row->auto_send) && $row->auto_send)?'checked':''; ?>>
              <label class="form-check-label" for="auto_send">
                Automatically send email when published
              </label>
            </div>
            
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Custom Email Template (Optional)</label>
                <textarea class="form-control" name="email_template" rows="6" placeholder="Override default email template..."><?php echo htmlspecialchars(isset($row->email_template)?$row->email_template:''); ?></textarea>
                <small class="text-muted">
                  Available variables: {title}, {content}, {date}, {priority}<br>
                  Leave empty to use default template
                </small>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Status and Actions -->
      <div class="row g-3 mt-3">
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <?php $status = isset($row->status)?$row->status:'draft'; ?>
          <select class="form-select" name="status" id="status">
            <option value="draft" <?php echo $status==='draft'?'selected':''; ?>>📝 Draft</option>
            <option value="published" <?php echo $status==='published'?'selected':''; ?>>✅ Published</option>
            <option value="scheduled" <?php echo $status==='scheduled'?'selected':''; ?>>⏰ Scheduled</option>
            <option value="archived" <?php echo $status==='archived'?'selected':''; ?>>📦 Archived</option>
          </select>
        </div>
        <div class="col-md-8 align-self-end">
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-circle me-2"></i><?php echo ($action==='edit'?'Update':'Create'); ?> Announcement
            </button>
            <button type="submit" name="save_draft" value="1" class="btn btn-outline-secondary">
              <i class="bi bi-save me-2"></i>Save Draft
            </button>
            <a class="btn btn-outline-danger" href="<?php echo site_url('announcements'); ?>">
              <i class="bi bi-x-circle me-2"></i>Cancel
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle recurrence options
    const isRecurring = document.getElementById('is_recurring');
    const recurrenceOptions = document.getElementById('recurrenceOptions');
    
    if (isRecurring && recurrenceOptions) {
        isRecurring.addEventListener('change', function() {
            recurrenceOptions.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // Auto-set status based on publish_at
    const publishAt = document.querySelector('input[name="publish_at"]');
    const statusSelect = document.getElementById('status');
    
    if (publishAt && statusSelect) {
        publishAt.addEventListener('change', function() {
            if (this.value && new Date(this.value) > new Date()) {
                statusSelect.value = 'scheduled';
            } else if (statusSelect.value === 'scheduled') {
                statusSelect.value = 'draft';
            }
        });
    }
    
    // Save draft functionality
    const saveDraftBtn = document.querySelector('button[name="save_draft"]');
    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('status').value = 'draft';
            this.closest('form').submit();
        });
    }
});
</script>

<?php $this->load->view('partials/footer'); ?>
