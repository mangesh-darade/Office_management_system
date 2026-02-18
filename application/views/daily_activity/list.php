<?php $this->load->view('partials/header', ['title' => 'Daily Activity List']); ?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-truncate">Activities</h4>
        <div class="d-flex flex-nowrap gap-2">
            <?php if($is_admin): ?>
            <a href="<?php echo site_url('reports/daily_activity'); ?>" class="btn btn-outline-secondary" title="View Report"><i class="bi bi-file-earmark-text"></i><span class="d-none d-sm-inline ms-2">Report</span></a>
            <?php endif; ?>
            <a href="<?php echo site_url('daily_activity'); ?>" class="btn btn-primary" title="Log New Activity"><i class="bi bi-plus-lg"></i><span class="d-none d-sm-inline ms-2">Add Activity</span></a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" action="<?php echo site_url('daily_activity/list_all'); ?>" class="row g-2 align-items-end">
                <?php if($is_admin && !empty($users)): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-1">Employee</label>
                    <select class="form-select form-select-sm footer-select" name="user_id">
                        <option value="">-- All Employees --</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?php echo $u->id; ?>" <?php echo (isset($filters['user_id']) && $filters['user_id'] == $u->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($u->name ?: $u->email); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-6 <?php echo ($is_admin) ? 'col-md-2' : 'col-md-3'; ?>">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-1">Period</label>
                    <select class="form-select form-select-sm" name="period" id="filterPeriod" onchange="this.form.submit()">
                        <option value="" <?php echo (empty($filters['period'])) ? 'selected' : ''; ?>>Custom Range</option>
                        <option value="daily" <?php echo (isset($filters['period']) && $filters['period']=='daily') ? 'selected' : ''; ?>>Today</option>
                        <option value="weekly" <?php echo (isset($filters['period']) && $filters['period']=='weekly') ? 'selected' : ''; ?>>This Week</option>
                        <option value="monthly" <?php echo (isset($filters['period']) && $filters['period']=='monthly') ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>
                
                <div class="col-6 <?php echo ($is_admin) ? 'col-md-2' : 'col-md-3'; ?>">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-1">Date From</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" id="dateFrom" value="<?php echo isset($filters['date_from']) ? htmlspecialchars($filters['date_from']) : ''; ?>" onchange="document.getElementById('filterPeriod').value='';">
                </div>
                <div class="col-6 <?php echo ($is_admin) ? 'col-md-2' : 'col-md-3'; ?>">
                     <label class="form-label small fw-bold text-uppercase text-muted mb-1">Date To</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" id="dateTo" value="<?php echo isset($filters['date_to']) ? htmlspecialchars($filters['date_to']) : ''; ?>" onchange="document.getElementById('filterPeriod').value='';">
                </div>
                
                <div class="col-6 <?php echo ($is_admin) ? 'col-md-3' : 'col-md-3'; ?> d-flex gap-1">
                    <button type="submit" class="btn btn-secondary btn-sm flex-grow-1" title="Apply Filter"><i class="bi bi-filter"></i><span class="d-none d-sm-inline ms-1">Filter</span></button>
                    <a href="<?php echo site_url('daily_activity/list_all'); ?>" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 120px;">Date</th>
                            <th class="d-none d-md-table-cell">User</th>
                            <th>Task / Activity</th>
                            <th class="text-end" style="width: 60px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No activities found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr class="cursor-pointer" onclick="viewActivity(this)">
                                    <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($log->work_date)); ?></td>
                                    <td class="d-none d-md-table-cell">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2 bg-secondary text-white rounded-circle d-flex justify-content-center align-items-center" style="width:32px;height:32px;">
                                                <?php echo strtoupper(substr($log->user_name ?: $log->user_email, 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($log->user_name ?: 'Unknown'); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($log->user_email); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($log->activity_title): ?>
                                            <span class="fw-bold activity-title-text"><?php echo htmlspecialchars($log->activity_title); ?></span>
                                        <?php elseif ($log->task_title): ?>
                                            <span class="badge bg-info text-dark task-title-text"><?php echo htmlspecialchars($log->task_title); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                        <div class="d-none full-description"><?php echo $log->description; ?></div>
                                    </td>
                                    <td class="text-end" onclick="event.stopPropagation()">
                                        <?php if (function_exists('has_module_access') && (has_module_access('daily_activity_delete') || has_module_access('daily_activity')) && ($is_admin || $log->user_id == $this->session->userdata('user_id'))): ?>
                                            <?php echo form_open('daily_activity/delete/' . $log->id, ['onsubmit' => "return confirm('Delete this log?');", 'class' => 'd-inline']); ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                            <?php echo form_close(); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <?php echo $this->pagination->create_links(); ?>
            </div>
        </div>
    </div>
</div>

<!-- View Activity Modal -->
<div class="modal fade" id="viewActivityModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="viewModalTitle">Activity Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex flex-column flex-sm-row justify-content-between mb-3 border-bottom pb-3 gap-2">
            <div>
                <div id="viewModalUser" class="fw-bold text-primary fs-5"></div>
                <div id="viewModalDate" class="text-muted small"></div>
            </div>
            <div id="viewModalTaskBadge"></div>
        </div>
        <div class="card bg-light border-0">
            <div class="card-body" id="viewModalDesc" style="min-height: 100px;">
            </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<style>
    .cursor-pointer { cursor: pointer; }
    .cursor-pointer:hover { background-color: #f8f9fa; }
    /* Ensure modal images are responsive */
    #viewModalDesc img { max-width: 100%; height: auto; }
</style>

<script>
    function viewActivity(row) {
        var user = $(row).find('td:eq(1) .fw-bold').text();
        var email = $(row).find('td:eq(1) .text-muted').text();
        var date = $(row).find('td:eq(0)').text();
        
        // Find task title
        var customActivity = $(row).find('.activity-title-text').text();
        var taskActivity = $(row).find('.task-title-text').text();
        var taskTitle = customActivity || taskActivity || 'General Update';
        
        // Determine badge style
        var badgeClass = taskActivity ? 'badge bg-info text-dark' : 'fw-bold text-dark';
        
        var descHtml = $(row).find('.full-description').html();

        $('#viewModalUser').text(user + ' (' + email + ')');
        $('#viewModalDate').text(date);
        
        // Construct badge html
        var badgeHtml = '<span class="' + badgeClass + ' fs-6 px-3 py-2 rounded-pill">' + taskTitle + '</span>';
        $('#viewModalTaskBadge').html(badgeHtml);
        
        $('#viewModalDesc').html(descHtml);
        
        var modal = new bootstrap.Modal(document.getElementById('viewActivityModal'));
        modal.show();
    }
</script>

<?php $this->load->view('partials/footer'); ?>
