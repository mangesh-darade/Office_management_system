<?php $this->load->view('partials/header', ['title' => 'Dashboard']); ?>
    <!-- Main content -->
    <div class="p-3 p-md-4">
      <!-- Removed page header to avoid duplicate branding and extra whitespace on Dashboard -->

      <div class="row g-3">
        <?php if(function_exists('has_module_access') && has_module_access('employees')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <h5 class="card-title">Employees</h5>
              <p class="card-text">Manage employee profiles</p>
              <a href="<?php echo site_url('employees'); ?>" class="btn btn-primary btn-sm">Open</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php if(function_exists('has_module_access') && has_module_access('projects')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <h5 class="card-title">Projects</h5>
              <p class="card-text">Projects and members</p>
              <a href="<?php echo site_url('projects'); ?>" class="btn btn-primary btn-sm">Open</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php if(function_exists('has_module_access') && has_module_access('tasks')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <h5 class="card-title">Tasks</h5>
              <p class="card-text">Task list and updates</p>
              <a href="<?php echo site_url('tasks'); ?>" class="btn btn-primary btn-sm">Open</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php if(function_exists('has_module_access') && has_module_access('chats')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <h5 class="card-title">Chat</h5>
              <p class="card-text">Direct, group & calls</p>
              <a href="<?php echo site_url('chats/app'); ?>" class="btn btn-primary btn-sm">Open</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php if(function_exists('has_module_access') && has_module_access('attendance')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <h5 class="card-title">Attendance</h5>
              <p class="card-text">Punch in/out and reports</p>
              <a href="<?php echo site_url('attendance'); ?>" class="btn btn-primary btn-sm">Open</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php if(function_exists('has_module_access') && has_module_access('leaves')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <h5 class="card-title">Leaves</h5>
              <p class="card-text">Apply and approvals</p>
              <a href="<?php echo site_url('leaves'); ?>" class="btn btn-primary btn-sm">Open</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php if(function_exists('has_module_access') && has_module_access('notifications')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <h5 class="card-title">Notifications</h5>
              <p class="card-text">In-app messages</p>
              <a href="<?php echo site_url('notifications'); ?>" class="btn btn-primary btn-sm">Open</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
        <?php if(function_exists('has_module_access') && has_module_access('reports')): ?>
        <div class="col-12 col-sm-6 col-lg-3">
          <div class="card shadow-sm h-100 hover-lift fade-in">
            <div class="card-body">
              <h5 class="card-title">Reports</h5>
              <p class="card-text">Overview and charts</p>
              <a href="<?php echo site_url('reports'); ?>" class="btn btn-primary btn-sm">Open</a>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
<?php $this->load->view('partials/footer'); ?>
