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

<?php /* Flash messages (success/error) are handled globally by the Bootstrap toast in partials/header.php */ ?>

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
                      <?php if(function_exists('has_module_access') && (has_module_access('projects_edit') || has_module_access('projects') || is_admin_group())): ?>
                      <form method="post" action="<?php echo site_url('projects/'.$project->id.'/member/'.(int)$m->user_id.'/role'); ?>" class="d-flex gap-2">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <select name="role" class="form-select form-select-sm" required>
                          <?php $roles = isset($member_roles) ? $member_roles : array('manager','lead','developer','tester','viewer','member'); foreach ($roles as $r): ?>
                            <option value="<?php echo $r; ?>" <?php echo ($r===$role)?'selected':''; ?>><?php echo ucfirst($r); ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-primary btn-sm">Update</button>
                      </form>
                      <form method="post" action="<?php echo site_url('projects/'.$project->id.'/remove-member/'.(int)$m->user_id); ?>" class="d-inline" onsubmit="return confirm('Remove this member?');">
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                      </form>
                      <?php endif; ?>
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
        <?php if(function_exists('has_module_access') && (has_module_access('projects_edit') || has_module_access('projects') || is_admin_group())): ?>
        <form method="get" class="d-flex gap-2 mb-3">
          <input type="text" class="form-control" name="q" placeholder="Search users by email or name" value="<?php echo htmlspecialchars(isset($q) ? $q : ''); ?>" />
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
                        <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                        <input type="hidden" name="user_id" value="<?php echo (int)$u->id; ?>" />
                        <select name="role" class="form-select form-select-sm">
                          <?php $roles = isset($member_roles) ? $member_roles : array('manager','lead','developer','tester','viewer','member'); foreach ($roles as $r): ?>
                            <option value="<?php echo $r; ?>" <?php echo ($r === 'member') ? 'selected' : ''; ?>><?php echo ucfirst($r); ?></option>
                          <?php endforeach; ?>
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
        <?php else: ?>
          <div class="text-muted small"><i class="bi bi-lock me-1"></i>You do not have permission to manage project members.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
