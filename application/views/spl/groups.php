<?php
$embed = !empty($embed);
if (!$embed) {
    $this->load->view('partials/header', array(
        'title' => 'SPL Groups',
        'extra_css' => array('assets/css/spl.css'),
    ));
}
$memberIdsByGroup = array();
foreach ($groups as $g) {
    $ids = array();
    if (!empty($g->members)) {
        foreach ($g->members as $m) {
            $ids[] = (int) $m->id;
        }
    }
    $memberIdsByGroup[(int) $g->id] = $ids;
}
$reward_period = isset($reward_period) ? $reward_period : 'week';
$reward_bounds = isset($reward_bounds) ? $reward_bounds : spl_reward_period_bounds($reward_period);
$use_period_points = !empty($use_period_points);
$points_period_label = $use_period_points ? $reward_bounds['label'] : 'Lifetime';
?>

<?php if (!$embed): ?>
<div class="container-fluid py-3 spl-page spl-groups-page">
  <div class="spl-groups-hero-bar">
    <div>
      <div class="spl-groups-eyebrow">SPL Performance</div>
      <h1 class="spl-groups-title mb-0">Team Groups</h1>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <?php if (!empty($can_manage)): ?>
      <button type="button" class="btn btn-light btn-sm spl-groups-btn-ghost" id="splBoardEditBtn">
        <i class="bi bi-pencil-square me-1"></i>Edit board
      </button>
      <button type="submit" form="splBoardForm" class="btn btn-warning btn-sm d-none" id="splBoardSaveBtn">
        <i class="bi bi-check2-circle me-1"></i>Save all
      </button>
      <button type="button" class="btn btn-outline-light btn-sm d-none" id="splBoardCancelBtn">Cancel</button>
      <?php endif; ?>
      <a class="btn btn-outline-light btn-sm" href="<?php echo spl_dashboard_url('my-reward'); ?>"><i class="bi bi-trophy me-1"></i>My Reward</a>
    </div>
  </div>
<?php else: ?>
<div class="spl-embed-pane spl-page spl-groups-page spl-groups-embed">
<?php endif; ?>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 mt-3 mb-0"><?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 mt-3 mb-0"><?php echo esc_view((string) $this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <?php if (empty($groups)): ?>
      <div class="alert alert-light border text-muted <?php echo $embed ? 'mt-2' : 'mt-3'; ?> mb-0">No SPL groups yet.</div>
    <?php else: ?>
    <div class="spl-board-shell<?php echo $embed ? '' : ' mt-3'; ?>">
      <?php if (!empty($can_manage)): ?>
      <form method="post" action="<?php echo site_url('spl/groups/save-board'); ?>" enctype="multipart/form-data" id="splBoardForm">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <?php echo form_hidden('reward_period', $reward_period); ?>
      <?php endif; ?>

      <div class="spl-board" id="splBoard">
        <?php foreach ($groups as $g): ?>
          <?php
          $gid = (int) $g->id;
          $poster_info = spl_poster_info(isset($g->poster_path) ? $g->poster_path : '');
          $poster = $poster_info['url'];
          $selectedMemberIds = isset($memberIdsByGroup[$gid]) ? $memberIdsByGroup[$gid] : array();
          $members = !empty($g->members) ? $g->members : array();
          $is_my_group = !empty($my_group) && (int) $my_group->id === $gid;
          ?>
          <div class="spl-board-col<?php echo $is_my_group ? ' is-mine' : ''; ?>" data-group-id="<?php echo $gid; ?>">
            <div class="spl-group-card-poster spl-board-poster--slot-<?php echo ((int) $g->sort_order % 5) + 1; ?>" data-poster-col="<?php echo $gid; ?>">
              <?php if ($poster !== ''): ?>
                <img src="<?php echo esc_view($poster, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="spl-group-card-poster-img"<?php if ($poster_info['width'] > 0): ?> width="<?php echo (int) $poster_info['width']; ?>" height="<?php echo (int) $poster_info['height']; ?>"<?php endif; ?>>
                <?php if ($poster_info['dimensions'] !== ''): ?>
                <span class="spl-poster-dimensions" data-spl-poster-dims><?php echo esc_view($poster_info['dimensions'], ENT_QUOTES, 'UTF-8'); ?></span>
                <?php else: ?>
                <span class="spl-poster-dimensions d-none" data-spl-poster-dims></span>
                <?php endif; ?>
              <?php else: ?>
                <div class="spl-group-card-poster-fallback">
                  <i class="bi bi-image"></i>
                </div>
              <?php endif; ?>
              <div class="spl-group-card-poster-shade"></div>
              <div class="spl-group-card-poster-title">
                <?php if ($is_my_group): ?><span class="spl-group-mine-tag">My group</span><?php endif; ?>
                <?php if (!empty($can_manage)): ?>
                <a href="<?php echo site_url('spl/groups/' . $gid); ?>" class="spl-board-head-view text-decoration-none" title="Edit <?php echo esc_view($g->name, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($g->name, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php else: ?>
                <span class="spl-board-head-view"><?php echo esc_view($g->name, ENT_QUOTES, 'UTF-8'); ?></span>
                <?php endif; ?>
              </div>
              <?php if (!empty($can_manage)): ?>
              <label class="spl-board-poster-edit">
                <i class="bi bi-camera"></i>
                <span><?php echo $poster !== '' ? 'Change poster' : 'Upload poster'; ?></span>
                <small class="spl-board-poster-hint">Recommended 800×1000 px (4:5)</small>
                <input type="file" name="group_poster[<?php echo $gid; ?>]" accept="image/*" class="d-none spl-board-poster-input" form="splBoardForm">
              </label>
              <?php endif; ?>
            </div>

            <div class="spl-board-avg spl-board-avg-view">
              <div class="spl-board-avg-main">
                <span class="spl-board-avg-label"><?php echo $use_period_points ? 'Team score' : 'Team score'; ?><?php echo ' (' . esc_view($points_period_label, ENT_QUOTES, 'UTF-8') . ')'; ?></span>
                <span class="spl-board-avg-value"><?php echo number_format($use_period_points ? (float) $g->total_period_net : (float) $g->total_lifetime_points, 0); ?></span>
              </div>
              <div class="spl-board-avg-sub">
                <?php echo (int) $g->member_count; ?> members<?php if ($use_period_points): ?> · avg <?php echo number_format((float) $g->avg_period_points, 0); ?><?php else: ?> · avg <?php echo number_format((float) $g->avg_lifetime_points, 0); ?> lifetime<?php endif; ?>
              </div>
            </div>

            <?php if ($use_period_points): ?>
            <div class="spl-group-period-calc">
              <span class="spl-group-period-calc-item is-positive">+<?php echo number_format((float) $g->total_period_positive, 0); ?></span>
              <span class="spl-group-period-calc-item is-negative">-<?php echo number_format((float) $g->total_period_negative, 0); ?></span>
              <span class="spl-group-period-calc-item is-net"><?php echo ((float) $g->total_period_net >= 0 ? '+' : '') . number_format((float) $g->total_period_net, 0); ?></span>
            </div>
            <?php endif; ?>

            <div class="spl-board-body spl-board-body-view">
              <?php if (empty($members)): ?>
                <div class="spl-board-cell"><span class="spl-board-member-name text-muted">No members</span></div>
              <?php else: foreach ($members as $member): ?>
                <a href="<?php echo esc_view(spl_member_url($member->id, array('reward_period' => $reward_period, 'from' => 'groups')), ENT_QUOTES, 'UTF-8'); ?>" class="spl-board-member-card-link" title="View all activities for <?php echo esc_view($member->name, ENT_QUOTES, 'UTF-8'); ?>">
                  <div class="spl-board-member-card<?php echo (int) $member->id === (int) $current_user_id ? ' is-you' : ''; ?>">
                    <div class="spl-board-member-card-top">
                      <span class="spl-board-member-name"><?php echo esc_view($member->name, ENT_QUOTES, 'UTF-8'); ?></span>
                      <span class="spl-board-member-points"><?php echo number_format((float) $member->display_points, 0); ?></span>
                    </div>
                    <div class="spl-board-member-card-meta">
                      <span class="spl-board-level-pill" style="--spl-level-color: <?php echo esc_view($member->level_color, ENT_QUOTES, 'UTF-8'); ?>;">
                        <?php echo esc_view($member->level_name, ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                      <span class="spl-board-member-month"><?php if ($use_period_points): ?>+<?php echo number_format((float) $member->period_positive, 0); ?> / -<?php echo number_format((float) $member->period_negative, 0); ?><?php else: ?><?php echo number_format((float) $member->month_points, 0); ?> mo<?php endif; ?></span>
                    </div>
                    <span class="spl-board-member-view-hint"><i class="bi bi-chevron-right"></i></span>
                  </div>
                </a>
              <?php endforeach; endif; ?>
            </div>

            <?php if (!empty($can_manage)): ?>
            <div class="spl-board-members-edit">
              <div class="spl-board-name-edit">
                <label class="form-label small fw-semibold mb-1" for="splGroupName<?php echo $gid; ?>">Group name</label>
                <input type="text" class="form-control form-control-sm spl-board-name-field" id="splGroupName<?php echo $gid; ?>" name="group_name[<?php echo $gid; ?>]" value="<?php echo esc_view($g->name, ENT_QUOTES, 'UTF-8'); ?>" required form="splBoardForm">
                <input type="hidden" name="group_code[<?php echo $gid; ?>]" value="<?php echo esc_view($g->code, ENT_QUOTES, 'UTF-8'); ?>" form="splBoardForm">
                <input type="hidden" name="group_sort[<?php echo $gid; ?>]" value="<?php echo (int) $g->sort_order; ?>" form="splBoardForm">
              </div>
              <div class="spl-board-members-edit-title">Assign members</div>
              <div class="spl-board-member-picks">
                <?php foreach ($users as $u): ?>
                <label class="spl-board-member-pick">
                  <input type="checkbox" name="member_ids[<?php echo $gid; ?>][]" value="<?php echo (int) $u->id; ?>" form="splBoardForm" <?php echo in_array((int) $u->id, $selectedMemberIds, true) ? 'checked' : ''; ?>>
                  <span><?php echo esc_view($u->name, ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($can_manage)): ?>
      </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>window.SPL_BOARD_CONFIG = { canManage: <?php echo !empty($can_manage) ? 'true' : 'false'; ?> };</script>
<?php if (!$embed): ?>
<script src="<?php echo base_url('assets/js/spl.js?v=' . (is_file(FCPATH . 'assets/js/spl.js') ? filemtime(FCPATH . 'assets/js/spl.js') : '1')); ?>"></script>
<script>if (window.initSplBoard) { window.initSplBoard(document.querySelector('.spl-groups-page')); }</script>
<?php endif; ?>

<?php if (!$embed): ?>
<?php $this->load->view('partials/footer'); ?>
<?php endif; ?>
