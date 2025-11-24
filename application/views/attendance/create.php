<?php $this->load->view('partials/header', ['title' => 'Mark Attendance']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div class="d-flex align-items-center gap-3 mb-2 mb-sm-0">
      <h1 class="h4 mb-0">Mark Attendance</h1>
      <span class="badge text-bg-light" id="liveClock">--:--:--</span>
    </div>
    <a class="btn btn-secondary" href="<?php echo site_url('attendance'); ?>">Back</a>
  </div>

  <?php if($this->session->flashdata('success')): ?>
    <div class="alert alert-success fade show" role="alert"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    <div class="alert alert-danger fade show" role="alert"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="card shadow-sm fade-in">
    <div class="card-body">
      <form method="post" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="lat" value="" />
        <input type="hidden" name="lng" value="" />
        <input type="hidden" name="location_name" value="" />
        <input type="hidden" name="face_required" id="faceRequired" value="0" />
        <input type="hidden" name="face_descriptor" id="faceDescriptor" value="" />
        <div class="col-12 col-sm-6 col-md-4">
          <label class="form-label">Action</label>
          <select name="action" class="form-select" required>
            <option value="in">IN</option>
            <option value="out">OUT</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Any notes..."></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Attachment (optional)</label>
          <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
          <div class="form-text">Max 4MB. Allowed: JPG, PNG, PDF, DOC, DOCX</div>
        </div>
        <div class="col-12 col-lg-6">
          <label class="form-label">Face Verification (optional)</label>
          <div class="row g-2">
            <div class="col-12 col-sm-6">
              <video id="attFaceVideo" class="w-100 border rounded" autoplay muted playsinline style="max-height:220px; background:#000;"></video>
            </div>
            <div class="col-12 col-sm-6">
              <canvas id="attFaceCanvas" class="w-100 border rounded" style="max-height:220px;"></canvas>
              <div class="small text-muted mt-1" id="attFaceStatus"></div>
            </div>
          </div>
          <div class="mt-2 d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary btn-sm" id="btnAttFaceVerify" disabled>Capture Face for Verification</button>
          </div>
        </div>
        <div class="col-12 col-lg-6 d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 mt-3 mt-lg-0">
          <div class="d-flex flex-column flex-sm-row gap-2 w-100">
            <button class="btn btn-primary w-100 w-sm-auto" type="submit">Save Attendance</button>
          </div>
          <small class="text-muted" id="geoHint"></small>
        </div>
      </form>
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
          function resolveAddress(lat, lng, hint, locEl){
            try {
              if (!hint) return;
              if (!lat || !lng) return;
              hint.textContent = 'Location captured, resolving address...';
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
                      if (locEl) { locEl.value = addr; }
                    }
                  } catch(e){}
                })
                .catch(function(){});
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
            }, function(){ try { if (hint) hint.textContent = 'Location not shared'; } catch(e){} }, { enableHighAccuracy:true, timeout:8000, maximumAge:0 });
          } else { if (hint) hint.textContent = 'Location not available'; }
        } catch(e){}
        // Face verification logic
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

          function setFaceStatus(msg, isError){
            if (!statusEl) return;
            statusEl.textContent = msg || '';
            statusEl.classList.toggle('text-danger', !!isError);
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
              setFaceStatus('Camera not supported in this browser.', true);
              return;
            }
            try {
              stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode:'user' }, audio:false });
              video.srcObject = stream;
              btnVerify.disabled = false;
              if (auto) {
                var seconds = 3;
                setFaceStatus('Camera started. Auto capture in ' + seconds + ' seconds...', false);
                showCountdownToast('Camera started. Auto capture in ' + seconds + ' seconds...');
                var countdownId = setInterval(function(){
                  seconds--;
                  if (seconds <= 0) {
                    clearInterval(countdownId);
                    // If a descriptor is already set, skip auto capture
                    if (faceDescEl && faceDescEl.value) { return; }
                    if (!hasLocation) {
                      setFaceStatus('Location not captured yet. Please allow location access or try again.', true);
                      return;
                    }
                    captureFace(true);
                  } else {
                    var msg = 'Auto capture in ' + seconds + ' seconds...';
                    setFaceStatus(msg, false);
                    showCountdownToast(msg);
                  }
                }, 1000);
              } else {
                setFaceStatus('Camera started. Align face and click Capture.', false);
              }
            } catch(e){ setFaceStatus('Unable to access camera: '+e.message, true); }
          }

          async function captureFace(autoSubmit){
            if (!modelsLoaded){ await ensureModels(); }
            if (!video || video.readyState < 2){ setFaceStatus('Camera not ready.', true); return; }
            try {
              var opts = new faceapi.TinyFaceDetectorOptions();
              var det = await faceapi.detectSingleFace(video, opts).withFaceLandmarks().withFaceDescriptor();
              if (!det || !det.descriptor){ setFaceStatus('No face detected. Please try again.', true); return; }
              var ctx = canvas.getContext('2d');
              canvas.width = video.videoWidth || 320;
              canvas.height = video.videoHeight || 240;
              ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
              var descArr = Array.prototype.slice.call(det.descriptor);
              if (faceDescEl) faceDescEl.value = JSON.stringify(descArr);
              if (faceReqEl) faceReqEl.value = '1';
              setFaceStatus('Face captured. You can now save attendance.', false);
              // Stop camera after capture
              try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); stream = null; } } catch(e){}
              // Auto submit form when requested
              if (autoSubmit) {
                if (!hasLocation) {
                  setFaceStatus('Location not captured yet. Please allow location access or try again.', true);
                  return;
                }
                var form = document.querySelector('form');
                if (form) { form.submit(); }
              }
            } catch(e){ setFaceStatus('Error capturing face: '+e.message, true); }
          }

          if (btnVerify){
            if (btnStart) {
              btnStart.addEventListener('click', function(ev){ ev.preventDefault(); startCam(false); });
            }
            btnVerify.addEventListener('click', function(ev){ ev.preventDefault(); captureFace(false); });
            window.addEventListener('beforeunload', function(){ try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); } } catch(e){} });
          }

          // Auto start camera on load and capture+submit after 3 seconds
          // Default action is IN; user can change it before auto submit if needed
          startCam(true);
        } catch(e){}
      });
    })();
  </script>
<?php $this->load->view('partials/footer'); ?>
