<?php
$rows = isset($rows) ? $rows : array();
$approval_view = isset($approval_view) ? (string) $approval_view : 'pending';
$table_id = isset($table_id) ? (string) $table_id : 'splApprovalTable';
$page_length = isset($page_length) ? (int) $page_length : 25;
$empty_message = isset($empty_message) ? (string) $empty_message : 'No submissions found.';
$csrf_name = isset($csrf_name) ? (string) $csrf_name : '';
$csrf_hash = isset($csrf_hash) ? (string) $csrf_hash : '';
$compact = ($approval_view === 'pending');
$statusMeta = spl_approval_status_meta($approval_view);
$table_class = 'table table-sm table-hover align-middle spl-activity-table spl-approval-table mb-0';
if ($compact) {
    $table_class .= ' spl-activity-table--compact spl-approval-table--compact';
}
$wrap_class = 'table-responsive spl-activity-table-wrap spl-approval-table-wrap';
if ($compact) {
    $wrap_class .= ' spl-approval-table-wrap--compact';
}
?>
<?php if (empty($rows)): ?>
<div class="spl-empty-state">
  <i class="bi bi-<?php echo $approval_view === 'approved' ? 'check2-circle' : ($approval_view === 'rejected' ? 'x-circle' : 'inbox'); ?>"></i>
  <p><?php echo esc_view($empty_message, ENT_QUOTES, 'UTF-8'); ?></p>
</div>
<?php else: ?>
<div class="<?php echo esc_view($wrap_class, ENT_QUOTES, 'UTF-8'); ?>">
  <table
    id="<?php echo esc_view($table_id, ENT_QUOTES, 'UTF-8'); ?>"
    class="<?php echo esc_view($table_class, ENT_QUOTES, 'UTF-8'); ?>"
    data-page-length="<?php echo (int) $page_length; ?>"
    data-approval-view="<?php echo esc_view($approval_view, ENT_QUOTES, 'UTF-8'); ?>"
  >
    <thead>
      <tr>
        <th>Employee</th>
        <th><?php echo $compact ? 'When' : 'Submitted'; ?></th>
        <th>Activity</th>
        <?php if (!$compact): ?>
        <th>Category</th>
        <?php endif; ?>
        <th class="text-end"><?php echo $compact ? 'Pts' : 'Points'; ?></th>
        <?php if (!$compact): ?>
        <th>Status</th>
        <?php endif; ?>
        <?php if ($approval_view !== 'pending'): ?>
        <th>Decided</th>
        <?php endif; ?>
        <?php if (!$compact): ?>
        <th>Notes</th>
        <?php else: ?>
        <th class="text-center spl-approval-col-icon" title="Notes">Note</th>
        <?php endif; ?>
        <th class="text-center spl-approval-col-icon" title="Attachment">File</th>
        <th class="text-end spl-approval-col-actions"><?php echo $approval_view === 'pending' ? 'Actions' : 'Action'; ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row):
        $points = (float) $row->requested_points;
        $notePreview = spl_activity_note_preview(isset($row->reference_label) ? $row->reference_label : '', 120);
        $hasEvidence = !empty($row->evidence_file);
        $submittedSort = !empty($row->submitted_at) ? strtotime((string) $row->submitted_at) : 0;
        $decidedSort = !empty($row->decided_at) ? strtotime((string) $row->decided_at) : 0;
        $rowClass = '';
        if ($points < 0) {
            $rowClass = 'is-deducted';
        } elseif ($approval_view === 'pending') {
            $rowClass = 'is-pending';
        } elseif ($approval_view === 'approved') {
            $rowClass = 'is-approved';
        } elseif ($approval_view === 'rejected') {
            $rowClass = 'is-rejected';
        }
        $activityLabel = !empty($row->rule_name) ? (string) $row->rule_name : (string) $row->rule_code;
        $submittedLabel = $compact
            ? spl_format_activity_datetime_compact($row->submitted_at)
            : spl_format_activity_datetime($row->submitted_at);
      ?>
      <tr class="spl-approval-row spl-activity-row is-clickable<?php echo $rowClass !== '' ? ' ' . esc_view($rowClass, ENT_QUOTES, 'UTF-8') : ''; ?>" data-approval-id="<?php echo (int) $row->id; ?>" tabindex="0">
        <td class="spl-approval-col-employee" data-order="<?php echo esc_view(strtolower((string) ($row->recipient_name ?: '')), ENT_QUOTES, 'UTF-8'); ?>">
          <span class="spl-approval-employee-name" title="<?php echo esc_view($row->recipient_name ?: '', ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo esc_view($row->recipient_name ?: '—', ENT_QUOTES, 'UTF-8'); ?>
          </span>
        </td>
        <td class="spl-approval-col-date" data-order="<?php echo (int) $submittedSort; ?>">
          <span class="spl-activity-table-date"><?php echo esc_view($submittedLabel, ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <td class="spl-approval-col-activity">
          <span class="spl-activity-table-title spl-approval-activity-title" title="<?php echo esc_view($activityLabel !== '' ? $activityLabel : '—', ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo esc_view($activityLabel !== '' ? $activityLabel : '—', ENT_QUOTES, 'UTF-8'); ?>
          </span>
          <?php if ($compact && !empty($row->category_name)): ?>
          <span class="spl-approval-activity-meta"><?php echo esc_view($row->category_name, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </td>
        <?php if (!$compact): ?>
        <td><?php echo esc_view(!empty($row->category_name) ? $row->category_name : '—', ENT_QUOTES, 'UTF-8'); ?></td>
        <?php endif; ?>
        <td class="text-end spl-approval-col-points" data-order="<?php echo esc_view(number_format($points, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?>">
          <span class="spl-activity-table-points <?php echo $points >= 0 ? 'is-positive' : 'is-negative'; ?>">
            <?php echo ($points >= 0 ? '+' : '') . number_format($points, 0); ?>
          </span>
        </td>
        <?php if (!$compact): ?>
        <td data-order="<?php echo esc_view($statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?>">
          <span class="badge rounded-pill text-bg-<?php echo esc_view($statusMeta['class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($statusMeta['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </td>
        <?php endif; ?>
        <?php if ($approval_view !== 'pending'): ?>
        <td data-order="<?php echo (int) $decidedSort; ?>">
          <?php if (!empty($row->decided_at)): ?>
          <span class="spl-activity-table-date"><?php echo esc_view(spl_format_activity_datetime($row->decided_at), ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if (!empty($row->approver_name)): ?>
          <div class="small text-muted"><?php echo esc_view($row->approver_name, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
          <?php else: ?>
          <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <?php endif; ?>
        <?php if (!$compact): ?>
        <td class="spl-activity-table-note" title="<?php echo esc_view($notePreview, ENT_QUOTES, 'UTF-8'); ?>">
          <?php echo esc_view($notePreview !== '' ? $notePreview : '—', ENT_QUOTES, 'UTF-8'); ?>
        </td>
        <?php else: ?>
        <td class="text-center spl-approval-col-icon" data-order="<?php echo $notePreview !== '' ? '1' : '0'; ?>">
          <?php if ($notePreview !== ''): ?>
          <span class="spl-approval-note-icon" title="<?php echo esc_view($notePreview, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-chat-left-text"></i></span>
          <?php else: ?>
          <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <?php endif; ?>
        <td class="text-center spl-approval-col-icon" data-order="<?php echo $hasEvidence ? '1' : '0'; ?>">
          <?php if ($hasEvidence): ?>
          <span class="spl-activity-table-file" title="<?php echo esc_view($row->evidence_name ?: 'Attachment', ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-paperclip"></i></span>
          <?php else: ?>
          <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
        <td class="text-end spl-approval-col-actions">
          <div class="spl-approval-actions">
            <button type="button" class="btn btn-sm btn-outline-primary spl-approval-view-btn" data-approval-id="<?php echo (int) $row->id; ?>" title="View details" aria-label="View details">
              <i class="bi bi-eye"></i>
            </button>
            <?php if ($approval_view === 'pending' && $csrf_name !== ''): ?>
            <form method="post" action="<?php echo esc_view(site_url('spl/approve-activity/' . (int) $row->id), ENT_QUOTES, 'UTF-8'); ?>" class="spl-approval-inline-form">
              <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
              <button type="submit" class="btn btn-sm btn-success spl-approval-approve-btn" title="Approve" aria-label="Approve">
                <i class="bi bi-check-lg"></i>
              </button>
            </form>
            <form method="post" action="<?php echo esc_view(site_url('spl/reject-activity/' . (int) $row->id), ENT_QUOTES, 'UTF-8'); ?>" class="spl-approval-inline-form spl-approval-reject-form">
              <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
              <button type="submit" class="btn btn-sm btn-outline-danger spl-approval-reject-btn" title="Reject" aria-label="Reject">
                <i class="bi bi-x-lg"></i>
              </button>
            </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
