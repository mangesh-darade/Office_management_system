<?php
  $embed = isset($embed) ? (bool)$embed : (bool)$this->input->get('embed');

  $this->load->view('partials/header', array('title' => $item->title, 'extra_css' => array('assets/css/my-works.css'), 'embed' => $embed));

  $statusLabels = my_works_status_labels();

  $statusColors = my_works_status_colors();

  $itemStatusColor = isset($statusColors[$item->status]) ? $statusColors[$item->status] : 'secondary';

  $itemStatusLabel = isset($statusLabels[$item->status]) ? $statusLabels[$item->status] : $item->status;

  $creatorLabel = my_works_user_label($creator ? $creator->name : '', $creator ? $creator->email : '', $item->created_by);

  $assigneeLabel = my_works_user_label($assignee ? $assignee->name : '', $assignee ? $assignee->email : '', $item->created_for);
  // Detail page: list every assignee name (not compact "Name +N").
  $assignee_name_parts = array();
  $assignee_seen = array();
  if ($assigneeLabel !== '') {
    $assignee_name_parts[] = $assigneeLabel;
    $assignee_seen[strtolower($assigneeLabel)] = true;
  }
  if (!empty($assignee_extra_names) && is_array($assignee_extra_names)) {
    foreach ($assignee_extra_names as $extra_name) {
      $extra_name = trim((string) $extra_name);
      if ($extra_name === '') {
        continue;
      }
      $key = strtolower($extra_name);
      if (isset($assignee_seen[$key])) {
        continue;
      }
      $assignee_seen[$key] = true;
      $assignee_name_parts[] = $extra_name;
    }
  }
  if (!empty($assignee_name_parts)) {
    $assigneeLabel = implode(', ', $assignee_name_parts);
  }

  $tagList = my_works_parse_tags(isset($item->tag) ? $item->tag : '');

  $isOverdue = my_works_is_overdue($item);

?>

<div class="container-fluid mw-detail-page mw-detail-compact <?php echo $embed ? 'mw-detail-page--embed p-0' : 'py-2'; ?>">

  <?php if (!$embed): ?>
  <nav aria-label="breadcrumb" class="small mb-1">

    <ol class="breadcrumb mb-0">

      <li class="breadcrumb-item"><a href="<?php echo site_url('my-works'); ?>">My Works</a></li>

      <li class="breadcrumb-item active" aria-current="page"><?php echo esc_view(mb_strlen($item->title) > 40 ? mb_substr($item->title, 0, 40) . '…' : $item->title); ?></li>

    </ol>

  </nav>
  <?php endif; ?>



  <div class="mw-detail-hero <?php echo my_works_row_border_class($item); ?>">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-2">

      <div class="d-flex align-items-start gap-2 flex-grow-1 min-w-0">
        <?php
          $parent_tab = trim((string) $this->input->get('parent_tab'));
          if ($parent_tab === 'team-dashboard') {
              $back_params = array('parent_tab' => 'team-dashboard');
              if ($embed) {
                  $back_params['embed'] = 1;
                  $back_url = site_url('tasks/my-dashboard') . '?' . http_build_query($back_params);
              } else {
                  $back_url = site_url('my-works') . '?tab=team-dashboard';
              }
          } elseif ($parent_tab !== '') {
              $back_url = site_url('my-works') . '?tab=' . rawurlencode($parent_tab);
          } else {
              $back_url = site_url('my-works');
          }
          $this->load->view('my_works/_back_btn', array('back_url' => $back_url));
        ?>
        <div class="min-w-0 flex-grow-1">

        <h1 class="mw-detail-title mb-1"><?php echo esc_view($item->title); ?></h1>

        <div class="d-flex flex-wrap gap-1 align-items-center mw-detail-badges">

          <span class="badge bg-<?php echo $itemStatusColor; ?>"><?php echo esc_view($itemStatusLabel); ?></span>

          <?php if (!empty($item->work_type)): ?><span class="badge bg-info text-dark"><?php echo esc_view(my_works_type_label($item->work_type)); ?></span><?php endif; ?>

          <?php if ((int) $item->is_urgent === 1): ?><span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Urgent</span><?php endif; ?>

          <?php if ((int) $item->is_important === 1): ?><span class="badge bg-warning text-dark"><i class="bi bi-star me-1"></i>Important</span><?php endif; ?>

          <?php if ($isOverdue): ?><span class="badge bg-danger-subtle text-danger border">Overdue</span><?php endif; ?>

          <?php foreach ($tagList as $tg): ?>

            <span class="badge bg-light text-dark border"><?php echo esc_view($tg); ?></span>

          <?php endforeach; ?>

        </div>

        </div>
      </div>

      <div class="btn-group btn-group-sm mw-detail-actions" role="group" aria-label="Actions">

        <?php if (!empty($can_edit)): ?>

          <a class="btn btn-outline-primary" href="<?php echo site_url('my-works/' . (int) $item->id . '/edit'); ?>" title="Edit"><i class="bi bi-pencil"></i></a>

        <?php endif; ?>

        <?php if (!empty($can_delete)): ?>

          <form method="post" class="d-inline" action="<?php echo site_url('my-works/' . (int) $item->id . '/delete'); ?>" onsubmit="return confirm('Delete this work item permanently?');">

            <?php $this->load->view('my_works/_csrf'); ?>

            <button type="submit" class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>

          </form>

        <?php endif; ?>

      </div>

    </div>

  </div>



  <?php if ($this->session->flashdata('success')): ?>

    <div class="alert alert-success alert-dismissible fade show py-1 px-2 mb-2 small" role="alert">

      <?php echo esc_view($this->session->flashdata('success')); ?>

      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>

    </div>

  <?php endif; ?>

  <?php if ($this->session->flashdata('error')): ?>

    <div class="alert alert-danger alert-dismissible fade show py-1 px-2 mb-2 small" role="alert">

      <?php echo esc_view($this->session->flashdata('error')); ?>

      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>

    </div>

  <?php endif; ?>



  <?php if (!empty($can_update_status)): ?>

  <div class="card border-0 shadow-sm mw-detail-card mb-2">

    <div class="card-body">

      <div class="mw-detail-section-label"><i class="bi bi-arrow-repeat me-1"></i>Update status</div>

      <div class="mw-status-pipeline">

        <?php foreach ($statusLabels as $k => $lbl): ?>

          <?php if ($item->status === $k): ?>

            <span class="active-status btn btn-sm btn-<?php echo isset($statusColors[$k]) ? $statusColors[$k] : 'secondary'; ?>"><?php echo esc_view($lbl); ?></span>

          <?php else: ?>

            <form method="post" action="<?php echo site_url('my-works/update-status'); ?>" class="d-inline">

              <?php $this->load->view('my_works/_csrf'); ?>

              <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">

              <input type="hidden" name="status" value="<?php echo $k; ?>">

              <button type="submit" class="btn btn-sm btn-outline-<?php echo isset($statusColors[$k]) ? $statusColors[$k] : 'secondary'; ?>"><?php echo esc_view($lbl); ?></button>

            </form>

          <?php endif; ?>

        <?php endforeach; ?>

      </div>

      <?php if (!empty($is_assignee) && empty($can_edit)): ?>

        <div class="form-text mt-1 mb-0">You are the assignee — you can change status without full edit access.</div>

      <?php endif; ?>

    </div>

  </div>

  <?php endif; ?>



  <div class="row g-2">

    <div class="col-lg-8 order-2 order-lg-1">

      <div class="card border-0 shadow-sm mw-detail-card mb-2">

        <div class="card-body">

          <h2 class="mw-detail-section-label"><i class="bi bi-card-text me-1"></i>Details</h2>

          <?php if (!empty($item->details)): ?>

            <div class="mw-details mw-details-rich"><?php echo my_works_render_details($item->details); ?></div>

          <?php else: ?>

            <p class="text-muted mb-0 small">No details provided.</p>

          <?php endif; ?>



          <?php if (!empty($item->url)): ?>

            <hr class="my-2">

            <a href="<?php echo esc_view($item->url); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Open link</a>

          <?php endif; ?>



          <?php if (!empty($item->closing_comment)): ?>

            <hr class="my-2">

            <div class="mw-detail-section-label mb-1">Closing comment</div>

            <div class="p-2 bg-light rounded border small"><?php echo nl2br(esc_view($item->closing_comment)); ?></div>

          <?php endif; ?>

        </div>

      </div>



      <?php if (!empty($attachments)): ?>

      <div class="card border-0 shadow-sm mw-detail-card mb-2">

        <div class="card-body">

          <?php $this->load->view('my_works/_attachments_panel', array(
            'attachments' => $attachments,
            'title' => $item->title,
          )); ?>

        </div>

      </div>

      <?php endif; ?>



      <?php if (!empty($can_comment)): ?>

      <div class="card border-0 shadow-sm mw-detail-card mb-2">

        <div class="card-body">

          <h2 class="mw-detail-section-label"><i class="bi bi-chat-dots me-1"></i>Comments</h2>

          <?php if (empty($comments)): ?>

            <p class="text-muted small mb-2">No comments yet. Start the conversation below.</p>

          <?php else: ?>

            <div class="mw-comments mb-2">

              <?php foreach ($comments as $c): ?>

                <div class="comment-item">

                  <div class="d-flex justify-content-between small text-muted mb-1">

                    <strong class="text-dark"><?php echo esc_view(my_works_user_label($c->user_name, $c->user_email, $c->user_id)); ?></strong>

                    <span title="<?php echo esc_view($c->created_at); ?>"><?php echo my_works_format_when($c->created_at); ?></span>

                  </div>

                  <div><?php echo nl2br(esc_view($c->comment)); ?></div>

                </div>

              <?php endforeach; ?>

            </div>

          <?php endif; ?>

          <form method="post" action="<?php echo site_url('my-works/' . (int) $item->id . '/comment'); ?>">

            <?php $this->load->view('my_works/_csrf'); ?>
            <?php if ($embed): ?>
              <input type="hidden" name="embed" value="1">
              <?php if ($this->input->get('parent_tab')): ?>
                <input type="hidden" name="parent_tab" value="<?php echo esc_view($this->input->get('parent_tab'), ENT_QUOTES, 'UTF-8'); ?>">
              <?php endif; ?>
              <input type="hidden" name="redirect" value="<?php echo esc_view(site_url('my-works/' . (int) $item->id) . '?embed=1' . ($this->input->get('parent_tab') ? '&parent_tab=' . rawurlencode((string) $this->input->get('parent_tab')) : ''), ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>

            <label class="form-label small fw-semibold mb-1">Add a comment</label>

            <textarea name="comment" class="form-control form-control-sm mb-2" rows="2" required placeholder="Share an update or note…"></textarea>

            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Post comment</button>

          </form>

        </div>

      </div>

      <?php endif; ?>



      <?php if (!empty($activity)): ?>

      <div class="card border-0 shadow-sm mw-detail-card mb-0">

        <div class="card-body">

          <h2 class="mw-detail-section-label"><i class="bi bi-clock-history me-1"></i>Activity</h2>

          <ul class="list-unstyled small mb-0 mw-activity-list">

            <?php foreach ($activity as $a): ?>

              <li class="d-flex gap-2 py-1 border-bottom">

                <span class="text-muted flex-shrink-0" title="<?php echo esc_view($a->created_at); ?>"><?php echo my_works_format_when($a->created_at); ?></span>

                <span>

                  <strong><?php echo esc_view(my_works_user_label($a->user_name, $a->user_email, $a->user_id)); ?></strong>

                  — <?php echo esc_view($a->action); ?>

                  <?php if (!empty($a->detail)): ?><span class="text-muted">(<?php echo esc_view($a->detail); ?>)</span><?php endif; ?>

                </span>

              </li>

            <?php endforeach; ?>

          </ul>

        </div>

      </div>

      <?php endif; ?>

    </div>



    <div class="col-lg-4 order-1 order-lg-2">

      <div class="card border-0 shadow-sm mw-info-card mw-detail-card">

        <div class="card-body">

          <h2 class="mw-detail-section-label">Information</h2>



          <div class="info-row">

            <span class="info-icon"><i class="bi bi-person-plus"></i></span>

            <div>

              <div class="info-label">Created by</div>

              <div class="info-value"><?php echo esc_view($creatorLabel); ?><?php if (!empty($is_creator)): ?> <span class="badge bg-light text-dark border">You</span><?php endif; ?></div>

            </div>

          </div>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-person-check"></i></span>

            <div>

              <div class="info-label">Assignee</div>

              <div class="info-value"><?php echo esc_view($assigneeLabel); ?><?php if (!empty($is_assignee)): ?> <span class="badge bg-primary">You</span><?php endif; ?></div>

            </div>

          </div>

          <?php if (!empty($item->due_date)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-calendar-event"></i></span>

            <div>

              <div class="info-label">Due date</div>

              <div class="info-value <?php echo $isOverdue ? 'text-danger' : ''; ?>"><?php echo esc_view($item->due_date); ?><?php if ($isOverdue): ?> <span class="badge bg-danger-subtle text-danger border">Overdue</span><?php endif; ?></div>

            </div>

          </div>

          <?php endif; ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-hourglass-split"></i></span>

            <div>

              <div class="info-label">Estimate (hrs)</div>

              <div class="info-value"><?php
                if (!function_exists('estimate_hours_display')) {
                    $this->load->helper('estimate_hours');
                }
                echo esc_view(estimate_hours_display(isset($item->estimate_hours) ? $item->estimate_hours : null));
              ?></div>

            </div>

          </div>

          <?php if (!empty($item->work_type)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-tag"></i></span>

            <div>

              <div class="info-label">Type</div>

              <div class="info-value"><?php echo esc_view(my_works_type_label($item->work_type)); ?></div>

            </div>

          </div>

          <?php endif; ?>

          <?php if (!empty($client_label)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-building"></i></span>

            <div>

              <div class="info-label">Client</div>

              <div class="info-value"><?php echo esc_view($client_label); ?></div>

            </div>

          </div>

          <?php endif; ?>

          <?php if (!empty($project_label)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-folder"></i></span>

            <div>

              <div class="info-label">Project</div>

              <div class="info-value"><?php echo esc_view($project_label); ?></div>

            </div>

          </div>

          <?php endif; ?>

          <?php if (!empty($item->url)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-link-45deg"></i></span>

            <div>

              <div class="info-label">URL</div>

              <div class="info-value"><a href="<?php echo esc_view($item->url); ?>" target="_blank" rel="noopener" class="text-break"><?php echo esc_view($item->url); ?></a></div>

            </div>

          </div>

          <?php endif; ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-clock"></i></span>

            <div>

              <div class="info-label">Created</div>

              <div class="info-value" title="<?php echo $item->created_at ? esc_view($item->created_at) : ''; ?>"><?php echo my_works_format_when($item->created_at); ?></div>

            </div>

          </div>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-arrow-repeat"></i></span>

            <div>

              <div class="info-label">Updated</div>

              <div class="info-value" title="<?php echo $item->updated_at ? esc_view($item->updated_at) : ''; ?>"><?php echo my_works_format_when($item->updated_at); ?></div>

            </div>

          </div>

          <?php if (!empty($item->closed_at)): ?>

          <div class="info-row">

            <span class="info-icon"><i class="bi bi-check-circle"></i></span>

            <div>

              <div class="info-label">Closed</div>

              <div class="info-value" title="<?php echo esc_view($item->closed_at); ?>"><?php echo my_works_format_when($item->closed_at); ?></div>

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
<?php $this->load->view('partials/footer', array('embed' => $embed)); ?>

