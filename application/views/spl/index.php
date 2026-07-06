<?php
$this->load->view('partials/header', array(
    'title' => 'SPL — My Reward',
    'extra_css' => array('assets/css/spl.css'),
));
$levelName = $level ? $level->name : ucfirst($summary->current_level_code);
$levelColor = $level ? $level->badge_color : '#6c757d';
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
$reward_period = isset($reward_period) ? $reward_period : 'week';
$reward_bounds = isset($reward_bounds) ? $reward_bounds : spl_reward_period_bounds($reward_period);
$reward_totals = isset($reward_totals) ? $reward_totals : array('positive' => 0, 'negative' => 0, 'net' => 0, 'pending_count' => 0, 'approved_count' => 0, 'rejected_count' => 0);
$reward_period_options = array(
    'today' => 'Today',
    'week' => 'This week',
    'month' => 'This month',
    'all' => 'All time',
);
?>

<div class="container-fluid py-2 spl-page spl-index-page">
  <div class="spl-index-hero">
    <div class="spl-index-hero-main">
      <div class="spl-index-eyebrow">SPL Rewards</div>
      <h1 class="spl-index-title mb-0"><i class="bi bi-trophy-fill me-1"></i>My Reward</h1>
    </div>
    <div class="spl-index-hero-actions">
      <?php if (!empty($can_groups)): ?>
      <a class="btn btn-outline-light btn-sm" href="<?php echo site_url('spl/groups'); ?>"><i class="bi bi-collection me-1"></i>Groups</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 mt-3 mb-0"><?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 mt-3 mb-0"><?php echo esc_view((string) $this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <ul class="nav nav-pills spl-index-tabs mt-2" role="tablist">
    <?php if (!empty($can_my_reward)): ?>
    <li class="nav-item">
      <button class="nav-link <?php echo $active_tab === 'my-reward' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#spl-tab-my-reward" type="button">
        <i class="bi bi-star me-1"></i>My Reward
      </button>
    </li>
    <?php endif; ?>
    <?php if (!empty($can_levels)): ?>
    <li class="nav-item">
      <button class="nav-link <?php echo $active_tab === 'levels' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#spl-tab-levels" type="button">
        <i class="bi bi-bar-chart-steps me-1"></i>Leaderboard
      </button>
    </li>
    <?php endif; ?>
    <?php if (!empty($can_submit)): ?>
    <li class="nav-item">
      <button class="nav-link <?php echo $active_tab === 'activity' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#spl-tab-activity" type="button">
        <i class="bi bi-plus-circle me-1"></i>Submit Activity
      </button>
    </li>
    <?php endif; ?>
    <?php if (!empty($can_approve)): ?>
    <li class="nav-item">
      <button class="nav-link <?php echo $active_tab === 'approvals' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#spl-tab-approvals" type="button">
        <i class="bi bi-check2-square me-1"></i>Approvals
        <?php if (!empty($approval_counts['pending'])): ?>
        <span class="badge bg-danger ms-1"><?php echo (int) $approval_counts['pending']; ?></span>
        <?php endif; ?>
      </button>
    </li>
    <?php endif; ?>
    <?php if (!empty($can_rules)): ?>
    <li class="nav-item">
      <button class="nav-link <?php echo $active_tab === 'rules' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#spl-tab-rules" type="button">
        <i class="bi bi-list-check me-1"></i>Rules
      </button>
    </li>
    <?php endif; ?>
  </ul>

  <div class="tab-content mt-2">
    <?php if (!empty($can_my_reward)): ?>
    <div class="tab-pane fade <?php echo $active_tab === 'my-reward' ? 'show active' : ''; ?>" id="spl-tab-my-reward">
      <div class="spl-stat-grid mb-2">
        <div class="spl-stat-tile">
          <div class="spl-stat-tile-icon is-primary"><i class="bi bi-star-fill"></i></div>
          <div class="spl-stat-tile-body">
            <div class="spl-stat-tile-value"><?php echo number_format((float) $summary->lifetime_points, 0); ?></div>
            <div class="spl-stat-tile-label">Lifetime</div>
          </div>
        </div>
        <div class="spl-stat-tile">
          <div class="spl-stat-tile-icon" style="color:<?php echo esc_view($levelColor, ENT_QUOTES, 'UTF-8'); ?>;background:<?php echo esc_view($levelColor, ENT_QUOTES, 'UTF-8'); ?>1a"><i class="bi bi-award-fill"></i></div>
          <div class="spl-stat-tile-body">
            <div class="spl-stat-tile-value" style="color:<?php echo esc_view($levelColor, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($levelName, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="spl-stat-tile-label">Level</div>
          </div>
        </div>
        <div class="spl-stat-tile">
          <div class="spl-stat-tile-icon is-month"><i class="bi bi-calendar3"></i></div>
          <div class="spl-stat-tile-body">
            <div class="spl-stat-tile-value"><?php echo number_format((float) $summary->month_points, 0); ?></div>
            <div class="spl-stat-tile-label">This month</div>
          </div>
        </div>
      </div>

      <div class="spl-panel-card">
        <div class="spl-panel-head spl-panel-head-stack">
          <div class="spl-panel-head-top">
            <div>
              <h2 class="spl-panel-title">Recent activity</h2>
              <p class="spl-panel-sub">Approved points in <strong><?php echo esc_view($reward_bounds['label'], ENT_QUOTES, 'UTF-8'); ?></strong> count toward your total</p>
            </div>
            <div class="spl-reward-period-tabs" role="tablist" aria-label="Activity period">
              <?php foreach ($reward_period_options as $periodKey => $periodLabel): ?>
              <a class="spl-reward-period-tab<?php echo $reward_period === $periodKey ? ' is-active' : ''; ?>" href="<?php echo site_url('spl?tab=my-reward&reward_period=' . urlencode($periodKey)); ?>">
                <?php echo esc_view($periodLabel, ENT_QUOTES, 'UTF-8'); ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="spl-reward-calc-bar">
          <div class="spl-reward-calc-item is-positive">
            <span class="spl-reward-calc-label"><i class="bi bi-plus-circle me-1"></i>Earned</span>
            <span class="spl-reward-calc-value">+<?php echo number_format((float) $reward_totals['positive'], 0); ?></span>
          </div>
          <div class="spl-reward-calc-item is-negative">
            <span class="spl-reward-calc-label"><i class="bi bi-dash-circle me-1"></i>Deducted</span>
            <span class="spl-reward-calc-value">-<?php echo number_format((float) $reward_totals['negative'], 0); ?></span>
          </div>
          <div class="spl-reward-calc-item is-net">
            <span class="spl-reward-calc-label"><i class="bi bi-calculator me-1"></i>Net</span>
            <span class="spl-reward-calc-value"><?php echo ((float) $reward_totals['net'] >= 0 ? '+' : '') . number_format((float) $reward_totals['net'], 0); ?></span>
          </div>
        </div>

        <div class="spl-reward-calc-meta">
          <?php if (!empty($reward_totals['pending_count'])): ?>
          <span class="badge rounded-pill text-bg-warning"><?php echo (int) $reward_totals['pending_count']; ?> pending</span>
          <?php endif; ?>
          <?php if (!empty($reward_totals['approved_count'])): ?>
          <span class="badge rounded-pill text-bg-success"><?php echo (int) $reward_totals['approved_count']; ?> approved</span>
          <?php endif; ?>
          <?php if (!empty($reward_totals['rejected_count'])): ?>
          <span class="badge rounded-pill text-bg-secondary"><?php echo (int) $reward_totals['rejected_count']; ?> rejected</span>
          <?php endif; ?>
        </div>

        <?php if (empty($recent)): ?>
        <div class="spl-empty-state">
          <i class="bi bi-inbox"></i>
          <p>No activity for <?php echo esc_view(strtolower($reward_bounds['label']), ENT_QUOTES, 'UTF-8'); ?></p>
          <?php if (!empty($can_submit)): ?>
          <a class="btn btn-sm btn-primary" href="#" onclick="document.querySelector('[data-bs-target=\'#spl-tab-activity\']').click(); return false;">Submit activity</a>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="spl-activity-grid">
          <?php foreach ($recent as $t):
            $statusMeta = spl_activity_status_meta($t->status);
            $points = (float) $t->points;
            $notePreview = spl_activity_note_preview($t->reference_label, 70);
            $isPending = ((string) $t->status === 'pending');
            $isRejected = ((string) $t->status === 'rejected');
          ?>
          <article class="spl-activity-card<?php echo $isPending ? ' is-pending' : ''; ?><?php echo $isRejected ? ' is-rejected' : ''; ?>">
            <div class="spl-activity-card-top">
              <span class="spl-activity-card-icon"><i class="bi <?php echo esc_view(spl_activity_icon_class($t), ENT_QUOTES, 'UTF-8'); ?>"></i></span>
              <span class="spl-activity-card-points <?php echo ($points >= 0) ? 'is-positive' : 'is-negative'; ?><?php echo ($isPending || $isRejected) ? ' is-muted' : ''; ?>">
                <?php echo ($points >= 0 ? '+' : '') . number_format($points, 0); ?>
              </span>
            </div>
            <h3 class="spl-activity-card-title"><?php echo esc_view(spl_activity_title($t), ENT_QUOTES, 'UTF-8'); ?></h3>
            <div class="spl-activity-card-meta">
              <?php if (!empty($t->category_name)): ?>
              <span><?php echo esc_view($t->category_name, ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
              <span><?php echo esc_view(spl_activity_source_label($t), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="spl-activity-card-foot">
              <time class="spl-activity-card-date"><?php echo esc_view(spl_format_activity_datetime($t->created_at), ENT_QUOTES, 'UTF-8'); ?></time>
              <span class="badge rounded-pill text-bg-<?php echo esc_view($statusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php if ($notePreview !== ''): ?>
            <p class="spl-activity-card-note"><?php echo esc_view($notePreview, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($can_submit)): ?>
    <div class="tab-pane fade <?php echo $active_tab === 'activity' ? 'show active' : ''; ?>" id="spl-tab-activity">
      <div class="spl-panel-card spl-submit-card">
        <div class="spl-panel-head">
          <div>
            <h2 class="spl-panel-title">Submit activity</h2>
            <p class="spl-panel-sub">Manual activities need admin approval</p>
          </div>
        </div>
        <div class="spl-panel-body">
          <form method="post" action="<?php echo site_url('spl/submit-activity'); ?>" enctype="multipart/form-data" id="splSubmitForm" class="spl-activity-form">
            <?php echo form_hidden($csrf_name, $csrf_hash); ?>

            <div class="row g-2 spl-activity-form-grid">
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="splCategorySelect">Category</label>
                <select class="form-select" id="splCategorySelect" name="category_id">
                  <option value="">All categories</option>
                  <?php foreach ($categories as $c): ?>
                  <option value="<?php echo (int) $c->id; ?>"><?php echo esc_view($c->name, ENT_QUOTES, 'UTF-8'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold" for="splActivitySelect">Activity</label>
                <select class="form-select" name="rule_code" id="splActivitySelect" required>
                  <option value="">— Select activity —</option>
                </select>
                <div class="form-text" id="splActivityPointsHint"></div>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold" for="splNoteInput">Note</label>
                <textarea name="reference_label" id="splNoteInput" class="form-control" rows="6" placeholder="Brief description for approver"></textarea>
              </div>
              <div class="col-12">
                <label class="form-label fw-semibold" for="splAttachmentInput">Attachment</label>
                <input type="file" name="attachment" id="splAttachmentInput" class="form-control" accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.doc,.docx,.xls,.xlsx">
                <div class="form-text">Optional evidence (PDF, image, or document).</div>
              </div>
            </div>

            <div class="alert alert-light border small mt-2 mb-0 py-2">
              Check-in / check-out points are automatic. Other activities need admin approval.
            </div>

            <div class="d-flex justify-content-end mt-2 pt-2 border-top">
              <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-send me-1"></i>Submit for approval
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

        <?php if (!empty($can_approve)): ?>
    <div class="tab-pane fade <?php echo $active_tab === 'approvals' ? 'show active' : ''; ?>" id="spl-tab-approvals">
      <?php
      $approval_view = isset($approval_view) ? $approval_view : 'pending';
      $approval_counts = isset($approval_counts) ? $approval_counts : array('pending' => 0, 'approved' => 0, 'rejected' => 0);
      $approved_approvals = isset($approved_approvals) ? $approved_approvals : array();
      $rejected_approvals = isset($rejected_approvals) ? $rejected_approvals : array();
      $approval_rows = array();
      if ($approval_view === 'approved') {
          $approval_rows = $approved_approvals;
      } elseif ($approval_view === 'rejected') {
          $approval_rows = $rejected_approvals;
      } else {
          $approval_rows = $pending_approvals;
      }
      ?>
      <div class="spl-approval-view-tabs mb-2">
        <a class="spl-approval-view-tab<?php echo $approval_view === 'pending' ? ' is-active' : ''; ?>" href="<?php echo site_url('spl?tab=approvals&approval_view=pending'); ?>">
          Pending<?php if (!empty($approval_counts['pending'])): ?> <span class="badge rounded-pill text-bg-danger"><?php echo (int) $approval_counts['pending']; ?></span><?php endif; ?>
        </a>
        <a class="spl-approval-view-tab<?php echo $approval_view === 'approved' ? ' is-active' : ''; ?>" href="<?php echo site_url('spl?tab=approvals&approval_view=approved'); ?>">
          Approved<?php if (!empty($approval_counts['approved'])): ?> <span class="badge rounded-pill text-bg-success"><?php echo (int) $approval_counts['approved']; ?></span><?php endif; ?>
        </a>
        <a class="spl-approval-view-tab<?php echo $approval_view === 'rejected' ? ' is-active' : ''; ?>" href="<?php echo site_url('spl?tab=approvals&approval_view=rejected'); ?>">
          Rejected<?php if (!empty($approval_counts['rejected'])): ?> <span class="badge rounded-pill text-bg-secondary"><?php echo (int) $approval_counts['rejected']; ?></span><?php endif; ?>
        </a>
      </div>
      <div class="spl-panel-card">
        <div class="spl-panel-head">
          <div>
            <h2 class="spl-panel-title">
              <?php if ($approval_view === 'approved'): ?>Approved employee activities
              <?php elseif ($approval_view === 'rejected'): ?>Rejected submissions
              <?php else: ?>Pending approvals<?php endif; ?>
            </h2>
            <p class="spl-panel-sub">
              <?php if ($approval_view === 'approved'): ?>Points were added to each employee after approval
              <?php elseif ($approval_view === 'rejected'): ?>These activities were not awarded points
              <?php else: ?>Review and approve to add points<?php endif; ?>
            </p>
          </div>
        </div>
        <?php if (empty($approval_rows)): ?>
        <div class="spl-empty-state">
          <i class="bi bi-<?php echo $approval_view === 'approved' ? 'check2-circle' : ($approval_view === 'rejected' ? 'x-circle' : 'inbox'); ?>"></i>
          <p>
            <?php if ($approval_view === 'approved'): ?>No approved activities yet
            <?php elseif ($approval_view === 'rejected'): ?>No rejected activities
            <?php else: ?>No pending submissions<?php endif; ?>
          </p>
        </div>
        <?php else: ?>
        <div class="spl-approval-grid">
          <?php foreach ($approval_rows as $row): ?>
          <article class="spl-approval-card<?php echo $approval_view === 'approved' ? ' is-approved' : ($approval_view === 'rejected' ? ' is-rejected' : ''); ?>">
            <div class="spl-approval-card-head">
              <div>
                <div class="spl-approval-card-name"><?php echo esc_view($row->recipient_name ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="spl-approval-card-date">
                  Submitted <?php echo esc_view(spl_format_activity_datetime($row->submitted_at), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php if ($approval_view !== 'pending' && !empty($row->decided_at)): ?>
                <div class="spl-approval-card-decided">
                  <?php echo $approval_view === 'approved' ? 'Approved' : 'Rejected'; ?>
                  <?php if (!empty($row->approver_name)): ?> by <?php echo esc_view($row->approver_name, ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                  · <?php echo esc_view(spl_format_activity_datetime($row->decided_at), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <?php endif; ?>
              </div>
              <div class="spl-approval-card-points <?php echo ((float) $row->requested_points >= 0) ? 'is-positive' : 'is-negative'; ?>">
                <?php echo ((float) $row->requested_points >= 0 ? '+' : '') . number_format((float) $row->requested_points, 0); ?>
              </div>
            </div>
            <div class="spl-approval-card-meta">
              <span><?php echo esc_view($row->category_name ?: '—', ENT_QUOTES, 'UTF-8'); ?></span>
              <span><?php echo esc_view($row->rule_name ?: $row->rule_code, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php if (!empty($row->reference_label)): ?>
            <div class="spl-approval-card-note"><?php echo spl_render_note_html($row->reference_label); ?></div>
            <?php endif; ?>
            <?php if (!empty($row->evidence_file)): ?>
            <div class="spl-approval-card-file">
              <a href="<?php echo esc_view(base_url($row->evidence_file), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><i class="bi bi-paperclip me-1"></i><?php echo esc_view($row->evidence_name ?: 'Attachment', ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
            <?php endif; ?>
            <?php if ($approval_view === 'pending'): ?>
            <div class="spl-approval-card-actions">
              <form method="post" action="<?php echo site_url('spl/approve-activity/' . (int) $row->id); ?>" class="d-inline">
                <?php echo form_hidden($csrf_name, $csrf_hash); ?>
                <button type="submit" class="btn btn-sm btn-success">Approve</button>
              </form>
              <form method="post" action="<?php echo site_url('spl/reject-activity/' . (int) $row->id); ?>" class="d-inline" onsubmit="return confirm('Reject this activity?');">
                <?php echo form_hidden($csrf_name, $csrf_hash); ?>
                <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
              </form>
            </div>
            <?php else: ?>
            <div class="spl-approval-card-status">
              <span class="badge rounded-pill text-bg-<?php echo $approval_view === 'approved' ? 'success' : 'secondary'; ?>">
                <?php echo $approval_view === 'approved' ? 'Approved' : 'Rejected'; ?>
              </span>
            </div>
            <?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($can_levels)): ?>
    <div class="tab-pane fade <?php echo $active_tab === 'levels' ? 'show active' : ''; ?>" id="spl-tab-levels">
      <div class="spl-panel-card">
        <div class="spl-panel-head spl-levels-panel-head">
          <div>
            <h2 class="spl-panel-title">Leaderboard titles</h2>
            <p class="spl-panel-sub mb-0">Earn lifetime points to unlock each title instead of only showing raw points.</p>
          </div>
          <?php if (!empty($can_rules)): ?>
          <div class="spl-level-save-msg alert alert-success py-1 px-2 mb-0 d-none" id="splLevelSaveMsg" role="status"></div>
          <?php endif; ?>
        </div>
        <div class="table-responsive spl-levels-wrap">
          <table class="table table-sm table-hover align-middle mb-0 spl-levels-table" id="splLevelsTable">
            <thead>
              <tr>
                <th>Level</th>
                <th>Points</th>
                <?php if (!empty($can_rules)): ?>
                <th class="text-center">Active</th>
                <th class="text-end">Actions</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($levels as $lv): ?>
              <?php
                $lv_active = (int) $lv->is_active === 1;
                $points_range = spl_format_level_points_range($lv);
                $lv_emoji = spl_level_emoji($lv->code);
                $lv_color = !empty($lv->badge_color) ? (string) $lv->badge_color : '#6c757d';
              ?>
              <tr class="spl-level-row" data-level-id="<?php echo (int) $lv->id; ?>" data-level-code="<?php echo esc_view($lv->code, ENT_QUOTES, 'UTF-8'); ?>">
                <td>
                  <span class="spl-level-display spl-level-display-name">
                    <span class="spl-level-emoji" aria-hidden="true"><?php echo $lv_emoji; ?></span>
                    <span class="spl-level-title" style="color:<?php echo esc_view($lv_color, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($lv->name, ENT_QUOTES, 'UTF-8'); ?></span>
                  </span>
                  <input class="form-control form-control-sm spl-level-field spl-level-name d-none" value="<?php echo esc_view($lv->name, ENT_QUOTES, 'UTF-8'); ?>">
                </td>
                <td>
                  <span class="spl-level-display spl-level-display-points"><?php echo esc_view($points_range, ENT_QUOTES, 'UTF-8'); ?></span>
                  <div class="spl-level-field spl-level-points-edit d-none">
                    <div class="input-group input-group-sm">
                      <input type="number" step="1" class="form-control spl-level-min" value="<?php echo (float) $lv->min_lifetime_points; ?>" placeholder="Min">
                      <span class="input-group-text">–</span>
                      <input type="number" step="1" class="form-control spl-level-max" value="<?php echo $lv->max_lifetime_points !== null ? (float) $lv->max_lifetime_points : ''; ?>" placeholder="Max+">
                    </div>
                  </div>
                </td>
                <?php if (!empty($can_rules)): ?>
                <td class="text-center">
                  <span class="spl-level-display spl-level-display-active">
                    <?php if ($lv_active): ?>
                    <span class="spl-rule-status spl-rule-status--on">Active</span>
                    <?php else: ?>
                    <span class="spl-rule-status spl-rule-status--off">Inactive</span>
                    <?php endif; ?>
                  </span>
                  <div class="spl-level-field form-check form-switch d-none justify-content-center mb-0">
                    <input type="checkbox" class="form-check-input spl-level-active" role="switch" <?php echo $lv_active ? 'checked' : ''; ?>>
                  </div>
                </td>
                <td class="text-end text-nowrap spl-levels-actions-cell">
                  <button type="button" class="btn btn-sm btn-outline-primary spl-toggle-level">Edit</button>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($can_rules)): ?>
    <div class="tab-pane fade <?php echo $active_tab === 'rules' ? 'show active' : ''; ?>" id="spl-tab-rules">
      <div class="spl-panel-card">
        <div class="spl-panel-head spl-rules-panel-head">
          <div>
            <h2 class="spl-panel-title">Reward rules</h2>
            <p class="spl-panel-sub mb-0">Click <strong>Edit</strong> on a row to change it. Click column headers to sort.</p>
          </div>
          <div class="spl-rules-toolbar">
            <div class="spl-rule-save-msg alert alert-success py-1 px-2 mb-0 d-none" id="splRuleSaveMsg" role="status"></div>
            <div class="btn-group btn-group-sm" role="group" aria-label="CSV actions">
              <a class="btn btn-outline-secondary" href="<?php echo site_url('spl/rules-sample-csv'); ?>" title="Download sample CSV"><i class="bi bi-download me-1"></i>Sample</a>
              <a class="btn btn-outline-secondary" href="<?php echo site_url('spl/rules-export'); ?>" title="Export all rules"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Export</a>
              <button type="button" class="btn btn-outline-primary" id="splRulesImportBtn" title="Import rules from CSV"><i class="bi bi-upload me-1"></i>Import</button>
            </div>
            <button type="button" class="btn btn-sm btn-primary" id="splAddRuleRow"><i class="bi bi-plus-lg me-1"></i>Add rule</button>
          </div>
        </div>
        <?php if ($active_tab === 'rules'): ?>
          <?php $this->load->view('partials/import_errors'); ?>
        <?php endif; ?>
        <form id="splRulesImportForm" method="post" action="<?php echo site_url('spl/rules-import'); ?>" enctype="multipart/form-data" class="d-none">
          <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="file" name="file" id="splRulesImportFile" accept=".csv,text/csv">
        </form>
        <div class="table-responsive spl-rules-wrap">
          <table class="table table-sm table-hover align-middle mb-0 spl-rules-table" id="splRulesTable" data-dt-manual data-order-col="1" data-order-dir="asc" data-order-disable-cols="6">
            <thead>
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Category</th>
                <th>Trigger</th>
                <th class="text-end">Points</th>
                <th class="text-center">Active</th>
                <th class="text-end no-sort">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rules as $r): ?>
              <?php
                $cat_label = isset($r->category_name) ? (string) $r->category_name : '';
                $is_active_order = (int) $r->is_active === 1 ? 1 : 0;
                $points_val = (float) $r->points;
              ?>
              <tr class="spl-rule-row" data-rule-id="<?php echo (int) $r->id; ?>">
                <td data-order="<?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?>">
                  <span class="spl-rule-display spl-rule-display-code" title="<?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?></span>
                  <input class="form-control form-control-sm spl-rule-field spl-rule-code d-none" value="<?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?>">
                </td>
                <td data-order="<?php echo esc_view($r->name, ENT_QUOTES, 'UTF-8'); ?>">
                  <span class="spl-rule-display spl-rule-display-name" title="<?php echo esc_view($r->name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($r->name, ENT_QUOTES, 'UTF-8'); ?></span>
                  <input class="form-control form-control-sm spl-rule-field spl-rule-name d-none" value="<?php echo esc_view($r->name, ENT_QUOTES, 'UTF-8'); ?>">
                </td>
                <td data-order="<?php echo esc_view($cat_label, ENT_QUOTES, 'UTF-8'); ?>">
                  <span class="spl-rule-display spl-rule-display-category"><?php echo $cat_label !== '' ? esc_view($cat_label, ENT_QUOTES, 'UTF-8') : '—'; ?></span>
                  <select class="form-select form-select-sm spl-rule-field spl-rule-category d-none">
                    <option value="">—</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?php echo (int) $c->id; ?>" <?php echo (int) $r->category_id === (int) $c->id ? 'selected' : ''; ?>><?php echo esc_view($c->name, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td data-order="<?php echo esc_view($r->trigger_event, ENT_QUOTES, 'UTF-8'); ?>">
                  <span class="spl-rule-display spl-rule-display-trigger"><code><?php echo esc_view($r->trigger_event, ENT_QUOTES, 'UTF-8'); ?></code></span>
                  <input class="form-control form-control-sm spl-rule-field spl-rule-trigger d-none" value="<?php echo esc_view($r->trigger_event, ENT_QUOTES, 'UTF-8'); ?>">
                </td>
                <td class="text-end" data-order="<?php echo $points_val; ?>">
                  <span class="spl-rule-display spl-rule-display-points"><?php echo $points_val; ?></span>
                  <input type="number" step="1" class="form-control form-control-sm spl-rule-field spl-rule-points d-none text-end" value="<?php echo $points_val; ?>">
                </td>
                <td class="text-center" data-order="<?php echo $is_active_order; ?>">
                  <span class="spl-rule-display spl-rule-display-active">
                    <?php if ($is_active_order === 1): ?>
                    <span class="spl-rule-status spl-rule-status--on">Active</span>
                    <?php else: ?>
                    <span class="spl-rule-status spl-rule-status--off">Inactive</span>
                    <?php endif; ?>
                  </span>
                  <div class="spl-rule-field form-check form-switch d-none justify-content-center mb-0">
                    <input type="checkbox" class="form-check-input spl-rule-active" role="switch" <?php echo $is_active_order === 1 ? 'checked' : ''; ?>>
                  </div>
                </td>
                <td class="text-end text-nowrap spl-rules-actions-cell no-sort">
                  <button type="button" class="btn btn-sm btn-outline-primary spl-toggle-rule">Edit</button>
                  <button type="button" class="btn btn-sm btn-outline-danger spl-delete-rule">Delete</button>
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
</div>

<script>
window.SPL_CONFIG = {
  rulesUrl: <?php echo json_encode(site_url('spl/rules-by-category')); ?>,
  saveRuleUrl: <?php echo json_encode(site_url('spl/save-rule')); ?>,
  deleteRuleUrlBase: <?php echo json_encode(site_url('spl/delete-rule/')); ?>,
  importRulesUrl: <?php echo json_encode(site_url('spl/rules-import')); ?>,
  saveLevelUrl: <?php echo json_encode(site_url('spl/save-level')); ?>,
  canManageLevels: <?php echo !empty($can_rules) ? 'true' : 'false'; ?>,
  csrfName: <?php echo json_encode($csrf_name); ?>,
  csrfHash: <?php echo json_encode($csrf_hash); ?>,
  categories: <?php echo json_encode(array_map(function ($c) {
      return array('id' => (int) $c->id, 'name' => (string) $c->name);
  }, $categories)); ?>
};
</script>
<?php if (!empty($can_submit)): ?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
  function initSplNoteEditor() {
    if (typeof tinymce === 'undefined') {
      setTimeout(initSplNoteEditor, 50);
      return;
    }
    var isMobile = window.matchMedia('(max-width: 767.98px)').matches;
    tinymce.init({
      selector: '#splNoteInput',
      menubar: false,
      statusbar: !isMobile,
      plugins: 'lists link autolink code wordcount',
      toolbar: isMobile
        ? 'bold italic underline | bullist numlist | link | removeformat'
        : 'undo redo | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | removeformat | code',
      toolbar_mode: isMobile ? 'scrolling' : 'wrap',
      branding: false,
      height: isMobile ? 140 : 180,
      width: '100%',
      resize: !isMobile,
      convert_urls: false,
      default_link_target: '_blank',
      placeholder: 'Brief description for approver',
      content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
      formats: {
        bold: { inline: 'strong' },
        italic: { inline: 'em' },
        underline: { inline: 'u' },
        strikethrough: { inline: 'del' }
      }
    });
  }
  initSplNoteEditor();
  var form = document.getElementById('splSubmitForm');
  if (form) {
    form.addEventListener('submit', function () {
      if (window.tinymce && tinymce.get('splNoteInput')) {
        tinymce.get('splNoteInput').save();
      }
    });
  }
})();
</script>
<?php endif; ?>
<script src="<?php echo base_url('assets/js/spl.js?v=' . (is_file(FCPATH . 'assets/js/spl.js') ? filemtime(FCPATH . 'assets/js/spl.js') : '1')); ?>"></script>

<?php $this->load->view('partials/footer'); ?>
