<?php
$activities = isset($activities) ? $activities : array();
$table_id = isset($table_id) ? (string) $table_id : 'splActivityTable';
$show_member = !empty($show_member);
$compact = !empty($compact);
$page_length = isset($page_length) ? (int) $page_length : 25;
$empty_message = isset($empty_message) ? (string) $empty_message : 'No activities found.';
$table_class = 'table table-sm table-hover align-middle spl-activity-table mb-0';
if ($compact) {
    $table_class .= ' spl-activity-table--compact';
}
?>
<?php if (empty($activities)): ?>
<div class="spl-empty-state">
  <i class="bi bi-inbox"></i>
  <p><?php echo esc_view($empty_message, ENT_QUOTES, 'UTF-8'); ?></p>
</div>
<?php else: ?>
<div class="table-responsive spl-activity-table-wrap">
  <table
    id="<?php echo esc_view($table_id, ENT_QUOTES, 'UTF-8'); ?>"
    class="<?php echo esc_view($table_class, ENT_QUOTES, 'UTF-8'); ?>"
    data-page-length="<?php echo (int) $page_length; ?>"
  >
    <thead>
      <tr>
        <th>Date</th>
        <?php if ($show_member): ?>
        <th>Member</th>
        <?php endif; ?>
        <th>Activity</th>
        <?php if (!$compact): ?>
        <th>Category</th>
        <th>Source</th>
        <?php endif; ?>
        <th class="text-end">Points</th>
        <th>Status</th>
        <?php if (!$compact): ?>
        <th>Notes</th>
        <th class="text-center">File</th>
        <?php endif; ?>
        <th class="text-end">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($activities as $t):
        $statusMeta = spl_activity_status_meta($t->status);
        $points = (float) $t->points;
        $notePreview = spl_activity_note_preview($t->reference_label, 80);
        $pointsMuted = in_array((string) $t->status, array('pending', 'rejected'), true) && $points >= 0;
        $hasEvidence = !empty($t->evidence_id) && !empty($t->evidence_file);
        $memberName = spl_activity_table_member_name($t);
        $dateSort = !empty($t->created_at) ? strtotime((string) $t->created_at) : 0;
        $rowClass = '';
        if ($points < 0) {
            $rowClass = 'is-deducted';
        } elseif ((string) $t->status === 'pending') {
            $rowClass = 'is-pending';
        } elseif ((string) $t->status === 'rejected') {
            $rowClass = 'is-rejected';
        }
      ?>
      <tr class="spl-activity-row is-clickable<?php echo $rowClass !== '' ? ' ' . esc_view($rowClass, ENT_QUOTES, 'UTF-8') : ''; ?>" data-activity-id="<?php echo (int) $t->id; ?>" tabindex="0">
        <td data-order="<?php echo (int) $dateSort; ?>">
          <span class="spl-activity-table-date"><?php echo esc_view(spl_format_activity_datetime($t->created_at), ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <?php if ($show_member): ?>
        <td><?php echo esc_view($memberName !== '' ? $memberName : '—', ENT_QUOTES, 'UTF-8'); ?></td>
        <?php endif; ?>
        <td>
          <span class="spl-activity-table-title">
            <i class="bi <?php echo esc_view(spl_activity_icon_class($t), ENT_QUOTES, 'UTF-8'); ?> me-1 text-muted"></i>
            <?php echo esc_view(spl_activity_title($t), ENT_QUOTES, 'UTF-8'); ?>
          </span>
        </td>
        <?php if (!$compact): ?>
        <td><?php echo esc_view(!empty($t->category_name) ? $t->category_name : '—', ENT_QUOTES, 'UTF-8'); ?></td>
        <td><?php echo esc_view(spl_activity_source_label($t), ENT_QUOTES, 'UTF-8'); ?></td>
        <?php endif; ?>
        <td class="text-end" data-order="<?php echo esc_view(number_format($points, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
          <span class="spl-activity-table-points <?php echo $points >= 0 ? 'is-positive' : 'is-negative'; ?><?php echo $pointsMuted ? ' is-muted' : ''; ?>">
            <?php echo ($points >= 0 ? '+' : '') . number_format($points, 0); ?>
          </span>
        </td>
        <td data-order="<?php echo esc_view((string) $t->status, ENT_QUOTES, 'UTF-8'); ?>">
          <span class="badge rounded-pill text-bg-<?php echo esc_view($statusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <?php if (!$compact): ?>
        <td class="spl-activity-table-note" title="<?php echo esc_view($notePreview, ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo esc_view($notePreview !== '' ? $notePreview : '—', ENT_QUOTES, 'UTF-8'); ?>
        </td>
        <td class="text-center" data-order="<?php echo $hasEvidence ? '1' : '0'; ?>">
          <?php if ($hasEvidence): ?>
          <span class="spl-activity-table-file" title="<?php echo esc_view($t->evidence_name ?: 'Attachment', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-paperclip"></i></span>
          <?php else: ?>
          <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <?php endif; ?>
        <td class="text-end">
          <button type="button" class="btn btn-sm btn-outline-primary spl-activity-view-btn" data-activity-id="<?php echo (int) $t->id; ?>">
            <i class="bi bi-eye"></i>
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
