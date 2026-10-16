<?php
  $section_key = isset($section_key) ? (string) $section_key : 'ad_hoc';
  $section_lanes = isset($dashboard_sections[$section_key]) ? $dashboard_sections[$section_key] : array();
  $can_add = !empty($can_add);
  $this->load->helper('my_works_status');
  if (!function_exists('estimate_hours_row')) {
      $this->load->helper('estimate_hours');
  }

  $user_name_map = array();
  foreach ((array) $users as $u) {
      if (!is_object($u) || !isset($u->id)) {
          continue;
      }
      $user_name_map[(int) $u->id] = my_works_user_label(
          isset($u->name) ? $u->name : '',
          isset($u->email) ? $u->email : '',
          $u->id
      );
  }

  $mw_status_options = my_works_status_records();
  $task_status_options = array();
  foreach (my_works_overview_task_status_codes() as $task_code) {
      $task_status_options[] = (object) array(
          'code'  => $task_code,
          'name'  => my_works_dashboard_task_status_label($task_code),
          'color' => my_works_dashboard_task_status_hex($task_code),
      );
  }

  $grid_rows = array();
  $seen_rows = array();
  foreach ((array) $section_lanes as $lane_items) {
      if (!is_array($lane_items)) {
          continue;
      }
      foreach ($lane_items as $lane_row) {
          if (!is_object($lane_row) || my_works_row_is_finished($lane_row)) {
              continue;
          }
          $row_source = (!empty($lane_row->item_source) && $lane_row->item_source === 'tasks') ? 'tasks' : 'my_works';
          $row_key = $row_source . ':' . (int) $lane_row->id;
          if (isset($seen_rows[$row_key])) {
              continue;
          }
          $seen_rows[$row_key] = true;
          $grid_rows[] = $lane_row;
      }
  }

  $total_est = 0;
  foreach ($grid_rows as $est_row) {
      if (isset($est_row->estimate_hours) && $est_row->estimate_hours !== '' && $est_row->estimate_hours !== null) {
          $total_est += (float) $est_row->estimate_hours;
      }
  }

  $table_id = 'mwOverviewGrid' . ucfirst($section_key);
?>
<div class="mw-dash-grid">
  <div class="bg-white border shadow-sm mw-overview-grid-wrap">
    <div class="mw-overview-grid-stats">
      <div class="mw-overview-grid-stat">
        <span class="mw-overview-grid-stat-label">Items</span>
        <span class="mw-overview-grid-stat-value"><?php echo count($grid_rows); ?></span>
      </div>
      <div class="mw-overview-grid-stat">
        <span class="mw-overview-grid-stat-label">Total Estimated</span>
        <span class="mw-overview-grid-stat-value mw-overview-grid-stat-est"><?php echo number_format($total_est, 1); ?> <span>hrs</span></span>
      </div>
      <?php if ($can_add): ?>
      <button type="button" class="btn btn-primary btn-sm mw-overview-grid-add" data-section="<?php echo esc_view($section_key, ENT_QUOTES, 'UTF-8'); ?>" title="Add row" aria-label="Add row"><i class="bi bi-plus-lg"></i></button>
      <?php endif; ?>
    </div>
    <div class="mw-overview-grid-table-host">
      <table id="<?php echo esc_view($table_id); ?>" class="table table-hover table-striped align-middle mb-0 mw-overview-grid-table">
        <thead>
          <tr>
            <th>Client</th>
            <th>Project</th>
            <th>Task</th>
            <th>Created By</th>
            <th>Date</th>
            <th>Created At</th>
            <th class="text-end">Est Hrs</th>
            <th class="mw-overview-grid-status-col">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($grid_rows as $r): ?>
            <?php
              $is_task_row = !empty($r->item_source) && $r->item_source === 'tasks';
              $st_code = isset($r->status) ? (string) $r->status : ($is_task_row ? 'pending' : my_works_status_default_code());
              if ($is_task_row) {
                  $st_color = my_works_dashboard_task_status_hex($st_code);
                  $st_label = my_works_dashboard_task_status_label($st_code);
                  $status_options = $task_status_options;
                  $item_type = 'project_task';
                  $item_source = 'tasks';
              } else {
                  $st_color = my_works_status_hex_color($st_code);
                  $st_label = $st_code;
                  foreach ($mw_status_options as $st_row) {
                      if ((string) $st_row->code === $st_code) {
                          $st_label = (string) $st_row->name;
                          break;
                      }
                  }
                  $status_options = $mw_status_options;
                  $item_type = 'my_work';
                  $item_source = 'my_works';
              }
              $client_label = !empty($r->client_name) ? trim((string) $r->client_name) : '';
              $project_label = !empty($r->project_name) ? trim((string) $r->project_name) : '';
              if ($is_task_row) {
                  $creator_id = isset($r->created_by) ? (int) $r->created_by : 0;
                  $creator_label = ($creator_id > 0 && isset($user_name_map[$creator_id])) ? $user_name_map[$creator_id] : '';
              } else {
                  $creator_label = my_works_user_label(
                      isset($r->created_by_name) ? $r->created_by_name : '',
                      isset($r->created_by_email) ? $r->created_by_email : '',
                      isset($r->created_by) ? $r->created_by : 0
                  );
              }
              $due_label = '—';
              if (!empty($r->due_date) && $r->due_date !== '0000-00-00') {
                  $due_ts = strtotime((string) $r->due_date);
                  if ($due_ts) {
                      $due_label = date('d M', $due_ts);
                  }
              }
              $created_label = '—';
              if (!empty($r->created_at)) {
                  $created_ts = strtotime((string) $r->created_at);
                  if ($created_ts) {
                      $created_label = date('d M Y', $created_ts);
                  }
              }
              $est_label = estimate_hours_row(isset($r->estimate_hours) ? $r->estimate_hours : null);
              $item_url = my_works_dashboard_item_url($r);
              $has_status_match = false;
              foreach ($status_options as $opt) {
                  if ((string) $opt->code === $st_code) {
                      $has_status_match = true;
                      break;
                  }
              }
            ?>
            <tr style="--pd-row-status-color:<?php echo esc_view($st_color, ENT_QUOTES, 'UTF-8'); ?>;background:<?php echo esc_view($st_color, ENT_QUOTES, 'UTF-8'); ?>14;">
              <td class="text-muted"><?php echo esc_view($client_label !== '' ? $client_label : '—'); ?></td>
              <td class="text-muted"><?php echo esc_view($project_label !== '' ? $project_label : '—'); ?></td>
              <td>
                <a href="<?php echo esc_view($item_url, ENT_QUOTES, 'UTF-8'); ?>" class="project-dash-task-title" title="<?php echo esc_view((string) $r->title, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view((string) $r->title); ?></a>
              </td>
              <td class="text-muted text-nowrap"><?php echo esc_view($creator_label !== '' ? $creator_label : '—'); ?></td>
              <td class="text-nowrap"><span class="project-dash-date"><?php echo esc_view($due_label); ?></span></td>
              <td class="text-muted text-nowrap"><?php echo esc_view($created_label); ?></td>
              <td class="text-end text-nowrap project-dash-est"><?php echo esc_view($est_label); ?></td>
              <td>
                <select class="form-select form-select-sm project-dash-status-select"
                        data-item-id="<?php echo (int) $r->id; ?>"
                        data-item-type="<?php echo esc_view($item_type, ENT_QUOTES, 'UTF-8'); ?>"
                        data-item-source="<?php echo esc_view($item_source, ENT_QUOTES, 'UTF-8'); ?>"
                        data-prev-status="<?php echo esc_view($st_code, ENT_QUOTES, 'UTF-8'); ?>"
                        data-estimate-hours="<?php echo esc_view(isset($r->estimate_hours) && $r->estimate_hours !== null && $r->estimate_hours !== '' ? (string) $r->estimate_hours : '', ENT_QUOTES, 'UTF-8'); ?>"
                        style="color:<?php echo esc_view($st_color, ENT_QUOTES, 'UTF-8'); ?>;background-color:<?php echo esc_view($st_color, ENT_QUOTES, 'UTF-8'); ?>1a;border-color:<?php echo esc_view($st_color, ENT_QUOTES, 'UTF-8'); ?>40;"
                        aria-label="Status">
                  <?php if (!$has_status_match): ?>
                    <option value="<?php echo esc_view($st_code, ENT_QUOTES, 'UTF-8'); ?>" selected data-color="<?php echo esc_view($st_color, ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($st_label); ?></option>
                  <?php endif; ?>
                  <?php foreach ($status_options as $opt): ?>
                    <option value="<?php echo esc_view((string) $opt->code, ENT_QUOTES, 'UTF-8'); ?>"
                            data-color="<?php echo esc_view((string) $opt->color, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo (string) $opt->code === $st_code ? 'selected' : ''; ?>><?php echo esc_view((string) $opt->name); ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
