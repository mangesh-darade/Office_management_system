<?php $this->load->view('partials/header', array('title' => 'Action Priority Matrix — My Works', 'extra_css' => array('assets/css/action-priority-matrix.css', 'assets/css/my-works.css'))); ?>
<?php
  $view_mode = 'matrix';
  $matrixCols = isset($matrix_columns) ? $matrix_columns : my_works_build_matrix_columns(isset($rows) ? $rows : array());
  $statusLabels = my_works_status_labels();
?>
<div class="container-fluid py-3 mw-page">
  <?php $this->load->view('my_works/_toolbar', array('view_mode' => $view_mode, 'can_add' => !empty($can_add))); ?>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <?php $this->load->view('my_works/_scope_banner', array('scope' => isset($scope) ? $scope : array())); ?>
  <?php $this->load->view('my_works/_filters'); ?>

  <?php
    $this->load->view('partials/apm_matrix_grid', array(
      'apm_title'      => 'Action Priority Matrix for My Works',
      'apm_date'       => date('M j, Y'),
      'matrix_columns' => $matrixCols,
      'apm_context'    => 'my_works',
      'apm_draggable'  => true,
      'apm_extra'      => array(
        'status_labels'    => $statusLabels,
        'can_view_projects'=> my_works_can_view_projects(),
      ),
    ));
  ?>

  <div class="small text-muted mt-2">
    Mark work items <strong>Important</strong> (impact) and <strong>Urgent</strong> or link to tasks (effort) on the edit form.
    Drag items between quadrants to reprioritize. Click title for work detail; click project name for project detail.
  </div>
</div>
<script>
(function () {
  var csrfName = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var csrfHash = <?php echo json_encode($this->security->get_csrf_hash()); ?>;
  var updateUrl = <?php echo json_encode(site_url('my-works/update-matrix')); ?>;
  var dragEl = null;

  function updateCounts() {
    document.querySelectorAll('.apm-quadrant-body').forEach(function (col) {
      var q = col.getAttribute('data-quadrant');
      var count = col.querySelectorAll('.apm-item-draggable-wrap').length;
      var badge = document.querySelector('.apm-matrix-count[data-quadrant="' + q + '"]');
      if (badge) badge.textContent = count;
      var empty = col.querySelector('.apm-matrix-empty');
      if (empty) empty.style.display = count ? 'none' : '';
    });
  }

  document.querySelectorAll('.apm-quadrant-body').forEach(function (col) {
    col.addEventListener('dragover', function (e) { e.preventDefault(); col.classList.add('apm-drop-target'); });
    col.addEventListener('dragleave', function () { col.classList.remove('apm-drop-target'); });
    col.addEventListener('drop', function (e) {
      e.preventDefault();
      col.classList.remove('apm-drop-target');
      var quadrant = col.getAttribute('data-quadrant');
      if (!dragEl || !quadrant) return;
      var id = dragEl.getAttribute('data-id');
      var form = new FormData();
      form.append('id', id);
      form.append('quadrant', quadrant);
      form.append(csrfName, csrfHash);
      fetch(updateUrl, { method: 'POST', body: form, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (json) {
          if (json && json.ok) {
            dragEl.setAttribute('data-quadrant', quadrant);
            col.insertBefore(dragEl, col.querySelector('.apm-matrix-empty'));
            updateCounts();
          }
        });
    });
  });

  document.querySelectorAll('.apm-item-draggable-wrap').forEach(function (card) {
    card.addEventListener('dragstart', function () { dragEl = card; card.classList.add('apm-item-draggable'); });
    card.addEventListener('dragend', function () { card.classList.remove('apm-item-draggable'); dragEl = null; });
  });

  updateCounts();
})();
</script>
<?php $this->load->view('partials/footer'); ?>
