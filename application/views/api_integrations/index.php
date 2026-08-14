<?php $this->load->view('partials/header', ['title' => 'API Integrations']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-plug me-2"></i>API Integrations
  </h1>
  <a href="<?php echo site_url('api-integrations/create'); ?>" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>Add Integration
  </a>
</div>

<?php
$flash_error = $this->session->flashdata('error');
$flash_success = $this->session->flashdata('success');
$flash_warning = $this->session->flashdata('warning');
?>
<?php if ($flash_error): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle me-1"></i><?php echo esc_view($flash_error); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($flash_warning): ?>
  <div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-circle me-1"></i><?php echo esc_view($flash_warning); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($flash_success): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-1"></i><?php echo esc_view($flash_success); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
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
                      'smtp' => 'bi-mailbox',
                      'jitsi' => 'bi-camera-video'
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
                  <strong><?php echo esc_view($int->service_name); ?></strong>
                  <?php if ($int->notes): ?>
                    <br><small class="text-muted"><?php echo esc_view(mb_substr($int->notes, 0, 50)); ?>...</small>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($int->account_id): ?>
                    <code><?php echo esc_view(mb_substr($int->account_id, 0, 30)); ?><?php echo mb_strlen($int->account_id) > 30 ? '...' : ''; ?></code>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php 
                  $from = $int->from_email ?: $int->from_number;
                  if ($from): 
                  ?>
                    <code><?php echo esc_view($from); ?></code>
                    <?php if ($int->from_name): ?>
                      <br><small class="text-muted"><?php echo esc_view($int->from_name); ?></small>
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
                <td class="text-end text-nowrap">
                  <div class="d-inline-flex gap-1 align-items-center justify-content-end">
                    <?php if ($int->service_type === 'whatsapp'): ?>
                      <?php echo form_open('api-integrations/test-whatsapp', array('class' => 'd-inline')); ?>
                        <input type="hidden" name="id" value="<?php echo (int) $int->id; ?>">
                        <button type="submit" class="btn btn-outline-success btn-sm" title="Test connection"><i class="bi bi-plug"></i></button>
                      <?php echo form_close(); ?>
                    <?php endif; ?>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Actions">
                      <a class="btn btn-outline-primary" href="<?php echo site_url('api-integrations/edit/' . $int->id); ?>" title="Edit">
                        <i class="bi bi-pencil"></i>
                      </a>
                      <form method="post" action="<?php echo site_url('api-integrations/delete/' . $int->id); ?>" class="d-inline" onsubmit="return confirm('Delete this API integration? This action cannot be undone.');">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
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

