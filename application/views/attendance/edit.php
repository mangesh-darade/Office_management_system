<?php $this->load->view('partials/header', ['title' => 'Edit Attendance']); ?>
  <div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div class="d-flex align-items-center gap-3 mb-2 mb-sm-0">
      <h1 class="h4 mb-0">Edit Attendance</h1>
      <span class="badge text-bg-light" id="liveClock">--:--:--</span>
    </div>
    <a class="btn btn-secondary" href="<?php echo site_url('attendance'); ?>">Back</a>
  </div>

  <?php if($this->session->flashdata('success')): ?>
    <div class="alert alert-success fade show" role="alert"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    <div class="alert alert-danger fade show" role="alert"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm fade-in">
    <div class="card-header bg-light">
      <h5 class="card-title mb-0">Attendance Details</h5>
    </div>
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" class="row g-3" id="attendanceEditForm">
        <input type="hidden" name="lat" value="" />
        <input type="hidden" name="lng" value="" />
        <input type="hidden" name="face_required" id="faceRequired" value="0" />
        <input type="hidden" name="face_descriptor" id="faceDescriptor" value="" />
        <?php
          $dateVal = isset($att->att_date) ? $att->att_date : (isset($att->date) ? $att->date : '');
          $inVal = isset($att->punch_in) ? $att->punch_in : (isset($att->check_in) ? $att->check_in : '');
          $outVal = isset($att->punch_out) ? $att->punch_out : (isset($att->check_out) ? $att->check_out : '');
          $inDisp = $inVal;
          $outDisp = $outVal;
          if ($inDisp && strpos($inDisp, ' ') !== false) { $inDisp = trim(explode(' ', $inDisp)[1]); }
          if ($outDisp && strpos($outDisp, ' ') !== false) { $outDisp = trim(explode(' ', $outDisp)[1]); }
        ?>
        <div class="col-12 col-md-6 col-lg-3">
          <label class="form-label">Date</label>
          <div class="form-control-plaintext"><?php echo esc_view($dateVal); ?></div>
        </div>
        <div class="col-6 col-md-3 col-lg-3">
          <label class="form-label">Check In</label>
          <div class="form-control-plaintext"><?php echo esc_view($inDisp); ?></div>
        </div>
        <div class="col-6 col-md-3 col-lg-3">
          <label class="form-label">Check Out</label>
          <div class="form-control-plaintext"><?php echo esc_view($outDisp); ?></div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
          <label class="form-label">Location</label>
          <div class="form-control-plaintext"><?php echo esc_view((isset($att->location_name) && $att->location_name !== '') ? $att->location_name : 'Not captured'); ?></div>
        </div>
        <div class="col-12">
          <label for="notes" class="form-label">Notes</label>
          <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Any notes..."><?php echo esc_view(isset($att->notes) ? $att->notes : ''); ?></textarea>
        </div>
        <div class="col-12">
          <label for="attachment" class="form-label">Attachment</label>
          <?php if(isset($att->attachment_path) && !empty($att->attachment_path)): ?>
            <div class="mb-2">
              <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url($att->attachment_path); ?>" target="_blank" title="View current file"><i class="bi bi-paperclip"></i> View Current File</a>
              <span class="text-muted small ms-2"><?php echo esc_view(basename($att->attachment_path)); ?></span>
            </div>
          <?php endif; ?>
          <input type="file" name="attachment" id="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
          <div class="form-text">Upload new file to replace current. Max 4MB. Allowed: JPG, PNG, PDF, DOC, DOCX</div>
        </div>
        <div class="col-12">
          <hr class="my-4">
          <div class="card border-light bg-light">
            <div class="card-body">
              <h6 class="card-title mb-3">
                <i class="bi bi-camera-video me-2"></i>Face Verification (Optional)
                <span class="badge bg-info ms-2">Enhanced Security</span>
              </h6>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="position-relative">
                    <video id="attFaceVideo" class="w-100 border rounded" autoplay muted playsinline style="max-height:220px; background:#000;"></video>
                    <div class="position-absolute top-0 start-0 m-2">
                      <span class="badge bg-danger" id="cameraStatus" style="display:none;">Camera Off</span>
                      <span class="badge bg-success" id="cameraActive" style="display:none;">Camera Active</span>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <canvas id="attFaceCanvas" class="w-100 border rounded" style="max-height:220px;"></canvas>
                  <div class="mt-2">
                    <div class="small text-muted" id="attFaceStatus">Initializing...</div>
                    <div class="progress mt-2" style="height: 4px;">
                      <div class="progress-bar" id="faceProgress" role="progressbar" style="width: 0%;"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mt-3 d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="btnAttFaceVerify" disabled>
                  <i class="bi bi-camera me-1"></i> Capture Face for Verification
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnRetakeFace" style="display:none;">
                  <i class="bi bi-arrow-clockwise me-1"></i> Retake
                </button>
              </div>
              <div class="alert alert-info mt-3 mb-0 small">
                <i class="bi bi-info-circle me-1"></i>
                Face verification adds an extra layer of security. Position your face clearly in the camera frame.
              </div>
            </div>
          </div>
        </div>
        <div class="col-12">
          <hr class="my-4">
          <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
            <div class="d-flex flex-wrap gap-2">
              <button class="btn btn-primary" type="submit" id="updateBtn">
                <i class="bi bi-check-circle me-1"></i> Update Attendance
              </button>
              <a class="btn btn-secondary" href="<?php echo site_url('attendance'); ?>">
                <i class="bi bi-arrow-left me-1"></i> Cancel
              </a>
            </div>
            <small class="text-muted" id="geoHint"></small>
          </div>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function(){
      function pad(n){ return (n<10?'0':'')+n; }
      function currentTimeStr(){ var d=new Date(); return pad(d.getHours())+':'+pad(d.getMinutes()); }
      function tick(){ try { var d=new Date(); document.getElementById('liveClock').textContent = pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds()); } catch(e){} }
      
      function showLoadingState(show) {
        var btn = document.getElementById('updateBtn');
        var form = document.getElementById('attendanceEditForm');
        if (btn) {
          btn.disabled = show;
          if (show) {
            btn.innerHTML = '<span class=\"spinner-border spinner-border-sm me-1\" role=\"status\" aria-hidden=\"true\"></span> Updating...';
          } else {
            btn.innerHTML = '<i class=\"bi bi-check-circle me-1\"></i> Update Attendance';
          }
        }
        if (form) {
          form.style.opacity = show ? '0.6' : '1';
          form.style.pointerEvents = show ? 'none' : 'auto';
        }
      }
      
      document.addEventListener('DOMContentLoaded', function(){
        try { tick(); setInterval(tick, 1000); } catch(e){}
        var inEl = document.querySelector('input[name="check_in"]');
        var outEl = document.querySelector('input[name="check_out"]');
        var btnIn = document.getElementById('btnNowIn');
        var btnOut = document.getElementById('btnNowOut');
        if (btnIn) btnIn.addEventListener('click', function(){ if (inEl) inEl.value = currentTimeStr(); });
        if (btnOut) btnOut.addEventListener('click', function(){ if (outEl) outEl.value = currentTimeStr(); });

        // Form submission with confirmation and loading state
        var form = document.getElementById('attendanceEditForm');
        if (form) {
          form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form before submission
            var notes = document.getElementById('notes');
            var attachment = document.getElementById('attachment');
            var errors = [];
            
            // Validate notes length
            if (notes && notes.value.length > 1000) {
              errors.push('Notes must be less than 1000 characters');
            }
            
            // Validate attachment size
            if (attachment && attachment.files[0]) {
              var fileSize = attachment.files[0].size / 1024 / 1024; // Convert to MB
              if (fileSize > 4) {
                errors.push('Attachment must be less than 4MB');
              }
            }
            
            // Show errors if any
            if (errors.length > 0) {
              var errorHtml = '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                              '<strong>Please fix the following errors:</strong><ul class="mb-0 mt-2">';
              errors.forEach(function(error) {
                errorHtml += '<li>' + error + '</li>';
              });
              errorHtml += '</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
              
              // Insert error message at the top of the form
              var firstChild = form.querySelector('.row > div:first-child');
              if (firstChild) {
                var existingAlert = form.querySelector('.alert-danger');
                if (existingAlert) existingAlert.remove();
                firstChild.insertAdjacentHTML('beforebegin', errorHtml);
              }
              return;
            }
            
            // Show confirmation dialog
            if (confirm('Are you sure you want to update this attendance record?')) {
              showLoadingState(true);
              
              // Submit form after a short delay for visual feedback
              setTimeout(function() {
                form.submit();
              }, 300);
            }
          });
        }

        // Geo capture similar to create
        try {
          var latEl = document.querySelector('input[name="lat"]');
          var lngEl = document.querySelector('input[name="lng"]');
          var hint = document.getElementById('geoHint');
          if (navigator.geolocation && latEl && lngEl){
            navigator.geolocation.getCurrentPosition(function(pos){
              try {
                latEl.value = String(pos.coords.latitude || '');
                lngEl.value = String(pos.coords.longitude || '');
                if (hint) {
                  hint.textContent = '✓ Location captured';
                  hint.classList.remove('text-muted');
                  hint.classList.add('text-success');
                }
              } catch(e){}
            }, function(){ 
              try { 
                if (hint) {
                  hint.textContent = '✗ Location not shared';
                  hint.classList.remove('text-muted');
                  hint.classList.add('text-warning');
                }
              } catch(e){} 
            }, { enableHighAccuracy:true, timeout:8000, maximumAge:0 });
          } else { 
            if (hint) {
              hint.textContent = 'Location not available';
              hint.classList.add('text-muted');
            }
          }
        } catch(e){}
      });
    })();
  </script>
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.5/dist/face-api.min.js"></script>
<script>
  (function(){
    function pad(n){ return (n<10?'0':'')+n; }
    document.addEventListener('DOMContentLoaded', function(){
      // Face verification logic (same behavior as create)
      try {
        var btnStart = document.getElementById('btnAttFaceStart'); // may be null if button removed
        var btnVerify = document.getElementById('btnAttFaceVerify');
        var video = document.getElementById('attFaceVideo');
        var canvas = document.getElementById('attFaceCanvas');
        var statusEl = document.getElementById('attFaceStatus');
        var faceDescEl = document.getElementById('faceDescriptor');
        var faceReqEl = document.getElementById('faceRequired');
        var stream = null;
        var modelsLoaded = false;
        var MODEL_URL = 'https://cdn.jsdelivr.net/gh/cgarciagl/face-api.js/weights/';
        var toastContainer = null; // dedicated center container for countdown toast
        var countdownToastEl = null;
        var countdownToast = null;

        function setFaceStatus(msg, isError, progress = 0){
          if (!statusEl) return;
          statusEl.textContent = msg || '';
          statusEl.classList.toggle('text-danger', !!isError);
          statusEl.classList.toggle('text-success', !isError && progress === 100);
          statusEl.classList.toggle('text-muted', !isError && progress < 100);
          
          // Update progress bar
          var progressEl = document.getElementById('faceProgress');
          if (progressEl) {
            progressEl.style.width = progress + '%';
            progressEl.classList.toggle('bg-success', progress === 100);
            progressEl.classList.toggle('bg-primary', progress < 100);
          }
          
          // Update camera status badges
          var cameraStatus = document.getElementById('cameraStatus');
          var cameraActive = document.getElementById('cameraActive');
          if (cameraStatus && cameraActive) {
            if (progress > 0 && progress < 100) {
              cameraStatus.style.display = 'none';
              cameraActive.style.display = 'inline-block';
            } else if (progress === 0) {
              cameraStatus.style.display = 'inline-block';
              cameraActive.style.display = 'none';
            } else {
              cameraStatus.style.display = 'none';
              cameraActive.style.display = 'none';
            }
          }
        }

        function showCountdownToast(msg){
          if (!window.bootstrap || !bootstrap.Toast) {
            // Fallback: only inline status
            setFaceStatus(msg, false);
            return;
          }
          if (!toastContainer){
            toastContainer = document.createElement('div');
            toastContainer.style.position = 'fixed';
            toastContainer.style.top = '50%';
            toastContainer.style.left = '50%';
            toastContainer.style.transform = 'translate(-50%, -50%)';
            toastContainer.style.zIndex = '1080';
            document.body.appendChild(toastContainer);
          }
          if (!countdownToastEl){
            countdownToastEl = document.createElement('div');
            countdownToastEl.className = 'toast align-items-center text-bg-dark border-0';
            countdownToastEl.setAttribute('role','alert');
            countdownToastEl.setAttribute('aria-live','assertive');
            countdownToastEl.setAttribute('aria-atomic','true');
            countdownToastEl.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
            toastContainer.appendChild(countdownToastEl);
            countdownToast = new bootstrap.Toast(countdownToastEl, { delay: 1500 });
          }
          var body = countdownToastEl.querySelector('.toast-body');
          if (body) { body.textContent = msg || ''; }
          countdownToast.show();
        }

        async function ensureModels(){
          if (modelsLoaded || !window.faceapi) return;
          try {
            setFaceStatus('Loading face models...', false, 10);
            await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
            setFaceStatus('Loading face recognition...', false, 40);
            await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
            setFaceStatus('Finalizing models...', false, 70);
            await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
            modelsLoaded = true;
            setFaceStatus('Models loaded. Starting camera...', false, 90);
          } catch(e){ setFaceStatus('Failed to load face models.', true, 0); }
        }

        async function startCam(auto){
          await ensureModels();
          if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia){
            setFaceStatus('Camera not supported in this browser.', true, 0);
            return;
          }
          try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode:'user' }, audio:false });
            video.srcObject = stream;
            btnVerify.disabled = false;
            if (auto) {
              var seconds = 3;
              setFaceStatus('Camera started. Auto capture in ' + seconds + ' seconds...', false, 95);
              showCountdownToast('Camera started. Auto capture in ' + seconds + ' seconds...');
              var countdownId = setInterval(function(){
                seconds--;
                if (seconds <= 0) {
                  clearInterval(countdownId);
                  // If a descriptor is already set, skip auto capture
                  if (faceDescEl && faceDescEl.value) { 
                    setFaceStatus('Face already captured.', false, 100);
                    return; 
                  }
                  captureFace(true);
                } else {
                  var msg = 'Auto capture in ' + seconds + ' seconds...';
                  setFaceStatus(msg, false, 95);
                  showCountdownToast(msg);
                }
              }, 1000);
            } else {
              setFaceStatus('Camera started. Align face and click Capture.', false, 95);
            }
          } catch(e){ setFaceStatus('Unable to access camera: '+e.message, true, 0); }
        }

        async function captureFace(autoSubmit){
          if (!modelsLoaded){ await ensureModels(); }
          if (!video || video.readyState < 2){ setFaceStatus('Camera not ready.', true, 0); return; }
          try {
            setFaceStatus('Detecting face...', false, 98);
            var opts = new faceapi.TinyFaceDetectorOptions();
            var det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
            if (!det || !det.descriptor){ 
              setFaceStatus('No face detected. Please try again.', true, 0); 
              return; 
            }
            setFaceStatus('Face captured successfully!', false, 100);
            var ctx = canvas.getContext('2d');
            canvas.width = video.videoWidth || 320;
            canvas.height = video.videoHeight || 240;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            var descArr = Array.prototype.slice.call(det.descriptor);
            if (faceDescEl) faceDescEl.value = JSON.stringify(descArr);
            if (faceReqEl) faceReqEl.value = '1';
            
            // Show retake button and hide capture button
            if (btnVerify) btnVerify.style.display = 'none';
            var retakeBtn = document.getElementById('btnRetakeFace');
            if (retakeBtn) retakeBtn.style.display = 'inline-block';
            
            // Stop camera after capture
            try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; } } catch(e){}
            // Auto submit form when requested
            if (autoSubmit) {
              var form = document.querySelector('form');
              if (form) { form.submit(); }
            }
          } catch(e){ setFaceStatus('Error capturing face: '+e.message, true, 0); }
        }

        if (btnVerify){
          if (btnStart) {
            btnStart.addEventListener('click', function(ev){ ev.preventDefault(); startCam(false); });
          }
          btnVerify.addEventListener('click', function(ev){ ev.preventDefault(); captureFace(false); });
          
          // Add retake functionality
          var retakeBtn = document.getElementById('btnRetakeFace');
          if (retakeBtn) {
            retakeBtn.addEventListener('click', function(ev){ 
              ev.preventDefault(); 
              // Clear face data
              if (faceDescEl) faceDescEl.value = '';
              if (faceReqEl) faceReqEl.value = '0';
              // Clear canvas
              var ctx = canvas.getContext('2d');
              ctx.clearRect(0, 0, canvas.width, canvas.height);
              // Reset UI
              setFaceStatus('Ready to capture new face...', false, 0);
              btnVerify.style.display = 'inline-block';
              btnVerify.disabled = true;
              retakeBtn.style.display = 'none';
              // Restart camera
              startCam(false);
            });
          }
          
          window.addEventListener('beforeunload', function(){ try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); } } catch(e){} });
        }

        // Auto start camera on load and capture+submit after 3 seconds
        startCam(true);
      } catch(e){}
    });
  })();
</script>
<?php $this->load->view('partials/footer'); ?>
