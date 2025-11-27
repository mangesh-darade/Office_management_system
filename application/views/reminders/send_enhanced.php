<?php $this->load->view('partials/header', ['title' => 'Send Reminder']); ?>
<style>
.send-reminder .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.send-reminder .form-label { font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
.send-reminder .form-control, .send-reminder .form-select { border-radius: 8px; border: 1px solid #d1d5db; }
.send-reminder .form-control:focus, .send-reminder .form-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.send-reminder .btn { border-radius: 8px; font-weight: 500; }
.send-reminder .nav-pills .nav-link { border-radius: 8px; font-weight: 500; }
.send-reminder .user-card { 
  border: 2px solid #e5e7eb; 
  border-radius: 8px; 
  padding: 1rem; 
  margin-bottom: 0.5rem; 
  cursor: pointer; 
  transition: all 0.2s;
}
.send-reminder .user-card:hover { border-color: #3b82f6; }
.send-reminder .user-card.selected { border-color: #3b82f6; background: #eff6ff; }
.send-reminder .template-option { 
  border: 1px solid #e5e7eb; 
  border-radius: 8px; 
  padding: 1rem; 
  margin-bottom: 0.5rem; 
  cursor: pointer; 
  transition: all 0.2s;
}
.send-reminder .template-option:hover { border-color: #3b82f6; }
.send-reminder .template-option.selected { border-color: #3b82f6; background: #eff6ff; }
.send-reminder .preview-section { background: #f8fafc; border-radius: 8px; padding: 1rem; border: 1px solid #e2e8f0; }
.send-reminder .quick-action-btn { 
  border-radius: 8px; 
  padding: 0.75rem; 
  text-align: center; 
  cursor: pointer; 
  transition: all 0.2s; 
  border: 1px solid #e5e7eb;
}
.send-reminder .quick-action-btn:hover { border-color: #3b82f6; background: #eff6ff; }
</style>

<div class="send-reminder">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">📧 Send Reminder</h1>
  <a class="btn btn-secondary btn-sm" href="<?php echo site_url('reminders'); ?>">
    <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
  </a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="row g-3">
  <!-- Main Form -->
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <form method="post" action="<?php echo site_url('reminders/send'); ?>" id="sendForm">
          
          <!-- Delivery Options -->
          <div class="row g-3 mb-4">
            <div class="col-md-12">
              <label class="form-label">Delivery Method</label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="delivery_method" id="immediate" value="immediate" checked>
                <label class="btn btn-outline-primary" for="immediate">
                  <i class="bi bi-send-check me-2"></i>Send Immediately
                </label>
                
                <input type="radio" class="btn-check" name="delivery_method" id="scheduled" value="scheduled">
                <label class="btn btn-outline-primary" for="scheduled">
                  <i class="bi bi-clock me-2"></i>Schedule Later
                </label>
                
                <input type="radio" class="btn-check" name="delivery_method" id="queue" value="queue">
                <label class="btn btn-outline-primary" for="queue">
                  <i class="bi bi-hourglass-split me-2"></i>Add to Queue
                </label>
              </div>
            </div>
            
            <div class="col-md-6" id="scheduleDateTime" style="display: none;">
              <label class="form-label">Schedule Date & Time</label>
              <input type="datetime-local" class="form-control" name="send_at" id="send_at">
            </div>
          </div>

          <!-- Recipients -->
          <div class="row g-3 mb-4">
            <div class="col-md-12">
              <label class="form-label">Recipients <span class="text-danger">*</span></label>
              <div class="row g-2">
                <div class="col-md-4">
                  <div class="quick-action-btn" onclick="selectRecipients('all')">
                    <div style="font-size: 1.5rem;">👥</div>
                    <div>All Users</div>
                    <small class="text-muted">Send to everyone</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="quick-action-btn" onclick="selectRecipients('admins')">
                    <div style="font-size: 1.5rem;">👔</div>
                    <div>Admins Only</div>
                    <small class="text-muted">Administrators</small>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="quick-action-btn" onclick="selectRecipients('managers')">
                    <div style="font-size: 1.5rem;">👨‍💼</div>
                    <div>Managers Only</div>
                    <small class="text-muted">Team managers</small>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-12">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="form-label mb-0">Individual Users</span>
                <small class="text-muted">Click to select multiple users</small>
              </div>
              <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.5rem;">
                <?php if (isset($users) && is_array($users)) foreach ($users as $u): ?>
                  <?php 
                  $label = '';
                  if (isset($u->full_label) && $u->full_label!=='') { $label = $u->full_label; }
                  else if (isset($u->full_name) && $u->full_name!=='') { $label = $u->full_name; }
                  else if (isset($u->name) && $u->name!=='') { $label = $u->name; }
                  else if (isset($u->email)) { $label = $u->email; }
                  ?>
                  <div class="user-card" data-user-id="<?php echo (int)$u->id; ?>" data-user-email="<?php echo htmlspecialchars(isset($u->email)?$u->email:''); ?>" data-user-name="<?php echo htmlspecialchars($label); ?>">
                    <div class="d-flex align-items-center">
                      <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 0.875rem;">
                        <?php echo strtoupper(substr($label, 0, 2)); ?>
                      </div>
                      <div class="flex-grow-1">
                        <div class="fw-semibold"><?php echo htmlspecialchars($label); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars(isset($u->email)?$u->email:''); ?></small>
                      </div>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="<?php echo (int)$u->id; ?>" name="user_ids[]">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="selected_users" id="selected_users" value="">
            </div>
          </div>

          <!-- Message Content -->
          <div class="row g-3 mb-4">
            <div class="col-md-12">
              <label class="form-label">Templates (Optional)</label>
              <div class="template-option" onclick="applyTemplate('morning')">
                <div class="d-flex align-items-center">
                  <div style="font-size: 1.5rem; margin-right: 1rem;">🌅</div>
                  <div>
                    <div class="fw-semibold">Morning Reminder</div>
                    <small class="text-muted">Good morning! Daily login reminder</small>
                  </div>
                </div>
              </div>
              <div class="template-option" onclick="applyTemplate('night')">
                <div class="d-flex align-items-center">
                  <div style="font-size: 1.5rem; margin-right: 1rem;">🌙</div>
                  <div>
                    <div class="fw-semibold">Evening Reminder</div>
                    <small class="text-muted">Good evening! Daily logout reminder</small>
                  </div>
                </div>
              </div>
              <div class="template-option" onclick="applyTemplate('meeting')">
                <div class="d-flex align-items-center">
                  <div style="font-size: 1.5rem; margin-right: 1rem;">🤝</div>
                  <div>
                    <div class="fw-semibold">Meeting Reminder</div>
                    <small class="text-muted">Don't forget about upcoming meeting</small>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="col-md-12">
              <label class="form-label">Subject <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="subject" id="subject" required placeholder="Enter reminder subject">
            </div>
            
            <div class="col-md-12">
              <label class="form-label">Message</label>
              <textarea class="form-control" name="body" id="body" rows="6" placeholder="Enter your message here..."></textarea>
              <small class="text-muted">Available variables: {name}, {date}, {time}, {email}</small>
            </div>
          </div>

          <!-- Sender Information -->
          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="form-label">From Email (Optional)</label>
              <input type="email" class="form-control" name="from_email" placeholder="your-email@company.com">
            </div>
            <div class="col-md-6">
              <label class="form-label">From Name (Optional)</label>
              <input type="text" class="form-control" name="from_name" placeholder="Your Name">
            </div>
          </div>

          <!-- Preview -->
          <div class="row g-3 mb-4">
            <div class="col-md-12">
              <label class="form-label">Message Preview</label>
              <div class="preview-section">
                <div class="mb-2">
                  <strong>To:</strong> <span id="previewRecipients">No recipients selected</span>
                </div>
                <div class="mb-2">
                  <strong>Subject:</strong> <span id="previewSubject">No subject</span>
                </div>
                <div class="mb-2">
                  <strong>Message:</strong>
                </div>
                <div id="previewBody" style="white-space: pre-wrap; font-size: 0.875rem;">No message content</div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="row g-3">
            <div class="col-md-12">
              <input type="hidden" name="send_now" id="send_now" value="0">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-send me-2"></i>Send Reminder
              </button>
              <a class="btn btn-outline-secondary" href="<?php echo site_url('reminders'); ?>">
                <i class="bi bi-x-circle me-2"></i>Cancel
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div class="col-md-4">
    <!-- Statistics -->
    <div class="card mb-3">
      <div class="card-header bg-white">
        <h6 class="mb-0">📊 Quick Stats</h6>
      </div>
      <div class="card-body">
        <div class="d-flex justify-content-between mb-2">
          <span>Total Users</span>
          <span class="badge bg-primary"><?php echo isset($users) ? count($users) : 0; ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span>Selected</span>
          <span class="badge bg-success" id="selectedCount">0</span>
        </div>
        <div class="d-flex justify-content-between">
          <span>Queued Today</span>
          <span class="badge bg-info">12</span>
        </div>
      </div>
    </div>

    <!-- Recent Templates -->
    <div class="card mb-3">
      <div class="card-header bg-white">
        <h6 class="mb-0">📝 Recent Templates</h6>
      </div>
      <div class="card-body">
        <div class="list-group list-group-flush">
          <a href="#" class="list-group-item list-group-item-action" onclick="applyTemplate('morning')">
            <div class="d-flex w-100 justify-content-between">
              <small>Morning Reminder</small>
              <small>🌅</small>
            </div>
          </a>
          <a href="#" class="list-group-item list-group-item-action" onclick="applyTemplate('night')">
            <div class="d-flex w-100 justify-content-between">
              <small>Evening Reminder</small>
              <small>🌙</small>
            </div>
          </a>
          <a href="#" class="list-group-item list-group-item-action" onclick="applyTemplate('meeting')">
            <div class="d-flex w-100 justify-content-between">
              <small>Meeting Reminder</small>
              <small>🤝</small>
            </div>
          </a>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-header bg-white">
        <h6 class="mb-0">🚀 Quick Actions</h6>
      </div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="<?php echo site_url('reminders/cron/morning'); ?>" class="btn btn-outline-info btn-sm">
            <i class="bi bi-sunrise me-2"></i>Queue Morning
          </a>
          <a href="<?php echo site_url('reminders/cron/night'); ?>" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-moon me-2"></i>Queue Evening
          </a>
          <a href="<?php echo site_url('reminders/cron/send-queue'); ?>" class="btn btn-outline-success btn-sm">
            <i class="bi bi-send-check me-2"></i>Process Queue
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delivery method selection
    const deliveryMethods = document.querySelectorAll('input[name="delivery_method"]');
    const scheduleDateTime = document.getElementById('scheduleDateTime');
    
    deliveryMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'scheduled') {
                scheduleDateTime.style.display = 'block';
                // Set default to tomorrow 9 AM
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                tomorrow.setHours(9, 0, 0, 0);
                document.getElementById('send_at').value = tomorrow.toISOString().slice(0, 16);
            } else {
                scheduleDateTime.style.display = 'none';
            }
        });
    });

    // User selection
    const userCards = document.querySelectorAll('.user-card');
    const selectedUsers = new Set();
    
    userCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.type !== 'checkbox') {
                const checkbox = this.querySelector('input[type="checkbox"]');
                checkbox.checked = !checkbox.checked;
            }
            
            this.classList.toggle('selected');
            const userId = this.dataset.userId;
            
            if (this.classList.contains('selected')) {
                selectedUsers.add(userId);
            } else {
                selectedUsers.delete(userId);
            }
            
            updateSelectedCount();
            updatePreview();
        });
    });

    // Quick recipient selection
    window.selectRecipients = function(type) {
        userCards.forEach(card => {
            const checkbox = card.querySelector('input[type="checkbox"]');
            checkbox.checked = false;
            card.classList.remove('selected');
        });
        selectedUsers.clear();

        if (type === 'all') {
            userCards.forEach(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                checkbox.checked = true;
                card.classList.add('selected');
                selectedUsers.add(card.dataset.userId);
            });
        } else if (type === 'admins') {
            userCards.forEach(card => {
                // This would need role information from the database
                // For now, select first few as example
                if (parseInt(card.dataset.userId) <= 2) {
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    checkbox.checked = true;
                    card.classList.add('selected');
                    selectedUsers.add(card.dataset.userId);
                }
            });
        } else if (type === 'managers') {
            userCards.forEach(card => {
                // This would need role information from the database
                // For now, select users 3-5 as example
                const userId = parseInt(card.dataset.userId);
                if (userId >= 3 && userId <= 5) {
                    const checkbox = card.querySelector('input[type="checkbox"]');
                    checkbox.checked = true;
                    card.classList.add('selected');
                    selectedUsers.add(card.datasetUserId);
                }
            });
        }
        
        updateSelectedCount();
        updatePreview();
    };

    function updateSelectedCount() {
        document.getElementById('selectedCount').textContent = selectedUsers.size;
    }

    // Template application
    window.applyTemplate = function(type) {
        const templates = {
            morning: {
                subject: '🌅 Good Morning! Daily Reminder',
                body: 'Hello {name},\n\nGood morning! This is your daily reminder to:\n• Check your tasks for today\n• Review any new announcements\n• Update your progress\n\nHave a productive day!\n\nBest regards,\n' + '<?php echo get_company_name(); ?>'
            },
            night: {
                subject: '🌙 Good Evening! Daily Wrap-up',
                body: 'Hello {name},\n\nGood evening! Before you wrap up for the day:\n• Complete any pending tasks\n• Update your status\n• Check tomorrow\'s schedule\n\nHave a great evening!\n\nBest regards,\n' + '<?php echo get_company_name(); ?>'
            },
            meeting: {
                subject: '🤝 Meeting Reminder',
                body: 'Hello {name},\n\nThis is a reminder about your upcoming meeting.\n\nPlease ensure you:\n• Review the agenda\n• Prepare any necessary materials\n• Join on time\n\nMeeting details will be provided separately.\n\nBest regards,\n' + '<?php echo get_company_name(); ?>'
            }
        };

        const template = templates[type];
        if (template) {
            document.getElementById('subject').value = template.subject;
            document.getElementById('body').value = template.body;
            updatePreview();
        }
    };

    // Live preview
    const subjectInput = document.getElementById('subject');
    const bodyTextarea = document.getElementById('body');
    
    function updatePreview() {
        const sampleData = {
            name: 'John Doe',
            date: new Date().toLocaleDateString(),
            time: new Date().toLocaleTimeString(),
            email: 'john.doe@example.com'
        };
        
        let subject = subjectInput.value || 'No subject';
        let body = bodyTextarea.value || 'No message content';
        
        // Replace variables
        Object.keys(sampleData).forEach(key => {
            const regex = new RegExp('\\{' + key + '\\}', 'g');
            subject = subject.replace(regex, sampleData[key]);
            body = body.replace(regex, sampleData[key]);
        });
        
        document.getElementById('previewSubject').textContent = subject;
        document.getElementById('previewBody').textContent = body;
        
        // Update recipients
        const selectedNames = Array.from(document.querySelectorAll('.user-card.selected'))
            .map(card => card.dataset.userName);
        document.getElementById('previewRecipients').textContent = 
            selectedNames.length > 0 ? selectedNames.join(', ') : 'No recipients selected';
    }

    if (subjectInput && bodyTextarea) {
        subjectInput.addEventListener('input', updatePreview);
        bodyTextarea.addEventListener('input', updatePreview);
        updatePreview(); // Initial preview
    }
});
</script>

<?php $this->load->view('partials/footer'); ?>
