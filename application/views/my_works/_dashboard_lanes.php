<?php

  $section_key = isset($section_key) ? $section_key : 'ad_hoc';

  $section_lanes = isset($dashboard_sections[$section_key]) ? $dashboard_sections[$section_key] : array();

  $lane_keys = isset($lane_keys_filter) && is_array($lane_keys_filter) && !empty($lane_keys_filter)

    ? $lane_keys_filter

    : my_works_dashboard_lane_keys();

  $lane_labels = my_works_dashboard_lane_labels();

  $lane_focus_pages = my_works_dashboard_lane_focus_pages();

  $can_view_all = !empty($can_view_all);

  $disable_lane_drag = !empty($disable_lane_drag);

  $hide_drag_column = !empty($hide_drag_column) || ($disable_lane_drag && !empty($fullscreen_lane));

  $unified_table = !empty($unified_table) || !empty($fullscreen_lane);

  $hide_project_column = !isset($hide_project_column) || !empty($hide_project_column);

  $group_by_project = isset($group_by_project)

    ? !empty($group_by_project)

    : in_array($section_key, array('project', 'focus'), true);

  $hide_empty_lanes = !empty($hide_empty_lanes);

  $show_status_column = !empty($show_status_column);

  $uid = (int) $this->session->userdata('user_id');

  $statusLabels = array();

  $statusColors = array();

  if ($show_status_column) {

    $this->load->helper('my_works_status');

    $statusLabels = my_works_status_labels();

    $statusColors = my_works_status_colors();

  }

  $status_redirect = $show_status_column

    ? (current_url() . (function_exists('safe_query_suffix') ? safe_query_suffix() : ''))

    : '';

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

      if (in_array((string) $lane, array('future_pipeline', 'back_log', 'yesterday', 'todays_plan'), true) && !empty($laneItems)) {

        $this->load->helper('my_works_status');

        $open_lane_items = array();

        foreach ($laneItems as $lane_row) {

          if (!my_works_row_is_finished($lane_row)) {

            $open_lane_items[] = $lane_row;

          }

        }

        $laneItems = $open_lane_items;

      }

      if ($hide_empty_lanes && empty($laneItems)) {

        continue;

      }

      if (!empty($laneItems)) {

        $laneItems = my_works_dashboard_lane_sort_by_date_asc($laneItems);

      }

      $laneLabel = isset($lane_labels[$lane]) ? $lane_labels[$lane] : $lane;

      $laneFocusUrl = '';

      if (empty($fullscreen_lane) && isset($lane_focus_pages[$lane]['route'])) {

        $laneFocusUrl = site_url($lane_focus_pages[$lane]['route']);

        // Keep full-view count aligned with this overview cart (Ad hoc vs Project).

        if (in_array((string) $section_key, array('ad_hoc', 'project'), true)) {

          $laneFocusUrl .= (strpos($laneFocusUrl, '?') === false ? '?' : '&') . 'section=' . rawurlencode((string) $section_key);

        }

      }

      $show_date = !empty($force_show_date) || my_works_dashboard_lane_shows_date($lane);

      $col_count = ($hide_drag_column ? 0 : 1) + 3 + ($show_date ? 1 : 0) + ($hide_project_column ? 0 : 1) + ($show_status_column ? 1 : 0);

    ?>

    <div class="mw-dash-lane mw-dash-lane-<?php echo esc_view($lane); ?><?php echo $show_date ? ' mw-dash-lane-has-date' : ' mw-dash-lane-no-date'; ?><?php echo $hide_drag_column ? ' mw-dash-lane-no-drag' : ''; ?><?php echo !empty($fullscreen_lane) ? ' mw-dash-lane-fullscreen' : ''; ?><?php echo $hide_project_column ? ' mw-dash-lane-no-project-col' : ''; ?><?php echo $show_status_column ? ' mw-dash-lane-has-status' : ''; ?>" data-lane="<?php echo esc_view($lane); ?>" data-section="<?php echo esc_view($section_key); ?>">

      <div class="mw-dash-lane-head">

        <?php if ($laneFocusUrl !== ''): ?>

          <a href="<?php echo esc_view($laneFocusUrl, ENT_QUOTES, 'UTF-8'); ?>" class="mw-dash-lane-title mw-dash-lane-title-link" title="Open full view"><?php echo esc_view($laneLabel); ?></a>

        <?php else: ?>

          <span class="mw-dash-lane-title"><?php echo esc_view($laneLabel); ?></span>

        <?php endif; ?>

        <span class="mw-dash-lane-count"><?php echo count($laneItems); ?></span>

      </div>

      <div class="mw-dash-lane-table-wrap<?php echo $unified_table ? ' mw-dash-lane-table-wrap-unified' : ''; ?>">

        <?php if (!$unified_table): ?>

        <table class="mw-dash-lane-table mw-dash-lane-table-head">

          <colgroup>

            <?php if (!$hide_drag_column): ?><col class="mw-dash-col-drag"><?php endif; ?>

            <?php if (!$hide_project_column): ?><col class="mw-dash-col-project"><?php endif; ?>

            <col class="mw-dash-col-title">

            <col class="mw-dash-col-assignee">

            <col class="mw-dash-col-est">

            <?php if ($show_date): ?><col class="mw-dash-col-date"><?php endif; ?>

            <?php if ($show_status_column): ?><col class="mw-dash-col-status"><?php endif; ?>

          </colgroup>

          <thead>

            <tr>

              <?php if (!$hide_drag_column): ?><th class="mw-dash-col-drag" aria-hidden="true"></th><?php endif; ?>

              <?php if (!$hide_project_column): ?><th>Project</th><?php endif; ?>

              <th>Title</th>

              <th class="mw-dash-col-assignee-head">Assign<br>to</th>

              <th class="mw-dash-col-est-head text-end">Est.hr</th>

              <?php if ($show_date): ?><th>Date</th><?php endif; ?>

              <?php if ($show_status_column): ?><th>Status</th><?php endif; ?>

            </tr>

          </thead>

        </table>

        <?php endif; ?>

        <div class="mw-dash-lane-body-scroll">

          <table class="mw-dash-lane-table mw-dash-lane-table-body">

            <colgroup>

              <?php if (!$hide_drag_column): ?><col class="mw-dash-col-drag"><?php endif; ?>

              <?php if (!$hide_project_column): ?><col class="mw-dash-col-project"><?php endif; ?>

              <col class="mw-dash-col-title">

              <col class="mw-dash-col-assignee">

              <col class="mw-dash-col-est">

              <?php if ($show_date): ?><col class="mw-dash-col-date"><?php endif; ?>

              <?php if ($show_status_column): ?><col class="mw-dash-col-status"><?php endif; ?>

            </colgroup>

            <?php if ($unified_table): ?>

            <thead>

              <tr>

                <?php if (!$hide_drag_column): ?><th class="mw-dash-col-drag" aria-hidden="true"></th><?php endif; ?>

                <?php if (!$hide_project_column): ?><th>Project</th><?php endif; ?>

                <th>Title</th>

                <th class="mw-dash-col-assignee-head">Assign<br>to</th>

                <th class="mw-dash-col-est-head text-end">Est.hr</th>

                <?php if ($show_date): ?><th>Date</th><?php endif; ?>

                <?php if ($show_status_column): ?><th>Status</th><?php endif; ?>

              </tr>

            </thead>

            <?php endif; ?>

            <tbody class="mw-dash-lane-body" data-lane="<?php echo esc_view($lane); ?>" data-section="<?php echo esc_view($section_key); ?>" data-colspan="<?php echo (int) $col_count; ?>">

            <?php if (empty($laneItems)): ?>

              <tr class="mw-dash-lane-empty-row">

                <td colspan="<?php echo (int) $col_count; ?>" class="mw-dash-lane-empty">No tasks planned for today</td>

              </tr>

            <?php else: ?>

              <?php

                $lane_groups = $group_by_project

                  ? my_works_dashboard_group_lane_by_project($laneItems)

                  : array(array('label' => '', 'project_id' => 0, 'items' => $laneItems));

              ?>

              <?php foreach ($lane_groups as $lane_group): ?>

                <?php

                  $show_project_header = $group_by_project

                    && $hide_project_column

                    && (int) $lane_group['project_id'] > 0

                    && (string) $lane_group['label'] !== '';

                ?>

                <?php if ($show_project_header): ?>

                  <tr class="mw-dash-project-group-row">

                    <td colspan="<?php echo (int) $col_count; ?>" class="mw-dash-project-group-cell">

                      <i class="bi bi-folder2-open me-1"></i>

                      <?php if (function_exists('has_module_access') && has_module_access('projects')): ?>

                        <a href="<?php echo site_url('projects/' . (int) $lane_group['project_id']); ?>" class="mw-dash-project-group-link"><?php echo esc_view((string) $lane_group['label'], ENT_QUOTES, 'UTF-8'); ?></a>

                      <?php else: ?>

                        <?php echo esc_view((string) $lane_group['label'], ENT_QUOTES, 'UTF-8'); ?>

                      <?php endif; ?>

                    </td>

                  </tr>

                <?php endif; ?>

                <?php foreach ($lane_group['items'] as $r): ?>

                  <?php

                    $this->load->helper('my_works_status');

                    $is_task_row = !empty($r->item_source) && $r->item_source === 'tasks';

                    $stCode = isset($r->status) ? (string) $r->status : ($is_task_row ? 'pending' : my_works_status_default_code());

                    if ($is_task_row) {
                      $dotColor = my_works_dashboard_task_status_hex($stCode);
                      $CI =& get_instance();
                      $CI->load->helper('status_row');
                      $rowBg = status_row_bg_from_hex($dotColor, 0.12);
                      $dotClass = ($stCode === 'in_progress') ? 'in_progress' : 'new';
                      $stLabel = my_works_dashboard_task_status_label($stCode);
                      $stColor = my_works_dashboard_task_status_bootstrap($stCode);
                    } else {
                      $dotColor = my_works_status_hex_color($stCode);
                      $rowBg = my_works_status_row_bg_color($stCode);
                      $dotClass = my_works_status_dashboard_dot_class($stCode);
                      $stLabel = isset($statusLabels[$stCode]) ? $statusLabels[$stCode] : $stCode;
                      $stColor = isset($statusColors[$stCode]) ? $statusColors[$stCode] : 'secondary';
                    }

                    $forLabel = my_works_short_user_name(
                      isset($r->created_for_name) ? $r->created_for_name : '',
                      isset($r->created_for_email) ? $r->created_for_email : '',
                      isset($r->created_for) ? $r->created_for : 0
                    );

                    $projLabel = my_works_dashboard_project_label($r);

                    $dateLabel = $show_date ? my_works_dashboard_lane_date_label($r) : '';

                    $dateRaw = $show_date ? my_works_dashboard_lane_date_raw($r) : '';

                    $can_drag = !$disable_lane_drag && my_works_can_update_status($r, $can_view_all, $uid);

                    $can_status = $show_status_column && my_works_can_update_status($r, $can_view_all, $uid);

                    $item_url = my_works_dashboard_item_url($r);

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

                    <?php if (!$hide_project_column): ?>

                    <td title="<?php echo esc_view($projLabel, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($projLabel); ?></td>

                    <?php endif; ?>

                    <td>

                      <a href="<?php echo esc_view($item_url, ENT_QUOTES, 'UTF-8'); ?>" class="mw-dash-task-link" title="<?php echo esc_view((string) $r->title, ENT_QUOTES, 'UTF-8'); ?>">

                        <?php echo esc_view((string) $r->title, ENT_QUOTES, 'UTF-8'); ?>

                        <?php if ($is_task_row): ?>
                          <span class="text-muted small ms-1" title="Task">· Task</span>
                        <?php endif; ?>

                      </a>

                    </td>

                    <td title="<?php echo esc_view($forLabel, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($forLabel); ?></td>

                    <td class="mw-dash-col-est-cell text-end text-nowrap" title="Estimate (hrs)">
                      <?php
                        if (!function_exists('estimate_hours_row')) {
                          $this->load->helper('estimate_hours');
                        }
                        echo esc_view(estimate_hours_row(isset($r->estimate_hours) ? $r->estimate_hours : null));
                      ?>
                    </td>

                    <?php if ($show_date): ?>

                      <td class="mw-dash-col-date-cell" title="<?php echo esc_view($dateRaw !== '' ? $dateRaw : 'No due date', ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($dateLabel); ?></td>

                    <?php endif; ?>

                    <?php if ($show_status_column): ?>

                      <td class="mw-dash-col-status-cell">

                        <?php if ($can_status): ?>

                          <form method="post" action="<?php echo site_url('my-works/update-status'); ?>" class="d-inline mw-quick-status mw-dash-status-form">

                            <?php $this->load->view('my_works/_csrf'); ?>

                            <input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">

                            <input type="hidden" name="redirect" value="<?php echo esc_view($status_redirect, ENT_QUOTES, 'UTF-8'); ?>">

                            <input type="hidden" name="status" value="<?php echo esc_view($stCode, ENT_QUOTES, 'UTF-8'); ?>" class="mw-dash-status-value">

                            <div class="dropdown mw-dash-status-dd">

                              <button type="button"

                                      class="btn btn-sm mw-dash-status-toggle dropdown-toggle"

                                      data-bs-toggle="dropdown"

                                      data-bs-popper-config='{"strategy":"fixed"}'

                                      aria-expanded="false"

                                      aria-label="Status"

                                      data-row-id="<?php echo (int) $r->id; ?>"

                                      data-prev-status="<?php echo esc_view($stCode, ENT_QUOTES, 'UTF-8'); ?>"

                                      style="color:<?php echo esc_view($dotColor, ENT_QUOTES, 'UTF-8'); ?>;border-color:<?php echo esc_view($dotColor, ENT_QUOTES, 'UTF-8'); ?>66;">

                                <span class="mw-dash-status-label"><?php echo esc_view($stLabel); ?></span>

                              </button>

                              <ul class="dropdown-menu mw-dash-status-menu shadow-sm">

                                <?php foreach ($statusLabels as $k => $lbl): ?>

                                  <?php

                                    $optColor = my_works_status_hex_color($k);

                                    $probe = clone $r;

                                    $probe->status = $k;

                                    if (my_works_status_is_closed($k)) {

                                      $probe->closed_at = date('Y-m-d H:i:s');

                                    } else {

                                      $probe->closed_at = null;

                                    }

                                    $next_lane = my_works_dashboard_lane_for_row($probe);

                                    $optLeaves = ($next_lane === null || (string) $next_lane !== (string) $lane);

                                  ?>

                                  <li>

                                    <button type="button"

                                            class="dropdown-item mw-dash-status-option<?php echo $stCode === $k ? ' active' : ''; ?>"

                                            data-status="<?php echo esc_view($k, ENT_QUOTES, 'UTF-8'); ?>"

                                            data-color="<?php echo esc_view($optColor, ENT_QUOTES, 'UTF-8'); ?>"

                                            data-leaves-lane="<?php echo $optLeaves ? '1' : '0'; ?>"

                                            style="color:<?php echo esc_view($optColor, ENT_QUOTES, 'UTF-8'); ?>;">

                                      <span class="mw-dash-status-dot-inline" style="background:<?php echo esc_view($optColor, ENT_QUOTES, 'UTF-8'); ?>;"></span>

                                      <?php echo esc_view($lbl); ?>

                                    </button>

                                  </li>

                                <?php endforeach; ?>

                              </ul>

                            </div>

                          </form>

                        <?php else: ?>

                          <span class="badge bg-<?php echo esc_view($stColor, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($stLabel); ?></span>

                        <?php endif; ?>

                      </td>

                    <?php endif; ?>

                  </tr>

                <?php endforeach; ?>

              <?php endforeach; ?>

            <?php endif; ?>

            </tbody>

          </table>

        </div>

      </div>

    </div>

  <?php endforeach; ?>

</div>

