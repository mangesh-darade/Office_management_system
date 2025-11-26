<?php $this->load->view('partials/header', ['title' => 'Dashboard']); ?>
    <!-- Main content -->
    <div class="p-3 p-md-4">
      <!-- Dashboard Statistics Cards -->
      <div class="row g-3 mb-4">
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
              <a href="<?php echo site_url('tasks/board'); ?>" class="btn btn-outline-light btn-sm">View Tasks</a>
            </div>
          </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card stat-card bg-gradient-info text-white h-100 hover-lift">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div>
                  <h6 class="card-title text-white-50 mb-1">Today's Attendance</h6>
                  <h3 class="mb-0 fw-bold"><?php echo isset($stats['attendance_today']) ? number_format($stats['attendance_today']) : '0'; ?></h3>
                  <small class="text-white-50"><?php echo isset($stats['leaves_pending']) ? number_format($stats['leaves_pending']) : '0'; ?> leaves pending</small>
                </div>
                <div class="stat-icon">
                  <i class="bi bi-calendar-check fs-2"></i>
                </div>
              </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
              <a href="<?php echo site_url('attendance'); ?>" class="btn btn-outline-light btn-sm">View Attendance</a>
            </div>
          </div>
        </div>
      </div>

      <?php if (!empty($announcements)): ?>
      <div class="mb-4">
        <div class="card shadow-sm announcement-card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0"><i class="bi bi-megaphone me-2 text-primary"></i>Latest Announcements</h5>
              <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('announcements'); ?>">View all</a>
            </div>
            <div class="row g-3">
              <?php foreach ($announcements as $a): ?>
                <div class="col-12 col-md-6">
                  <div class="d-flex align-items-start p-3 rounded bg-light">
                    <div class="me-3">
                      <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-bullhorn"></i>
                      </div>
                    </div>
                    <div class="flex-grow-1">
                      <h6 class="mb-1 fw-semibold"><?php echo htmlspecialchars($a->title); ?></h6>
                      <?php if (!empty($a->start_date) || !empty($a->end_date)): ?>
                        <small class="text-muted"><?php echo htmlspecialchars(($a->start_date?:'—').' to '.($a->end_date?:'—')); ?></small>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="row g-3">
        <?php if(function_exists('has_module_access') && has_module_access('employees')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-primary text-white me-3">
                  <i class="bi bi-people"></i>
                </div>
                <h5 class="card-title mb-0">Employees</h5>
              </div>
              <p class="card-text text-muted">Manage employee profiles and records</p>
              <a href="<?php echo site_url('employees'); ?>" class="btn btn-primary btn-sm w-100">Open Module</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if(function_exists('has_module_access') && has_module_access('projects')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-success text-white me-3">
                  <i class="bi bi-kanban"></i>
                </div>
                <h5 class="card-title mb-0">Projects</h5>
              </div>
              <p class="card-text text-muted">Projects and team management</p>
              <a href="<?php echo site_url('projects'); ?>" class="btn btn-primary btn-sm w-100">Open Module</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if(function_exists('has_module_access') && has_module_access('tasks')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-warning text-white me-3">
                  <i class="bi bi-list-check"></i>
                </div>
                <h5 class="card-title mb-0">Tasks</h5>
              </div>
              <p class="card-text text-muted">Task board and progress tracking</p>
              <a href="<?php echo site_url('tasks/board'); ?>" class="btn btn-primary btn-sm w-100">Open Module</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if(function_exists('has_module_access') && has_module_access('chats')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-info text-white me-3">
                  <i class="bi bi-chat-dots"></i>
                </div>
                <h5 class="card-title mb-0">Chat</h5>
              </div>
              <p class="card-text text-muted">Team messaging and video calls</p>
              <a href="<?php echo site_url('chats/app'); ?>" class="btn btn-primary btn-sm w-100">Open Module</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if(function_exists('has_module_access') && has_module_access('attendance')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-secondary text-white me-3">
                  <i class="bi bi-calendar-check"></i>
                </div>
                <h5 class="card-title mb-0">Attendance</h5>
              </div>
              <p class="card-text text-muted">Punch in/out and reports</p>
              <a href="<?php echo site_url('attendance'); ?>" class="btn btn-primary btn-sm w-100">Open Module</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if(function_exists('has_module_access') && has_module_access('leaves')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-teal text-white me-3">
                  <i class="bi bi-airplane-engines"></i>
                </div>
                <h5 class="card-title mb-0">Leaves</h5>
              </div>
              <p class="card-text text-muted">Apply and manage leave requests</p>
              <a href="<?php echo site_url('leave/apply'); ?>" class="btn btn-primary btn-sm w-100">Open Module</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if(function_exists('has_module_access') && has_module_access('reports')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-indigo text-white me-3">
                  <i class="bi bi-graph-up"></i>
                </div>
                <h5 class="card-title mb-0">Reports</h5>
              </div>
              <p class="card-text text-muted">Analytics and insights</p>
              <a href="<?php echo site_url('reports'); ?>" class="btn btn-primary btn-sm w-100">Open Module</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
        <?php if(function_exists('has_module_access') && has_module_access('settings')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <div class="d-flex align-items-center mb-3">
                <div class="icon-circle bg-dark text-white me-3">
                  <i class="bi bi-gear"></i>
                </div>
                <h5 class="card-title mb-0">Settings</h5>
              </div>
              <p class="card-text text-muted">System configuration</p>
              <a href="<?php echo site_url('settings'); ?>" class="btn btn-primary btn-sm w-100">Open Module</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
<?php $this->load->view('partials/footer'); ?>
