<?php $this->load->view('partials/header', array('title' => 'My Works', 'extra_css' => array('assets/css/my-works.css'))); ?>

<?php

  $view_mode = 'list';

  $statusLabels = my_works_status_labels();

  $statusColors = my_works_status_colors();

  $uid = (int) $this->session->userdata('user_id');

?>

<div class="container-fluid py-3 mw-page">

  <?php $this->load->view('my_works/_toolbar', array(
    'view_mode' => $view_mode,
    'can_add' => !empty($can_add),
  )); ?>



  <?php if ($this->session->flashdata('success')): ?>

    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">

      <?php echo htmlspecialchars($this->session->flashdata('success')); ?>

      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>

    </div>

  <?php endif; ?>

  <?php if ($this->session->flashdata('error')): ?>

    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">

      <?php echo htmlspecialchars($this->session->flashdata('error')); ?>

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

                <th>Work item</th>

                <th>Context</th>

                <th>Due</th>

                <th>Assignee</th>

                <th class="d-none d-xl-table-cell">Created by</th>

                <th>Status</th>

                <th>Updated</th>

                <th class="text-end">Actions</th>

              </tr>

            </thead>

            <tbody>

              <?php foreach ($rows as $r): ?>

                <?php

                  $stColor = isset($statusColors[$r->status]) ? $statusColors[$r->status] : 'secondary';

                  $stLabel = isset($statusLabels[$r->status]) ? $statusLabels[$r->status] : $r->status;

                  $rowClass = my_works_row_border_class($r);

                  $canStatus = !empty($can_quick_edit) || (int) $r->created_for === $uid;

                  $overdue = my_works_is_overdue($r);

                  $dueOrder = !empty($r->due_date) ? strtotime($r->due_date) : 0;

                  $updatedOrder = !empty($r->updated_at) ? strtotime($r->updated_at) : 0;

                  $forLabel = my_works_user_label($r->created_for_name, $r->created_for_email, $r->created_for);

                  $byLabel = my_works_user_label($r->created_by_name, $r->created_by_email, $r->created_by);

                  $initials = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $forLabel), 0, 2));

                  if ($initials === '') { $initials = '?'; }

                ?>

                <tr class="<?php echo $rowClass; ?>">

                  <td>

                    <a href="<?php echo site_url('my-works/' . (int) $r->id); ?>" class="mw-work-title">

                      <?php echo htmlspecialchars($r->title); ?>

                    </a>

                    <div class="mw-work-meta d-flex flex-wrap align-items-center gap-1 mt-1">

                      <?php if ((int) $r->is_urgent === 1): ?><span class="badge bg-danger">Urgent</span><?php endif; ?>

                      <?php if ((int) $r->is_important === 1): ?><span class="badge bg-warning text-dark">Important</span><?php endif; ?>

                      <?php if ($overdue): ?><span class="badge bg-danger-subtle text-danger border border-danger-subtle">Overdue</span><?php endif; ?>

                      <?php foreach (my_works_parse_tags(isset($r->tag) ? $r->tag : '') as $tg): ?>

                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($tg); ?></span>

                      <?php endforeach; ?>

                      <?php $this->load->view('my_works/_attachment_badge', array('r' => $r, 'attachments_map' => isset($attachments_map) ? $attachments_map : null)); ?>

                      <?php if (!empty($r->url)): ?><a href="<?php echo htmlspecialchars($r->url); ?>" target="_blank" rel="noopener" class="text-primary" title="Open link" onclick="event.stopPropagation();"><i class="bi bi-link-45deg"></i></a><?php endif; ?>

                    </div>

                    <?php if (!empty($r->closing_comment)): ?>

                      <?php $cc = (string) $r->closing_comment; ?>

                      <div class="mw-work-meta text-truncate" style="max-width:16rem;" title="<?php echo htmlspecialchars($cc); ?>">

                        <i class="bi bi-chat-left-text me-1"></i><?php echo htmlspecialchars(strlen($cc) > 50 ? substr($cc, 0, 50) . '…' : $cc); ?>

                      </div>

                    <?php endif; ?>

                  </td>

                  <td>

                    <div class="d-flex flex-wrap">

                      <?php if (!empty($r->work_type)): ?>

                        <span class="mw-chip mw-chip-type"><i class="bi bi-tag"></i><?php echo htmlspecialchars(my_works_type_label($r->work_type)); ?></span>

                      <?php endif; ?>

                      <?php if (!empty($r->client_name)): ?>

                        <span class="mw-chip mw-chip-client"><i class="bi bi-building"></i><?php echo htmlspecialchars($r->client_name); ?></span>

                      <?php endif; ?>

                      <?php if (!empty($r->project_name)): ?>

                        <span class="mw-chip mw-chip-project"><i class="bi bi-folder"></i><?php echo htmlspecialchars($r->project_name); ?></span>

                      <?php endif; ?>

                      <?php if (empty($r->work_type) && empty($r->client_name) && empty($r->project_name)): ?>

                        <span class="text-muted small">&mdash;</span>

                      <?php endif; ?>

                    </div>

                  </td>

                  <td class="<?php echo $overdue ? 'mw-due-overdue' : 'mw-due-ok'; ?>" data-order="<?php echo (int) $dueOrder; ?>">

                    <?php echo !empty($r->due_date) ? htmlspecialchars($r->due_date) : '<span class="text-muted">&mdash;</span>'; ?>

                  </td>

                  <td>

                    <div class="mw-assignee">

                      <span class="mw-assignee-avatar" title="<?php echo htmlspecialchars($forLabel); ?>"><?php echo htmlspecialchars($initials); ?></span>

                      <span class="small"><?php echo htmlspecialchars($forLabel); ?></span>

                    </div>

                  </td>

                  <td class="small d-none d-xl-table-cell text-muted"><?php echo htmlspecialchars($byLabel); ?></td>

                  <td data-order="<?php echo htmlspecialchars($r->status); ?>">

                    <?php if ($canStatus): ?>

                      <form method="post" action="<?php echo site_url('my-works/update-status'); ?>" class="d-inline mw-quick-status">

                        <?php $this->load->view('my_works/_csrf'); ?>

                        <input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">

                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars(current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')); ?>">

                        <select name="status" class="form-select form-select-sm mw-status-select" onchange="this.form.submit()">

                          <?php foreach ($statusLabels as $k => $lbl): ?>

                            <option value="<?php echo $k; ?>" <?php echo $r->status === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>

                          <?php endforeach; ?>

                        </select>

                      </form>

                    <?php else: ?>

                      <span class="badge bg-<?php echo $stColor; ?>"><?php echo htmlspecialchars($stLabel); ?></span>

                    <?php endif; ?>

                  </td>

                  <td class="small text-muted" data-order="<?php echo (int) $updatedOrder; ?>" title="<?php echo $r->updated_at ? htmlspecialchars($r->updated_at) : ''; ?>"><?php echo my_works_format_when($r->updated_at); ?></td>

                  <td class="text-end text-nowrap">
                    <div class="btn-group btn-group-sm mw-list-actions" role="group">
                      <a class="btn btn-outline-primary" href="<?php echo site_url('my-works/' . (int) $r->id); ?>" title="View details"><i class="bi bi-eye"></i></a>
                      <?php $this->load->view('my_works/_attachment_actions', array('r' => $r, 'attachments_map' => isset($attachments_map) ? $attachments_map : null)); ?>
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

<?php $this->load->view('my_works/_media_preview_modal'); ?>
<?php $this->load->view('my_works/_media_preview_scripts'); ?>

<?php $this->load->view('partials/footer'); ?>

