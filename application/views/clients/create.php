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
    <form method="post" action="" enctype="multipart/form-data" class="vstack gap-3" data-validate="true">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="company_name">Company Name <span class="text-danger">*</span></label>
          <input type="text" name="company_name" id="company_name" class="form-control" data-mandatory="true" data-min-length="2" data-max-length="255" required>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="contact_person">Contact Person <span class="text-danger">*</span></label>
          <input type="text" name="contact_person" id="contact_person" class="form-control" data-mandatory="true" data-min-length="2" data-max-length="200" required>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="email">Email</label>
          <input type="email" name="email" id="email" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label" for="phone">Phone <span class="text-danger">*</span></label>
          <input type="text" name="phone" id="phone" class="form-control" data-mandatory="true" data-min-length="10" data-max-length="20" data-pattern="^[0-9+\s\-\(\)]+$" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Alternate Phone</label>
          <input type="text" name="alternate_phone" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Website</label>
          <input type="text" name="website" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Demo URL</label>
          <input type="text" name="demo_url" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">POS URL</label>
          <input type="text" name="pos_url" class="form-control">
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
            <option value="other">Other</option>
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
        <div class="col-md-4">
          <label class="form-label">Onboarding Date</label>
          <input type="date" name="onboarding_date" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Logo</label>
          <input type="file" name="logo" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">DB Name</label>
          <input type="text" name="db_name" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">DB Username</label>
          <input type="text" name="db_username" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">DB Password</label>
          <input type="text" name="db_password" class="form-control">
        </div>
        <div class="col-md-12">
          <label class="form-label">Notes</label>
          <textarea name="notes" rows="3" class="form-control"></textarea>
        </div>
      </div>
      <div>
        <button type="submit" class="btn btn-primary" id="submitBtn">Create</button>
        <a class="btn btn-light" href="<?php echo site_url('clients'); ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
