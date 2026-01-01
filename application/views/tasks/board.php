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
    <a class="btn btn-primary" href="<?php echo site_url('tasks/create'); ?>">
      <i class="bi bi-plus-lg me-1"></i> New Task
    </a>
    <a class="btn btn-outline-secondary" href="<?php echo site_url('tasks'); ?>">
      <i class="bi bi-list me-1"></i> List View
    </a>
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
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
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
                    <div class="kanban-card-header">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                          <span class="priority-indicator priority-<?php echo $priority; ?>" title="Priority: <?php echo ucfirst($priority); ?>"></span>
                          <span class="task-id text-muted small fw-mono">#<?php echo (int)$t->id; ?></span>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                          <button class="btn btn-sm btn-link text-muted p-0" type="button" onclick="showTaskPreview(<?php echo (int)$t->id; ?>)" title="Quick Preview">
                            <i class="bi bi-eye"></i>
                          </button>
                          <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                              <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
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
                      </div>
                    </div>
                    <div class="kanban-card-body">
                      <h6 class="task-title mb-2">
                        <?php echo htmlspecialchars($t->title); ?>
                      </h6>
                      <?php if (!empty($t->description)): ?>
                        <div class="task-description mb-2">
                          <?php 
                            $allowed = '<p><br><strong><em><b><i><ul><ol><li><a>';
                            $desc = isset($t->description) ? strip_tags($t->description, $allowed) : '';
                            $desc = trim($desc);
                            if (!empty($desc)) {
                              echo strlen($desc) > 80 ? htmlspecialchars(substr($desc, 0, 80)) . '...' : htmlspecialchars($desc);
                            }
                          ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <div class="kanban-card-footer">
                      <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center flex-wrap gap-1">
                          <?php if (!empty($t->project_name)): ?>
                            <span class="project-chip" title="Project: <?php echo htmlspecialchars($t->project_name); ?>">
                              <i class="bi bi-folder me-1"></i>
                              <span class="project-name"><?php echo htmlspecialchars(mb_substr($t->project_name, 0, 15)); ?><?php echo mb_strlen($t->project_name) > 15 ? '...' : ''; ?></span>
                            </span>
                          <?php endif; ?>
                          <?php if ($created_date): ?>
                            <span class="date-chip text-muted">
                              <i class="bi bi-calendar3 me-1"></i><?php echo $created_date; ?>
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

  <!-- Task Preview Modal -->
  <div class="modal fade" id="taskPreviewModal" tabindex="-1" aria-labelledby="taskPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-light">
          <h5 class="modal-title" id="taskPreviewModalLabel">
            <i class="bi bi-eye me-2"></i>Task Preview
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="taskPreviewContent">
          <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading task details...</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="button" class="btn btn-primary" id="editTaskBtn">
            <i class="bi bi-pencil me-1"></i>Edit Task
          </button>
          <button type="button" class="btn btn-info" id="viewTaskBtn">
            <i class="bi bi-eye me-1"></i>Full Details
          </button>
        </div>
      </div>
    </div>
  </div>

  <script>
    let draggedId = null;
    let draggedElement = null;
    let currentPreviewTask = null;
    
    // Task Preview functions
    async function showTaskPreview(taskId) {
      currentPreviewTask = taskId;
      const modal = new bootstrap.Modal(document.getElementById('taskPreviewModal'));
      const content = document.getElementById('taskPreviewContent');
      
      // Reset content to loading state
      content.innerHTML = `
        <div class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2 text-muted">Loading task details...</p>
        </div>
      `;
      
      // Show modal
      modal.show();
      
      try {
        const res = await fetch('<?php echo site_url('tasks/' . ''); ?>' + taskId + '/preview', {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            'Accept': 'text/html',
            'Content-Type': 'application/x-www-form-urlencoded'
          }
        });
        
        console.log('Response status:', res.status);
        console.log('Response ok:', res.ok);
        
        if (res.ok) {
          const html = await res.text();
          console.log('Response HTML length:', html.length);
          content.innerHTML = html;
          
          // Update modal footer buttons
          document.getElementById('editTaskBtn').onclick = () => {
            window.location.href = '<?php echo site_url('tasks/'); ?>' + taskId + '/edit';
          };
          document.getElementById('viewTaskBtn').onclick = () => {
            window.location.href = '<?php echo site_url('tasks/'); ?>' + taskId;
          };
        } else {
          console.error('Response not ok:', res.status, res.statusText);
          throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
      } catch (error) {
        console.error('Preview error:', error);
        content.innerHTML = `
          <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Failed to load task details: ${error.message}
            <br><small>Please try again or contact support.</small>
          </div>
        `;
      }
    }
    
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
        if (!column) return;
        // Count only visible cards (not hidden by search)
        const cards = column.querySelectorAll('.kanban-card');
        let visibleCount = 0;
        cards.forEach(card => {
          if (card.style.display !== 'none') {
            visibleCount++;
          }
        });
        const badge = document.getElementById(`count-${status}`);
        if (badge) {
          badge.textContent = visibleCount;
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
    
    // Column expand/collapse functions
    function expandColumn(status) {
      const column = document.querySelector(`.kanban-column[data-status="${status}"]`).parentElement.parentElement;
      column.classList.remove('collapsed');
    }
    
    function collapseColumn(status) {
      const column = document.querySelector(`.kanban-column[data-status="${status}"]`).parentElement.parentElement;
      column.classList.add('collapsed');
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchTasks');
    const clearBtn = document.getElementById('clearSearch');
    
    if (searchInput && clearBtn) {
      searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.kanban-card');
        
        cards.forEach(card => {
          const title = (card.dataset.title || '').toLowerCase();
          const project = (card.dataset.project || '').toLowerCase();
          const assignee = (card.dataset.assignee || '').toLowerCase();
          
          if (searchTerm === '' || 
              title.includes(searchTerm) || 
              project.includes(searchTerm) || 
              assignee.includes(searchTerm)) {
            card.style.display = '';
          } else {
            card.style.display = 'none';
          }
        });
        
        // Update column counts after filtering
        updateColumnCounts();
      });
      
      clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        const cards = document.querySelectorAll('.kanban-card');
        cards.forEach(card => {
          card.style.display = '';
        });
        updateColumnCounts();
      });
    }
    
    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
      updateColumnCounts();
    });
  </script>
<?php $this->load->view('partials/footer'); ?>
