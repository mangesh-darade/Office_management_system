<?php $this->load->view('partials/header', ['title' => 'WhatsApp Integration', 'active' => 'whatsapp']); ?>

<div class="row">
  <div class="col-12">
    <?php if($this->session->flashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?php echo esc_view($this->session->flashdata('success')); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <?php if($this->session->flashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo esc_view($this->session->flashdata('error')); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Configuration Status -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>WhatsApp Configuration</h5>
      </div>
      <div class="card-body">
        <?php if ($config_configured): ?>
          <div class="alert alert-success mb-0">
            <i class="bi bi-check-circle me-2"></i><strong>WhatsApp is configured and ready to use!</strong>
            <br><small>Provider: <?php echo strtoupper($provider); ?></small>
            <hr class="my-2">
            <div class="alert alert-info mb-0 py-2">
              <strong><i class="bi bi-info-circle me-1"></i>Important:</strong> For Twilio Sandbox, recipients must join the sandbox first by sending the join code to <code>whatsapp:+14155238886</code>. 
              <a href="https://www.twilio.com/console/sms/whatsapp/sandbox" target="_blank" class="alert-link">Learn more about Twilio Sandbox</a>
            </div>
          </div>
        <?php else: ?>
          <div class="alert alert-danger mb-0">
            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>WhatsApp Not Configured - Buttons Disabled</h6>
            <p class="mb-2"><strong>The "Send via WhatsApp" buttons are disabled because Twilio credentials are missing.</strong></p>
            <p class="mb-2">To enable WhatsApp sending, add your Twilio credentials in the API Integrations module:</p>
            <ol class="mb-2">
              <li>Get your Twilio Account SID and Auth Token from <a href="https://www.twilio.com/console" target="_blank">Twilio Console</a></li>
              <li>Go to <a href="<?php echo site_url('api-integrations/create'); ?>">Settings → API Integrations → Add Integration</a></li>
              <li>Select <strong>Service Type: WhatsApp (Twilio)</strong></li>
              <li>Enter your Account SID, Auth Token, and From Number</li>
              <li>For WhatsApp, join the <a href="https://www.twilio.com/console/sms/whatsapp/sandbox" target="_blank">Twilio Sandbox</a> (free for testing)</li>
              <li>Refresh this page after adding credentials</li>
            </ol>
            <p class="mb-0">
              <strong>Quick Links:</strong>
              <a href="<?php echo site_url('api-integrations/create'); ?>" class="btn btn-sm btn-primary ms-2">
                <i class="bi bi-plus-circle me-1"></i>Add WhatsApp Integration
              </a>
              <a href="https://www.twilio.com/console" target="_blank" class="btn btn-sm btn-outline-primary ms-1">
                <i class="bi bi-box-arrow-up-right me-1"></i>Get Twilio Credentials
              </a>
              <a href="https://www.twilio.com/console/sms/whatsapp/sandbox" target="_blank" class="btn btn-sm btn-outline-primary ms-1">
                <i class="bi bi-box-arrow-up-right me-1"></i>WhatsApp Sandbox
              </a>
            </p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Send Message Card -->
    <div class="card shadow-sm mb-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-whatsapp me-2"></i>Send WhatsApp Message</h5>
      </div>
      <div class="card-body">
        <form id="whatsapp-form">
          <div class="mb-3">
            <label class="form-label">Select Employee <span class="text-danger">*</span></label>
            <select name="employee_id" id="employee-select" class="form-select" required>
              <option value="">-- Select Employee --</option>
              <?php foreach ($employees as $emp): ?>
                <option value="<?php echo $emp->id; ?>" data-phone="<?php echo esc_view($emp->phone); ?>">
                  <?php 
                    $first_name = isset($emp->first_name) ? $emp->first_name : '';
                    $last_name = isset($emp->last_name) ? $emp->last_name : '';
                    echo esc_view(trim($first_name . ' ' . $last_name)); 
                  ?>
                  (<?php echo esc_view($emp->emp_code); ?>) - <?php echo esc_view($emp->phone); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Only employees with phone numbers are shown</div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Message <span class="text-danger">*</span></label>
            <textarea name="message" id="message-input" class="form-control" rows="6" 
                      placeholder="Type your WhatsApp message here..." required></textarea>
            <div class="form-text">
              <strong>Tips:</strong>
              <ul class="mb-0 small">
                <li>Use *text* for <strong>bold</strong></li>
                <li>Use _text_ for <em>italic</em></li>
                <li>Use ~text~ for <strike>strikethrough</strike></li>
                <li>Use ```code``` for monospace</li>
              </ul>
            </div>
          </div>
          
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-light" onclick="document.getElementById('whatsapp-form').reset();">Clear</button>
            <button type="submit" class="btn btn-success" id="send-whatsapp-btn" <?php echo !$config_configured ? 'disabled' : ''; ?>>
              <i class="bi bi-whatsapp me-1"></i>Send via WhatsApp
            </button>
            <?php if (!$config_configured): ?>
              <div class="alert alert-warning mt-2 mb-0 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Note:</strong> WhatsApp is not configured. Add your Twilio credentials in <code>application/config/whatsapp.php</code> to enable sending.
              </div>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h6 class="mb-0"><i class="bi bi-list-task me-2"></i>Send Task Notification</h6>
          </div>
          <div class="card-body">
            <form id="task-whatsapp-form">
              <div class="mb-3">
                <label class="form-label">Task ID</label>
                <input type="number" name="task_id" class="form-control" placeholder="Enter task ID" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Employee (Optional)</label>
                <select name="employee_id" class="form-select">
                  <option value="">Use task assigned employee</option>
                  <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo $emp->id; ?>">
                      <?php 
                        $first_name = isset($emp->first_name) ? $emp->first_name : '';
                        $last_name = isset($emp->last_name) ? $emp->last_name : '';
                        echo esc_view(trim($first_name . ' ' . $last_name)); 
                      ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" class="btn btn-primary btn-sm w-100" id="send-task-btn" <?php echo !$config_configured ? 'disabled' : ''; ?>>
                <i class="bi bi-send me-1"></i>Send Task Notification
              </button>
              <?php if (!$config_configured): ?>
                <div class="alert alert-warning mt-2 mb-0 small">
                  <i class="bi bi-exclamation-triangle me-1"></i>Configure Twilio credentials to enable
                </div>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-header bg-info text-white">
            <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i>Send Report</h6>
          </div>
          <div class="card-body">
            <form id="report-whatsapp-form">
              <div class="mb-3">
                <label class="form-label">Employee <span class="text-danger">*</span></label>
                <select name="employee_id" class="form-select" required>
                  <option value="">-- Select Employee --</option>
                  <?php foreach ($employees as $emp): ?>
                    <option value="<?php echo $emp->id; ?>">
                      <?php 
                        $first_name = isset($emp->first_name) ? $emp->first_name : '';
                        $last_name = isset($emp->last_name) ? $emp->last_name : '';
                        echo esc_view(trim($first_name . ' ' . $last_name)); 
                      ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Report Type <span class="text-danger">*</span></label>
                <select name="report_type" class="form-select" required>
                  <option value="attendance">Attendance Report</option>
                  <option value="tasks">Tasks Report</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Period <span class="text-danger">*</span></label>
                <select name="period" class="form-select" required>
                  <option value="week">This Week</option>
                  <option value="month">This Month</option>
                </select>
              </div>
              <button type="submit" class="btn btn-info btn-sm w-100" id="send-report-btn" <?php echo !$config_configured ? 'disabled' : ''; ?>>
                <i class="bi bi-send me-1"></i>Send Report
              </button>
              <?php if (!$config_configured): ?>
                <div class="alert alert-warning mt-2 mb-0 small">
                  <i class="bi bi-exclamation-triangle me-1"></i>Configure Twilio credentials to enable
                </div>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Ensure jQuery is loaded
if (typeof jQuery === 'undefined') {
  console.error('jQuery is not loaded! WhatsApp forms will not work.');
} else {
  $(document).ready(function() {
    console.log('WhatsApp forms initialized');
    console.log('Config configured:', <?php echo $config_configured ? 'true' : 'false'; ?>);
    
    // Enable buttons if config is set (in case they were disabled by PHP)
    <?php if ($config_configured): ?>
    $('#send-whatsapp-btn, #send-task-btn, #send-report-btn').prop('disabled', false);
    <?php endif; ?>
    
    // Send manual message
    $('#whatsapp-form').on('submit', function(e) {
      e.preventDefault();
      console.log('WhatsApp form submitted');
      
      // Remove disabled attribute if present
      $('#send-whatsapp-btn').prop('disabled', false);
      
      const formData = {
        employee_id: $('#employee-select').val(),
        message: $('#message-input').val()
      };
      
      if (!formData.employee_id || !formData.message) {
        alert('Please select an employee and enter a message');
        return;
      }
      
      // Show loading state
      const $btn = $('#send-whatsapp-btn');
      const originalText = $btn.html();
      $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Sending...');
      
      $.ajax({
        url: '<?php echo site_url('whatsapp/send'); ?>',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert('✅ ' + response.message);
            $('#whatsapp-form')[0].reset();
          } else {
            alert('❌ ' + response.message);
          }
        },
        error: function(xhr, status, error) {
          let errorMsg = '❌ An error occurred while sending the message';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg = '❌ ' + xhr.responseJSON.message;
          } else if (xhr.responseText) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.message) {
                errorMsg = '❌ ' + response.message;
              }
            } catch(e) {
              errorMsg = '❌ Error: ' + (xhr.status ? 'HTTP ' + xhr.status : error);
            }
          }
          alert(errorMsg);
          console.error('WhatsApp send error:', xhr, status, error);
        },
        complete: function() {
          $btn.prop('disabled', false).html(originalText);
        }
      });
    });
  
    // Send task notification
    $('#task-whatsapp-form').on('submit', function(e) {
      e.preventDefault();
      console.log('Task WhatsApp form submitted');
      
      // Remove disabled attribute if present
      $('#send-task-btn').prop('disabled', false);
      
      const formData = {
        task_id: $('input[name="task_id"]', this).val(),
        employee_id: $('select[name="employee_id"]', this).val() || ''
      };
      
      if (!formData.task_id) {
        alert('Please enter a task ID');
        return;
      }
      
      // Show loading state
      const $btn = $('#send-task-btn');
      const originalText = $btn.html();
      $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Sending...');
      
      $.ajax({
        url: '<?php echo site_url('whatsapp/send-task'); ?>',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert('✅ ' + response.message);
            $('#task-whatsapp-form')[0].reset();
          } else {
            alert('❌ ' + response.message);
          }
        },
        error: function(xhr, status, error) {
          let errorMsg = '❌ An error occurred while sending the notification';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg = '❌ ' + xhr.responseJSON.message;
          } else if (xhr.responseText) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.message) {
                errorMsg = '❌ ' + response.message;
              }
            } catch(e) {
              errorMsg = '❌ Error: ' + (xhr.status ? 'HTTP ' + xhr.status : error);
            }
          }
          alert(errorMsg);
          console.error('WhatsApp task send error:', xhr, status, error);
        },
        complete: function() {
          $btn.prop('disabled', false).html(originalText);
        }
      });
    });
  
    // Send report
    $('#report-whatsapp-form').on('submit', function(e) {
      e.preventDefault();
      console.log('Report WhatsApp form submitted');
      
      // Remove disabled attribute if present
      $('#send-report-btn').prop('disabled', false);
      
      const formData = {
        employee_id: $('select[name="employee_id"]', this).val(),
        report_type: $('select[name="report_type"]', this).val(),
        period: $('select[name="period"]', this).val()
      };
      
      if (!formData.employee_id || !formData.report_type || !formData.period) {
        alert('Please fill in all required fields');
        return;
      }
      
      // Show loading state
      const $btn = $('#send-report-btn');
      const originalText = $btn.html();
      $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-1"></i>Sending...');
      
      $.ajax({
        url: '<?php echo site_url('whatsapp/send-report'); ?>',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            alert('✅ ' + response.message);
            $('#report-whatsapp-form')[0].reset();
          } else {
            alert('❌ ' + response.message);
          }
        },
        error: function(xhr, status, error) {
          let errorMsg = '❌ An error occurred while sending the report';
          if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMsg = '❌ ' + xhr.responseJSON.message;
          } else if (xhr.responseText) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.message) {
                errorMsg = '❌ ' + response.message;
              }
            } catch(e) {
              errorMsg = '❌ Error: ' + (xhr.status ? 'HTTP ' + xhr.status : error);
            }
          }
          alert(errorMsg);
          console.error('WhatsApp report send error:', xhr, status, error);
        },
        complete: function() {
          $btn.prop('disabled', false).html(originalText);
        }
      });
    });
  }); // End document.ready
} // End jQuery check
</script>

<?php $this->load->view('partials/footer'); ?>

