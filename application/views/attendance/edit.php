<?php $this->load->view('partials/header', ['title' => 'Edit Attendance']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div class="d-flex align-items-center gap-3 mb-2 mb-sm-0">
      <h1 class="h4 mb-0">Edit Attendance</h1>
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
        <div class="col-12 col-md-4">
          <label class="form-label">Date</label>
          <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($att->date); ?>" required>
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label d-flex justify-content-between align-items-center">
            <span>Check In</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnNowIn" title="Set to current time">Now</button>
          </label>
          <div class="input-group">
            <input type="time" name="check_in" class="form-control" value="<?php echo htmlspecialchars($att->check_in); ?>">
          </div>
        </div>
        <div class="col-6 col-md-4">
          <label class="form-label d-flex justify-content-between align-items-center">
            <span>Check Out</span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnNowOut" title="Set to current time">Now</button>
          </label>
          <div class="input-group">
            <input type="time" name="check_out" class="form-control" value="<?php echo htmlspecialchars($att->check_out); ?>">
          </div>
        </div>
        <div class="col-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="Any notes..."><?php echo htmlspecialchars($att->notes); ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label">Attachment</label>
          <?php if(!empty($att->attachment_path)): ?>
            <div class="mb-2">
              <a class="btn btn-outline-secondary btn-sm" href="<?php echo base_url($att->attachment_path); ?>" target="_blank" title="View current file"><i class="bi bi-paperclip"></i> Current file</a>
            </div>
          <?php endif; ?>
          <input type="file" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
          <div class="form-text">Upload to replace. Max 4MB. Allowed: JPG, PNG, PDF, DOC, DOCX</div>
        </div>
        <div class="col-12">
          <button class="btn btn-primary" type="submit">Update Attendance</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    (function(){
      function pad(n){ return (n<10?'0':'')+n; }
      function currentTimeStr(){ const d=new Date(); return pad(d.getHours())+':'+pad(d.getMinutes()); }
      function tick(){ try { const d=new Date(); document.getElementById('liveClock').textContent = pad(d.getHours())+':'+pad(d.getMinutes())+':'+pad(d.getSeconds()); } catch(e){} }
      document.addEventListener('DOMContentLoaded', function(){
        try { tick(); setInterval(tick, 1000); } catch(e){}
        var inEl = document.querySelector('input[name="check_in"]');
        var outEl = document.querySelector('input[name="check_out"]');
        var btnIn = document.getElementById('btnNowIn');
        var btnOut = document.getElementById('btnNowOut');
        if (btnIn) btnIn.addEventListener('click', function(){ if (inEl) inEl.value = currentTimeStr(); });
        if (btnOut) btnOut.addEventListener('click', function(){ if (outEl) outEl.value = currentTimeStr(); });
      });
    })();
  </script>
<?php $this->load->view('partials/footer'); ?>
