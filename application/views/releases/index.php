<?php $this->load->view('partials/header', ['title' => 'Releases']); ?>
<div class="container-fluid py-3">
<?php
$can_add = function_exists('has_module_access') && (has_module_access('releases_add') || has_module_access('releases'));
$actions = $can_add ? '<a class="btn btn-primary btn-sm" href="'.site_url('releases/create').'"><i class="bi bi-plus-lg me-1"></i>New Release</a>' : '';
$this->load->view('partials/oms_page_head', ['title' => 'Release Management', 'icon' => 'bi-rocket-takeoff', 'subtitle' => 'Track project releases and go-live dates', 'actions_html' => $actions]);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div><?php endif; ?>
<div class="card shadow-soft mb-3"><div class="card-body"><form class="row g-2" method="get">
  <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option><?php foreach (['planned','in_progress','released','cancelled'] as $s): ?><option value="<?php echo $s; ?>" <?php echo (!empty($filters['status']) && $filters['status']===$s)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><button class="btn btn-outline-secondary">Filter</button></div>
</form></div></div>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Version</th><th>Title</th><th>Project</th><th>Status</th><th>Planned</th><th></th></tr></thead><tbody>
<?php if (empty($rows)): ?><tr><td colspan="6" class="text-muted text-center">No releases yet.</td></tr><?php else: foreach ($rows as $r): ?>
<tr><td><?php echo htmlspecialchars($r->version); ?></td><td><?php echo htmlspecialchars($r->title); ?></td><td><?php echo htmlspecialchars($r->project_name ?: '—'); ?></td><td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($r->status); ?></span></td><td><?php echo htmlspecialchars($r->planned_date ?: '—'); ?></td><td><?php if (function_exists('has_module_access') && (has_module_access('releases_edit') || has_module_access('releases'))): ?><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('releases/edit/'.$r->id); ?>">Edit</a><?php else: ?>—<?php endif; ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
