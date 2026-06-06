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
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
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
        <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars(isset($filters['from']) ? $filters['from'] : ''); ?>" />
      </div>
      <div class="col-md-3">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars(isset($filters['to']) ? $filters['to'] : ''); ?>" />
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
            <th style="width:220px">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="9" class="text-center text-muted">No leave requests found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="leave-row-clickable" data-user-id="<?php echo (int)$r->user_id; ?>" data-user-email="<?php echo htmlspecialchars(isset($r->user_email) ? $r->user_email : ''); ?>" style="cursor: pointer;">
              <td>
                <?php 
                  // Show employee name (user_name or first_name + last_name) with email
                  $emp_name = '';
                  if (!empty($r->user_name)) {
                    $emp_name = htmlspecialchars($r->user_name);
                  } elseif (!empty($r->emp_first_name)) {
                    $emp_name = htmlspecialchars(trim($r->emp_first_name . ' ' . (!empty($r->emp_last_name) ? $r->emp_last_name : '')));
                  }
                  if (!empty($emp_name)) {
                    echo $emp_name . '<br><small class="text-muted">' . htmlspecialchars(isset($r->user_email) ? $r->user_email : '') . '</small>';
                  } else {
                    echo htmlspecialchars(isset($r->user_email) ? $r->user_email : 'N/A');
                  }
                ?>
              </td>
              <td>
                <?php 
                  // Show lead name with email
                  if (!empty($r->lead_name) || !empty($r->lead_email)) {
                    $lead_display = '';
                    if (!empty($r->lead_name)) {
                      $lead_display = htmlspecialchars($r->lead_name);
                      if (!empty($r->lead_email)) {
                        $lead_display .= '<br><small class="text-muted">' . htmlspecialchars($r->lead_email) . '</small>';
                      }
                    } else {
                      $lead_display = htmlspecialchars($r->lead_email);
                    }
                    echo $lead_display;
                  } else {
                    echo '<span class="text-muted">N/A</span>';
                  }
                ?>
              </td>
              <td><?php echo htmlspecialchars(isset($r->type_name) ? $r->type_name : ''); ?></td>
              <td>
                <?php
                  $sd = isset($r->start_date) ? (string)$r->start_date : '';
                  $ed = isset($r->end_date) ? (string)$r->end_date : '';
                  if ($sd !== '' && $sd === $ed) {
                    echo htmlspecialchars($sd);
                  } else {
                    echo htmlspecialchars($sd.' to '.$ed);
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
                  echo htmlspecialchars($daysText);
                ?>
              </td>
              <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ', $r->status))); ?></span></td>
              <td><?php echo htmlspecialchars(isset($r->created_at) ? $r->created_at : ''); ?></td>
              <td class="text-truncate" style="max-width:min(280px, 50vw);"><?php echo htmlspecialchars(isset($r->reason) ? $r->reason : ''); ?></td>
              <td>
                <div class="d-flex flex-column gap-2" onclick="event.stopPropagation();">
                  <!-- Approve/Reject actions for managers - Single comment box -->
                  <?php 
                    // Disable actions only if request is in a final state
                    // allowing intermediate states (lead_approved, etc.) to be acted upon
                    $is_final = in_array($r->status, ['approved', 'rejected', 'cancelled'], true);
                    $approve_disabled = $is_final ? 'disabled' : '';
                    $reject_disabled = $is_final ? 'disabled' : '';
                  ?>
                  <div class="mb-2">
                    <input type="text" class="form-control form-control-sm mb-1" name="comments" id="comments_<?php echo $r->id; ?>" placeholder="Enter comments (optional)" value="<?php echo htmlspecialchars(isset($r->latest_remarks) ? $r->latest_remarks : ''); ?>" />
                    <div class="d-flex gap-1 align-items-center">
                      <?php if(function_exists('has_module_access') && (has_module_access('leave_approve') || has_module_access('leave_requests'))): ?>
                      <form method="post" action="<?php echo site_url('leave/approve/'.(int)$r->id); ?>" class="d-inline">
                        <input type="hidden" name="comments" value="" id="approve_comments_<?php echo $r->id; ?>" />
                        <button type="submit" class="btn btn-success btn-sm" <?php echo isset($approve_disabled) ? $approve_disabled : ''; ?> onclick="document.getElementById('approve_comments_<?php echo $r->id; ?>').value = document.getElementById('comments_<?php echo $r->id; ?>').value;">Approve</button>
                      </form>
                      <?php endif; ?>
                      <?php if(function_exists('has_module_access') && (has_module_access('leave_approve') || has_module_access('leave_requests'))): ?>
                      <button type="button" class="btn btn-danger btn-sm" onclick="rejectLeave(<?php echo $r->id; ?>)" <?php echo isset($reject_disabled) ? $reject_disabled : ''; ?>>Reject</button>
                      <?php endif; ?>
                      <?php if(function_exists('has_module_access') && (has_module_access('leaves_delete') || has_module_access('leave_requests'))): ?>
                      <?php if (isset($is_admin) && $is_admin): ?>
                        <!-- Admin Delete button - only visible to admin -->
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteLeave(<?php echo $r->id; ?>)" title="Delete Leave Request">
                          <i class="bi bi-trash"></i>
                        </button>
                      <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php if(function_exists('has_module_access') && (has_module_access('leaves_delete') || has_module_access('leave_requests'))): ?>
                  <?php if (isset($is_admin) && $is_admin): ?>
                    <!-- Hidden delete form for admin -->
                    <form method="post" action="<?php echo site_url('leave/delete/'.(int)$r->id); ?>" id="delete_form_<?php echo $r->id; ?>" style="display:none;">
                    </form>
                  <?php endif; ?>
                  <?php endif; ?>
                  <form method="post" action="<?php echo site_url('leave/reject/'.(int)$r->id); ?>" id="reject_form_<?php echo $r->id; ?>" style="display:none;">
                    <input type="hidden" name="comments" id="reject_comments_<?php echo $r->id; ?>" />
                  </form>
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
function rejectLeave(leaveId) {
  var comments = document.getElementById('comments_' + leaveId).value;
  document.getElementById('reject_comments_' + leaveId).value = comments;
  document.getElementById('reject_form_' + leaveId).submit();
}

function deleteLeave(leaveId) {
  if (confirm('Are you sure you want to delete this leave request?')) {
    document.getElementById('delete_form_' + leaveId).submit();
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
