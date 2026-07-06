<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$task_priority = isset($t->priority) ? strtolower((string) $t->priority) : 'medium';
$task_priority_badge_class = isset($tasks_priority_badge[$task_priority]) ? $tasks_priority_badge[$task_priority] : 'secondary';
$task_status = isset($t->status) ? (string) $t->status : 'pending';
$task_status_badge_class = isset($tasks_status_badge[$task_status]) ? $tasks_status_badge[$task_status] : 'info';
$assignee = '';
if (isset($t->emp_name) && $t->emp_name !== '') {
    $assignee = $t->emp_name;
} else if (isset($t->full_name) && $t->full_name !== '') {
    $assignee = $t->full_name;
} else if (isset($t->name) && $t->name !== '') {
    $assignee = $t->name;
} else if (isset($t->assignee_email) && $t->assignee_email !== '') {
    $assignee = $t->assignee_email;
}
$desc_plain = isset($t->description) ? trim(strip_tags((string) $t->description)) : '';
$project_label = isset($t->project_name) && $t->project_name !== '' ? $t->project_name : ('#' . (int) $t->project_id);
?>
<article class="oms-mobile-card">
  <div class="oms-mobile-card-top">
    <div class="min-w-0 flex-grow-1">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge bg-light text-muted border">#<?php echo (int) $t->id; ?></span>
        <span class="badge bg-<?php echo esc_view($task_status_badge_class); ?>"><?php echo esc_view(ucfirst(str_replace('_', ' ', $task_status))); ?></span>
      </div>
      <a href="<?php echo site_url('tasks/' . (int) $t->id); ?>" class="oms-mobile-card-title text-decoration-none d-block"><?php echo esc_view($t->title); ?></a>
    </div>
    <span class="badge bg-<?php echo esc_view($task_priority_badge_class); ?> flex-shrink-0"><?php echo esc_view(ucfirst($task_priority !== '' ? $task_priority : 'medium')); ?></span>
  </div>
  <div class="oms-mobile-card-meta mt-2">
    <span><i class="bi bi-folder2 me-1"></i><?php echo esc_view($project_label); ?></span>
    <?php if (!empty($is_admin) && $assignee !== ''): ?>
      <span class="mx-1">·</span>
      <span><i class="bi bi-person me-1"></i><?php echo esc_view($assignee); ?></span>
    <?php endif; ?>
  </div>
  <?php if ($desc_plain !== ''): ?>
    <p class="oms-mobile-card-desc mb-0 mt-2"><?php echo esc_view(strlen($desc_plain) > 140 ? substr($desc_plain, 0, 140) . '…' : $desc_plain); ?></p>
  <?php endif; ?>
  <div class="oms-mobile-card-actions">
    <a class="btn btn-light btn-sm" href="<?php echo site_url('tasks/' . (int) $t->id); ?>" title="View"><i class="bi bi-eye"></i></a>
    <?php if (function_exists('has_module_access') && (has_module_access('tasks_edit') || has_module_access('tasks'))): ?>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('tasks/' . (int) $t->id . '/edit'); ?>" title="Edit"><i class="bi bi-pencil"></i></a>
    <?php endif; ?>
    <?php if (function_exists('has_module_access') && (has_module_access('tasks_delete') || has_module_access('tasks'))): ?>
    <form method="post" action="<?php echo site_url('tasks/' . (int) $t->id . '/delete'); ?>" class="d-inline m-0" onsubmit="return confirm('Delete this task?');">
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
      <button type="submit" class="btn btn-danger btn-sm" title="Delete"><i class="bi bi-trash"></i></button>
    </form>
    <?php endif; ?>
  </div>
</article>
