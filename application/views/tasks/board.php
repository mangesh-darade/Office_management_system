<?php $this->load->view('partials/header', [
  'title' => 'Task Board',
  'extra_css' => ['assets/css/tasks.css'],
]); ?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-2">
      <i class="bi bi-kanban me-2"></i>Task Board
    </h1>
    <p class="text-muted mb-0">Drag and drop tasks to update status</p>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <div class="input-group" style="width: 250px;">
      <input type="text" class="form-control" id="searchTasks" placeholder="Search tasks...">
      <button class="btn btn-outline-secondary" type="button" id="clearSearch">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <div class="btn-group" role="group">
      <button type="button" class="btn btn-outline-secondary" id="filterProject">
        <i class="bi bi-folder me-1"></i> Project
      </button>
      <button type="button" class="btn btn-outline-secondary" id="filterAssignee">
        <i class="bi bi-person me-1"></i> Assignee
      </button>
      <button type="button" class="btn btn-outline-secondary" id="filterPriority">
        <i class="bi bi-flag me-1"></i> Priority
      </button>
    </div>
    <a class="btn btn-primary" href="<?php echo site_url('tasks/create'); ?>">
      <i class="bi bi-plus-lg me-1"></i> New Task
    </a>
    <a class="btn btn-outline-secondary" href="<?php echo site_url('tasks'); ?>">
      <i class="bi bi-list me-1"></i> List View
    </a>
  </div>
</div>

<!-- Filter Dropdowns (Hidden by default) -->
<div class="row mb-3" id="filterRow" style="display: none;">
  <div class="col-12">
    <div class="card card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Project</label>
          <select class="form-select" id="projectFilter">
            <option value="">All Projects</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Assignee</label>
          <select class="form-select" id="assigneeFilter">
            <option value="">All Assignees</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label fw-semibold">Priority</label>
          <select class="form-select" id="priorityFilter">
            <option value="">All Priorities</option>
            <option value="high">High</option>
            <option value="medium">Medium</option>
            <option value="low">Low</option>
          </select>
        </div>
      </div>
      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary btn-sm" id="applyFilters">Apply Filters</button>
        <button class="btn btn-outline-secondary btn-sm" id="resetFilters">Reset</button>
      </div>
    </div>
  </div>
</div>

  <div class="kanban board-responsive">
    <?php
      $labels = [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'blocked' => 'Blocked',
      ];
      $badges = [
        'pending' => 'secondary',
        'in_progress' => 'info',
        'completed' => 'success',
        'blocked' => 'danger',
      ];
      $assigneeName = function($t){
        $name = '';
        if (isset($t->emp_name) && trim((string)$t->emp_name) !== '') { $name = $t->emp_name; }
        else if (isset($t->full_name) && trim((string)$t->full_name) !== '') { $name = $t->full_name; }
        else if (isset($t->name) && trim((string)$t->name) !== '') { $name = $t->name; }
        else if (isset($t->assignee_email)) { $name = $t->assignee_email; }
        return trim((string)$name);
      };
      $initials = function($text){
        $text = trim((string)$text);
        if ($text === '') return 'NA';
        $parts = preg_split('/\s+/', $text);
        $first = strtoupper(substr($parts[0],0,1));
        $last = isset($parts[count($parts)-1]) ? strtoupper(substr($parts[count($parts)-1],0,1)) : '';
        return $first.($last && $last!==$first ? $last : '');
      };
    ?>
    <div class="row g-3">
      <?php foreach ($columns as $status => $items): ?>
        <div class="col-12 col-md-6 col-lg-3">
          <div class="card shadow-sm kanban-column-card fade-in">
            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
              <div class="d-flex align-items-center">
                <div class="status-indicator status-<?php echo $status; ?> me-2"></div>
                <h6 class="mb-0 fw-semibold">
                  <?php echo $labels[$status]; ?>
                  <span class="badge bg-<?php echo $badges[$status]; ?> ms-2" id="count-<?php echo $status; ?>"><?php echo count($items); ?></span>
                </h6>
              </div>
              <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                  <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="#" onclick="expandColumn('<?php echo $status; ?>')">
                    <i class="bi bi-arrows-expand me-2"></i>Expand
                  </a></li>
                  <li><a class="dropdown-item" href="#" onclick="collapseColumn('<?php echo $status; ?>')">
                    <i class="bi bi-arrows-collapse me-2"></i>Collapse
                  </a></li>
                </ul>
              </div>
            </div>
            <div class="card-body p-2">
              <div class="kanban-column" data-status="<?php echo $status; ?>" ondragover="event.preventDefault();" ondrop="handleDrop(event, this)">
                <?php if (empty($items)): ?>
                  <div class="d-flex flex-column align-items-center justify-content-center empty-hint xsmall empty-hint-placeholder">
                    <i class="bi bi-inbox text-muted mb-2" style="font-size: 2rem;"></i>
                    <span class="text-muted">No tasks</span>
                    <small class="text-muted">Drag tasks here</small>
                  </div>
                <?php endif; ?>
                <?php foreach ($items as $t): ?>
                  <?php 
                    $assignee = $assigneeName($t); 
                    $init = $initials($assignee);
                    $priority = isset($t->priority) ? $t->priority : 'medium';
                    $created_date = isset($t->created_at) ? date('M j', strtotime($t->created_at)) : '';
                  ?>
                  <div class="kanban-card" draggable="true" ondragstart="handleDragStart(event)" data-id="<?php echo (int)$t->id; ?>" data-status="<?php echo $status; ?>" data-priority="<?php echo $priority; ?>" data-project="<?php echo htmlspecialchars(isset($t->project_name) ? $t->project_name : ''); ?>" data-assignee="<?php echo htmlspecialchars($assignee); ?>" data-title="<?php echo htmlspecialchars($t->title); ?>">
                    <div class="card-header d-flex justify-content-between align-items-center py-1">
                      <div class="d-flex align-items-center">
                        <span class="priority-indicator priority-<?php echo $priority; ?> me-2" title="Priority: <?php echo ucfirst($priority); ?>"></span>
                        <span class="task-id text-muted small">#<?php echo (int)$t->id; ?></span>
                      </div>
                      <div class="dropdown">
                        <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                          <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu">
                          <li><a class="dropdown-item" href="<?php echo site_url('tasks/'.$t->id); ?>">
                            <i class="bi bi-eye me-2"></i>View Details
                          </a></li>
                          <li><a class="dropdown-item" href="<?php echo site_url('tasks/'.$t->id.'/edit'); ?>">
                            <i class="bi bi-pencil me-2"></i>Edit
                          </a></li>
                          <li><hr class="dropdown-divider"></li>
                          <li><a class="dropdown-item text-danger" href="<?php echo site_url('tasks/'.$t->id.'/delete'); ?>" onclick="return confirm('Delete this task?')">
                            <i class="bi bi-trash me-2"></i>Delete
                          </a></li>
                        </ul>
                      </div>
                    </div>
                    <div class="card-body py-2">
                      <div class="fw-semibold mb-2 task-title">
                        <?php echo htmlspecialchars($t->title); ?>
                      </div>
                      <?php if (!empty($t->description)): ?>
                        <div class="text-muted small mb-2 task-description">
                          <?php 
                            $allowed = '<p><br><strong><em><b><i><ul><ol><li><a>';
                            $desc = isset($t->description) ? strip_tags($t->description, $allowed) : '';
                            echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                          ?>
                        </div>
                      <?php endif; ?>
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                          <?php if (!empty($t->project_name)): ?>
                            <span class="project-chip me-2" title="Project: <?php echo htmlspecialchars($t->project_name); ?>">
                              <i class="bi bi-folder me-1"></i><?php echo htmlspecialchars($t->project_name); ?>
                            </span>
                          <?php endif; ?>
                          <?php if ($created_date): ?>
                            <span class="date-chip text-muted">
                              <i class="bi bi-calendar me-1"></i><?php echo $created_date; ?>
                            </span>
                          <?php endif; ?>
                        </div>
                        <div class="avatar avatar-bg" title="<?php echo htmlspecialchars($assignee ?: 'Unassigned'); ?>">
                          <?php echo htmlspecialchars($init); ?>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script>
    let draggedId = null;
    let draggedElement = null;
    
    function handleDragStart(e){
      draggedId = e.target?.dataset?.id || null;
      draggedElement = e.target;
      e.target.style.opacity = '0.5';
      e.dataTransfer.effectAllowed = 'move';
    }
    
    document.addEventListener('dragend', function(e) {
      if (e.target.classList.contains('kanban-card')) {
        e.target.style.opacity = '';
      }
    });
    
    async function handleDrop(e, column){
      e.preventDefault();
      const status = column.getAttribute('data-status');
      if(!draggedId || !status) return;
      
      // Show loading state
      const card = document.querySelector(`.kanban-card[data-id="${draggedId}"]`);
      if (card) {
        card.style.opacity = '0.7';
        card.style.pointerEvents = 'none';
      }
      
      try {
        const form = new FormData();
        form.append('id', draggedId);
        form.append('status', status);
        
        const res = await fetch('<?php echo site_url('tasks/update-status'); ?>', { 
          method: 'POST', 
          body: form, 
          credentials: 'same-origin' 
        });
        
        const json = await res.json();
        if(json && json.ok){
          // Update card status
          card.dataset.status = status;
          column.prepend(card);
          updateColumnCounts();
          showNotification('Task status updated successfully', 'success');
        } else {
          showNotification(json.error || 'Failed to update task status', 'error');
        }
      } catch(err){
        showNotification('Network error. Please try again.', 'error');
      } finally {
        // Reset card state
        if (card) {
          card.style.opacity = '';
          card.style.pointerEvents = '';
        }
      }
    }
    
    function updateColumnCounts() {
      ['pending', 'in_progress', 'completed', 'blocked'].forEach(status => {
        const column = document.querySelector(`.kanban-column[data-status="${status}"]`);
        const count = column.querySelectorAll('.kanban-card').length;
        const badge = document.getElementById(`count-${status}`);
        if (badge) {
          badge.textContent = count;
        }
      });
    }
    
    function showNotification(message, type = 'info') {
      const alertDiv = document.createElement('div');
      alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
      alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
      alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      `;
      document.body.appendChild(alertDiv);
      
      setTimeout(() => {
        alertDiv.remove();
      }, 5000);
    }
    
    // Search functionality
    document.getElementById('searchTasks').addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      document.querySelectorAll('.kanban-card').forEach(card => {
        const title = card.dataset.title?.toLowerCase() || '';
        const project = card.dataset.project?.toLowerCase() || '';
        const assignee = card.dataset.assignee?.toLowerCase() || '';
        
        if (title.includes(searchTerm) || project.includes(searchTerm) || assignee.includes(searchTerm)) {
          card.style.display = '';
        } else {
          card.style.display = 'none';
        }
      });
    });
    
    document.getElementById('clearSearch').addEventListener('click', function() {
      document.getElementById('searchTasks').value = '';
      document.getElementById('searchTasks').dispatchEvent(new Event('input'));
    });
    
    // Filter toggle and functionality
    let filterRowVisible = false;
    document.getElementById('filterProject').addEventListener('click', function() {
      filterRowVisible = !filterRowVisible;
      document.getElementById('filterRow').style.display = filterRowVisible ? 'block' : 'none';
    });
    
    // Populate filter dropdowns
    document.addEventListener('DOMContentLoaded', function() {
      // Populate projects
      <?php if (isset($projects)): ?>
        const projectFilter = document.getElementById('projectFilter');
        <?php foreach ($projects as $project): ?>
          const option = document.createElement('option');
          option.value = '<?php echo $project->id; ?>';
          option.textContent = '<?php echo htmlspecialchars($project->name); ?>';
          projectFilter.appendChild(option);
        <?php endforeach; ?>
        <?php if ($filter_project_id): ?>
          projectFilter.value = '<?php echo $filter_project_id; ?>';
        <?php endif; ?>
      <?php endif; ?>
      
      // Populate assignees
      <?php if (isset($assignees)): ?>
        const assigneeFilter = document.getElementById('assigneeFilter');
        <?php foreach ($assignees as $assignee): ?>
          <?php 
            $name = '';
            if (isset($assignee->emp_name) && trim((string)$assignee->emp_name) !== '') { $name = $assignee->emp_name; }
            else if (isset($assignee->full_name) && trim((string)$assignee->full_name) !== '') { $name = $assignee->full_name; }
            else if (isset($assignee->name) && trim((string)$assignee->name) !== '') { $name = $assignee->name; }
            else { $name = $assignee->email; }
          ?>
          const option = document.createElement('option');
          option.value = '<?php echo $assignee->id; ?>';
          option.textContent = '<?php echo htmlspecialchars($name); ?>';
          assigneeFilter.appendChild(option);
        <?php endforeach; ?>
        <?php if ($filter_assigned_to): ?>
          assigneeFilter.value = '<?php echo $filter_assigned_to; ?>';
        <?php endif; ?>
      <?php endif; ?>
      
      // Set priority filter
      <?php if ($filter_priority): ?>
        document.getElementById('priorityFilter').value = '<?php echo $filter_priority; ?>';
      <?php endif; ?>
    });
    
    // Apply filters
    document.getElementById('applyFilters').addEventListener('click', function() {
      const projectId = document.getElementById('projectFilter').value;
      const assigneeId = document.getElementById('assigneeFilter').value;
      const priority = document.getElementById('priorityFilter').value;
      
      const params = new URLSearchParams();
      if (projectId) params.append('project_id', projectId);
      if (assigneeId) params.append('assigned_to', assigneeId);
      if (priority) params.append('priority', priority);
      
      const url = '<?php echo site_url('tasks/board'); ?>' + (params.toString() ? '?' + params.toString() : '');
      window.location.href = url;
    });
    
    // Reset filters
    document.getElementById('resetFilters').addEventListener('click', function() {
      window.location.href = '<?php echo site_url('tasks/board'); ?>';
    });
    
    // Column expand/collapse
    function expandColumn(status) {
      const column = document.querySelector(`.kanban-column[data-status="${status}"]`).parentElement.parentElement;
      column.classList.remove('collapsed');
    }
    
    function collapseColumn(status) {
      const column = document.querySelector(`.kanban-column[data-status="${status}"]`).parentElement.parentElement;
      column.classList.add('collapsed');
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      updateColumnCounts();
    });
  </script>
<?php $this->load->view('partials/footer'); ?>
