<?php $this->load->view('partials/header', ['title' => 'Session Calendar']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<h1 class="h4 mb-3">Calendar — <?php echo htmlspecialchars($month); ?></h1>
<div class="card shadow-soft"><ul class="list-group list-group-flush">
<?php foreach ($rows as $r): ?><li class="list-group-item"><?php echo date('D d M, H:i', strtotime($r->scheduled_at)); ?> — <strong><?php echo htmlspecialchars($r->client_name); ?></strong> with <?php echo htmlspecialchars($r->coach_name); ?> (<?php echo htmlspecialchars($r->status); ?>)</li><?php endforeach; ?>
</ul></div>
<?php $this->load->view('partials/footer'); ?>
