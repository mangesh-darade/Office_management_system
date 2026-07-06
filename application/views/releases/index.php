<?php $this->load->view('partials/header', ['title' => 'Releases']); ?>
<div class="container-fluid py-3">
<?php
$can_add = function_exists('has_module_access') && (has_module_access('releases_add') || has_module_access('releases'));
$can_export = function_exists('has_module_access') && (has_module_access('releases_export') || has_module_access('releases'));
$actions = '';
if ($can_export) {
    $export_q = $_SERVER['QUERY_STRING'] ?? '';
    $actions .= '<a class="btn btn-outline-secondary btn-sm me-1" href="'.site_url('releases/export'.($export_q ? '?'.$export_q : '')).'"><i class="bi bi-download me-1"></i>Export CSV</a>';
}
if ($can_add) {
    $actions .= '<a class="btn btn-primary btn-sm" href="'.site_url('releases/create').'"><i class="bi bi-plus-lg me-1"></i>New Release</a>';
}
$this->load->view('partials/oms_page_head', ['title' => 'Release Management', 'icon' => 'bi-rocket-takeoff', 'subtitle' => 'Track project releases and go-live dates', 'actions_html' => $actions]);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div><?php endif; ?>
<div class="card shadow-soft mb-3"><div class="card-body"><form class="row g-2" method="get">
  <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option><?php foreach (['planned','in_progress','released','cancelled'] as $s): ?><option value="<?php echo $s; ?>" <?php echo (!empty($filters['status']) && $filters['status']===$s)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><select name="project_id" class="form-select"><option value="">All projects</option><?php foreach ($projects as $p): ?><option value="<?php echo (int)$p->id; ?>" <?php echo (!empty($filters['project_id']) && (int)$filters['project_id']===(int)$p->id)?'selected':''; ?>><?php echo esc_view($p->name); ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><button class="btn btn-outline-secondary">Filter</button></div>
</form></div></div>
<?php if (isset($total)): ?><p class="text-muted small"><?php echo (int)$total; ?> release(s)</p><?php endif; ?>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Version</th><th>Title</th><th>Project</th><th>Status</th><th>Planned</th><th></th></tr></thead><tbody>
<?php if (empty($rows)): ?><?php else: foreach ($rows as $r): ?>
<tr>
  <td><a href="<?php echo site_url('releases/view/'.$r->id); ?>"><?php echo esc_view($r->version); ?></a></td>
  <td><?php echo esc_view($r->title); ?></td>
  <td><?php echo esc_view($r->project_name ?: '—'); ?></td>
  <td><span class="badge bg-light text-dark border"><?php echo esc_view(str_replace('_',' ',$r->status)); ?></span></td>
  <td><?php echo esc_view($r->planned_date ?: '—'); ?></td>
  <td class="text-nowrap">
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('releases/view/'.$r->id); ?>">View</a>
    <?php if (function_exists('has_module_access') && (has_module_access('releases_edit') || has_module_access('releases'))): ?><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('releases/edit/'.$r->id); ?>">Edit</a><?php endif; ?>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php if (!empty($pagination_links)): ?><div class="card-footer"><?php echo $pagination_links; ?></div><?php endif; ?>
</div></div>
<?php $this->load->view('partials/footer'); ?>
