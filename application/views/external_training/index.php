<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/header', array(
  'title' => 'External Trainings',
  'with_sidebar' => true,
));
$canAdd = function_exists('has_module_access') && (has_module_access('external_training') || has_module_access('external_training_add'));
$canEdit = function_exists('has_module_access') && (has_module_access('external_training') || has_module_access('external_training_edit'));
$canDelete = function_exists('has_module_access') && (has_module_access('external_training') || has_module_access('external_training_delete'));
?>
<div class="container-fluid py-3">
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

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:60px;">ID</th>
              <th>Name</th>
              <th>Active</th>
              <th>Embed link</th>
              <th>Created</th>
              <th style="width:200px;">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?php echo (int) $r->id; ?></td>
                <td><?php echo htmlspecialchars($r->name); ?></td>
                <td>
                  <?php if ((int) $r->is_active === 1): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                  $code = (string) $r->embed_code;
                  $display = (strlen($code) > 60) ? substr($code, 0, 57) . '...' : $code;
                  // Try to extract a URL from the embed code (handles raw URL or iframe HTML).
                  $url = '';
                  if (preg_match('~https?://[^\s"\']+~i', $code, $m)) {
                    $url = $m[0];
                  }
                  ?>
                  <div class="input-group input-group-sm">
                    <input type="text"
                           class="form-control form-control-sm"
                           readonly
                           value="<?php echo htmlspecialchars($code); ?>"
                           onclick="this.select();">
                    <button type="button"
                            class="btn btn-outline-secondary btn-sm et-copy-btn"
                            data-code="<?php echo htmlspecialchars($code); ?>">
                      Copy
                    </button>
                  </div>
                  <div class="small text-muted mt-1" title="<?php echo htmlspecialchars($code); ?>">
                    <?php if ($url): ?>
                      <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener">
                        <?php echo htmlspecialchars($display); ?>
                      </a>
                    <?php else: ?>
                        <?php echo htmlspecialchars($display); ?>
                    <?php endif; ?>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($r->created_at); ?></td>
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
              <td colspan="6" class="text-center text-muted py-4">No external trainings found.</td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script>
(function() {
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.et-copy-btn');
    if (!btn) return;
    var code = btn.getAttribute('data-code') || '';
    if (!code) return;
    try {
      navigator.clipboard.writeText(code).then(function() {
        btn.textContent = 'Copied';
        setTimeout(function() { btn.textContent = 'Copy'; }, 1500);
      }).catch(function() {
        var tmp = document.createElement('textarea');
        tmp.style.position = 'fixed';
        tmp.style.opacity = '0';
        tmp.value = code;
        document.body.appendChild(tmp);
        tmp.select();
        try { document.execCommand('copy'); } catch (e2) {}
        document.body.removeChild(tmp);
        btn.textContent = 'Copied';
        setTimeout(function() { btn.textContent = 'Copy'; }, 1500);
      });
    } catch (e) {}
  });
})();
</script>
<?php $this->load->view('partials/footer'); ?>

