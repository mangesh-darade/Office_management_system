<?php $this->load->view('partials/header', ['title' => 'Defects']); ?>
<div class="container-fluid py-3">
<?php
$can_add = function_exists('has_module_access') && (has_module_access('defects_add') || has_module_access('defects'));
$actions = $can_add ? '<a class="btn btn-primary btn-sm" href="'.site_url('defects/create').'"><i class="bi bi-plus-lg me-1"></i>Log Defect</a>' : '';
$this->load->view('partials/oms_page_head', ['title' => 'Defect Tracking', 'icon' => 'bi-bug', 'subtitle' => 'Report and resolve project bugs and issues', 'actions_html' => $actions]);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div><?php endif; ?>
<div class="card shadow-soft mb-3"><div class="card-body"><form class="row g-2" method="get">
  <div class="col-md-2"><input type="text" name="q" class="form-control" placeholder="Search…" value="<?php echo htmlspecialchars($filters['q'] ?? ''); ?>"></div>
  <div class="col-md-2"><select name="status" class="form-select"><option value="">All statuses</option><?php foreach (['open','in_progress','fixed','verified','closed','rejected'] as $s): ?><option value="<?php echo $s; ?>" <?php echo (!empty($filters['status']) && $filters['status']===$s)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="severity" class="form-select"><option value="">All severities</option><?php foreach (['low','medium','high','critical'] as $s): ?><option value="<?php echo $s; ?>" <?php echo (!empty($filters['severity']) && $filters['severity']===$s)?'selected':''; ?>><?php echo ucfirst($s); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="project_id" class="form-select"><option value="">All projects</option><?php foreach ($projects as $p): ?><option value="<?php echo (int)$p->id; ?>" <?php echo (!empty($filters['project_id']) && (int)$filters['project_id']===(int)$p->id)?'selected':''); ?>><?php echo htmlspecialchars($p->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><select name="assigned_to" class="form-select"><option value="">All assignees</option><?php foreach ($members as $m): ?><option value="<?php echo (int)$m->id; ?>" <?php echo (!empty($filters['assigned_to']) && (int)$filters['assigned_to']===(int)$m->id)?'selected':''); ?>><?php echo htmlspecialchars($m->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
</form></div></div>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>ID</th><th>Title</th><th>Project</th><th>Severity</th><th>Status</th><th>Assignee</th><th></th></tr></thead><tbody>
<?php if (empty($rows)): ?><tr><td colspan="7" class="text-muted text-center">No defects yet.</td></tr><?php else: foreach ($rows as $r): ?>
<tr>
  <td><a href="<?php echo site_url('defects/view/'.$r->id); ?>"><?php echo htmlspecialchars($r->defect_number); ?></a></td>
  <td><?php echo htmlspecialchars($r->title); ?></td>
  <td><?php echo htmlspecialchars($r->project_name ?: '—'); ?></td>
  <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r->severity); ?></span></td>
  <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(str_replace('_',' ',$r->status)); ?></span></td>
  <td><?php echo htmlspecialchars($r->assignee_name ?: '—'); ?></td>
  <td class="text-nowrap">
    <?php if (function_exists('has_module_access') && (has_module_access('defects_view') || has_module_access('defects_list') || has_module_access('defects'))): ?><a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('defects/view/'.$r->id); ?>">View</a><?php endif; ?>
    <?php if (function_exists('has_module_access') && (has_module_access('defects_edit') || has_module_access('defects'))): ?><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('defects/edit/'.$r->id); ?>">Edit</a><?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
