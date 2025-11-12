<?php $this->load->view('partials/header', ['title' => 'Requirement Version']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Requirement Version: <?php echo htmlspecialchars(isset($req->req_number)?$req->req_number:'#'.(int)$req->id); ?> - v<?php echo (int)$ver->version_no; ?></h1>
  <a class="btn btn-light btn-sm" href="<?php echo site_url('requirements/view/'.(int)$req->id); ?>">Back</a>
</div>

<div class="row g-3">
  <div class="col-12">
    <div class="card shadow-soft">
      <div class="card-header"><strong>Metadata</strong></div>
      <div class="card-body small">
        <div><span class="text-muted">Client:</span> <?php echo htmlspecialchars(isset($req->client_name)?$req->client_name:''); ?></div>
        <div><span class="text-muted">Version:</span> <?php echo (int)$ver->version_no; ?></div>
        <div><span class="text-muted">Created At:</span> <?php echo htmlspecialchars(isset($ver->created_at)?$ver->created_at:''); ?></div>
        <div><span class="text-muted">Status:</span> <?php echo htmlspecialchars(isset($ver->status)?$ver->status:''); ?></div>
        <div><span class="text-muted">Priority:</span> <?php echo htmlspecialchars(isset($ver->priority)?$ver->priority:''); ?></div>
        <div><span class="text-muted">Expected:</span> <?php echo htmlspecialchars(isset($ver->expected_delivery_date)?$ver->expected_delivery_date:''); ?></div>
        <div><span class="text-muted">Budget:</span> <?php echo htmlspecialchars(isset($ver->budget_estimate)?$ver->budget_estimate:''); ?></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-12">
    <div class="card shadow-soft">
      <div class="card-header"><strong>Changes vs Previous</strong></div>
      <div class="card-body">
        <?php if (!$prev): ?>
        <div class="text-muted small">No previous version to compare.</div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th style="width:180px">Field</th>
                <th>Previous</th>
                <th>Current</th>
              </tr>
            </thead>
            <tbody>
              <?php
                function val($o, $k){ return isset($o->$k) ? $o->$k : ''; }
                $fields = array(
                  'title' => 'Title',
                  'status' => 'Status',
                  'priority' => 'Priority',
                  'requirement_type' => 'Type',
                  'expected_delivery_date' => 'Expected Delivery',
                  'budget_estimate' => 'Budget',
                  'assigned_to' => 'Assigned To',
                );
                foreach ($fields as $k => $label):
                  $old = (string)val($prev, $k);
                  $cur = (string)val($ver, $k);
                  if ($old === $cur) { continue; }
              ?>
              <tr>
                <td><?php echo htmlspecialchars($label); ?></td>
                <td class="text-muted"><?php echo htmlspecialchars($old); ?></td>
                <td><strong><?php echo htmlspecialchars($cur); ?></strong></td>
              </tr>
              <?php endforeach; ?>
              <?php
                $desc_old = (string)val($prev, 'description');
                $desc_new = (string)val($ver, 'description');
              ?>
              <?php if ($desc_old !== $desc_new): ?>
              <tr>
                <td>Description</td>
                <td><div class="border rounded p-2 bg-white" style="max-height:300px; overflow:auto; white-space:normal"><?php echo $desc_old; ?></div></td>
                <td><div class="border rounded p-2 bg-white" style="max-height:300px; overflow:auto; white-space:normal"><?php echo $desc_new; ?></div></td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


<?php $this->load->view('partials/footer'); ?>
