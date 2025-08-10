<?php $this->load->view('partials/header', ['title' => 'Attendance']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Attendance</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" title="Add" href="<?php echo site_url('attendance/create'); ?>"><i class="bi bi-plus-lg"></i></a>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle datatable" data-order-col="0" data-order-dir="asc">
        <thead>
          <tr>
            <th>Name / Email</th>
            <th>Date</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Notes</th>
            <th>File</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if(!empty($records)) foreach($records as $r): ?>
            <?php 
              $name = '';
              if (!empty($r->first_name) || !empty($r->last_name)) {
                $name = trim(($r->first_name ?? '').' '.($r->last_name ?? ''));
              }
              if ($name === '') { $name = $r->email ?? '—'; }
            ?>
            <tr>
              <td><?php echo htmlspecialchars($name); ?></td>
              <td><?php echo htmlspecialchars($r->date); ?></td>
              <td><?php echo htmlspecialchars($r->check_in); ?></td>
              <td><?php echo htmlspecialchars($r->check_out); ?></td>
              <td><?php echo htmlspecialchars($r->notes); ?></td>
              <td>
                <?php if(!empty($r->attachment_path)): ?>
                  <a class="btn btn-outline-secondary btn-sm" title="Download" href="<?php echo base_url($r->attachment_path); ?>" target="_blank"><i class="bi bi-download"></i></a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a class="btn btn-light btn-sm" title="Edit" href="<?php echo site_url('attendance/'.$r->id.'/edit'); ?>"><i class="bi bi-pencil"></i></a>
                <a class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this record?')" href="<?php echo site_url('attendance/'.$r->id.'/delete'); ?>"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
