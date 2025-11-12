<?php $this->load->view('partials/header', ['title' => 'Reminder Schedules']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Reminder Schedules</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('reminders/schedules/create'); ?>">New Schedule</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('reminders/cron/generate-today'); ?>">Generate Today</a>
  </div>
</div>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>
<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Audience</th>
            <th>Weekdays</th>
            <th>Time</th>
            <th>Subject</th>
            <th>Active</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted">No schedules found.</td></tr>
          <?php else: foreach ($rows as $s): ?>
          <tr>
            <td><?php echo (int)$s->id; ?></td>
            <td><?php echo htmlspecialchars($s->name); ?></td>
            <td><?php echo htmlspecialchars($s->audience); ?></td>
            <td><?php echo htmlspecialchars($s->weekdays); ?></td>
            <td><?php echo htmlspecialchars($s->send_time); ?></td>
            <td class="small"><?php echo htmlspecialchars($s->subject); ?></td>
            <td><?php echo ((int)$s->active) ? 'Yes' : 'No'; ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
