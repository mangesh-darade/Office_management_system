<?php $this->load->view('partials/header', ['title' => 'Task Board']); ?>
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <h1 class="h4 mb-2 mb-sm-0">Backlog & Kanban</h1>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="<?php echo site_url('tasks'); ?>">List View</a>
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
    ?>
    <div class="row g-3">
      <?php foreach ($columns as $status => $items): ?>
        <div class="col-12 col-md-6 col-lg-3">
          <div class="card shadow-sm fade-in">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">
                  <?php echo $labels[$status]; ?>
                  <span class="badge bg-<?php echo $badges[$status]; ?> ms-2"><?php echo count($items); ?></span>
                </div>
              </div>
              <div class="kanban-column" data-status="<?php echo $status; ?>" ondragover="event.preventDefault();" ondrop="handleDrop(event, this)">
                <?php foreach ($items as $t): ?>
                  <div class="kanban-card hover-lift" draggable="true" ondragstart="handleDragStart(event)" data-id="<?php echo (int)$t->id; ?>">
                    <div class="fw-semibold small mb-1"><?php echo htmlspecialchars($t->title); ?></div>
                    <div class="text-muted xsmall text-truncate-2">
                      <?php 
                        $allowed = '<p><br><strong><em><b><i><ul><ol><li><a>';
                        $desc = isset($t->description) ? strip_tags($t->description, $allowed) : '';
                        echo $desc;
                      ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 xsmall text-muted">
                      <span>#<?php echo (int)$t->id; ?></span>
                      <span class="badge rounded-pill bg-light text-dark">Assignee: <?php echo (int)(isset($t->assigned_to) ? $t->assigned_to : 0); ?></span>
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
