<?php $this->load->view('partials/header', ['title' => 'Workshops']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="d-flex justify-content-between mb-3"><h1 class="h4">Workshops</h1><a class="btn btn-primary btn-sm" href="<?php echo site_url('coaching-leads/workshop-form'); ?>">Add Workshop</a></div>
<div class="card shadow-soft"><table class="table table-sm"><thead><tr><th>Title</th><th>Date</th><th>Status</th><th>Register link</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><?php echo esc_view($r->title); ?></td><td><?php echo $r->workshop_date ? date('d M Y', strtotime($r->workshop_date)) : '—'; ?></td><td><?php echo esc_view($r->status); ?></td><td class="small"><?php if ($r->status==='published'): ?><code><?php echo site_url('coaching/workshop-register/'.$r->id); ?></code><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php $this->load->view('partials/footer'); ?>
