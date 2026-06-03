<?php $this->load->view('partials/header', ['title' => 'Leads']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<?php $this->load->view('coaching/partials/list_header', [
    'title' => 'Leads',
    'subtitle' => 'Prospects and workshop sign-ups',
    'create_url' => 'coaching-leads/create',
    'create_label' => 'Add Lead',
]); ?>
<div class="card border-0 shadow-sm">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0 datatable">
<thead><tr><th>Name</th><th>Phone</th><th>Source</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php if (empty($rows)): ?><tr><td colspan="5" class="text-muted">No leads yet.</td></tr><?php else: foreach ($rows as $r): ?>
<tr>
<td><?php echo htmlspecialchars($r->full_name); ?></td>
<td><?php echo htmlspecialchars($r->phone); ?></td>
<td><?php echo htmlspecialchars($r->source); ?></td>
<td><?php echo htmlspecialchars($r->status); ?></td>
<td>
<a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('coaching-leads/edit/'.$r->id); ?>">Edit</a>
<a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('coaching-leads/convert/'.$r->id); ?>" onclick="return confirm('Convert to client?');">Convert</a>
</td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
