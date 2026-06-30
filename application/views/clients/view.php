<?php $this->load->view('partials/header', ['title' => 'Client Details']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Client: <?php echo esc_view($client->company_name); ?></h1>
  <div class="d-flex gap-2">
    <a class="btn btn-light btn-sm" href="<?php echo site_url('clients'); ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <?php if(function_exists('has_module_access') && (has_module_access('clients_edit') || has_module_access('clients'))): ?>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('clients/edit/'.(int)$client->id); ?>"><i class="bi bi-pencil me-1"></i>Edit</a>
    <?php endif; ?>
    <?php if(function_exists('has_module_access') && (has_module_access('clients_delete') || has_module_access('clients'))): ?>
    <button type="button" class="btn btn-danger btn-sm"
            onclick="confirmDeleteClient(<?php echo (int)$client->id; ?>, '<?php echo esc_view(addslashes($client->company_name), ENT_QUOTES, 'UTF-8'); ?>')">
      <i class="bi bi-trash me-1"></i>Delete
    </button>
    <?php endif; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-soft mb-3">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="small text-muted">Client Code</div>
            <div><?php echo esc_view($client->client_code); ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Status</div>
            <div><span class="badge bg-light text-dark border"><?php echo esc_view(isset($client->status)?$client->status:'active'); ?></span></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Contact Person</div>
            <div><?php echo esc_view(isset($client->contact_person)?$client->contact_person:''); ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Email</div>
            <div><?php echo esc_view(isset($client->email)?$client->email:''); ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Phone</div>
            <div><?php echo esc_view(isset($client->phone)?$client->phone:''); ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Alternate Phone</div>
            <div><?php echo esc_view(isset($client->alternate_phone)?$client->alternate_phone:''); ?></div>
          </div>
          <div class="col-md-12">
            <div class="small text-muted">Address</div>
            <div><?php echo nl2br(esc_view(isset($client->address)?$client->address:'')); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">City</div>
            <div><?php echo esc_view(isset($client->city)?$client->city:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">State</div>
            <div><?php echo esc_view(isset($client->state)?$client->state:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">Country</div>
            <div><?php echo esc_view(isset($client->country)?$client->country:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">GSTIN</div>
            <div><?php echo esc_view(isset($client->gstin)?$client->gstin:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">PAN</div>
            <div><?php echo esc_view(isset($client->pan_number)?$client->pan_number:''); ?></div>
          </div>
          <div class="col-md-4">
            <div class="small text-muted">Industry</div>
            <div><?php echo esc_view(isset($client->industry)?$client->industry:''); ?></div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">URL</div>
            <div>
              <?php if (!empty($client->website)): ?>
                <a href="<?php echo esc_view($client->website); ?>" target="_blank" rel="noopener">
                  <?php echo esc_view($client->website); ?>
                </a>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Demo URL</div>
            <div>
              <?php if (!empty($client->demo_url)): ?>
                <a href="<?php echo esc_view($client->demo_url); ?>" target="_blank" rel="noopener">
                  <?php echo esc_view($client->demo_url); ?>
                </a>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">POS URL</div>
            <div>
              <?php if (!empty($client->pos_url)): ?>
                <a href="<?php echo esc_view($client->pos_url); ?>" target="_blank" rel="noopener">
                  <?php echo esc_view($client->pos_url); ?>
                </a>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted">Onboarding Date</div>
            <div><?php echo esc_view(isset($client->onboarding_date)?$client->onboarding_date:''); ?></div>
          </div>
          <?php if (!empty($client->logo)): ?>
          <div class="col-md-6">
            <div class="small text-muted">Logo</div>
            <div>
              <button type="button"
                      class="btn p-0 border-0 bg-transparent js-client-logo-trigger"
                      data-bs-toggle="modal"
                      data-bs-target="#clientLogoModal"
                      data-logo-url="<?php echo esc_view(base_url($client->logo)); ?>"
                      data-client-name="<?php echo esc_view($client->company_name); ?>">
                <div style="width:64px;height:64px;border:1px solid #dee2e6;border-radius:4px;display:flex;align-items:center;justify-content:center;background:#fff;">
                  <img src="<?php echo esc_view(base_url($client->logo)); ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
                </div>
              </button>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-md-12">
            <div class="small text-muted">Notes</div>
            <div><?php echo nl2br(esc_view(isset($client->notes)?$client->notes:'')); ?></div>
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
                <td><?php echo esc_view($ct->contact_name); ?></td>
                <td><?php echo esc_view(isset($ct->designation)?$ct->designation:''); ?></td>
                <td><?php echo esc_view(isset($ct->email)?$ct->email:''); ?></td>
                <td><?php echo esc_view(isset($ct->phone)?$ct->phone:''); ?></td>
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
        <div>Created At: <?php echo esc_view(isset($client->created_at)?$client->created_at:''); ?></div>
        <div>Updated At: <?php echo esc_view(isset($client->updated_at)?$client->updated_at:''); ?></div>
        <div>DB Name: <?php echo esc_view(isset($client->db_name)?$client->db_name:''); ?></div>
        <div>DB Username: <?php echo esc_view(isset($client->db_username)?$client->db_username:''); ?></div>
        <div>DB Password: <?php echo esc_view(isset($client->db_password)?$client->db_password:''); ?></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="clientLogoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="clientLogoModalTitle">Client Logo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="clientLogoModalImg" src="" alt="Client Logo" class="img-fluid mb-3" style="max-height:400px;object-fit:contain;">
      </div>
      <div class="modal-footer">
        <a id="clientLogoDownload" href="#" class="btn btn-outline-primary" download>Download Logo</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function(){
    var imgEl = document.getElementById('clientLogoModalImg');
    var downloadEl = document.getElementById('clientLogoDownload');
    var titleEl = document.getElementById('clientLogoModalTitle');
    var triggers = document.querySelectorAll('.js-client-logo-trigger');
    triggers.forEach(function(btn){
      btn.addEventListener('click', function(){
        var url = this.getAttribute('data-logo-url') || '';
        var name = this.getAttribute('data-client-name') || '';
        imgEl.src = url;
        imgEl.alt = name || 'Client logo';
        if (titleEl){ titleEl.textContent = name ? (name + ' Logo') : 'Client Logo'; }
        if (downloadEl){
          downloadEl.href = url;
        }
      });
    });
  });
</script>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteClientModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Client</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-1">Are you sure you want to permanently delete:</p>
        <p class="fw-bold fs-6" id="deleteClientName"></p>
        <p class="text-muted small mb-0">This action cannot be undone.</p>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <form id="deleteClientForm" method="post" action="" class="d-inline">
          <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
          <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Yes, Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
function confirmDeleteClient(id, name) {
  document.getElementById('deleteClientName').textContent = name;
  document.getElementById('deleteClientForm').action = '<?php echo site_url('clients/delete/'); ?>' + id;
  new bootstrap.Modal(document.getElementById('deleteClientModal')).show();
}
</script>

<?php $this->load->view('partials/footer'); ?>
