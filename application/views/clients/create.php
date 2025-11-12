<?php $this->load->view('partials/header', ['title' => 'Add Client']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Add Client</h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('clients'); ?>">Back</a>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="" class="vstack gap-3">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Company Name</label>
          <input type="text" name="company_name" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Contact Person</label>
          <input type="text" name="contact_person" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Alternate Phone</label>
          <input type="text" name="alternate_phone" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Website</label>
          <input type="text" name="website" class="form-control">
        </div>
        <div class="col-md-12">
          <label class="form-label">Address</label>
          <textarea name="address" rows="2" class="form-control"></textarea>
        </div>
        <div class="col-md-3">
          <label class="form-label">City</label>
          <input type="text" name="city" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">State</label>
          <input type="text" name="state" class="form-control">
        </div>
        <div class="col-md-3">
          <label class="form-label">Country</label>
          <input type="text" name="country" class="form-control" value="India">
        </div>
        <div class="col-md-3">
          <label class="form-label">Zip</label>
          <input type="text" name="zip_code" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">GSTIN</label>
          <input type="text" name="gstin" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">PAN</label>
          <input type="text" name="pan_number" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Industry</label>
          <input type="text" name="industry" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Type</label>
          <select name="client_type" class="form-select">
            <option value="company">Company</option>
            <option value="individual">Individual</option>
            <option value="government">Government</option>
            <option value="startup">Startup</option>
          </select>
        </div>
        <div class="col-md-8">
          <label class="form-label">Account Manager</label>
          <select name="account_manager_id" class="form-select">
            <option value="">-- Select --</option>
            <?php if (isset($managers) && is_array($managers)) foreach ($managers as $m): ?>
              <?php $label = isset($m->full_name) && $m->full_name !== '' ? $m->full_name : (isset($m->name) && $m->name !== '' ? $m->name : $m->email); ?>
              <option value="<?php echo (int)$m->id; ?>"><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" rows="3" class="form-control"></textarea>
        </div>
      </div>
      <div>
        <button class="btn btn-primary">Create</button>
        <a class="btn btn-light" href="<?php echo site_url('clients'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
