<?php $this->load->view('partials/header', ['title' => 'Leaderboard']); ?>
<div class="container-fluid py-3"><h1 class="h4 fw-bold mb-3">Leaderboard · <?php echo esc_view($period_key); ?></h1>
<div class="card shadow-soft"><ol class="list-group list-group-numbered list-group-flush">
<?php if (empty($rows)): ?><li class="list-group-item text-muted">No rankings yet.</li><?php else: $rank=0; foreach ($rows as $r): $rank++; ?>
<li class="list-group-item d-flex justify-content-between align-items-center"><span><?php echo esc_view(isset($r->user_name)?$r->user_name:'User #'.$r->user_id); ?></span><span class="badge bg-primary rounded-pill"><?php echo number_format((float)(isset($r->net_points)?$r->net_points:0),0); ?> pts</span></li>
<?php endforeach; endif; ?>
</ol></div></div>
<?php $this->load->view('partials/footer'); ?>
