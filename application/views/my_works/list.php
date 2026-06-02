<?php $this->load->view('partials/header', array('title' => 'My Works', 'extra_css' => array('assets/css/my-works.css'))); ?>
<?php
  $view_mode = 'list';
  $listUrl = site_url('my-works?view=list');
  $boardUrl = site_url('my-works?view=board');
  if (!empty($_SERVER['QUERY_STRING'])) {
    $qs = preg_replace('/(^|&)view=[^&]*/', '', (string) $_SERVER['QUERY_STRING']);
    $qs = ltrim($qs, '&');
    if ($qs !== '') {
      $listUrl .= '&' . $qs;
      $boardUrl .= '&' . $qs;
    }
  }
  $statusLabels = my_works_status_labels();
  $statusColors = my_works_status_colors();
  $uid = (int) $this->session->userdata('user_id');
?>
<div class="container-fluid py-3">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center mb-3 mw-page-header">
    <div>
      <h1 class="h4 mb-1 fw-bold"><i class="bi bi-clipboard2-check text-primary me-2"></i>My Works</h1>
      <p class="text-muted small mb-0">Track personal tasks, assignments, links, and files</p>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <div class="btn-group btn-group-sm" role="group" aria-label="View mode">
        <a class="btn btn-primary" href="<?php echo htmlspecialchars($listUrl); ?>"><i class="bi bi-list-ul me-1"></i><span class="d-none d-sm-inline">List</span></a>
        <a class="btn btn-outline-primary" href="<?php echo htmlspecialchars($boardUrl); ?>"><i class="bi bi-kanban me-1"></i><span class="d-none d-sm-inline">Board</span></a>
      </div>
      <?php if (!empty($can_add)): ?>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('my-works/create'); ?>"><i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New Work</span><span class="d-inline d-sm-none">New</span></a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php $this->load->view('my_works/_scope_banner', array('scope' => isset($scope) ? $scope : array())); ?>

  <?php $this->load->view('my_works/_filters'); ?>

  <?php if (empty($rows)): ?>
    <div class="card shadow-sm border-0">
      <div class="mw-empty">
        <i class="bi bi-inbox"></i>
        <p class="mb-2">No work items match your filters.</p>
        <?php if (!empty($can_add)): ?>
          <a class="btn btn-primary btn-sm" href="<?php echo site_url('my-works/create'); ?>">Create your first work item</a>
        <?php endif; ?>
      </div>
    </div>
  <?php else: ?>

    <div class="d-md-none mw-card-list">
      <?php foreach ($rows as $r): ?>
        <?php $this->load->view('my_works/_mobile_card', array('r' => $r, 'can_quick_edit' => !empty($can_quick_edit), 'uid' => $uid)); ?>
      <?php endforeach; ?>
    </div>

    <div class="card shadow-sm border-0 d-none d-md-block mw-list-table-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table id="myWorksTable" class="table table-hover align-middle mb-0 w-100">
            <thead class="table-light">
              <tr>
                <th>Title</th>
                <th>Tag</th>
                <th>Due</th>
                <th>Created by</th>
                <th>Created for</th>
                <th>Status</th>
                <th>Flags</th>
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
                ?>
                <tr class="<?php echo $rowClass; ?>">
                  <td>
                    <a href="<?php echo site_url('my-works/' . (int) $r->id); ?>" class="fw-semibold text-decoration-none">
                      <?php echo htmlspecialchars($r->title); ?>
                    </a>
                    <?php if (!empty($r->url)): ?>
                      <a href="<?php echo htmlspecialchars($r->url); ?>" target="_blank" rel="noopener" class="ms-1 text-muted" title="Open URL"><i class="bi bi-link-45deg"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($r->attachment_stored)): ?>
                      <i class="bi bi-paperclip text-muted ms-1" title="Has attachment"></i>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($r->tag)): ?>
                      <?php foreach (my_works_parse_tags($r->tag) as $tg): ?>
                        <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($tg); ?></span>
                      <?php endforeach; ?>
                    <?php else: ?>&mdash;<?php endif; ?>
                  </td>
                  <td class="small <?php echo $overdue ? 'text-danger fw-semibold' : 'text-muted'; ?>" data-order="<?php echo (int) $dueOrder; ?>">
                    <?php echo !empty($r->due_date) ? htmlspecialchars($r->due_date) : '&mdash;'; ?>
                  </td>
                  <td class="small"><?php echo htmlspecialchars(my_works_user_label($r->created_by_name, $r->created_by_email, $r->created_by)); ?></td>
                  <td class="small"><?php echo htmlspecialchars(my_works_user_label($r->created_for_name, $r->created_for_email, $r->created_for)); ?></td>
                  <td data-order="<?php echo htmlspecialchars($r->status); ?>">
                    <?php if ($canStatus): ?>
                      <form method="post" action="<?php echo site_url('my-works/update-status'); ?>" class="d-inline mw-quick-status">
                        <?php $this->load->view('my_works/_csrf'); ?>
                        <input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
                        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars(current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')); ?>">
                        <select name="status" class="form-select form-select-sm" style="min-width:7rem" onchange="this.form.submit()">
                          <?php foreach ($statusLabels as $k => $lbl): ?>
                            <option value="<?php echo $k; ?>" <?php echo $r->status === $k ? 'selected' : ''; ?>><?php echo htmlspecialchars($lbl); ?></option>
                          <?php endforeach; ?>
                        </select>
                      </form>
                    <?php else: ?>
                      <span class="badge bg-<?php echo $stColor; ?>"><?php echo htmlspecialchars($stLabel); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ((int) $r->is_urgent === 1): ?><span class="badge bg-danger">Urgent</span><?php endif; ?>
                    <?php if ((int) $r->is_important === 1): ?><span class="badge bg-warning text-dark">Important</span><?php endif; ?>
                    <?php if ($overdue): ?><span class="badge bg-danger">Overdue</span><?php endif; ?>
                    <?php if ((int) $r->is_urgent !== 1 && (int) $r->is_important !== 1 && !$overdue): ?>&mdash;<?php endif; ?>
                  </td>
                  <td class="small text-muted" data-order="<?php echo (int) $updatedOrder; ?>" title="<?php echo $r->updated_at ? htmlspecialchars($r->updated_at) : ''; ?>"><?php echo my_works_format_when($r->updated_at); ?></td>
                  <td class="text-end text-nowrap">
                    <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('my-works/' . (int) $r->id); ?>">View</a>
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
<?php $this->load->view('partials/footer'); ?>
