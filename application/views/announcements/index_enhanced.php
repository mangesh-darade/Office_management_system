<?php $this->load->view('partials/header', ['title' => 'Announcements']); ?>
<style>
.announcements-dashboard .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.announcements-dashboard .table { border-radius: 8px; }
.announcements-dashboard .btn { border-radius: 8px; font-weight: 500; }
.announcements-dashboard .badge { border-radius: 6px; font-weight: 500; }
.announcement-row { transition: background-color 0.2s; }
.announcement-row:hover { background-color: #f8fafc; }
.priority-high { border-left: 4px solid #ef4444; }
.priority-medium { border-left: 4px solid #f59e0b; }
.priority-low { border-left: 4px solid #10b981; }
.status-scheduled { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
.status-published { background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); }
.status-draft { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100% ); }
.status-expired { background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); }
</style>

<div class="announcements-dashboard">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">📢 Announcements</h1>
  <div class="d-flex gap-2">
    <?php if (!empty($can_manage)): ?>
      <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('announcements/process_scheduled'); ?>">
        <i class="bi bi-clock-history me-1"></i>Process Scheduled
      </a>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('announcements/create'); ?>">
        <i class="bi bi-plus-circle me-1"></i>Create Announcement
      </a>
    <?php endif; ?>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<!-- Quick Stats -->
<?php if (!empty($rows)): ?>
<?php
$stats = [
    'total' => count($rows),
    'published' => count(array_filter($rows, fn($r) => $r->status === 'published')),
    'scheduled' => count(array_filter($rows, fn($r) => $r->status === 'scheduled')),
    'draft' => count(array_filter($rows, fn($r) => $r->status === 'draft')),
    'expired' => count(array_filter($rows, fn($r) => $r->status === 'expired')),
];
?>
<div class="row g-3 mb-4">
  <div class="col-md-2">
    <div class="card text-center">
      <div class="card-body py-2">
        <h5 class="mb-0"><?php echo $stats['total']; ?></h5>
        <small class="text-muted">Total</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center bg-success bg-opacity-10">
      <div class="card-body py-2">
        <h5 class="mb-0 text-success"><?php echo $stats['published']; ?></h5>
        <small class="text-muted">Published</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center bg-warning bg-opacity-10">
      <div class="card-body py-2">
        <h5 class="mb-0 text-warning"><?php echo $stats['scheduled']; ?></h5>
        <small class="text-muted">Scheduled</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center bg-secondary bg-opacity-10">
      <div class="card-body py-2">
        <h5 class="mb-0 text-secondary"><?php echo $stats['draft']; ?></h5>
        <small class="text-muted">Draft</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center bg-danger bg-opacity-10">
      <div class="card-body py-2">
        <h5 class="mb-0 text-danger"><?php echo $stats['expired']; ?></h5>
        <small class="text-muted">Expired</small>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card text-center bg-info bg-opacity-10">
      <div class="card-body py-2">
        <h5 class="mb-0 text-info"><?php echo count(array_filter($rows, fn($r) => $r->is_recurring)); ?></h5>
        <small class="text-muted">Recurring</small>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
  <div class="card-body">
    <form class="row g-3" method="get">
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="">All Status</option>
          <option value="draft" <?php echo (isset($filters['status']) && $filters['status']==='draft')?'selected':''; ?>>📝 Draft</option>
          <option value="published" <?php echo (isset($filters['status']) && $filters['status']==='published')?'selected':''; ?>>✅ Published</option>
          <option value="scheduled" <?php echo (isset($filters['status']) && $filters['status']==='scheduled')?'selected':''; ?>>⏰ Scheduled</option>
          <option value="expired" <?php echo (isset($filters['status']) && $filters['status']==='expired')?'selected':''; ?>>📦 Expired</option>
          <option value="archived" <?php echo (isset($filters['status']) && $filters['status']==='archived')?'selected':''; ?>>📁 Archived</option>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Search</label>
        <input class="form-control" name="q" value="<?php echo htmlspecialchars(isset($filters['q']) ? $filters['q'] : ''); ?>" placeholder="Title or content..." />
      </div>
      <div class="col-md-3 align-self-end">
        <button class="btn btn-outline-secondary w-100">
          <i class="bi bi-funnel me-1"></i>Filter
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Announcements Table -->
<div class="card">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>Title & Content</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Schedule</th>
            <th>Target</th>
            <th style="width:150px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                <i class="bi bi-megaphone" style="font-size: 2rem;"></i>
                <div class="mt-2">No announcements found.</div>
                <?php if (!empty($can_manage)): ?>
                  <a href="<?php echo site_url('announcements/create'); ?>" class="btn btn-primary btn-sm mt-2">
                    <i class="bi bi-plus-circle me-1"></i>Create First Announcement
                  </a>
                <?php endif; ?>
              </td>
            </tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="announcement-row priority-<?php echo $r->priority; ?>">
              <td>
                <div class="d-flex align-items-start">
                  <div class="flex-grow-1">
                    <div class="fw-semibold"><?php echo htmlspecialchars($r->title); ?></div>
                    <?php if ($r->is_recurring): ?>
                      <span class="badge bg-purple bg-opacity-20 text-purple mb-1">
                        <i class="bi bi-arrow-repeat me-1"></i>Recurring
                      </span>
                    <?php endif; ?>
                    <div class="text-muted small" style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                      <?php echo htmlspecialchars(substr($r->content, 0, 100) . (strlen($r->content) > 100 ? '...' : '')); ?>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <?php
                $priority_colors = [
                    'low' => 'success',
                    'medium' => 'warning', 
                    'high' => 'danger'
                ];
                $priority_icons = [
                    'low' => '🟢',
                    'medium' => '🟡',
                    'high' => '🔴'
                ];
                $color = $priority_colors[$r->priority] ?? 'secondary';
                $icon = $priority_icons[$r->priority] ?? '⚪';
                ?>
                <span class="badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?> border">
                  <?php echo $icon; ?> <?php echo ucfirst($r->priority); ?>
                </span>
              </td>
              <td>
                <?php
                $status_colors = [
                    'draft' => 'secondary',
                    'published' => 'success',
                    'scheduled' => 'warning',
                    'expired' => 'danger',
                    'archived' => 'info'
                ];
                $status_icons = [
                    'draft' => '📝',
                    'published' => '✅',
                    'scheduled' => '⏰',
                    'expired' => '📦',
                    'archived' => '📁'
                ];
                $color = $status_colors[$r->status] ?? 'secondary';
                $icon = $status_icons[$r->status] ?? '📄';
                ?>
                <span class="badge bg-<?php echo $color; ?> bg-opacity-10 text-<?php echo $color; ?> border">
                  <?php echo $icon; ?> <?php echo ucfirst($r->status); ?>
                </span>
              </td>
              <td>
                <div class="small">
                  <?php if ($r->publish_at): ?>
                    <div class="text-muted">
                      <i class="bi bi-clock me-1"></i>
                      <?php echo date('M j, Y H:i', strtotime($r->publish_at)); ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($r->expire_at): ?>
                    <div class="text-muted">
                      <i class="bi bi-x-circle me-1"></i>
                      <?php echo date('M j, Y H:i', strtotime($r->expire_at)); ?>
                    </div>
                  <?php endif; ?>
                  <?php if (!$r->publish_at && !$r->expire_at): ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <?php
                $target_labels = [
                    'all' => '👥 All',
                    '1' => '👔 Admin',
                    '2' => '👨‍💼 Manager',
                    '1,2' => '🎯 Admin+Mgr'
                ];
                $label = $target_labels[$r->target_roles] ?? '👥 All';
                ?>
                <span class="badge bg-light text-dark"><?php echo $label; ?></span>
              </td>
              <td>
                <?php if (!empty($can_manage)): ?>
                  <div class="btn-group btn-group-sm" role="group">
                    <a class="btn btn-outline-primary" href="<?php echo site_url('announcements/'.(int)$r->id.'/edit'); ?>" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <?php if ($r->status === 'published'): ?>
                      <button class="btn btn-outline-success" onclick="duplicateAnnouncement(<?php echo (int)$r->id; ?>)" title="Duplicate">
                        <i class="bi bi-copy"></i>
                      </button>
                    <?php endif; ?>
                    <a class="btn btn-outline-danger" onclick="return confirm('Delete this announcement?')" href="<?php echo site_url('announcements/'.(int)$r->id.'/delete'); ?>" title="Delete">
                      <i class="bi bi-trash"></i>
                    </a>
                  </div>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<script>
function duplicateAnnouncement(id) {
    if (confirm('Create a copy of this announcement?')) {
        // This would need to be implemented as a new controller method
        window.location.href = '<?php echo site_url('announcements/duplicate/'); ?>' + id;
    }
}

// Auto-refresh for scheduled announcements
setInterval(function() {
    const scheduledCount = document.querySelectorAll('.badge:contains("Scheduled")').length;
    if (scheduledCount > 0) {
        // Optionally refresh the page to process scheduled announcements
        // window.location.reload();
    }
}, 60000); // Check every minute
</script>

<?php $this->load->view('partials/footer'); ?>
