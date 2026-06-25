<?php if (!empty($scope) && !empty($scope['message'])): ?>
  <div class="alert alert-<?php echo esc_view(isset($scope['alert_class']) ? $scope['alert_class'] : 'info'); ?> py-2 small mb-3 d-flex align-items-start gap-2">
    <i class="bi bi-shield-check mt-1 flex-shrink-0"></i>
    <div>
      <?php echo esc_view($scope['message']); ?>
      <?php if (!empty($scope['role_label'])): ?>
        <span class="text-muted">(Role: <?php echo esc_view(ucfirst($scope['role_label'])); ?>)</span>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
