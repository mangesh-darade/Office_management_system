<?php
  $section_key = isset($section_key) ? $section_key : 'ad_hoc';
  $section_lanes = isset($dashboard_sections[$section_key]) ? $dashboard_sections[$section_key] : array();
  $lane_keys = isset($lane_keys_filter) && is_array($lane_keys_filter) && !empty($lane_keys_filter)
    ? $lane_keys_filter
    : my_works_dashboard_lane_keys();
  $lane_labels = my_works_dashboard_lane_labels();
  $can_view_all = !empty($can_view_all);
  $disable_lane_drag = !empty($disable_lane_drag);
  $hide_drag_column = !empty($hide_drag_column) || ($disable_lane_drag && !empty($fullscreen_lane));
  $unified_table = !empty($unified_table) || !empty($fullscreen_lane);
  $uid = (int) $this->session->userdata('user_id');
  $lanes_class = 'mw-dash-lanes';
  if (!empty($single_lane_layout)) {
    $lanes_class .= ' mw-dash-lanes-single';
  }
  if (!empty($fullscreen_lane)) {
    $lanes_class .= ' mw-dash-lanes-fullscreen';
  }
?>

<div class="<?php echo esc_view($lanes_class); ?>" data-section="<?php echo esc_view($section_key); ?>">
  <?php foreach ($lane_keys as $lane): ?>
    <?php
      $laneItems = isset($section_lanes[$lane]) ? $section_lanes[$lane] : array();
      if (!empty($laneItems)) {
        $laneItems = my_works_dashboard_lane_sort_by_date_asc($laneItems);
      }
      $laneLabel = isset($lane_labels[$lane]) ? $lane_labels[$lane] : $lane;
      $show_date = !empty($force_show_date) || my_works_dashboard_lane_shows_date($lane);
      $col_count = ($hide_drag_column ? 0 : 1) + 3 + ($show_date ? 1 : 0);
    ?>
    <div class="mw-dash-lane mw-dash-lane-<?php echo esc_view($lane); ?><?php echo $show_date ? ' mw-dash-lane-has-date' : ' mw-dash-lane-no-date'; ?><?php echo $hide_drag_column ? ' mw-dash-lane-no-drag' : ''; ?><?php echo !empty($fullscreen_lane) ? ' mw-dash-lane-fullscreen' : ''; ?>" data-lane="<?php echo esc_view($lane); ?>" data-section="<?php echo esc_view($section_key); ?>">
      <div class="mw-dash-lane-head">
        <span class="mw-dash-lane-title"><?php echo esc_view($laneLabel); ?></span>
        <span class="mw-dash-lane-count"><?php echo count($laneItems); ?></span>
      </div>
      <div class="mw-dash-lane-table-wrap<?php echo $unified_table ? ' mw-dash-lane-table-wrap-unified' : ''; ?>">
        <?php if (!$unified_table): ?>
        <table class="mw-dash-lane-table mw-dash-lane-table-head">
          <colgroup>
            <?php if (!$hide_drag_column): ?><col class="mw-dash-col-drag"><?php endif; ?>
            <col class="mw-dash-col-project">
            <col class="mw-dash-col-title">
            <col class="mw-dash-col-assignee">
            <?php if ($show_date): ?><col class="mw-dash-col-date"><?php endif; ?>
          </colgroup>
          <thead>
            <tr>
              <?php if (!$hide_drag_column): ?><th class="mw-dash-col-drag" aria-hidden="true"></th><?php endif; ?>
              <th>Project</th>
              <th>Title</th>
              <th class="mw-dash-col-assignee-head">Assign<br>to</th>
              <?php if ($show_date): ?><th>Date</th><?php endif; ?>
            </tr>
          </thead>
        </table>
        <?php endif; ?>
        <div class="mw-dash-lane-body-scroll">
          <table class="mw-dash-lane-table mw-dash-lane-table-body">
            <colgroup>
              <?php if (!$hide_drag_column): ?><col class="mw-dash-col-drag"><?php endif; ?>
              <col class="mw-dash-col-project">
              <col class="mw-dash-col-title">
              <col class="mw-dash-col-assignee">
              <?php if ($show_date): ?><col class="mw-dash-col-date"><?php endif; ?>
            </colgroup>
            <?php if ($unified_table): ?>
            <thead>
              <tr>
                <?php if (!$hide_drag_column): ?><th class="mw-dash-col-drag" aria-hidden="true"></th><?php endif; ?>
                <th>Project</th>
                <th>Title</th>
                <th class="mw-dash-col-assignee-head">Assign<br>to</th>
                <?php if ($show_date): ?><th>Date</th><?php endif; ?>
              </tr>
            </thead>
            <?php endif; ?>
            <tbody class="mw-dash-lane-body" data-lane="<?php echo esc_view($lane); ?>" data-section="<?php echo esc_view($section_key); ?>" data-colspan="<?php echo (int) $col_count; ?>">
            <?php if (empty($laneItems)): ?>
              <tr class="mw-dash-lane-empty-row">
                <td colspan="<?php echo (int) $col_count; ?>" class="mw-dash-lane-empty">No tasks planned for today</td>
              </tr>
            <?php else: ?>
              <?php foreach ($laneItems as $r): ?>
                <?php
                  $this->load->helper('my_works_status');
                  $stCode = isset($r->status) ? (string) $r->status : my_works_status_default_code();
                  $dotColor = my_works_status_hex_color($stCode);
                  $rowBg = my_works_status_row_bg_color($stCode);
                  $dotClass = my_works_status_dashboard_dot_class($stCode);
                  $forLabel = my_works_short_user_name($r->created_for_name, $r->created_for_email, $r->created_for);
                  $projLabel = my_works_dashboard_project_label($r);
                  $dateLabel = $show_date ? my_works_dashboard_lane_date_label($r) : '';
                  $dateRaw = $show_date ? my_works_dashboard_lane_date_raw($r) : '';
                  $can_drag = !$disable_lane_drag && my_works_can_update_status($r, $can_view_all, $uid);
                ?>
                <tr class="mw-dash-task-row mw-dash-status-<?php echo esc_view($dotClass); ?><?php echo $can_drag ? ' mw-dash-task-row-draggable' : ''; ?>"
                    style="background-color:<?php echo esc_view($rowBg, ENT_QUOTES, 'UTF-8'); ?>;--mw-status-color:<?php echo esc_view($dotColor, ENT_QUOTES, 'UTF-8'); ?>;"
                    <?php if ($can_drag): ?>
                      data-id="<?php echo (int) $r->id; ?>"
                      data-lane="<?php echo esc_view($lane); ?>"
                      data-section="<?php echo esc_view($section_key); ?>"
                    <?php endif; ?>>
                  <?php if (!$hide_drag_column): ?>
                  <td class="mw-dash-col-drag">
                    <?php if ($can_drag): ?>
                      <span class="mw-dash-drag-handle" draggable="true" title="Drag to another column"><i class="bi bi-grip-vertical"></i></span>
                    <?php endif; ?>
                  </td>
                  <?php endif; ?>
                  <td title="<?php echo esc_view($projLabel, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($projLabel); ?></td>
                  <td>
                    <a href="<?php echo site_url('my-works/' . (int) $r->id); ?>" class="mw-dash-task-link" title="<?php echo esc_view((string) $r->title, ENT_QUOTES, 'UTF-8'); ?>">
                      <?php echo esc_view((string) $r->title, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                  </td>
                  <td title="<?php echo esc_view($forLabel, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($forLabel); ?></td>
                  <?php if ($show_date): ?>
                    <td class="mw-dash-col-date-cell" title="<?php echo esc_view($dateRaw !== '' ? $dateRaw : 'No due date', ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($dateLabel); ?></td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
