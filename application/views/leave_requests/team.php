<?php $this->load->view('partials/header', ['title' => 'Team Leaves']); ?>
<div class="container-fluid py-3">
<div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
  <div>
    <h1 class="h4 mb-1 fw-bold"><i class="bi bi-people text-primary me-2"></i>Team Leave Requests</h1>
    <p class="text-muted small mb-0">Review and manage team leave applications</p>
  </div>
  <a class="btn btn-outline-secondary btn-sm mt-2 mt-sm-0" href="<?php echo site_url('leave/calendar'); ?>"><i class="bi bi-calendar-week me-1"></i>Calendar View</a>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="">All</option>
          <?php $statuses=['pending','lead_approved','hr_approved','approved','rejected','cancelled'];
          foreach ($statuses as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo (isset($filters['status']) && $filters['status']===$st)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ', $st)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">From</label>
        <input type="date" class="form-control" name="from" value="<?php echo esc_view(isset($filters['from']) ? $filters['from'] : ''); ?>" />
      </div>
      <div class="col-md-3">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?php echo esc_view(isset($filters['to']) ? $filters['to'] : ''); ?>" />
      </div>
      <div class="col-md-3 align-self-end">
        <button class="btn btn-outline-secondary">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Employee</th>
            <th>Lead</th>
            <th>Type</th>
            <th>Dates</th>
            <th>Days</th>
            <th>Status</th>
            <th>Applied On</th>
            <th>Reason</th>
            <th>Lead / Approve Comments</th>
            <th style="width:220px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="10" class="text-center text-muted">No leave requests found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="leave-row-clickable" data-user-id="<?php echo (int)$r->user_id; ?>" data-user-email="<?php echo esc_view(isset($r->user_email) ? $r->user_email : ''); ?>" style="cursor: pointer;">
              <td>
                <?php 
                  // Show employee name (user_name or first_name + last_name) with email
                  $emp_name = '';
                  if (!empty($r->user_name)) {
                    $emp_name = esc_view($r->user_name);
                  } elseif (!empty($r->emp_first_name)) {
                    $emp_name = esc_view(trim($r->emp_first_name . ' ' . (!empty($r->emp_last_name) ? $r->emp_last_name : '')));
                  }
                  if (!empty($emp_name)) {
                    echo $emp_name . '<br><small class="text-muted">' . esc_view(isset($r->user_email) ? $r->user_email : '') . '</small>';
                  } else {
                    echo esc_view(isset($r->user_email) ? $r->user_email : 'N/A');
                  }
                ?>
              </td>
              <td>
                <?php 
                  // Show lead name with email
                  if (!empty($r->lead_name) || !empty($r->lead_email)) {
                    $lead_display = '';
                    if (!empty($r->lead_name)) {
                      $lead_display = esc_view($r->lead_name);
                      if (!empty($r->lead_email)) {
                        $lead_display .= '<br><small class="text-muted">' . esc_view($r->lead_email) . '</small>';
                      }
                    } else {
                      $lead_display = esc_view($r->lead_email);
                    }
                    echo $lead_display;
                  } else {
                    echo '<span class="text-muted">N/A</span>';
                  }
                ?>
              </td>
              <td><?php echo esc_view(isset($r->type_name) ? $r->type_name : ''); ?></td>
              <td>
                <?php
                  $sd = isset($r->start_date) ? (string)$r->start_date : '';
                  $ed = isset($r->end_date) ? (string)$r->end_date : '';
                  if ($sd !== '' && $sd === $ed) {
                    echo esc_view($sd);
                  } else {
                    echo esc_view($sd.' to '.$ed);
                  }
                ?>
              </td>
              <td>
                <?php
                  $daysVal = isset($r->days) ? (float)$r->days : 0.0;
                  $daysText = (fmod($daysVal, 1.0) === 0.0)
                    ? (string)(int)$daysVal
                    : rtrim(rtrim(number_format($daysVal, 2, '.', ''), '0'), '.');
                  if ($daysVal === 0.5) {
                    $daysText .= ' (Half Day)';
                  }
                  echo esc_view($daysText);
                ?>
              </td>
              <td><span class="badge bg-info text-dark"><?php echo esc_view(ucfirst(str_replace('_',' ', $r->status))); ?></span></td>
              <td><?php echo esc_view(isset($r->created_at) ? $r->created_at : ''); ?></td>
              <td class="text-truncate" style="max-width:min(280px, 50vw);"><?php echo esc_view(isset($r->reason) ? $r->reason : ''); ?></td>
              <td style="max-width: 280px;">
                <?php
                  $history = (isset($r->approval_history) && is_array($r->approval_history)) ? $r->approval_history : array();
                ?>
                <?php if (!empty($history)): ?>
                  <div class="small d-flex flex-column gap-2">
                    <?php foreach ($history as $h): ?>
                      <div class="border-start border-2 ps-2 <?php echo (isset($h->decision) && $h->decision === 'rejected') ? 'border-danger' : 'border-success'; ?>">
                        <div class="fw-semibold"><?php echo esc_view(isset($h->approver_name) ? $h->approver_name : 'Approver'); ?></div>
                        <?php if (!empty($h->decision)): ?>
                        <span class="badge <?php echo ($h->decision === 'rejected') ? 'bg-danger' : 'bg-success'; ?>">
                          <?php echo esc_view(ucfirst($h->decision)); ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($h->remarks)): ?>
                        <div class="mt-1 text-body"><?php echo esc_view($h->remarks); ?></div>
                        <?php else: ?>
                        <div class="mt-1 text-muted">No comment text</div>
                        <?php endif; ?>
                        <?php if (!empty($h->decided_at)): ?>
                        <div class="text-muted" style="font-size:0.75rem;"><?php echo esc_view($h->decided_at); ?></div>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <span class="text-muted small">No comments</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex flex-column gap-2" onclick="event.stopPropagation();">
                  <?php
                    $is_final = in_array($r->status, array('approved', 'rejected', 'cancelled'), true);
                    $csrf_name = $this->security->get_csrf_token_name();
                    $csrf_hash = $this->security->get_csrf_hash();
                  ?>
                  <?php if (!$is_final && function_exists('has_module_access') && (has_module_access('leave_approve') || has_module_access('leave_requests'))): ?>
                  <form method="post" action="<?php echo site_url('leave/approve/' . (int) $r->id); ?>" class="leave-action-form">
                    <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="text" class="form-control form-control-sm mb-1" name="comments" placeholder="Enter comments (optional)" autocomplete="off" />
                    <div class="d-flex gap-1 align-items-center flex-wrap">
                      <button type="submit" class="btn btn-success btn-sm">Approve</button>
                      <button type="submit" class="btn btn-danger btn-sm" formaction="<?php echo site_url('leave/reject/' . (int) $r->id); ?>">Reject</button>
                      <?php if (function_exists('has_module_access') && (has_module_access('leaves_delete') || has_module_access('leave_requests')) && !empty($is_admin)): ?>
                      <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteLeave(<?php echo (int) $r->id; ?>)" title="Delete Leave Request">
                        <i class="bi bi-trash"></i>
                      </button>
                      <?php endif; ?>
                    </div>
                  </form>
                  <?php elseif (function_exists('has_module_access') && (has_module_access('leaves_delete') || has_module_access('leave_requests')) && !empty($is_admin)): ?>
                  <button type="button" class="btn btn-outline-danger btn-sm align-self-start" onclick="deleteLeave(<?php echo (int) $r->id; ?>)" title="Delete Leave Request">
                    <i class="bi bi-trash"></i>
                  </button>
                  <?php else: ?>
                  <span class="text-muted small">—</span>
                  <?php endif; ?>
                  <?php if (function_exists('has_module_access') && (has_module_access('leaves_delete') || has_module_access('leave_requests')) && !empty($is_admin)): ?>
                  <form method="post" action="<?php echo site_url('leave/delete/' . (int) $r->id); ?>" id="delete_form_<?php echo (int) $r->id; ?>" class="d-none">
                    <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
                  </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Tasks Modal -->
<div class="modal fade" id="tasksModal" tabindex="-1" aria-labelledby="tasksModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="max-width: 85%; width: 85%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tasksModalLabel">Employee Tasks</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="tasksLoading" class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Loading tasks...</p>
        </div>
        <div id="tasksError" class="alert alert-danger" style="display: none;"></div>
        <div id="tasksContent" style="display: none;">
          <div class="row mb-4">
            <div class="col-md-6">
              <p class="mb-2"><strong>Employee:</strong> <span id="employeeEmail" class="text-primary"></span></p>
            </div>
            <div class="col-md-6">
              <p class="mb-2"><strong>Total Tasks:</strong> <span id="tasksCount" class="badge bg-info text-dark fs-6"></span></p>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th style="width: 30%;">Title & Description</th>
                  <th style="width: 15%;">Project</th>
                  <th style="width: 12%;">Status</th>
                  <th style="width: 12%;">Priority</th>
                  <th style="width: 15%;">Due Date</th>
                  <th style="width: 16%;">Hours</th>
                </tr>
              </thead>
              <tbody id="tasksTableBody">
              </tbody>
            </table>
          </div>
        </div>
        <div id="tasksEmpty" class="text-center text-muted py-4" style="display: none;">
          <p>No active tasks found for this employee.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function deleteLeave(leaveId) {
  if (confirm('Are you sure you want to delete this leave request?')) {
    var form = document.getElementById('delete_form_' + leaveId);
    if (form) {
      form.submit();
    }
  }
}

// Handle row click to show tasks
document.addEventListener('DOMContentLoaded', function() {
  var rows = document.querySelectorAll('.leave-row-clickable');
  var modal = new bootstrap.Modal(document.getElementById('tasksModal'));
  
  rows.forEach(function(row) {
    row.addEventListener('click', function(e) {
      // Don't trigger if clicking on action buttons
      if (e.target.closest('button, input, form')) {
        return;
      }
      
      var userId = row.getAttribute('data-user-id');
      var userEmail = row.getAttribute('data-user-email');
      
      if (!userId) return;
      
      // Show modal
      document.getElementById('employeeEmail').textContent = userEmail || 'N/A';
      document.getElementById('tasksLoading').style.display = 'block';
      document.getElementById('tasksError').style.display = 'none';
      document.getElementById('tasksContent').style.display = 'none';
      document.getElementById('tasksEmpty').style.display = 'none';
      document.getElementById('tasksTableBody').innerHTML = '';
      
      modal.show();
      
      // Fetch tasks via AJAX
      fetch('<?php echo site_url('leave/get-employee-tasks'); ?>/' + userId)
        .then(response => response.json())
        .then(data => {
          document.getElementById('tasksLoading').style.display = 'none';
          
          if (data.success) {
            if (data.tasks && data.tasks.length > 0) {
              document.getElementById('tasksCount').textContent = data.count;
              document.getElementById('tasksContent').style.display = 'block';
              
              var tbody = document.getElementById('tasksTableBody');
              tbody.innerHTML = '';
              
              data.tasks.forEach(function(task) {
                var row = document.createElement('tr');
                
                // Status badge color
                var statusClass = 'bg-secondary';
                if (task.status === 'pending') statusClass = 'bg-warning text-dark';
                else if (task.status === 'in_progress') statusClass = 'bg-info text-dark';
                else if (task.status === 'blocked') statusClass = 'bg-danger';
                
                // Priority badge color
                var priorityClass = 'bg-secondary';
                if (task.priority === 'urgent') priorityClass = 'bg-danger';
                else if (task.priority === 'high') priorityClass = 'bg-warning text-dark';
                else if (task.priority === 'medium') priorityClass = 'bg-info text-dark';
                else if (task.priority === 'low') priorityClass = 'bg-secondary';
                
                // Strip HTML tags from description for display
                var descriptionText = task.description.replace(/<[^>]*>/g, '').trim();
                if (!descriptionText || descriptionText === 'No description') {
                  descriptionText = '<em class="text-muted">No description</em>';
                }
                
                row.innerHTML = 
                  '<td><strong class="fs-6">' + task.title + '</strong><br><small class="text-muted mt-1 d-block">' + descriptionText + '</small></td>' +
                  '<td><span class="badge bg-secondary">' + task.project_name + '</span></td>' +
                  '<td><span class="badge ' + statusClass + ' px-3 py-2">' + task.status.replace('_', ' ').toUpperCase() + '</span></td>' +
                  '<td><span class="badge ' + priorityClass + ' px-3 py-2">' + task.priority.toUpperCase() + '</span></td>' +
                  '<td>' + task.due_date + '</td>' +
                  '<td><small>Est: <strong>' + task.estimate_hours + '</strong></small><br><small>Act: <strong>' + task.actual_hours + '</strong></small></td>';
                
                tbody.appendChild(row);
              });
            } else {
              document.getElementById('tasksEmpty').style.display = 'block';
            }
          } else {
            document.getElementById('tasksError').textContent = data.message || 'Failed to load tasks';
            document.getElementById('tasksError').style.display = 'block';
          }
        })
        .catch(error => {
          document.getElementById('tasksLoading').style.display = 'none';
          document.getElementById('tasksError').textContent = 'Error loading tasks: ' + error.message;
          document.getElementById('tasksError').style.display = 'block';
        });
    });
  });
});
</script>
</div>
<?php $this->load->view('partials/footer'); ?>
