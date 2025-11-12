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
                $name = trim((isset($r->first_name) ? $r->first_name : '').' '.(isset($r->last_name) ? $r->last_name : ''));
              }
              if ($name === '') { $name = isset($r->email) && $r->email !== '' ? $r->email : '—'; }
              // Schema-aware fields
              $d = isset($r->att_date) ? $r->att_date : (isset($r->date) ? $r->date : '');
              $cin = isset($r->punch_in) ? $r->punch_in : (isset($r->check_in) ? $r->check_in : '');
              $cout = isset($r->punch_out) ? $r->punch_out : (isset($r->check_out) ? $r->check_out : '');
              $notes = isset($r->notes) ? $r->notes : '';
              $file = isset($r->attachment_path) ? $r->attachment_path : '';
            ?>
            <tr>
              <td><?php echo htmlspecialchars($name); ?></td>
              <td><?php echo htmlspecialchars($d); ?></td>
              <td><?php echo htmlspecialchars($cin); ?></td>
              <td><?php echo htmlspecialchars($cout); ?></td>
              <td><?php echo htmlspecialchars($notes); ?></td>
              <td>
                <?php if(!empty($file)): ?>
                  <a class="btn btn-outline-secondary btn-sm" title="Download" href="<?php echo base_url($file); ?>" target="_blank"><i class="bi bi-download"></i></a>
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
