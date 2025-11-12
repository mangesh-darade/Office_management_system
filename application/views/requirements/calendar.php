<?php $this->load->view('partials/header', ['title' => 'Requirements Calendar']); ?>
<h1 class="h4 mb-3">Requirements Calendar</h1>
<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Req#</th>
            <th>Title</th>
            <th>Client</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Expected Delivery</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="6" class="text-center text-muted">No items to display.</td></tr>
          <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars(isset($r->req_number)?$r->req_number:'#'.(int)$r->id); ?></td>
            <td><a href="<?php echo site_url('requirements/view/'.(int)$r->id); ?>"><?php echo htmlspecialchars($r->title); ?></a></td>
            <td><?php echo htmlspecialchars(isset($r->client_name)?$r->client_name:''); ?></td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(isset($r->status)?$r->status:'received'); ?></span></td>
            <td><span class="badge bg-secondary"><?php echo htmlspecialchars(isset($r->priority)?$r->priority:'medium'); ?></span></td>
            <td><?php echo htmlspecialchars(isset($r->expected_delivery_date)?$r->expected_delivery_date:''); ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
