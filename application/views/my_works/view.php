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

  <nav aria-label="breadcrumb" class="small mb-2">

    <ol class="breadcrumb mb-0">

      <li class="breadcrumb-item"><a href="<?php echo site_url('my-works'); ?>">My Works</a></li>

      <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars(mb_strlen($item->title) > 40 ? mb_substr($item->title, 0, 40) . '…' : $item->title); ?></li>

    </ol>

  </nav>



  <div class="mw-detail-hero <?php echo my_works_row_border_class($item); ?>">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3">

      <div class="flex-grow-1">

        <h1 class="mw-detail-title mb-2"><?php echo htmlspecialchars($item->title); ?></h1>

        <div class="d-flex flex-wrap gap-2 align-items-center">

          <span class="badge bg-<?php echo $itemStatusColor; ?> px-3 py-2"><?php echo htmlspecialchars($itemStatusLabel); ?></span>

          <?php if (!empty($item->work_type)): ?><span class="badge bg-info text-dark"><?php echo htmlspecialchars(my_works_type_label($item->work_type)); ?></span><?php endif; ?>

          <?php if ((int) $item->is_urgent === 1): ?><span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Urgent</span><?php endif; ?>

          <?php if ((int) $item->is_important === 1): ?><span class="badge bg-warning text-dark"><i class="bi bi-star me-1"></i>Important</span><?php endif; ?>

          <?php if ($isOverdue): ?><span class="badge bg-danger-subtle text-danger border">Overdue</span><?php endif; ?>

          <?php foreach ($tagList as $tg): ?>

            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($tg); ?></span>

          <?php endforeach; ?>

        </div>

      </div>

      <div class="d-flex flex-wrap gap-2">

        <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('my-works'); ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>

        <?php if (!empty($can_edit)): ?>

          <a class="btn btn-primary btn-sm" href="<?php echo site_url('my-works/' . (int) $item->id . '/edit'); ?>"><i class="bi bi-pencil me-1"></i>Edit</a>

        <?php endif; ?>

        <?php if (!empty($can_delete)): ?>

          <form method="post" class="d-inline" action="<?php echo site_url('my-works/' . (int) $item->id . '/delete'); ?>" onsubmit="return confirm('Delete this work item permanently?');">

            <?php $this->load->view('my_works/_csrf'); ?>

            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>

          </form>

        <?php endif; ?>

      </div>

    </div>

  </div>



  <?php if ($this->session->flashdata('success')): ?>

    <div class="alert alert-success alert-dismissible fade show py-2" role="alert">

      <?php echo htmlspecialchars($this->session->flashdata('success')); ?>

      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>

    </div>

  <?php endif; ?>

  <?php if ($this->session->flashdata('error')): ?>

    <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">

      <?php echo htmlspecialchars($this->session->flashdata('error')); ?>

      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>

    </div>

  <?php endif; ?>



  <?php if (!empty($can_update_status)): ?>

  <div class="card shadow-sm border-0 mb-3">

    <div class="card-body py-3">

      <div class="small text-muted text-uppercase mb-2 fw-bold"><i class="bi bi-arrow-repeat me-1"></i>Update status</div>

      <div class="mw-status-pipeline">

        <?php foreach ($statusLabels as $k => $lbl): ?>

          <?php if ($item->status === $k): ?>

            <span class="active-status btn btn-sm btn-<?php echo isset($statusColors[$k]) ? $statusColors[$k] : 'secondary'; ?>"><?php echo htmlspecialchars($lbl); ?></span>

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

      <div class="card shadow-sm border-0 mb-3">

        <div class="card-body">

          <h2 class="h6 text-muted text-uppercase mb-3 fw-bold"><i class="bi bi-card-text me-1"></i>Details</h2>

          <?php if (!empty($item->details)): ?>

            <div class="mw-details mw-details-rich"><?php echo my_works_render_details($item->details); ?></div>

          <?php else: ?>

            <p class="text-muted mb-0">No details provided.</p>

          <?php endif; ?>



          <?php if (!empty($item->url)): ?>

            <hr class="my-3">

            <a href="<?php echo htmlspecialchars($item->url); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Open link</a>

          <?php endif; ?>



          <?php if (!empty($item->closing_comment)): ?>

            <hr class="my-3">

            <div class="small text-muted text-uppercase fw-bold mb-1">Closing comment</div>

            <div class="p-3 bg-light rounded border"><?php echo nl2br(htmlspecialchars($item->closing_comment)); ?></div>

          <?php endif; ?>

        </div>

      </div>



      <?php if (!empty($attachments)): ?>

      <div class="card shadow-sm border-0 mb-3">

        <div class="card-body">

          <?php $this->load->view('my_works/_attachments_panel', array(
            'attachments' => $attachments,
            'title' => $item->title,
          )); ?>

        </div>

      </div>

      <?php endif; ?>



      <?php if (!empty($can_comment)): ?>

      <div class="card shadow-sm border-0 mb-3">

        <div class="card-body">

          <h2 class="h6 text-muted text-uppercase mb-3 fw-bold"><i class="bi bi-chat-dots me-1"></i>Comments</h2>

          <?php if (empty($comments)): ?>

            <p class="text-muted small mb-3">No comments yet. Start the conversation below.</p>

          <?php else: ?>

            <div class="mw-comments mb-3">

              <?php foreach ($comments as $c): ?>

                <div class="comment-item">

                  <div class="d-flex justify-content-between small text-muted mb-1">

                    <strong class="text-dark"><?php echo htmlspecialchars(my_works_user_label($c->user_name, $c->user_email, $c->user_id)); ?></strong>

                    <span title="<?php echo htmlspecialchars($c->created_at); ?>"><?php echo my_works_format_when($c->created_at); ?></span>

                  </div>

                  <div><?php echo nl2br(htmlspecialchars($c->comment)); ?></div>

                </div>

              <?php endforeach; ?>

            </div>

          <?php endif; ?>

          <form method="post" action="<?php echo site_url('my-works/' . (int) $item->id . '/comment'); ?>">

            <?php $this->load->view('my_works/_csrf'); ?>

            <label class="form-label small fw-semibold">Add a comment</label>

            <textarea name="comment" class="form-control mb-2" rows="3" required placeholder="Share an update or note…"></textarea>

            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Post comment</button>

          </form>

        </div>

      </div>

      <?php endif; ?>



      <?php if (!empty($activity)): ?>

      <div class="card shadow-sm border-0">

        <div class="card-body">

          <h2 class="h6 text-muted text-uppercase mb-3 fw-bold"><i class="bi bi-clock-history me-1"></i>Activity</h2>

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

      <div class="card shadow-sm border-0 mw-info-card">

        <div class="card-body">

          <h2 class="h6 text-muted text-uppercase mb-3 fw-bold">Information</h2>



          <div class="info-row">

            <span class="info-icon"><i class="bi bi-person-plus"></i></span>

            <div>

              <div class="info-label">Created by</div>

              <div class="info-value"><?php echo htmlspecialchars($creatorLabel); ?><?php if (!empty($is_creator)): ?> <span class="badge bg-light text-dark border">You</span><?php endif; ?></div>

            </div>

          </div>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-person-check"></i></span>

            <div>

              <div class="info-label">Assignee</div>

              <div class="info-value"><?php echo htmlspecialchars($assigneeLabel); ?><?php if (!empty($is_assignee)): ?> <span class="badge bg-primary">You</span><?php endif; ?></div>

            </div>

          </div>

          <?php if (!empty($item->due_date)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-calendar-event"></i></span>

            <div>

              <div class="info-label">Due date</div>

              <div class="info-value <?php echo $isOverdue ? 'text-danger' : ''; ?>"><?php echo htmlspecialchars($item->due_date); ?><?php if ($isOverdue): ?> <span class="badge bg-danger-subtle text-danger border">Overdue</span><?php endif; ?></div>

            </div>

          </div>

          <?php endif; ?>

          <?php if (!empty($item->work_type)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-tag"></i></span>

            <div>

              <div class="info-label">Type</div>

              <div class="info-value"><?php echo htmlspecialchars(my_works_type_label($item->work_type)); ?></div>

            </div>

          </div>

          <?php endif; ?>

          <?php if (!empty($client_label)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-building"></i></span>

            <div>

              <div class="info-label">Client</div>

              <div class="info-value"><?php echo htmlspecialchars($client_label); ?></div>

            </div>

          </div>

          <?php endif; ?>

          <?php if (!empty($project_label)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-folder"></i></span>

            <div>

              <div class="info-label">Project</div>

              <div class="info-value"><?php echo htmlspecialchars($project_label); ?></div>

            </div>

          </div>

          <?php endif; ?>

          <?php if (!empty($item->url)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-link-45deg"></i></span>

            <div>

              <div class="info-label">URL</div>

              <div class="info-value"><a href="<?php echo htmlspecialchars($item->url); ?>" target="_blank" rel="noopener" class="text-break"><?php echo htmlspecialchars($item->url); ?></a></div>

            </div>

          </div>

          <?php endif; ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-clock"></i></span>

            <div>

              <div class="info-label">Created</div>

              <div class="info-value" title="<?php echo $item->created_at ? htmlspecialchars($item->created_at) : ''; ?>"><?php echo my_works_format_when($item->created_at); ?></div>

            </div>

          </div>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-arrow-repeat"></i></span>

            <div>

              <div class="info-label">Updated</div>

              <div class="info-value" title="<?php echo $item->updated_at ? htmlspecialchars($item->updated_at) : ''; ?>"><?php echo my_works_format_when($item->updated_at); ?></div>

            </div>

          </div>

          <?php if (!empty($item->closed_at)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-check-circle"></i></span>

            <div>

              <div class="info-label">Closed</div>

              <div class="info-value" title="<?php echo htmlspecialchars($item->closed_at); ?>"><?php echo my_works_format_when($item->closed_at); ?></div>

            </div>

          </div>

          <?php endif; ?>

        </div>

      </div>

    </div>

  </div>

</div>

<?php $this->load->view('my_works/_media_preview_modal'); ?>
<?php $this->load->view('my_works/_media_preview_scripts'); ?>
<?php $this->load->view('partials/footer'); ?>

