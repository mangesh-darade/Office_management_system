<?php
$levelName = $level ? (string) $level->name : ucfirst((string) $summary->current_level_code);
$levelColor = $level ? (string) $level->badge_color : '#6c757d';
$levelEmoji = spl_level_emoji($summary->current_level_code);
$teamName = $team_overview ? strtoupper((string) $team_overview->name) : '—';
$reward_period = isset($reward_period) ? $reward_period : 'week';
$reward_bounds = isset($reward_bounds) ? $reward_bounds : spl_reward_period_bounds($reward_period);
$prev_period_bounds = isset($prev_period_bounds) ? $prev_period_bounds : spl_reward_period_previous_bounds($reward_period);
$kpi_period = isset($kpi_period) ? $kpi_period : array('positive' => 0, 'negative' => 0, 'net' => 0, 'pending_points' => 0, 'pending_count' => 0);
$org_period_totals = isset($org_period_totals) ? $org_period_totals : $kpi_period;
$periodNet = (float) $kpi_period['net'];
$periodDeducted = (float) $kpi_period['negative'];
$periodEarned = (float) $kpi_period['positive'];
$periodPendingPoints = (float) $kpi_period['pending_points'];
$periodPendingCount = (int) $kpi_period['pending_count'];
$weekNet = isset($kpi_week['net']) ? (float) $kpi_week['net'] : 0;
$weekDeducted = isset($kpi_week['negative']) ? (float) $kpi_week['negative'] : 0;
$kpi_today = isset($kpi_today) ? $kpi_today : array('positive' => 0, 'negative' => 0, 'net' => 0, 'pending_points' => 0, 'pending_count' => 0);
$kpi_week = isset($kpi_week) ? $kpi_week : array('positive' => 0, 'negative' => 0, 'net' => 0, 'pending_points' => 0, 'pending_count' => 0);
$kpi_month = isset($kpi_month) ? $kpi_month : array('positive' => 0, 'negative' => 0, 'net' => 0, 'pending_points' => 0, 'pending_count' => 0);
$kpi_pending_activities = isset($kpi_pending_activities)
    ? (int) $kpi_pending_activities
    : (isset($pending_count) ? (int) $pending_count : $periodPendingCount);
$activity_period_totals = !empty($is_org_view) ? $org_period_totals : $kpi_period;
$activityPeriodNet = (float) $activity_period_totals['net'];
$activityPeriodPending = (float) $activity_period_totals['pending_points'];
$periodLabel = (string) $reward_bounds['label'];
$trendLabel = !empty($prev_period_bounds['label']) ? (string) $prev_period_bounds['label'] : '';
$periodTotal = $activityPeriodNet;
$teamTotal = count($team_standings) > 0 ? count($team_standings) : 5;
$current_user_id = isset($current_user_id) ? (int) $current_user_id : 0;
$is_user_scoped = !empty($is_user_scoped);
$is_org_view = !empty($is_org_view);
$can_my_reward = isset($can_my_reward) ? !empty($can_my_reward) : (function_exists('spl_can_my_reward') && spl_can_my_reward());
$can_groups = isset($can_groups) ? !empty($can_groups) : (function_exists('spl_can_view_groups') && spl_can_view_groups());
$can_approve = isset($can_approve) ? !empty($can_approve) : (function_exists('spl_can_approve') && spl_can_approve());
$can_submit = isset($can_submit) ? !empty($can_submit) : (function_exists('spl_can_submit') && spl_can_submit());
$can_levels = isset($can_levels) ? !empty($can_levels) : (function_exists('spl_can_view_levels') && spl_can_view_levels());
$show_pending_panel = true;
$show_pending_kpi = $show_pending_panel;
$recent_panel_title = $is_user_scoped ? 'My Recent Activities' : 'Recent League Activity';
$pending_panel_title = 'Pending Approvals';
$live_feed_title = $is_user_scoped ? 'My Activity Feed' : 'Live Activity Feed';
if ($can_my_reward) {
    $recent_view_url = spl_dashboard_url('my-reward');
    $live_feed_view_url = spl_dashboard_url('my-reward');
} elseif ($can_approve) {
    $recent_view_url = spl_dashboard_url('approvals');
    $live_feed_view_url = spl_dashboard_url('overview');
} elseif ($can_levels) {
    $recent_view_url = spl_dashboard_url('levels');
    $live_feed_view_url = spl_dashboard_url('overview');
} else {
    $recent_view_url = spl_dashboard_url('overview');
    $live_feed_view_url = spl_dashboard_url('overview');
}
$pending_view_url = spl_dashboard_url('my-reward');
$pending_shown = !empty($pending_approvals) ? array_slice($pending_approvals, 0, 10) : array();
$pending_more = !empty($pending_approvals) ? max(0, count($pending_approvals) - count($pending_shown)) : 0;
$top_performers_title = isset($top_performers_title) ? (string) $top_performers_title : 'Top 3 Performers (' . $periodLabel . ')';
$top_performers_subtitle = isset($top_performers_subtitle) ? trim((string) $top_performers_subtitle) : '';
$performer_accents = array(
    array('border' => '#f59e0b', 'bg' => '#fffbeb', 'icon' => '#d97706'),
    array('border' => '#3b82f6', 'bg' => '#eff6ff', 'icon' => '#2563eb'),
    array('border' => '#ec4899', 'bg' => '#fdf2f8', 'icon' => '#db2777'),
    array('border' => '#8b5cf6', 'bg' => '#f5f3ff', 'icon' => '#7c3aed'),
);
$pending_icon_classes = array('bi-gear-fill', 'bi-lightbulb-fill', 'bi-people-fill', 'bi-star-fill', 'bi-rocket-fill');
$cat_icon_map = array(
    'innovation' => array('bi-lightbulb-fill', '#8b5cf6'),
    'delivery' => array('bi-rocket-fill', '#3b82f6'),
    'learning' => array('bi-mortarboard-fill', '#10b981'),
    'team' => array('bi-people-fill', '#ec4899'),
    'consistency' => array('bi-calendar2-check', '#f59e0b'),
);
?>
  <div class="spl-dash-body">
    <div class="spl-dash-hero">
      <div class="spl-dash-hero-welcome">
        <?php if (!empty($avatar_url)): ?>
        <img src="<?php echo esc_view($avatar_url, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="spl-dash-hero-avatar">
        <?php else: ?>
        <span class="spl-dash-hero-avatar spl-dash-avatar--initials is-dark"><?php echo esc_view($initials, ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>
        <div>
          <div class="spl-dash-hero-title">Welcome back, <?php echo esc_view($display_name, ENT_QUOTES, 'UTF-8'); ?>!</div>
          <div class="spl-dash-hero-sub">Keep going, champion!</div>
        </div>
      </div>
      <div class="spl-dash-hero-level">
        <div class="spl-dash-hero-level-label">Level</div>
        <div class="spl-dash-level-pill" style="--level-color:<?php echo esc_view($levelColor, ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo esc_view($levelEmoji . ' ' . strtoupper($levelName), ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php if (!empty($next_level['points_to_go'])): ?>
        <div class="spl-dash-hero-level-next">Next: <strong><?php echo esc_view(strtoupper($next_level['name']), ENT_QUOTES, 'UTF-8'); ?></strong></div>
        <div class="progress spl-dash-level-progress">
          <div class="progress-bar" style="width:<?php echo (int) $next_level['progress_pct']; ?>%;background:<?php echo esc_view($levelColor, ENT_QUOTES, 'UTF-8'); ?>"></div>
        </div>
        <div class="spl-dash-level-pts"><?php echo number_format((int) $next_level['points_to_go']); ?> pts to go</div>
        <?php endif; ?>
      </div>
      <div class="spl-dash-hero-stat">
        <div class="spl-dash-card-label">Lifetime Points</div>
        <div class="spl-dash-stat-value"><?php echo number_format((float) $summary->lifetime_points, 0); ?></div>
        <div class="spl-dash-stat-rank">Rank #<?php echo (int) $lifetime_rank; ?> / <?php echo (int) $total_users; ?></div>
      </div>
      <div class="spl-dash-hero-stat">
        <div class="spl-dash-card-label">This Month</div>
        <div class="spl-dash-stat-value"><?php echo number_format((float) $summary->month_points, 0); ?></div>
        <div class="spl-dash-stat-rank">Rank #<?php echo (int) $month_rank; ?> / <?php echo (int) $total_users; ?></div>
      </div>
      <div class="spl-dash-hero-stat">
        <div class="spl-dash-card-label">Current Streak</div>
        <div class="spl-dash-stat-value"><?php echo (int) $streak['current']; ?> <span class="spl-dash-stat-unit">Days</span></div>
        <div class="spl-dash-stat-rank">Best: <?php echo (int) $streak['best']; ?> Days</div>
      </div>
      <div class="spl-dash-hero-stat">
        <div class="spl-dash-card-label">Team</div>
        <div class="spl-dash-team-name"><?php echo esc_view($teamName, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php if ($my_group_rank > 0): ?>
        <div class="spl-dash-stat-rank">Team Rank #<?php echo (int) $my_group_rank; ?> / <?php echo (int) $teamTotal; ?></div>
        <?php else: ?>
        <div class="spl-dash-stat-rank text-muted">Not assigned</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="spl-dash-kpi-row">
      <div class="spl-dash-kpi spl-dash-kpi--icon is-green">
        <span class="spl-dash-kpi-icon"><i class="bi bi-plus-circle-fill"></i></span>
        <div>
          <div class="spl-dash-kpi-label">Earned Today</div>
          <div class="spl-dash-kpi-value">+<?php echo number_format(max(0, (float) $kpi_today['positive']), 0); ?> <span class="spl-dash-kpi-unit">Points</span></div>
        </div>
      </div>
      <div class="spl-dash-kpi spl-dash-kpi--icon is-blue">
        <span class="spl-dash-kpi-icon"><i class="bi bi-calendar-week"></i></span>
        <div>
          <div class="spl-dash-kpi-label">This Week</div>
          <div class="spl-dash-kpi-value"><?php echo number_format(max(0, $weekNet), 0); ?> <span class="spl-dash-kpi-unit">Points</span></div>
        </div>
      </div>
      <div class="spl-dash-kpi spl-dash-kpi--icon is-purple">
        <span class="spl-dash-kpi-icon"><i class="bi bi-bar-chart-fill"></i></span>
        <div>
          <div class="spl-dash-kpi-label">This Month</div>
          <div class="spl-dash-kpi-value"><?php echo number_format((float) $kpi_month['net'], 0); ?> <span class="spl-dash-kpi-unit">Points</span></div>
        </div>
      </div>
      <div class="spl-dash-kpi spl-dash-kpi--icon is-orange<?php echo $show_pending_kpi ? '' : ' d-none'; ?>">
        <span class="spl-dash-kpi-icon"><i class="bi bi-clock-fill"></i></span>
        <div>
          <div class="spl-dash-kpi-label">Pending Approval</div>
          <div class="spl-dash-kpi-value"><?php echo (int) $kpi_pending_activities; ?> <span class="spl-dash-kpi-unit">Activities</span></div>
        </div>
      </div>
      <div class="spl-dash-kpi spl-dash-kpi--icon is-red">
        <span class="spl-dash-kpi-icon"><i class="bi bi-arrow-down-circle-fill"></i></span>
        <div>
          <div class="spl-dash-kpi-label">Deducted</div>
          <div class="spl-dash-kpi-value">-<?php echo number_format($weekDeducted, 0); ?> <span class="spl-dash-kpi-unit">Points</span></div>
        </div>
      </div>
      <div class="spl-dash-kpi spl-dash-kpi--icon is-green">
        <span class="spl-dash-kpi-icon"><i class="bi bi-graph-up-arrow"></i></span>
        <div>
          <div class="spl-dash-kpi-label">Net Score</div>
          <div class="spl-dash-kpi-value"><?php echo ($weekNet >= 0 ? '+' : '') . number_format($weekNet, 0); ?> <span class="spl-dash-kpi-unit">Points</span></div>
        </div>
      </div>
    </div>

    <div class="spl-dash-columns">
      <div class="spl-dash-col spl-dash-col--left">
        <div class="spl-dash-panel">
          <div class="spl-dash-panel-head">
            <h2 class="spl-dash-panel-title"><i class="bi bi-clock-history me-1"></i><?php echo esc_view($recent_panel_title, ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if ($can_my_reward || $can_approve || $can_levels): ?>
            <a href="<?php echo esc_view($recent_view_url, ENT_QUOTES, 'UTF-8'); ?>" class="spl-dash-link">View all</a>
            <?php endif; ?>
          </div>
          <div class="spl-dash-panel-body spl-dash-panel-body--pad-0">
            <div class="spl-dash-scroll spl-dash-scroll--activity">
            <?php if (empty($recent)): ?>
            <div class="spl-dash-empty">No recent activities yet.</div>
            <?php else: ?>
            <?php foreach ($recent as $row): ?>
            <?php
              $pts = (float) $row->points;
              $ptsClass = $pts >= 0 ? 'is-positive' : 'is-negative';
              if ($row->status === 'pending') {
                  $ptsClass = 'is-muted';
              }
              $memberName = (!$is_user_scoped) ? spl_activity_table_member_name($row) : '';
            ?>
            <div class="spl-dash-activity-row<?php echo $memberName !== '' ? ' has-member' : ''; ?>">
              <span class="spl-dash-activity-icon"><i class="bi <?php echo esc_view(spl_activity_icon_class($row), ENT_QUOTES, 'UTF-8'); ?>"></i></span>
              <div class="spl-dash-activity-main">
                <div class="spl-dash-activity-title">
                  <span class="spl-dash-activity-label"><?php echo esc_view(spl_activity_title($row), ENT_QUOTES, 'UTF-8'); ?></span>
                  <?php if ($row->status === 'pending'): ?>
                  <span class="spl-dash-activity-badge">Pending</span>
                  <?php endif; ?>
                </div>
                <div class="spl-dash-activity-meta">
                  <?php if ($memberName !== ''): ?>
                  <span class="spl-dash-activity-user"><?php echo esc_view($memberName, ENT_QUOTES, 'UTF-8'); ?></span>
                  <span class="spl-dash-activity-meta-sep" aria-hidden="true">·</span>
                  <?php endif; ?>
                  <span class="spl-dash-activity-time"><?php echo esc_view(spl_format_activity_datetime($row->created_at), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              </div>
              <div class="spl-dash-activity-pts <?php echo esc_view($ptsClass, ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($row->status === 'approved' && abs($pts) < 0.00001): ?>
                0
                <?php else: ?>
                <?php echo ($pts >= 0 ? '+' : '') . number_format($pts, 0); ?>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
            </div>
          </div>
          <div class="spl-dash-panel-foot">
            <?php echo esc_view($periodLabel, ENT_QUOTES, 'UTF-8'); ?> approved <strong><?php echo ($activityPeriodNet >= 0 ? '+' : '') . number_format($activityPeriodNet, 0); ?></strong>
            <?php if ($show_pending_kpi && $activityPeriodPending > 0): ?>
            · pending <strong>+<?php echo number_format($activityPeriodPending, 0); ?></strong>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="spl-dash-col spl-dash-col--mid">
        <?php if ($team_overview): ?>
        <?php
          $team_poster = !empty($team_overview->poster_path) ? spl_poster_url($team_overview->poster_path) : '';
          $team_trend_up = (float) $team_overview->trend >= 0;
        ?>
        <div class="spl-team-card">
          <div class="spl-team-card__head">
            <div class="spl-team-card__brand">
              <?php if ($team_poster !== ''): ?>
              <img src="<?php echo esc_view($team_poster, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="spl-team-card__logo-img">
              <?php else: ?>
              <span class="spl-team-card__logo"><i class="bi bi-shield-fill"></i></span>
              <?php endif; ?>
              <span class="spl-team-card__name">Team <?php echo esc_view($team_overview->name, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <a href="<?php echo $can_groups ? esc_view(spl_dashboard_url('groups', array('reward_period' => $reward_period)), ENT_QUOTES, 'UTF-8') : '#'; ?>" class="spl-team-card__link<?php echo $can_groups ? '' : ' pe-none text-muted'; ?>">View Team</a>
          </div>

          <div class="spl-team-card__scores">
            <div class="spl-team-card__score-main">
              <div class="spl-team-card__score-label">Team Score (<?php echo esc_view($periodLabel, ENT_QUOTES, 'UTF-8'); ?>)</div>
              <div class="spl-team-card__score-row">
                <span class="spl-team-card__score-value"><?php echo number_format((float) $team_overview->score, 0); ?></span>
                <span class="spl-team-card__trend-arrow <?php echo $team_trend_up ? 'is-up' : 'is-down'; ?>">
                  <i class="bi bi-arrow-<?php echo $team_trend_up ? 'up' : 'down'; ?>-short"></i>
                </span>
              </div>
              <div class="spl-team-card__trend-sub <?php echo $team_trend_up ? 'is-up' : 'is-down'; ?>">
                <i class="bi bi-diamond-fill"></i>
                <?php if ($trendLabel !== ''): ?>
                <?php echo ($team_trend_up ? '+' : '') . number_format((float) $team_overview->trend, 0); ?> from <?php echo esc_view($trendLabel, ENT_QUOTES, 'UTF-8'); ?>
                <?php else: ?>
                All-time score
                <?php endif; ?>
              </div>
            </div>
            <div class="spl-team-card__score-divider" aria-hidden="true"></div>
            <div class="spl-team-card__breakdown">
              <div class="spl-team-card__breakdown-row">
                <span class="spl-team-card__dot is-approved"></span>
                <span class="spl-team-card__breakdown-label">Approved</span>
                <span class="spl-team-card__breakdown-value"><?php echo number_format((float) $team_overview->approved, 0); ?></span>
              </div>
              <div class="spl-team-card__breakdown-row">
                <span class="spl-team-card__dot is-pending"></span>
                <span class="spl-team-card__breakdown-label">Pending</span>
                <span class="spl-team-card__breakdown-value"><?php echo number_format((float) $team_overview->pending, 0); ?></span>
              </div>
              <div class="spl-team-card__breakdown-row">
                <span class="spl-team-card__dot is-deducted"></span>
                <span class="spl-team-card__breakdown-label">Deducted</span>
                <span class="spl-team-card__breakdown-value">-<?php echo number_format((float) $team_overview->deducted, 0); ?></span>
              </div>
            </div>
          </div>

          <div class="spl-team-card__contributors">
            <div class="spl-team-card__contrib-head">
              <span class="spl-team-card__contrib-title">Top Contributors</span>
              <?php if ($can_groups): ?>
              <a href="<?php echo esc_view(spl_dashboard_url('groups', array('reward_period' => $reward_period)), ENT_QUOTES, 'UTF-8'); ?>" class="spl-team-card__contrib-link">View All</a>
              <?php endif; ?>
            </div>
            <div class="spl-dash-scroll spl-dash-scroll--contributor spl-team-card__contrib-list">
            <?php foreach (array_slice($team_overview->members, 0, 20) as $member): ?>
            <?php
              $barPct = min(100, (int) round(((float) $member->display_points / (float) $team_overview->max_member_points) * 100));
              $memberInitials = spl_user_initials($member->name);
              $is_you = ($current_user_id > 0 && (int) $member->id === $current_user_id);
              $memberLabel = (string) $member->name;
              if ($is_you) {
                  $memberLabel .= ' (You)';
              }
            ?>
            <div class="spl-team-card__contrib-row<?php echo $is_you ? ' is-you' : ''; ?>">
              <span class="spl-team-card__contrib-avatar"><?php echo esc_view($memberInitials, ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="spl-team-card__contrib-name" title="<?php echo esc_view($memberLabel, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($memberLabel, ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="spl-team-card__contrib-bar-wrap">
                <span class="spl-team-card__contrib-bar" style="width:<?php echo $barPct; ?>%"></span>
              </span>
              <span class="spl-team-card__contrib-pts"><?php echo number_format((float) $member->display_points, 0); ?> pts</span>
            </div>
            <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="spl-team-card spl-team-card--empty">
          <div class="spl-team-card__head">
            <div class="spl-team-card__brand">
              <span class="spl-team-card__logo"><i class="bi bi-people-fill"></i></span>
              <span class="spl-team-card__name">Team Overview</span>
            </div>
          </div>
          <div class="spl-team-card__empty-body">
            <div class="spl-dash-empty">You are not assigned to a team yet.<?php if ($can_groups): ?> <a href="<?php echo esc_view(spl_dashboard_url('groups', array('reward_period' => $reward_period)), ENT_QUOTES, 'UTF-8'); ?>">Browse groups</a><?php endif; ?></div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="spl-dash-col spl-dash-col--right">
        <div class="spl-standings-card">
          <div class="spl-standings-card__head">
            <h2 class="spl-standings-card__title">Team League Standings</h2>
            <?php if ($can_groups): ?>
            <a href="<?php echo esc_view(spl_dashboard_url('groups', array('reward_period' => $reward_period)), ENT_QUOTES, 'UTF-8'); ?>" class="spl-standings-card__link">View Full Table</a>
            <?php endif; ?>
          </div>
          <div class="spl-standings-card__body">
            <?php if (empty($team_standings)): ?>
            <div class="spl-dash-empty">No teams configured.</div>
            <?php else: ?>
            <div class="spl-dash-scroll spl-dash-scroll--standings">
            <table class="spl-standings-table">
              <thead>
                <tr>
                  <th class="col-team">Team</th>
                  <th class="col-avg">AVG</th>
                  <th class="col-points">Points</th>
                  <th class="col-trend">Trend</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($team_standings as $team): ?>
                <?php
                  $slot = ((int) $team->rank % 5) + 1;
                  $is_mine = ($team_overview && (int) $team->id === (int) $team_overview->id);
                  $team_poster_url = !empty($team->poster_path) ? spl_poster_url($team->poster_path) : '';
                  $trend_up = (float) $team->trend >= 0;
                  $member_count = isset($team->member_count) ? (int) $team->member_count : 0;
                  $avg_points = isset($team->avg_points) ? (float) $team->avg_points : 0;
                  $avg_title = $member_count > 0
                    ? 'Avg points per member (' . $member_count . ' user' . ($member_count === 1 ? '' : 's') . ')'
                    : 'No members';
                ?>
                <tr class="<?php echo $is_mine ? 'is-mine' : ''; ?>">
                  <td class="col-team">
                    <?php if ($team_poster_url !== ''): ?>
                    <img src="<?php echo esc_view($team_poster_url, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="spl-standings-shield-img">
                    <?php else: ?>
                    <span class="spl-standings-shield spl-standings-shield--<?php echo (int) $slot; ?>"><i class="bi bi-shield-fill"></i></span>
                    <?php endif; ?>
                    <span class="spl-standings-team-name"><?php echo esc_view(strtoupper($team->name), ENT_QUOTES, 'UTF-8'); ?></span>
                  </td>
                  <td class="col-avg" title="<?php echo esc_view($avg_title, ENT_QUOTES, 'UTF-8'); ?>"><?php echo number_format($avg_points, 0); ?></td>
                  <td class="col-points"><?php echo number_format((float) $team->points, 0); ?></td>
                  <td class="col-trend">
                    <span class="spl-standings-trend <?php echo $trend_up ? 'is-up' : 'is-down'; ?>" title="<?php echo ($trend_up ? '+' : '') . number_format((float) $team->trend, 0); ?>">
                      <i class="bi bi-arrow-<?php echo $trend_up ? 'up' : 'down'; ?>-short"></i>
                    </span>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="spl-widgets-row spl-widgets-row--3">
      <?php if ($show_pending_panel): ?>
      <div class="spl-widget-card">
        <div class="spl-widget-card__head">
          <h3 class="spl-widget-card__title"><?php echo esc_view($pending_panel_title, ENT_QUOTES, 'UTF-8'); ?></h3>
          <a href="<?php echo esc_view($pending_view_url, ENT_QUOTES, 'UTF-8'); ?>" class="spl-widget-card__link">View All</a>
        </div>
        <div class="spl-widget-card__body">
          <div class="spl-widget-card__scroll">
          <?php if (empty($pending_shown)): ?>
          <div class="spl-dash-empty">No pending approvals.</div>
          <?php else: ?>
          <?php foreach ($pending_shown as $idx => $row): ?>
          <?php $picon = $pending_icon_classes[$idx % count($pending_icon_classes)]; ?>
          <div class="spl-widget-pending-row">
            <span class="spl-widget-pending-icon"><i class="bi <?php echo esc_view($picon, ENT_QUOTES, 'UTF-8'); ?>"></i></span>
            <div class="spl-widget-pending-main">
              <div class="spl-widget-pending-title"><?php echo esc_view($row->rule_name, ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="spl-widget-pending-meta">Submitted by <?php echo esc_view(!empty($row->submitter_name) ? $row->submitter_name : $row->recipient_name, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="spl-widget-pending-side">
              <div class="spl-widget-pending-pts">+<?php echo number_format((float) $row->requested_points, 0); ?></div>
              <div class="spl-widget-pending-time"><?php echo esc_view(spl_time_ago($row->submitted_at), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
          </div>
          <div class="spl-widget-card__foot<?php echo ($pending_more > 0) ? '' : ' spl-widget-card__foot--empty'; ?>">
            <?php if ($pending_more > 0): ?>
            <a href="<?php echo esc_view($pending_view_url, ENT_QUOTES, 'UTF-8'); ?>" class="spl-widget-more-btn"><?php echo (int) $pending_more; ?> More Pending</a>
            <?php else: ?>
            <span class="spl-widget-card__foot-placeholder" aria-hidden="true"></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (false): ?>
      <div class="spl-widget-card">
        <div class="spl-widget-card__head">
          <h3 class="spl-widget-card__title"><?php echo esc_view($live_feed_title, ENT_QUOTES, 'UTF-8'); ?></h3>
          <?php if ($can_my_reward || (!$is_user_scoped && $can_approve)): ?>
          <a href="<?php echo esc_view($live_feed_view_url, ENT_QUOTES, 'UTF-8'); ?>" class="spl-widget-card__link">View All</a>
          <?php else: ?>
          <span class="spl-widget-card__head-end spl-widget-card__head-end--placeholder">View All</span>
          <?php endif; ?>
        </div>
        <div class="spl-widget-card__body">
          <div class="spl-widget-card__scroll">
          <?php if (empty($live_feed)): ?>
          <div class="spl-dash-empty"><?php echo $is_user_scoped ? 'No recent activity yet.' : 'No recent league activity.'; ?></div>
          <?php else: ?>
          <?php foreach ($live_feed as $row): ?>
          <div class="spl-widget-live-row">
            <span class="spl-widget-live-avatar"><?php echo esc_view(spl_user_initials($row->user_name ?: 'U'), ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="spl-widget-live-main">
              <div class="spl-widget-live-text"><strong><?php echo esc_view($row->user_name ?: 'User', ENT_QUOTES, 'UTF-8'); ?></strong> <?php echo esc_view(spl_activity_title($row), ENT_QUOTES, 'UTF-8'); ?></div>
              <div class="spl-widget-live-sub"><?php echo esc_view(!empty($row->rule_name) ? $row->rule_name : ($row->category_name ?: 'Activity'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="spl-widget-live-side">
              <div class="spl-widget-live-pts <?php echo (float) $row->points >= 0 ? 'is-up' : 'is-down'; ?>"><?php echo ((float) $row->points >= 0 ? '+' : '') . number_format((float) $row->points, 0); ?></div>
              <div class="spl-widget-live-time"><?php echo esc_view(spl_time_ago($row->created_at), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
          </div>
          <div class="spl-widget-card__foot spl-widget-card__foot--empty">
            <span class="spl-widget-card__foot-placeholder" aria-hidden="true"></span>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div class="spl-widget-card spl-widget-card--performers">
        <div class="spl-widget-card__head">
          <h3 class="spl-widget-card__title"><?php echo esc_view($top_performers_title, ENT_QUOTES, 'UTF-8'); ?></h3>
          <?php if ($top_performers_subtitle !== ''): ?>
          <span class="spl-widget-card__head-end spl-widget-card__sub"><?php echo esc_view($top_performers_subtitle, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php else: ?>
          <span class="spl-widget-card__head-end spl-widget-card__head-end--placeholder">View All</span>
          <?php endif; ?>
        </div>
        <div class="spl-widget-card__body">
          <div class="spl-widget-card__scroll spl-widget-card__scroll--static spl-widget-card__scroll--performers">
          <?php if (empty($top_performers)): ?>
          <div class="spl-dash-empty"><?php echo $is_user_scoped ? 'No performers in your group yet for ' . esc_view(strtolower($periodLabel), ENT_QUOTES, 'UTF-8') . '.' : 'No performers yet for ' . esc_view(strtolower($periodLabel), ENT_QUOTES, 'UTF-8') . '.'; ?></div>
          <?php else: ?>
          <div class="spl-widget-performers spl-widget-performers--count-<?php echo min(3, max(1, (int) count($top_performers))); ?>">
            <?php foreach ($top_performers as $pi => $perf): ?>
            <?php $accent = $performer_accents[$pi % count($performer_accents)]; ?>
            <div class="spl-widget-performer" style="--perf-border:<?php echo esc_view($accent['border'], ENT_QUOTES, 'UTF-8'); ?>;--perf-bg:<?php echo esc_view($accent['bg'], ENT_QUOTES, 'UTF-8'); ?>;--perf-icon:<?php echo esc_view($accent['icon'], ENT_QUOTES, 'UTF-8'); ?>">
              <span class="spl-widget-performer-icon"><i class="bi <?php echo esc_view($perf->icon, ENT_QUOTES, 'UTF-8'); ?>"></i></span>
              <span class="spl-widget-performer-avatar"><?php echo esc_view(spl_user_initials($perf->user_name), ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="spl-widget-performer-name"><?php echo esc_view($perf->user_name, ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="spl-widget-performer-pts"><?php echo number_format((float) $perf->points, 0); ?> pts</span>
              <span class="spl-widget-performer-role"><?php echo esc_view($perf->title, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
          </div>
          <div class="spl-widget-card__foot spl-widget-card__foot--empty">
            <span class="spl-widget-card__foot-placeholder" aria-hidden="true"></span>
          </div>
        </div>
      </div>

      <?php if ($is_org_view): ?>
      <div class="spl-widget-card">
        <div class="spl-widget-card__head">
          <h3 class="spl-widget-card__title">Category Leaders</h3>
          <span class="spl-widget-card__head-end spl-widget-card__sub"><?php echo esc_view($periodLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <div class="spl-widget-card__body">
          <div class="spl-widget-card__scroll">
          <?php if (empty($category_leaders)): ?>
          <div class="spl-dash-empty">No category leaders yet.</div>
          <?php else: ?>
          <?php foreach ($category_leaders as $leader): ?>
          <?php
            $cat_code = (string) $leader->category_code;
            $cat_icon = isset($cat_icon_map[$cat_code]) ? $cat_icon_map[$cat_code] : array('bi-star-fill', '#64748b');
          ?>
          <div class="spl-widget-cat-row">
            <span class="spl-widget-cat-icon" style="color:<?php echo esc_view($cat_icon[1], ENT_QUOTES, 'UTF-8'); ?>"><i class="bi <?php echo esc_view($cat_icon[0], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
            <span class="spl-widget-cat-name"><?php echo esc_view($leader->category_name, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="spl-widget-cat-user"><?php echo esc_view($leader->user_name, ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="spl-widget-cat-pts"><?php echo number_format((float) $leader->net_points, 0); ?></span>
          </div>
          <?php endforeach; ?>
          <?php endif; ?>
          </div>
          <div class="spl-widget-card__foot spl-widget-card__foot--empty">
            <span class="spl-widget-card__foot-placeholder" aria-hidden="true"></span>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="spl-widgets-row spl-widgets-row--bottom">
      <?php if (false && $can_my_reward): ?>
      <div class="spl-widget-card spl-widget-card--badges">
        <div class="spl-widget-card__head">
          <h3 class="spl-widget-card__title">My Badges</h3>
        </div>
        <div class="spl-widget-card__body spl-widget-card__body--badges">
          <?php if (empty($badges)): ?>
          <div class="spl-dash-empty">Earn badges by staying active!</div>
          <?php else: ?>
          <div class="spl-widget-badges">
            <?php foreach ($badges as $badge): ?>
            <div class="spl-widget-badge">
              <span class="spl-widget-badge-hex"><i class="bi <?php echo esc_view($badge['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i></span>
              <span class="spl-widget-badge-label"><?php echo esc_view($badge['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="spl-widget-card spl-widget-card--season">
        <div class="spl-widget-card__head">
          <h3 class="spl-widget-card__title">Season Progress</h3>
        </div>
        <div class="spl-widget-card__body spl-widget-season-body">
          <div class="spl-widget-season-left">
            <div class="spl-widget-season-days">Season Ends in <strong><?php echo (int) $season['days_remaining']; ?> Days</strong></div>
            <div class="progress spl-widget-season-bar">
              <div class="progress-bar bg-success" style="width:<?php echo (int) $season['progress_pct']; ?>%"></div>
            </div>
            <div class="spl-widget-season-pct"><?php echo (int) $season['progress_pct']; ?>% Completed</div>
          </div>
          <div class="spl-widget-season-right">
            <span class="spl-widget-season-shield"><i class="bi bi-shield-fill"></i><i class="bi bi-award-fill spl-widget-season-crown"></i></span>
            <?php if (!empty($next_level['points_to_go'])): ?>
            <div class="spl-widget-season-milestone">Next Milestone: <strong><?php echo esc_view(strtoupper($next_level['name']), ENT_QUOTES, 'UTF-8'); ?></strong></div>
            <div class="spl-widget-season-pts"><?php echo number_format((int) $next_level['points_to_go']); ?> pts to go</div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="spl-widget-reward">
        <div class="spl-widget-reward-content">
          <div class="spl-widget-reward-title">Champion Team Reward</div>
          <div class="spl-widget-reward-sub">Winning Team Gets</div>
          <ul class="spl-widget-reward-list">
            <li><i class="bi bi-trophy"></i> Trophy</li>
            <li><i class="bi bi-gift"></i> Exciting Rewards</li>
            <li><i class="bi bi-balloon"></i> Team Celebration</li>
          </ul>
        </div>
        <div class="spl-widget-reward-trophy"><i class="bi bi-trophy-fill"></i></div>
      </div>
    </div>

  </div>
