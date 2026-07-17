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
    echo '<div class="mw-pulse-more text-muted small">+' . (int) $block['more'] . ' more</div>';
};
?>

<div class="mw-pulse-page<?php echo $embed ? ' mw-pulse-page--embed' : ''; ?>">
  <div class="mw-pulse-head">
    <div>
      <h2 class="mw-pulse-title">Daily Pulse</h2>
      <p class="mw-pulse-sub mb-0"><?php echo esc_view($date_label, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
  </div>

  <div class="mw-pulse-grid">

    <?php $clients = $section('clients_added'); if ($clients !== null): ?>
    <section class="mw-pulse-card">
      <div class="mw-pulse-card-head">
        <h3 class="mw-pulse-card-title"><i class="bi bi-building me-1"></i>Clients added</h3>
        <span class="badge text-bg-secondary"><?php echo (int) $clients['total']; ?></span>
      </div>
      <?php if (empty($clients['items'])): ?>
      <p class="mw-pulse-empty">No clients added today.</p>
      <?php else: ?>
      <ul class="mw-pulse-list">
        <?php foreach ($clients['items'] as $c): ?>
        <li>
          <a href="<?php echo esc_view($c['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($c['company_name'] !== '' ? $c['company_name'] : 'Client #' . (int) $c['id'], ENT_QUOTES, 'UTF-8'); ?></a>
          <?php if (!empty($c['client_code'])): ?>
          <span class="text-muted small"><?php echo esc_view($c['client_code'], ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php $render_more($clients); ?>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php $att = $section('attendance'); if (is_array($att)): ?>
    <section class="mw-pulse-card mw-pulse-card--wide">
      <div class="mw-pulse-card-head">
        <h3 class="mw-pulse-card-title"><i class="bi bi-person-check me-1"></i>Attendance today</h3>
      </div>
      <div class="row g-1">
        <div class="col-md-6 col-lg-3">
          <div class="mw-pulse-mini-title">Leave (<?php echo (int) $att['on_leave']['total']; ?>)</div>
          <?php if (empty($att['on_leave']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($att['on_leave']['items'] as $row): ?>
            <li><?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?> <span class="text-muted"><?php echo esc_view($row['leave_type'], ENT_QUOTES, 'UTF-8'); ?></span></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($att['on_leave']); ?>
          <?php endif; ?>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="mw-pulse-mini-title">WFH (<?php echo (int) $att['wfh']['total']; ?>)</div>
          <?php if (empty($att['wfh']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($att['wfh']['items'] as $row): ?>
            <li><?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($att['wfh']); ?>
          <?php endif; ?>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="mw-pulse-mini-title">In (<?php echo (int) $att['checked_in']['total']; ?>)</div>
          <?php if (empty($att['checked_in']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($att['checked_in']['items'] as $row): ?>
            <li>
              <?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?>
              <span class="text-muted"><?php echo esc_view(substr($row['check_in'], 0, 5), ENT_QUOTES, 'UTF-8'); ?><?php echo $row['check_out'] !== '' ? ' – ' . esc_view(substr($row['check_out'], 0, 5), ENT_QUOTES, 'UTF-8') : ''; ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($att['checked_in']); ?>
          <?php endif; ?>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="mw-pulse-mini-title">Not in (<?php echo (int) $att['not_checked_in']['total']; ?>)</div>
          <?php if (empty($att['not_checked_in']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($att['not_checked_in']['items'] as $row): ?>
            <li><?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($att['not_checked_in']); ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php $da = $section('daily_activity'); if (is_array($da)): ?>
    <section class="mw-pulse-card mw-pulse-card--wide">
      <div class="mw-pulse-card-head">
        <h3 class="mw-pulse-card-title"><i class="bi bi-journal-text me-1"></i>Daily Activity</h3>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo esc_view($da['view_all_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">View all</a>
      </div>
      <div class="row g-1">
        <div class="col-md-6">
          <div class="mw-pulse-mini-title">Updated (<?php echo (int) $da['logged']['total']; ?>)</div>
          <?php if (empty($da['logged']['items'])): ?>
          <p class="mw-pulse-empty mb-0">No updates yet.</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($da['logged']['items'] as $row): ?>
            <li><?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?> <span class="badge text-bg-light"><?php echo (int) $row['entry_count']; ?></span></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($da['logged']); ?>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <div class="mw-pulse-mini-title">Not updated (<?php echo (int) $da['not_logged']['total']; ?>)</div>
          <?php if (empty($da['not_logged']['items'])): ?>
          <p class="mw-pulse-empty mb-0">Everyone updated.</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact mw-pulse-list--missing">
            <?php foreach ($da['not_logged']['items'] as $row): ?>
            <li><?php echo esc_view(isset($row['label']) ? $row['label'] : ('- ' . $row['name']), ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($da['not_logged']); ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php $ph = $section('project_history'); ?>
    <section class="mw-pulse-card">
      <div class="mw-pulse-card-head">
        <h3 class="mw-pulse-card-title"><i class="bi bi-kanban me-1"></i>Project Task History</h3>
        <span class="badge text-bg-secondary"><?php echo (int) $ph['total']; ?></span>
      </div>
      <?php if (empty($ph['items'])): ?>
      <p class="mw-pulse-empty">No project task activity today.</p>
      <?php else: ?>
      <ul class="mw-pulse-list mw-pulse-list--compact">
        <?php foreach ($ph['items'] as $row): ?>
        <li title="<?php echo esc_view($row['title'] . ' · ' . $row['action'], ENT_QUOTES, 'UTF-8'); ?>">
          <a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?></a>
          <span class="text-muted"><?php echo esc_view($row['action'], ENT_QUOTES, 'UTF-8'); ?><?php if ($row['user_name'] !== ''): ?> · <?php echo esc_view($row['user_name'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?> · <?php echo esc_view(date('H:i', strtotime($row['at'])), ENT_QUOTES, 'UTF-8'); ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php $render_more($ph); ?>
      <?php endif; ?>
    </section>

    <?php $ah = $section('adhoc_history'); ?>
    <section class="mw-pulse-card">
      <div class="mw-pulse-card-head">
        <h3 class="mw-pulse-card-title"><i class="bi bi-lightning me-1"></i>Ad hoc Task History</h3>
        <span class="badge text-bg-secondary"><?php echo (int) $ah['total']; ?></span>
      </div>
      <?php if (empty($ah['items'])): ?>
      <p class="mw-pulse-empty">No ad hoc task activity today.</p>
      <?php else: ?>
      <ul class="mw-pulse-list mw-pulse-list--compact">
        <?php foreach ($ah['items'] as $row): ?>
        <li title="<?php echo esc_view($row['title'] . ' · ' . $row['action'], ENT_QUOTES, 'UTF-8'); ?>">
          <a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?></a>
          <span class="text-muted"><?php echo esc_view($row['action'], ENT_QUOTES, 'UTF-8'); ?><?php if ($row['user_name'] !== ''): ?> · <?php echo esc_view($row['user_name'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?> · <?php echo esc_view(date('H:i', strtotime($row['at'])), ENT_QUOTES, 'UTF-8'); ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php $render_more($ah); ?>
      <?php endif; ?>
    </section>

    <?php $reqs = $section('requirements_added'); if ($reqs !== null): ?>
    <section class="mw-pulse-card">
      <div class="mw-pulse-card-head">
        <h3 class="mw-pulse-card-title"><i class="bi bi-clipboard-check me-1"></i>Requirements added</h3>
        <span class="badge text-bg-secondary"><?php echo (int) $reqs['total']; ?></span>
      </div>
      <?php if (empty($reqs['items'])): ?>
      <p class="mw-pulse-empty">No requirements added today.</p>
      <?php else: ?>
      <ul class="mw-pulse-list">
        <?php foreach ($reqs['items'] as $row): ?>
        <li>
          <a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['req_number'], ENT_QUOTES, 'UTF-8'); ?></a>
          — <?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php $render_more($reqs); ?>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php $defs = $section('defects_added'); if ($defs !== null): ?>
    <section class="mw-pulse-card">
      <div class="mw-pulse-card-head">
        <h3 class="mw-pulse-card-title"><i class="bi bi-bug me-1"></i>Defects added</h3>
        <span class="badge text-bg-secondary"><?php echo (int) $defs['total']; ?></span>
      </div>
      <?php if (empty($defs['items'])): ?>
      <p class="mw-pulse-empty">No defects added today.</p>
      <?php else: ?>
      <ul class="mw-pulse-list">
        <?php foreach ($defs['items'] as $row): ?>
        <li>
          <a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['defect_number'], ENT_QUOTES, 'UTF-8'); ?></a>
          — <?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?>
          <?php if ($row['severity'] !== ''): ?><span class="badge text-bg-light"><?php echo esc_view($row['severity'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ul>
      <?php $render_more($defs); ?>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <?php $ov = $section('overview_today'); if (is_array($ov)): ?>
    <section class="mw-pulse-card mw-pulse-card--wide">
      <div class="mw-pulse-card-head">
        <h3 class="mw-pulse-card-title"><i class="bi bi-sunrise me-1"></i>Overview — Today</h3>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo esc_view($ov['url'], ENT_QUOTES, 'UTF-8'); ?>">Open Overview</a>
      </div>
      <div class="row g-1">
        <div class="col-md-6">
          <div class="mw-pulse-mini-title">Ad hoc (<?php echo (int) $ov['ad_hoc']['total']; ?>)</div>
          <?php if (empty($ov['ad_hoc']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($ov['ad_hoc']['items'] as $row): ?>
            <li><a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($ov['ad_hoc']); ?>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <div class="mw-pulse-mini-title">Project (<?php echo (int) $ov['project']['total']; ?>)</div>
          <?php if (empty($ov['project']['items'])): ?>
          <p class="mw-pulse-empty mb-0">None</p>
          <?php else: ?>
          <ul class="mw-pulse-list mw-pulse-list--compact">
            <?php foreach ($ov['project']['items'] as $row): ?>
            <li><a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
            <?php endforeach; ?>
          </ul>
          <?php $render_more($ov['project']); ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php $spl = $section('spl_group_scores'); if (is_array($spl)): ?>
    <section class="mw-pulse-card mw-pulse-card--wide">
      <div class="mw-pulse-card-head">
        <h3 class="mw-pulse-card-title"><i class="bi bi-trophy me-1"></i>SPL Group Scores</h3>
        <a class="btn btn-sm btn-outline-primary" href="<?php echo esc_view($spl['url'], ENT_QUOTES, 'UTF-8'); ?>">Groups</a>
      </div>
      <p class="small text-muted mb-1"><?php echo esc_view($spl['from'] . ' → ' . $spl['to'], ENT_QUOTES, 'UTF-8'); ?></p>
      <?php $items = isset($spl['items']['items']) ? $spl['items']['items'] : array(); ?>
      <?php if (empty($items)): ?>
      <p class="mw-pulse-empty">No SPL groups.</p>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 mw-pulse-table">
          <thead>
            <tr>
              <th style="width:3rem;">#</th>
              <th>Group</th>
              <th class="text-end">Points</th>
              <th class="text-end">Members</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $row): ?>
            <tr>
              <td><?php echo (int) $row['rank']; ?></td>
              <td><a href="<?php echo esc_view($row['url'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($row['name'], ENT_QUOTES, 'UTF-8'); ?></a></td>
              <td class="text-end fw-semibold"><?php echo ($row['points'] >= 0 ? '+' : '') . number_format((float) $row['points'], 0); ?></td>
              <td class="text-end"><?php echo (int) $row['member_count']; ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php $render_more($spl['items']); ?>
      <?php endif; ?>
    </section>
    <?php endif; ?>

  </div>
</div>

<?php if (!$embed): ?>
<?php $this->load->view('partials/footer'); ?>
<?php endif; ?>
