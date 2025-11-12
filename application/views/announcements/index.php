<?php $this->load->view('partials/header', ['title' => 'Announcements']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Announcements</h1>
  <?php if (!empty($can_manage)): ?>
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('announcements/create'); ?>">Create Announcement</a>
  <?php endif; ?>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form class="row g-2" method="get">
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="">All</option>
          <?php $opts=['draft','published','archived']; foreach ($opts as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo (isset($filters['status']) && $filters['status']===$st)?'selected':''; ?>><?php echo ucfirst($st); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Search</label>
        <input class="form-control" name="q" value="<?php echo htmlspecialchars(isset($filters['q']) ? $filters['q'] : ''); ?>" placeholder="Title or content" />
      </div>
      <div class="col-md-3 align-self-end">
        <button class="btn btn-outline-secondary">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Title</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Valid</th>
            <th style="width:200px"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="5" class="text-center text-muted">No announcements found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r->title); ?></td>
              <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(ucfirst($r->priority)); ?></span></td>
              <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($r->status); ?></span></td>
              <td><?php echo htmlspecialchars(($r->start_date ?: '—').' to '.($r->end_date ?: '—')); ?></td>
              <td>
                <?php if (!empty($can_manage)): ?>
                  <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('announcements/'.(int)$r->id.'/edit'); ?>">Edit</a>
                  <a class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this announcement?')" href="<?php echo site_url('announcements/'.(int)$r->id.'/delete'); ?>">Delete</a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
