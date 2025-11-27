<?php $this->load->view('partials/header', ['title' => 'Activity Logs']); ?>
<style>
.activity-user-name { font-weight: 600; color: #374151; }
.activity-user-email { color: #6b7280; font-size: 0.875rem; }
.activity-unknown { color: #9ca3af; font-style: italic; }
.activity-badge { 
  display: inline-block; 
  padding: 0.25rem 0.5rem; 
  font-size: 0.75rem; 
  font-weight: 500; 
  border-radius: 0.25rem;
}
.activity-badge-module { background: #dbeafe; color: #1e40af; }
.activity-badge-action { background: #f3f4f6; color: #374151; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">📊 Activity Logs</h1>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('activity/export'); ?>">
    <i class="bi bi-download me-1"></i>Export CSV
  </a>
</div>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <label class="form-label">User</label>
        <select class="form-select" name="user_id">
          <option value="">All</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo (int)$u->id; ?>" <?php echo (!empty($filters['user_id']) && (int)$filters['user_id']===(int)$u->id)?'selected':''; ?>><?php echo htmlspecialchars(isset($u->display_name) ? $u->display_name : $u->email); ?></option>
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
              <td><span class="badge bg-secondary">#<?php echo (int)$r->id; ?></span></td>
              <td>
                <?php 
                  if (!empty($r->user_name)) {
                    echo '<div class="activity-user-name">' . htmlspecialchars($r->user_name) . '</div>';
                    if (!empty($r->user_email)) {
                      echo '<div class="activity-user-email">' . htmlspecialchars($r->user_email) . '</div>';
                    }
                  } elseif (!empty($r->user_email)) {
                    echo '<div class="activity-user-name">' . htmlspecialchars($r->user_email) . '</div>';
                  } else {
                    echo '<div class="activity-unknown">Unknown User (ID: ' . (int)$r->actor_id . ')</div>';
                  }
                ?>
              </td>
              <td><span class="activity-badge activity-badge-module"><?php echo htmlspecialchars(ucfirst($r->entity_type)); ?></span></td>
              <td><span class="activity-badge activity-badge-action"><?php echo htmlspecialchars(ucfirst(str_replace('_',' ', $r->action))); ?></span></td>
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
