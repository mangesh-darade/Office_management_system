<?php 
$this->load->view('partials/header', ['title' => 'Dashboard']);
// Get current user role for filtering indicators
$current_role_id = isset($role_id) ? (int)$role_id : 0;
$show_group_indicator = !in_array($current_role_id, [1, 2], true);
// Get accessible modules
$accessible_modules = isset($accessible_modules) ? $accessible_modules : [];
$meal_dashboard = isset($meal_dashboard) ? $meal_dashboard : null;
$announcements = isset($announcements) ? $announcements : array();
$can_dashboard = function ($module) {
    return function_exists('dashboard_has_module_access') && dashboard_has_module_access($module);
};
$can_announcements = $can_dashboard('announcements');
$can_attendance = $can_dashboard('attendance');
$can_meals_dashboard = (
    $meal_dashboard !== null
    && (
        (function_exists('meal_can_view_dashboard_announcement') && meal_can_view_dashboard_announcement())
        || $can_dashboard('meals')
    )
);
$show_announcements = ($can_announcements && !empty($announcements)) || $can_meals_dashboard;
?>
    <!-- Main content -->
    <div class="p-3 p-md-4">
      <!-- Access Denied Message -->
      <?php if ($this->session->flashdata('access_denied')): ?>
      <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <div>
          <?php echo esc_view($this->session->flashdata('access_denied'), ENT_QUOTES, 'UTF-8'); ?>
        </div>
      </div>
      <?php endif; ?>
      
      <?php if ($show_group_indicator): ?>
      <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        <div>
          <strong>Group View:</strong> Showing data for your department/team only.
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Announcements at Top -->
      <?php if ($show_announcements): ?>
      <div class="mb-4 dashboard-announcements">
        <div class="card shadow-sm announcement-panel">
          <div class="card-body p-3 p-md-4">
            <div class="announcement-panel__head">
              <h5 class="announcement-panel__title mb-0">
                <i class="bi bi-megaphone"></i>Latest Announcements
              </h5>
              <?php if ($can_announcements): ?>
              <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('announcements'); ?>">View all</a>
              <?php endif; ?>
            </div>
            <div class="announcements-feed">
              <?php if ($can_meals_dashboard): ?>
                <?php $this->load->view('meals/_dashboard_grid', array(
                  'meal_dashboard' => $meal_dashboard,
                  'can_order' => function_exists('meal_can_order') && meal_can_order(),
                  'can_provider' => function_exists('meal_can_access') && meal_can_access('meals_provider'),
                )); ?>
              <?php endif; ?>
              <?php if ($can_announcements && !empty($announcements)): ?>
                <?php foreach ($announcements as $a):
                  $snippet = '';
                  if (!empty($a->content)) {
                      $snippet = trim(preg_replace('/\s+/', ' ', strip_tags((string) $a->content)));
                  }
                  $priority = isset($a->priority) ? strtolower((string) $a->priority) : '';
                  $prioClass = ($priority === 'high') ? 'announcement-item--high' : '';
                ?>
                <article class="announcement-item <?php echo esc_view($prioClass); ?>">
                  <div class="announcement-item__icon" aria-hidden="true">
                    <i class="bi bi-bullhorn"></i>
                  </div>
                  <div class="announcement-item__body">
                    <div class="announcement-item__head">
                      <div class="min-w-0">
                        <h6 class="announcement-item__title mb-0"><?php echo esc_view($a->title); ?></h6>
                        <?php if ($priority !== ''): ?>
                          <span class="announcement-item__meta"><?php echo esc_view(ucfirst($priority)); ?> priority</span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <?php if ($snippet !== ''): ?>
                      <p class="announcement-item__text mb-0"><?php echo esc_view($snippet); ?></p>
                    <?php endif; ?>
                  </div>
                </article>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Mark Attendance — shown only when user has attendance permission -->
      <?php if ($can_attendance): ?>
      <?php
        $ma = isset($mark_attendance) && is_array($mark_attendance) ? $mark_attendance : array();
        $ma_checkin  = !empty($ma['has_checkin']);
        $ma_checkout = !empty($ma['has_checkout']);
        $ma_in_raw   = isset($ma['checkin_time']) ? trim((string) $ma['checkin_time']) : '';
        $ma_out_raw  = isset($ma['checkout_time']) ? trim((string) $ma['checkout_time']) : '';
        $ma_format_time = function ($raw) {
          if ($raw === '') { return ''; }
          $ts = strtotime($raw);
          if ($ts !== false) { return date('g:i A', $ts); }
          if (preg_match('/^(\d{1,2}:\d{2}(?::\d{2})?)/', $raw, $m)) {
            $t = strtotime($m[1]);
            return $t !== false ? date('g:i A', $t) : $m[1];
          }
          return $raw;
        };
        $ma_in_time  = $ma_format_time($ma_in_raw);
        $ma_out_time = $ma_format_time($ma_out_raw);
        $ma_status_detail = '';
        if ($ma_checkin && $ma_checkout) {
          $ma_status_label = 'Complete for today';
          $ma_status_class = 'success';
          $ma_status_icon  = 'bi-check-circle-fill';
          if ($ma_in_time !== '' || $ma_out_time !== '') {
            $parts = array();
            if ($ma_in_time !== '') { $parts[] = 'In ' . $ma_in_time; }
            if ($ma_out_time !== '') { $parts[] = 'Out ' . $ma_out_time; }
            $ma_status_detail = implode(' · ', $parts);
          }
        } elseif ($ma_checkin) {
          $ma_status_label = 'Checked in';
          $ma_status_class = 'warning';
          $ma_status_icon  = 'bi-clock-history';
          $ma_status_detail = $ma_in_time !== '' ? 'Since ' . $ma_in_time . ' — remember to check out' : 'Remember to check out when you leave';
        } else {
          $ma_status_label = 'Not marked yet';
          $ma_status_class = 'primary';
          $ma_status_icon  = 'bi-calendar-check';
          $ma_status_detail = 'Tap the button below to check in for today';
        }
        $ma_btn_label = ($ma_checkin && !$ma_checkout) ? 'Check out now' : (($ma_checkin && $ma_checkout) ? 'View attendance' : 'Mark attendance');
        $ma_btn_icon  = ($ma_checkin && !$ma_checkout) ? 'bi-box-arrow-right' : (($ma_checkin && $ma_checkout) ? 'bi-eye' : 'bi-plus-square');
      ?>
      <div class="mb-4 mark-attendance-wrap">
        <div class="card shadow-sm border-0 mark-attendance-card overflow-hidden">
          <div class="card-body p-3 p-md-4">
            <div class="mark-attendance-layout">
              <div class="mark-attendance-main">
                <div class="mark-attendance-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                  <i class="bi bi-fingerprint"></i>
                </div>
                <div class="mark-attendance-copy min-w-0">
                  <h5 class="mark-attendance-title mb-1">Mark Attendance</h5>
                  <p class="text-muted small mb-2 mb-md-1">Record your check-in and check-out for today.</p>
                  <div class="mark-attendance-status">
                    <span class="badge rounded-pill mark-attendance-badge bg-<?php echo $ma_status_class; ?>-subtle text-<?php echo $ma_status_class; ?> border border-<?php echo $ma_status_class; ?>-subtle">
                      <i class="bi <?php echo $ma_status_icon; ?> me-1"></i><?php echo esc_view($ma_status_label); ?>
                    </span>
                    <?php if ($ma_status_detail !== ''): ?>
                      <p class="mark-attendance-status-detail small text-muted mb-0"><?php echo esc_view($ma_status_detail); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <div class="mark-attendance-actions">
                <a href="<?php echo site_url('attendance/create'); ?>" class="btn btn-primary mark-attendance-btn-primary">
                  <i class="bi <?php echo $ma_btn_icon; ?> me-2"></i><?php echo esc_view($ma_btn_label); ?>
                </a>
                <a href="<?php echo site_url('attendance'); ?>" class="btn btn-outline-secondary mark-attendance-btn-secondary">
                  <i class="bi bi-clock-history me-1 d-md-none"></i>History
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- External Dashboards -->
      <?php
        $visible_external_dashboards = array();
        if (!empty($external_dashboards)) {
            foreach ($external_dashboards as $dash) {
                $permission_key = isset($dash['permission_key']) ? (string) $dash['permission_key'] : '';
                if ($permission_key === '') {
                    continue;
                }
                if (function_exists('dashboard_has_module_access') && dashboard_has_module_access($permission_key)) {
                    $visible_external_dashboards[] = $dash;
                    continue;
                }
                if (function_exists('has_module_access') && has_module_access($permission_key)) {
                    $visible_external_dashboards[] = $dash;
                }
            }
        }
      ?>
      <?php if (!empty($visible_external_dashboards)): ?>
      <div class="row g-3 mb-3">
          <?php foreach ($visible_external_dashboards as $dash): ?>
            <?php
              $label = isset($dash['label']) ? (string)$dash['label'] : 'Dashboard';
              $url = isset($dash['url']) ? (string)$dash['url'] : '#';
              $icon_class = isset($dash['icon_class']) && trim($dash['icon_class']) !== '' ? trim($dash['icon_class']) : 'bi bi-bar-chart-line';
              $gradient_class = isset($dash['gradient_class']) && trim($dash['gradient_class']) !== '' ? trim($dash['gradient_class']) : 'bg-gradient-primary';
            ?>
            <div class="col-12 col-sm-6 col-lg-3">
              <a href="<?php echo esc_view($url); ?>" class="card stat-card <?php echo esc_view($gradient_class); ?> text-white h-100 hover-lift external-dashboard-card text-decoration-none">
                <div class="card-body text-start">
                  <div class="d-flex align-items-center justify-content-between">
                    <div>
                      <h6 class="card-title text-white-50 mb-1"><?php echo esc_view($label); ?></h6>
                      <small class="text-white-50">Open <?php echo esc_view($label); ?> Looker dashboard</small>
                    </div>
                    <div class="stat-icon">
                      <i class="<?php echo esc_view($icon_class); ?> fs-2"></i>
                    </div>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Dashboard Statistics Cards -->
      <div class="row g-3 mb-4">
        <?php if ($can_dashboard('spl')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stat-card bg-gradient-spl text-white h-100 hover-lift">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="card-title text-white-50 mb-1">SPL Rewards</h6>
                  <h3 class="mb-0 fw-bold"><?php echo isset($stats['spl_points_week']) ? number_format($stats['spl_points_week']) : '0'; ?></h3>
                  <small class="text-white-50">
                    <?php if (!empty($stats['spl_level'])): ?>
                      <?php echo esc_view($stats['spl_level']); ?> · this week
                    <?php else: ?>
                      Points this week
                    <?php endif; ?>
                  </small>
                </div>
                <div class="stat-icon">
                  <i class="bi bi-trophy-fill fs-2"></i>
                </div>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
              <div class="d-flex align-items-center justify-content-between gap-2">
                <small class="text-white-50">
                  <?php echo isset($stats['spl_pending']) ? number_format($stats['spl_pending']) : '0'; ?>
                  <?php echo esc_view(isset($stats['spl_pending_label']) ? $stats['spl_pending_label'] : 'pending'); ?>
                </small>
                <a href="<?php echo site_url('spl'); ?>" class="btn btn-outline-light btn-sm">View SPL</a>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if($can_dashboard('employees')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stat-card bg-gradient-primary text-white h-100 hover-lift">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="card-title text-white-50 mb-1">Employees</h6>
                  <h3 class="mb-0 fw-bold"><?php echo isset($stats['employees']) ? number_format($stats['employees']) : '0'; ?></h3>
                  <small class="text-white-50">Active staff</small>
                </div>
                <div class="stat-icon">
                  <i class="bi bi-people fs-2"></i>
                </div>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
              <a href="<?php echo site_url('employees'); ?>" class="btn btn-outline-light btn-sm">View Details</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if($can_dashboard('projects')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stat-card bg-gradient-success text-white h-100 hover-lift">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="card-title text-white-50 mb-1">Active Projects</h6>
                  <h3 class="mb-0 fw-bold"><?php echo isset($stats['projects_active']) ? number_format($stats['projects_active']) : '0'; ?></h3>
                  <small class="text-white-50">of <?php echo isset($stats['projects_total']) ? number_format($stats['projects_total']) : '0'; ?> total</small>
                </div>
                <div class="stat-icon">
                  <i class="bi bi-kanban fs-2"></i>
                </div>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
              <a href="<?php echo site_url('projects'); ?>" class="btn btn-outline-light btn-sm">View Projects</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if($can_dashboard('tasks')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stat-card bg-gradient-warning text-white h-100 hover-lift">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="card-title text-white-50 mb-1">Pending Tasks</h6>
                  <h3 class="mb-0 fw-bold"><?php echo isset($stats['tasks_pending']) ? number_format($stats['tasks_pending']) : '0'; ?></h3>
                  <small class="text-white-50"><?php echo isset($stats['tasks_completed']) ? number_format($stats['tasks_completed']) : '0'; ?> completed</small>
                </div>
                <div class="stat-icon">
                  <i class="bi bi-list-check fs-2"></i>
                </div>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
              <a href="<?php echo site_url('tasks/my-dashboard'); ?>" class="btn btn-outline-light btn-sm">Team Dashboard</a>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($can_dashboard('my_works')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stat-card bg-gradient-secondary text-white h-100 hover-lift">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="card-title text-white-50 mb-1">Open My Works</h6>
                  <h3 class="mb-0 fw-bold"><?php echo isset($stats['my_works_open']) ? number_format($stats['my_works_open']) : '0'; ?></h3>
                  <small class="text-white-50"><?php echo isset($stats['my_works_urgent']) ? number_format($stats['my_works_urgent']) : '0'; ?> urgent</small>
                </div>
                <div class="stat-icon">
                  <i class="bi bi-clipboard2-check fs-2"></i>
                </div>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
              <a href="<?php echo site_url('my-works'); ?>" class="btn btn-outline-light btn-sm">View My Works</a>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if($can_dashboard('defects')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stat-card bg-gradient-danger text-white h-100 hover-lift">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="card-title text-white-50 mb-1">Open Defects</h6>
                  <h3 class="mb-0 fw-bold"><?php echo isset($stats['defects_open']) ? number_format($stats['defects_open']) : '0'; ?></h3>
                  <small class="text-white-50"><?php echo isset($stats['defects_overdue']) ? number_format($stats['defects_overdue']) : '0'; ?> overdue</small>
                </div>
                <div class="stat-icon">
                  <i class="bi bi-bug fs-2"></i>
                </div>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
              <a href="<?php echo site_url('defects'); ?>" class="btn btn-outline-light btn-sm">View Defects</a>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if($can_dashboard('releases')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stat-card bg-gradient-dark text-white h-100 hover-lift">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="card-title text-white-50 mb-1">Upcoming Releases</h6>
                  <h3 class="mb-0 fw-bold"><?php echo isset($stats['releases_upcoming']) ? number_format($stats['releases_upcoming']) : '0'; ?></h3>
                  <small class="text-white-50">Next 14 days</small>
                </div>
                <div class="stat-icon">
                  <i class="bi bi-rocket-takeoff fs-2"></i>
                </div>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
              <a href="<?php echo site_url('releases'); ?>" class="btn btn-outline-light btn-sm">View Releases</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <?php
        $dashboard_quick_links = array();
        if ($can_dashboard('chats')) {
            $dashboard_quick_links[] = array(
                'url' => site_url('chats/app'),
                'icon' => 'bi-chat-dots',
                'icon_class' => 'bg-info',
                'title' => 'Chat',
                'text' => 'Team messaging and video calls',
            );
        }
        if ($can_dashboard('leaves')) {
            $dashboard_quick_links[] = array(
                'url' => site_url('leave/apply'),
                'icon' => 'bi-airplane-engines',
                'icon_class' => 'bg-teal',
                'title' => 'Leaves',
                'text' => 'Apply and manage leave requests',
            );
        }
        if ($can_dashboard('reports')) {
            $dashboard_quick_links[] = array(
                'url' => site_url('reports'),
                'icon' => 'bi-graph-up',
                'icon_class' => 'bg-indigo',
                'title' => 'Reports',
                'text' => 'Analytics and insights',
            );
        }
        if ($can_dashboard('settings')) {
            $dashboard_quick_links[] = array(
                'url' => site_url('settings'),
                'icon' => 'bi-gear',
                'icon_class' => 'bg-dark',
                'title' => 'Settings',
                'text' => 'System configuration',
            );
        }
      ?>
      <?php if (!empty($dashboard_quick_links)): ?>
      <div class="mb-2">
        <h6 class="text-muted text-uppercase small fw-semibold mb-0">Quick Links</h6>
      </div>
      <div class="row g-3">
        <?php foreach ($dashboard_quick_links as $link): ?>
        <a href="<?php echo esc_view($link['url']); ?>" class="col-12 col-sm-6 col-lg-3 text-decoration-none">
          <div class="card shadow-sm h-100 hover-lift fade-in module-card">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle <?php echo esc_view($link['icon_class']); ?> text-white me-3">
                  <i class="bi <?php echo esc_view($link['icon']); ?>"></i>
                </div>
                <h5 class="card-title mb-0 text-dark"><?php echo esc_view($link['title']); ?></h5>
              </div>
              <p class="card-text text-muted"><?php echo esc_view($link['text']); ?></p>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

<style>
.module-card {
  transition: all 0.3s ease;
  cursor: pointer;
  border: 2px solid transparent;
}

.module-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-color: #007bff;
}

.module-card .card-title {
  transition: color 0.3s ease;
}

.module-card:hover .card-title {
  color: #007bff !important;
}

.module-card .icon-circle {
  transition: all 0.3s ease;
}

.module-card:hover .icon-circle {
  transform: scale(1.1);
}

/* Ensure text remains readable on hover */
.module-card:hover .card-text {
  color: #6c757d !important;
}

/* Remove default link styling */
.text-decoration-none:hover {
  text-decoration: none !important;
}

/* Add subtle animation on page load */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in {
  animation: fadeInUp 0.6s ease-out;
}

/* Stagger animation for multiple cards */
.fade-in:nth-child(1) { animation-delay: 0.1s; }
.fade-in:nth-child(2) { animation-delay: 0.2s; }
.fade-in:nth-child(3) { animation-delay: 0.3s; }
.fade-in:nth-child(4) { animation-delay: 0.4s; }
.fade-in:nth-child(5) { animation-delay: 0.5s; }
.fade-in:nth-child(6) { animation-delay: 0.6s; }
.fade-in:nth-child(7) { animation-delay: 0.7s; }
.fade-in:nth-child(8) { animation-delay: 0.8s; }

.mark-attendance-wrap {
  max-width: 100%;
}

.mark-attendance-card {
  background: linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%);
  border-left: 4px solid #0d6efd !important;
  border-radius: 12px;
}

.mark-attendance-layout {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

@media (min-width: 768px) {
  .mark-attendance-layout {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    gap: 1.25rem;
  }
}

.mark-attendance-main {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  min-width: 0;
  flex: 1 1 auto;
}

.mark-attendance-copy {
  flex: 1 1 auto;
  min-width: 0;
}

.mark-attendance-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.3;
  word-wrap: break-word;
}

.mark-attendance-icon {
  width: 2.75rem;
  height: 2.75rem;
  background: #e7f1ff;
  color: #0d6efd;
  font-size: 1.35rem;
}

@media (min-width: 768px) {
  .mark-attendance-icon {
    width: 3.25rem;
    height: 3.25rem;
    font-size: 1.5rem;
  }
}

.mark-attendance-status {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.35rem;
  max-width: 100%;
}

.mark-attendance-badge {
  font-size: 0.78rem;
  font-weight: 600;
  padding: 0.4em 0.75em;
  white-space: normal;
  text-align: left;
  line-height: 1.35;
  max-width: 100%;
  word-break: break-word;
}

.mark-attendance-status-detail {
  line-height: 1.45;
  word-break: break-word;
}

.mark-attendance-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  width: 100%;
  flex-shrink: 0;
}

@media (min-width: 576px) {
  .mark-attendance-actions {
    flex-direction: row;
    width: auto;
  }
}

.mark-attendance-btn-primary,
.mark-attendance-btn-secondary {
  width: 100%;
  min-height: 2.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  border-radius: 10px;
}

@media (min-width: 576px) {
  .mark-attendance-btn-primary,
  .mark-attendance-btn-secondary {
    width: auto;
    min-width: 9rem;
  }
}

@media (min-width: 768px) {
  .mark-attendance-btn-primary {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
    font-size: 1rem;
  }
}

.external-dashboard-card .card-body {
  padding: 1rem 1.1rem;
}

@media (max-width: 575.98px) {
  .external-dashboard-card .card-body small {
    display: block;
    max-width: 100%;
    white-space: normal;
    line-height: 1.35;
  }

  .stat-card .card-body {
    padding: 1rem;
  }
}

.announcement-card-top {
  border-top: 3px solid #007bff !important;
}

.announcement-card-top .card-body {
  padding: 1.25rem;
}

.external-dashboard-card {
  width: 100%;
  border: 2px solid transparent;
}

.external-dashboard-card.active-dashboard {
  border-color: #ffffff;
  box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.25);
}
</style>

<?php $this->load->view('partials/footer'); ?>
