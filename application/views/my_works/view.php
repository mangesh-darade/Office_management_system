<?php
  $this->load->view('partials/header', array('title' => $item->title, 'extra_css' => array('assets/css/my-works.css')));
  $statusLabels = my_works_status_labels();
  $statusColors = my_works_status_colors();
  $itemStatusColor = isset($statusColors[$item->status]) ? $statusColors[$item->status] : 'secondary';
  $itemStatusLabel = isset($statusLabels[$item->status]) ? $statusLabels[$item->status] : $item->status;
  $creatorLabel = my_works_user_label($creator ? $creator->name : '', $creator ? $creator->email : '', $item->created_by);
  $assigneeLabel = my_works_user_label($assignee ? $assignee->name : '', $assignee ? $assignee->email : '', $item->created_for);
  $tagList = my_works_parse_tags(isset($item->tag) ? $item->tag : '');
  $isOverdue = my_works_is_overdue($item);
?>
<div class="container-fluid py-3">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-3">
    <div class="flex-grow-1">
      <h1 class="h4 mb-2"><?php echo htmlspecialchars($item->title); ?></h1>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <span class="badge bg-<?php echo $itemStatusColor; ?>"><?php echo htmlspecialchars($itemStatusLabel); ?></span>
        <?php if ((int) $item->is_urgent === 1): ?><span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Urgent</span><?php endif; ?>
        <?php if ((int) $item->is_important === 1): ?><span class="badge bg-warning text-dark"><i class="bi bi-star me-1"></i>Important</span><?php endif; ?>
        <?php if ($isOverdue): ?><span class="badge bg-danger">Overdue</span><?php endif; ?>
        <?php foreach ($tagList as $tg): ?>
          <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($tg); ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="d-flex flex-wrap gap-2 w-100 w-md-auto">
      <a class="btn btn-outline-secondary btn-sm flex-grow-1 flex-sm-grow-0" href="<?php echo site_url('my-works'); ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
      <?php if (!empty($can_edit)): ?>
        <a class="btn btn-primary btn-sm flex-grow-1 flex-sm-grow-0" href="<?php echo site_url('my-works/' . (int) $item->id . '/edit'); ?>"><i class="bi bi-pencil me-1"></i>Edit</a>
      <?php endif; ?>
      <?php if (!empty($can_delete)): ?>
        <form method="post" class="flex-grow-1 flex-sm-grow-0" action="<?php echo site_url('my-works/' . (int) $item->id . '/delete'); ?>" onsubmit="return confirm('Delete this work item permanently?');">
          <?php $this->load->view('my_works/_csrf'); ?>
          <button type="submit" class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-trash me-1"></i>Delete</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <?php if (!empty($can_update_status)): ?>
  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body py-3">
      <div class="small text-muted text-uppercase mb-2 fw-semibold">Update status</div>
      <div class="mw-detail-actions d-flex flex-wrap gap-2">
        <?php foreach ($statusLabels as $k => $lbl): ?>
          <?php if ($item->status === $k): ?>
            <span class="btn btn-sm btn-<?php echo isset($statusColors[$k]) ? $statusColors[$k] : 'secondary'; ?> active"><?php echo htmlspecialchars($lbl); ?></span>
          <?php else: ?>
            <form method="post" action="<?php echo site_url('my-works/update-status'); ?>" class="d-inline">
              <?php $this->load->view('my_works/_csrf'); ?>
              <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
              <input type="hidden" name="status" value="<?php echo $k; ?>">
              <button type="submit" class="btn btn-sm btn-outline-<?php echo isset($statusColors[$k]) ? $statusColors[$k] : 'secondary'; ?>"><?php echo htmlspecialchars($lbl); ?></button>
            </form>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($is_assignee) && empty($can_edit)): ?>
        <div class="form-text mt-2">You are the assignee — you can change status without full edit access.</div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-8 order-2 order-lg-1">
      <div class="card shadow-sm border-0 <?php echo my_works_row_border_class($item); ?> mb-3">
        <div class="card-body">
          <h2 class="h6 text-muted text-uppercase mb-2">Details</h2>
          <?php if (!empty($item->details)): ?>
            <div class="mw-details"><?php echo nl2br(htmlspecialchars($item->details)); ?></div>
          <?php else: ?>
            <p class="text-muted mb-0">No details provided.</p>
          <?php endif; ?>

          <?php if (!empty($item->url) || !empty($item->attachment_stored)): ?>
            <hr>
            <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
              <?php if (!empty($item->url)): ?>
                <a href="<?php echo htmlspecialchars($item->url); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Open link</a>
              <?php endif; ?>
              <?php if (!empty($item->attachment_stored)): ?>
                <a href="<?php echo site_url('my-works/' . (int) $item->id . '/download'); ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1"></i><?php echo htmlspecialchars($item->attachment_original ? $item->attachment_original : 'Download attachment'); ?></a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($can_comment)): ?>
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
          <h2 class="h6 text-muted text-uppercase mb-3">Comments</h2>
          <?php if (empty($comments)): ?>
            <p class="text-muted small mb-3">No comments yet.</p>
          <?php else: ?>
            <div class="mw-comments mb-3">
              <?php foreach ($comments as $c): ?>
                <div class="border rounded p-2 mb-2 bg-light">
                  <div class="d-flex justify-content-between small text-muted mb-1">
                    <strong class="text-dark"><?php echo htmlspecialchars(my_works_user_label($c->user_name, $c->user_email, $c->user_id)); ?></strong>
                    <span title="<?php echo htmlspecialchars($c->created_at); ?>"><?php echo my_works_format_when($c->created_at); ?></span>
                  </div>
                  <div class="small"><?php echo nl2br(htmlspecialchars($c->comment)); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <form method="post" action="<?php echo site_url('my-works/' . (int) $item->id . '/comment'); ?>">
            <?php $this->load->view('my_works/_csrf'); ?>
            <label class="form-label small">Add comment</label>
            <textarea name="comment" class="form-control form-control-sm mb-2" rows="3" required placeholder="Write a note…"></textarea>
            <button type="submit" class="btn btn-sm btn-primary">Post comment</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($activity)): ?>
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h2 class="h6 text-muted text-uppercase mb-3">Activity</h2>
          <ul class="list-unstyled small mb-0 mw-activity-list">
            <?php foreach ($activity as $a): ?>
              <li class="d-flex gap-2 py-2 border-bottom">
                <span class="text-muted flex-shrink-0" title="<?php echo htmlspecialchars($a->created_at); ?>"><?php echo my_works_format_when($a->created_at); ?></span>
                <span>
                  <strong><?php echo htmlspecialchars(my_works_user_label($a->user_name, $a->user_email, $a->user_id)); ?></strong>
                  — <?php echo htmlspecialchars($a->action); ?>
                  <?php if (!empty($a->detail)): ?><span class="text-muted">(<?php echo htmlspecialchars($a->detail); ?>)</span><?php endif; ?>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <div class="col-lg-4 order-1 order-lg-2">
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h2 class="h6 text-muted text-uppercase mb-3">Information</h2>
          <dl class="row small mb-0 g-2">
            <dt class="col-5 text-muted">Created by</dt>
            <dd class="col-7 mb-0"><?php echo htmlspecialchars($creatorLabel); ?><?php if (!empty($is_creator)): ?> <span class="badge bg-light text-dark border">You</span><?php endif; ?></dd>
            <dt class="col-5 text-muted">Created for</dt>
            <dd class="col-7 mb-0"><?php echo htmlspecialchars($assigneeLabel); ?><?php if (!empty($is_assignee)): ?> <span class="badge bg-primary">You</span><?php endif; ?></dd>
            <?php if (!empty($item->due_date)): ?>
            <dt class="col-5 text-muted">Due date</dt>
            <dd class="col-7 mb-0 <?php echo $isOverdue ? 'text-danger fw-semibold' : ''; ?>"><?php echo htmlspecialchars($item->due_date); ?></dd>
            <?php endif; ?>
            <?php if (!empty($linked_task)): ?>
            <dt class="col-5 text-muted">Linked task</dt>
            <dd class="col-7 mb-0"><a href="<?php echo site_url('tasks/' . (int) $linked_task->id); ?>">#<?php echo (int) $linked_task->id; ?> <?php echo htmlspecialchars($linked_task->title); ?></a></dd>
            <?php endif; ?>
            <dt class="col-5 text-muted">Created</dt>
            <dd class="col-7 mb-0" title="<?php echo $item->created_at ? htmlspecialchars($item->created_at) : ''; ?>"><?php echo my_works_format_when($item->created_at); ?></dd>
            <dt class="col-5 text-muted">Updated</dt>
            <dd class="col-7 mb-0" title="<?php echo $item->updated_at ? htmlspecialchars($item->updated_at) : ''; ?>"><?php echo my_works_format_when($item->updated_at); ?></dd>
            <?php if (!empty($item->closed_at)): ?>
            <dt class="col-5 text-muted">Closed</dt>
            <dd class="col-7 mb-0" title="<?php echo htmlspecialchars($item->closed_at); ?>"><?php echo my_works_format_when($item->closed_at); ?></dd>
            <?php endif; ?>
          </dl>
        </div>
      </div>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
