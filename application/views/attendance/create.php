<?php
  $this->load->view('partials/header', array('title' => 'Mark Attendance', 'extra_css' => array('assets/css/attendance-create.css')));
  $att_format_time = function ($raw) {
    $raw = trim((string) $raw);
    if ($raw === '') { return ''; }
    $ts = strtotime($raw);
    if ($ts !== false) { return date('g:i A', $ts); }
    if (preg_match('/^(\d{1,2}:\d{2}(?::\d{2})?)/', $raw, $m)) {
      $t = strtotime($m[1]);
      return $t !== false ? date('g:i A', $t) : $m[1];
    }
    return $raw;
  };
  $has_status = isset($attendance_status) && is_array($attendance_status);
  $st_in  = $has_status && !empty($attendance_status['has_checkin']);
  $st_out = $has_status && !empty($attendance_status['has_checkout']);
  $st_in_label  = $has_status ? $att_format_time(isset($attendance_status['checkin_time']) ? $attendance_status['checkin_time'] : '') : '';
  $st_out_label = $has_status ? $att_format_time(isset($attendance_status['checkout_time']) ? $attendance_status['checkout_time'] : '') : '';
  $is_holiday_blocked = isset($is_holiday) && $is_holiday;
  $can_manage_holidays = function_exists('has_module_access') && (
      has_module_access('holidays') || has_module_access('settings') || has_module_access('admin')
  );
?>
<div class="oms-form-compact">
<div class="container-fluid px-3 px-md-4 att-punch-page">
  <div class="att-punch-header">
    <div class="att-punch-header-row">
      <a class="btn btn-outline-secondary att-punch-back" href="<?php echo site_url('dashboard'); ?>" aria-label="Back to dashboard">
        <i class="bi bi-arrow-left"></i>
      </a>
      <div class="att-punch-title-wrap">
        <h1 class="att-punch-title">Mark Attendance</h1>
        <p class="att-punch-subtitle">Check in when you arrive, check out when you leave</p>
      </div>
      <span class="badge bg-primary att-punch-clock" id="liveClock">--:--:--</span>
    </div>
    <div class="att-punch-header-actions">
      <?php if (function_exists('has_module_access') && has_module_access('attendance')): ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('attendance'); ?>">
          <i class="bi bi-list-ul me-1"></i><span class="d-none d-sm-inline">Attendance </span>List
        </a>
      <?php endif; ?>
      <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('dashboard'); ?>">
        <i class="bi bi-house me-1"></i>Dashboard
      </a>
    </div>
  </div>

  <?php if($this->session->flashdata('success')): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        var toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-white bg-success border-0';
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.setAttribute('data-bs-autohide', 'true');
        toastEl.setAttribute('data-bs-delay', '3000');
        toastEl.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i><strong>Success!</strong> <?php echo esc_view($this->session->flashdata('success')); ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        document.querySelector('.toast-container').appendChild(toastEl);
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        setTimeout(function(){
          window.location.href = '<?php echo site_url('attendance'); ?>';
        }, 3000);
      });
    </script>
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function(){
        var toastEl = document.createElement('div');
        toastEl.className = 'toast align-items-center text-white bg-danger border-0';
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.setAttribute('data-bs-autohide', 'true');
        toastEl.setAttribute('data-bs-delay', '5000');
        var errorMsg = '<?php echo esc_view($this->session->flashdata('error')); ?>';
        var tipHtml = '';
        <?php if(strpos($this->session->flashdata('error'), 'Face verification') !== false): ?>
          tipHtml = '<div class="mt-2 small"><i class="bi bi-lightbulb"></i> <strong>Tip:</strong> Make sure you\'re in good lighting and facing the camera directly.</div>';
        <?php endif; ?>
        toastEl.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Error:</strong> ' + errorMsg + tipHtml + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        document.querySelector('.toast-container').appendChild(toastEl);
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
      });
    </script>
  <?php endif; ?>

  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
    <!-- Toasts will be dynamically created here -->
  </div>

  <div class="card shadow-sm att-punch-card">
    <div class="card-body p-3 p-md-4">
      <form method="post" enctype="multipart/form-data" id="attendanceForm">
        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>" />
        <input type="hidden" name="lat" value="" />
        <input type="hidden" name="lng" value="" />
        <input type="hidden" name="location_name" value="" />
        <input type="hidden" name="face_required" id="faceRequired" value="0" />
        <input type="hidden" name="face_descriptor" id="faceDescriptor" value="" />
        
        <?php if(isset($attendance_status) && ($attendance_status['has_checkin'] || $attendance_status['has_checkout'])): ?>
        <script>
          document.addEventListener('DOMContentLoaded', function(){
            // Only show attendance status toast if no error message is shown
            var hasError = <?php echo $this->session->flashdata('error') ? 'true' : 'false'; ?>;
            if (!hasError) {
              var toastEl = document.createElement('div');
              toastEl.className = 'toast align-items-center text-white bg-info border-0';
              toastEl.setAttribute('role', 'alert');
              toastEl.setAttribute('aria-live', 'assertive');
              toastEl.setAttribute('aria-atomic', 'true');
              toastEl.setAttribute('data-bs-autohide', 'true');
              toastEl.setAttribute('data-bs-delay', '5000');
              var msg = '';
              <?php if($attendance_status['has_checkin'] && $attendance_status['has_checkout']): ?>
                msg = '<i class="bi bi-info-circle-fill me-2"></i><strong>Today\'s Attendance Status:</strong><div class="mt-2"><div><i class="bi bi-check-circle"></i> Check-in: <strong><?php echo esc_view($attendance_status['checkin_time']); ?></strong></div><div><i class="bi bi-check-circle"></i> Check-out: <strong><?php echo esc_view($attendance_status['checkout_time']); ?></strong></div><div class="mt-2 small">You have already completed attendance for today.</div></div>';
              <?php elseif($attendance_status['has_checkin']): ?>
                msg = '<i class="bi bi-info-circle-fill me-2"></i><strong>Today\'s Attendance Status:</strong><div class="mt-2"><div><i class="bi bi-check-circle"></i> Check-in: <strong><?php echo esc_view($attendance_status['checkin_time']); ?></strong></div><div class="mt-2 small">You have already checked in today. You can now check out.</div></div>';
              <?php endif; ?>
              toastEl.innerHTML = '<div class="d-flex"><div class="toast-body">' + msg + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
              var container = document.querySelector('.toast-container');
              if (container) {
                container.appendChild(toastEl);
                var toast = new bootstrap.Toast(toastEl);
                toast.show();
                
                // Remove toast element after it's hidden
                toastEl.addEventListener('hidden.bs.toast', function() {
                  toastEl.remove();
                });
              }
            }
          });
        </script>
        <?php endif; ?>

        <?php if (isset($face_verification_enabled) && $face_verification_enabled && isset($has_registered_face) && !$has_registered_face): ?>
        <div class="row mb-3">
          <div class="col-12">
            <div class="alert alert-danger d-flex shadow-sm mb-0">
              <div class="me-3 mt-1">
                <i class="bi bi-person-bounding-box text-danger fs-2"></i>
              </div>
              <div>
                <h5 class="alert-heading fw-bold mb-1">Face Not Registered</h5>
                <p class="mb-2">Your face is not registered in the system. Face verification is mandatory for marking attendance.</p>
                <hr class="my-2 border-danger opacity-25">
                <p class="mb-0">
                  <a href="<?php echo site_url('profile/edit'); ?>" class="btn btn-danger btn-sm shadow-sm">
                    <i class="bi bi-camera me-1"></i> Register Face Now
                  </a>
                </p>
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>

        <?php if ($has_status): ?>
        <div class="att-punch-status-banner <?php echo ($st_in && $st_out) ? 'is-complete' : ($st_in ? 'is-checked-in' : 'is-pending'); ?>">
          <i class="bi <?php echo ($st_in && $st_out) ? 'bi-check-circle-fill' : ($st_in ? 'bi-clock-history' : 'bi-info-circle'); ?> flex-shrink-0 mt-1"></i>
          <div>
            <?php if ($st_in && $st_out): ?>
              <strong>Attendance complete for today.</strong>
              <?php if ($st_in_label !== '' || $st_out_label !== ''): ?>
                <div class="small mt-1">
                  <?php if ($st_in_label !== ''): ?>In <?php echo esc_view($st_in_label); ?><?php endif; ?>
                  <?php if ($st_out_label !== ''): ?><?php echo $st_in_label !== '' ? ' · ' : ''; ?>Out <?php echo esc_view($st_out_label); ?><?php endif; ?>
                </div>
              <?php endif; ?>
            <?php elseif ($st_in): ?>
              <?php if ($is_holiday_blocked): ?>
                <strong>You're checked in<?php echo $st_in_label !== '' ? ' at ' . esc_view($st_in_label) : ''; ?>.</strong>
                <div class="small mt-1">Attendance is closed for this holiday. Contact admin or HR if check-out is required.</div>
              <?php else: ?>
                <strong>You're checked in<?php echo $st_in_label !== '' ? ' at ' . esc_view($st_in_label) : ''; ?>.</strong>
                <div class="small mt-1">Select <strong>Check OUT</strong> below when you finish for the day.</div>
              <?php endif; ?>
            <?php else: ?>
              <?php if ($is_holiday_blocked): ?>
                <strong>Company holiday today.</strong>
                <div class="small mt-1">Attendance marking is not available.</div>
              <?php else: ?>
                <strong>Ready to start your day.</strong>
                <div class="small mt-1">Tap <strong>Check IN</strong> below to mark your arrival.</div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($is_holiday_blocked): ?>
        <div class="row mb-0">
          <div class="col-12">
            <div class="alert alert-warning mb-0">
              <div class="d-flex align-items-start">
                <i class="bi bi-calendar-event fs-4 me-3 flex-shrink-0"></i>
                <div>
                  <h5 class="alert-heading fw-bold mb-2">Today is a company holiday</h5>
                  <?php if (!empty($holiday_name)): ?>
                    <p class="mb-2"><strong><?php echo esc_view($holiday_name); ?></strong></p>
                  <?php endif; ?>
                  <p class="mb-2">Attendance marking is disabled on company holidays.</p>
                  <?php if ($can_manage_holidays): ?>
                    <p class="mb-2 small">If staff must work today, open <strong>Settings → Holidays</strong>, edit this holiday, and set status to <strong>Inactive</strong>. Attendance will be allowed until you set it back to Active.</p>
                    <a href="<?php echo site_url('settings/holidays'); ?>" class="btn btn-outline-dark btn-sm">
                      <i class="bi bi-calendar-event me-1"></i> Manage Holidays
                    </a>
                  <?php else: ?>
                    <p class="mb-0 small text-muted">If you must work today, contact your admin or HR to allow attendance for this holiday.</p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php else: ?>

        <!-- Action Selection -->
        <div class="mb-2">
          <div class="att-punch-action-toggle" role="group" aria-label="Check in or check out">
            <input type="radio" class="btn-check" name="action" id="actionIn" value="in"
                   <?php echo ($st_in && !$st_out) ? '' : (($st_in && $st_out) ? '' : 'checked'); ?>
                   <?php echo ($st_in && $st_out) ? 'disabled' : ''; ?>>
            <label class="btn btn-outline-success" for="actionIn">
              <i class="bi bi-box-arrow-in-right"></i> Check IN
            </label>

            <input type="radio" class="btn-check" name="action" id="actionOut" value="out"
                   <?php echo ($st_in && !$st_out) ? 'checked' : ''; ?>
                   <?php echo !$st_in ? 'disabled' : ''; ?>
                   <?php echo ($st_in && $st_out) ? 'disabled' : ''; ?>>
            <label class="btn btn-outline-danger" for="actionOut">
              <i class="bi bi-box-arrow-right"></i> Check OUT
            </label>
          </div>
          <p class="att-punch-hint mb-0" id="actionHint">
            <?php if ($st_in && $st_out): ?>
              <i class="bi bi-check2-circle"></i> No further action needed today.
            <?php elseif ($st_in): ?>
              <i class="bi bi-arrow-right-circle"></i> Check OUT is available — use it when you leave.
            <?php else: ?>
              <i class="bi bi-sunrise"></i> Check IN is selected — submit after face and location are ready.
            <?php endif; ?>
          </p>
        </div>

        <!-- Notes and Location -->
        <div class="mb-3">
          <label class="att-punch-section-label">
            <i class="bi bi-chat-text"></i> Notes <span class="text-muted fw-normal">(optional)</span>
          </label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Add any notes…"></textarea>
        </div>

        <!-- Location Status -->
        <div class="mb-3">
          <label class="att-punch-section-label">
            <i class="bi bi-geo-alt"></i> Location
          </label>
          <div class="att-punch-location-box is-loading" id="geoHintBox">
            <i class="bi bi-geo-alt-fill text-primary" id="geoHintIcon"></i>
            <small class="text-primary" id="geoHint">Getting your location…</small>
          </div>
        </div>

        <!-- Face Verification -->
        <?php $face_verification_enabled = isset($face_verification_enabled) ? $face_verification_enabled : true; ?>
        <div class="mb-4" id="faceVerificationSection" style="<?php echo $face_verification_enabled ? '' : 'display: none;'; ?>">
            <label class="att-punch-section-label">
              <i class="bi bi-camera"></i> Face verification <?php echo $face_verification_enabled ? '<span class="text-danger">*</span>' : ''; ?>
            </label>
            <div class="position-relative att-punch-face-wrap" id="attFaceMachine">
              <video id="attFaceVideo" class="w-100"
                     autoplay muted playsinline webkit-playsinline
                     disablePictureInPicture disableRemotePlayback
                     controlslist="nodownload nofullscreen noremoteplayback"></video>
              <canvas id="attFaceCanvas" class="att-punch-face-shot" style="display:none;"></canvas>
              <canvas id="attFaceOverlay" class="att-punch-face-overlay" style="display:none;"></canvas>
              <div class="att-punch-face-guide" aria-hidden="true"></div>
              <div class="att-punch-face-shutter" id="attFaceShutter" aria-hidden="true"></div>
              <span class="badge att-punch-face-badge" id="attFaceBadge" style="display:none;">Looking for face…</span>
              <div class="position-absolute top-50 start-50 translate-middle text-white text-center" id="cameraLoader">
                <div class="spinner-border spinner-border-sm text-light" role="status"></div>
                <div class="small mt-1">Starting camera...</div>
              </div>
            </div>
            <div class="small mt-2 text-center" id="attFaceStatus"></div>
            <button type="button" class="btn btn-primary w-100 mt-3 fw-semibold att-punch-submit" id="btnAttFaceVerify" disabled>
              <i class="bi bi-camera-fill me-2"></i> Capture face
            </button>
            <button type="button" class="btn btn-outline-secondary w-100 mt-2 fw-semibold" id="btnRetakeFace" style="display: none;">
              <i class="bi bi-arrow-clockwise me-2"></i> Retake face
            </button>
        </div>

        <!-- Submit Button -->
        <div class="mt-2">
            <button class="btn btn-primary w-100 att-punch-submit" type="submit" id="submitBtn" disabled>
              <i class="bi bi-check-circle me-2"></i>Mark attendance
            </button>
            <div class="att-punch-validation text-muted mt-2" id="validationStatus">
              <i class="bi bi-info-circle"></i> Complete required steps: Location<?php echo $face_verification_enabled ? ', Face verification' : ''; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>
  <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.5/dist/face-api.min.js"></script>
  <script>
    (function(){
      function pad(n){ return (n<10?'0':'')+n; }
      function tick(){ try { const d=new Date(); document.getElementById('liveClock').textContent = pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds()); } catch(e){} }
      document.addEventListener('DOMContentLoaded', function(){
        try { tick(); setInterval(tick, 1000); } catch(e){}
        
        try {
          var attendanceHolidayBlocked = <?php echo $is_holiday_blocked ? 'true' : 'false'; ?>;
          if (attendanceHolidayBlocked) {
            return;
          }

          var hasLocation = false;
          var locationCaptured = false;
          var cameraStarted = false;
          var locationToast = null;
          var hasFaceCapture = false;
          var faceDetectionInterval = null;
          
          // Move face verification variables to outer scope
          var btnVerify = document.getElementById('btnAttFaceVerify');
          var video = document.getElementById('attFaceVideo');
          var canvas = document.getElementById('attFaceCanvas');
          var statusEl = document.getElementById('attFaceStatus');
          var faceDescEl = document.getElementById('faceDescriptor');
          var faceReqEl = document.getElementById('faceRequired');
          var cameraLoader = document.getElementById('cameraLoader');
          var submitBtn = document.getElementById('submitBtn');
          var validationStatus = document.getElementById('validationStatus');
          var stream = null;
          var modelsLoaded = false;
          var MODEL_URL = 'https://cdn.jsdelivr.net/gh/cgarciagl/face-api.js/weights/';
          
          // Check if face verification is required from PHP setting
          var faceVerificationRequired = <?php echo $face_verification_enabled ? 'true' : 'false'; ?>;
          // Server always requires lat/lng on punch (strict mode only adds office-radius check)
          var locationRequired = true;
          
          // Check if auto capture is enabled from PHP setting
          var autoCaptureEnabled = <?php echo isset($auto_capture_enabled) && $auto_capture_enabled ? 'true' : 'false'; ?>;
          
          // Hide "Capture Face" button if auto capture is enabled
          if (autoCaptureEnabled && btnVerify) {
            btnVerify.style.display = 'none';
          }
          
          // Function to validate mandatory fields and enable/disable submit button
          function validateMandatoryFields() {
            var latEl = document.querySelector('input[name="lat"]');
            var lngEl = document.querySelector('input[name="lng"]');
            var lat = latEl ? latEl.value : '';
            var lng = lngEl ? lngEl.value : '';
            var faceDesc = faceDescEl ? faceDescEl.value : '';
            
            var locationValid = !locationRequired || (lat && lng && lat.trim() !== '' && lng.trim() !== '');
            var faceValid = !faceVerificationRequired || (faceDesc && faceDesc.trim() !== '');
            
            if (submitBtn) {
              if (locationValid && faceValid) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-secondary');
                submitBtn.classList.add('btn-primary');
                if (validationStatus) {
                  validationStatus.innerHTML = '<i class="bi bi-check-circle text-success"></i> All mandatory fields completed. You can now submit.';
                  validationStatus.classList.remove('text-muted', 'text-danger');
                  validationStatus.classList.add('text-success');
                }
              } else {
                submitBtn.disabled = true;
                submitBtn.classList.remove('btn-primary');
                submitBtn.classList.add('btn-secondary');
                var missingFields = [];
                if (locationRequired && !locationValid) missingFields.push('Location');
                if (faceVerificationRequired && !faceDesc) missingFields.push('Face Verification');
                if (validationStatus) {
                  validationStatus.innerHTML = '<i class="bi bi-info-circle"></i> Please complete all mandatory fields: ' + missingFields.join(', ');
                  validationStatus.classList.remove('text-success', 'text-danger');
                  validationStatus.classList.add('text-muted');
                }
              }
            }
          }
          
          // Call validation on page load and periodically
          validateMandatoryFields();
          setInterval(validateMandatoryFields, 1000);
          
          // Track shown toasts to prevent duplicates
          var shownToasts = {};
          
          // Function to show custom toast notifications
          function showCustomToast(message, type = 'info', delay = 3000) {
            // Create unique key for this toast message
            var toastKey = type + '_' + message.substring(0, 50);
            
            // Check if this toast was already shown recently (within 5 seconds)
            if (shownToasts[toastKey] && (Date.now() - shownToasts[toastKey]) < 5000) {
              return; // Skip duplicate toast
            }
            
            // Mark this toast as shown
            shownToasts[toastKey] = Date.now();
            
            var bgClass = 'bg-info';
            var icon = 'bi-info-circle-fill';
            
            switch(type) {
              case 'success':
                bgClass = 'bg-success';
                icon = 'bi-check-circle-fill';
                break;
              case 'error':
                bgClass = 'bg-danger';
                icon = 'bi-exclamation-triangle-fill';
                break;
              case 'warning':
                bgClass = 'bg-warning';
                icon = 'bi-exclamation-circle-fill';
                break;
              default:
                bgClass = 'bg-info';
                icon = 'bi-info-circle-fill';
            }
            
            var toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-center text-white ' + bgClass + ' border-0';
            toastEl.setAttribute('role', 'alert');
            toastEl.setAttribute('aria-live', 'assertive');
            toastEl.setAttribute('aria-atomic', 'true');
            toastEl.setAttribute('data-bs-autohide', 'true');
            toastEl.setAttribute('data-bs-delay', delay);
            toastEl.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="bi ' + icon + ' me-2"></i>' + message + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
            
            var container = document.querySelector('.toast-container');
            if (container) {
              container.appendChild(toastEl);
              var toast = new bootstrap.Toast(toastEl);
              toast.show();
              
              // Remove toast element after it's hidden
              toastEl.addEventListener('hidden.bs.toast', function() {
                toastEl.remove();
              });
            }
          }
          
          // Form submission validation
          var attendanceForm = document.getElementById('attendanceForm');
          if (attendanceForm) {
            attendanceForm.addEventListener('submit', function(e) {
              var latEl = document.querySelector('input[name="lat"]');
              var lngEl = document.querySelector('input[name="lng"]');
              var lat = latEl ? latEl.value : '';
              var lng = lngEl ? lngEl.value : '';
              var faceDesc = faceDescEl ? faceDescEl.value : '';
              
              var locationValid = !locationRequired || (lat && lng && lat.trim() !== '' && lng.trim() !== '');
              var faceValid = !faceVerificationRequired || (faceDesc && faceDesc.trim() !== '');
              
              if (!locationValid || !faceValid) {
                e.preventDefault();
                var missingFields = [];
                if (locationRequired && !locationValid) missingFields.push('Location');
                if (faceVerificationRequired && !faceDesc) missingFields.push('Face Verification');
                showCustomToast('Please complete all mandatory fields: ' + missingFields.join(', '), 'error', 5000);
                return false;
              }
              
              // Get the selected action (check-in or check-out)
              var actionIn = document.getElementById('actionIn');
              var actionOut = document.getElementById('actionOut');
              var action = '';
              var actionText = '';
              
              if (actionOut && actionOut.checked) {
                action = 'out';
                actionText = 'Check-out';
              } else {
                action = 'in';
                actionText = 'Check-in';
              }
              
              // Show toast notification for the action being performed
              showCustomToast('<strong>' + actionText + ' in progress...</strong><div class="small mt-1">Please wait while we process your attendance.</div>', 'info', 2000);
              
              // Disable submit button to prevent double submission
              if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
              }
              
              // Ensure face_required is set based on setting
              if (faceReqEl) faceReqEl.value = faceVerificationRequired ? '1' : '0';
              
              return true;
            });
          }
          
          // Track shown toasts to prevent duplicates
          var shownToasts = {};
          
          function showToast(message, type = 'info') {
            // Create unique key for this toast message
            var toastKey = type + '_' + message.substring(0, 50);
            
            // Check if this toast was already shown recently (within 5 seconds)
            if (shownToasts[toastKey] && (Date.now() - shownToasts[toastKey]) < 5000) {
              return; // Skip duplicate toast
            }
            
            // Mark this toast as shown
            shownToasts[toastKey] = Date.now();
            
            // Use showCustomToast function to show the toast
            showCustomToast(message, type, type === 'error' ? 5000 : 3000);
          }
          
          function setGeoBoxState(state, text) {
            var box = document.getElementById('geoHintBox');
            var hint = document.getElementById('geoHint');
            var icon = document.getElementById('geoHintIcon');
            if (!hint) return;
            hint.textContent = text;
            if (box) {
              box.classList.remove('is-loading', 'is-success', 'is-error');
              if (state === 'success') box.classList.add('is-success');
              else if (state === 'error') box.classList.add('is-error');
              else if (state === 'loading') box.classList.add('is-loading');
            }
            hint.classList.remove('text-primary', 'text-success', 'text-danger', 'text-secondary', 'text-muted');
            if (state === 'success') {
              hint.classList.add('text-success');
              if (icon) icon.className = 'bi bi-check-circle-fill text-success';
            } else if (state === 'error') {
              hint.classList.add('text-danger');
              if (icon) icon.className = 'bi bi-geo-alt-fill text-danger';
            } else if (state === 'loading') {
              hint.classList.add('text-primary');
              if (icon) icon.className = 'bi bi-geo-alt-fill text-primary';
            } else {
              hint.classList.add('text-secondary');
            }
          }

          function resolveAddress(lat, lng, hint, locEl){
            try {
              if (!hint) return;
              if (!lat || !lng) return;
              showToast('Location captured, resolving address...', 'info');
              setGeoBoxState('loading', 'Location captured, resolving address…');
              
              // Use a CORS proxy or skip address resolution due to CORS issues
              // For now, we'll just mark location as captured without address resolution
              setTimeout(function(){
                setGeoBoxState('success', 'Location captured successfully');
                showToast('Location captured successfully', 'success');
                
                // Location is fully captured, now start camera
                locationCaptured = true;
                
                // Validate mandatory fields after location capture
                validateMandatoryFields();
                
                if (!cameraStarted) {
                  cameraStarted = true;
                  startCameraAfterLocation();
                }
              }, 1000);
              
              // Alternative: You could use your own backend proxy for Nominatim
              /*
              var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng);
              fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function(resp){ return resp && resp.ok ? resp.json() : null; })
                .then(function(data){
                  try {
                    if (!data) return;
                    var addr = '';
                    if (data.display_name) { addr = data.display_name; }
                    else if (data.address){
                      var a = data.address;
                      var parts = [];
                      ['road','suburb','city','state','country'].forEach(function(k){ if (a[k]) parts.push(a[k]); });
                      addr = parts.join(', ');
                    }
                    if (addr) {
                      hint.textContent = addr;
                      hint.classList.remove('text-primary');
                      hint.classList.add('text-success');
                      if (locEl) { locEl.value = addr; }
                      showToast('Location resolved: ' + addr.substring(0, 50) + (addr.length > 50 ? '...' : ''), 'success');
                    }
                  } catch(e){}
                })
                .catch(function(){
                  hint.textContent = 'Location found, address unavailable';
                  hint.classList.remove('text-primary');
                  hint.classList.add('text-warning');
                  showToast('Location found but address unavailable', 'warning');
                })
                .finally(function(){
                  // Location is fully captured, now start camera
                  locationCaptured = true;
                  if (!cameraStarted) {
                    cameraStarted = true;
                    startCameraAfterLocation();
                  }
                });
              */
            } catch(e){}
          }
          
          function setFaceStatus(msg, isError){
            if (!statusEl) return;
            statusEl.textContent = msg || '';
            statusEl.classList.toggle('text-danger', !!isError);
            statusEl.classList.toggle('text-success', !isError && msg);
            
            // Show retake button if there's an error (like "No face detected")
            if (isError) {
              var retakeBtn = document.getElementById('btnRetakeFace');
              if (retakeBtn && msg && (msg.indexOf('No face detected') !== -1 || msg.indexOf('Face verification failed') !== -1 || msg.indexOf('Capture failed') !== -1)) {
                retakeBtn.style.display = 'block';
                // Hide capture button if auto capture is enabled
                if (btnVerify && autoCaptureEnabled) {
                  btnVerify.style.display = 'none';
                }
              }
            }
          }

          function getDetectorOptions(){
            var inputSize = (window.innerWidth < 768) ? 224 : 320;
            return new faceapi.TinyFaceDetectorOptions({ inputSize: inputSize, scoreThreshold: 0.4 });
          }

          function getFaceDisplaySize(){
            var wrap = video && video.parentElement;
            var w = wrap && wrap.clientWidth ? wrap.clientWidth : 320;
            var h = wrap && wrap.clientHeight ? wrap.clientHeight : 240;
            return { width: Math.round(w), height: Math.round(h) };
          }

          function setFaceBadge(text, found){
            var badge = document.getElementById('attFaceBadge');
            if (!badge) return;
            if (!text) {
              badge.style.display = 'none';
              badge.textContent = '';
              badge.classList.remove('is-found');
              return;
            }
            badge.style.display = 'inline-block';
            badge.textContent = text;
            badge.classList.toggle('is-found', !!found);
          }

          function drawFaceOverlay(det){
            var overlay = document.getElementById('attFaceOverlay');
            if (!overlay || !window.faceapi) return;
            if (!det) {
              var ctxEmpty = overlay.getContext('2d', { alpha: true });
              if (ctxEmpty) ctxEmpty.clearRect(0, 0, overlay.width, overlay.height);
              overlay.style.display = 'none';
              return;
            }
            overlay.style.display = 'block';
            var size = getFaceDisplaySize();
            if (overlay.width !== size.width || overlay.height !== size.height) {
              overlay.width = size.width;
              overlay.height = size.height;
            }
            var ctx = overlay.getContext('2d', { alpha: true });
            ctx.clearRect(0, 0, overlay.width, overlay.height);
            var resized = faceapi.resizeResults(det, size);
            faceapi.draw.drawDetections(overlay, resized);
            if (resized.landmarks) {
              faceapi.draw.drawFaceLandmarks(overlay, resized);
            }
          }

          function hideFaceOverlay(){
            var overlay = document.getElementById('attFaceOverlay');
            if (overlay) {
              var ctx = overlay.getContext('2d');
              if (ctx) ctx.clearRect(0, 0, overlay.width, overlay.height);
              overlay.style.display = 'none';
            }
            setFaceBadge('', false);
          }

          async function ensureModels(){
            if (modelsLoaded || !window.faceapi) return;
            try {
              setFaceStatus('Loading face models...', false);
              await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
              await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
              await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
              modelsLoaded = true;
              setFaceStatus('Models loaded. Starting camera...', false);
            } catch(e){ setFaceStatus('Failed to load face models.', true); }
          }

          function bindInlineCamera(videoEl, mediaStream){
            if (!videoEl) return Promise.resolve();
            videoEl.setAttribute('playsinline', 'true');
            videoEl.setAttribute('webkit-playsinline', 'true');
            videoEl.setAttribute('controlslist', 'nodownload nofullscreen noremoteplayback');
            videoEl.controls = false;
            videoEl.muted = true;
            videoEl.defaultMuted = true;
            videoEl.autoplay = true;
            videoEl.playsInline = true;
            if ('disablePictureInPicture' in videoEl) {
              videoEl.disablePictureInPicture = true;
            }
            videoEl.srcObject = mediaStream;
            return new Promise(function(resolve){
              var done = false;
              function finish(){
                if (done) return;
                done = true;
                var p = videoEl.play();
                if (p && typeof p.then === 'function') {
                  p.then(function(){ resolve(true); }).catch(function(){ resolve(false); });
                } else {
                  resolve(true);
                }
              }
              if (videoEl.readyState >= 1) {
                finish();
                return;
              }
              videoEl.addEventListener('loadedmetadata', finish, { once: true });
              setTimeout(finish, 1500);
            });
          }

          function waitForVideoReady(videoEl, timeoutMs){
            timeoutMs = timeoutMs || 6000;
            return new Promise(function(resolve){
              if (!videoEl) { resolve(false); return; }
              if (videoEl.readyState >= 2 && videoEl.videoWidth > 0) { resolve(true); return; }
              var done = false;
              function finish(ok){
                if (done) return;
                done = true;
                videoEl.removeEventListener('loadeddata', onReady);
                videoEl.removeEventListener('playing', onReady);
                videoEl.removeEventListener('canplay', onReady);
                clearTimeout(timer);
                resolve(!!ok);
              }
              function onReady(){ finish(videoEl.videoWidth > 0 || videoEl.readyState >= 2); }
              var timer = setTimeout(function(){
                finish(videoEl.readyState >= 2 && videoEl.videoWidth > 0);
              }, timeoutMs);
              videoEl.addEventListener('loadeddata', onReady);
              videoEl.addEventListener('playing', onReady);
              videoEl.addEventListener('canplay', onReady);
            });
          }

          function preventVideoNativePlayer(videoEl){
            if (!videoEl || videoEl.getAttribute('data-inline-bound') === '1') return;
            videoEl.setAttribute('data-inline-bound', '1');
            var exitFs = function(){
              try {
                if (document.fullscreenElement === videoEl && document.exitFullscreen) {
                  document.exitFullscreen();
                }
                if (videoEl.webkitDisplayingFullscreen && videoEl.webkitExitFullscreen) {
                  videoEl.webkitExitFullscreen();
                }
              } catch (e) {}
            };
            videoEl.addEventListener('webkitbeginfullscreen', function(ev){
              if (ev && ev.preventDefault) ev.preventDefault();
              exitFs();
            });
            videoEl.addEventListener('fullscreenchange', exitFs);
          }

          async function openCameraStream(){
            var attempts = [
              { video: { facingMode: { ideal: 'user' }, aspectRatio: { ideal: 0.75 }, width: { ideal: 480 }, height: { ideal: 640 } }, audio: false },
              { video: { facingMode: { ideal: 'user' } }, audio: false },
              { video: { facingMode: 'user' }, audio: false },
              { video: true, audio: false }
            ];
            var lastErr = null;
            for (var i = 0; i < attempts.length; i++) {
              try {
                return await navigator.mediaDevices.getUserMedia(attempts[i]);
              } catch (e) {
                lastErr = e;
              }
            }
            throw lastErr || new Error('Camera not available');
          }

          async function startCam(auto){
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
              setFaceStatus('Camera not supported', true);
              return;
            }
            
            // Check if location is ready before starting auto-capture
            if (auto && !locationCaptured) {
              setFaceStatus('Waiting for location capture...', false);
              return;
            }
            
            try {
              if (cameraLoader) cameraLoader.style.display = 'block';
              hideFaceOverlay();
              setFaceStatus('Starting camera...', false);

              stream = await openCameraStream();
              preventVideoNativePlayer(video);
              await bindInlineCamera(video, stream);
              var ready = await waitForVideoReady(video, 6000);

              if (cameraLoader) {
                cameraLoader.style.display = 'none';
              }
              if (!ready || !video.videoWidth) {
                setFaceStatus('Camera preview is blank. Allow camera permission and tap Retake.', true);
                var retakeEarly = document.getElementById('btnRetakeFace');
                if (retakeEarly) retakeEarly.style.display = 'block';
              }

              var modelsPromise = ensureModels();
              await modelsPromise;

              if (auto) {
                // Auto capture mode - hide "Capture Face" button, only show retake when needed
                if (btnVerify) {
                  btnVerify.style.display = 'none';
                  btnVerify.disabled = false;
                }
                // Continuous face detection - capture immediately when face detected
                setFaceStatus('Detecting face...', false);
                setFaceBadge('Looking for face…', false);
                var detectionInterval = null;
                var isDetecting = false;
                var detectionStartTime = Date.now();
                var noFaceTimeout = null;
                var retakeBtn = document.getElementById('btnRetakeFace');
                
                var detectFace = async function() {
                  if (isDetecting || (faceDescEl && faceDescEl.value)) {
                    return;
                  }
                  
                  if (!hasLocation) {
                    setFaceStatus('Location required first', true);
                    return;
                  }
                  
                  isDetecting = true;
                  try {
                    if (!modelsLoaded) {
                      await ensureModels();
                    }
                    if (!video || video.readyState < 2) {
                      isDetecting = false;
                      return;
                    }
                    
                    var opts = getDetectorOptions();
                    var det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
                    
                    if (det && det.descriptor) {
                      drawFaceOverlay(det);
                      setFaceBadge('Face detected', true);
                      // Face detected! Stop detection and capture immediately
                      if (detectionInterval) {
                        clearInterval(detectionInterval);
                        detectionInterval = null;
                        faceDetectionInterval = null;
                      }
                      if (noFaceTimeout) {
                        clearTimeout(noFaceTimeout);
                        noFaceTimeout = null;
                      }
                      isDetecting = false;
                      setFaceStatus('Face detected! Capturing...', false);
                      captureFace(true);
                    } else {
                      drawFaceOverlay(null);
                      setFaceBadge('Looking for face…', false);
                      isDetecting = false;
                      // Show retake button if no face detected after 3 seconds
                      var elapsedTime = Date.now() - detectionStartTime;
                      if (elapsedTime > 3000 && retakeBtn && retakeBtn.style.display === 'none') {
                        setFaceStatus('No face detected. Click Retake to try again.', true);
                        retakeBtn.style.display = 'block';
                        if (btnVerify) {
                          btnVerify.style.display = 'none';
                        }
                      }
                    }
                  } catch(e) {
                    console.error('Face detection error:', e);
                    isDetecting = false;
                    // Show retake button on error
                    if (retakeBtn) {
                      retakeBtn.style.display = 'block';
                    }
                    if (btnVerify) {
                      btnVerify.style.display = 'none';
                    }
                  }
                };
                
                // Start continuous face detection every 500ms
                detectionInterval = setInterval(detectFace, 500);
                faceDetectionInterval = detectionInterval; // Store globally to stop later
                // Also try immediately
                detectFace();
              } else {
                setFaceStatus('Align face and capture', false);
                setFaceBadge('Looking for face…', false);
                var previewDetecting = false;
                var previewInterval = setInterval(async function(){
                  if (previewDetecting || (faceDescEl && faceDescEl.value) || !video || video.readyState < 2) return;
                  previewDetecting = true;
                  try {
                    var det = await faceapi.detectSingleFace(video, getDetectorOptions()).withFaceLandmarks();
                    if (det) {
                      drawFaceOverlay(det);
                      setFaceBadge('Face detected', true);
                      setFaceStatus('Face detected. Tap Capture face.', false);
                    } else {
                      drawFaceOverlay(null);
                      setFaceBadge('Looking for face…', false);
                    }
                  } catch (e) {
                    drawFaceOverlay(null);
                  }
                  previewDetecting = false;
                }, 400);
                faceDetectionInterval = previewInterval;
              }
            } catch(e){ 
              setFaceStatus('Camera access denied', true);
              if (cameraLoader) {
                cameraLoader.style.display = 'block';
              }
            }
          }

          async function captureFace(autoSubmit){
            // Stop any ongoing face detection
            if (faceDetectionInterval) {
              clearInterval(faceDetectionInterval);
              faceDetectionInterval = null;
            }
            
            if (!modelsLoaded){ await ensureModels(); }
            if (!video || video.readyState < 2){ setFaceStatus('Camera not ready', true); return; }
            try {
              var opts = getDetectorOptions();
              var det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
              if (!det || !det.descriptor){ 
                setFaceStatus('No face detected', true); 
                // Show retake button when face detection fails
                var retakeBtn = document.getElementById('btnRetakeFace');
                if (retakeBtn) {
                  retakeBtn.style.display = 'block';
                }
                // Hide capture button if auto capture is enabled
                if (btnVerify && autoCaptureEnabled) {
                  btnVerify.style.display = 'none';
                }
                return; 
              }
              
              // Get actual video dimensions
              var videoWidth = video.videoWidth || 640;
              var videoHeight = video.videoHeight || 480;
              
              // Set canvas dimensions to match video
              canvas.width = videoWidth;
              canvas.height = videoHeight;
              
              // Get canvas context and clear it first
              var ctx = canvas.getContext('2d');
              ctx.clearRect(0, 0, canvas.width, canvas.height);
              
              // Draw the video frame to canvas
              ctx.drawImage(video, 0, 0, videoWidth, videoHeight);
              
              // Verify the image was drawn (optional check)
              var imageData = ctx.getImageData(0, 0, Math.min(10, canvas.width), Math.min(10, canvas.height));
              var hasData = false;
              for (var i = 0; i < imageData.data.length; i += 4) {
                if (imageData.data[i] !== 0 || imageData.data[i+1] !== 0 || imageData.data[i+2] !== 0) {
                  hasData = true;
                  break;
                }
              }
              
              if (!hasData) {
                setFaceStatus('Failed to capture image', true);
                return;
              }
              
              hideFaceOverlay();
              var machine = document.getElementById('attFaceMachine');
              if (machine) {
                machine.classList.remove('is-shutter');
                void machine.offsetWidth;
                machine.classList.add('is-shutter');
                machine.classList.add('is-captured');
              }
              if (video) video.style.display = 'none';
              if (canvas) canvas.style.display = 'block';
              setFaceBadge('Captured', true);
              
              // Hide capture button (if visible), show retake button
              if (btnVerify) btnVerify.style.display = 'none';
              var retakeBtn = document.getElementById('btnRetakeFace');
              if (retakeBtn) retakeBtn.style.display = 'block';
              
              var descArr = Array.prototype.slice.call(det.descriptor);
              if (faceDescEl) faceDescEl.value = JSON.stringify(descArr);
              if (faceReqEl) faceReqEl.value = '1';
              hasFaceCapture = true;
              setFaceStatus('Face captured successfully', false);
              
              // Validate mandatory fields after face capture
              validateMandatoryFields();
              
              // Stop camera
              try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; } } catch(e){}
              
              // Auto submit if enabled and all validations pass
              if (autoSubmit) {
                validateMandatoryFields();
                // Check if auto-submit is enabled from settings
                var autoSubmitEnabled = <?php echo isset($auto_submit_enabled) && $auto_submit_enabled ? 'true' : 'false'; ?>;
                if (autoSubmitEnabled && submitBtn && !submitBtn.disabled) {
                  // Small delay to ensure UI updates
                  setTimeout(function() {
                    if (submitBtn && !submitBtn.disabled) {
                      submitBtn.click();
                    }
                  }, 500);
                }
              }
            } catch(e){ 
              console.error('Face capture error:', e);
              setFaceStatus('Capture failed: '+e.message, true); 
            }
          }
          
          function startCameraAfterLocation() {
            // Start camera only if face verification is required
            if (!faceVerificationRequired) {
              // Face verification disabled, skip camera
              validateMandatoryFields();
              return;
            }
            // Start camera now that location is captured
            showToast('Starting camera for face verification...', 'info');
            if (btnVerify) {
              btnVerify.disabled = false;
              // Check if auto capture is enabled from settings
              var autoCaptureEnabled = <?php echo isset($auto_capture_enabled) && $auto_capture_enabled ? 'true' : 'false'; ?>;
              startCam(autoCaptureEnabled); // Use setting to determine auto or manual capture
            }
          }
          
          // Move event listener to main scope
          if (btnVerify){
            btnVerify.addEventListener('click', function(ev){ 
              ev.preventDefault(); 
              captureFace(false); 
            });
            window.addEventListener('beforeunload', function(){ 
              try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); } } catch(e){}
            });
          }
          
          // Retake face button handler
          var retakeBtn = document.getElementById('btnRetakeFace');
          if (retakeBtn) {
            retakeBtn.addEventListener('click', function(ev){
              ev.preventDefault();
              
              // Clear face data
              if (faceDescEl) faceDescEl.value = '';
              if (faceReqEl) faceReqEl.value = '0';
              hasFaceCapture = false;
              
              var machine = document.getElementById('attFaceMachine');
              if (machine) {
                machine.classList.remove('is-captured', 'is-shutter');
              }
              if (canvas) canvas.style.display = 'none';
              if (video) video.style.display = 'block';
              hideFaceOverlay();
              setFaceBadge('Looking for face…', false);
              
              // Hide retake button initially (will show again if needed)
              retakeBtn.style.display = 'none';
              
              // Show capture button only if auto capture is disabled
              if (btnVerify) {
                if (!autoCaptureEnabled) {
                  // Only show capture button if auto capture is disabled
                  btnVerify.style.display = 'block';
                } else {
                  // Keep hidden if auto capture is enabled
                  btnVerify.style.display = 'none';
                }
                btnVerify.disabled = false;
              }
              
              // Reset status message
              setFaceStatus('', false);
              
              // Clear canvas
              if (canvas) {
                var ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
              }
              
              // Restart camera - use auto capture setting to determine mode
              setFaceStatus('Restarting camera...', false);
              startCam(autoCaptureEnabled); // Use setting to determine auto or manual mode
              
              // Validate mandatory fields
              validateMandatoryFields();
            });
          }
          
          var latEl = document.querySelector('input[name="lat"]');
          var lngEl = document.querySelector('input[name="lng"]');
          var locEl = document.querySelector('input[name="location_name"]');
          var hint = document.getElementById('geoHint');
          
          // Show initial location toast
          showToast('Getting location...', 'info');
          setGeoBoxState('loading', 'Getting your location…');
          
          if (navigator.geolocation && latEl && lngEl){
            navigator.geolocation.getCurrentPosition(function(pos){
              try {
                var lat = String(pos.coords.latitude || '');
                var lng = String(pos.coords.longitude || '');
                latEl.value = lat;
                lngEl.value = lng;
                hasLocation = true;
                showToast('Location captured successfully', 'success');
                
                // Validate mandatory fields after location capture
                validateMandatoryFields();
                
                if (hint) {
                  resolveAddress(lat, lng, hint, locEl);
                }
              } catch(e){}
            }, function(){ 
              try { 
                showToast('Location access denied', 'error');
                setGeoBoxState('error', 'Location access denied — enable GPS in browser settings');
                // Even if location fails, start camera after a delay
                setTimeout(function(){
                  if (!cameraStarted) {
                    cameraStarted = true;
                    startCameraAfterLocation();
                  }
                }, 2000);
              }
              catch(e){} 
            }, { enableHighAccuracy:true, timeout:8000, maximumAge:0 });
          } else { 
            showToast('Location not available on this device', 'warning');
            setGeoBoxState('error', 'Location not available on this device');
            // Start camera even if location not available
            setTimeout(function(){
              if (!cameraStarted) {
                cameraStarted = true;
                startCameraAfterLocation();
              }
            }, 1000);
          }
        } catch(e){}
      });
    })();
  </script>
</div>
<?php $this->load->view('partials/footer'); ?>
