<?php $this->load->view('partials/header', ['title' => 'Mark Attendance']); ?>
<div class="container-fluid px-3 px-md-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h5 mb-0 fw-bold">Mark Attendance</h1>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-primary text-white" id="liveClock">--:--:--</span>
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('attendance'); ?>">
        <i class="bi bi-arrow-left"></i> Back
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
        toastEl.innerHTML = '<div class="d-flex"><div class="toast-body"><i class="bi bi-check-circle-fill me-2"></i><strong>Success!</strong> <?php echo htmlspecialchars($this->session->flashdata('success')); ?></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        document.querySelector('.toast-container').appendChild(toastEl);
        var toast = new bootstrap.Toast(toastEl);
        toast.show();
        
        // Redirect to attendance index page after successful submission
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
        var errorMsg = '<?php echo htmlspecialchars($this->session->flashdata('error')); ?>';
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

  <div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
      <form method="post" enctype="multipart/form-data" id="attendanceForm">
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
                msg = '<i class="bi bi-info-circle-fill me-2"></i><strong>Today\'s Attendance Status:</strong><div class="mt-2"><div><i class="bi bi-check-circle"></i> Check-in: <strong><?php echo htmlspecialchars($attendance_status['checkin_time']); ?></strong></div><div><i class="bi bi-check-circle"></i> Check-out: <strong><?php echo htmlspecialchars($attendance_status['checkout_time']); ?></strong></div><div class="mt-2 small">You have already completed attendance for today.</div></div>';
              <?php elseif($attendance_status['has_checkin']): ?>
                msg = '<i class="bi bi-info-circle-fill me-2"></i><strong>Today\'s Attendance Status:</strong><div class="mt-2"><div><i class="bi bi-check-circle"></i> Check-in: <strong><?php echo htmlspecialchars($attendance_status['checkin_time']); ?></strong></div><div class="mt-2 small">You have already checked in today. You can now check out.</div></div>';
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

        <!-- Action Selection -->
        <div class="row mb-3">
          <div class="col-12">
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="action" id="actionIn" value="in" 
                     <?php echo (isset($attendance_status) && $attendance_status['has_checkin'] && !$attendance_status['has_checkout']) ? '' : 'checked'; ?>
                     <?php echo (isset($attendance_status) && $attendance_status['has_checkin'] && $attendance_status['has_checkout']) ? 'disabled' : ''; ?>>
              <label class="btn btn-outline-success" for="actionIn">
                <i class="bi bi-box-arrow-in-right"></i> Check IN
              </label>
              
              <input type="radio" class="btn-check" name="action" id="actionOut" value="out"
                     <?php echo (isset($attendance_status) && $attendance_status['has_checkin'] && !$attendance_status['has_checkout']) ? 'checked' : ''; ?>
                     <?php echo (isset($attendance_status) && !$attendance_status['has_checkin']) ? 'disabled' : ''; ?>
                     <?php echo (isset($attendance_status) && $attendance_status['has_checkin'] && $attendance_status['has_checkout']) ? 'disabled' : ''; ?>>
              <label class="btn btn-outline-danger" for="actionOut">
                <i class="bi bi-box-arrow-right"></i> Check OUT
              </label>
            </div>
            <?php if(isset($attendance_status) && $attendance_status['has_checkin'] && $attendance_status['has_checkout']): ?>
            <div class="text-center mt-2">
              <small class="text-muted"><i class="bi bi-info-circle"></i> Attendance already completed for today</small>
            </div>
            <?php elseif(isset($attendance_status) && !$attendance_status['has_checkin']): ?>
            <div class="text-center mt-2">
              <small class="text-muted"><i class="bi bi-info-circle"></i> Please check in first</small>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Notes and Location -->
        <div class="row mb-3">
          <div class="col-12">
            <label class="form-label fw-semibold">
              <i class="bi bi-chat-text"></i> Notes
            </label>
            <textarea name="notes" class="form-control form-control-sm" rows="2" 
                      placeholder="Add any notes..."></textarea>
          </div>
        </div>

        <!-- Location Status -->
        <div class="row mb-3">
          <div class="col-12">
            <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
              <i class="bi bi-geo-alt text-primary"></i>
              <small class="text-muted" id="geoHint">Getting location...</small>
            </div>
          </div>
        </div>

        <!-- Face Verification -->
        <?php $face_verification_enabled = isset($face_verification_enabled) ? $face_verification_enabled : true; ?>
        <?php $auto_submit_enabled = isset($auto_submit_enabled) ? $auto_submit_enabled : false; ?>
        <?php $auto_capture_enabled = isset($auto_capture_enabled) ? $auto_capture_enabled : false; ?>
        <div class="row mb-4" id="faceVerificationSection" style="<?php echo $face_verification_enabled ? '' : 'display: none;'; ?>">
          <div class="col-12 col-md-8 col-lg-6 mx-auto">
            <label class="form-label fw-semibold">
              <i class="bi bi-camera"></i> Face Verification <?php echo $face_verification_enabled ? '<span class="text-danger">*</span>' : ''; ?>
            </label>
            <?php if($auto_capture_enabled): ?>
            <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
              <i class="bi bi-info-circle-fill me-2"></i>
              <strong>Auto Capture Enabled:</strong> Your face will be captured automatically when detected.
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            <?php if($auto_submit_enabled): ?>
            <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
              <i class="bi bi-info-circle-fill me-2"></i>
              <strong>Auto Submit Enabled:</strong> Your attendance will be automatically submitted after successful face capture.
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            <div class="position-relative">
              <video id="attFaceVideo" class="w-100 rounded border shadow-sm" 
                     autoplay muted playsinline style="height: 240px; background: #000; object-fit: cover;"></video>
              <div class="position-absolute top-50 start-50 translate-middle text-white text-center" id="cameraLoader">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <div class="small mt-1">Starting camera...</div>
              </div>
            </div>
            <canvas id="attFaceCanvas" class="w-100 rounded border shadow-sm mt-2" style="height: 240px; display: none; object-fit: cover; background: #000;"></canvas>
            <div class="small mt-2 text-center" id="attFaceStatus"></div>
            <?php if(!$auto_capture_enabled): ?>
            <button type="button" class="btn btn-secondary btn-lg w-100 mt-3 fw-semibold" id="btnAttFaceVerify" disabled>
              <i class="bi bi-camera-fill me-2"></i> Capture Face
            </button>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-secondary btn-lg w-100 mt-2 fw-semibold" id="btnRetakeFace" style="display: none;">
              <i class="bi bi-arrow-clockwise me-2"></i> Retake Face
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <?php if(!$auto_submit_enabled): ?>
        <div class="row">
          <div class="col-12">
            <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit" id="submitBtn" disabled>
              <i class="bi bi-check-circle"></i> Mark Attendance
            </button>
            <div class="small text-muted mt-2" id="validationStatus">
              <i class="bi bi-info-circle"></i> Please complete all mandatory fields: Location<?php echo $face_verification_enabled ? ', Face Verification' : ''; ?>
            </div>
          </div>
        </div>
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
          var hasLocation = false;
          var locationCaptured = false;
          var cameraStarted = false;
          var locationToast = null;
          var hasFaceCapture = false;
          
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
          var faceDetectionInterval = null; // For continuous face detection
          var isFaceDetected = false; // Track if face is currently detected
          
          // Check if face verification is required from PHP setting
          var faceVerificationRequired = <?php echo $face_verification_enabled ? 'true' : 'false'; ?>;
          
          // Check if auto submit is enabled from PHP setting
          var autoSubmitEnabled = <?php echo isset($auto_submit_enabled) && $auto_submit_enabled ? 'true' : 'false'; ?>;
          
          // Function to validate mandatory fields and enable/disable submit button
          function validateMandatoryFields() {
            var latEl = document.querySelector('input[name="lat"]');
            var lngEl = document.querySelector('input[name="lng"]');
            var lat = latEl ? latEl.value : '';
            var lng = lngEl ? lngEl.value : '';
            var faceDesc = faceDescEl ? faceDescEl.value : '';
            
            var locationValid = lat && lng && lat.trim() !== '' && lng.trim() !== '';
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
                if (!locationValid) missingFields.push('Location');
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
          
          // Track shown toasts to prevent duplicates (declared once at top level)
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
              
              var locationValid = lat && lng && lat.trim() !== '' && lng.trim() !== '';
              var faceValid = !faceVerificationRequired || (faceDesc && faceDesc.trim() !== '');
              
              if (!locationValid || !faceValid) {
                e.preventDefault();
                var missingFields = [];
                if (!locationValid) missingFields.push('Location');
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
          
          // Use the same shownToasts object declared above
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
          
          function resolveAddress(lat, lng, hint, locEl){
            try {
              if (!hint) return;
              if (!lat || !lng) return;
              showToast('Location captured, resolving address...', 'info');
              hint.textContent = 'Location captured, resolving address...';
              hint.classList.remove('text-muted');
              hint.classList.add('text-primary');
              
              // Use a CORS proxy or skip address resolution due to CORS issues
              // For now, we'll just mark location as captured without address resolution
              // Note: locationCaptured is already set in geolocation success callback
              // Camera is already started in geolocation success callback
              setTimeout(function(){
                hint.textContent = 'Location captured successfully';
                hint.classList.remove('text-primary');
                hint.classList.add('text-success');
                // Validate mandatory fields after location capture
                validateMandatoryFields();
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
            // Support HTML content in messages
            statusEl.innerHTML = msg || '';
            statusEl.classList.toggle('text-danger', !!isError);
            statusEl.classList.toggle('text-success', !isError && msg && msg.indexOf('success') > -1);
            statusEl.classList.toggle('text-warning', !isError && msg && msg.indexOf('warning') > -1);
            statusEl.classList.toggle('text-info', !isError && msg && msg.indexOf('info') > -1);
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

          // Continuous face detection function
          async function detectFaceContinuously(){
            if (!modelsLoaded || !video || video.readyState < 2 || hasFaceCapture) {
              return;
            }
            
            // Check if auto-capture is enabled
            var autoCaptureEnabled = <?php echo isset($auto_capture_enabled) && $auto_capture_enabled ? 'true' : 'false'; ?>;
            
            try {
              var opts = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.6 });
              var detection = await faceapi.detectSingleFace(video, opts);
              
              // Check if face is actually detected with valid score and bounding box
              var faceCurrentlyDetected = false;
              if (detection && typeof detection.score === 'number' && detection.score > 0.6) {
                // Additional validation: check if bounding box exists and has valid dimensions
                if (detection.box && typeof detection.box.width === 'number' && typeof detection.box.height === 'number') {
                  if (detection.box.width > 0 && detection.box.height > 0) {
                    faceCurrentlyDetected = true;
                  }
                }
              }
              
              if (faceCurrentlyDetected) {
                // Face detected - update status and button state
                isFaceDetected = true;
                
                // Only enable button if it exists (not hidden when auto-capture is enabled)
                if (btnVerify) {
                  btnVerify.disabled = false;
                  btnVerify.classList.remove('btn-secondary');
                  btnVerify.classList.add('btn-primary');
                }
                
                // When auto-capture is enabled, only show "Face detected! You can capture now."
                // The auto-capture logic will handle the actual capture and auto-submit
                if (autoCaptureEnabled) {
                  setFaceStatus('<span class="text-success"><i class="bi bi-check-circle-fill"></i> Face detected! You can capture now.</span>', false);
                } else {
                  // Manual mode - show the same message
                  setFaceStatus('<span class="text-success"><i class="bi bi-check-circle-fill"></i> Face detected! You can capture now.</span>', false);
                }
              } else {
                // No face detected
                isFaceDetected = false;
                
                // Only disable button if it exists (not hidden when auto-capture is enabled)
                if (btnVerify) {
                  btnVerify.disabled = true;
                  btnVerify.classList.remove('btn-primary');
                  btnVerify.classList.add('btn-secondary');
                }
                
                // When auto-capture is enabled, don't show "Detecting face..." message
                // Only show it in manual mode
                if (!autoCaptureEnabled) {
                  setFaceStatus('<span class="text-info"><i class="bi bi-search"></i> Detecting face... Please position your face in front of the camera</span>', false);
                }
                // When auto-capture is enabled and no face detected, don't show any message
                // (or show a minimal one if needed)
              }
            } catch(e) {
              // Silently handle detection errors - don't show error during continuous detection
              console.error('Face detection error:', e);
              // Reset face detected state on error
              isFaceDetected = false;
              
              // Keep showing detecting message even on error (only in manual mode)
              if (!hasFaceCapture && !autoCaptureEnabled) {
                setFaceStatus('<span class="text-info"><i class="bi bi-search"></i> Detecting face... Please position your face in front of the camera</span>', false);
              }
              
              // Disable button on error
              if (btnVerify) {
                btnVerify.disabled = true;
                btnVerify.classList.remove('btn-primary');
                btnVerify.classList.add('btn-secondary');
              }
            }
          }

          async function startCam(auto){
            await ensureModels();
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
              stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                  facingMode:'user',
                  width: { ideal: 640 },
                  height: { ideal: 480 }
                }, 
                audio:false 
              });
              video.srcObject = stream;
              
              // Initially disable capture button until face is detected
              if (btnVerify) {
                btnVerify.disabled = true;
                btnVerify.classList.remove('btn-primary');
                btnVerify.classList.add('btn-secondary');
              }
              
              // Hide camera loader
              if (cameraLoader) {
                cameraLoader.style.display = 'none';
              }
              
              // Wait for video to be ready before starting face detection
              function startFaceDetection() {
                // Start continuous face detection
                if (!faceDetectionInterval && video && video.readyState >= 2) {
                  faceDetectionInterval = setInterval(detectFaceContinuously, 300); // Check every 300ms
                  // Only show initial message in manual mode (not auto-capture)
                  if (!auto) {
                    setFaceStatus('<span class="text-info"><i class="bi bi-search"></i> Detecting face... Please position your face in front of the camera</span>', false);
                  }
                  // In auto-capture mode, wait for face detection to show message
                }
              }
              
              // Check if video is already loaded
              if (video.readyState >= 2) {
                startFaceDetection();
              } else {
                // Wait for metadata to load
                video.addEventListener('loadedmetadata', startFaceDetection, { once: true });
              }
              
              if (auto) {
                // For auto-capture, wait for face detection first
                var autoCaptureTriggered = false; // Flag to prevent multiple captures
                var autoCaptureCheck = setInterval(function(){
                  if (isFaceDetected && locationCaptured && !autoCaptureTriggered) {
                    // Validate all requirements before auto-capturing
                    if (faceDescEl && faceDescEl.value) { 
                      clearInterval(autoCaptureCheck);
                      setFaceStatus('Face already captured', false);
                      return; 
                    }
                    if (!locationCaptured) {
                      setFaceStatus('Location required first', true);
                      return;
                    }
                    
                    // Set flag to prevent multiple captures
                    autoCaptureTriggered = true;
                    clearInterval(autoCaptureCheck);
                    
                    // All validations passed - directly capture without countdown
                    setFaceStatus('<span class="text-success"><i class="bi bi-check-circle"></i> Face detected! Capturing...</span>', false);
                    
                    // Stop continuous detection before capturing
                    if (faceDetectionInterval) {
                      clearInterval(faceDetectionInterval);
                      faceDetectionInterval = null;
                    }
                    
                    // Directly capture face (no countdown)
                    captureFace(autoSubmitEnabled);
                  }
                }, 500);
              } else {
                setFaceStatus('<span class="text-info"><i class="bi bi-search"></i> Detecting face... Please position your face in front of the camera</span>', false);
              }
            } catch(e){ 
              setFaceStatus('Camera access denied', true);
              if (cameraLoader) {
                cameraLoader.style.display = 'block';
              }
            }
          }

          async function captureFace(autoSubmit){
            // Validate prerequisites
            if (!modelsLoaded){ 
              try {
                await ensureModels();
              } catch(e) {
                setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Failed to load face models. Please refresh the page.</span>', true);
                return;
              }
            }
            
            if (!video || video.readyState < 2){ 
              setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Camera not ready. Please wait...</span>', true); 
              return; 
            }
            
            // Check if face already captured
            if (hasFaceCapture && faceDescEl && faceDescEl.value) {
              setFaceStatus('<span class="text-info"><i class="bi bi-info-circle"></i> Face already captured</span>', false);
              return;
            }
            
            // Stop continuous face detection
            if (faceDetectionInterval) {
              clearInterval(faceDetectionInterval);
              faceDetectionInterval = null;
            }
            
            try {
              var opts = new faceapi.TinyFaceDetectorOptions();
              var det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
              if (!det || !det.descriptor){ 
                // Restart continuous detection first, then show appropriate message
                var autoCaptureEnabled = <?php echo isset($auto_capture_enabled) && $auto_capture_enabled ? 'true' : 'false'; ?>;
                if (!autoCaptureEnabled && video && video.readyState >= 2 && !hasFaceCapture) {
                  // Restart continuous detection for manual mode
                  faceDetectionInterval = setInterval(detectFaceContinuously, 300);
                  setFaceStatus('<span class="text-info"><i class="bi bi-search"></i> Detecting face... Please position your face in front of the camera</span>', false);
                } else if (autoCaptureEnabled) {
                  // For auto-capture mode, just show detecting message
                  faceDetectionInterval = setInterval(detectFaceContinuously, 300);
                  setFaceStatus('<span class="text-info"><i class="bi bi-search"></i> Detecting face... Please position your face in front of the camera</span>', false);
                } else {
                  // Only show error if we can't restart detection
                  setFaceStatus('<span class="text-warning"><i class="bi bi-exclamation-circle"></i> No face detected. Please position your face in front of the camera.</span>', false);
                }
                return; 
              }
              
              // Validate required elements
              if (!canvas) {
                setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Canvas element not found</span>', true);
                return;
              }
              
              // Get actual video dimensions
              var videoWidth = video.videoWidth || 640;
              var videoHeight = video.videoHeight || 480;
              
              if (videoWidth <= 0 || videoHeight <= 0) {
                setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Invalid video dimensions</span>', true);
                return;
              }
              
              // Set canvas dimensions to match video
              canvas.width = videoWidth;
              canvas.height = videoHeight;
              
              // Get canvas context and clear it first
              var ctx = canvas.getContext('2d');
              if (!ctx) {
                setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Failed to get canvas context</span>', true);
                return;
              }
              
              ctx.clearRect(0, 0, canvas.width, canvas.height);
              
              // Draw the video frame to canvas
              try {
                ctx.drawImage(video, 0, 0, videoWidth, videoHeight);
              } catch(e) {
                setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Failed to draw video frame: ' + e.message + '</span>', true);
                return;
              }
              
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
                setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Failed to capture image. Please try again.</span>', true);
                // Restart continuous detection if auto-capture is disabled
                var autoCaptureEnabled = <?php echo isset($auto_capture_enabled) && $auto_capture_enabled ? 'true' : 'false'; ?>;
                if (!autoCaptureEnabled && video && video.readyState >= 2 && !hasFaceCapture) {
                  faceDetectionInterval = setInterval(detectFaceContinuously, 300);
                }
                return;
              }
              
              // Show canvas, hide video
              if (video) video.style.display = 'none';
              if (canvas) {
                canvas.style.display = 'block';
                // Ensure canvas is visible with proper styling
                canvas.style.background = '#000';
                canvas.style.objectFit = 'cover';
              }
              
              // Hide capture button (if exists), show retake button
              if (btnVerify) btnVerify.style.display = 'none';
              var retakeBtn = document.getElementById('btnRetakeFace');
              if (retakeBtn) retakeBtn.style.display = 'block';
              
              // Store face descriptor
              try {
                var descArr = Array.prototype.slice.call(det.descriptor);
                if (faceDescEl) faceDescEl.value = JSON.stringify(descArr);
                if (faceReqEl) faceReqEl.value = '1';
                hasFaceCapture = true;
                setFaceStatus('<span class="text-success"><i class="bi bi-check-circle-fill"></i> Face captured successfully!</span>', false);
              } catch(e) {
                setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Failed to process face descriptor: ' + e.message + '</span>', true);
                return;
              }
              
              // Validate mandatory fields after face capture
              validateMandatoryFields();
              
              // Stop camera
              try { 
                if (stream){ 
                  stream.getTracks().forEach(function(t){ 
                    try { t.stop(); } catch(e){} 
                  }); 
                  stream = null; 
                } 
              } catch(e){
                console.error('Error stopping camera stream:', e);
              }
              
            } catch(e) {
              // Handle any unexpected errors during face capture
              console.error('Error in captureFace:', e);
              setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> An error occurred during face capture: ' + (e.message || 'Unknown error') + '</span>', true);
              
              // Restart continuous detection if auto-capture is disabled
              var autoCaptureEnabled = <?php echo isset($auto_capture_enabled) && $auto_capture_enabled ? 'true' : 'false'; ?>;
              if (!autoCaptureEnabled && video && video.readyState >= 2 && !hasFaceCapture) {
                faceDetectionInterval = setInterval(detectFaceContinuously, 300);
                setFaceStatus('<span class="text-info"><i class="bi bi-search"></i> Detecting face... Please try again</span>', false);
              }
              return;
            }
            
            // Auto submit if enabled (autoSubmit parameter indicates if auto-submit should happen)
            if (autoSubmitEnabled && autoSubmit) {
                // Show submitting message
                setFaceStatus('<span class="text-info"><i class="bi bi-hourglass-split"></i> Submitting attendance automatically...</span>', false);
                
                // Small delay to show the message, then submit
                setTimeout(function() {
                  if (attendanceForm) {
                    // Double check all mandatory fields are complete
                    var latEl = document.querySelector('input[name="lat"]');
                    var lngEl = document.querySelector('input[name="lng"]');
                    var lat = latEl ? latEl.value : '';
                    var lng = lngEl ? lngEl.value : '';
                    var faceDesc = faceDescEl ? faceDescEl.value : '';
                    
                    var locationValid = lat && lng && lat.trim() !== '' && lng.trim() !== '';
                    var faceValid = !faceVerificationRequired || (faceDesc && faceDesc.trim() !== '');
                    
                    if (locationValid && faceValid) {
                      // All valid, submit the form
                      showCustomToast('<strong>Auto-submitting attendance...</strong><div class="small mt-1">Please wait while we process your attendance.</div>', 'info', 2000);
                      attendanceForm.submit();
                    } else {
                      // Validation failed, show error
                      setFaceStatus('<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Cannot auto-submit: Missing required fields</span>', true);
                      showCustomToast('Cannot auto-submit: Please complete all mandatory fields', 'error', 5000);
                      // Re-enable submit button for manual submission
                      if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('btn-secondary');
                        submitBtn.classList.add('btn-primary');
                      }
                    }
                  }
                }, 1000);
              } else {
                // Auto-submit disabled or not triggered - just validate
                validateMandatoryFields();
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
            
            // Check if auto capture is enabled from settings
            var autoCaptureEnabled = <?php echo isset($auto_capture_enabled) && $auto_capture_enabled ? 'true' : 'false'; ?>;
            
            // Enable button only if it exists (not hidden when auto-capture is enabled)
            if (btnVerify) {
              btnVerify.disabled = false;
            }
            
            // Start camera regardless of button existence (for auto-capture mode)
            startCam(autoCaptureEnabled); // Use setting to determine auto or manual capture
          }
          
          // Move event listener to main scope
          if (btnVerify){
            btnVerify.addEventListener('click', function(ev){ 
              ev.preventDefault(); 
              // Pass autoSubmitEnabled flag to captureFace
              // If auto-submit is enabled, it will auto-submit after capture
              captureFace(autoSubmitEnabled); 
            });
            window.addEventListener('beforeunload', function(){ 
              try { 
                // Stop continuous face detection
                if (faceDetectionInterval) {
                  clearInterval(faceDetectionInterval);
                  faceDetectionInterval = null;
                }
                // Stop camera stream
                if (stream){ 
                  stream.getTracks().forEach(function(t){ t.stop(); }); 
                  stream = null;
                } 
              } catch(e){}
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
              isFaceDetected = false;
              
              // Hide canvas, show video
              if (canvas) canvas.style.display = 'none';
              if (video) video.style.display = 'block';
              
              // Show capture button, hide retake button
              if (btnVerify) {
                btnVerify.style.display = 'block';
                btnVerify.disabled = true; // Disabled until face is detected
                btnVerify.classList.remove('btn-primary');
                btnVerify.classList.add('btn-secondary');
              }
              retakeBtn.style.display = 'none';
              
              // Clear canvas
              if (canvas) {
                var ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
              }
              
              // Check if auto capture is enabled
              var autoCaptureEnabled = <?php echo isset($auto_capture_enabled) && $auto_capture_enabled ? 'true' : 'false'; ?>;
              
              // Restart continuous face detection if video is ready
              if (video && video.readyState >= 2) {
                if (!faceDetectionInterval) {
                  faceDetectionInterval = setInterval(detectFaceContinuously, 300);
                }
                // Only show detecting message in manual mode (not auto-capture)
                if (!autoCaptureEnabled) {
                  setFaceStatus('<span class="text-info"><i class="bi bi-search"></i> Detecting face... Please position your face in front of the camera</span>', false);
                }
                
                // If auto-capture is enabled, restart auto-capture check
                if (autoCaptureEnabled && locationCaptured) {
                  var autoCaptureTriggered = false;
                  var autoCaptureCheck = setInterval(function(){
                    if (isFaceDetected && locationCaptured && !autoCaptureTriggered) {
                      // Validate all requirements before auto-capturing
                      if (faceDescEl && faceDescEl.value) { 
                        clearInterval(autoCaptureCheck);
                        setFaceStatus('Face already captured', false);
                        return; 
                      }
                      if (!locationCaptured) {
                        setFaceStatus('Location required first', true);
                        return;
                      }
                      
                      // Set flag to prevent multiple captures
                      autoCaptureTriggered = true;
                      clearInterval(autoCaptureCheck);
                      
                      // All validations passed - directly capture without countdown
                      setFaceStatus('<span class="text-success"><i class="bi bi-check-circle"></i> Face detected! Capturing...</span>', false);
                      
                      // Stop continuous detection before capturing
                      if (faceDetectionInterval) {
                        clearInterval(faceDetectionInterval);
                        faceDetectionInterval = null;
                      }
                      
                      // Directly capture face (no countdown)
                      captureFace(autoSubmitEnabled);
                    }
                  }, 500);
                }
              } else {
                // Restart camera with auto-capture setting
                setFaceStatus('Restarting camera...', false);
                startCam(autoCaptureEnabled);
              }
              
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
          
          // Initialize location status
          if (hint) {
            hint.textContent = 'Getting location...';
            hint.classList.remove('text-muted');
            hint.classList.add('text-primary');
          }
          
          if (navigator.geolocation && latEl && lngEl){
            navigator.geolocation.getCurrentPosition(function(pos){
              try {
                var lat = String(pos.coords.latitude || '');
                var lng = String(pos.coords.longitude || '');
                latEl.value = lat;
                lngEl.value = lng;
                hasLocation = true;
                locationCaptured = true; // Set locationCaptured immediately
                showToast('Location captured successfully', 'success');
                
                // Validate mandatory fields after location capture
                validateMandatoryFields();
                
                // Start camera immediately after location is captured (for auto-capture mode)
                if (!cameraStarted && locationCaptured) {
                  cameraStarted = true;
                  startCameraAfterLocation();
                }
                
                if (hint) {
                  resolveAddress(lat, lng, hint, locEl);
                }
              } catch(e){
                console.error('Error processing location:', e);
              }
            }, function(){ 
              try { 
                showToast('Location access denied', 'error');
                if (hint) {
                  hint.textContent = 'Location access denied';
                  hint.classList.remove('text-muted');
                  hint.classList.add('text-danger');
                }
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
            if (hint) {
              hint.textContent = 'Location not available';
              hint.classList.remove('text-muted');
              hint.classList.add('text-secondary');
            }
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
<?php $this->load->view('partials/footer'); ?>
