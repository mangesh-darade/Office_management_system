<?php $this->load->view('partials/header', ['title' => 'Register', 'hide_navbar' => true]); ?>
<div class="row justify-content-center">
  <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
    <div class="card shadow-soft">
      <div class="card-body p-4">
        <h1 class="h4 mb-3 text-center">Create account</h1>
        <p class="text-muted small text-center mb-4">Register a new user</p>
        <?php if($this->session->flashdata('error')): ?>
          <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="Your full name">
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
              <input type="email" name="email" id="regEmail" class="form-control" placeholder="you@gmail.com" required>
              <button class="btn btn-outline-secondary" type="button" id="btnSendCode">Send code</button>
            </div>
            <div class="form-text" id="emailHelp"></div>
          </div>
          <div class="mb-3">
            <label class="form-label">Verification Code</label>
            <input type="text" name="verify_code" class="form-control" placeholder="Enter code sent to your Gmail" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Mobile Number</label>
            <input type="text" name="phone" class="form-control" placeholder="Enter mobile number" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role_id" class="form-select" required>
              <option value="">Select role</option>
              <option value="1">Admin</option>
              <option value="2">HR</option>
              <option value="3">Lead</option>
              <option value="4">Employee</option>
            </select>
          </div>
          <button class="btn btn-primary w-100" type="submit">Register</button>
        </form>
        <div class="text-center mt-3 small">
          Already have an account?
          <a href="<?php echo site_url('login'); ?>">Sign in</a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  var site = '<?php echo rtrim(site_url(), "/"); ?>/';
  var emailInput = document.getElementById('regEmail');
  var btn = document.getElementById('btnSendCode');
  var help = document.getElementById('emailHelp');
  if (!emailInput || !btn || !help) return;
  btn.addEventListener('click', function(){
    var email = (emailInput.value || '').trim();
    if (!email) {
      help.textContent = 'Enter your Gmail address first.';
      help.className = 'form-text text-danger';
      return;
    }
    btn.disabled = true;
    help.textContent = 'Sending verification code...';
    help.className = 'form-text text-muted';
    fetch(site + 'auth/send-verify-code', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({ email: email })
    }).then(function(res){ return res.json(); }).then(function(data){
      if (data && data.ok) {
        help.textContent = 'Verification code sent to your Gmail. Please check your inbox or spam folder.';
        help.className = 'form-text text-success';
      } else {
        help.textContent = (data && data.error) ? data.error : 'Failed to send verification code.';
        help.className = 'form-text text-danger';
      }
    }).catch(function(){
      help.textContent = 'Error sending verification code.';
      help.className = 'form-text text-danger';
    }).finally(function(){
      btn.disabled = false;
    });
  });
})();
</script>
<?php $this->load->view('partials/footer'); ?>
