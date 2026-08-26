<?php
$back_url = site_url('clients/view/' . (int) $client->id);
$this->load->view('partials/header', array('title' => 'Edit Client'));
?>

<div class="oms-form-compact">
  <div class="oms-form-page-head">
    <a class="btn btn-outline-secondary btn-sm oms-form-back" href="<?php echo esc_view($back_url); ?>">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <div class="oms-form-page-titles">
      <h1 class="h4 mb-0">Edit Client</h1>
    </div>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('info')): ?>
  <div class="alert alert-info"><?php echo esc_view($this->session->flashdata('info')); ?></div>
  <?php endif; ?>

  <form method="post" action="" enctype="multipart/form-data" data-validate="true">
    <div class="card shadow-soft oms-form-card">
      <div class="card-body">

        <section class="oms-form-section">
          <div class="oms-form-section-title"><i class="bi bi-building me-1"></i>Basic details</div>
          <div class="row g-2 oms-form-grid">
            <div class="col-lg-3 col-md-6">
              <label class="form-label" for="company_name">Company Name <span class="text-danger">*</span></label>
              <input type="text" name="company_name" id="company_name" class="form-control" data-mandatory="true" data-min-length="2" data-max-length="255" required value="<?php echo esc_view(isset($client->company_name)?$client->company_name:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label" for="contact_person">Contact Person <span class="text-danger">*</span></label>
              <input type="text" name="contact_person" id="contact_person" class="form-control" data-mandatory="true" data-min-length="2" data-max-length="200" required value="<?php echo esc_view(isset($client->contact_person)?$client->contact_person:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label" for="email">Email</label>
              <input type="email" name="email" id="email" class="form-control" value="<?php echo esc_view(isset($client->email)?$client->email:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label" for="phone">Phone</label>
              <input type="text" name="phone" id="phone" class="form-control" data-max-length="20" data-pattern="^[0-9+\s\-\(\)]*$" value="<?php echo esc_view(isset($client->phone)?$client->phone:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Alternate Phone</label>
              <input type="text" name="alternate_phone" class="form-control" value="<?php echo esc_view(isset($client->alternate_phone)?$client->alternate_phone:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Type</label>
              <?php $ct = isset($client->client_type) ? (string) $client->client_type : 'company'; ?>
              <?php $this->load->view('partials/module_type_select', array(
                'field_name' => 'client_type',
                'options' => isset($client_types) ? $client_types : array(),
                'current' => $ct,
                'required' => true,
              )); ?>
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Status</label>
              <?php $st = isset($client->status) ? (string) $client->status : 'active'; ?>
              <?php $this->load->view('partials/status_select', array(
                'field_name' => 'status',
                'module_type' => 'clients',
                'current' => $st,
              )); ?>
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Account Manager</label>
              <select name="account_manager_id" class="form-select">
                <option value="">— Select —</option>
                <?php if (isset($managers) && is_array($managers)) foreach ($managers as $m): ?>
                  <?php $label = isset($m->full_name) && $m->full_name !== '' ? $m->full_name : (isset($m->name) && $m->name !== '' ? $m->name : $m->email); ?>
                  <option value="<?php echo (int)$m->id; ?>" <?php echo isset($client->account_manager_id) && (int)$client->account_manager_id === (int)$m->id ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Onboarding Date</label>
              <input type="date" name="onboarding_date" class="form-control" value="<?php echo esc_view(isset($client->onboarding_date)?$client->onboarding_date:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Logo</label>
              <input type="file" name="logo" class="form-control">
              <?php if (!empty($client->logo)): ?>
              <div class="small text-muted mt-1">Current: <?php echo esc_view($client->logo); ?></div>
              <?php endif; ?>
            </div>
            <div class="col-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" rows="2" class="form-control"><?php echo esc_view(isset($client->notes)?$client->notes:''); ?></textarea>
            </div>
          </div>
        </section>

        <section class="oms-form-section">
          <div class="oms-form-section-title"><i class="bi bi-geo-alt me-1"></i>Address</div>
          <div class="row g-2 oms-form-grid">
            <div class="col-12">
              <label class="form-label">Address</label>
              <textarea name="address" rows="2" class="form-control"><?php echo esc_view(isset($client->address)?$client->address:''); ?></textarea>
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">City</label>
              <input type="text" name="city" class="form-control" value="<?php echo esc_view(isset($client->city)?$client->city:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">State</label>
              <input type="text" name="state" class="form-control" value="<?php echo esc_view(isset($client->state)?$client->state:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Country</label>
              <input type="text" name="country" class="form-control" value="<?php echo esc_view(isset($client->country)?$client->country:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Zip</label>
              <input type="text" name="zip_code" class="form-control" value="<?php echo esc_view(isset($client->zip_code)?$client->zip_code:''); ?>">
            </div>
          </div>
        </section>

        <section class="oms-form-section">
          <div class="oms-form-section-title"><i class="bi bi-briefcase me-1"></i>Business</div>
          <div class="row g-2 oms-form-grid">
            <div class="col-lg-3 col-md-6">
              <label class="form-label">GSTIN</label>
              <input type="text" name="gstin" class="form-control" value="<?php echo esc_view(isset($client->gstin)?$client->gstin:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">PAN</label>
              <input type="text" name="pan_number" class="form-control" value="<?php echo esc_view(isset($client->pan_number)?$client->pan_number:''); ?>">
            </div>
            <div class="col-lg-3 col-md-6">
              <label class="form-label">Industry</label>
              <input type="text" name="industry" class="form-control" value="<?php echo esc_view(isset($client->industry)?$client->industry:''); ?>">
            </div>
          </div>
        </section>

        <?php $this->load->view('clients/_url_db_section', array(
          'existing_urls' => isset($existing_urls) ? $existing_urls : array(),
          'password_mode' => 'text',
        )); ?>

      </div>
      <div class="card-footer oms-form-actions">
        <button type="submit" class="btn btn-primary" id="submitBtn"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
        <a class="btn btn-outline-secondary" href="<?php echo esc_view($back_url); ?>">Cancel</a>
      </div>
    </div>
  </form>
</div>
<?php $this->load->view('partials/footer'); ?>
