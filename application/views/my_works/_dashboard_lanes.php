<?php
  $section_key = isset($section_key) ? $section_key : 'ad_hoc';
  $section_lanes = isset($dashboard_sections[$section_key]) ? $dashboard_sections[$section_key] : array();
  $lane_keys = my_works_dashboard_lane_keys();
  $lane_labels = my_works_dashboard_lane_labels();
  $can_view_all = !empty($can_view_all);
  $uid = (int) $this->session->userdata('user_id');
  $createUrl = site_url('my-works/create');
  if (!empty($can_add)) {
    $createUrl = site_url('my-works/quick-add') . '?redirect=' . rawurlencode('my-works?view=overview');
  }
?>

<div class="mw-dash-lanes" data-section="<?php echo htmlspecialchars($section_key, ENT_QUOTES, 'UTF-8'); ?>">
  <?php foreach ($lane_keys as $lane): ?>
    <?php
      $laneItems = isset($section_lanes[$lane]) ? $section_lanes[$lane] : array();
      $laneLabel = isset($lane_labels[$lane]) ? $lane_labels[$lane] : $lane;
    ?>
    <div class="mw-dash-lane mw-dash-lane-<?php echo htmlspecialchars($lane); ?>" data-lane="<?php echo htmlspecialchars($lane, ENT_QUOTES, 'UTF-8'); ?>" data-section="<?php echo htmlspecialchars($section_key, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="mw-dash-lane-head">
        <span class="mw-dash-lane-title"><?php echo htmlspecialchars($laneLabel); ?></span>
        <span class="mw-dash-lane-count"><?php echo count($laneItems); ?></span>
      </div>
      <div class="mw-dash-lane-table-wrap">
        <table class="mw-dash-lane-table">
          <thead>
            <tr>
              <th class="mw-dash-col-drag" aria-hidden="true"></th>
              <th>Task Title</th>
              <th>Project</th>
              <th>Assigned To</th>
            </tr>
          </thead>
          <tbody class="mw-dash-lane-body" data-lane="<?php echo htmlspecialchars($lane, ENT_QUOTES, 'UTF-8'); ?>" data-section="<?php echo htmlspecialchars($section_key, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (empty($laneItems)): ?>
              <tr class="mw-dash-lane-empty-row">
                <td colspan="4" class="mw-dash-lane-empty">No tasks</td>
              </tr>
            <?php else: ?>
              <?php foreach ($laneItems as $r): ?>
                <?php
                  $this->load->helper('my_works_status');
                  $stCode = isset($r->status) ? (string) $r->status : my_works_status_default_code();
                  $dot = my_works_dashboard_status_dot($r);
                  $dotColor = my_works_status_hex_color($stCode);
                  $forLabel = my_works_short_user_name($r->created_for_name, $r->created_for_email, $r->created_for);
                  $projLabel = my_works_dashboard_project_label($r);
                  $can_drag = my_works_can_update_status($r, $can_view_all, $uid);
                ?>
                <tr class="mw-dash-task-row<?php echo $can_drag ? ' mw-dash-task-row-draggable' : ''; ?>"
                    <?php if ($can_drag): ?>
                      draggable="true"
                      data-id="<?php echo (int) $r->id; ?>"
                      data-lane="<?php echo htmlspecialchars($lane, ENT_QUOTES, 'UTF-8'); ?>"
                      data-section="<?php echo htmlspecialchars($section_key, ENT_QUOTES, 'UTF-8'); ?>"
                    <?php endif; ?>>
                  <td class="mw-dash-col-drag">
                    <?php if ($can_drag): ?>
                      <span class="mw-dash-drag-handle" title="Drag to another column"><i class="bi bi-grip-vertical"></i></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <a href="<?php echo site_url('my-works/' . (int) $r->id); ?>" class="mw-dash-task-link">
                      <span class="mw-dash-status-dot mw-dash-dot-<?php echo htmlspecialchars($dot, ENT_QUOTES, 'UTF-8'); ?>" style="background-color:<?php echo htmlspecialchars($dotColor, ENT_QUOTES, 'UTF-8'); ?>;"></span>
                      <?php echo htmlspecialchars((string) $r->title, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                  </td>
                  <td><?php echo htmlspecialchars($projLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($forLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if (!empty($can_add)): ?>
        <a class="mw-dash-add-btn mw-dash-add-<?php echo htmlspecialchars($lane); ?>" href="<?php echo htmlspecialchars($createUrl, ENT_QUOTES, 'UTF-8'); ?>">
          <i class="bi bi-plus-lg"></i> Add Task
        </a>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
