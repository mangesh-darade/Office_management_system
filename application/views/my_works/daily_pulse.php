<?php
$embed = !empty($embed);
$pulse = isset($pulse) && is_array($pulse) ? $pulse : array();
$pulse_date = isset($pulse_date) ? (string) $pulse_date : date('Y-m-d');
$date_label = date('D, M j, Y', strtotime($pulse_date));

if (!$embed) {
    $this->load->view('partials/header', array(
        'title' => 'Daily Pulse',
        'extra_css' => array('assets/css/my-works.css'),
    ));
}

$section = function ($key, $default = null) use ($pulse) {
    return array_key_exists($key, $pulse) ? $pulse[$key] : $default;
};

$render_more = function ($block) {
    if (!is_array($block) || empty($block['more'])) {
        return;
    }
    echo '<div class="mw-pulse-more text-muted">+' . (int) $block['more'] . ' more</div>';
};

$pulse_count_badge = function ($total) {
    $n = (int) $total;
    $cls = $n > 0 ? 'mw-pulse-count' : 'mw-pulse-count mw-pulse-count--zero';
    echo '<span class="' . $cls . '">' . $n . '</span>';
};
?>

<div class="mw-pulse-page<?php echo $embed ? ' mw-pulse-page--embed' : ''; ?>">
  <div class="mw-pulse-head">
    <h2 class="mw-pulse-title">Daily Pulse</h2>
    <span class="mw-pulse-sub"><?php echo esc_view($date_label, ENT_QUOTES, 'UTF-8'); ?></span>
  </div>


  <ul class="nav nav-tabs mb-4 mw-pulse-tabs" id="pulseTabs" role="tablist" style="margin-top: 1.5rem;">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="pulse-summary-tab" data-bs-toggle="tab" data-bs-target="#pulse-summary" type="button" role="tab" aria-controls="pulse-summary" aria-selected="true">Summary</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="pulse-updates-tab" data-bs-toggle="tab" data-bs-target="#pulse-updates" type="button" role="tab" aria-controls="pulse-updates" aria-selected="false">Updates</button>
    </li>
  </ul>

  <div class="tab-content" id="pulseTabsContent">
    <div class="tab-pane fade show active" id="pulse-summary" role="tabpanel" aria-labelledby="pulse-summary-tab">
<div class="pulse-updates-wrapper" style="display:flex; flex-direction:column; gap:16px;">

    <?php $att = $section('attendance'); if (is_array($att)): ?>
    <?php
      $pulse_att_times = function ($row) {
          $in = isset($row['check_in']) ? trim((string) $row['check_in']) : '';
          $out = isset($row['check_out']) ? trim((string) $row['check_out']) : '';
          if ($in === '' && $out === '') {
              return;
          }
          echo '<span class="mw-pulse-item-meta mw-pulse-att-times">';
          if ($in !== '') {
              echo '<span class="mw-pulse-att-time" title="Check-in"><i class="bi bi-box-arrow-in-right"></i>In ' . esc_view($in, ENT_QUOTES, 'UTF-8') . '</span>';
          }
          if ($out !== '') {
              echo '<span class="mw-pulse-att-time" title="Check-out"><i class="bi bi-box-arrow-right"></i>Out ' . esc_view($out, ENT_QUOTES, 'UTF-8') . '</span>';
          } elseif ($in !== '') {
              echo '<span class="mw-pulse-att-time mw-pulse-att-time--pending" title="Not checked out yet">Out —</span>';
          }
          echo '</span>';
      };
    ?>
    <div class="premium-card" style="width: 100%;">
      <div class="premium-card-header">
        <div class="premium-icon-box bg-light-green">
            <i class="bi bi bi-person-check"></i>
        </div>
        <div>
            <h3 class="premium-card-title">Attendance today</h3>
            <p class="premium-card-subtitle">Snapshot of today's records</p>
        </div>
        <div class="premium-card-menu">
            
        </div>
      </div>
      <div class="mw-pulse-cols mw-pulse-cols--4">
        <div class="mw-pulse-col">
          <div class="mw-pulse-col-head">
            <span class="mw-pulse-mini-title">Leave</span>
            <?php $pulse_count_badge($att['on_leave']['total']); ?>
          </div>
          <?php if (empty($att['on_leave']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($att['on_leave']['items'] as $row): ?>
            <?php
              $leave_note = isset($row['notes']) ? trim((string) $row['notes']) : '';
            ?>
            <li class="mw-pulse-note-row<?php echo $leave_note !== '' ? ' mw-pulse-note-row--has' : ''; ?>">
              <span class="mw-pulse-item-primary"><?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php if (!empty($row['leave_type'])): ?>
              <span class="mw-pulse-item-meta"><?php echo esc_view($row['leave_type'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php endif; ?>
              <?php if ($leave_note !== ''): ?>
              <button type="button"
                      class="btn btn-link p-0 border-0 mw-pulse-note-btn"
                      data-note-name="<?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?>"
                      data-note-text="<?php echo esc_view($leave_note, ENT_QUOTES, 'UTF-8'); ?>"
                      title="View notes"
                      aria-label="View notes for <?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-chat-left-text mw-pulse-note-icon" aria-hidden="true"></i>
              </button>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($att['on_leave']); ?>
          <?php endif; ?>
        </div>
        <div class="mw-pulse-col">
          <div class="mw-pulse-col-head">
            <span class="mw-pulse-mini-title">WFH</span>
            <?php $pulse_count_badge($att['wfh']['total']); ?>
          </div>
          <?php if (empty($att['wfh']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($att['wfh']['items'] as $row): ?>
            <?php
              $wfh_note = isset($row['notes']) ? trim((string) $row['notes']) : '';
            ?>
            <li class="mw-pulse-note-row<?php echo $wfh_note !== '' ? ' mw-pulse-note-row--has' : ''; ?>">
              <span class="mw-pulse-item-primary"><?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php if ($wfh_note !== ''): ?>
              <button type="button"
                      class="btn btn-link p-0 border-0 mw-pulse-note-btn"
                      data-note-name="<?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?>"
                      data-note-text="<?php echo esc_view($wfh_note, ENT_QUOTES, 'UTF-8'); ?>"
                      title="View notes"
                      aria-label="View notes for <?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?>">
                <i class="bi bi-chat-left-text mw-pulse-note-icon" aria-hidden="true"></i>
              </button>
              <?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($att['wfh']); ?>
          <?php endif; ?>
        </div>
        <div class="mw-pulse-col">
          <div class="mw-pulse-col-head">
            <span class="mw-pulse-mini-title">Check-in / Out</span>
            <?php $pulse_count_badge($att['checked_in']['total']); ?>
          </div>
          <?php if (empty($att['checked_in']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($att['checked_in']['items'] as $row): ?>
            <li>
              <span class="mw-pulse-item-primary"><?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?></span>
              <?php $pulse_att_times($row); ?>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($att['checked_in']); ?>
          <?php endif; ?>
        </div>
        <div class="mw-pulse-col<?php echo !empty($att['not_checked_in']['items']) ? ' mw-pulse-col--warn' : ''; ?>">
          <div class="mw-pulse-col-head">
            <span class="mw-pulse-mini-title">Not in</span>
            <?php $pulse_count_badge($att['not_checked_in']['total']); ?>
          </div>
          <?php if (empty($att['not_checked_in']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact mw-pulse-list--missing">
            <?php foreach ($att['not_checked_in']['items'] as $row): ?>
            <li><span class="mw-pulse-item-primary"><?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?></span></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($att['not_checked_in']); ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php $clients = $section('clients_added'); if ($clients !== null): ?>
    <div class="premium-card" style="width: 100%;">
      <div class="premium-card-header">
        <div class="premium-icon-box bg-light-blue">
            <i class="bi bi bi-building"></i>
        </div>
        <div>
            <h3 class="premium-card-title">Clients added</h3>
            <p class="premium-card-subtitle">Snapshot of today's records</p>
        </div>
        <div class="premium-card-menu">
            <?php $pulse_count_badge($clients['total']); ?>
        </div>
      </div>
      <?php if (empty($clients['items'])): ?>
      <p class="mw-pulse-empty">No clients added today.</p>
      <?php else: ?>
      <ul class="mw-pulse-chips">
        <?php foreach ($clients['items'] as $c): ?>
        <li>
          <a class="mw-pulse-chip" href="<?php echo esc_view($c['url'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo !empty($c['client_code']) ? ' title="' . esc_view($c['client_code'], ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
            <?php echo esc_view($c['company_name'] !== '' ? $c['company_name'] : 'Client #' . (int) $c['id'], ENT_QUOTES, 'UTF-8'); ?>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php $render_more($clients); ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>


    <?php $reqs = $section('requirements_added'); if ($reqs !== null): ?>
    <div class="premium-card">
      <div class="premium-card-header">
        <div class="premium-icon-box bg-light-orange">
            <i class="bi bi bi-clipboard-check"></i>
        </div>
        <div>
            <h3 class="premium-card-title">Requirements added</h3>
            <p class="premium-card-subtitle">Snapshot of today's records</p>
        </div>
        <div class="premium-card-menu">
            <?php $pulse_count_badge($reqs['total']); ?>
        </div>
      </div>
      <?php if (empty($reqs['items'])): ?>
      <p class="mw-pulse-empty">No requirements added today.</p>
      <?php else: ?>
      <ul class="mw-pulse-list mw-pulse-list--compact">
        <?php foreach ($reqs['items'] as $row): ?>
        <li title="<?php echo esc_view($row['req_number'], ENT_QUOTES, 'UTF-8'); ?>">
          <a class="mw-pulse-item-primary" href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?></a>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php $render_more($reqs); ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php $defs = $section('defects_added'); if ($defs !== null): ?>
    <div class="premium-card">
      <div class="premium-card-header">
        <div class="premium-icon-box bg-light-purple">
            <i class="bi bi bi-bug"></i>
        </div>
        <div>
            <h3 class="premium-card-title">Defects added</h3>
            <p class="premium-card-subtitle">Snapshot of today's records</p>
        </div>
        <div class="premium-card-menu">
            <?php $pulse_count_badge($defs['total']); ?>
        </div>
      </div>
      <?php if (empty($defs['items'])): ?>
      <p class="mw-pulse-empty">No defects added today.</p>
      <?php else: ?>
      <ul class="mw-pulse-list mw-pulse-list--compact">
        <?php foreach ($defs['items'] as $row): ?>
        <li title="<?php echo esc_view($row['defect_number'], ENT_QUOTES, 'UTF-8'); ?>">
          <a class="mw-pulse-item-primary" href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?></a>
          <?php if ($row['severity'] !== ''): ?><span class="mw-pulse-tag"><?php echo esc_view($row['severity'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php $render_more($defs); ?>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php $ov = $section('overview_today'); if (is_array($ov)): ?>
    <div class="premium-card" style="width: 100%;">
      <div class="premium-card-header">
        <div class="premium-icon-box bg-light-blue">
            <i class="bi bi bi-sunrise"></i>
        </div>
        <div>
            <h3 class="premium-card-title">Overview — Today</h3>
            <p class="premium-card-subtitle">Snapshot of today's records</p>
        </div>
        <div class="premium-card-menu">
            <a class="btn btn-sm btn-outline-primary mw-pulse-action" href="<?php echo esc_view($ov['url'], ENT_QUOTES, 'UTF-8'); ?>">Open Overview</a>
        </div>
      </div>
      <div class="mw-pulse-cols mw-pulse-cols--2">
        <div class="mw-pulse-col">
          <div class="mw-pulse-col-head">
            <span class="mw-pulse-mini-title">Ad hoc</span>
            <?php $pulse_count_badge($ov['ad_hoc']['total']); ?>
          </div>
          <?php if (empty($ov['ad_hoc']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($ov['ad_hoc']['items'] as $row): ?>
            <li><a class="mw-pulse-item-primary" href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($ov['ad_hoc']); ?>
          <?php endif; ?>
        </div>
        <div class="mw-pulse-col">
          <div class="mw-pulse-col-head">
            <span class="mw-pulse-mini-title">Project</span>
            <?php $pulse_count_badge($ov['project']['total']); ?>
          </div>
          <?php if (empty($ov['project']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($ov['project']['items'] as $row): ?>
            <li><a class="mw-pulse-item-primary" href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($ov['project']); ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<div class="tab-pane fade" id="pulse-updates" role="tabpanel" aria-labelledby="pulse-updates-tab">
      
<style>
.pulse-updates-wrapper {
    margin-top: 16px;
}
.mw-pulse-cols--2 {
    gap: 24px;
}
.premium-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #f1f3f5;
    box-shadow: 0px 2px 12px rgba(0, 0, 0, 0.04);
    padding: 16px;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.premium-card-header {
    display: flex;
    align-items: center;
    margin-bottom: 16px;
}
.premium-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    margin-right: 12px;
    flex-shrink: 0;
}
.bg-light-blue { background: #e0e7ff; color: #4f46e5; }
.bg-light-green { background: #dcfce7; color: #16a34a; }
.bg-light-purple { background: #f3e8ff; color: #9333ea; }
.bg-light-orange { background: #ffedd5; color: #ea580c; }

.premium-card-title {
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
    margin: 0;
    line-height: 1.2;
}
.premium-card-subtitle {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 2px 0 0 0;
}
.premium-card-menu {
    margin-left: auto;
    color: #9ca3af;
    cursor: pointer;
    padding: 8px;
}

.premium-table-wrap {
    flex-grow: 1;
    overflow-x: auto;
}
.premium-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.8rem;
}
.premium-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.65rem;
    padding: 6px 8px;
    border: none;
    letter-spacing: 0.05em;
    white-space: normal;
}
.premium-table thead th:first-child {
    border-top-left-radius: 6px;
    border-bottom-left-radius: 6px;
}
.premium-table thead th:last-child {
    border-top-right-radius: 6px;
    border-bottom-right-radius: 6px;
}
.premium-table tbody td {
    padding: 6px 8px;
    color: #334155;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    line-height: 1.3;
}
.premium-table tbody tr:last-child td {
    border-bottom: none;
}
.premium-title-link {
    color: #0f172a;
    font-weight: 500;
    text-decoration: none;
}
.premium-title-link:hover {
    color: #4f46e5;
}
.premium-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    white-space: nowrap;
    display: inline-block;
}
.badge-success-light { background: #dcfce7; color: #16a34a; }
.badge-warning-light { background: #ffedd5; color: #ea580c; }
.badge-info-light { background: #e0f2fe; color: #0284c7; }
.badge-purple-light { background: #f3e8ff; color: #9333ea; }
.badge-gray-light { background: #f1f5f9; color: #64748b; }

.premium-footer {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}
.premium-footer-link {
    font-size: 0.8rem;
    font-weight: 600;
    color: #4f46e5;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.premium-footer-link:hover {
    text-decoration: underline;
}
</style>
      <div class="pulse-updates-wrapper">
      <div class="mw-pulse-grid mw-pulse-cols mw-pulse-cols--2">
    <?php $client_history_var = $section('client_history'); if (!is_array($client_history_var)) { $client_history_var = array('items' => array(), 'total' => 0, 'more' => 0); } ?>
    <div class="premium-card">
      <div class="premium-card-header">
        <div class="premium-icon-box bg-light-blue">
            <i class="bi bi-building"></i>
        </div>
        <div>
            <h3 class="premium-card-title">Client History</h3>
            <p class="premium-card-subtitle">Real-time overview of client updates</p>
        </div>
        <div class="premium-card-menu"><i class="bi bi-three-dots-vertical"></i></div>
      </div>
      
      <?php if (empty($client_history_var['items'])): ?>
      <p class="text-muted" style="font-size:0.9rem;">No activity today.</p>
      <?php else: ?>
      <div class="premium-table-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Project</th>
              <th>Client</th>
              <th>Action / Status</th>
              <th>Created By</th>
              <th>Time</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($client_history_var['items'], 0, 5) as $row): ?>
            <?php 
                // Determine badge color based on action
                $action_lower = strtolower($row['action']);
                $badge_class = 'badge-gray-light';
                if (strpos($action_lower, 'add') !== false || strpos($action_lower, 'create') !== false) $badge_class = 'badge-success-light';
                elseif (strpos($action_lower, 'updat') !== false || strpos($action_lower, 'edit') !== false) $badge_class = 'badge-info-light';
                elseif (strpos($action_lower, 'delet') !== false || strpos($action_lower, 'remov') !== false) $badge_class = 'badge-warning-light';
                elseif (strpos($action_lower, 'note') !== false || strpos($action_lower, 'comment') !== false) $badge_class = 'badge-purple-light';
            ?>
            <tr>
              <td>
                <a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>" class="premium-title-link" title="<?php echo esc_view($row['detail'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </td>
              <td><?php echo esc_view($row['project_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo esc_view($row['client_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><span class="premium-badge <?php echo $badge_class; ?>"><?php echo esc_view($row['action'], ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td><?php echo esc_view($row['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="text-nowrap text-muted"><?php echo esc_view(date('M j, H:i', strtotime($row['at'])), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
      
      <div class="premium-footer">
        <a href="<?php echo site_url('clients'); ?>" class="premium-footer-link">View All Client History <i class="bi bi-chevron-right" style="font-size:0.75rem;"></i></a>
      </div>
    </div>
    <?php $daily_activity_var = $section('daily_activity'); if (!is_array($daily_activity_var)) { $daily_activity_var = array('items' => array(), 'total' => 0, 'more' => 0); } ?>
    <div class="premium-card">
      <div class="premium-card-header">
        <div class="premium-icon-box bg-light-purple">
            <i class="bi bi-journal-text"></i>
        </div>
        <div>
            <h3 class="premium-card-title">Daily Activity</h3>
            <p class="premium-card-subtitle">Recent work activities logged today</p>
        </div>
        <div class="premium-card-menu"><i class="bi bi-three-dots-vertical"></i></div>
      </div>
      
      <?php if (empty($daily_activity_var['items'])): ?>
      <p class="text-muted" style="font-size:0.9rem;">No activity today.</p>
      <?php else: ?>
      <div class="premium-table-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Project</th>
              <th>Client</th>
              <th>Action / Status</th>
              <th>Created By</th>
              <th>Time</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($daily_activity_var['items'], 0, 5) as $row): ?>
            <?php 
                // Determine badge color based on action
                $action_lower = strtolower($row['action']);
                $badge_class = 'badge-gray-light';
                if (strpos($action_lower, 'add') !== false || strpos($action_lower, 'create') !== false) $badge_class = 'badge-success-light';
                elseif (strpos($action_lower, 'updat') !== false || strpos($action_lower, 'edit') !== false) $badge_class = 'badge-info-light';
                elseif (strpos($action_lower, 'delet') !== false || strpos($action_lower, 'remov') !== false) $badge_class = 'badge-warning-light';
                elseif (strpos($action_lower, 'note') !== false || strpos($action_lower, 'comment') !== false) $badge_class = 'badge-purple-light';
            ?>
            <tr>
              <td>
                <a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>" class="premium-title-link" title="<?php echo esc_view($row['detail'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </td>
              <td><?php echo esc_view($row['project_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo esc_view($row['client_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><span class="premium-badge <?php echo $badge_class; ?>"><?php echo esc_view($row['action'], ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td><?php echo esc_view($row['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="text-nowrap text-muted"><?php echo esc_view(date('M j, H:i', strtotime($row['at'])), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
      
      <div class="premium-footer">
        <a href="<?php echo site_url('daily-activity/list'); ?>" class="premium-footer-link">View All Daily Activity <i class="bi bi-chevron-right" style="font-size:0.75rem;"></i></a>
      </div>
    </div>
    <?php $adhoc_history_var = $section('adhoc_history'); if (!is_array($adhoc_history_var)) { $adhoc_history_var = array('items' => array(), 'total' => 0, 'more' => 0); } ?>
    <div class="premium-card">
      <div class="premium-card-header">
        <div class="premium-icon-box bg-light-orange">
            <i class="bi bi-lightning"></i>
        </div>
        <div>
            <h3 class="premium-card-title">Ad hoc History</h3>
            <p class="premium-card-subtitle">Updates on ad hoc assignments</p>
        </div>
        <div class="premium-card-menu"><i class="bi bi-three-dots-vertical"></i></div>
      </div>
      
      <?php if (empty($adhoc_history_var['items'])): ?>
      <p class="text-muted" style="font-size:0.9rem;">No activity today.</p>
      <?php else: ?>
      <div class="premium-table-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Project</th>
              <th>Client</th>
              <th>Action / Status</th>
              <th>Created By</th>
              <th>Time</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($adhoc_history_var['items'], 0, 5) as $row): ?>
            <?php 
                // Determine badge color based on action
                $action_lower = strtolower($row['action']);
                $badge_class = 'badge-gray-light';
                if (strpos($action_lower, 'add') !== false || strpos($action_lower, 'create') !== false) $badge_class = 'badge-success-light';
                elseif (strpos($action_lower, 'updat') !== false || strpos($action_lower, 'edit') !== false) $badge_class = 'badge-info-light';
                elseif (strpos($action_lower, 'delet') !== false || strpos($action_lower, 'remov') !== false) $badge_class = 'badge-warning-light';
                elseif (strpos($action_lower, 'note') !== false || strpos($action_lower, 'comment') !== false) $badge_class = 'badge-purple-light';
            ?>
            <tr>
              <td>
                <a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>" class="premium-title-link" title="<?php echo esc_view($row['detail'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </td>
              <td><?php echo esc_view($row['project_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo esc_view($row['client_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><span class="premium-badge <?php echo $badge_class; ?>"><?php echo esc_view($row['action'], ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td><?php echo esc_view($row['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="text-nowrap text-muted"><?php echo esc_view(date('M j, H:i', strtotime($row['at'])), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
      
      <div class="premium-footer">
        <a href="<?php echo site_url('my-works'); ?>" class="premium-footer-link">View All Ad hoc History <i class="bi bi-chevron-right" style="font-size:0.75rem;"></i></a>
      </div>
    </div>
    <?php $project_history_var = $section('project_history'); if (!is_array($project_history_var)) { $project_history_var = array('items' => array(), 'total' => 0, 'more' => 0); } ?>
    <div class="premium-card">
      <div class="premium-card-header">
        <div class="premium-icon-box bg-light-green">
            <i class="bi bi-kanban"></i>
        </div>
        <div>
            <h3 class="premium-card-title">Project History</h3>
            <p class="premium-card-subtitle">Latest progress on project updates</p>
        </div>
        <div class="premium-card-menu"><i class="bi bi-three-dots-vertical"></i></div>
      </div>
      
      <?php if (empty($project_history_var['items'])): ?>
      <p class="text-muted" style="font-size:0.9rem;">No activity today.</p>
      <?php else: ?>
      <div class="premium-table-wrap">
        <table class="premium-table">
          <thead>
            <tr>
              <th>Title</th>
              <th>Project</th>
              <th>Client</th>
              <th>Action / Status</th>
              <th>Created By</th>
              <th>Time</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (array_slice($project_history_var['items'], 0, 5) as $row): ?>
            <?php 
                // Determine badge color based on action
                $action_lower = strtolower($row['action']);
                $badge_class = 'badge-gray-light';
                if (strpos($action_lower, 'add') !== false || strpos($action_lower, 'create') !== false) $badge_class = 'badge-success-light';
                elseif (strpos($action_lower, 'updat') !== false || strpos($action_lower, 'edit') !== false) $badge_class = 'badge-info-light';
                elseif (strpos($action_lower, 'delet') !== false || strpos($action_lower, 'remov') !== false) $badge_class = 'badge-warning-light';
                elseif (strpos($action_lower, 'note') !== false || strpos($action_lower, 'comment') !== false) $badge_class = 'badge-purple-light';
            ?>
            <tr>
              <td>
                <a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>" class="premium-title-link" title="<?php echo esc_view($row['detail'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </td>
              <td><?php echo esc_view($row['project_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo esc_view($row['client_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><span class="premium-badge <?php echo $badge_class; ?>"><?php echo esc_view($row['action'], ENT_QUOTES, 'UTF-8'); ?></span></td>
              <td><?php echo esc_view($row['user_name'], ENT_QUOTES, 'UTF-8'); ?></td>
              <td class="text-nowrap text-muted"><?php echo esc_view(date('M j, H:i', strtotime($row['at'])), ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
      
      <div class="premium-footer">
        <a href="<?php echo site_url('projects'); ?>" class="premium-footer-link">View All Project History <i class="bi bi-chevron-right" style="font-size:0.75rem;"></i></a>
      </div>
    </div>
      </div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
    var urlParams = new URLSearchParams(window.location.search);
    var currentTab = urlParams.get('tab');
    
    if (currentTab === 'pulse-updates' || currentTab === 'pulse-summary') {
        var tabBtn = document.getElementById(currentTab + '-tab');
        if (tabBtn && typeof bootstrap !== 'undefined') {
            var bsTab = new bootstrap.Tab(tabBtn);
            bsTab.show();
        }
    }

    var tabs = document.querySelectorAll('#pulseTabs .nav-link');
    tabs.forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function(e) {
            var id = e.target.id.replace('-tab', '');
            var url = new URL(window.location.href);
            url.searchParams.set('tab', id);
            window.history.replaceState({}, '', url);
        });
    });
})();
</script>

<?php if (!$embed): ?>
<?php $this->load->view('partials/footer'); ?>
<script src="<?php echo base_url('assets/js/my-works-lane-status.js'); ?>"></script>
<?php endif; ?>
