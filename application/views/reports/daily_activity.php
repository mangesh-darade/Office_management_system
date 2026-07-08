<?php $this->load->view('partials/header', ['title' => 'Daily Activity Report']); ?>

<style>
.daily-act-report .stat-card {
    border: none;
    border-radius: 12px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    overflow: hidden;
}
.daily-act-report .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.1) !important;
}
.daily-act-report .stat-card .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}
.daily-act-report .stat-card .stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    line-height: 1.2;
}
.daily-act-report .stat-card .stat-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}
.daily-act-report .filter-card {
    border: none;
    border-radius: 12px;
    background: #fff;
}
.daily-act-report .report-table thead th {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 700;
    color: #6b7280;
    border-bottom-width: 2px;
    padding: 0.75rem 1rem;
    white-space: nowrap;
}
.daily-act-report .report-table tbody td {
    padding: 0.875rem 1rem;
    vertical-align: middle;
}
.daily-act-report .report-table tbody tr {
    cursor: pointer;
    transition: background 0.1s;
}
.daily-act-report .report-table tbody tr:hover {
    background: #f0f4ff !important;
}
.daily-act-report .user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.75rem;
    flex-shrink: 0;
}
.daily-act-report .activity-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
}
.daily-act-report .desc-preview {
    color: #6b7280;
    font-size: 0.85rem;
    max-width: 350px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.daily-act-report .da-empty-state {
    padding: 4rem 2rem;
    text-align: center;
}
.daily-act-report .da-empty-state i {
    font-size: 3.5rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}
.daily-act-report #viewReportModal .modal-content {
    border: none;
    border-radius: 16px;
    overflow: hidden;
}
.daily-act-report #viewReportModal .modal-header {
    border-bottom: 1px solid #f3f4f6;
    padding: 1.25rem 1.5rem;
}
.daily-act-report #viewReportModal .modal-body {
    padding: 1.5rem;
}
.daily-act-report #viewRepDesc img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
}
</style>

<div class="container-fluid py-4 daily-act-report">

    <!-- Page Header -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold mb-1"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Daily Activity Report</h4>
            <p class="text-muted mb-0 small">Track team productivity and daily work logs</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (!empty($rows)): ?>
            <a href="<?php echo site_url('reports/daily_activity?' . http_build_query(array_filter($filters)) . '&export=csv'); ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-download me-1"></i>Export</a>
            <?php endif; ?>
            <a href="<?php echo site_url('reports'); ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Reports</a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-text"></i></div>
                    <div>
                        <div class="stat-value text-dark"><?php echo (int)$stats['total_entries']; ?></div>
                        <div class="stat-label text-muted">Total Entries</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-people"></i></div>
                    <div>
                        <div class="stat-value text-dark"><?php echo (int)$stats['unique_users']; ?></div>
                        <div class="stat-label text-muted">Active Users</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="stat-value text-dark"><?php echo (int)$stats['unique_dates']; ?></div>
                        <div class="stat-label text-muted">Days Covered</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-check2-square"></i></div>
                    <div>
                        <div class="stat-value text-dark"><?php echo (int)$stats['unique_tasks']; ?></div>
                        <div class="stat-label text-muted">Tasks Linked</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card filter-card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-end">
                <?php if($is_admin && !empty($users)): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-person me-1"></i>Employee</label>
                    <select class="form-select form-select-sm" name="user_id">
                        <option value="">All Employees</option>
                        <?php foreach($users as $u): ?>
                            <option value="<?php echo $u->id; ?>" <?php echo ($filters['user_id'] == $u->id) ? 'selected' : ''; ?>>
                                <?php echo esc_view($u->name ?: $u->email); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-clock me-1"></i>Period</label>
                    <select class="form-select form-select-sm" name="period" id="filterPeriod" onchange="this.form.submit()">
                        <option value="" <?php echo empty($filters['period']) ? 'selected' : ''; ?>>Custom</option>
                        <option value="daily" <?php echo (isset($filters['period']) && $filters['period']=='daily') ? 'selected' : ''; ?>>Today</option>
                        <option value="weekly" <?php echo (isset($filters['period']) && $filters['period']=='weekly') ? 'selected' : ''; ?>>This Week</option>
                        <option value="monthly" <?php echo (isset($filters['period']) && $filters['period']=='monthly') ? 'selected' : ''; ?>>This Month</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-calendar-event me-1"></i>From</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo esc_view(isset($filters['date_from']) ? $filters['date_from'] : ''); ?>" onchange="document.getElementById('filterPeriod').value='';">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small fw-semibold text-muted mb-1"><i class="bi bi-calendar-event me-1"></i>To</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo esc_view(isset($filters['date_to']) ? $filters['date_to'] : ''); ?>" onchange="document.getElementById('filterPeriod').value='';">
                </div>
                <div class="col-6 col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="bi bi-funnel me-1"></i>Apply</button>
                    <a href="<?php echo site_url('reports/daily_activity'); ?>" class="btn btn-outline-secondary btn-sm px-3"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm" style="border:none; border-radius:12px; overflow:hidden;">
        <?php if(empty($rows)): ?>
            <div class="da-empty-state">
                <i class="bi bi-inbox d-block"></i>
                <h5 class="fw-semibold text-dark mb-2">No Activities Found</h5>
                <p class="text-muted mb-3">No daily activity logs match the selected filters.</p>
                <a href="<?php echo site_url('reports/daily_activity'); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filters</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table report-table mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th style="width:50px;">#</th>
                            <th style="width:120px;">Date</th>
                            <th>Employee</th>
                            <th>Activity / Task</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 0; foreach($rows as $r): $idx++; ?>
                        <?php
                            $colors = ['#4f46e5','#0891b2','#059669','#d97706','#dc2626','#7c3aed','#db2777','#2563eb'];
                            $initial = strtoupper(substr($r->user_name ?: $r->user_email ?: '?', 0, 1));
                            $color_idx = abs(crc32($r->user_name ?: $r->user_email ?: '')) % count($colors);
                            $bg_color = $colors[$color_idx];
                            $plain_desc = strip_tags($r->description);
                            if (strlen($plain_desc) > 80) { $plain_desc = substr($plain_desc, 0, 80) . '...'; }
                        ?>
                            <tr onclick="viewActivity(this)" data-date="<?php echo date('D, M d, Y', strtotime($r->work_date)); ?>" data-user="<?php echo esc_view($r->user_name ?: $r->user_email ?: 'Unknown'); ?>" data-activity="<?php echo esc_view($r->activity_title); ?>" data-task="<?php echo esc_view($r->task_title); ?>">
                                <td class="text-muted small"><?php echo $idx; ?></td>
                                <td>
                                    <div class="fw-semibold" style="font-size:0.85rem;"><?php echo date('M d', strtotime($r->work_date)); ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;"><?php echo date('D, Y', strtotime($r->work_date)); ?></div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="user-avatar text-white" style="background:<?php echo $bg_color; ?>;"><?php echo $initial; ?></div>
                                        <div>
                                            <div class="fw-semibold" style="font-size:0.85rem;"><?php echo esc_view($r->user_name ?: 'Unknown'); ?></div>
                                            <div class="text-muted" style="font-size:0.7rem;"><?php echo esc_view($r->user_email); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($r->activity_title)): ?>
                                        <span class="activity-badge bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-text"></i><?php echo esc_view($r->activity_title); ?></span>
                                    <?php elseif (!empty($r->task_title)): ?>
                                        <span class="activity-badge bg-info bg-opacity-10 text-info"><i class="bi bi-check2-circle"></i><?php echo esc_view($r->task_title); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic">General</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="desc-preview"><?php echo esc_view($plain_desc); ?></div>
                                    <div class="d-none full-description"><?php echo sanitize_html_output($r->description); ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white border-top py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">Showing <?php echo count($rows); ?> entries</span>
                    <span class="text-muted small">Click a row to view full details</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Activity Detail Modal -->
<div class="modal fade" id="viewReportModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-fullscreen-sm-down modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" id="viewReportModalContent">
      <div class="modal-header bg-white">
        <div>
          <h5 class="modal-title fw-bold mb-0"><i class="bi bi-journal-text text-primary me-2"></i>Activity Details</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row mb-3 pb-3 border-bottom">
            <div class="col-sm-4">
                <div class="text-muted small fw-semibold mb-1">EMPLOYEE</div>
                <div id="viewRepUser" class="fw-bold"></div>
            </div>
            <div class="col-sm-4">
                <div class="text-muted small fw-semibold mb-1">DATE</div>
                <div id="viewRepDate" class="fw-semibold"></div>
            </div>
            <div class="col-sm-4">
                <div class="text-muted small fw-semibold mb-1">ACTIVITY / TASK</div>
                <div id="viewRepBadge"></div>
            </div>
        </div>
        <div class="text-muted small fw-semibold mb-2">DESCRIPTION</div>
        <div class="bg-light rounded-3 p-3" id="viewRepDesc" style="min-height:80px;"></div>
      </div>
      <div class="modal-footer bg-white border-top">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i>Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function viewActivity(row) {
    var $row = $(row);
    var date = $row.data('date');
    var userName = $row.data('user');
    var actTitle = $row.data('activity');
    var taskTitle = $row.data('task');
    var descHtml = $row.find('.full-description').html();

    $('#viewRepUser').text(userName);
    $('#viewRepDate').text(date);

    var badgeHtml = '';
    if (actTitle) {
        badgeHtml = '<span class="activity-badge bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-text me-1"></i>' + $('<span>').text(actTitle).html() + '</span>';
    } else if (taskTitle) {
        badgeHtml = '<span class="activity-badge bg-info bg-opacity-10 text-info"><i class="bi bi-check2-circle me-1"></i>' + $('<span>').text(taskTitle).html() + '</span>';
    } else {
        badgeHtml = '<span class="text-muted fst-italic">General Activity</span>';
    }
    $('#viewRepBadge').html(badgeHtml);
    $('#viewRepDesc').html(descHtml);

    var modalEl = document.getElementById('viewReportModal');
    var modal = bootstrap.Modal.getInstance(modalEl);
    if (!modal) { modal = new bootstrap.Modal(modalEl); }
    modal.show();
}
</script>

<?php $this->load->view('partials/footer'); ?>
