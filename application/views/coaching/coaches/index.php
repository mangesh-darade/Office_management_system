<?php $this->load->view('partials/header', ['title' => 'Coaches']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<?php $this->load->view('coaching/partials/list_header', [
    'title' => 'Coaches',
    'subtitle' => 'Coach profiles, rates, and commission',
    'create_url' => 'coaching-coaches/create',
    'create_label' => 'Add Coach',
]); ?>
<div class="card border-0 shadow-sm">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0 datatable">
<thead><tr><th>Name</th><th>Email</th><th>Title</th><th>Rate</th><th>Commission %</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php if (empty($rows)): ?><tr><td colspan="7" class="text-muted">No coaches yet.</td></tr><?php else: foreach ($rows as $r): ?>
<tr>
<td><?php echo esc_view($r->name ? $r->name : ''); ?></td>
<td><?php echo esc_view($r->email ? $r->email : ''); ?></td>
<td><?php echo esc_view($r->title ? $r->title : ''); ?></td>
<td>₹<?php echo number_format((float) $r->hourly_rate, 2); ?></td>
<td><?php echo number_format((float) $r->commission_pct, 1); ?>%</td>
<td><?php echo esc_view($r->status); ?></td>
<td><a href="<?php echo site_url('coaching-coaches/edit/'.$r->id); ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
