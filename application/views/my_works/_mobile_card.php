<?php
  $statusLabels = my_works_status_labels();
  $statusColors = my_works_status_colors();
  $borderClass = my_works_row_border_class($r);
  $stColor = isset($statusColors[$r->status]) ? $statusColors[$r->status] : 'secondary';
  $stLabel = isset($statusLabels[$r->status]) ? $statusLabels[$r->status] : $r->status;
  $forLabel = my_works_user_label($r->created_for_name, $r->created_for_email, $r->created_for);
  $byLabel = my_works_user_label($r->created_by_name, $r->created_by_email, $r->created_by);
  $uid = isset($uid) ? (int) $uid : 0;
  $canStatus = (!empty($can_quick_edit) || (int) $r->created_for === $uid);
  $overdue = my_works_is_overdue($r);
?>
<div class="mw-item-card <?php echo $borderClass; ?>">
  <a href="<?php echo site_url('my-works/' . (int) $r->id); ?>" class="d-block text-decoration-none text-body">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
      <div class="fw-semibold fs-6 text-dark"><?php echo esc_view($r->title); ?></div>
      <?php if (!$canStatus): ?>
        <span class="badge bg-<?php echo $stColor; ?> flex-shrink-0"><?php echo esc_view($stLabel); ?></span>
      <?php endif; ?>
    </div>
    <?php if (!empty($r->details)): ?>
      <p class="small text-muted mb-2"><?php echo esc_view(substr(strip_tags($r->details), 0, 120)); ?></p>
    <?php endif; ?>
    <div class="d-flex flex-wrap gap-1 mb-2">
      <?php if ((int) $r->is_urgent === 1): ?><span class="badge bg-danger">Urgent</span><?php endif; ?>
      <?php if ((int) $r->is_important === 1): ?><span class="badge bg-warning text-dark">Important</span><?php endif; ?>
      <?php if ($overdue): ?><span class="badge bg-danger">Overdue</span><?php endif; ?>
      <?php foreach (my_works_parse_tags(isset($r->tag) ? $r->tag : '') as $tg): ?>
        <span class="badge bg-light text-dark border"><?php echo esc_view($tg); ?></span>
      <?php endforeach; ?>
      <?php $this->load->view('my_works/_attachment_badge', array('r' => $r, 'attachments_map' => isset($attachments_map) ? $attachments_map : null)); ?>
      <?php if (!empty($r->url)): ?><span class="badge bg-light text-primary border"><i class="bi bi-link-45deg"></i></span><?php endif; ?>
    </div>
    <?php if (!empty($r->work_type) || !empty($r->client_name) || !empty($r->project_name)): ?>
    <div class="d-flex flex-wrap gap-1 mb-2">
      <?php if (!empty($r->work_type)): ?><span class="mw-chip mw-chip-type"><i class="bi bi-tag"></i><?php echo esc_view(my_works_type_label($r->work_type)); ?></span><?php endif; ?>
      <?php if (!empty($r->client_name)): ?><span class="mw-chip mw-chip-client"><i class="bi bi-building"></i><?php echo esc_view($r->client_name); ?></span><?php endif; ?>
      <?php if (!empty($r->project_name)): ?><span class="mw-chip mw-chip-project"><i class="bi bi-folder"></i><?php echo esc_view($r->project_name); ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($r->url) || !empty($r->closing_comment)): ?>
    <div class="small text-muted mb-2">
      <?php if (!empty($r->url)): ?><a href="<?php echo esc_view($r->url); ?>" target="_blank" rel="noopener" class="me-2"><i class="bi bi-link-45deg me-1"></i>URL</a><?php endif; ?>
      <?php if (!empty($r->closing_comment)): ?><?php $cc = (string) $r->closing_comment; ?><span title="<?php echo esc_view($cc); ?>"><i class="bi bi-chat-left-text me-1"></i><?php echo esc_view(strlen($cc) > 40 ? substr($cc, 0, 40) . '…' : $cc); ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="small text-muted d-flex flex-wrap gap-2 justify-content-between">
      <span><i class="bi bi-person me-1"></i><?php echo esc_view($forLabel); ?></span>
      <span><?php echo my_works_format_when($r->updated_at); ?></span>
    </div>
    <div class="small text-muted mt-1">By <?php echo esc_view($byLabel); ?></div>
    <?php if (!empty($r->due_date)): ?>
      <div class="small mt-1 <?php echo $overdue ? 'text-danger fw-semibold' : 'text-muted'; ?>">Due <?php echo esc_view($r->due_date); ?></div>
    <?php endif; ?>
  </a>
  <?php if (!empty(my_works_row_attachments($r, isset($attachments_map) ? $attachments_map : null))): ?>
    <div class="d-flex flex-wrap gap-1 mt-2 mw-mobile-att-actions">
      <?php $this->load->view('my_works/_attachment_actions', array('r' => $r, 'attachments_map' => isset($attachments_map) ? $attachments_map : null)); ?>
    </div>
  <?php endif; ?>
  <?php if ($canStatus): ?>
    <form method="post" action="<?php echo site_url('my-works/update-status'); ?>" class="mt-2 mw-quick-status">
      <?php $this->load->view('my_works/_csrf'); ?>
      <input type="hidden" name="id" value="<?php echo (int) $r->id; ?>">
      <input type="hidden" name="redirect" value="<?php echo esc_view(current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '')); ?>">
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <?php foreach ($statusLabels as $k => $lbl): ?>
          <option value="<?php echo $k; ?>" <?php echo $r->status === $k ? 'selected' : ''; ?>><?php echo esc_view($lbl); ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>
</div>
