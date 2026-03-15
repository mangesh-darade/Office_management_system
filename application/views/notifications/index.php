<?php $this->load->view('partials/header', ['title' => 'Notifications']); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-2">
      <i class="bi bi-bell me-2"></i>Notifications
    </h1>
    <p class="text-muted mb-0">Manage your notifications and alerts</p>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-primary" id="markAllRead">
      <i class="bi bi-check-all me-1"></i>Mark All Read
    </button>
    <a href="<?php echo site_url('dashboard'); ?>" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="<?php echo site_url('notifications?filter=all'); ?>">
      All
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $filter === 'unread' ? 'active' : ''; ?>" href="<?php echo site_url('notifications?filter=unread'); ?>">
      Unread
      <?php if ($unread_count > 0): ?>
        <span class="badge bg-danger"><?php echo $unread_count; ?></span>
      <?php endif; ?>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $filter === 'read' ? 'active' : ''; ?>" href="<?php echo site_url('notifications?filter=read'); ?>">
      Read
    </a>
  </li>
</ul>

<!-- Notifications List -->
<div class="card">
  <div class="card-body p-0">
    <?php if (empty($notifications)): ?>
      <div class="text-center py-5">
        <i class="bi bi-bell-slash" style="font-size: 3rem; color: #ccc;"></i>
        <p class="text-muted mt-3">No notifications found</p>
      </div>
    <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($notifications as $notification): ?>
          <div class="list-group-item notification-item <?php echo !$notification->is_read ? 'unread' : ''; ?>" data-id="<?php echo $notification->id; ?>">
            <div class="d-flex w-100 align-items-start">
              <!-- Icon based on type -->
              <div class="me-3">
                <?php
                $icon_class = 'bi-info-circle text-info';
                switch ($notification->type) {
                    case 'success':
                        $icon_class = 'bi-check-circle text-success';
                        break;
                    case 'warning':
                        $icon_class = 'bi-exclamation-triangle text-warning';
                        break;
                    case 'error':
                        $icon_class = 'bi-x-circle text-danger';
                        break;
                }
                ?>
                <i class="bi <?php echo $icon_class; ?>" style="font-size: 1.5rem;"></i>
              </div>
              
              <!-- Content -->
              <div class="flex-grow-1">
                <div class="d-flex w-100 justify-content-between">
                  <h6 class="mb-1 <?php echo !$notification->is_read ? 'fw-bold' : ''; ?>">
                    <?php echo htmlspecialchars($notification->title); ?>
                  </h6>
                  <small class="text-muted">
                    <?php
                    $time_ago = time() - strtotime($notification->created_at);
                    if ($time_ago < 60) {
                        echo 'Just now';
                    } elseif ($time_ago < 3600) {
                        echo floor($time_ago / 60) . ' min ago';
                    } elseif ($time_ago < 86400) {
                        echo floor($time_ago / 3600) . ' hours ago';
                    } else {
                        echo date('M d, Y', strtotime($notification->created_at));
                    }
                    ?>
                  </small>
                </div>
                <p class="mb-1"><?php echo htmlspecialchars($notification->message); ?></p>
                
                <?php if ($notification->module): ?>
                  <small class="text-muted">
                    <i class="bi bi-tag me-1"></i><?php echo ucfirst($notification->module); ?>
                  </small>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="mt-2">
                  <?php if ($notification->action_url): ?>
                    <a href="<?php echo htmlspecialchars($notification->action_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-arrow-right me-1"></i>View
                    </a>
                  <?php endif; ?>
                  
                  <?php if (!$notification->is_read): ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary mark-read" data-id="<?php echo $notification->id; ?>">
                      <i class="bi bi-check me-1"></i>Mark Read
                    </button>
                  <?php endif; ?>
                  
                  <button type="button" class="btn btn-sm btn-outline-danger delete-notification" data-id="<?php echo $notification->id; ?>">
                    <i class="bi bi-trash me-1"></i>Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<style>
.notification-item.unread {
  background-color: #f8f9fa;
  border-left: 3px solid #0d6efd;
}

.notification-item {
  transition: background-color 0.2s;
}

.notification-item:hover {
  background-color: #f1f3f5;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Helper: get CSRF token from cookie
    function csrfToken() {
        var m = document.cookie.match(/(?:^|;\s*)ci_csrf_token=([^;]*)/);
        return m ? decodeURIComponent(m[1]) : '';
    }
    function csrfBody(extra) {
        var base = '<?php echo $this->security->get_csrf_token_name(); ?>=' + encodeURIComponent(csrfToken());
        return extra ? base + '&' + extra : base;
    }

    // Mark all as read
    document.getElementById('markAllRead')?.addEventListener('click', function() {
        if (!confirm('Mark all notifications as read?')) return;
        fetch('<?php echo site_url('notifications/mark-all-read'); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: csrfBody()
        })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
    });

    // Mark single as read
    document.querySelectorAll('.mark-read').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            fetch(`<?php echo site_url('notifications'); ?>/${id}/mark-read`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: csrfBody()
            })
            .then(r => r.json())
            .then(data => { if (data.success) location.reload(); });
        });
    });

    // Delete notification
    document.querySelectorAll('.delete-notification').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this notification?')) return;
            const id = this.dataset.id;
            fetch(`<?php echo site_url('notifications'); ?>/${id}/delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: csrfBody()
            })
            .then(r => r.json())
            .then(data => { if (data.success) location.reload(); });
        });
    });
});
</script>

<?php $this->load->view('partials/footer'); ?>
