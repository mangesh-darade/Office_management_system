<?php $this->load->view('partials/header', ['title' => 'Defects']); ?>
<div class="container-fluid py-3">
<?php
$can_add = function_exists('has_module_access') && (has_module_access('defects_add') || has_module_access('defects'));
$can_export = function_exists('has_module_access') && (has_module_access('defects_export') || has_module_access('defects_list') || has_module_access('defects'));
$actions = '';
if ($can_export) {
    $export_q = safe_query_string();
    $actions .= '<a class="btn btn-outline-secondary btn-sm me-1" href="'.site_url('defects/export'.($export_q ? '?'.$export_q : '')).'"><i class="bi bi-download me-1"></i>Export CSV</a>';
}
if ($can_add) {
    $actions .= '<a class="btn btn-outline-secondary btn-sm me-1" href="'.site_url('defects/import').'"><i class="bi bi-upload me-1"></i>Import CSV</a>';
    $actions .= '<a class="btn btn-primary btn-sm" href="'.site_url('defects/create').'"><i class="bi bi-plus-lg me-1"></i>Log Defect</a>';
}
$this->load->view('partials/oms_page_head', ['title' => 'Defect Tracking', 'icon' => 'bi-bug', 'subtitle' => 'Report and resolve project bugs and issues', 'actions_html' => $actions]);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div><?php endif; ?>
<div class="card shadow-soft mb-3"><div class="card-body"><form class="row g-2" method="get">
  <div class="col-md-2"><input type="text" name="q" class="form-control" placeholder="Search…" value="<?php echo esc_view($filters['q'] ?? ''); ?>"></div>
  <div class="col-md-2"><select name="status" class="form-select"><option value="">All statuses</option><?php foreach (['open','in_progress','fixed','verified','closed','rejected'] as $s): ?><option value="<?php echo $s; ?>" <?php echo (!empty($filters['status']) && $filters['status']===$s)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="severity" class="form-select"><option value="">All severities</option><?php foreach (['low','medium','high','critical'] as $s): ?><option value="<?php echo $s; ?>" <?php echo (!empty($filters['severity']) && $filters['severity']===$s)?'selected':''; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="project_id" class="form-select"><option value="">All projects</option><?php foreach ($projects as $p): ?><option value="<?php echo (int)$p->id; ?>" <?php echo (!empty($filters['project_id']) && (int)$filters['project_id']===(int)$p->id)?'selected':''; ?>><?php echo esc_view($p->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="assigned_to" class="form-select"><option value="">All assignees</option><?php foreach ($members as $m): ?><option value="<?php echo (int)$m->id; ?>" <?php echo (!empty($filters['assigned_to']) && (int)$filters['assigned_to']===(int)$m->id)?'selected':''; ?>><?php echo esc_view($m->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-1"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="overdue" value="1" id="fltOverdue" <?php echo !empty($filters['overdue'])?'checked':''; ?>><label class="form-check-label small" for="fltOverdue">Overdue</label></div></div>
  <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form></div></div>
<?php if (isset($total)): ?><p class="text-muted small"><?php echo (int)$total; ?> defect(s)</p><?php endif; ?>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>ID</th><th>Title</th><th>Project</th><th>Severity</th><th>Status</th><th>Due</th><th>Assignee</th><th></th></tr></thead><tbody>
<?php if (empty($rows)): ?><?php else: foreach ($rows as $r): ?>
<?php $overdue = function_exists('defect_is_overdue') && defect_is_overdue($r); ?>
<tr<?php echo $overdue ? ' class="table-warning"' : ''; ?>>
  <td><a href="<?php echo site_url('defects/view/'.$r->id); ?>"><?php echo esc_view($r->defect_number); ?></a></td>
  <td><?php echo esc_view($r->title); ?><?php if ($overdue): ?> <span class="badge bg-danger">Overdue</span><?php endif; ?></td>
  <td><?php echo esc_view($r->project_name ?: '—'); ?></td>
  <td><span class="badge bg-light text-dark border"><?php echo esc_view($r->severity); ?></span></td>
  <td><span class="badge bg-light text-dark border"><?php echo esc_view(str_replace('_',' ',$r->status)); ?></span></td>
  <td><?php echo esc_view(isset($r->due_date) && $r->due_date ? $r->due_date : '—'); ?></td>
  <td><?php echo esc_view($r->assignee_name ?: '—'); ?></td>
  <td class="text-nowrap">
    <?php if (function_exists('has_module_access') && (has_module_access('defects_view') || has_module_access('defects_list') || has_module_access('defects'))): ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('defects/view/'.$r->id); ?>">View</a><?php endif; ?>
    <?php if (function_exists('has_module_access') && (has_module_access('defects_edit') || has_module_access('defects'))): ?><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('defects/edit/'.$r->id); ?>">Edit</a><?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php if (!empty($pagination_links)): ?><div class="card-footer"><?php echo $pagination_links; ?></div><?php endif; ?>
</div></div>
<?php $this->load->view('partials/footer'); ?>
