<?php $this->load->view('partials/header', ['title' => $item->title]); ?>
<div class="container-fluid py-3">
<div class="card shadow-soft"><div class="card-body">
  <h1 class="h4"><?php echo htmlspecialchars($item->title); ?></h1>
  <p class="text-muted small"><?php echo htmlspecialchars($item->category ?: ''); ?> · <?php echo htmlspecialchars($item->status); ?></p>
  <div class="mt-3"><?php echo nl2br(htmlspecialchars($item->body)); ?></div>
  <a class="btn btn-outline-secondary btn-sm mt-3" href="<?php echo site_url('knowledge-base'); ?>">Back</a>
</div></div></div>
<?php $this->load->view('partials/footer'); ?>
