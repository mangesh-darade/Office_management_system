<?php 
  // Load settings for branding
  $ci =& get_instance();
  $ci->load->model('Setting_model', 'settings');
  $settings = $ci->settings->get_all_settings();
  
  // Hide navbar and sidebar for 2FA page
  $this->load->view('partials/header', ['title' => 'Two-Factor Authentication', 'hide_navbar' => true, 'with_sidebar' => false, 'full_width' => true]); 
?>

<style>
.verify-2fa-page {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  position: relative;
}
.verify-2fa-page::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
  background-size: cover;
}
.verify-2fa-card {
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
.verify-2fa-header {
  text-align: center;
  margin-bottom: 2rem;
}
.verify-2fa-header .icon {
  width: 80px;
  height: 80px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  font-size: 2rem;
  color: white;
}
.verify-2fa-header h3 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a202c;
  margin-bottom: 0.5rem;
}
.verify-2fa-header p {
  color: #718096;
  font-size: 0.95rem;
}
.form-floating {
  margin-bottom: 1.5rem;
}
.form-floating input {
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1rem;
  font-size: 1rem;
  transition: all 0.3s;
}
.form-floating input:focus {
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.btn-verify {
  width: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  border-radius: 12px;
  padding: 0.875rem;
  font-size: 1rem;
  font-weight: 600;
  color: white;
  transition: all 0.3s;
  margin-top: 0.5rem;
}
.btn-verify:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}
.btn-verify:active {
  transform: translateY(0);
}
.info-box {
  background: #f7fafc;
  border-left: 4px solid #667eea;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}
.info-box p {
  margin: 0;
  color: #4a5568;
  font-size: 0.9rem;
}
@media (max-width: 576px) {
  .verify-2fa-card {
    padding: 1.5rem;
  }
  .verify-2fa-header h3 {
    font-size: 1.5rem;
  }
}
</style>

<div class="verify-2fa-page">
  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
    <!-- Toast messages will be dynamically added here -->
  </div>

  <div class="verify-2fa-card">
    <div class="verify-2fa-header">
      <div class="icon">
        <i class="bi bi-shield-lock"></i>
      </div>
      <h3>Two-Factor Authentication</h3>
      <p>Enter the verification code sent to your email</p>
    </div>
    
    <div class="info-box">
      <p><i class="bi bi-info-circle me-2"></i>A 6-digit code has been sent to your registered email address. Please check your inbox or spam folder.</p>
    </div>
    
    <?php echo form_open('auth/verify-2fa', array('id' => 'verify2faForm', 'novalidate' => '')); ?>
      <div class="form-floating">
        <input type="text" name="code" class="form-control" id="codeInput" placeholder="Enter 6-digit code" required autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}">
        <label for="codeInput">Verification Code</label>
        <div class="invalid-feedback">Please enter the 6-digit verification code</div>
      </div>
      
      <button class="btn btn-verify" type="submit">
        <i class="bi bi-shield-check me-2"></i>Verify Code
      </button>
    <?php echo form_close(); ?>
    
    <div class="text-center mt-3">
      <a href="<?php echo site_url('auth/login'); ?>" class="text-decoration-none" style="color: #667eea;">
        <i class="bi bi-arrow-left me-1"></i>Back to Login
      </a>
    </div>
  </div>
</div>

<script>
(function(){
  // Show flash messages as toasts on page load
  <?php if($this->session->flashdata('error')): ?>
    showToast('<?php echo addslashes($this->session->flashdata('error')); ?>', 'error');
  <?php endif; ?>
  <?php if($this->session->flashdata('success')): ?>
    showToast('<?php echo addslashes($this->session->flashdata('success')); ?>', 'success');
  <?php endif; ?>
  
  // Form validation
  const form = document.getElementById('verify2faForm');
  const codeInput = document.getElementById('codeInput');
  
  // Auto-focus on code input
  codeInput.focus();
  
  // Allow only numbers
  codeInput.addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
  });
  
  // Auto-submit when 6 digits entered
  codeInput.addEventListener('input', function(e) {
    if (this.value.length === 6) {
      // Optional: auto-submit after short delay
      // setTimeout(() => form.submit(), 500);
    }
  });
  
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!form.checkValidity()) {
      e.stopPropagation();
      form.classList.add('was-validated');
      return false;
    }
    
    const code = codeInput.value.trim();
    if (code.length !== 6 || !/^\d{6}$/.test(code)) {
      codeInput.setCustomValidity('Please enter a valid 6-digit code');
      form.classList.add('was-validated');
      return false;
    }
    
    codeInput.setCustomValidity('');
    form.submit();
  });
  
  function showToast(message, type = 'info') {
    const toastContainer = document.querySelector('.toast-container');
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function() {
      toast.remove();
    });
  }
})();
</script>

<?php $this->load->view('partials/footer'); ?>
