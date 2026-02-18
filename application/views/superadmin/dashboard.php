<?php 
$this->load->view('partials/header', ['title' => 'Superadmin Dashboard']);
?>
<div class="p-3 p-md-4">
    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
        <i class="bi bi-shield-lock-fill me-2 fs-4"></i>
        <div>
            <strong>Super Admin Mode:</strong> You have unrestricted access to all system data and configurations.
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row g-3 mb-4">
        <!-- Users -->
        <div class="col-12 col-md-3">
            <div class="card bg-primary text-white h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Total Users</h6>
                            <h2 class="fw-bold mb-0"><?php echo number_format($users_count); ?></h2>
                        </div>
                        <i class="bi bi-people fs-1 text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Projects -->
        <div class="col-12 col-md-3">
            <div class="card bg-success text-white h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Total Projects</h6>
                            <h2 class="fw-bold mb-0"><?php echo number_format($projects_count); ?></h2>
                        </div>
                        <i class="bi bi-kanban fs-1 text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
         <!-- Tasks -->
        <div class="col-12 col-md-3">
            <div class="card bg-warning text-white h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-white-50">Total Tasks</h6>
                            <h2 class="fw-bold mb-0"><?php echo number_format($tasks_count); ?></h2>
                        </div>
                        <i class="bi bi-list-check fs-1 text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <!-- Environment -->
        <div class="col-12 col-md-3">
            <div class="card bg-dark text-white h-100 hover-lift">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                           <h6 class="card-title text-white-50">PHP / DB</h6>
                            <div class="fw-bold h5 mb-0"><?php echo $php_version; ?></div>
                            <small class="text-white-50"><?php echo $db_platform; ?></small>
                        </div>
                        <i class="bi bi-hdd-network fs-1 text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Administrative Actions -->
        <div class="col-12 col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-tools me-2"></i>Administration Tools</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach($admin_actions as $action): ?>
                        <div class="col-12 col-sm-6 col-md-4">
                            <a href="<?php echo site_url($action['url']); ?>" class="card p-3 text-decoration-none text-dark hover-lift h-100 text-center border action-card">
                                <div class="py-2">
                                     <i class="bi <?php echo $action['icon']; ?> fs-1 text-primary mb-2 d-block"></i>
                                     <span class="fw-semibold"><?php echo $action['label']; ?></span>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                        <div class="col-12 col-sm-6 col-md-4">
                            <a href="<?php echo site_url('activity'); ?>" class="card p-3 text-decoration-none text-dark hover-lift h-100 text-center border action-card">
                                <div class="py-2">
                                    <i class="bi bi-activity fs-1 text-danger mb-2 d-block"></i>
                                    <span class="fw-semibold">Activity Logs</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Info / Recent Users -->
        <div class="col-12 col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="bi bi-server me-2"></i>System Info</h5>
                </div>
                 <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Server IP</span>
                        <code class="text-dark bg-light px-2 py-1 rounded"><?php echo $server_ip; ?></code>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Database</span>
                        <span class="badge bg-secondary"><?php echo $db_platform . ' ' . $db_version; ?></span>
                    </li>
                     <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>CodeIgniter</span>
                        <span class="badge bg-info text-dark"><?php echo CI_VERSION; ?></span>
                    </li>
                </ul>
            </div>

            <div class="card shadow-sm">
                 <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Users</h5>
                    <a href="<?php echo site_url('users'); ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach($recent_users as $u): ?>
                    <li class="list-group-item border-bottom-0">
                        <div class="d-flex align-items-center">
                             <div class="me-3 rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width:36px;height:36px">
                                <i class="bi bi-person text-secondary"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold text-truncate"><?php echo !empty($u->name) ? htmlspecialchars($u->name) : 'User #'.$u->id; ?></div>
                                <div class="text-muted small text-truncate"><?php echo htmlspecialchars($u->email); ?></div>
                            </div>
                            <div class="text-muted small ms-2" style="font-size:0.7rem">
                                <?php echo date('M d', strtotime($u->created_at)); ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.hover-lift {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hover-lift:hover {
    transform: translateY(-3px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
.action-card:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa;
}
</style>
<?php $this->load->view('partials/footer'); ?>
