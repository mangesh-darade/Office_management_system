<?php $this->load->view('partials/header', ['title' => 'Activity Logs']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Activity Logs</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('activity/export'); ?>">Export CSV</a>
</div>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <label class="form-label">User</label>
        <select class="form-select" name="user_id">
          <option value="">All</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo (int)$u->id; ?>" <?php echo (!empty($filters['user_id']) && (int)$filters['user_id']===(int)$u->id)?'selected':''; ?>><?php echo htmlspecialchars($u->email); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Module</label>
        <select class="form-select" name="module">
          <option value="">All</option>
          <?php foreach ($modules as $m): ?>
            <option value="<?php echo htmlspecialchars($m); ?>" <?php echo (!empty($filters['module']) && $filters['module']===$m)?'selected':''; ?>><?php echo ucfirst($m); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Action</label>
        <select class="form-select" name="action">
          <option value="">All</option>
          <?php foreach ($actions as $a): ?>
            <option value="<?php echo htmlspecialchars($a); ?>" <?php echo (!empty($filters['action']) && $filters['action']===$a)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ', $a)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label">From</label>
        <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars(isset($filters['from'])?$filters['from']:''); ?>" />
      </div>
      <div class="col-md-1">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars(isset($filters['to'])?$filters['to']:''); ?>" />
      </div>
      <div class="col-md-1 align-self-end">
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
            <th>ID</th>
            <th>User</th>
            <th>Module</th>
            <th>Action</th>
            <th>Description</th>
            <th>IP</th>
            <th>When</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="text-center text-muted">No activity found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?php echo (int)$r->id; ?></td>
              <td><?php echo isset($r->actor_id)?(int)$r->actor_id:0; ?></td>
              <td><?php echo htmlspecialchars($r->entity_type); ?></td>
              <td><?php echo htmlspecialchars($r->action); ?></td>
              <td style="max-width:360px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <?php echo htmlspecialchars(isset($r->description)?$r->description:''); ?>
              </td>
              <td class="text-muted small"><?php echo htmlspecialchars(isset($r->ip_address)?$r->ip_address:''); ?></td>
              <td class="text-muted small"><?php echo htmlspecialchars(isset($r->created_at)?$r->created_at:''); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
