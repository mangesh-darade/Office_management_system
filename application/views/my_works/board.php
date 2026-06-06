<?php $this->load->view('partials/header', array('title' => 'My Works Board', 'extra_css' => array('assets/css/my-works.css'))); ?>
<?php
  $view_mode = 'board';
  $statusLabels = my_works_status_labels();
  $statusColors = my_works_status_colors();
?>
<div class="container-fluid py-3 mw-page">
  <?php $this->load->view('my_works/_toolbar', array('view_mode' => $view_mode, 'can_add' => !empty($can_add))); ?>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <?php $this->load->view('my_works/_scope_banner', array('scope' => isset($scope) ? $scope : array())); ?>

  <?php $this->load->view('my_works/_filters'); ?>

  <div class="mw-board">
    <?php foreach (array('new', 'in_progress', 'closed') as $colStatus): ?>
      <?php
        $colItems = isset($columns[$colStatus]) ? $columns[$colStatus] : array();
        $badge = isset($statusColors[$colStatus]) ? $statusColors[$colStatus] : 'secondary';
        $label = isset($statusLabels[$colStatus]) ? $statusLabels[$colStatus] : $colStatus;
      ?>
      <div class="mw-board-col">
        <div class="mw-board-col-head">
          <span><span class="badge bg-<?php echo $badge; ?> me-1">&nbsp;</span><?php echo htmlspecialchars($label); ?></span>
          <span class="badge bg-light text-dark border"><?php echo count($colItems); ?></span>
        </div>
        <div class="mw-board-col-body" data-status="<?php echo htmlspecialchars($colStatus); ?>">
          <?php if (empty($colItems)): ?>
            <div class="text-muted small text-center py-3 mw-board-empty">No items</div>
          <?php else: ?>
            <?php foreach ($colItems as $r): ?>
              <?php
                $forLabel = my_works_user_label($r->created_for_name, $r->created_for_email, $r->created_for);
                $borderClass = my_works_row_border_class($r);
                $overdue = my_works_is_overdue($r);
              ?>
              <div class="mw-board-card-wrap" draggable="true" data-id="<?php echo (int) $r->id; ?>" data-status="<?php echo htmlspecialchars($r->status); ?>">
                <a href="<?php echo site_url('my-works/' . (int) $r->id); ?>" class="mw-board-card <?php echo $borderClass; ?>">
                  <div class="title"><?php echo htmlspecialchars($r->title); ?></div>
                  <?php if (!empty($r->work_type) || !empty($r->client_name)): ?>
                  <div class="d-flex flex-wrap gap-1 mb-1">
                    <?php if (!empty($r->work_type)): ?><span class="mw-chip mw-chip-type" style="font-size:0.65rem;"><i class="bi bi-tag"></i><?php echo htmlspecialchars(my_works_type_label($r->work_type)); ?></span><?php endif; ?>
                    <?php if (!empty($r->client_name)): ?><span class="mw-chip mw-chip-client" style="font-size:0.65rem;"><i class="bi bi-building"></i><?php echo htmlspecialchars($r->client_name); ?></span><?php endif; ?>
                  </div>
                  <?php endif; ?>
                  <div class="d-flex flex-wrap gap-1 mb-1">
                    <?php if ((int) $r->is_urgent === 1): ?><span class="badge bg-danger">Urgent</span><?php endif; ?>
                    <?php if ((int) $r->is_important === 1): ?><span class="badge bg-warning text-dark">Important</span><?php endif; ?>
                    <?php if ($overdue): ?><span class="badge bg-danger-subtle text-danger border">Overdue</span><?php endif; ?>
                    <?php foreach (my_works_parse_tags(isset($r->tag) ? $r->tag : '') as $tg): ?>
                      <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($tg); ?></span>
                    <?php endforeach; ?>
                  </div>
                  <div class="small text-muted">
                    <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($forLabel); ?>
                    &middot; <?php echo my_works_format_when($r->updated_at); ?>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<script>
(function () {
  var csrfName = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var csrfHash = <?php echo json_encode($this->security->get_csrf_hash()); ?>;
  var updateUrl = <?php echo json_encode(site_url('my-works/update-status')); ?>;
  var dragEl = null;

  document.querySelectorAll('.mw-board-col-body').forEach(function (col) {
    col.addEventListener('dragover', function (e) {
      e.preventDefault();
      col.classList.add('mw-board-drop-target');
    });
    col.addEventListener('dragleave', function () {
      col.classList.remove('mw-board-drop-target');
    });
    col.addEventListener('drop', function (e) {
      e.preventDefault();
      col.classList.remove('mw-board-drop-target');
      if (!dragEl) return;
      var newStatus = col.getAttribute('data-status');
      var id = dragEl.getAttribute('data-id');
      var oldStatus = dragEl.getAttribute('data-status');
      if (!newStatus || !id || newStatus === oldStatus) return;
      col.insertBefore(dragEl, col.querySelector('.mw-board-empty'));
      var empty = col.querySelector('.mw-board-empty');
      if (empty) empty.remove();
      dragEl.setAttribute('data-status', newStatus);
      var body = new URLSearchParams();
      body.append('id', id);
      body.append('status', newStatus);
      body.append(csrfName, csrfHash);
      fetch(updateUrl, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
      }).then(function (r) { return r.json(); }).then(function (data) {
        if (!data || !data.ok) {
          window.location.reload();
        }
      }).catch(function () { window.location.reload(); });
      dragEl = null;
    });
  });

  document.querySelectorAll('.mw-board-card-wrap').forEach(function (card) {
    card.addEventListener('dragstart', function (e) {
      dragEl = card;
      e.dataTransfer.effectAllowed = 'move';
    });
    card.addEventListener('dragend', function () {
      dragEl = null;
      document.querySelectorAll('.mw-board-drop-target').forEach(function (c) {
        c.classList.remove('mw-board-drop-target');
      });
    });
  });
})();
</script>
<?php $this->load->view('partials/footer'); ?>
