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
        <div class="col-12 d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2">
          <button class="btn btn-primary w-100 w-sm-auto" type="submit">Save Attendance</button>
          <small class="text-muted" id="geoHint"></small>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function(){
      function pad(n){ return (n<10?'0':'')+n; }
      function tick(){ try { const d=new Date(); document.getElementById('liveClock').textContent = pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds()); } catch(e){} }
      document.addEventListener('DOMContentLoaded', function(){
        try { tick(); setInterval(tick, 1000); } catch(e){}
        try {
          var latEl = document.querySelector('input[name="lat"]');
          var lngEl = document.querySelector('input[name="lng"]');
          var hint = document.getElementById('geoHint');
          if (navigator.geolocation && latEl && lngEl){
            navigator.geolocation.getCurrentPosition(function(pos){
              try {
                latEl.value = String(pos.coords.latitude || '');
                lngEl.value = String(pos.coords.longitude || '');
                if (hint) hint.textContent = 'Location captured';
              } catch(e){}
            }, function(){ try { if (hint) hint.textContent = 'Location not shared'; } catch(e){} }, { enableHighAccuracy:true, timeout:8000, maximumAge:0 });
          } else { if (hint) hint.textContent = 'Location not available'; }
        } catch(e){}
      });
    })();
  </script>
<?php $this->load->view('partials/footer'); ?>
