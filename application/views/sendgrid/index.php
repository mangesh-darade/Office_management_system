<?php $this->load->view('partials/header', ['title' => 'SendGrid Email', 'active' => 'sendgrid']); ?>

<div class="row justify-content-center">
  <div class="col-12 col-md-10 col-lg-8">
    <?php if($this->session->flashdata('success')): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($this->session->flashdata('success')); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <?php if($this->session->flashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($this->session->flashdata('error')); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Configuration Status Card -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>SendGrid Configuration</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="d-flex align-items-center">
              <span class="me-2">API Key Status:</span>
              <?php if ($api_key_configured): ?>
                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Configured</span>
                <small class="text-muted ms-2"><?php echo htmlspecialchars($api_key_preview); ?></small>
              <?php else: ?>
                <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Not Configured</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div>
              <strong>From Email:</strong> 
              <?php if (empty($from_email) || $from_email === 'noreply@example.com'): ?>
                <code class="text-danger"><?php echo htmlspecialchars($from_email ?: 'Not Set'); ?></code>
                <span class="badge bg-danger ms-2">Not Verified</span>
              <?php else: ?>
                <code><?php echo htmlspecialchars($from_email); ?></code>
                <span class="badge bg-warning ms-2">Verify in SendGrid</span>
              <?php endif; ?>
            </div>
            <div class="mt-1">
              <strong>From Name:</strong> <?php echo htmlspecialchars($from_name); ?>
            </div>
          </div>
        </div>
        
        <?php if (empty($from_email) || $from_email === 'noreply@example.com'): ?>
          <div class="alert alert-danger mt-3 mb-0">
            <h6 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Sender Email Not Verified</h6>
            <p class="mb-2"><strong>You must verify your sender email address in SendGrid before sending emails.</strong></p>
            <ol class="mb-2">
              <li>Log in to <a href="https://app.sendgrid.com" target="_blank">SendGrid Dashboard</a></li>
              <li>Go to <strong>Settings → Sender Authentication</strong></li>
              <li>Click <strong>"Verify a Single Sender"</strong></li>
              <li>Fill in your email details and verify via email</li>
              <li>Update the <strong>From Email</strong> in <a href="<?php echo site_url('api-integrations'); ?>">Settings → API Integrations</a> with your verified email</li>
            </ol>
            <p class="mb-0">
              <strong>Quick Link:</strong> 
              <a href="https://app.sendgrid.com/settings/sender_auth/senders/new" target="_blank" class="btn btn-sm btn-primary">
                <i class="bi bi-box-arrow-up-right me-1"></i>Verify Sender Email in SendGrid
              </a>
              <a href="<?php echo site_url('api-integrations'); ?>" class="btn btn-sm btn-outline-primary ms-2">
                <i class="bi bi-gear me-1"></i>Configure API Integrations
              </a>
            </p>
          </div>
        <?php endif; ?>
        
        <?php if (!$api_key_configured): ?>
          <div class="alert alert-warning mt-3 mb-0">
            <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Setup Required</h6>
            <p class="mb-2">To use SendGrid, you need to:</p>
            <ol class="mb-2">
              <li>Sign up at <a href="https://sendgrid.com" target="_blank">sendgrid.com</a></li>
              <li>Create an API key in your SendGrid dashboard</li>
              <li>Add SendGrid integration in <a href="<?php echo site_url('api-integrations/create'); ?>">Settings → API Integrations</a></li>
              <li>Verify your sender email address in SendGrid dashboard</li>
            </ol>
            <p class="mb-0">
              <a href="<?php echo site_url('api-integrations/create'); ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Add SendGrid Integration
              </a>
            </p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Send Email Card -->
    <div class="card shadow-sm">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-envelope me-2"></i>Send Email via SendGrid</span>
        <a href="<?php echo site_url('sendgrid/test'); ?>" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-send me-1"></i>Send Test Email
        </a>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('sendgrid/send'); ?>" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">To <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="to" 
                   placeholder="recipient@example.com" 
                   value="<?php echo htmlspecialchars($this->session->userdata('email') ?: ''); ?>" 
                   required>
          </div>
          
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">CC <span class="text-muted small">(optional, comma separated)</span></label>
              <input type="text" class="form-control" name="cc" 
                     placeholder="cc1@example.com, cc2@example.com">
            </div>
            <div class="col-md-6">
              <label class="form-label">BCC <span class="text-muted small">(optional, comma separated)</span></label>
              <input type="text" class="form-control" name="bcc" 
                     placeholder="bcc1@example.com, bcc2@example.com">
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Subject <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="subject" 
                   placeholder="Email Subject" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Message <span class="text-danger">*</span></label>
            <textarea class="form-control" name="message" rows="8" 
                      placeholder="Write your message..." required></textarea>
            <div class="form-text">You can use HTML tags if you check "HTML Email" below</div>
          </div>
          
          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_html" id="is_html" value="1" checked>
              <label class="form-check-label" for="is_html">
                HTML Email (format message as HTML)
              </label>
            </div>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Attachment <span class="text-muted small">(optional)</span></label>
            <input type="file" class="form-control" name="attachment" 
                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.csv">
            <div class="form-text">Maximum file size: 10MB</div>
          </div>
          
          <div class="d-flex justify-content-end gap-2">
            <a href="<?php echo site_url('sendgrid'); ?>" class="btn btn-light">Cancel</a>
            <button type="submit" class="btn btn-primary" <?php echo !$api_key_configured ? 'disabled' : ''; ?>>
              <i class="bi bi-send me-1"></i>Send Email
            </button>
          </div>
        </form>
      </div>
      <div class="card-footer small text-muted">
        <div class="row">
          <div class="col-md-6">
            <strong>From:</strong> <code><?php echo htmlspecialchars($from_email); ?></code> 
            (<?php echo htmlspecialchars($from_name); ?>)
          </div>
          <div class="col-md-6 text-end">
            <strong>Method:</strong> SendGrid REST API v3
          </div>
        </div>
        <?php if (!$api_key_configured): ?>
          <div class="mt-2 text-danger">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <strong>Note:</strong> API key must be configured before sending emails.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Information Card -->
    <div class="card shadow-sm mt-4">
      <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>About SendGrid</h5>
      </div>
      <div class="card-body">
        <h6>What is SendGrid?</h6>
        <p>SendGrid is a cloud-based email delivery service that provides reliable email infrastructure. It's an alternative to SMTP-based email sending.</p>
        
        <h6 class="mt-3">Advantages of SendGrid:</h6>
        <ul>
          <li><strong>High Deliverability:</strong> Better inbox placement rates</li>
          <li><strong>Scalability:</strong> Handle large volumes of emails</li>
          <li><strong>Analytics:</strong> Track opens, clicks, bounces, and more</li>
          <li><strong>API-Based:</strong> No SMTP configuration needed</li>
          <li><strong>Reliability:</strong> 99.99% uptime SLA</li>
        </ul>
        
        <h6 class="mt-3">Setup Instructions:</h6>
        <ol>
          <li>Create a free account at <a href="https://sendgrid.com" target="_blank">sendgrid.com</a> (100 emails/day free)</li>
          <li>Go to Settings → API Keys → Create API Key</li>
          <li>Give it "Full Access" or "Mail Send" permissions</li>
          <li>Copy the API key (you'll only see it once!)</li>
          <li>Set it as environment variable: <code>SENDGRID_API_KEY</code></li>
          <li>Or add it to <code>application/config/sendgrid.php</code></li>
          <li>Verify your sender email in SendGrid dashboard (Settings → Sender Authentication)</li>
        </ol>
        
        <div class="alert alert-warning mt-3 mb-0">
          <strong>Important:</strong> The sender email address must be verified in your SendGrid account before you can send emails.
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>

