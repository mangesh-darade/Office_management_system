<?php $this->load->view('partials/header', ['title' => 'Sessions']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<?php $this->load->view('coaching/partials/list_header', [
    'title' => 'Sessions',
    'subtitle' => 'Scheduled coaching sessions',
    'create_url' => 'coaching-sessions/create',
    'create_label' => 'Schedule',
    'extra_actions' => '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('coaching-sessions/calendar') . '"><i class="bi bi-calendar3 me-1"></i>Calendar</a>',
]); ?>
<div class="card border-0 shadow-sm">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0 datatable">
<thead><tr><th>When</th><th>Client</th><th>Coach</th><th>Title</th><th>Status</th><th></th></tr></thead>
<tbody>
<?php if (empty($rows)): ?><tr><td colspan="6" class="text-muted">No sessions scheduled.</td></tr><?php else: foreach ($rows as $r): ?>
<tr>
<td><?php echo date('d M Y H:i', strtotime($r->scheduled_at)); ?></td>
<td><?php echo esc_view($r->client_name); ?></td>
<td><?php echo esc_view($r->coach_name); ?></td>
<td><?php echo esc_view($r->title); ?></td>
<td><?php echo esc_view($r->status); ?></td>
<td><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('coaching-sessions/edit/'.$r->id); ?>">Edit</a></td>
</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
