<?php $this->load->view('partials/header', ['title' => 'Task Board']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Backlog & Kanban</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo site_url('tasks'); ?>">List View</a>
    </div>
  </div>

  <style>
    .kanban .card{border-radius:14px}
    .kanban .kanban-column{min-height:280px; padding:8px; background: repeating-linear-gradient(45deg, #f8f9fa, #f8f9fa 10px, #fafbfc 10px, #fafbfc 20px); border:1px dashed #e5e7eb; border-radius:10px}
    .kanban-card{background:#fff; border:1px solid #eef0f4; border-radius:12px; padding:10px 12px; margin-bottom:10px; box-shadow:0 1px 2px rgba(16,24,40,.05)}
    .kanban-card:hover{box-shadow:0 4px 12px rgba(16,24,40,.12); transform:translateY(-1px); transition:.2s ease}
    .xsmall{font-size:.78rem}
    .text-truncate-2{display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden}
    .avatar{width:26px; height:26px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:600}
    .avatar-bg{background:#eef2ff; color:#3730a3}
    .chip{background:#f1f5f9; color:#0f172a; padding:2px 8px; border-radius:999px}
    .col-header{font-weight:600}
    .empty-hint{color:#94a3b8}
  </style>

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
          <div class="card shadow-sm fade-in">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2 col-header">
                <div>
                  <?php echo $labels[$status]; ?>
                  <span class="badge bg-<?php echo $badges[$status]; ?> ms-2"><?php echo count($items); ?></span>
                </div>
              </div>
              <div class="kanban-column" data-status="<?php echo $status; ?>" ondragover="event.preventDefault();" ondrop="handleDrop(event, this)">
                <?php if (empty($items)): ?>
                  <div class="d-flex align-items-center justify-content-center empty-hint xsmall" style="height:120px;">No tasks</div>
                <?php endif; ?>
                <?php foreach ($items as $t): ?>
                  <?php $assignee = $assigneeName($t); $init = $initials($assignee); ?>
                  <div class="kanban-card" draggable="true" ondragstart="handleDragStart(event)" data-id="<?php echo (int)$t->id; ?>">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                      <div class="flex-grow-1">
                        <div class="fw-semibold small mb-1 text-truncate"><?php echo htmlspecialchars($t->title); ?></div>
                        <div class="text-muted xsmall text-truncate-2">
                          <?php 
                            $allowed = '<p><br><strong><em><b><i><ul><ol><li><a>';
                            $desc = isset($t->description) ? strip_tags($t->description, $allowed) : '';
                            echo $desc;
                          ?>
                        </div>
                      </div>
                      <div class="avatar avatar-bg" title="<?php echo htmlspecialchars($assignee ?: 'Unassigned'); ?>"><?php echo htmlspecialchars($init); ?></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 xsmall text-muted">
                      <span>#<?php echo (int)$t->id; ?></span>
                      <div class="d-flex align-items-center gap-2">
                        <?php if (!empty($t->project_name)): ?>
                          <span class="chip"><?php echo htmlspecialchars($t->project_name); ?></span>
                        <?php endif; ?>
                        <span class="chip">Assignee: <?php echo htmlspecialchars($assignee ?: 'Unassigned'); ?></span>
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
    function handleDragStart(e){
      draggedId = e.target?.dataset?.id || null;
      e.dataTransfer.effectAllowed = 'move';
    }
    async function handleDrop(e, column){
      e.preventDefault();
      const status = column.getAttribute('data-status');
      if(!draggedId || !status) return;
      try {
        const form = new FormData();
        form.append('id', draggedId);
        form.append('status', status);
        const res = await fetch('<?php echo site_url('tasks/update-status'); ?>', { method: 'POST', body: form, credentials: 'same-origin' });
        const json = await res.json();
        if(json && json.ok){
          column.prepend(document.querySelector(`.kanban-card[data-id="${draggedId}"]`));
        } else {
          alert(json.error || 'Failed to update');
        }
      } catch(err){
        alert('Network error');
      }
    }
  </script>
<?php $this->load->view('partials/footer'); ?>
