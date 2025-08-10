<?php $this->load->view('partials/header', ['title' => 'Permission Manager']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Permission Manager</h1>
  <div>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('dashboard'); ?>">Back to Dashboard</a>
  </div>
</div>

<div class="alert alert-info">
  <i class="bi bi-shield-lock me-2"></i>
  Configure which roles can access each module/screen. Changes apply immediately after save.
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('permissions/save'); ?>">
      <div class="table-responsive">
        <table class="table table-striped align-middle">
          <thead>
            <tr>
              <th>Module</th>
              <?php foreach($roles as $rid => $rname): ?>
                <th class="text-center"><?php echo htmlspecialchars($rname); ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach($modules as $key => $label): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($label); ?></strong><div class="text-muted small"><?php echo htmlspecialchars($key); ?></div></td>
                <?php foreach($roles as $rid => $rname): ?>
                  <?php $checked = isset($existing[$rid][$key]) ? ((int)$existing[$rid][$key] === 1) : false; ?>
                  <td class="text-center">
                    <input class="form-check-input" type="checkbox" name="perms[<?php echo (int)$rid; ?>][<?php echo htmlspecialchars($key); ?>]" value="1" <?php echo $checked ? 'checked' : ''; ?>>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Save Permissions</button>
      </div>
    </form>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
