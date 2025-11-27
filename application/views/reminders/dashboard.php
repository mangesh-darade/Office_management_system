<?php $this->load->view('partials/header', ['title' => 'Reminders Dashboard']); ?>
<style>
.reminders-dashboard .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.reminders-dashboard .metric-card { 
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
  color: white; 
  border-radius: 12px; 
  padding: 1.5rem; 
  margin-bottom: 1rem;
  transition: transform 0.3s ease;
}
.reminders-dashboard .metric-card:hover { transform: translateY(-5px); }
.reminders-dashboard .metric-value { font-size: 2rem; font-weight: 700; }
.reminders-dashboard .metric-label { font-size: 0.875rem; opacity: 0.9; }
.reminders-dashboard .btn { border-radius: 8px; font-weight: 500; }
.reminders-dashboard .nav-pills .nav-link { border-radius: 8px; margin-bottom: 0.5rem; }
.reminders-dashboard .table { border-radius: 8px; }
.reminders-dashboard .status-badge { border-radius: 6px; font-weight: 500; }
.reminders-dashboard .priority-high { border-left: 4px solid #ef4444; }
.reminders-dashboard .priority-medium { border-left: 4px solid #f59e0b; }
.reminders-dashboard .priority-low { border-left: 4px solid #10b981; }

/* Mobile responsive adjustments */
@media (max-width: 768px) {
  .reminders-dashboard .metric-card {
    padding: 1rem;
  }
  .reminders-dashboard .metric-value {
    font-size: 1.5rem;
  }
  .reminders-dashboard .btn-group .btn {
    font-size: 0.875rem;
    padding: 0.375rem 0.625rem;
  }
  .reminders-dashboard .d-flex.justify-content-between {
    flex-direction: column;
    gap: 1rem;
  }
  .reminders-dashboard .d-flex.justify-content-between .d-flex {
    width: 100%;
    justify-content: center;
  }
  .reminders-dashboard .row.g-2 .col-md-3 {
    margin-bottom: 0.5rem;
  }
}
@media (max-width: 576px) {
  .reminders-dashboard .table-responsive {
    font-size: 0.875rem;
  }
  .reminders-dashboard .table th,
  .reminders-dashboard .table td {
    padding: 0.5rem;
    vertical-align: middle;
  }
  .reminders-dashboard .table .btn-group .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
  }
  .reminders-dashboard .badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
  .reminders-dashboard .rounded-circle {
    width: 28px !important;
    height: 28px !important;
    font-size: 0.65rem !important;
  }
  .reminders-dashboard .fw-semibold {
    font-weight: 600;
  }
  .reminders-dashboard .text-muted {
    font-size: 0.75rem;
  }
  .reminders-dashboard .card-footer .d-flex {
    flex-direction: column;
    text-align: center;
  }
  .reminders-dashboard .pagination {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>

<div class="reminders-dashboard">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">📧 Reminders Dashboard</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('reminders/schedules'); ?>">
      <i class="bi bi-calendar-check me-1"></i>Schedules
    </a>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('reminders/send'); ?>">
      <i class="bi bi-send me-1"></i>Send Reminder
    </a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<!-- Quick Stats -->
<?php
$stats = [
    'queued' => 0,
    'sent' => 0,
    'error' => 0,
    'scheduled' => 0
];
if (!empty($rows)) {
    foreach ($rows as $r) {
        $status = isset($r->status) ? $r->status : 'queued';
        if (isset($stats[$status])) {
            $stats[$status]++;
        }
    }
}
// Get scheduled count from schedules table
$scheduled_count = 0;
if ($this->db->table_exists('reminder_schedules')) {
    $scheduled_count = $this->db->where('active', 1)->count_all_results('reminder_schedules');
}
$stats['scheduled'] = $scheduled_count;
?>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="metric-card">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <div class="metric-value"><?php echo $stats['queued']; ?></div>
          <div class="metric-label">📋 Queued</div>
        </div>
        <div style="font-size: 2rem;">⏳</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="metric-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <div class="metric-value"><?php echo $stats['sent']; ?></div>
          <div class="metric-label">✅ Sent</div>
        </div>
        <div style="font-size: 2rem;">📤</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="metric-card" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <div class="metric-value"><?php echo $stats['error']; ?></div>
          <div class="metric-label">❌ Failed</div>
        </div>
        <div style="font-size: 2rem;">⚠️</div>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="metric-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <div class="metric-value"><?php echo $stats['scheduled']; ?></div>
          <div class="metric-label">🗓️ Scheduled</div>
        </div>
        <div style="font-size: 2rem;">📅</div>
      </div>
    </div>
  </div>
</div>

<!-- Quick Actions -->
<div class="card mb-4">
  <div class="card-header bg-white">
    <h5 class="mb-0">🚀 Quick Actions</h5>
  </div>
  <div class="card-body">
    <div class="row g-2">
      <div class="col-md-3">
        <a href="<?php echo site_url('reminders/cron/morning'); ?>" class="btn btn-info w-100">
          <i class="bi bi-sunrise me-2"></i>Queue Morning Reminders
        </a>
      </div>
      <div class="col-md-3">
        <a href="<?php echo site_url('reminders/cron/night'); ?>" class="btn btn-warning w-100">
          <i class="bi bi-moon me-2"></i>Queue Night Reminders
        </a>
      </div>
      <div class="col-md-3">
        <a href="<?php echo site_url('reminders/cron/send-queue'); ?>" class="btn btn-success w-100">
          <i class="bi bi-send-check me-2"></i>Send Queued Emails
        </a>
      </div>
      <div class="col-md-3">
        <a href="<?php echo site_url('reminders/cron/generate-today'); ?>" class="btn btn-primary w-100">
          <i class="bi bi-calendar-day me-2"></i>Generate Today's Schedule
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Activity -->
<div class="card">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h5 class="mb-0">📋 Recent Activity</h5>
    <div class="btn-group btn-group-sm">
      <a class="btn btn-outline-secondary <?php echo empty($current_filter) ? 'active' : ''; ?>" href="<?php echo site_url('reminders/dashboard'); ?>">All</a>
      <a class="btn btn-outline-secondary <?php echo $current_filter === 'queued' ? 'active' : ''; ?>" href="<?php echo site_url('reminders/dashboard?filter=queued'); ?>">Queued</a>
      <a class="btn btn-outline-secondary <?php echo $current_filter === 'sent' ? 'active' : ''; ?>" href="<?php echo site_url('reminders/dashboard?filter=sent'); ?>">Sent</a>
      <a class="btn btn-outline-secondary <?php echo $current_filter === 'error' ? 'active' : ''; ?>" href="<?php echo site_url('reminders/dashboard?filter=error'); ?>">Failed</a>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>User</th>
            <th>Subject</th>
            <th>Type</th>
            <th>Status</th>
            <th>Scheduled</th>
            <th>Sent</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">
                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                <div class="mt-2">No reminders found</div>
                <a href="<?php echo site_url('reminders/send'); ?>" class="btn btn-primary btn-sm mt-2">
                  <i class="bi bi-plus-circle me-1"></i>Create First Reminder
                </a>
              </td>
            </tr>
          <?php else: 
          foreach ($rows as $r): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; font-size: 0.75rem;">
                    <?php 
                    $label = '';
                    if (isset($r->full_label) && $r->full_label!=='') { $label = $r->full_label; }
                    else if (isset($r->full_name) && $r->full_name!=='') { $label = $r->full_name; }
                    else if (isset($r->name) && $r->name!=='') { $label = $r->name; }
                    echo strtoupper(substr($label, 0, 2));
                    ?>
                  </div>
                  <div>
                    <div class="fw-semibold"><?php echo htmlspecialchars($label); ?></div>
                    <small class="text-muted"><?php echo htmlspecialchars(isset($r->email)?$r->email:(isset($r->user_email)?$r->user_email:'')); ?></small>
                  </div>
                </div>
              </td>
              <td>
                <div class="fw-semibold"><?php echo htmlspecialchars(isset($r->subject)?$r->subject:'No Subject'); ?></div>
                <small class="text-muted"><?php echo htmlspecialchars(substr(isset($r->body)?$r->body:'', 0, 50) . '...'); ?></small>
              </td>
              <td>
                <?php
                $t = isset($r->type)?$r->type:'';
                $type_config = [
                    'daily_morning' => ['color' => 'info', 'icon' => '🌅', 'label' => 'Morning'],
                    'daily_night' => ['color' => 'warning', 'icon' => '🌙', 'label' => 'Night'],
                    'manual' => ['color' => 'primary', 'icon' => '✉️', 'label' => 'Manual'],
                    'schedule' => ['color' => 'secondary', 'icon' => '🗓️', 'label' => 'Scheduled'],
                    'announcement' => ['color' => 'success', 'icon' => '📢', 'label' => 'Announcement']
                ];
                $config = isset($type_config[$t]) ? $type_config[$t] : ['color' => 'secondary', 'icon' => '📧', 'label' => $t];
                ?>
                <span class="badge bg-<?php echo $config['color']; ?> bg-opacity-10 text-<?php echo $config['color']; ?> border">
                  <?php echo $config['icon']; ?> <?php echo $config['label']; ?>
                </span>
              </td>
              <td>
                <?php $st = isset($r->status)?$r->status:'queued'; 
                $status_config = [
                    'queued' => ['color' => 'secondary', 'icon' => '⏳'],
                    'sent' => ['color' => 'success', 'icon' => '✅'],
                    'error' => ['color' => 'danger', 'icon' => '❌']
                ];
                $config = isset($status_config[$st]) ? $status_config[$st] : $status_config['queued'];
                ?>
                <span class="status-badge bg-<?php echo $config['color']; ?> bg-opacity-10 text-<?php echo $config['color']; ?>">
                  <?php echo $config['icon']; ?> <?php echo ucfirst($st); ?>
                </span>
              </td>
              <td>
                <?php if (isset($r->send_at) && $r->send_at): ?>
                  <div><?php echo date('M j, H:i', strtotime($r->send_at)); ?></div>
                  <small class="text-muted"><?php echo date('Y-m-d', strtotime($r->send_at)); ?></small>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (isset($r->sent_at) && $r->sent_at): ?>
                  <div><?php echo date('M j, H:i', strtotime($r->sent_at)); ?></div>
                  <small class="text-muted"><?php echo date('Y-m-d', strtotime($r->sent_at)); ?></small>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <?php if ($st !== 'sent'): ?>
                    <a class="btn btn-outline-success" href="<?php echo site_url('reminders/send-now/'.(int)$r->id); ?>" title="Send Now">
                      <i class="bi bi-send"></i>
                    </a>
                  <?php endif; ?>
                  <a class="btn btn-outline-primary" href="<?php echo site_url('reminders/edit/'.(int)$r->id); ?>" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a class="btn btn-outline-danger" href="<?php echo site_url('reminders/delete/'.(int)$r->id); ?>" title="Delete" onclick="return confirm('Delete this reminder?');">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($pagination_links)): ?>
      <div class="card-footer bg-white">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <small class="text-muted">
            Showing <?php echo ($current_page + 1); ?> to <?php echo min($current_page + $per_page, $total_rows); ?> of <?php echo $total_rows; ?> reminders
          </small>
          <?php echo $pagination_links; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

</div>

<?php $this->load->view('partials/footer'); ?>
