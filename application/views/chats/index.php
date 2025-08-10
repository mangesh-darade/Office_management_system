<?php $this->load->view('partials/header', ['title' => 'Chats']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Chats</h1>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold">Start Direct Message</div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('chats/start-dm'); ?>">
          <div class="mb-2">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
          </div>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-chat-dots"></i> Start</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card h-100">
      <div class="card-header fw-semibold">Create Group</div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('chats/create-group'); ?>">
          <div class="row g-2">
            <div class="col-12 col-md-4">
              <label class="form-label">Title</label>
              <input type="text" class="form-control" name="title" placeholder="Team Alpha" required>
            </div>
            <div class="col-12 col-md-8">
              <label class="form-label">Participants</label>
              <select class="form-select" name="participants[]" multiple required>
                <?php foreach ($users as $u): $label = $u->email; if (!empty($u->full_name)) { $label = $u->full_name.' ('.$u->email.')'; } else if (!empty($u->name)) { $label = $u->name.' ('.$u->email.')'; } ?>
                  <option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Hold Ctrl/Cmd to select multiple users.</small>
            </div>
          </div>
          <div class="mt-2">
            <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-people"></i> Create Group</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="card mt-4">
  <div class="card-header fw-semibold">Your Conversations</div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Type</th>
            <th>Title / Members</th>
            <th>Created</th>
            <th class="text-end">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($conversations)) foreach ($conversations as $c): ?>
            <tr>
              <td><?php echo (int)$c->id; ?></td>
              <td><span class="badge bg-secondary"><?php echo htmlspecialchars(strtoupper($c->type)); ?></span></td>
              <td>
                <?php if ($c->type === 'group'): ?>
                  <strong><?php echo htmlspecialchars($c->title ?: 'Untitled Group'); ?></strong>
                <?php else: ?>
                  <span><?php echo htmlspecialchars($c->members); ?></span>
                <?php endif; ?>
              </td>
              <td class="text-muted small"><?php echo htmlspecialchars($c->created_at); ?></td>
              <td class="text-end">
                <a class="btn btn-light btn-sm" title="Open" href="<?php echo site_url('chats/app?open='.(int)$c->id); ?>"><i class="bi bi-arrow-right"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($conversations)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">No conversations yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
