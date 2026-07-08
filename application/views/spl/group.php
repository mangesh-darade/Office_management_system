<?php
$this->load->view('partials/header', array(
    'title' => 'SPL Group — ' . $group->name,
    'extra_css' => array('assets/css/spl.css'),
));
$poster_info = spl_poster_info(isset($group->poster_path) ? $group->poster_path : '');
$poster = $poster_info['url'];
?>

<div class="container-fluid py-3 spl-page">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div class="d-flex align-items-start gap-2">
      <a class="btn btn-outline-secondary btn-sm flex-shrink-0" href="<?php echo site_url('spl/groups'); ?>"><i class="bi bi-arrow-left"></i><span class="d-none d-sm-inline ms-1">Back</span></a>
      <div>
        <h1 class="h4 mb-1 fw-bold"><?php echo esc_view($group->name, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="text-muted small mb-0"><?php echo esc_view($group->description ?: '', ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
    </div>
    <?php if (!empty($can_submit)): ?>
    <a class="btn btn-primary btn-sm" href="<?php echo spl_dashboard_url('activity'); ?>"><i class="bi bi-plus-circle me-1"></i>Submit Activity</a>
    <?php endif; ?>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-lg-4">
      <div class="card shadow-sm">
        <div class="spl-group-poster-wrap spl-group-poster-wrap--detail">
          <?php if ($poster !== ''): ?>
            <img src="<?php echo esc_view($poster, ENT_QUOTES, 'UTF-8'); ?>" alt="" class="spl-group-poster"<?php if ($poster_info['width'] > 0): ?> width="<?php echo (int) $poster_info['width']; ?>" height="<?php echo (int) $poster_info['height']; ?>"<?php endif; ?>>
            <?php if ($poster_info['dimensions'] !== ''): ?>
            <span class="spl-poster-dimensions"><?php echo esc_view($poster_info['dimensions'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
          <?php else: ?>
            <div class="spl-group-poster spl-group-poster--placeholder"><i class="bi bi-image"></i></div>
          <?php endif; ?>
        </div>
        <div class="card-body py-2">
          <div class="small fw-semibold text-muted mb-1">Members</div>
          <?php if (empty($members)): ?>
            <p class="small text-muted mb-0">No members assigned.</p>
          <?php else: ?>
            <ul class="spl-groups-member-list mb-0">
              <?php foreach ($members as $m): ?>
                <li><?php echo esc_view($m->name, ENT_QUOTES, 'UTF-8'); ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card shadow-sm h-100">
        <div class="card-header bg-white fw-semibold">Activities &amp; points (from rules)</div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Activity</th><th>Code</th><th class="text-end">Points</th></tr></thead>
            <tbody>
            <?php if (empty($rules)): ?>
              <tr><td colspan="3" class="text-muted text-center py-3">No activities linked to this group.</td></tr>
            <?php else: foreach ($rules as $r): ?>
              <tr>
                <td><?php echo esc_view($r->name, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><code><?php echo esc_view($r->code, ENT_QUOTES, 'UTF-8'); ?></code></td>
                <td class="text-end fw-semibold <?php echo ((float) $r->points >= 0) ? 'text-success' : 'text-danger'; ?>">
                  <?php echo ((float) $r->points >= 0 ? '+' : '') . number_format((float) $r->points, 0); ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($can_manage)): ?>
  <div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Edit group &amp; link rules</div>
    <div class="card-body">
      <form method="post" action="<?php echo site_url('spl/groups/save'); ?>" enctype="multipart/form-data">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <input type="hidden" name="id" value="<?php echo (int) $group->id; ?>">
        <input type="hidden" name="redirect" value="spl/groups/<?php echo (int) $group->id; ?>">
        <div class="row g-2 mb-3">
          <div class="col-md-4"><label class="form-label small">Name</label><input class="form-control form-control-sm" name="name" value="<?php echo esc_view($group->name, ENT_QUOTES, 'UTF-8'); ?>" required></div>
          <div class="col-md-3"><label class="form-label small">Code</label><input class="form-control form-control-sm" name="code" value="<?php echo esc_view($group->code, ENT_QUOTES, 'UTF-8'); ?>" required></div>
          <div class="col-md-3"><label class="form-label small">Poster</label><input type="file" class="form-control form-control-sm" name="poster" accept="image/*"></div>
          <div class="col-md-2"><label class="form-label small">Sort</label><input type="number" class="form-control form-control-sm" name="sort_order" value="<?php echo (int) $group->sort_order; ?>"></div>
          <div class="col-12"><label class="form-label small">Description</label><textarea class="form-control form-control-sm" name="description" rows="2"><?php echo esc_view($group->description ?: '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
          <div class="col-md-6">
            <label class="form-label small">Members</label>
            <?php
            $selectedMemberIds = array();
            if (!empty($members)) {
                foreach ($members as $m) {
                    $selectedMemberIds[] = (int) $m->id;
                }
            }
            ?>
            <select class="form-select form-select-sm" name="member_ids[]" multiple size="6">
              <?php foreach ($users as $u): ?>
                <option value="<?php echo (int) $u->id; ?>" <?php echo in_array((int) $u->id, $selectedMemberIds, true) ? 'selected' : ''; ?>>
                  <?php echo esc_view($u->name, ENT_QUOTES, 'UTF-8'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small">Active</label>
            <select class="form-select form-select-sm" name="is_active">
              <option value="1" <?php echo (int) $group->is_active === 1 ? 'selected' : ''; ?>>Yes</option>
              <option value="0" <?php echo (int) $group->is_active !== 1 ? 'selected' : ''; ?>>No</option>
            </select>
          </div>
        </div>
        <label class="form-label small fw-semibold">Linked reward rules</label>
        <p class="small text-muted mb-2">All active SPL rules are automatically linked to every group. Manage rules from <a href="<?php echo spl_dashboard_url('rules'); ?>">SPL Rules</a>.</p>
        <div class="row g-2 spl-rule-check-grid mb-3">
          <?php if (empty($rules)): ?>
            <div class="col-12 text-muted small">No rules linked yet.</div>
          <?php else: foreach ($rules as $ar): ?>
          <div class="col-md-6 col-lg-4">
            <div class="border rounded p-2 bg-light">
              <span class="fw-semibold d-block"><?php echo esc_view($ar->name, ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="small text-muted"><?php echo esc_view($ar->code, ENT_QUOTES, 'UTF-8'); ?> · <?php echo number_format((float) $ar->points, 0); ?> pts</span>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Save group</button>
      </form>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $this->load->view('partials/footer'); ?>
