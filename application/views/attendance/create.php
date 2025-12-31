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
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Toast Container -->
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
    <div id="locationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <i class="bi bi-geo-alt-fill text-primary me-2"></i>
        <strong class="me-auto">Location Status</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="toastMessage">
        Getting location...
      </div>
    </div>
    <div id="attendanceToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="toast-header">
        <i class="bi bi-info-circle-fill text-info me-2"></i>
        <strong class="me-auto">Attendance Status</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="attendanceToastMessage">
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
      <form method="post" enctype="multipart/form-data" id="attendanceForm">
        <input type="hidden" name="lat" value="" />
        <input type="hidden" name="lng" value="" />
        <input type="hidden" name="location_name" value="" />
        <input type="hidden" name="face_required" id="faceRequired" value="0" />
        <input type="hidden" name="face_descriptor" id="faceDescriptor" value="" />
        
        <!-- Action Selection -->
        <div class="row mb-3">
          <div class="col-12">
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="action" id="actionIn" value="in" checked>
              <label class="btn btn-outline-success" for="actionIn">
                <i class="bi bi-box-arrow-in-right"></i> Check IN
              </label>
              
              <input type="radio" class="btn-check" name="action" id="actionOut" value="out">
              <label class="btn btn-outline-danger" for="actionOut">
                <i class="bi bi-box-arrow-right"></i> Check OUT
              </label>
            </div>
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
        <div class="row mb-4">
          <div class="col-12 col-md-8 col-lg-6 mx-auto">
            <label class="form-label fw-semibold">
              <i class="bi bi-camera"></i> Face Verification
            </label>
            <div class="position-relative">
              <video id="attFaceVideo" class="w-100 rounded border shadow-sm" 
                     autoplay muted playsinline style="height: 240px; background: #000; object-fit: cover;"></video>
              <div class="position-absolute top-50 start-50 translate-middle text-white text-center" id="cameraLoader">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <div class="small mt-1">Starting camera...</div>
              </div>
            </div>
            <canvas id="attFaceCanvas" class="w-100 rounded border shadow-sm mt-2" style="height: 240px; display: none; object-fit: cover;"></canvas>
            <div class="small mt-2 text-center" id="attFaceStatus"></div>
            <button type="button" class="btn btn-primary btn-lg w-100 mt-3 fw-semibold" id="btnAttFaceVerify" disabled>
              <i class="bi bi-camera-fill me-2"></i> Capture Face
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="row">
          <div class="col-12">
            <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit" id="submitBtn" disabled>
              <i class="bi bi-check-circle"></i> Mark Attendance
            </button>
            <div class="small text-muted mt-2" id="validationStatus">
              <i class="bi bi-info-circle"></i> Please complete all mandatory fields: Location, Face Verification
            </div>
          </div>
        </div>
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
        
        // Check existing attendance status and show toast
        <?php if(isset($attendance_status) && ($attendance_status['has_checkin'] || $attendance_status['has_checkout'])): ?>
        try {
          var attendanceToast = new bootstrap.Toast(document.getElementById('attendanceToast'));
          var toastMsg = document.getElementById('attendanceToastMessage');
          var msg = '';
          <?php if($attendance_status['has_checkin'] && $attendance_status['has_checkout']): ?>
            msg = 'You have already checked in and checked out today.<br>Check-in: <?php echo htmlspecialchars($attendance_status['checkin_time']); ?><br>Check-out: <?php echo htmlspecialchars($attendance_status['checkout_time']); ?>';
          <?php elseif($attendance_status['has_checkin']): ?>
            msg = 'You have already checked in today at <?php echo htmlspecialchars($attendance_status['checkin_time']); ?>. You can now check out.';
          <?php endif; ?>
          if (toastMsg && msg) {
            toastMsg.innerHTML = msg;
            attendanceToast.show();
          }
        } catch(e){}
        <?php endif; ?>
        
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
          
          // Function to validate mandatory fields and enable/disable submit button
          function validateMandatoryFields() {
            var latEl = document.querySelector('input[name="lat"]');
            var lngEl = document.querySelector('input[name="lng"]');
            var lat = latEl ? latEl.value : '';
            var lng = lngEl ? lngEl.value : '';
            var faceDesc = faceDescEl ? faceDescEl.value : '';
            
            var locationValid = lat && lng && lat.trim() !== '' && lng.trim() !== '';
            var faceValid = faceDesc && faceDesc.trim() !== '';
            
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
                if (!faceValid) missingFields.push('Face Verification');
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
              var faceValid = faceDesc && faceDesc.trim() !== '';
              
              if (!locationValid || !faceValid) {
                e.preventDefault();
                var missingFields = [];
                if (!locationValid) missingFields.push('Location');
                if (!faceValid) missingFields.push('Face Verification');
                showToast('Please complete all mandatory fields: ' + missingFields.join(', '), 'error');
                return false;
              }
              
              // Ensure face_required is set to 1
              if (faceReqEl) faceReqEl.value = '1';
              
              return true;
            });
          }
          
          function showToast(message, type = 'info') {
            var toastEl = document.getElementById('locationToast');
            var toastMessage = document.getElementById('toastMessage');
            var toastHeader = toastEl.querySelector('.toast-header i');
            
            if (!locationToast) {
              locationToast = new bootstrap.Toast(toastEl);
            }
            
            toastMessage.textContent = message;
            
            // Update icon and color based on type
            toastHeader.className = 'bi me-2';
            switch(type) {
              case 'success':
                toastHeader.classList.add('bi-check-circle-fill', 'text-success');
                break;
              case 'error':
                toastHeader.classList.add('bi-exclamation-triangle-fill', 'text-danger');
                break;
              case 'warning':
                toastHeader.classList.add('bi-exclamation-circle-fill', 'text-warning');
                break;
              default:
                toastHeader.classList.add('bi-geo-alt-fill', 'text-primary');
            }
            
            locationToast.show();
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
              setTimeout(function(){
                hint.textContent = 'Location captured successfully';
                hint.classList.remove('text-primary');
                hint.classList.add('text-success');
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
              btnVerify.disabled = false;
              
              // Hide camera loader
              if (cameraLoader) {
                cameraLoader.style.display = 'none';
              }
              
              if (auto) {
                var seconds = 3;
                setFaceStatus('Auto capture in ' + seconds + 's...', false);
                var countdownId = setInterval(function(){
                  seconds--;
                  if (seconds <= 0) {
                    clearInterval(countdownId);
                    if (faceDescEl && faceDescEl.value) { 
                      setFaceStatus('Face already captured', false);
                      return; 
                    }
                    if (!hasLocation) {
                      setFaceStatus('Location required first', true);
                      return;
                    }
                    captureFace(true);
                  } else {
                    setFaceStatus('Auto capture in ' + seconds + 's...', false);
                  }
                }, 1000);
              } else {
                setFaceStatus('Align face and capture', false);
              }
            } catch(e){ 
              setFaceStatus('Camera access denied', true);
              if (cameraLoader) {
                cameraLoader.style.display = 'block';
              }
            }
          }

          async function captureFace(autoSubmit){
            if (!modelsLoaded){ await ensureModels(); }
            if (!video || video.readyState < 2){ setFaceStatus('Camera not ready', true); return; }
            try {
              var opts = new faceapi.TinyFaceDetectorOptions();
              var det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
              if (!det || !det.descriptor){ setFaceStatus('No face detected', true); return; }
              
              var ctx = canvas.getContext('2d');
              canvas.width = video.videoWidth || 320;
              canvas.height = video.videoHeight || 240;
              ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
              
              // Show canvas, hide video
              video.style.display = 'none';
              canvas.style.display = 'block';
              
              var descArr = Array.prototype.slice.call(det.descriptor);
              if (faceDescEl) faceDescEl.value = JSON.stringify(descArr);
              if (faceReqEl) faceReqEl.value = '1';
              hasFaceCapture = true;
              setFaceStatus('Face captured successfully', false);
              
              // Validate mandatory fields after face capture
              validateMandatoryFields();
              
              // Stop camera
              try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; } } catch(e){}
              
              // Auto submit disabled - user must click submit button after validation
              if (autoSubmit) {
                // Just validate, don't auto-submit
                validateMandatoryFields();
              }
            } catch(e){ setFaceStatus('Capture failed: '+e.message, true); }
          }
          
          function startCameraAfterLocation() {
            // Start camera now that location is captured
            showToast('Starting camera for face verification...', 'info');
            if (btnVerify) {
              btnVerify.disabled = false;
              startCam(true); // Start with auto-capture
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
