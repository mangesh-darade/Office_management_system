<?php 
$embed = (bool)$this->input->get('embed');
if (!$embed) {
  $this->load->view('partials/header', array(
    'title' => 'My Works', 
    'extra_css' => array('assets/css/my-works.css'),
  ));
} ?>

<?php

  $view_mode = 'list';

  $statusLabels = my_works_status_labels();

  $statusColors = my_works_status_colors();

  $uid = (int) $this->session->userdata('user_id');

?>

<div class="container-fluid <?php echo $embed ? 'p-1' : 'py-3'; ?> mw-page">

  <?php if (!$embed): ?>
  <?php $this->load->view('my_works/_toolbar', array(
    'view_mode' => $view_mode,
    'can_add' => !empty($can_add),
  )); ?>
  <?php endif; ?>



  <?php if ($this->session->flashdata('success')): ?>

    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">

      <?php echo esc_view($this->session->flashdata('success')); ?>

      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>

    </div>

  <?php endif; ?>

  <?php if ($this->session->flashdata('error')): ?>

    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">

      <?php echo esc_view($this->session->flashdata('error')); ?>

      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>

    </div>

  <?php endif; ?>



  <?php $this->load->view('my_works/_scope_banner', array('scope' => isset($scope) ? $scope : array())); ?>

  <?php $this->load->view('my_works/_filters'); ?>



  <?php if (empty($rows)): ?>

    <div class="card shadow-sm border-0">

      <div class="mw-empty">

        <div class="mw-empty-icon"><i class="bi bi-inbox"></i></div>

        <h3 class="mb-2">No work items found</h3>

        <p class="mb-3">Try adjusting your filters, or create a new item to get started.</p>

        <?php if (!empty($can_add)): ?>

          <a class="btn btn-primary" href="<?php echo site_url('my-works/create'); ?>"><i class="bi bi-plus-lg me-1"></i>Create work item</a>

        <?php endif; ?>

      </div>

    </div>

  <?php else: ?>



    <div class="d-md-none mw-card-list">

      <?php foreach ($rows as $r): ?>

        <?php $this->load->view('my_works/_mobile_card', array(
          'r' => $r,
          'can_quick_edit' => !empty($can_quick_edit),
          'uid' => $uid,
          'attachments_map' => isset($attachments_map) ? $attachments_map : null,
        )); ?>

      <?php endforeach; ?>

    </div>



    <div class="card shadow-sm border-0 d-none d-md-block mw-list-table-card">

      <div class="card-body p-0">

        <div class="table-responsive">

          <table id="myWorksTable" class="table table-hover align-middle mb-0 w-100">

            <thead>

              <tr>

                <th>Item</th>

                <th>Assignee</th>

                <th>Status</th>

                <th>Last update</th>

                <th>Priority</th>

                <th class="text-end">Estimate</th>

                <th class="text-end">Actions</th>

              </tr>

            </thead>

            <tbody>

              <?php foreach ($rows as $r): ?>

                <?php

                  $this->load->helper('my_works_status');

                  $stCode = isset($r->status) ? (string) $r->status : my_works_status_default_code();

                  $statusHex = my_works_status_hex_color($stCode);

                  $rowBg = my_works_status_row_bg_color($stCode);

                  $stColor = isset($statusColors[$r->status]) ? $statusColors[$r->status] : 'secondary';

                  $stLabel = isset($statusLabels[$r->status]) ? $statusLabels[$r->status] : $r->status;

                  $rowClass = my_works_row_border_class($r);

                  $canStatus = !empty($can_quick_edit) || (int) $r->created_for === $uid;

                  $overdue = my_works_is_overdue($r);

                  $updatedOrder = !empty($r->updated_at) ? strtotime($r->updated_at) : 0;

                  $priorityOrder = ((int) $r->is_urgent * 10) + (int) $r->is_important;

                  $forLabel = my_works_user_label($r->created_for_name, $r->created_for_email, $r->created_for);

                  $rowAttachments = my_works_row_attachments($r, isset($attachments_map) ? $attachments_map : null);

                  $initials = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $forLabel), 0, 2));

                  if ($initials === '') { $initials = '?'; }

                ?>

                <tr class="mw-list-status-row <?php echo $rowClass; ?>"
                    style="--mw-status-color:<?php echo esc_view($statusHex, ENT_QUOTES, 'UTF-8'); ?>;background-color:<?php echo esc_view($rowBg, ENT_QUOTES, 'UTF-8'); ?>;">

                  <td>

                    <a href="<?php echo site_url('my-works/' . (int) $r->id); ?>" class="mw-work-title">

                      <?php echo esc_view($r->title); ?>

                    </a>

                    <div class="mw-work-meta d-flex flex-wrap align-items-center gap-1 mt-1">

                      <?php if ($overdue): ?><span class="badge bg-danger-subtle text-danger border border-danger-subtle">Overdue</span><?php endif; ?>

                      <?php foreach (my_works_parse_tags(isset($r->tag) ? $r->tag : '') as $tg): ?>

                        <span class="badge bg-light text-dark border"><?php echo esc_view($tg); ?></span>

                      <?php endforeach; ?>

                      <?php $this->load->view('my_works/_attachment_badge', array('r' => $r, 'attachments_map' => isset($attachments_map) ? $attachments_map : null)); ?>

                      <?php if (!empty($r->url)): ?><a href="<?php echo esc_view($r->url); ?>" target="_blank" rel="noopener" class="text-primary" title="Open link" onclick="event.stopPropagation();"><i class="bi bi-link-45deg"></i></a><?php endif; ?>

                    </div>

                    <?php if (!empty($r->closing_comment)): ?>

                      <?php $cc = (string) $r->closing_comment; ?>

                      <div class="mw-work-meta text-truncate" style="max-width:16rem;" title="<?php echo esc_view($cc); ?>">

                        <i class="bi bi-chat-left-text me-1"></i><?php echo esc_view(strlen($cc) > 50 ? substr($cc, 0, 50) . '…' : $cc); ?>

                      </div>

                    <?php endif; ?>

                  </td>

                  <td>

                    <div class="mw-assignee">

                      <span class="mw-assignee-avatar" title="<?php echo esc_view($forLabel); ?>"><?php echo esc_view($initials); ?></span>

                      <span class="small"><?php echo esc_view($forLabel); ?></span>

                    </div>

                  </td>

                  <td data-order="<?php echo esc_view($r->status); ?>">

                    <?php if ($canStatus): ?>

                      <form method="post" action="<?php echo site_url('my-works/update-status'); ?>" class="d-inline mw-quick-status">

                        <?php $this->load->view('my_works/_csrf'); ?>

                        <input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">

                        <input type="hidden" name="redirect" value="<?php echo esc_view(current_url() . safe_query_suffix()); ?>">

                        <select name="status" class="form-select form-select-sm mw-status-select" onchange="this.form.submit()">

                          <?php foreach ($statusLabels as $k => $lbl): ?>

                            <option value="<?php echo $k; ?>" <?php echo $r->status === $k ? 'selected' : ''; ?>><?php echo esc_view($lbl); ?></option>

                          <?php endforeach; ?>

                        </select>

                      </form>

                    <?php else: ?>

                      <span class="badge bg-<?php echo $stColor; ?>"><?php echo esc_view($stLabel); ?></span>

                    <?php endif; ?>

                  </td>

                  <td class="small text-muted" data-order="<?php echo (int) $updatedOrder; ?>" title="<?php echo $r->updated_at ? esc_view($r->updated_at) : ''; ?>"><?php echo my_works_format_when($r->updated_at); ?></td>

                  <td data-order="<?php echo (int) $priorityOrder; ?>">
                    <?php if ((int) $r->is_urgent === 1): ?><span class="badge bg-danger">Urgent</span><?php endif; ?>
                    <?php if ((int) $r->is_important === 1): ?><span class="badge bg-warning text-dark">Important</span><?php endif; ?>
                    <?php if ((int) $r->is_urgent !== 1 && (int) $r->is_important !== 1): ?>
                      <span class="text-muted small">&mdash;</span>
                    <?php endif; ?>
                  </td>

                  <td class="text-end text-nowrap" data-order="<?php echo isset($r->estimate_hours) && $r->estimate_hours !== null && $r->estimate_hours !== '' ? (float) $r->estimate_hours : -1; ?>">
                    <?php
                      if (!function_exists('estimate_hours_display')) {
                          $this->load->helper('estimate_hours');
                      }
                      echo esc_view(estimate_hours_display(isset($r->estimate_hours) ? $r->estimate_hours : null));
                    ?>
                  </td>

                  <td class="text-end text-nowrap">
                    <div class="btn-group btn-group-sm mw-list-actions" role="group">
                      <a class="btn btn-outline-primary" href="<?php echo site_url('my-works/' . (int) $r->id); ?>" title="View"><i class="bi bi-eye"></i></a>
                      <?php if (!empty($rowAttachments)): ?>
                        <?php $this->load->view('my_works/_attachment_actions', array('r' => $r, 'attachments_map' => isset($attachments_map) ? $attachments_map : null)); ?>
                      <?php else: ?>
                        <button type="button" class="btn btn-outline-secondary" disabled title="No attachments"><i class="bi bi-paperclip"></i></button>
                      <?php endif; ?>
                      <button type="button"
                              class="btn btn-outline-secondary mw-list-comment-btn"
                              data-bs-toggle="modal"
                              data-bs-target="#mwListCommentModal"
                              data-work-id="<?php echo (int) $r->id; ?>"
                              data-work-title="<?php echo esc_view($r->title); ?>"
                              title="Add comment">
                        <i class="bi bi-chat-dots"></i>
                      </button>
                    </div>
                  </td>

                </tr>

              <?php endforeach; ?>

            </tbody>

          </table>

        </div>

      </div>

    </div>

  <?php endif; ?>

</div>

<?php $this->load->view('my_works/_list_comment_modal'); ?>
<?php $this->load->view('my_works/_media_preview_modal'); ?>
<?php $this->load->view('my_works/_media_preview_scripts'); ?>

<?php if (!empty($rows)): ?>
<script>
(function () {
  var modal = document.getElementById('mwListCommentModal');
  var form = document.getElementById('mwListCommentForm');
  if (!modal || !form) { return; }
  var commentBase = <?php echo json_encode(site_url('my-works/')); ?>;
  var listRedirect = <?php echo json_encode(current_url() . safe_query_suffix()); ?>;
  modal.addEventListener('show.bs.modal', function (event) {
    var btn = event.relatedTarget;
    if (!btn || !btn.classList.contains('mw-list-comment-btn')) { return; }
    var workId = btn.getAttribute('data-work-id') || '';
    var workTitle = btn.getAttribute('data-work-title') || '';
    form.action = commentBase + workId + '/comment';
    document.getElementById('mwListCommentRedirect').value = listRedirect;
    document.getElementById('mwListCommentWorkTitle').textContent = workTitle;
    var textarea = document.getElementById('mwListCommentText');
    if (textarea) { textarea.value = ''; }
    var fileInput = form.querySelector('input[type="file"]');
    if (fileInput) { fileInput.value = ''; }
  });
})();
</script>
<script src="<?php echo base_url('assets/js/my-works-attachment.js'); ?>"></script>
<?php endif; ?>

<?php if (!$embed) {
  $this->load->view('partials/footer');
} ?>

