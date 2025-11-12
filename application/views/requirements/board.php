<?php $this->load->view('partials/header', ['title' => 'Requirements Board']); ?>
<h1 class="h4 mb-3">Requirements Board</h1>
<div class="row g-3">
  <?php $cols = array('received','under_review','approved','in_progress','completed','on_hold','rejected','cancelled');
  $labels = array(
    'received'=>'Received','under_review'=>'Under Review','approved'=>'Approved','in_progress'=>'In Progress',
    'completed'=>'Completed','on_hold'=>'On Hold','rejected'=>'Rejected','cancelled'=>'Cancelled'
  );
  foreach ($cols as $st): ?>
  <div class="col-lg-3">
    <div class="card shadow-soft">
      <div class="card-header"><strong><?php echo $labels[$st]; ?></strong></div>
      <div class="card-body" style="max-height:520px; overflow:auto">
        <?php if (empty($columns[$st])): ?>
          <div class="text-muted small">No items.</div>
        <?php else: foreach ($columns[$st] as $r): ?>
          <div class="border rounded p-2 mb-2 bg-light">
            <div class="small text-muted"><?php echo htmlspecialchars(isset($r->req_number)?$r->req_number:'#'.(int)$r->id); ?></div>
            <div><a href="<?php echo site_url('requirements/view/'.(int)$r->id); ?>"><?php echo htmlspecialchars($r->title); ?></a></div>
            <div class="small text-muted"><?php echo htmlspecialchars(isset($r->client_name)?$r->client_name:''); ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php $this->load->view('partials/footer'); ?>
