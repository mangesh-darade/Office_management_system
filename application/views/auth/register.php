<?php 
  // Hide navbar and sidebar for register page, and allow full-width control
  $this->load->view('partials/header', ['title' => 'Register', 'hide_navbar' => true, 'with_sidebar' => false, 'full_width' => true]); 
?>

<style>
.register-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  position: relative;
}
.register-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
  background-size: cover;
}
.register-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  padding: 1rem;
  width: 100%;
  max-width: 500px;
  position: relative;
  z-index: 1;
  animation: slideUp 0.6s ease-out;
}
@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.register-header {
  text-align: center;
  margin-bottom: 1rem;
}
.register-header h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.25rem;
}
.register-header p {
  color: #6b7280;
  font-size: 0.75rem;
}
.form-floating {
  margin-bottom: 0.625rem;
}
.form-floating .form-control {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 0.75rem 0.625rem;
  font-size: 0.875rem;
  height: auto;
  transition: all 0.2s ease;
  background: #f9fafb;
}
.form-floating .form-control:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  background: white;
}
.form-floating label {
  color: #6b7280;
  font-size: 0.75rem;
  font-weight: 500;
}
.form-select {
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 0.75rem 0.625rem;
  font-size: 0.875rem;
  transition: all 0.2s ease;
  background: #f9fafb;
}
.form-select:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  background: white;
}
.input-group .form-control {
  border-radius: 12px 0 0 12px;
  border-right: none;
}
.input-group .btn {
  border-radius: 0 12px 12px 0;
  border: 1px solid #e5e7eb;
  border-left: none;
  background: #f9fafb;
  color: #6b7280;
  font-weight: 500;
  transition: all 0.2s ease;
}
.input-group .btn:hover {
  background: #667eea;
  color: white;
  border-color: #667eea;
}
.btn-register {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 10px;
  padding: 0.75rem;
  font-weight: 600;
  font-size: 0.875rem;
  color: white;
  width: 100%;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}
.btn-register::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s ease;
}
.btn-register:hover::before {
  left: 100%;
}
.btn-register:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}
.btn-register:active {
  transform: translateY(0);
}
.btn-register:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.form-text {
  font-size: 0.75rem;
  margin-top: 0.25rem;
}
.form-text.text-success {
  color: #10b981;
}
.form-text.text-danger {
  color: #ef4444;
}
.form-text.text-muted {
  color: #6b7280;
}
.signin-link {
  text-align: center;
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #e5e7eb;
  font-size: 0.75rem;
  color: #6b7280;
}
.signin-link a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s ease;
}
.signin-link a:hover {
  color: #764ba2;
  text-decoration: underline;
}
@media (max-width: 640px) {
  .register-card {
    margin: 1rem;
  }
  .register-brand h2 {
    font-size: 1.25rem;
  }
}
</style>

<div class="register-page">
  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <!-- Toast messages will be dynamically added here -->
  </div>

  <div class="register-card">
    <!-- Form Section -->
    <div class="register-header">
      <h3>Create Account</h3>
      <p>Fill in your information to register</p>
    </div>
    
    <form method="post" id="registerForm" novalidate>
      <div class="form-floating">
        <input type="text" name="name" class="form-control" id="nameInput" placeholder="Your full name" required>
        <label for="nameInput">Full Name</label>
        <div class="invalid-feedback">Please enter your full name</div>
      </div>
      
      <div class="form-floating">
        <input type="email" name="email" class="form-control" id="regEmail" placeholder="you@example.com" required>
        <label for="regEmail">Email Address</label>
        <div class="invalid-feedback">Please enter a valid email</div>
      </div>
      
      <div class="form-floating">
        <input type="text" name="verify_code" class="form-control" id="verifyCodeInput" placeholder="Enter verification code" required>
        <label for="verifyCodeInput">Verification Code</label>
        <div class="invalid-feedback">Please enter the verification code</div>
      </div>
      
      <div class="form-floating">
        <input type="text" name="phone" class="form-control" id="phoneInput" placeholder="Enter mobile number" required>
        <label for="phoneInput">Mobile Number</label>
        <div class="invalid-feedback">Please enter your mobile number</div>
      </div>
      
      <div class="form-floating">
        <input type="password" name="password" class="form-control" id="passwordInput" placeholder="At least 6 characters" required>
        <label for="passwordInput">Password</label>
        <div class="invalid-feedback">Password must be at least 6 characters</div>
      </div>
      
      <div class="form-floating">
        <select name="role_id" class="form-select" id="roleSelect" required>
          <option value="">Select Role</option>
          <?php
            $roleOptions = isset($roles) && is_array($roles) && !empty($roles)
              ? $roles
              : [1 => 'Admin', 2 => 'Manager', 3 => 'Lead', 4 => 'Staff'];
            foreach ($roleOptions as $id => $name): ?>
              <option value="<?php echo (int)$id; ?>"><?php echo htmlspecialchars($name); ?></option>
          <?php endforeach; ?>
        </select>
        <label for="roleSelect">Role</label>
        <div class="invalid-feedback">Please select a role</div>
      </div>
      
      <div class="d-grid gap-2 mt-2">
        <button class="btn btn-register" type="submit" id="registerBtn">Create Account</button>
      </div>
    </form>
    
    <div class="signin-link">
      Already have an account? <a href="<?php echo site_url('auth/login'); ?>">Sign in</a>
    </div>
  </div>
</div>

<script>
(function(){
  // Show flash messages as toasts on page load
  <?php if($this->session->flashdata('success')): ?>
    showToast('success', '<?php echo htmlspecialchars($this->session->flashdata('success')); ?>');
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    showToast('error', '<?php echo htmlspecialchars($this->session->flashdata('error')); ?>');
  <?php endif; ?>

  // Toast helper function
  function showToast(type, message) {
    const toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) return;

    const toastId = 'toast-' + Date.now();
    const toastClass = type === 'success' ? 'bg-success' : 'bg-danger';
    const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';

    const toastHtml = `
      <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header ${toastClass} text-white">
          <i class="bi ${icon} me-2"></i>
          <strong class="me-auto">${type === 'success' ? 'Success' : 'Error'}</strong>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
          ${message}
        </div>
      </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
      autohide: true,
      delay: 2000
    });
    
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
      toastElement.remove();
    });
  }

  // Email verification
  var site = '<?php echo rtrim(site_url(), "/"); ?>/';
  var emailInput = document.getElementById('regEmail');
  var verifyCodeInput = document.getElementById('verifyCodeInput');
  var registerForm = document.getElementById('registerForm');
  var verificationSent = false;
  
  if (emailInput && verifyCodeInput) {
    emailInput.addEventListener('blur', function(){
      var email = (emailInput.value || '').trim();
      if (email && email.includes('@')) {
        // Auto-send verification code when valid email is entered
        sendVerificationCode(email);
        verificationSent = true;
      }
    });
    
    // Show verification feedback when user types code
    verifyCodeInput.addEventListener('input', function(){
      if (verificationSent && this.value.length >= 4) {
        var feedbackDiv = this.parentNode.querySelector('.verification-feedback');
        if (!feedbackDiv) {
          feedbackDiv = document.createElement('div');
          feedbackDiv.className = 'verification-feedback text-success small mt-1';
          this.parentNode.appendChild(feedbackDiv);
        }
        feedbackDiv.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Code verified';
      }
    });
  }

  function sendVerificationCode(email) {
    // Show loading indicator
    var emailInput = document.getElementById('regEmail');
    var feedbackDiv = emailInput.parentNode.querySelector('.verification-feedback');
    if (!feedbackDiv) {
      feedbackDiv = document.createElement('div');
      feedbackDiv.className = 'verification-feedback text-muted small mt-1';
      emailInput.parentNode.appendChild(feedbackDiv);
    }
    feedbackDiv.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Sending verification code...';
    
    fetch(site + 'auth/send-verify-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ email: email })
    }).then(function(res){ return res.json(); }).then(function(data){
      if (data && data.ok) {
        showToast('success', 'Verification code sent to your email');
        // Update to success message
        feedbackDiv.className = 'verification-feedback text-success small mt-1';
        feedbackDiv.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Code sent successfully';
      } else {
        showToast('error', (data && data.error) ? data.error : 'Failed to send verification code');
        // Update to error message
        feedbackDiv.className = 'verification-feedback text-danger small mt-1';
        feedbackDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Failed to send code';
      }
    }).catch(function(){
      showToast('error', 'Error sending verification code');
      // Update to error message
      feedbackDiv.className = 'verification-feedback text-danger small mt-1';
      feedbackDiv.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-1"></i>Error sending code';
    });
  }

  // Form validation and submission
  if (registerForm) {
    registerForm.addEventListener('submit', function(event){
      event.preventDefault();
      
      // Clear previous validation
      registerForm.classList.remove('was-validated');
      
      // Get all field values
      var nameInput = document.getElementById('nameInput');
      var emailInput = document.getElementById('regEmail');
      var verifyCodeInput = document.getElementById('verifyCodeInput');
      var phoneInput = document.getElementById('phoneInput');
      var passwordInput = document.getElementById('passwordInput');
      var roleSelect = document.getElementById('roleSelect');
      
      // Validate each field and show specific errors
      var errors = [];
      
      // Name validation
      if (!nameInput.value.trim()) {
        errors.push('Please enter your full name');
        nameInput.classList.add('is-invalid');
      } else if (nameInput.value.trim().length < 2) {
        errors.push('Name must be at least 2 characters long');
        nameInput.classList.add('is-invalid');
      } else {
        nameInput.classList.remove('is-invalid');
      }
      
      // Email validation
      if (!emailInput.value.trim()) {
        errors.push('Please enter your email address');
        emailInput.classList.add('is-invalid');
      } else if (!isValidEmail(emailInput.value)) {
        errors.push('Please enter a valid email address');
        emailInput.classList.add('is-invalid');
      } else {
        emailInput.classList.remove('is-invalid');
      }
      
      // Verification code validation
      if (!verifyCodeInput.value.trim()) {
        errors.push('Please enter the verification code');
        verifyCodeInput.classList.add('is-invalid');
      } else if (verifyCodeInput.value.trim().length < 4) {
        errors.push('Verification code must be at least 4 characters');
        verifyCodeInput.classList.add('is-invalid');
      } else {
        verifyCodeInput.classList.remove('is-invalid');
      }
      
      // Phone validation
      if (!phoneInput.value.trim()) {
        errors.push('Please enter your mobile number');
        phoneInput.classList.add('is-invalid');
      } else if (phoneInput.value.trim().length < 10) {
        errors.push('Mobile number must be at least 10 digits');
        phoneInput.classList.add('is-invalid');
      } else {
        phoneInput.classList.remove('is-invalid');
      }
      
      // Password validation
      if (!passwordInput.value) {
        errors.push('Please enter your password');
        passwordInput.classList.add('is-invalid');
      } else if (passwordInput.value.length < 6) {
        errors.push('Password must be at least 6 characters long');
        passwordInput.classList.add('is-invalid');
      } else if (!/(?=.*[a-zA-Z])(?=.*\d)/.test(passwordInput.value)) {
        errors.push('Password must contain at least one letter and one number');
        passwordInput.classList.add('is-invalid');
      } else {
        passwordInput.classList.remove('is-invalid');
      }
      
      // Role validation
      if (!roleSelect.value) {
        errors.push('Please select a role');
        roleSelect.classList.add('is-invalid');
      } else {
        roleSelect.classList.remove('is-invalid');
      }
      
      // Show errors if any
      if (errors.length > 0) {
        // Show first error as toast
        showToast('error', errors[0]);
        
        // Mark form as validated to show inline errors
        registerForm.classList.add('was-validated');
        
        // Scroll to first error field
        var firstErrorField = registerForm.querySelector('.is-invalid');
        if (firstErrorField) {
          firstErrorField.focus();
          firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return;
      }
      
      // Show loading state
      var registerBtn = document.getElementById('registerBtn');
      var originalText = registerBtn.innerHTML;
      registerBtn.disabled = true;
      registerBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Creating Account...';
      
      // Submit form
      var formData = new FormData(registerForm);
      fetch('<?php echo site_url("auth/register"); ?>', {
        method: 'POST',
        body: formData
      })
      .then(function(response) {
        if (response.redirected) {
          // Successful registration - redirect
          window.location.href = response.url;
        } else {
          // Failed registration - reload page to show flash message
          window.location.reload();
        }
      })
      .catch(function(error) {
        console.error('Registration error:', error);
        // Restore button and show error toast
        registerBtn.disabled = false;
        registerBtn.innerHTML = originalText;
        showToast('error', 'Connection error. Please check your internet and try again.');
      });
    });
    
    // Real-time validation feedback
    var inputs = registerForm.querySelectorAll('input[required], select[required]');
    inputs.forEach(function(input) {
      input.addEventListener('blur', function() {
        validateField(input);
      });
      
      input.addEventListener('input', function() {
        // Clear error on typing
        if (this.classList.contains('is-invalid')) {
          this.classList.remove('is-invalid');
        }
      });
    });
  }
  
  // Field validation helper
  function validateField(field) {
    field.classList.remove('is-invalid');
    
    if (!field.value.trim()) {
      field.classList.add('is-invalid');
      return false;
    }
    
    // Specific field validations
    if (field.id === 'regEmail' && !isValidEmail(field.value)) {
      field.classList.add('is-invalid');
      return false;
    }
    
    if (field.id === 'passwordInput' && field.value.length < 6) {
      field.classList.add('is-invalid');
      return false;
    }
    
    if (field.id === 'phoneInput' && field.value.length < 10) {
      field.classList.add('is-invalid');
      return false;
    }
    
    return true;
  }
  
  // Email validation helper
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }
})();
</script>

<?php $this->load->view('partials/footer'); ?>
