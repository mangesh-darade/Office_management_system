<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/header', array(
  'title' => 'External Trainings',
  'with_sidebar' => true,
  'extra_css' => array('assets/css/lms-ui.css'),
));
$canAdd = function_exists('has_module_access') && (has_module_access('external_training') || has_module_access('external_training_add'));
$canEdit = function_exists('has_module_access') && (has_module_access('external_training') || has_module_access('external_training_edit'));
$canDelete = function_exists('has_module_access') && (has_module_access('external_training') || has_module_access('external_training_delete'));
?>
<div class="container-fluid py-3">
  <style>
    .et-wrap { background:#f8fafc; border-radius:12px; padding:16px; }
    .et-card { border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 8px 18px rgba(15,23,42,.06); background:#fff; }
    .et-card:hover { box-shadow:0 10px 20px rgba(15,23,42,.08); }
    @media (max-width: 991.98px) {
      .et-table-wrap { display:none; }
      .et-mobile { display:block !important; }
    }
    @media (min-width: 992px) { .et-mobile { display:none !important; } }
  </style>
  <div class="et-wrap">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">External Trainings</h1>
    <?php if ($canAdd): ?>
    <a href="<?php echo site_url('external-training/create'); ?>" class="btn btn-primary">
      <i class="bi bi-plus-lg"></i> Add External Training
    </a>
    <?php endif; ?>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo $this->session->flashdata('success'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo $this->session->flashdata('error'); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="card et-card et-table-wrap">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:60px;">ID</th>
              <th>Title</th>
              <th>Provider</th>
              <th>Duration</th>
              <th>Active</th>
              <th>Play in app</th>
              <th>Certificate</th>
              <th>Created</th>
              <th style="width:200px;">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?php echo (int) $r->id; ?></td>
                <td><?php echo esc_view($r->name); ?></td>
                <td>External</td>
                <td>Self-paced</td>
                <td>
                  <?php if ((int) $r->is_active === 1): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <a href="<?php echo site_url('external-training/watch/' . (int) $r->id); ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-play-circle me-1"></i>Open (signed in)
                  </a>
                  <div class="small text-muted mt-1">URL/embed text is hidden on this list to reduce sharing.</div>
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Certificate upload UI placeholder">
                    <i class="bi bi-upload me-1"></i>Upload
                  </button>
                </td>
                <td><?php echo esc_view($r->created_at); ?></td>
                <td>
                  <?php if ($canEdit): ?>
                  <a href="<?php echo site_url('external-training/edit/' . (int) $r->id); ?>" class="btn btn-sm btn-outline-primary me-1">
                    Edit
                  </a>
                  <?php endif; ?>
                  <?php if ($canDelete): ?>
                  <?php echo form_open('external-training/delete/' . (int) $r->id, array('class' => 'd-inline-block', 'onsubmit' => "return confirm('Delete this external training?');")); ?>
                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                  <?php echo form_close(); ?>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">No external trainings found.</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="et-mobile mt-3">
    <?php if (!empty($rows)): foreach ($rows as $r): ?>
    <div class="card et-card p-3 mb-2">
      <div class="fw-semibold"><?php echo esc_view($r->name); ?></div>
      <div class="small text-muted">Provider: External · Duration: Self-paced</div>
      <div class="small text-muted">Created: <?php echo esc_view($r->created_at); ?></div>
      <div class="mt-2 d-flex flex-wrap gap-2">
        <a href="<?php echo site_url('external-training/watch/' . (int) $r->id); ?>" class="btn btn-sm btn-primary"><i class="bi bi-play-circle me-1"></i>Open</a>
        <button type="button" class="btn btn-sm btn-outline-secondary" disabled><i class="bi bi-upload me-1"></i>Upload certificate</button>
      </div>
    </div>
    <?php endforeach; else: ?>
      <div class="card et-card p-4 text-center text-muted">No external trainings found.</div>
    <?php endif; ?>
  </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>

