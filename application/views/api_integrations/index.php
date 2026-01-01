<?php $this->load->view('partials/header', ['title' => 'API Integrations']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-plug me-2"></i>API Integrations
  </h1>
  <a href="<?php echo site_url('api-integrations/create'); ?>" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>Add Integration
  </a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-body">
    <?php if (empty($integrations)): ?>
      <div class="text-center py-5">
        <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-3">No API integrations configured yet.</p>
        <a href="<?php echo site_url('api-integrations/create'); ?>" class="btn btn-primary">
          <i class="bi bi-plus-circle me-1"></i>Add First Integration
        </a>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Service</th>
              <th>Name</th>
              <th>Account ID/Key</th>
              <th>From Email/Number</th>
              <th>Status</th>
              <th>Default</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $current_type = '';
            foreach ($integrations as $int): 
              if ($current_type !== $int->service_type):
                $current_type = $int->service_type;
            ?>
              <tr class="table-secondary">
                <td colspan="7">
                  <strong class="text-uppercase">
                    <?php 
                    $icons = [
                      'sendgrid' => 'bi-envelope',
                      'whatsapp' => 'bi-whatsapp',
                      'smtp' => 'bi-mailbox'
                    ];
                    $icon = isset($icons[$int->service_type]) ? $icons[$int->service_type] : 'bi-gear';
                    ?>
                    <i class="bi <?php echo $icon; ?> me-2"></i><?php echo ucfirst($int->service_type); ?>
                  </strong>
                </td>
              </tr>
            <?php endif; ?>
              <tr>
                <td>
                  <span class="badge bg-info"><?php echo strtoupper($int->service_type); ?></span>
                </td>
                <td>
                  <strong><?php echo htmlspecialchars($int->service_name); ?></strong>
                  <?php if ($int->notes): ?>
                    <br><small class="text-muted"><?php echo htmlspecialchars(mb_substr($int->notes, 0, 50)); ?>...</small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($int->account_id): ?>
                    <code><?php echo htmlspecialchars(mb_substr($int->account_id, 0, 30)); ?><?php echo mb_strlen($int->account_id) > 30 ? '...' : ''; ?></code>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php 
                  $from = $int->from_email ?: $int->from_number;
                  if ($from): 
                  ?>
                    <code><?php echo htmlspecialchars($from); ?></code>
                    <?php if ($int->from_name): ?>
                      <br><small class="text-muted"><?php echo htmlspecialchars($int->from_name); ?></small>
                    <?php endif; ?>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($int->is_active): ?>
                    <span class="badge bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($int->is_default): ?>
                    <span class="badge bg-primary">Default</span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <a class="btn btn-outline-secondary" href="<?php echo site_url('api-integrations/edit/' . $int->id); ?>" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <a class="btn btn-outline-danger" href="<?php echo site_url('api-integrations/delete/' . $int->id); ?>" 
                       onclick="return confirm('Delete this API integration? This action cannot be undone.');" title="Delete">
                      <i class="bi bi-trash"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>

