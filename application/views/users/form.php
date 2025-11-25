<?php $this->load->view('partials/header', array('title' => (isset($title) ? $title : 'User'), 'active' => 'users')); ?>
<div class="row g-3">
  <div class="col-12">
    <div class="card mb-3 border-0 shadow-sm">
      <div class="card-body d-flex justify-content-between align-items-center">
        <h1 class="h5 mb-0"><?php echo htmlspecialchars(isset($title) ? $title : 'User'); ?></h1>
        <a href="<?php echo site_url('users'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
      <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <div class="card shadow-soft border-0">
      <div class="card-body">
        <form method="post" enctype="multipart/form-data" action="<?php echo $is_edit ? site_url('users/update/'.(int)$row->id) : site_url('users/store'); ?>">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars(isset($row->name) ? $row->name : ''); ?>" required>
            </div>
            <?php if (!$is_edit): ?>
            <div class="col-md-4">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="email" name="email" id="userEmail" class="form-control" value="<?php echo htmlspecialchars(isset($row->email) ? $row->email : ''); ?>" placeholder="you@gmail.com" required>
                <button class="btn btn-outline-secondary" type="button" id="btnSendCode">Send code</button>
              </div>
              <div class="form-text" id="emailHelp"></div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Verification Code</label>
              <input type="text" name="verify_code" class="form-control" placeholder="Enter code sent to this Gmail">
            </div>
            <?php else: ?>
            <div class="col-md-4">
              <label class="form-label">Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars(isset($row->email) ? $row->email : ''); ?>" required>
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
              <select name="role_id" class="form-select" required>
                <?php foreach ($roleOptions as $id => $name): ?>
                  <option value="<?php echo (int)$id; ?>" <?php echo $rid===(int)$id?'selected':''; ?>><?php echo htmlspecialchars($name); ?></option>
                <?php endforeach; ?>
              </select>
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
              <select name="status" class="form-select" required>
                <option value="1" <?php echo $st===1?'selected':''; ?>>Active</option>
                <option value="0" <?php echo $st===0?'selected':''; ?>>Inactive</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Phone <span class="text-danger">*</span></label>
              <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars(isset($row->phone) ? $row->phone : ''); ?>" required pattern="[0-9]{10}" maxlength="10" inputmode="numeric" title="Enter 10-digit mobile number">
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

          <div class="mt-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Save</button>
            <a class="btn btn-outline-secondary" href="<?php echo site_url('users'); ?>">Cancel</a>
          </div>
        </form>
      </div>
    </div>

    <?php if ($is_edit && isset($row->id) && (int)$row->id > 0): ?>
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="mb-2">Face Registration</h6>
        <p class="small text-muted mb-3">Capture the user's face using the camera. This will be used later to verify attendance.</p>
        <div class="row g-2 align-items-start">
          <div class="col-12 col-md-6">
            <video id="faceVideo" class="w-100 border rounded" autoplay muted playsinline style="max-height:260px; background:#000;"></video>
          </div>
          <div class="col-12 col-md-6">
            <canvas id="faceCanvas" class="w-100 border rounded" style="max-height:260px;"></canvas>
            <div class="small text-muted mt-2" id="faceStatus"></div>
          </div>
        </div>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <button type="button" class="btn btn-outline-primary btn-sm" id="btnFaceStart">Start Camera</button>
          <button type="button" class="btn btn-primary btn-sm" id="btnFaceCapture" disabled>Capture &amp; Save Face</button>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info mt-3 small">After creating the user, open Edit User to register their face for attendance.</div>
    <?php endif; ?>
  </div>
</div>
<script>
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    if (<?php echo $is_edit ? 'true' : 'false'; ?>) return;
    var site = '<?php echo rtrim(site_url(), "/"); ?>/';
    var emailInput = document.getElementById('userEmail');
    var btn = document.getElementById('btnSendCode');
    var help = document.getElementById('emailHelp');
    if (!emailInput || !btn || !help) return;
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
        body: new URLSearchParams({ email: email })
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

  function setStatus(msg, isError){
    if (!statusEl) return;
    statusEl.textContent = msg || '';
    statusEl.classList.toggle('text-danger', !!isError);
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
      fetch('<?php echo site_url('users/save_face'); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      }).then(function(r){ return r.json(); }).then(function(j){
        if (j && j.ok){
          setStatus('Face data saved successfully.', false);
        } else {
          setStatus(j && j.error ? j.error : 'Failed to save face data.', true);
        }
      }).catch(function(){ setStatus('Failed to save face data.', true); });
    } catch (e){
      setStatus('Error capturing face: ' + e.message, true);
    }
  }

  btnStart.addEventListener('click', function(ev){ ev.preventDefault(); startCamera(); });
  btnCapture.addEventListener('click', function(ev){ ev.preventDefault(); captureFace(); });

  window.addEventListener('beforeunload', function(){
    try { if (stream){ stream.getTracks().forEach(function(t){ t.stop(); }); } } catch(e){}
  });
})();
</script>
<?php $this->load->view('partials/footer'); ?>
