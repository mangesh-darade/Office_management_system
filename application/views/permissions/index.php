<?php $this->load->view('partials/header', ['title' => 'Permission Manager']); ?>

<style>
.perm-table th, .perm-table td { vertical-align: middle; }
.perm-table thead tr:first-child th { font-size: 0.78rem; text-transform: uppercase; letter-spacing: .04em; }
.perm-table .module-key { font-size: 0.72rem; color: #6c757d; font-family: monospace; }
.perm-table .section-header td { background: #e9ecef; font-weight: 700; font-size: 0.82rem; letter-spacing: .03em; }
.col-admin { background: #fff8e1; }
.col-user  { background: #e8f5e9; }
.perm-table input[type=checkbox] { width: 1.15rem; height: 1.15rem; cursor: pointer; }
.sticky-head th { position: sticky; top: 0; z-index: 10; background: #fff; }
.sticky-head tr:first-child th { top: 0; }
.sticky-head tr:nth-child(2) th { top: 2.4rem; }
.btn-col-toggle { font-size: 0.7rem; padding: 1px 6px; }
.perm-search { max-width: 280px; }
</style>

<div class="container-fluid py-3">
  <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 gap-2">
    <div>
      <h1 class="h4 mb-1 fw-bold"><i class="bi bi-shield-lock text-primary me-2"></i>Permission Manager</h1>
      <p class="text-muted small mb-0">Configure role-based access for each module. Admin role always has full access.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
      <input type="text" id="permSearch" class="form-control form-control-sm perm-search" placeholder="Search module…">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('dashboard'); ?>">
        <i class="bi bi-arrow-left me-1"></i>Dashboard
      </a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center py-2 mb-3" role="alert">
      <i class="bi bi-check-circle-fill me-2 fs-5"></i>
      <span><?php echo htmlspecialchars($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center py-2 mb-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
      <span><?php echo htmlspecialchars($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="alert alert-info d-flex align-items-center py-2 mb-3">
    <i class="bi bi-info-circle me-2 fs-5"></i>
    <span>
      <strong>Tip:</strong> Use the column header checkboxes to toggle all permissions for a role.
      Use <kbd>Select All</kbd> / <kbd>Deselect All</kbd> to bulk-toggle the entire table.
      Changes apply immediately after clicking <strong>Save Permissions</strong>.
    </span>
  </div>

  <div class="mb-2 d-flex gap-2 flex-wrap align-items-center">
    <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleAll(true)">
      <i class="bi bi-check2-all me-1"></i>Select All
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleAll(false)">
      <i class="bi bi-x-circle me-1"></i>Deselect All
    </button>
    <span class="text-muted small ms-2" id="checkedCount"></span>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <?php echo form_open('permissions/save', ['id' => 'permForm']); ?>
        <?php
          // CSRF token
          echo '<input type="hidden" name="'.$this->security->get_csrf_token_name().'" value="'.$this->security->get_csrf_hash().'">';

          // Separate roles into admin-group and user-group
          $admin_roles = [];
          $user_roles  = [];

          if (isset($this->db) && $this->db->table_exists('roles') && $this->db->field_exists('group_type', 'roles')) {
              $map = [];
              $this->db->select('id, group_type');
              $this->db->from('roles');
              $rows = $this->db->get()->result();
              foreach ($rows as $r) {
                  $map[(int)$r->id] = strtolower(trim((string)$r->group_type));
              }
              foreach ($roles as $rid => $rname) {
                  $gt = isset($map[$rid]) ? $map[$rid] : '';
                  if ($gt === 'admin') {
                      $admin_roles[$rid] = $rname;
                  } else {
                      $user_roles[$rid] = $rname;
                  }
              }
          } else {
              // Fallback: first 3 IDs are admin group
              $i = 0;
              foreach ($roles as $rid => $rname) {
                  if ($i < 3) { $admin_roles[$rid] = $rname; }
                  else        { $user_roles[$rid]  = $rname; }
                  $i++;
              }
          }

          $total_cols = 1 + count($admin_roles) + count($user_roles);
        ?>

        <div class="table-responsive">
          <table class="table table-bordered table-hover perm-table mb-0" id="permTable">
            <thead class="sticky-head">
              <!-- Group header row -->
              <tr>
                <th class="align-middle" rowspan="2" style="min-width:220px;">
                  Module / Permission
                </th>
                <?php if (!empty($admin_roles)): ?>
                  <th class="text-center col-admin" colspan="<?php echo count($admin_roles); ?>">
                    <i class="bi bi-shield-fill-check text-warning me-1"></i>
                    Admin Group
                  </th>
                <?php endif; ?>
                <?php if (!empty($user_roles)): ?>
                  <th class="text-center col-user" colspan="<?php echo count($user_roles); ?>">
                    <i class="bi bi-person-fill text-success me-1"></i>
                    User Group
                  </th>
                <?php endif; ?>
              </tr>
              <!-- Role name row with per-column toggle -->
              <tr>
                <?php foreach($admin_roles as $rid => $rname): ?>
                  <th class="text-center col-admin" style="min-width:90px;">
                    <div><?php echo htmlspecialchars($rname, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="mt-1">
                      <button type="button" class="btn btn-col-toggle btn-outline-success"
                        onclick="toggleCol('col_<?php echo (int)$rid; ?>', true)"
                        title="Select all for <?php echo htmlspecialchars($rname, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-check-all"></i>
                      </button>
                      <button type="button" class="btn btn-col-toggle btn-outline-danger"
                        onclick="toggleCol('col_<?php echo (int)$rid; ?>', false)"
                        title="Deselect all for <?php echo htmlspecialchars($rname, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </th>
                <?php endforeach; ?>
                <?php foreach($user_roles as $rid => $rname): ?>
                  <th class="text-center col-user" style="min-width:90px;">
                    <div><?php echo htmlspecialchars($rname, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="mt-1">
                      <button type="button" class="btn btn-col-toggle btn-outline-success"
                        onclick="toggleCol('col_<?php echo (int)$rid; ?>', true)"
                        title="Select all for <?php echo htmlspecialchars($rname, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-check-all"></i>
                      </button>
                      <button type="button" class="btn btn-col-toggle btn-outline-danger"
                        onclick="toggleCol('col_<?php echo (int)$rid; ?>', false)"
                        title="Deselect all for <?php echo htmlspecialchars($rname, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($modules as $menu_name => $menu_data): ?>
                <!-- Section header -->
                <tr class="section-header perm-section">
                  <td colspan="<?php echo $total_cols; ?>">
                    <i class="<?php echo htmlspecialchars($menu_data['icon'], ENT_QUOTES, 'UTF-8'); ?> me-2 text-primary"></i>
                    <?php echo htmlspecialchars($menu_name, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="btn btn-col-toggle btn-outline-success ms-3"
                      onclick="toggleSection('section_<?php echo preg_replace('/[^a-z0-9]/i','_', $menu_name); ?>', true)">
                      <i class="bi bi-check-all"></i> All
                    </button>
                    <button type="button" class="btn btn-col-toggle btn-outline-danger"
                      onclick="toggleSection('section_<?php echo preg_replace('/[^a-z0-9]/i','_', $menu_name); ?>', false)">
                      <i class="bi bi-x"></i> None
                    </button>
                  </td>
                </tr>

                <!-- Module rows -->
                <?php foreach($menu_data['modules'] as $key => $label): ?>
                  <tr class="perm-row section_<?php echo preg_replace('/[^a-z0-9]/i','_', $menu_name); ?>"
                      data-label="<?php echo htmlspecialchars(strtolower($label.' '.$key), ENT_QUOTES, 'UTF-8'); ?>">
                    <td class="ps-4">
                      <div class="fw-semibold"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></div>
                      <div class="module-key"><?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?></div>
                    </td>
                    <?php foreach($admin_roles as $rid => $rname): ?>
                      <?php $checked = isset($existing[$rid][$key]) ? ((int)$existing[$rid][$key] === 1) : false; ?>
                      <td class="text-center col-admin">
                        <input class="form-check-input col_<?php echo (int)$rid; ?>"
                               type="checkbox"
                               name="perms[<?php echo (int)$rid; ?>][<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]"
                               value="1"
                               <?php echo $checked ? 'checked' : ''; ?>>
                      </td>
                    <?php endforeach; ?>
                    <?php foreach($user_roles as $rid => $rname): ?>
                      <?php $checked = isset($existing[$rid][$key]) ? ((int)$existing[$rid][$key] === 1) : false; ?>
                      <td class="text-center col-user">
                        <input class="form-check-input col_<?php echo (int)$rid; ?>"
                               type="checkbox"
                               name="perms[<?php echo (int)$rid; ?>][<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>]"
                               value="1"
                               <?php echo $checked ? 'checked' : ''; ?>>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="p-3 border-top d-flex gap-2 align-items-center flex-wrap">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Save Permissions
          </button>
          <span class="text-muted small" id="saveHint">Unsaved changes will be lost if you navigate away.</span>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<script>
(function () {
  // Live search
  var searchInput = document.getElementById('permSearch');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      var q = this.value.toLowerCase().trim();
      document.querySelectorAll('#permTable .perm-row').forEach(function (row) {
        var label = row.getAttribute('data-label') || '';
        var section = row.previousElementSibling;
        row.style.display = (!q || label.indexOf(q) !== -1) ? '' : 'none';
      });
      // Show/hide section headers based on whether any row in section is visible
      document.querySelectorAll('#permTable .perm-section').forEach(function (sec) {
        var next = sec.nextElementSibling;
        var hasVisible = false;
        while (next && next.classList.contains('perm-row')) {
          if (next.style.display !== 'none') { hasVisible = true; break; }
          next = next.nextElementSibling;
        }
        sec.style.display = hasVisible ? '' : 'none';
      });
    });
  }

  // Count checked
  function updateCount() {
    var total   = document.querySelectorAll('#permTable input[type=checkbox]').length;
    var checked = document.querySelectorAll('#permTable input[type=checkbox]:checked').length;
    var el = document.getElementById('checkedCount');
    if (el) el.textContent = checked + ' of ' + total + ' permissions enabled';
  }
  document.getElementById('permTable').addEventListener('change', updateCount);
  updateCount();
})();

function toggleAll(state) {
  document.querySelectorAll('#permTable input[type=checkbox]').forEach(function (cb) {
    if (cb.closest('tr') && cb.closest('tr').style.display !== 'none') {
      cb.checked = state;
    }
  });
  var el = document.getElementById('checkedCount');
  var total   = document.querySelectorAll('#permTable input[type=checkbox]').length;
  var checked = document.querySelectorAll('#permTable input[type=checkbox]:checked').length;
  if (el) el.textContent = checked + ' of ' + total + ' permissions enabled';
}

function toggleCol(colClass, state) {
  document.querySelectorAll('#permTable .' + colClass).forEach(function (cb) {
    if (cb.closest('tr') && cb.closest('tr').style.display !== 'none') {
      cb.checked = state;
    }
  });
}

function toggleSection(sectionClass, state) {
  document.querySelectorAll('#permTable .' + sectionClass + ' input[type=checkbox]').forEach(function (cb) {
    cb.checked = state;
  });
}
</script>

<?php $this->load->view('partials/footer'); ?>
