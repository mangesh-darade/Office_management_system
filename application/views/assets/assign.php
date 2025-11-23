<?php $this->load->view('partials/header', ['title' => 'Assign Asset']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Assign Asset</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('assets-mgmt'); ?>">Back</a>
</div>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <div class="row g-2 small">
      <div class="col-md-4"><strong>Name:</strong> <?php echo htmlspecialchars($row->name); ?></div>
      <div class="col-md-4"><strong>Tag:</strong> <?php echo htmlspecialchars(isset($row->asset_tag)?$row->asset_tag:''); ?></div>
      <div class="col-md-4"><strong>Status:</strong> <?php echo htmlspecialchars(isset($row->status)?$row->status:''); ?></div>
      <?php if ($current): ?>
      <div class="col-md-12 text-muted">
        Currently assigned to user ID <?php echo (int)$current->user_id; ?> since <?php echo htmlspecialchars($current->allocated_on); ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">User <span class="text-danger">*</span></label>
          <select name="user_id" class="form-select" required>
            <option value="">Select user</option>
            <?php foreach ($users as $u): ?>
              <option value="<?php echo (int)$u['id']; ?>"><?php echo htmlspecialchars($u['label']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Allocated On</label>
          <input type="date" name="allocated_on" class="form-control" value="<?php echo date('Y-m-d'); ?>" />
        </div>
        <div class="col-md-12">
          <label class="form-label">Remarks</label>
          <textarea name="remarks" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary">Assign</button>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
