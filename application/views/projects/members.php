<?php $this->load->view('partials/header', ['title' => 'Project Members']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">Members: <?php echo htmlspecialchars($project->name); ?><?php if (!empty($project->code)) echo ' ('.htmlspecialchars($project->code).')'; ?></h1>
    <div class="text-muted small">Project ID: <?php echo (int)$project->id; ?></div>
  </div>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('projects/'.$project->id); ?>">Back to Project</a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card shadow-soft h-100">
      <div class="card-body">
        <h2 class="h6">Current Members</h2>
        <div class="table-responsive">
          <table class="table table-sm align-middle">
            <thead>
              <tr>
                <th>User</th>
                <th>Role</th>
                <th style="width:220px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($members)): ?>
                <tr><td colspan="3" class="text-center text-muted">No members yet.</td></tr>
              <?php else: foreach ($members as $m): ?>
                <tr>
                  <td>
                    <div class="fw-semibold"><?php echo htmlspecialchars($m->email); ?></div>
                    <?php if (!empty($m->name)): ?><div class="text-muted small"><?php echo htmlspecialchars($m->name); ?></div><?php endif; ?>
                  </td>
                  <td>
                    <?php $role = $m->role ?: 'member'; ?>
                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
                  </td>
                  <td>
                    <div class="d-flex gap-2">
                      <form method="post" action="<?php echo site_url('projects/'.$project->id.'/member/'.(int)$m->user_id.'/role'); ?>" class="d-flex gap-2">
                        <select name="role" class="form-select form-select-sm" required>
                          <?php $roles=['manager','lead','developer','tester','viewer','member']; foreach ($roles as $r): ?>
                            <option value="<?php echo $r; ?>" <?php echo ($r===$role)?'selected':''; ?>><?php echo ucfirst($r); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary btn-sm">Update</button>
                      </form>
                      <a class="btn btn-outline-danger btn-sm" href="<?php echo site_url('projects/'.$project->id.'/remove-member/'.(int)$m->user_id); ?>" onclick="return confirm('Remove this member?')">Remove</a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card shadow-soft h-100">
      <div class="card-body">
        <h2 class="h6">Add Member</h2>
        <form method="get" class="d-flex gap-2 mb-3">
          <input type="text" class="form-control" name="q" placeholder="Search users by email or name" value="<?php echo htmlspecialchars($q ?? ''); ?>" />
          <button class="btn btn-outline-secondary">Search</button>
        </form>
        <?php if (!empty($users)): ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>User</th>
                  <th style="width:180px">Role</th>
                  <th style="width:120px"></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($u->email); ?></div>
                      <?php if (!empty($u->name)): ?><div class="text-muted small"><?php echo htmlspecialchars($u->name); ?></div><?php endif; ?>
                    </td>
                    <td>
                      <form method="post" action="<?php echo site_url('projects/'.$project->id.'/add-member'); ?>" class="d-flex gap-2">
                        <input type="hidden" name="user_id" value="<?php echo (int)$u->id; ?>" />
                        <select name="role" class="form-select form-select-sm">
                          <option value="member">Member</option>
                          <option value="manager">Manager</option>
                          <option value="lead">Lead</option>
                          <option value="developer">Developer</option>
                          <option value="tester">Tester</option>
                          <option value="viewer">Viewer</option>
                        </select>
                        <button class="btn btn-primary btn-sm">Add</button>
                      </form>
                    </td>
                    <td></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php elseif(isset($q) && $q!==''): ?>
          <div class="text-muted">No users found for your search.</div>
        <?php else: ?>
          <div class="text-muted">Search to find users to add as members.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
