<?php $this->load->view('partials/header', array('title' => (isset($title) ? $title : 'User'), 'active' => 'users')); ?>
<div class="oms-form-compact">
<div class="row g-2 oms-form-grid">
  <div class="col-12">
    <div class="card mb-3 border-0 shadow-sm">
      <div class="card-body d-flex justify-content-between align-items-center">
        <h1 class="h5 mb-0"><?php echo esc_view(isset($title) ? $title : 'User'); ?></h1>
        <a href="<?php echo site_url('users'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card shadow-soft border-0">
      <div class="card-body">
        <?php 
        // Use CodeIgniter form helper to automatically include CSRF token
        // form_open_multipart() automatically adds the CSRF token
        $form_action = $is_edit ? site_url('users/update/'.(int)$row->id) : site_url('users/store');
        $form_attrs = array('id' => 'userForm', 'class' => 'needs-validation', 'novalidate' => '');
        echo form_open_multipart($form_action, $form_attrs);
        ?>
          <div class="row g-2 oms-form-grid">
            <div class="col-md-4">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control <?php echo form_error('name') ? 'is-invalid' : ''; ?>"
                     value="<?php echo esc_view(set_value('name', isset($row->name) ? $row->name : '')); ?>" required>
              <?php if (form_error('name')): ?><div class="invalid-feedback"><?php echo form_error('name'); ?></div><?php endif; ?>
            </div>
            <?php if (!$is_edit): ?>
            <div class="col-md-4">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <div class="input-group <?php echo form_error('email') ? 'is-invalid' : ''; ?>">
                <input type="email" name="email" id="userEmail"
                       class="form-control <?php echo form_error('email') ? 'is-invalid' : ''; ?>"
                       value="<?php echo esc_view(set_value('email', isset($row->email) ? $row->email : '')); ?>"
                       placeholder="you@example.com" required>
                <button class="btn btn-outline-secondary" type="button" id="btnSendCode">Send code</button>
              </div>
              <?php if (form_error('email')): ?><div class="invalid-feedback d-block"><?php echo form_error('email'); ?></div><?php endif; ?>
              <div class="form-text" id="emailHelp"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Verification Code</label>
              <input type="text" name="verify_code" class="form-control" placeholder="Enter code sent to your email">
            </div>
            <?php else: ?>
            <div class="col-md-4">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email"
                     class="form-control <?php echo form_error('email') ? 'is-invalid' : ''; ?>"
                     value="<?php echo esc_view(set_value('email', isset($row->email) ? $row->email : '')); ?>" required>
              <?php if (form_error('email')): ?><div class="invalid-feedback"><?php echo form_error('email'); ?></div><?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="col-md-4">
              <label class="form-label">Role <span class="text-danger">*</span></label>
              <?php
                $roleOptions = isset($roles) && is_array($roles) && !empty($roles)
                  ? $roles
                  : [1 => 'Admin', 2 => 'Manager', 3 => 'Lead', 4 => 'Staff'];
                $rid = isset($row->role_id) ? (int)$row->role_id : null;
                if (!$rid && isset($row->role)) {
                  $current = strtolower(trim((string)$row->role));
                  foreach ($roleOptions as $id => $name) {
                    if (strtolower(trim($name)) === $current) { $rid = (int)$id; break; }
                  }
                }
                if (!$rid) {
                  $firstKey = null;
                  foreach ($roleOptions as $k => $v) { $firstKey = $k; break; }
                  $rid = $firstKey !== null ? (int)$firstKey : 1;
                }
              ?>
              <select name="role_id" class="form-select <?php echo form_error('role_id') ? 'is-invalid' : ''; ?>" required>
                <?php foreach ($roleOptions as $id => $name): ?>
                  <option value="<?php echo (int)$id; ?>" <?php echo $rid===(int)$id?'selected':''; ?>><?php echo esc_view($name); ?></option>
                <?php endforeach; ?>
              </select>
              <?php if (form_error('role_id')): ?><div class="invalid-feedback"><?php echo form_error('role_id'); ?></div><?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="form-label">Shift</label>
              <select name="shift_id" class="form-select">
                <option value="">-- Default / No Shift --</option>
                <?php if(isset($shifts) && !empty($shifts)): ?>
                  <?php foreach($shifts as $shift): ?>
                    <option value="<?php echo $shift->id; ?>" <?php echo (isset($current_shift_id) && $current_shift_id == $shift->id) ? 'selected' : ''; ?>>
                      <?php echo esc_view($shift->name); ?> (<?php echo date('H:i', strtotime($shift->start_time)) . ' - ' . date('H:i', strtotime($shift->end_time)); ?>)
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label" for="department_id">Department</label>
              <select name="department_id" id="department_id" class="form-select">
                <option value="">-- Select department --</option>
                <?php
                  $currentDeptName = (isset($employee) && isset($employee->department)) ? (string)$employee->department : '';
                  $currentDeptId = (isset($employee) && isset($employee->department_id)) ? (int)$employee->department_id : 0;
                ?>
                <?php if (isset($departments) && !empty($departments)) : foreach ($departments as $d): ?>
                  <?php
                    $sel = '';
                    if ($currentDeptId && $currentDeptId === (int)$d->id) {
                      $sel = 'selected';
                    } elseif (!$currentDeptId && $currentDeptName !== '' && $currentDeptName === (string)$d->dept_name) {
                      $sel = 'selected';
                    }
                  ?>
                  <option value="<?php echo (int)$d->id; ?>" <?php echo $sel; ?>><?php echo esc_view($d->dept_name); ?></option>
                <?php endforeach; endif; ?>
              </select>
              <input type="hidden" name="department" value="<?php echo esc_view((isset($employee) && isset($employee->department)) ? $employee->department : ''); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label" for="designation_id">Designation</label>
              <select name="designation_id" id="designation_id" class="form-select">
                <option value="">-- Select designation --</option>
                <?php
                  $currentDesgName = (isset($employee) && isset($employee->designation)) ? (string)$employee->designation : '';
                  $currentDesgId = (isset($employee) && isset($employee->designation_id)) ? (int)$employee->designation_id : 0;
                ?>
                <?php if (isset($designations) && !empty($designations)) : foreach ($designations as $dg): ?>
                  <?php
                    $sel = '';
                    if ($currentDesgId && $currentDesgId === (int)$dg->id) {
                      $sel = 'selected';
                    } elseif (!$currentDesgId && $currentDesgName !== '' && $currentDesgName === (string)$dg->designation_name) {
                      $sel = 'selected';
                    }
                  ?>
                  <option value="<?php echo (int)$dg->id; ?>" data-department-id="<?php echo isset($dg->department_id) ? (int)$dg->department_id : 0; ?>" <?php echo $sel; ?>>
                    <?php echo esc_view($dg->designation_name); ?>
                  </option>
                <?php endforeach; endif; ?>
              </select>
              <input type="hidden" name="designation" value="<?php echo esc_view((isset($employee) && isset($employee->designation)) ? $employee->designation : ''); ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Status <span class="text-danger">*</span></label>
              <?php
                $stRaw = isset($row->status) ? $row->status : 1;
                $isActive = false;
                if (is_numeric($stRaw)) {
                  $isActive = ((int)$stRaw) === 1;
                } else if (is_string($stRaw)) {
                  $isActive = in_array(strtolower(trim($stRaw)), ['active','enabled','true','yes'], true);
                }
                $st = $isActive ? 1 : 0;
              ?>
              <select name="status" class="form-select <?php echo form_error('status') ? 'is-invalid' : ''; ?>" required>
                <option value="1" <?php echo $st===1?'selected':''; ?>>Active</option>
                <option value="0" <?php echo $st===0?'selected':''; ?>>Inactive</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Phone</label>
              <input type="tel" name="phone" id="userPhone" class="form-control" value="<?php echo esc_view(isset($row->phone) ? $row->phone : ''); ?>" pattern="[0-9]{10}" maxlength="10" inputmode="numeric" title="Enter 10-digit mobile number (optional)">
              <div class="form-text" id="phoneHelp"></div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Verified</label>
              <?php $ver = (int)(isset($row->is_verified) ? $row->is_verified : 0); ?>
              <select name="is_verified" class="form-select">
                <option value="1" <?php echo $ver===1?'selected':''; ?>>Yes</option>
                <option value="0" <?php echo $ver===0?'selected':''; ?>>No</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Attendance Email</label>
              <?php $attNotify = (int)(isset($row->notify_attendance) ? $row->notify_attendance : 1); ?>
              <select name="notify_attendance" class="form-select">
                <option value="1" <?php echo $attNotify===1?'selected':''; ?>>Enabled</option>
                <option value="0" <?php echo $attNotify===0?'selected':''; ?>>Disabled</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Check-in Google Alert</label>
              <?php $gCheckin = (int)(isset($row->google_alert_checkin) ? $row->google_alert_checkin : 1); ?>
              <select name="google_alert_checkin" class="form-select">
                <option value="1" <?php echo $gCheckin===1?'selected':''; ?>>Enabled</option>
                <option value="0" <?php echo $gCheckin===0?'selected':''; ?>>Disabled</option>
              </select>
              <div class="form-text">When org check-in alerts are on, this user gets a Google Calendar reminder.</div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Check-out Google Alert</label>
              <?php $gCheckout = (int)(isset($row->google_alert_checkout) ? $row->google_alert_checkout : 1); ?>
              <select name="google_alert_checkout" class="form-select">
                <option value="1" <?php echo $gCheckout===1?'selected':''; ?>>Enabled</option>
                <option value="0" <?php echo $gCheckout===0?'selected':''; ?>>Disabled</option>
              </select>
              <div class="form-text">When org check-out alerts are on, this user gets a Google Calendar reminder.</div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Avatar</label>
              <input type="file" name="avatar" accept="image/*" class="form-control">
              <?php if (!empty($row->avatar)): ?>
                <div class="form-text">Current: <a href="<?php echo base_url(trim($row->avatar, '/')); ?>" target="_blank">View</a></div>
              <?php endif; ?>
            </div>

            <div class="col-md-4">
              <label class="form-label"><?php echo $is_edit ? 'Reset Password (optional)' : 'Password <span class="text-danger">*</span>'; ?></label>
              <input type="password" name="password" class="form-control" <?php echo $is_edit ? '' : 'required'; ?> autocomplete="new-password">
              <?php if ($is_edit): ?><div class="form-text">Leave blank to keep current password.</div><?php endif; ?>
          </div>
          </div>

        <?php echo form_close(); ?>
      </div>
    </div>

    <?php if ($is_edit && isset($row->id) && (int)$row->id > 0): ?>
    <div class="card mt-3">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Face Registration</h6>
          <?php if (isset($row->face_registered) && $row->face_registered): ?>
            <span class="badge bg-success">
              <i class="bi bi-check-circle-fill"></i> Face Registered
              <?php if (isset($row->face_registered_date) && $row->face_registered_date): ?>
                <small class="ms-1">(<?php echo date('M d, Y', strtotime($row->face_registered_date)); ?>)</small>
              <?php endif; ?>
            </span>
          <?php else: ?>
            <span class="badge bg-warning text-dark">
              <i class="bi bi-x-circle-fill"></i> Not Registered
            </span>
          <?php endif; ?>
        </div>
        <p class="small text-muted mb-3">
          <?php if (isset($row->face_registered) && $row->face_registered): ?>
            Face is already registered. You can update it by capturing a new face below.
          <?php else: ?>
            Capture the user's face using the camera. This will be used later to verify attendance.
          <?php endif; ?>
        </p>
        <div class="row g-2 align-items-start">
          <div class="col-12 col-md-6">
            <video id="faceVideo" class="w-100 border rounded" autoplay muted playsinline style="width:100%; height:400px; object-fit:cover; background:#000;"></video>
          </div>
          <div class="col-12 col-md-6">
            <canvas id="faceCanvas" class="w-100 border rounded" style="width:100%; height:400px; object-fit:contain; background:#f8f9fa;"></canvas>
            <div class="small text-muted mt-2" id="faceStatus"></div>
          </div>
        </div>
        <div class="mt-3 d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-outline-primary" id="btnFaceStart">
            <i class="bi bi-camera-video me-2"></i> Start Camera
          </button>
          <button type="button" class="btn btn-primary" id="btnFaceCapture" disabled>
            <i class="bi bi-camera-fill me-2"></i> Capture &amp; Save Face
          </button>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info mt-3 small">After creating the user, open Edit User to register their face for attendance.</div>
    <?php endif; ?>

    <div class="mt-4 d-flex justify-content-end gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo site_url('users'); ?>">
        <i class="bi bi-x-circle me-2"></i> Cancel
      </a>
      <button class="btn btn-primary" type="submit" form="userForm">
        <i class="bi bi-check2-circle me-2"></i> Save
      </button>
    </div>
  </div>
</div>
<script>
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    var deptInput = document.querySelector('input[name="department"]');
    var desgInput = document.querySelector('input[name="designation"]');
    var deptSelect = document.querySelector('select[name="department_id"]');
    var desgSelect = document.querySelector('select[name="designation_id"]');

    if (deptSelect) {
      deptSelect.addEventListener('change', function(){
        var opt = deptSelect.options[deptSelect.selectedIndex];
        if (opt && deptInput) {
          deptInput.value = (opt.value === '') ? '' : (opt.textContent || '');
        }
      });
    }

    if (desgSelect) {
      desgSelect.addEventListener('change', function(){
        var opt = desgSelect.options[desgSelect.selectedIndex];
        if (opt) {
          var depId = opt.getAttribute('data-department-id');
          if (depId && deptSelect && deptSelect.value !== depId) {
            deptSelect.value = depId;
            var depOpt = deptSelect.options[deptSelect.selectedIndex];
            if (depOpt && deptInput) {
              deptInput.value = (depOpt.value === '') ? '' : (depOpt.textContent || '');
            }
          }
          if (desgInput) {
            desgInput.value = (opt.value === '') ? '' : (opt.textContent || '');
          }
        }
      });
    }

    if (<?php echo $is_edit ? 'true' : 'false'; ?>) return;
    var site = '<?php echo rtrim(site_url(), "/"); ?>/';
    var emailInput = document.getElementById('userEmail');
    var phoneInput = document.getElementById('userPhone');
    var btn = document.getElementById('btnSendCode');
    var help = document.getElementById('emailHelp');
    var phoneHelp = document.getElementById('phoneHelp');
    
    if (!emailInput || !btn || !help) return;
    
    // Check for existing values on page load (handles auto-fill)
    setTimeout(function() {
      var email = (emailInput.value || '').trim();
      var phone = (phoneInput.value || '').trim();
      
      if (email && email.includes('@') && email.indexOf('@') < email.length - 1) {
        checkExistingEmail(email);
      }
      if (phone && phone.length === 10) {
        checkExistingPhone(phone);
      }
    }, 1000);
    
    // Email validation for existing users
    emailInput.addEventListener('input', function(){
      var email = (emailInput.value || '').trim();
      if (email && email.includes('@') && email.indexOf('@') < email.length - 1) {
        // Debounce to avoid too many requests
        clearTimeout(emailInput.validationTimeout);
        emailInput.validationTimeout = setTimeout(function() {
          checkExistingEmail(email);
        }, 800);
      }
    });
    
    // Phone validation for existing users
    phoneInput.addEventListener('input', function(){
      var phone = (phoneInput.value || '').trim();
      if (phone.length === 10) {
        // Debounce to avoid too many requests
        clearTimeout(phoneInput.validationTimeout);
        phoneInput.validationTimeout = setTimeout(function() {
          checkExistingPhone(phone);
        }, 800);
      }
    });
    
    // Helper: build URLSearchParams with CSRF token included
    function buildParams(obj) {
      var p = new URLSearchParams(obj);
      var m = document.cookie.match(/(?:^|;\s*)ci_csrf_token=([^;]*)/);
      if (m) p.append('<?php echo $this->security->get_csrf_token_name(); ?>', decodeURIComponent(m[1]));
      return p;
    }

    function checkExistingEmail(email) {
      fetch(site + 'users/check-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: buildParams({ email: email })
      }).then(function(res){ return res.json(); }).then(function(data){
        if (data && data.exists) {
          alert('Email "' + email + '" already exists in the system!');
          emailInput.value = '';
          emailInput.focus();
        }
      }).catch(function(){
        // Silent fail for network issues
      });
    }
    
    function checkExistingPhone(phone) {
      fetch(site + 'users/check-phone', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: buildParams({ phone: phone })
      }).then(function(res){ return res.json(); }).then(function(data){
        if (data && data.exists) {
          alert('Phone number "' + phone + '" already exists in the system!');
          phoneInput.value = '';
          phoneInput.focus();
        }
      }).catch(function(){
        // Silent fail for network issues
      });
    }
    
    btn.addEventListener('click', function(){
      var email = (emailInput.value || '').trim();
      if (!email) {
        help.textContent = 'Enter Gmail address first.';
        help.className = 'form-text text-danger';
        return;
      }
      btn.disabled = true;
      help.textContent = 'Sending verification code...';
      help.className = 'form-text text-muted';
      fetch(site + 'auth/send-verify-code', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: buildParams({ email: email })
      }).then(function(res){ return res.json(); }).then(function(data){
        if (data && data.ok) {
          help.textContent = 'Verification code sent. Please check inbox or spam.';
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
  });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.5/dist/face-api.min.js"></script>
<script>
(function(){
  var btnStart = document.getElementById('btnFaceStart');
  var btnCapture = document.getElementById('btnFaceCapture');
  if (!btnStart || !btnCapture) return;

  var video = document.getElementById('faceVideo');
  var canvas = document.getElementById('faceCanvas');
  var statusEl = document.getElementById('faceStatus');
  var stream = null;
  var modelsLoaded = false;
  var userId = <?php echo isset($row->id) ? (int)$row->id : 0; ?>;
  var MODEL_URL = 'https://cdn.jsdelivr.net/gh/cgarciagl/face-api.js/weights/';
  var existingFaceImageUrl = '<?php echo (isset($row->face_image) && $row->face_image) ? base_url($row->face_image) : ''; ?>';

  function setStatus(msg, isError){
    if (!statusEl) return;
    statusEl.textContent = msg || '';
    statusEl.classList.toggle('text-danger', !!isError);
  }

  function loadExistingFace(){
    if (!existingFaceImageUrl || !canvas) return;
    var img = new Image();
    img.crossOrigin = 'anonymous';
    img.onload = function(){
      // Set canvas to match the display size (400px height)
      var displayHeight = 400;
      var displayWidth = (img.width / img.height) * displayHeight;
      canvas.width = displayWidth;
      canvas.height = displayHeight;
      var ctx = canvas.getContext('2d');
      // Clear canvas and draw image scaled to fit
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    };
    img.onerror = function(){
      console.warn('Failed to load existing face image');
    };
    img.src = existingFaceImageUrl;
  }

  async function ensureModels(){
    if (modelsLoaded || !window.faceapi) return;
    try {
      setStatus('Loading face models...', false);
      await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
      await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
      await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
      modelsLoaded = true;
      setStatus('Models loaded. Start camera to capture face.', false);
    } catch (e){
      setStatus('Failed to load face models.', true);
    }
  }

  async function startCamera(){
    await ensureModels();
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
      setStatus('Camera not supported in this browser.', true);
      return;
    }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
      video.srcObject = stream;
      btnCapture.disabled = false;
      setStatus('Camera started. Align face and click Capture.', false);
    } catch (e){
      setStatus('Unable to access camera: ' + e.message, true);
    }
  }

  async function captureFace(){
    if (!modelsLoaded){ await ensureModels(); }
    if (!video || video.readyState < 2){
      setStatus('Camera not ready.', true);
      return;
    }
    try {
      var opts = new faceapi.TinyFaceDetectorOptions();
      var det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
      if (!det || !det.descriptor){
        setStatus('No face detected. Please try again.', true);
        return;
      }
      var ctx = canvas.getContext('2d');
      canvas.width = video.videoWidth || 320;
      canvas.height = video.videoHeight || 240;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

      var descArr = Array.prototype.slice.call(det.descriptor);
      var payload = {
        user_id: userId,
        descriptor: JSON.stringify(descArr),
        image: canvas.toDataURL('image/png')
      };

      setStatus('Saving face data...', false);

      var csrfToken = (typeof window.getCsrfToken === 'function') ? window.getCsrfToken() : '';
      if (!csrfToken) {
        var csrfInput = document.querySelector('input[name="ci_csrf_token"]');
        if (csrfInput) csrfToken = csrfInput.value;
      }

      var fd = new FormData();
      fd.append('user_id', String(payload.user_id));
      fd.append('descriptor', payload.descriptor);
      fd.append('image', payload.image);
      if (csrfToken) {
        fd.append('<?php echo $this->security->get_csrf_token_name(); ?>', csrfToken);
      }

      fetch('<?php echo site_url('users/save_face'); ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
      }).then(function(r){
        if (!r.ok && r.status === 403) {
          setStatus('Access denied. You do not have permission to save face data.', true);
          return null;
        }
        return r.text().then(function(text){
          try {
            return JSON.parse(text);
          } catch (e) {
            setStatus('Could not save face (HTTP ' + r.status + '). The server returned a page instead of JSON.', true);
            return null;
          }
        });
      }).then(function(j){
        if (!j) return;
        if (j.ok){
          setStatus('Face data saved successfully! ✓', false);
        } else {
          setStatus(j.error ? j.error : 'Failed to save face data.', true);
        }
      }).catch(function(err){ setStatus('Network error: ' + (err.message || 'Failed to save face data.'), true); });
    } catch (e){
      setStatus('Error capturing face: ' + e.message, true);
    }
  }

  btnStart.addEventListener('click', function(ev){ ev.preventDefault(); startCamera(); });
  btnCapture.addEventListener('click', function(ev){ ev.preventDefault(); captureFace(); });

  // Load existing face image into canvas on page load (if available)
  loadExistingFace();

  window.addEventListener('beforeunload', function(){
    try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); } } catch(e){}
  });
})();
</script>
</div>
<?php $this->load->view('partials/footer'); ?>
