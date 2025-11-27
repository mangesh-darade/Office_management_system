<?php 
  // Load settings for branding
  $ci =& get_instance();
  $ci->load->model('Setting_model', 'settings');
  $settings = $ci->settings->get_all_settings();
  
  // Hide navbar and sidebar for login page, and allow full-width control
  $this->load->view('partials/header', ['title' => 'Login', 'hide_navbar' => true, 'with_sidebar' => false, 'full_width' => true]); 
?>

<style>
.login-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  position: relative;
}
.login-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
  background-size: cover;
}
.login-card {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  padding: 2rem;
  width: 100%;
  max-width: 450px;
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
.login-brand {
  text-align: center;
  margin-bottom: 1.75rem;
}
.login-brand .logo {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  font-size: 1.5rem;
  color: white;
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}
.login-brand h2 {
  font-size: 1.625rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.25rem;
}
.login-brand p {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}
.login-header {
  text-align: center;
  margin-bottom: 1.5rem;
}
.login-header h3 {
  font-size: 1.375rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.25rem;
}
.login-header p {
  color: #6b7280;
  font-size: 0.8rem;
}
.form-floating {
  margin-bottom: 1rem;
}
.form-floating .form-control {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 0.875rem 0.75rem;
  font-size: 0.9rem;
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
  font-size: 0.875rem;
  font-weight: 500;
}
.password-toggle {
  position: absolute;
  right: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  z-index: 10;
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 6px;
  transition: all 0.2s ease;
}
.password-toggle:hover {
  color: #667eea;
  background: rgba(102, 126, 234, 0.05);
}
.form-options {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}
.form-check {
  margin-bottom: 0;
}
.form-check-input {
  width: 1.25rem;
  height: 1.25rem;
  border-radius: 6px;
  border: 2px solid #d1d5db;
  transition: all 0.2s ease;
}
.form-check-input:checked {
  background-color: #667eea;
  border-color: #667eea;
}
.form-check-input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.form-check-label {
  font-size: 0.875rem;
  color: #4b5563;
  font-weight: 500;
  margin-left: 0.5rem;
}
.forgot-link {
  color: #667eea;
  text-decoration: none;
  font-size: 0.875rem;
  font-weight: 500;
  transition: color 0.2s ease;
}
.forgot-link:hover {
  color: #764ba2;
  text-decoration: underline;
}
.btn-login {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 12px;
  padding: 0.875rem;
  font-weight: 600;
  font-size: 0.95rem;
  color: white;
  width: 100%;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}
.btn-login::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
  transition: left 0.5s ease;
}
.btn-login:hover::before {
  left: 100%;
}
.btn-login:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}
.btn-login:active {
  transform: translateY(0);
}
.signup-link {
  text-align: center;
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e5e7eb;
  font-size: 0.875rem;
  color: #6b7280;
}
.signup-link a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.2s ease;
}
.signup-link a:hover {
  color: #764ba2;
  text-decoration: underline;
}
@media (max-width: 640px) {
  .login-card {
    padding: 2rem;
    margin: 1rem;
  }
  .login-brand h2 {
    font-size: 1.5rem;
  }
  .form-options {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
}
</style>

<div class="login-page">
  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <!-- Toast messages will be dynamically added here -->
  </div>

  <div class="login-card">
    <!-- Brand Section -->
    <div class="login-brand">
      <div class="logo">
        <?php echo isset($settings['company_logo']) && $settings['company_logo'] ? 
          '<img src="'.base_url('uploads/'.$settings['company_logo']).'" alt="Logo" style="max-width: 50px; max-height: 50px;">' : 
          '<i class="bi bi-building"></i>'; ?>
      </div>
      <h2><?php echo isset($settings['company_name']) ? htmlspecialchars($settings['company_name']) : 'OfficeMgmt'; ?></h2>
      <p>Sign in to continue to your account</p>
    </div>

    <!-- Form Section -->
    <div class="login-header">
      <h3>Welcome Back</h3>
      <p>Please enter your credentials</p>
    </div>
    
    <form method="post" id="loginForm" novalidate>
      <div class="form-floating">
        <input type="text" name="login" class="form-control" id="loginInput" placeholder="Enter email or phone" required autocomplete="tel">
        <label for="loginInput">Email or Phone Number</label>
        <div class="invalid-feedback">Please enter your email or phone number</div>
      </div>
      
      <div class="form-floating position-relative">
        <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Password" required autocomplete="current-password">
        <label for="loginPassword">Password</label>
        <button type="button" class="password-toggle" id="btnTogglePassword" aria-label="Show password">
          <i class="bi bi-eye" id="iconTogglePassword"></i>
        </button>
        <div class="invalid-feedback">Please enter your password</div>
      </div>
      
      <div class="form-options">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
          <label class="form-check-label" for="rememberMe">Remember me</label>
        </div>
        <a href="<?php echo site_url('auth/forgot_password'); ?>" class="forgot-link">Forgot password?</a>
      </div>
      
      <button class="btn btn-login" type="submit">Sign In</button>
    </form>
    
    <div class="signup-link">
      Don't have an account? <a href="<?php echo site_url('auth/register'); ?>">Sign up</a>
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
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
      toastElement.remove();
    });
  }

  // Password toggle
  var passwordInput = document.getElementById('loginPassword');
  var toggleBtn = document.getElementById('btnTogglePassword');
  var toggleIcon = document.getElementById('iconTogglePassword');
  
  if (passwordInput && toggleBtn) {
    toggleBtn.addEventListener('click', function(){
      var type = passwordInput.type === 'password' ? 'text' : 'password';
      passwordInput.type = type;
      toggleIcon.className = type === 'text' ? 'bi bi-eye-slash' : 'bi bi-eye';
      toggleBtn.setAttribute('aria-label', type === 'text' ? 'Hide password' : 'Show password');
    });
  }
  
  // Form validation and submission
  var loginForm = document.getElementById('loginForm');
  var loginBtn = document.querySelector('.btn-login');
  
  if (loginForm && loginBtn) {
    loginForm.addEventListener('submit', function(event){
      event.preventDefault();
      
      // Clear previous validation
      loginForm.classList.remove('was-validated');
      
      // Get form fields
      var loginInput = document.getElementById('loginInput');
      var passwordInput = document.getElementById('loginPassword');
      
      // Clear previous error states
      loginInput.classList.remove('is-invalid');
      passwordInput.classList.remove('is-invalid');
      
      // Validate and collect errors
      var errors = [];
      
      // Validate login field
      if (!loginInput.value.trim()) {
        errors.push('Please enter your email or mobile number');
        loginInput.classList.add('is-invalid');
      } else if (loginInput.value.includes('@') && !isValidEmail(loginInput.value)) {
        errors.push('Please enter a valid email address');
        loginInput.classList.add('is-invalid');
      }
      
      // Validate password field
      if (!passwordInput.value) {
        errors.push('Please enter your password');
        passwordInput.classList.add('is-invalid');
      }
      
      // Show errors if any
      if (errors.length > 0) {
        // Show first error as toast
        showToast('error', errors[0]);
        
        // Mark form as validated to show inline errors
        loginForm.classList.add('was-validated');
        
        // Focus on first error field
        var firstErrorField = loginForm.querySelector('.is-invalid');
        if (firstErrorField) {
          firstErrorField.focus();
        }
        return;
      }
      
      // Show loading state
      var originalText = loginBtn.innerHTML;
      loginBtn.disabled = true;
      loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Signing in...';
      
      // Submit form
      var formData = new FormData(loginForm);
      fetch('<?php echo site_url("auth/login"); ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
      })
      .then(function(response) {
        return response.json().then(function(data) {
          if (response.ok && data.success) {
            // Show success toast before redirect
            showToast('success', 'Login successful! Redirecting to dashboard...');
            
            // Redirect after showing toast
            setTimeout(function() {
              window.location.href = data.redirect;
            }, 1500);
          } else {
            // Show error message from server
            showToast('error', data.error || 'Login failed. Please try again.');
            
            // Restore button state
            loginBtn.disabled = false;
            loginBtn.innerHTML = originalText;
          }
        }).catch(function() {
          // If JSON parsing fails, treat as non-AJAX response
          if (response.redirected) {
            showToast('success', 'Login successful! Redirecting to dashboard...');
            setTimeout(function() {
              window.location.href = response.url;
            }, 1500);
          } else {
            showToast('error', 'Login failed. Please try again.');
            loginBtn.disabled = false;
            loginBtn.innerHTML = originalText;
          }
        });
      })
      .catch(function(error) {
        console.error('Login error:', error);
        // Restore button and show error toast
        loginBtn.disabled = false;
        loginBtn.innerHTML = originalText;
        
        // Show error toast
        showToast('error', 'Connection error. Please check your internet and try again.');
      });
    });
    
    // Real-time validation feedback
    var inputs = loginForm.querySelectorAll('input[required]');
    inputs.forEach(function(input) {
      input.addEventListener('blur', function() {
        validateLoginField(input);
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
  function validateLoginField(field) {
    field.classList.remove('is-invalid');
    
    if (!field.value.trim()) {
      field.classList.add('is-invalid');
      return false;
    }
    
    // Email validation if email entered
    if (field.id === 'loginInput' && field.value.includes('@') && !isValidEmail(field.value)) {
      field.classList.add('is-invalid');
      return false;
    }
    
    return true;
  }
  
  // Email validation helper
  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }
  
  // Auto-focus first empty field
  var loginInput = document.getElementById('loginInput');
  var passwordInput = document.getElementById('loginPassword');
  
  if (loginInput && !loginInput.value) {
    loginInput.focus();
  } else if (passwordInput && !passwordInput.value) {
    passwordInput.focus();
  }
  
  // Handle Enter key in form fields
  [loginInput, passwordInput].forEach(function(input) {
    if (input) {
      input.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          loginForm.dispatchEvent(new Event('submit'));
        }
      });
    }
  });
})();
</script>

<?php $this->load->view('partials/footer'); ?>
