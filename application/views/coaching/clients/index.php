<?php $this->load->view('partials/header', ['title' => 'Coaching Clients']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<?php
$this->load->view('coaching/partials/list_header', [
    'title' => 'Clients',
    'subtitle' => 'Manage coaching clients and portal access',
    'create_url' => 'coaching-clients/create',
    'create_label' => 'Add Client',
]);
$show_crm = coaching_crm_available();
?>
<div class="card border-0 shadow-sm">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0 datatable">
<thead><tr>
<th>Name</th><th>Email</th><th>Phone</th>
<?php if ($show_crm): ?><th>CRM Client</th><?php endif; ?>
<th>Coach</th><th>Portal</th><th></th>
</tr></thead>
<tbody>
<?php if (empty($rows)): ?>
<tr><td colspan="<?php echo $show_crm ? 7 : 6; ?>" class="text-muted">No clients.</td></tr>
<?php else: foreach ($rows as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r->full_name); ?></td>
<td><?php echo htmlspecialchars($r->email ? $r->email : ''); ?></td>
<td><?php echo htmlspecialchars($r->phone ? $r->phone : ''); ?></td>
<?php if ($show_crm): ?>
<td><?php
if (!empty($r->crm_client_id)) {
    $crm_label = trim((string) $r->crm_company_name);
    if ($crm_label === '' && !empty($r->crm_client_code)) {
        $crm_label = $r->crm_client_code;
    }
    if ($crm_label === '') {
        $crm_label = '#' . (int) $r->crm_client_id;
    }
    echo '<a href="' . site_url('clients/view/' . (int) $r->crm_client_id) . '">' . htmlspecialchars($crm_label) . '</a>';
} else {
    echo '—';
}
?></td>
<?php endif; ?>
<td><?php echo htmlspecialchars($r->coach_name ? $r->coach_name : '—'); ?></td>
<td><?php echo $r->portal_enabled ? 'Yes' : 'No'; ?></td>
<td><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('coaching-clients/view/'.$r->id); ?>">View</a></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
