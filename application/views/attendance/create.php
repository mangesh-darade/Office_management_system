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

        <!-- Face Verification & Attachment -->
        <div class="row mb-4">
          <div class="col-12 col-lg-6 mb-3 mb-lg-0">
            <label class="form-label fw-semibold">
              <i class="bi bi-camera"></i> Face Verification
            </label>
            <div class="position-relative">
              <video id="attFaceVideo" class="w-100 rounded border" 
                     autoplay muted playsinline style="height: 180px; background: #000;"></video>
              <div class="position-absolute top-50 start-50 translate-middle text-white text-center" id="cameraLoader">
                <div class="spinner-border spinner-border-sm" role="status"></div>
                <div class="small mt-1">Starting camera...</div>
              </div>
            </div>
            <canvas id="attFaceCanvas" class="w-100 rounded border mt-2" style="height: 180px; display: none;"></canvas>
            <div class="small mt-1" id="attFaceStatus"></div>
            <button type="button" class="btn btn-primary btn-sm w-100 mt-2" id="btnAttFaceVerify" disabled>
              <i class="bi bi-camera-fill"></i> Capture Face
            </button>
          </div>
          
          <div class="col-12 col-lg-6">
            <label class="form-label fw-semibold">
              <i class="bi bi-paperclip"></i> Attachment
            </label>
            <div class="border rounded p-3 text-center bg-light">
              <input type="file" name="attachment" id="attachmentFile" 
                     class="d-none" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
              <label for="attachmentFile" class="btn btn-outline-primary btn-sm cursor-pointer mb-2">
                <i class="bi bi-upload"></i> Choose File
              </label>
              <div class="small text-muted" id="fileName">No file selected</div>
              <div class="small text-secondary">Max 4MB • JPG, PNG, PDF, DOC</div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="row">
          <div class="col-12">
            <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit" id="submitBtn">
              <i class="bi bi-check-circle"></i> Mark Attendance
            </button>
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
        
        // File upload handling
        try {
          var fileInput = document.getElementById('attachmentFile');
          var fileName = document.getElementById('fileName');
          if (fileInput && fileName) {
            fileInput.addEventListener('change', function(e){
              var file = e.target.files[0];
              if (file) {
                fileName.textContent = file.name + ' (' + (file.size/1024/1024).toFixed(2) + ' MB)';
                fileName.classList.remove('text-muted');
                fileName.classList.add('text-success');
              } else {
                fileName.textContent = 'No file selected';
                fileName.classList.remove('text-success');
                fileName.classList.add('text-muted');
              }
            });
          }
        } catch(e){}
        
        try {
          var hasLocation = false;
          function resolveAddress(lat, lng, hint, locEl){
            try {
              if (!hint) return;
              if (!lat || !lng) return;
              hint.textContent = 'Location captured, resolving address...';
              hint.classList.remove('text-muted');
              hint.classList.add('text-primary');
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
                    }
                  } catch(e){}
                })
                .catch(function(){
                  hint.textContent = 'Location found, address unavailable';
                  hint.classList.remove('text-primary');
                  hint.classList.add('text-warning');
                });
            } catch(e){}
          }
          var latEl = document.querySelector('input[name="lat"]');
          var lngEl = document.querySelector('input[name="lng"]');
          var locEl = document.querySelector('input[name="location_name"]');
          var hint = document.getElementById('geoHint');
          if (navigator.geolocation && latEl && lngEl){
            navigator.geolocation.getCurrentPosition(function(pos){
              try {
                var lat = String(pos.coords.latitude || '');
                var lng = String(pos.coords.longitude || '');
                latEl.value = lat;
                lngEl.value = lng;
                hasLocation = true;
                if (hint) {
                  resolveAddress(lat, lng, hint, locEl);
                }
              } catch(e){}
            }, function(){ 
              try { 
                if (hint) {
                  hint.textContent = 'Location access denied';
                  hint.classList.remove('text-muted');
                  hint.classList.add('text-danger');
                }
              } catch(e){} 
            }, { enableHighAccuracy:true, timeout:8000, maximumAge:0 });
          } else { 
            if (hint) {
              hint.textContent = 'Location not available';
              hint.classList.remove('text-muted');
              hint.classList.add('text-secondary');
            }
          }
        } catch(e){}
        
        // Face verification logic
        try {
          var btnVerify = document.getElementById('btnAttFaceVerify');
          var video = document.getElementById('attFaceVideo');
          var canvas = document.getElementById('attFaceCanvas');
          var statusEl = document.getElementById('attFaceStatus');
          var faceDescEl = document.getElementById('faceDescriptor');
          var faceReqEl = document.getElementById('faceRequired');
          var cameraLoader = document.getElementById('cameraLoader');
          var stream = null;
          var modelsLoaded = false;
          var MODEL_URL = 'https://cdn.jsdelivr.net/gh/cgarciagl/face-api.js/weights/';

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
                    if (faceDescEl && faceDescEl.value) { return; }
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
                cameraLoader.innerHTML = '<div class="text-danger">Camera unavailable</div>';
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
              setFaceStatus('Face captured successfully', false);
              
              // Stop camera
              try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; } } catch(e){}
              
              // Auto submit
              if (autoSubmit) {
                if (!hasLocation) {
                  setFaceStatus('Location required', true);
                  return;
                }
                var form = document.querySelector('form');
                if (form) { form.submit(); }
              }
            } catch(e){ setFaceStatus('Capture failed: '+e.message, true); }
          }

          if (btnVerify){
            btnVerify.addEventListener('click', function(ev){ 
              ev.preventDefault(); 
              captureFace(false); 
            });
            window.addEventListener('beforeunload', function(){ 
              try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); } } catch(e){}
            });
            
            // Auto start camera
            startCam(true);
          }
        } catch(e){}
      });
    })();
  </script>
<?php $this->load->view('partials/footer'); ?>
