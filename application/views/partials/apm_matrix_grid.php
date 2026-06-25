<?php
defined('BASEPATH') OR exit('No direct script access allowed');
if (!function_exists('apm_quadrants')) {
    $CI =& get_instance();
    $CI->load->helper('action_priority_matrix');
}
/**
 * @var string $apm_title
 * @var string $apm_date
 * @var array  $matrix_columns
 * @var string $apm_context projects|my_works
 * @var bool   $apm_draggable
 * @var array  $apm_extra
 */
$quadrants = apm_quadrants();
$gridOrder = apm_grid_order();
$matrixCols = isset($matrix_columns) ? $matrix_columns : array();
$apm_context = isset($apm_context) ? $apm_context : 'projects';
$apm_draggable = !empty($apm_draggable);
$apm_extra = isset($apm_extra) ? $apm_extra : array();
$apm_title = isset($apm_title) ? $apm_title : 'Action Priority Matrix';
$apm_date = isset($apm_date) ? $apm_date : date('M j, Y');
$cellMap = array(
  'quick_wins' => 'apm-q-quick_wins',
  'major_projects' => 'apm-q-major_projects',
  'fill_ins' => 'apm-q-fill_ins',
  'hard_slogs' => 'apm-q-hard_slogs',
);
?>
<div class="apm-sheet mb-3">
  <div class="apm-title-bar">
    <?php echo esc_view($apm_title); ?> &mdash; <?php echo esc_view($apm_date); ?>
  </div>
  <div class="apm-table">
    <div class="apm-corner" aria-hidden="true"></div>
    <div class="apm-axis-x apm-axis-x-low" aria-hidden="true">Low Effort</div>
    <div class="apm-axis-x apm-axis-x-high" aria-hidden="true">High Effort</div>
    <div class="apm-axis-y apm-axis-y-high" aria-hidden="true">High Impact</div>
    <div class="apm-axis-y apm-axis-y-low" aria-hidden="true">Low Impact</div>

    <?php foreach ($gridOrder as $qKey): ?>
      <?php
        $qDef = isset($quadrants[$qKey]) ? $quadrants[$qKey] : array('label' => $qKey, 'tone' => 'yellow-soft');
        $items = isset($matrixCols[$qKey]) ? $matrixCols[$qKey] : array();
        $tone = isset($qDef['tone']) ? $qDef['tone'] : 'yellow-soft';
        $cellClass = isset($cellMap[$qKey]) ? $cellMap[$qKey] : '';
      ?>
      <div class="apm-quadrant <?php echo esc_view($cellClass); ?> apm-tone-<?php echo esc_view($tone); ?>">
        <div class="apm-quadrant-head">
          <span><?php echo esc_view($qDef['label']); ?></span>
          <span class="apm-count apm-matrix-count" data-quadrant="<?php echo esc_view($qKey); ?>"><?php echo count($items); ?></span>
        </div>
        <div class="apm-quadrant-body" data-quadrant="<?php echo esc_view($qKey); ?>">
          <div class="apm-empty apm-matrix-empty"<?php echo empty($items) ? '' : ' style="display:none"'; ?>>
            <?php echo $apm_draggable ? 'Drop items here' : 'No items'; ?>
          </div>
          <?php foreach ($items as $item): ?>
            <?php if ($apm_context === 'projects'): ?>
              <?php
                $p = $item;
                $st = isset($p->status) ? (string) $p->status : '';
                $status_map = isset($apm_extra['status_map']) ? $apm_extra['status_map'] : array();
                $stLabel = function_exists('project_matrix_status_label') ? project_matrix_status_label($st, $status_map) : $st;
              ?>
              <a class="apm-item" href="<?php echo site_url('projects/' . (int) $p->id); ?>">
                <div class="apm-item-title"><?php echo esc_view($p->name); ?></div>
                <div class="apm-item-meta"><?php echo esc_view($stLabel); ?><?php if (!empty($p->code)): ?> &middot; <?php echo esc_view($p->code); ?><?php endif; ?></div>
              </a>
            <?php else: ?>
              <?php
                $r = $item;
                $statusLabels = isset($apm_extra['status_labels']) ? $apm_extra['status_labels'] : array();
                $st = isset($r->status) ? (string) $r->status : 'new';
                $stLabel = isset($statusLabels[$st]) ? $statusLabels[$st] : $st;
                $projectId = isset($r->project_id) ? (int) $r->project_id : 0;
                $projectName = isset($r->project_name) ? trim((string) $r->project_name) : '';
                $can_view_projects = !empty($apm_extra['can_view_projects']);
              ?>
              <div class="apm-item apm-item-draggable-wrap"<?php echo $apm_draggable ? ' draggable="true" data-id="' . (int) $r->id . '" data-quadrant="' . esc_view($qKey) . '"' : ''; ?>>
                <a class="apm-item-title" href="<?php echo site_url('my-works/' . (int) $r->id); ?>"><?php echo esc_view($r->title); ?></a>
                <?php if ($projectName !== ''): ?>
                  <div class="apm-item-meta">
                    <?php if ($can_view_projects && $projectId > 0): ?>
                      <a class="apm-item-link" href="<?php echo site_url('projects/' . $projectId); ?>"><?php echo esc_view($projectName); ?></a>
                    <?php else: ?>
                      <?php echo esc_view($projectName); ?>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
                <div class="apm-item-meta"><?php echo esc_view($stLabel); ?></div>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
