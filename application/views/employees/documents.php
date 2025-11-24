<?php $this->load->view('partials/header', ['title' => 'Employee Documents']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Employee Documents - #<?php echo (int)$employee->id; ?> <?php echo htmlspecialchars(trim((isset($employee->first_name) ? $employee->first_name : '').' '.(isset($employee->last_name) ? $employee->last_name : ''))); ?></h1>
    <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
      <a class="btn btn-secondary w-100 w-sm-auto" href="<?php echo site_url('employees/'.$employee->id); ?>">Back to Profile</a>
    </div>
  </div>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card h-100 shadow-soft"><div class="card-body">
        <h5 class="card-title">Upload Document</h5>
        <form method="post" enctype="multipart/form-data" class="vstack gap-3">
          <div>
            <label class="form-label">Document Type</label>
            <input type="text" name="doc_type" class="form-control" placeholder="Aadhar, PAN, Offer Letter, etc.">
          </div>
          <div>
            <label class="form-label">File <span class="text-danger">*</span></label>
            <input type="file" name="document" class="form-control" required>
          </div>
          <div>
            <button class="btn btn-primary">Upload</button>
          </div>
        </form>
      </div></div>
    </div>
    <div class="col-md-8">
      <div class="card h-100 shadow-soft"><div class="card-body">
        <h5 class="card-title">Documents List</h5>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Type</th>
                <th>File</th>
                <th>Size (KB)</th>
                <th>Uploaded At</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!empty($documents)): foreach ($documents as $d): ?>
              <tr>
                <td><?php echo (int)$d->id; ?></td>
                <td><?php echo htmlspecialchars(isset($d->doc_type) ? $d->doc_type : ''); ?></td>
                <td>
                  <a href="<?php echo site_url('employees/documents/'.(int)$d->id.'/download'); ?>">
                    <?php
                      $label = isset($d->original_name) && $d->original_name !== '' ? $d->original_name : (isset($d->file_path) ? basename($d->file_path) : '');
                      echo htmlspecialchars($label);
                    ?>
                  </a>
                </td>
                <td>
                  <?php
                    $kb = isset($d->file_size) ? (int)$d->file_size : 0;
                    if ($kb > 0) {
                        echo number_format($kb / 1024, 2);
                    }
                  ?>
                </td>
                <td><?php echo htmlspecialchars(isset($d->uploaded_at) ? $d->uploaded_at : ''); ?></td>
                <td class="text-end">
                  <?php $roleId = (int)$this->session->userdata('role_id'); ?>
                  <?php if (in_array($roleId, [1,2], true)): ?>
                    <form method="post" action="<?php echo site_url('employees/documents/'.(int)$d->id.'/delete'); ?>" class="d-inline" onsubmit="return confirm('Delete this document?');">
                      <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr class="no-data">
                <td colspan="6" class="text-center text-muted py-4">No documents uploaded yet.</td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div></div>
    </div>
  </div>
<?php $this->load->view('partials/footer'); ?>
