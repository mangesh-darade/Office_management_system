<?php $this->load->view('partials/header', ['title' => 'Requirement Details']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Requirement: <?php echo htmlspecialchars(isset($req->req_number)?$req->req_number:'#'.(int)$req->id); ?></h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('requirements/edit/'.(int)$req->id); ?>">Edit</a>
    <a class="btn btn-light btn-sm" href="<?php echo site_url('requirements'); ?>">Back</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card shadow-soft mb-3">
      <div class="card-body">
        <h5 class="mb-1"><?php echo htmlspecialchars($req->title); ?></h5>
        <div class="mb-2 text-muted small">
          <span class="me-3">Client: <?php echo htmlspecialchars(isset($req->client_name)?$req->client_name:''); ?></span>
          <span class="me-3">Status: <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(isset($req->status)?$req->status:'received'); ?></span></span>
          <span>Priority: <span class="badge bg-secondary"><?php echo htmlspecialchars(isset($req->priority)?$req->priority:'medium'); ?></span></span>
        </div>
        <?php if (isset($req->description) && $req->description !== ''): ?>
        <div class="border rounded p-3 bg-white">
          <?php echo $req->description; ?>
        </div>
        <?php else: ?>
        <div class="text-muted small">No description provided.</div>
        <?php endif; ?>
        <div class="row mt-3 small">
          <div class="col-md-6">
            <div class="text-muted">Expected Delivery</div>
            <div><?php echo htmlspecialchars(isset($req->expected_delivery_date)?$req->expected_delivery_date:''); ?></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted">Assigned To</div>
            <div><?php echo htmlspecialchars(isset($req->assigned_to_name)?$req->assigned_to_name:'Unassigned'); ?></div>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-soft">
      <div class="card-header"><h6 class="mb-0">Attachments</h6></div>
      <div class="card-body">
        <?php if (empty($attachments)): ?>
          <div class="text-muted small">No attachments.</div>
        <?php else: ?>
        <div class="list-group">
          <?php foreach ($attachments as $a): ?>
          <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?php echo base_url($a->file_path); ?>" download>
            <div>
              <i class="bi bi-file-earmark me-2"></i>
              <strong><?php echo htmlspecialchars(isset($a->original_name)?$a->original_name:$a->file_name); ?></strong>
              <?php if (isset($a->file_size)): ?><small class="text-muted"> (<?php echo (int)$a->file_size; ?> KB)</small><?php endif; ?>
            </div>
            <i class="bi bi-download"></i>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card shadow-soft mt-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Version History</h6>
        <form method="get" action="<?php echo site_url('requirements/view/'.(int)$req->id); ?>" class="d-flex align-items-center gap-2">
          <?php $curType = isset($type_filter) ? (string)$type_filter : ''; ?>
          <label class="small text-muted me-2">Filter by type</label>
          <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <?php $types = array('', 'new_feature','enhancement','bug_fix','maintenance','consultation','other','announcement');
              foreach ($types as $t): ?>
              <option value="<?php echo htmlspecialchars($t); ?>" <?php echo ($curType===$t)?'selected':''; ?>>
                <?php echo $t===''?'All':ucfirst(str_replace('_',' ',$t)); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <div class="card-body">
        <?php if (!isset($versions) || empty($versions)): ?>
          <div class="text-muted small">No versions yet.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>Version</th>
                <th>Title</th>
                <th>Status</th>
                <th>Priority</th>
                <th>Created At</th>
                <th>By</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($versions as $v): ?>
              <tr>
                <td><?php echo (int)$v->version_no; ?></td>
                <td><?php echo htmlspecialchars(isset($v->title)?$v->title:''); ?></td>
                <td><?php echo htmlspecialchars(isset($v->status)?$v->status:''); ?></td>
                <td><?php echo htmlspecialchars(isset($v->priority)?$v->priority:''); ?></td>
                <td><?php echo htmlspecialchars(isset($v->created_at)?$v->created_at:''); ?></td>
                <td><?php echo htmlspecialchars(isset($v->created_by)?$v->created_by:''); ?></td>
                <td><a class="btn btn-light btn-sm" href="<?php echo site_url('requirements/version/'.(int)$v->id); ?>">View</a></td>
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
      <div class="card-header"><h6 class="mb-0">Meta</h6></div>
      <div class="card-body small text-muted">
        <div>Received: <?php echo htmlspecialchars(isset($req->received_date)?$req->received_date:''); ?></div>
        <div>Created: <?php echo htmlspecialchars(isset($req->created_at)?$req->created_at:''); ?></div>
        <div>Updated: <?php echo htmlspecialchars(isset($req->updated_at)?$req->updated_at:''); ?></div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
