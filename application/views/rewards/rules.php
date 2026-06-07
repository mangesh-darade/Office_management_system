<?php $this->load->view('partials/header', ['title' => 'Reward Rules']); ?>
<div class="container-fluid py-3">
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4 fw-bold mb-0">Reward rules</h1>
<div><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('rewards/edit-rule'); ?>">Add rule</a> <a class="btn btn-sm btn-primary" href="<?php echo site_url('rewards/manual-grant'); ?>">Manual grant</a></div></div>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Code</th><th>Name</th><th>Trigger</th><th>Points</th><th>Active</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td><code><?php echo htmlspecialchars($r->code); ?></code></td><td><?php echo htmlspecialchars($r->name); ?></td><td><?php echo htmlspecialchars($r->trigger_event); ?></td><td><?php echo number_format((float)$r->points,0); ?></td><td><?php echo $r->is_active?'Yes':'No'; ?></td><td><a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('rewards/edit-rule/'.$r->id); ?>">Edit</a></td></tr><?php endforeach; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
