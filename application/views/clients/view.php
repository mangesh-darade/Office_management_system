<?php $this->load->view('partials/header', ['title' => 'Client Details']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Client: <?php echo htmlspecialchars($client->company_name); ?></h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('clients'); ?>">Back</a>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-soft mb-3">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="small text-muted">Client Code</div>
            <div><?php echo htmlspecialchars($client->client_code); ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Status</div>
            <div><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(isset($client->status)?$client->status:'active'); ?></span></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Contact Person</div>
            <div><?php echo htmlspecialchars(isset($client->contact_person)?$client->contact_person:''); ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Email</div>
            <div><?php echo htmlspecialchars(isset($client->email)?$client->email:''); ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Phone</div>
            <div><?php echo htmlspecialchars(isset($client->phone)?$client->phone:''); ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Alternate Phone</div>
            <div><?php echo htmlspecialchars(isset($client->alternate_phone)?$client->alternate_phone:''); ?></div>
          </div>
          <div class="col-md-12">
            <div class="small text-muted">Address</div>
            <div><?php echo nl2br(htmlspecialchars(isset($client->address)?$client->address:'')); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">City</div>
            <div><?php echo htmlspecialchars(isset($client->city)?$client->city:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">State</div>
            <div><?php echo htmlspecialchars(isset($client->state)?$client->state:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">Country</div>
            <div><?php echo htmlspecialchars(isset($client->country)?$client->country:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">GSTIN</div>
            <div><?php echo htmlspecialchars(isset($client->gstin)?$client->gstin:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">PAN</div>
            <div><?php echo htmlspecialchars(isset($client->pan_number)?$client->pan_number:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">Industry</div>
            <div><?php echo htmlspecialchars(isset($client->industry)?$client->industry:''); ?></div>
          </div>
          <div class="col-md-12">
            <div class="small text-muted">Notes</div>
            <div><?php echo nl2br(htmlspecialchars(isset($client->notes)?$client->notes:'')); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-soft">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Contacts</h5>
      </div>
      <div class="card-body">
        <?php if (empty($contacts)): ?>
          <div class="text-muted">No contacts added yet.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead><tr>
              <th>Name</th>
              <th>Designation</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Primary</th>
            </tr></thead>
            <tbody>
              <?php foreach ($contacts as $ct): ?>
              <tr>
                <td><?php echo htmlspecialchars($ct->contact_name); ?></td>
                <td><?php echo htmlspecialchars(isset($ct->designation)?$ct->designation:''); ?></td>
                <td><?php echo htmlspecialchars(isset($ct->email)?$ct->email:''); ?></td>
                <td><?php echo htmlspecialchars(isset($ct->phone)?$ct->phone:''); ?></td>
                <td><?php echo ((int)$ct->is_primary) ? 'Yes' : 'No'; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card shadow-soft">
      <div class="card-header">
        <h5 class="mb-0">Meta</h5>
      </div>
      <div class="card-body small text-muted">
        <div>Created At: <?php echo htmlspecialchars(isset($client->created_at)?$client->created_at:''); ?></div>
        <div>Updated At: <?php echo htmlspecialchars(isset($client->updated_at)?$client->updated_at:''); ?></div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
