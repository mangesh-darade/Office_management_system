<?php
$embed = !empty($embed);
if (!$embed) {
    $this->load->view('partials/header', array(
        'title' => 'SPL — My Reward',
        'extra_css' => array('assets/css/spl.css'),
    ));
}
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

<?php if (!$embed): ?>
<div class="container-fluid py-2 spl-page spl-index-page">
  <div class="spl-index-hero">
    <div class="spl-index-hero-main">
      <div class="spl-index-eyebrow">SPL Rewards</div>
      <h1 class="spl-index-title mb-0"><i class="bi bi-trophy-fill me-1"></i>My Reward</h1>
    </div>
    <div class="spl-index-hero-actions">
      <?php if (!empty($can_groups)): ?>
      <a class="btn btn-outline-light btn-sm" href="<?php echo spl_dashboard_url('groups'); ?>"><i class="bi bi-collection me-1"></i>Groups</a>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
<div class="spl-embed-pane spl-page spl-index-page">
<?php endif; ?>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 mt-3 mb-0"><?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 mt-3 mb-0"><?php echo esc_view((string) $this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <?php if (!$embed): ?>
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
  <?php endif; ?>

  <?php if (!$embed): ?><div class="tab-content mt-2"><?php endif; ?>
    <?php if (!empty($can_my_reward) && (!$embed || $active_tab === 'my-reward')): ?>
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
              <a class="spl-reward-period-tab<?php echo $reward_period === $periodKey ? ' is-active' : ''; ?>" href="<?php echo spl_dashboard_url('my-reward', array('reward_period' => $periodKey)); ?>">
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

        <?php
        $activity_table_ctx = spl_prepare_activity_table_context(!empty($recent) ? $recent : array());
        $activity_payloads = $activity_table_ctx['payloads'];
        $recent_activities = $activity_table_ctx['activities'];
        ?>
        <div class="spl-panel-body spl-panel-body--table pt-0">
          <?php $this->load->view('spl/_activity_table', array(
              'activities' => $recent_activities,
              'table_id' => 'splMyRewardActivityTable',
              'page_length' => 25,
              'empty_message' => 'No activity for ' . strtolower($reward_bounds['label']),
          )); ?>
        </div>
      </div>
      <script>
      window.SPL_ACTIVITY_PAYLOADS = Object.assign(window.SPL_ACTIVITY_PAYLOADS || {}, <?php echo json_encode($activity_payloads, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
      if (window.initSplActivityTable) { window.initSplActivityTable(document); }
      </script>
    </div>
    <?php endif; ?>

    <?php if (!empty($can_submit) && (!$embed || $active_tab === 'activity')): ?>
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
              <div class="col-12 spl-note-field-wrap">
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

    <?php if (!empty($can_approve) && (!$embed || $active_tab === 'approvals')): ?>
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
        <a class="spl-approval-view-tab<?php echo $approval_view === 'pending' ? ' is-active' : ''; ?>" href="<?php echo spl_dashboard_url('approvals', array('approval_view' => 'pending')); ?>">
          Pending<?php if (!empty($approval_counts['pending'])): ?> <span class="badge rounded-pill text-bg-danger"><?php echo (int) $approval_counts['pending']; ?></span><?php endif; ?>
        </a>
        <a class="spl-approval-view-tab<?php echo $approval_view === 'approved' ? ' is-active' : ''; ?>" href="<?php echo spl_dashboard_url('approvals', array('approval_view' => 'approved')); ?>">
          Approved<?php if (!empty($approval_counts['approved'])): ?> <span class="badge rounded-pill text-bg-success"><?php echo (int) $approval_counts['approved']; ?></span><?php endif; ?>
        </a>
        <a class="spl-approval-view-tab<?php echo $approval_view === 'rejected' ? ' is-active' : ''; ?>" href="<?php echo spl_dashboard_url('approvals', array('approval_view' => 'rejected')); ?>">
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
              <?php else: ?>Use row actions to approve or reject, or click a row to review details<?php endif; ?>
            </p>
          </div>
        </div>
        <?php
        $approval_table_ctx = spl_prepare_approval_table_context($approval_rows, $approval_view);
        $approval_payloads = $approval_table_ctx['payloads'];
        $approval_table_rows = $approval_table_ctx['rows'];
        $approval_empty_message = 'No submissions found.';
        if ($approval_view === 'approved') {
            $approval_empty_message = 'No approved activities yet';
        } elseif ($approval_view === 'rejected') {
            $approval_empty_message = 'No rejected activities';
        } else {
            $approval_empty_message = 'No pending submissions';
        }
        ?>
        <div class="spl-panel-body spl-panel-body--table pt-0">
          <?php $this->load->view('spl/_approval_table', array(
              'rows' => $approval_table_rows,
              'approval_view' => $approval_view,
              'table_id' => 'splApprovalTable',
              'page_length' => ($approval_view === 'pending') ? 50 : 25,
              'empty_message' => $approval_empty_message,
              'csrf_name' => $csrf_name,
              'csrf_hash' => $csrf_hash,
          )); ?>
        </div>
        <?php if (!empty($approval_table_rows)): ?>
        <script>
        window.SPL_APPROVAL_PAYLOADS = Object.assign(window.SPL_APPROVAL_PAYLOADS || {}, <?php echo json_encode($approval_payloads, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
        if (window.initSplApprovalsPanel) { window.initSplApprovalsPanel(document); }
        </script>
        <?php endif; ?>
      </div>
      <div class="modal fade" id="splApprovalDetailModal" tabindex="-1" aria-labelledby="splApprovalDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
          <div class="modal-content spl-approval-modal">
            <div class="modal-header">
              <h5 class="modal-title" id="splApprovalDetailModalLabel">Activity details</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="splApprovalDetailBody"></div>
            <div class="modal-footer flex-column align-items-stretch" id="splApprovalDetailFooter">
              <form id="splApprovalModalActionForm" method="post" class="spl-approval-modal-form d-none">
                <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" id="splApprovalModalCsrf" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                  <label class="form-label" for="splApprovalModalPoints">Points</label>
                  <input type="number" step="1" class="form-control" name="requested_points" id="splApprovalModalPoints" aria-label="Points to approve">
                </div>
                <label class="form-label" for="splApprovalModalComment">Comment</label>
                <textarea class="form-control" name="comment" id="splApprovalModalComment" rows="3" placeholder="Why are you approving or rejecting this activity?"></textarea>
                <div class="d-flex justify-content-end gap-2 mt-3">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-outline-danger" id="splApprovalModalRejectBtn">Reject</button>
                  <button type="button" class="btn btn-success" id="splApprovalModalApproveBtn">Approve</button>
                </div>
              </form>
              <div class="d-flex justify-content-end" id="splApprovalDetailFooterReadonly">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
    <?php endif; ?>

    <?php if (!empty($can_levels) && (!$embed || $active_tab === 'levels')): ?>
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

    <?php if (!empty($can_rules) && (!$embed || $active_tab === 'rules')): ?>
    <?php
      $rules_view = isset($rules_view) ? (string) $rules_view : 'rules';
      if (!in_array($rules_view, array('rules', 'categories'), true)) {
        $rules_view = 'rules';
      }
      if ($rules_view === 'categories' && empty($can_categories)) {
        $rules_view = 'rules';
      }
    ?>
    <div class="tab-pane fade <?php echo $active_tab === 'rules' ? 'show active' : ''; ?>" id="spl-tab-rules">
      <?php if (!empty($can_categories)): ?>
      <ul class="nav nav-pills gap-1 mb-2" id="splRulesInnerTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $rules_view === 'rules' ? 'active' : ''; ?>" type="button" data-bs-toggle="pill" data-bs-target="#spl-rules-sub-rules" role="tab">
            <i class="bi bi-list-check me-1"></i>Rules
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $rules_view === 'categories' ? 'active' : ''; ?>" type="button" data-bs-toggle="pill" data-bs-target="#spl-rules-sub-categories" role="tab">
            <i class="bi bi-tags me-1"></i>Categories
          </button>
        </li>
      </ul>
      <?php endif; ?>

      <div class="tab-content">
        <div class="tab-pane fade <?php echo $rules_view === 'rules' ? 'show active' : ''; ?>" id="spl-rules-sub-rules" role="tabpanel">
          <div class="spl-panel-card">
            <div class="spl-panel-head spl-rules-panel-head">
              <div>
                <h2 class="spl-panel-title">Reward rules</h2>
                <p class="spl-panel-sub mb-0">Click <strong>Edit</strong> on a row to change it. Use search or column headers to filter and sort.</p>
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
            <?php if ($active_tab === 'rules' && $rules_view === 'rules'): ?>
              <?php $this->load->view('partials/import_errors'); ?>
            <?php endif; ?>
            <form id="splRulesImportForm" method="post" action="<?php echo site_url('spl/rules-import'); ?>" enctype="multipart/form-data" class="d-none">
              <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
              <input type="file" name="file" id="splRulesImportFile" accept=".csv,text/csv">
            </form>
            <div class="spl-rules-filter-bar">
              <div class="spl-rules-search-wrap">
                <i class="bi bi-search spl-rules-search-icon" aria-hidden="true"></i>
                <input type="search" class="form-control form-control-sm spl-rules-search-input" id="splRulesSearch" placeholder="Search rules by category, name, trigger, code, points…" autocomplete="off">
                <button type="button" class="btn btn-link btn-sm spl-rules-search-clear d-none" id="splRulesSearchClear" title="Clear search" aria-label="Clear search">
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
            </div>
            <div class="table-responsive spl-rules-wrap">
              <table class="table table-sm table-hover align-middle mb-0 spl-rules-table" id="splRulesTable" data-dt-manual data-order-col="0" data-order-dir="asc" data-order-disable-cols="5">
                <thead>
                  <tr>
                    <th>Category</th>
                    <th>Name</th>
                    <th>Trigger</th>
                    <th class="text-end">Points</th>
                    <th class="text-center">Active</th>
                    <th class="text-end no-sort">Actions</th>
                    <th class="d-none" aria-hidden="true">Code</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rules as $r): ?>
                  <?php
                    $cat_label = isset($r->category_name) ? (string) $r->category_name : '';
                    $is_active_order = (int) $r->is_active === 1 ? 1 : 0;
                    $points_val = (float) $r->points;
                  ?>
                  <tr class="spl-rule-row" data-rule-id="<?php echo (int) $r->id; ?>" data-rule-code="<?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?>">
                    <td data-order="<?php echo esc_view($cat_label, ENT_QUOTES, 'UTF-8'); ?>">
                      <span class="spl-rule-display spl-rule-display-category"><?php echo $cat_label !== '' ? esc_view($cat_label, ENT_QUOTES, 'UTF-8') : '—'; ?></span>
                      <select class="form-select form-select-sm spl-rule-field spl-rule-category d-none">
                        <option value="">—</option>
                        <?php foreach ($categories as $c): ?>
                        <option value="<?php echo (int) $c->id; ?>" <?php echo (int) $r->category_id === (int) $c->id ? 'selected' : ''; ?>><?php echo esc_view($c->name, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td data-order="<?php echo esc_view($r->name, ENT_QUOTES, 'UTF-8'); ?>">
                      <span class="spl-rule-display spl-rule-display-name" title="<?php echo esc_view($r->name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($r->name, ENT_QUOTES, 'UTF-8'); ?></span>
                      <input class="form-control form-control-sm spl-rule-field spl-rule-name d-none" value="<?php echo esc_view($r->name, ENT_QUOTES, 'UTF-8'); ?>">
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
                    <td class="d-none spl-rule-code-col" aria-hidden="true" data-order="<?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?>">
                      <span class="spl-rule-display spl-rule-display-code" title="<?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?></span>
                      <input class="form-control form-control-sm spl-rule-field spl-rule-code d-none" value="<?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?>">
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <?php if (!empty($can_categories)): ?>
        <div class="tab-pane fade <?php echo $rules_view === 'categories' ? 'show active' : ''; ?>" id="spl-rules-sub-categories" role="tabpanel">
          <?php $this->load->view('spl/_categories_panel', array(
            'manage_categories' => isset($manage_categories) ? $manage_categories : array(),
            'nested' => true,
          )); ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  <?php if (!$embed): ?></div><?php endif; ?>
</div>

<script>
window.SPL_CONFIG = Object.assign(window.SPL_CONFIG || {}, {
  rulesUrl: <?php echo json_encode(site_url('spl/rules-by-category')); ?>,
  saveRuleUrl: <?php echo json_encode(site_url('spl/save-rule')); ?>,
  deleteRuleUrlBase: <?php echo json_encode(site_url('spl/delete-rule/')); ?>,
  importRulesUrl: <?php echo json_encode(site_url('spl/rules-import')); ?>,
  saveLevelUrl: <?php echo json_encode(site_url('spl/save-level')); ?>,
  canManageLevels: <?php echo !empty($can_rules) ? 'true' : 'false'; ?>,
  csrfName: <?php echo json_encode($csrf_name); ?>,
  csrfHash: <?php echo json_encode($csrf_hash); ?>,
  approveActivityUrlBase: <?php echo json_encode(site_url('spl/approve-activity/')); ?>,
  rejectActivityUrlBase: <?php echo json_encode(site_url('spl/reject-activity/')); ?>,
  updatePendingActivityUrlBase: <?php echo json_encode(site_url('spl/update-pending-activity/')); ?>,
  deletePendingActivityUrlBase: <?php echo json_encode(site_url('spl/delete-pending-activity/')); ?>,
  approvalEditRules: <?php echo json_encode(isset($approval_edit_rules) ? $approval_edit_rules : array(), JSON_UNESCAPED_UNICODE); ?>,
  categories: <?php echo json_encode(array_map(function ($c) {
      return array('id' => (int) $c->id, 'name' => (string) $c->name);
  }, $categories)); ?>
});
</script>
<?php if (!$embed): ?>
<script src="<?php echo base_url('assets/js/spl.js?v=' . (is_file(FCPATH . 'assets/js/spl.js') ? filemtime(FCPATH . 'assets/js/spl.js') : '1')); ?>"></script>
<?php endif; ?>

<?php if (!$embed): ?>
<?php $this->load->view('partials/footer'); ?>
<?php endif; ?>
