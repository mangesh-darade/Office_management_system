<?php
$this->load->view('partials/header', ['title' => 'Permission Manager']);

// Split roles into admin-group and user-group (matrix columns)
$admin_roles = [];
$user_roles  = [];
if (isset($this->db) && $this->db->table_exists('roles') && schema_table_has_column($this->db, 'roles', 'group_type')) {
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
    $i = 0;
    foreach ($roles as $rid => $rname) {
        if ($i < 3) { $admin_roles[$rid] = $rname; }
        else        { $user_roles[$rid]  = $rname; }
        $i++;
    }
}
$total_cols = 1 + count($admin_roles) + count($user_roles);
?>

<style>
.perm-table th, .perm-table td { vertical-align: middle; }
.perm-table thead th { box-shadow: inset 0 -1px 0 #dee2e6; }
.perm-table thead tr:first-child th { font-size: 0.78rem; text-transform: uppercase; letter-spacing: .04em; }
.perm-table .module-key { font-size: 0.72rem; color: #6c757d; font-family: monospace; }
.perm-table .section-header td { background: #e9ecef; font-weight: 700; font-size: 0.82rem; letter-spacing: .03em; }
.col-admin { background: #fff8e1 !important; }
.col-user  { background: #e8f5e9 !important; }
.perm-table input[type=checkbox] { width: 1.15rem; height: 1.15rem; cursor: pointer; }
.perm-grid-wrap {
  height: calc(100dvh - 100px);
  min-height: 85vh;
  max-height: calc(100dvh - 80px);
  overflow: auto;
  position: relative;
}
.sticky-head th {
  position: sticky;
  background: #fff;
  z-index: 8;
}
.sticky-head tr:first-child th {
  top: 0;
  z-index: 10;
}
.sticky-head tr:nth-child(2) th {
  top: var(--perm-head-row1-h, 42px);
  z-index: 9;
}
.sticky-head th.perm-sticky-module {
  left: 0;
  z-index: 12;
  min-width: 220px;
  background: #fff !important;
  box-shadow: inset -1px 0 0 #dee2e6, inset 0 -1px 0 #dee2e6;
}
.sticky-head th.col-admin { background: #fff8e1 !important; }
.sticky-head th.col-user  { background: #e8f5e9 !important; }
.group-admin-header,
.group-user-header { white-space: nowrap; }
.perm-table tbody td.perm-sticky-module {
  position: sticky;
  left: 0;
  z-index: 4;
  background: #fff !important;
  box-shadow: inset -1px 0 0 #dee2e6;
}
.perm-table tbody tr:hover td.perm-sticky-module { background: #f8f9fa !important; }
.btn-col-toggle { font-size: 0.7rem; padding: 1px 6px; }
.perm-search { max-width: 100%; width: 100%; }
@media (min-width: 576px) {
  .perm-search { max-width: 320px; width: auto; }
}
.perm-toolbar { gap: 0.5rem; }
.perm-toolbar .perm-stat {
  flex: 1 1 100%;
  font-size: 0.8125rem;
  line-height: 1.4;
}
@media (min-width: 768px) {
  .perm-toolbar .perm-stat { flex: 0 1 auto; }
}
.perm-page-header .perm-search-col {
  flex: 1 1 100%;
  width: 100%;
}
@media (min-width: 576px) {
  .perm-page-header .perm-search-col {
    flex: 0 1 auto;
    width: auto;
  }
}
.perm-section .section-toggle-btn {
  color: #495057; text-decoration: none; line-height: 1;
  width: 1.5rem; height: 1.5rem; padding: 0;
}
.perm-section .section-toggle-btn:hover { color: #0d6efd; }
.perm-section { cursor: pointer; user-select: none; }
.perm-section .section-actions { cursor: default; }
.perm-row.section-collapsed { display: none !important; }
.perm-tag { font-size: 0.65rem; font-weight: 600; letter-spacing: .03em; vertical-align: middle; }
</style>

<div class="container-fluid py-2 perm-page">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3 gap-2 perm-page-header">
    <div class="flex-grow-1">
      <h1 class="h4 mb-1 fw-bold"><i class="bi bi-shield-lock text-primary me-2"></i>Permission Manager</h1>
      <p class="text-muted small mb-0">Configure role-based access for each module. Super Admin (role 1) always has full access in the app.</p>
    </div>
    <div class="perm-search-col">
      <label for="permSearch" class="visually-hidden">Search permissions</label>
      <input type="search" id="permSearch" class="form-control form-control-sm perm-search" placeholder="Search permissions…" autocomplete="off">
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center py-2 mb-3" role="alert">
      <i class="bi bi-check-circle-fill me-2 fs-5"></i>
      <span><?php echo esc_view($this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center py-2 mb-3" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
      <span><?php echo esc_view($this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="mb-2 d-flex gap-2 flex-wrap align-items-center perm-toolbar">
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="expandAllSections()">
      <i class="bi bi-chevron-double-down me-1"></i><span class="d-none d-sm-inline">Expand </span>All
    </button>
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="collapseAllSections()">
      <i class="bi bi-chevron-double-up me-1"></i><span class="d-none d-sm-inline">Collapse </span>All
    </button>
    <span class="text-muted small d-none d-md-inline">|</span>
    <button type="button" class="btn btn-sm btn-outline-success" onclick="toggleAll(true)">
      <i class="bi bi-check2-all me-1"></i>Select All
    </button>
    <button type="button" class="btn btn-sm btn-outline-danger" onclick="toggleAll(false)">
      <i class="bi bi-x-circle me-1"></i>Deselect All
    </button>
    <span class="text-muted perm-stat" id="checkedCount" aria-live="polite"></span>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <?php echo form_open('permissions/save', ['id' => 'permForm']); ?>
        <?php
          // CSRF token
          echo '<input type="hidden" name="'.$this->security->get_csrf_token_name().'" value="'.$this->security->get_csrf_hash().'">';
        ?>

        <div class="table-responsive perm-grid-wrap" id="permGridWrap">
          <table class="table table-bordered table-hover perm-table mb-0" id="permTable" data-no-datatable="1">
            <thead class="sticky-head">
              <!-- Group header row -->
              <tr>
                <th class="align-middle perm-sticky-module" rowspan="2" style="min-width:220px;">
                  Module / Permission
                </th>
                <?php if (!empty($admin_roles)): ?>
                  <th class="text-center col-admin group-admin-header" colspan="<?php echo count($admin_roles); ?>" data-group="admin">
                    <i class="bi bi-shield-fill-check text-warning me-1"></i>
                    Admin Group
                  </th>
                <?php endif; ?>
                <?php if (!empty($user_roles)): ?>
                  <th class="text-center col-user group-user-header" colspan="<?php echo count($user_roles); ?>" data-group="user">
                    <i class="bi bi-person-fill text-success me-1"></i>
                    User Group
                  </th>
                <?php endif; ?>
              </tr>
              <!-- Role name row with per-column toggle -->
              <tr>
                <?php foreach($admin_roles as $rid => $rname): ?>
                  <th class="text-center col-admin role-col" data-role-col="<?php echo (int)$rid; ?>" data-group="admin" style="min-width:90px;">
                    <div><?php echo esc_view($rname); ?></div>
                    <div class="mt-1">
                      <button type="button" class="btn btn-col-toggle btn-outline-success"
                        onclick="toggleCol('col_<?php echo (int)$rid; ?>', true)"
                        title="Select all for <?php echo esc_view($rname); ?>">
                        <i class="bi bi-check-all"></i>
                      </button>
                      <button type="button" class="btn btn-col-toggle btn-outline-danger"
                        onclick="toggleCol('col_<?php echo (int)$rid; ?>', false)"
                        title="Deselect all for <?php echo esc_view($rname); ?>">
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </th>
                <?php endforeach; ?>
                <?php foreach($user_roles as $rid => $rname): ?>
                  <th class="text-center col-user role-col" data-role-col="<?php echo (int)$rid; ?>" data-group="user" style="min-width:90px;">
                    <div><?php echo esc_view($rname); ?></div>
                    <div class="mt-1">
                      <button type="button" class="btn btn-col-toggle btn-outline-success"
                        onclick="toggleCol('col_<?php echo (int)$rid; ?>', true)"
                        title="Select all for <?php echo esc_view($rname); ?>">
                        <i class="bi bi-check-all"></i>
                      </button>
                      <button type="button" class="btn btn-col-toggle btn-outline-danger"
                        onclick="toggleCol('col_<?php echo (int)$rid; ?>', false)"
                        title="Deselect all for <?php echo esc_view($rname); ?>">
                        <i class="bi bi-x"></i>
                      </button>
                    </div>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($modules as $menu_name => $menu_data): ?>
                <?php $section_slug = preg_replace('/[^a-z0-9]/i', '_', $menu_name); ?>
                <!-- Section header -->
                <tr class="section-header perm-section is-collapsed" data-section-id="section_<?php echo $section_slug; ?>"
                    onclick="handleSectionRowClick(event, 'section_<?php echo $section_slug; ?>')"
                    title="Click row to expand/collapse">
                  <td class="section-label-cell" colspan="<?php echo $total_cols; ?>">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                      <div class="d-flex align-items-center">
                        <span class="btn btn-sm section-toggle-btn me-1" aria-hidden="true">
                          <i class="bi bi-chevron-down section-chevron"></i>
                        </span>
                        <i class="<?php echo esc_view($menu_data['icon']); ?> me-2 text-primary"></i>
                        <span><?php echo esc_view($menu_name); ?></span>
                        <span class="badge bg-secondary ms-2 section-count-badge"><?php echo count($menu_data['modules']); ?></span>
                      </div>
                      <div class="d-flex gap-1 section-actions">
                        <button type="button" class="btn btn-col-toggle btn-outline-success"
                          onclick="toggleSection('section_<?php echo $section_slug; ?>', true)">
                          <i class="bi bi-check-all"></i> All
                        </button>
                        <button type="button" class="btn btn-col-toggle btn-outline-danger"
                          onclick="toggleSection('section_<?php echo $section_slug; ?>', false)">
                          <i class="bi bi-x"></i> None
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>

                <!-- Module rows -->
                <?php foreach($menu_data['modules'] as $key => $def):
                  $meta = permissions_module_meta($def);
                  $label = $meta['label'];
                  $tag = $meta['tag'];
                  $searchLabel = strtolower($label . ' ' . $key . ' ' . $tag);
                ?>
                  <tr class="perm-row section-collapsed section_<?php echo $section_slug; ?>"
                      data-label="<?php echo esc_view($searchLabel); ?>">
                    <td class="ps-4 perm-sticky-module">
                      <div class="fw-semibold d-flex align-items-center flex-wrap gap-1">
                        <span><?php echo esc_view($label); ?></span>
                        <?php if ($tag !== ''): ?>
                          <span class="badge perm-tag <?php echo esc_view(permissions_module_tag_class($tag), ENT_QUOTES, 'UTF-8'); ?>"><?php echo esc_view($tag); ?></span>
                        <?php endif; ?>
                      </div>
                      <div class="module-key"><?php echo esc_view($key); ?></div>
                    </td>
                    <?php foreach($admin_roles as $rid => $rname): ?>
                      <?php $checked = isset($existing[$rid][$key]) ? ((int)$existing[$rid][$key] === 1) : false; ?>
                      <td class="text-center col-admin role-col" data-role-col="<?php echo (int)$rid; ?>" data-group="admin">
                        <input class="form-check-input col_<?php echo (int)$rid; ?>"
                               type="checkbox"
                               name="perms[<?php echo (int)$rid; ?>][<?php echo esc_view($key); ?>]"
                               value="1"
                               <?php echo $checked ? 'checked' : ''; ?>>
                      </td>
                    <?php endforeach; ?>
                    <?php foreach($user_roles as $rid => $rname): ?>
                      <?php $checked = isset($existing[$rid][$key]) ? ((int)$existing[$rid][$key] === 1) : false; ?>
                      <td class="text-center col-user role-col" data-role-col="<?php echo (int)$rid; ?>" data-group="user">
                        <input class="form-check-input col_<?php echo (int)$rid; ?>"
                               type="checkbox"
                               name="perms[<?php echo (int)$rid; ?>][<?php echo esc_view($key); ?>]"
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

        <div class="p-2 border-top d-flex gap-2 align-items-center flex-wrap perm-save-bar">
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
  var preSearchCollapsed = null;

  function updateSectionChevron(header, collapsed) {
    var icon = header ? header.querySelector('.section-chevron') : null;
    if (!icon) return;
    icon.className = collapsed
      ? 'bi bi-chevron-down section-chevron'
      : 'bi bi-chevron-up section-chevron';
  }

  function setSectionCollapsed(sectionId, collapsed) {
    var header = document.querySelector('.perm-section[data-section-id="' + sectionId + '"]');
    if (header) {
      header.classList.toggle('is-collapsed', collapsed);
      updateSectionChevron(header, collapsed);
    }

    document.querySelectorAll('#permTable .perm-row.' + sectionId).forEach(function (row) {
      row.classList.toggle('section-collapsed', collapsed);
      if (!collapsed && row.getAttribute('data-search-hidden') === '1') {
        row.style.display = 'none';
      } else if (!collapsed) {
        row.style.display = '';
      }
    });

    updateCount();
  }

  window.handleSectionRowClick = function (e, sectionId) {
    if (e.target.closest('.section-actions') || e.target.closest('button') || e.target.closest('input')) {
      return;
    }
    toggleSectionCollapse(sectionId);
  };

  window.toggleSectionCollapse = function (sectionId) {
    var header = document.querySelector('.perm-section[data-section-id="' + sectionId + '"]');
    if (!header) return;
    setSectionCollapsed(sectionId, !header.classList.contains('is-collapsed'));
  };

  window.expandAllSections = function () {
    document.querySelectorAll('.perm-section[data-section-id]').forEach(function (header) {
      setSectionCollapsed(header.getAttribute('data-section-id'), false);
    });
    applySearchFilter();
  };

  window.collapseAllSections = function () {
    document.querySelectorAll('.perm-section[data-section-id]').forEach(function (header) {
      setSectionCollapsed(header.getAttribute('data-section-id'), true);
    });
    updateCount();
  };

  function sectionHasSearchMatch(sectionId, q) {
    var matched = false;
    document.querySelectorAll('#permTable .perm-row.' + sectionId).forEach(function (row) {
      var label = row.getAttribute('data-label') || '';
      if (label.indexOf(q) !== -1) matched = true;
    });
    return matched;
  }

  function applySearchFilter() {
    var searchInput = document.getElementById('permSearch');
    var q = searchInput ? searchInput.value.toLowerCase().trim() : '';
    var isSearching = q.length > 0;

    if (isSearching) {
      if (preSearchCollapsed === null) {
        preSearchCollapsed = [];
        document.querySelectorAll('.perm-section.is-collapsed').forEach(function (sec) {
          preSearchCollapsed.push(sec.getAttribute('data-section-id'));
        });
      }

      document.querySelectorAll('#permTable .perm-section').forEach(function (sec) {
        var sectionId = sec.getAttribute('data-section-id');
        setSectionCollapsed(sectionId, !sectionHasSearchMatch(sectionId, q));
      });
    } else if (preSearchCollapsed !== null) {
      var saved = preSearchCollapsed.slice();
      preSearchCollapsed = null;
      document.querySelectorAll('.perm-section[data-section-id]').forEach(function (sec) {
        var sectionId = sec.getAttribute('data-section-id');
        setSectionCollapsed(sectionId, saved.indexOf(sectionId) !== -1);
      });
    }

    document.querySelectorAll('#permTable .perm-row').forEach(function (row) {
      if (row.classList.contains('section-collapsed')) {
        row.setAttribute('data-search-hidden', '1');
        return;
      }
      var label = row.getAttribute('data-label') || '';
      var match = !isSearching || label.indexOf(q) !== -1;
      row.style.display = match ? '' : 'none';
      row.setAttribute('data-search-hidden', match ? '0' : '1');
    });

    document.querySelectorAll('#permTable .perm-section').forEach(function (sec) {
      if (!isSearching) {
        sec.style.display = '';
        return;
      }
      var sectionId = sec.getAttribute('data-section-id');
      var hasVisible = false;
      document.querySelectorAll('#permTable .perm-row.' + sectionId).forEach(function (row) {
        if (!row.classList.contains('section-collapsed') && row.style.display !== 'none') {
          hasVisible = true;
        }
      });
      sec.style.display = hasVisible ? '' : 'none';
    });

    updateCount();
  }

  function syncStickyHeaderHeight() {
    var row1 = document.querySelector('#permTable thead tr:first-child');
    if (!row1) return;
    var h = Math.ceil(row1.getBoundingClientRect().height);
    document.documentElement.style.setProperty('--perm-head-row1-h', h + 'px');
  }

  function syncGridHeight() {
    var wrap = document.getElementById('permGridWrap');
    if (!wrap) return;
    var saveBar = wrap.parentElement ? wrap.parentElement.querySelector('.perm-save-bar') : null;
    var saveH = saveBar ? saveBar.offsetHeight : 48;
    var bottomPad = 8;
    var available = window.innerHeight - wrap.getBoundingClientRect().top - saveH - bottomPad;
    var minH = window.innerWidth < 576 ? 280 : 640;
    var height = Math.max(minH, available);
    wrap.style.height = height + 'px';
    wrap.style.maxHeight = height + 'px';
    syncStickyHeaderHeight();
  }

  // On load: sections collapsed; open first section on small screens for discoverability
  document.querySelectorAll('.perm-section[data-section-id]').forEach(function (header) {
    setSectionCollapsed(header.getAttribute('data-section-id'), true);
  });
  if (window.innerWidth < 768) {
    var firstSection = document.querySelector('.perm-section[data-section-id]');
    if (firstSection) {
      setSectionCollapsed(firstSection.getAttribute('data-section-id'), false);
    }
  }

  // Live search
  var searchInput = document.getElementById('permSearch');
  if (searchInput) {
    searchInput.addEventListener('input', applySearchFilter);
  }

  function isRowVisible(row) {
    if (!row) return false;
    if (row.classList.contains('section-collapsed')) return false;
    if (row.style.display === 'none') return false;
    return true;
  }

  function updateCount() {
    var boxes = document.querySelectorAll('#permTable .perm-row input[type=checkbox]');
    var allTotal = 0;
    var allChecked = 0;
    var visibleTotal = 0;
    var visibleChecked = 0;

    boxes.forEach(function (cb) {
      allTotal++;
      if (cb.checked) allChecked++;

      var row = cb.closest('tr');
      if (!isRowVisible(row)) return;
      visibleTotal++;
      if (cb.checked) visibleChecked++;
    });

    var el = document.getElementById('checkedCount');
    if (!el) return;

    var searchQ = (document.getElementById('permSearch') || {}).value || '';
    var isSearching = searchQ.trim().length > 0;
    var collapsedSections = document.querySelectorAll('.perm-section.is-collapsed').length;
    var totalSections = document.querySelectorAll('.perm-section[data-section-id]').length;

    if (allTotal === 0) {
      el.textContent = 'No permissions loaded';
      return;
    }

    if (isSearching && visibleTotal !== allTotal) {
      el.textContent = visibleChecked + ' of ' + visibleTotal + ' matching · ' + allChecked + ' of ' + allTotal + ' total enabled';
    } else if (visibleTotal === 0 && collapsedSections === totalSections) {
      el.textContent = allChecked + ' of ' + allTotal + ' enabled · expand a section below to edit';
    } else if (visibleTotal < allTotal) {
      el.textContent = visibleChecked + ' of ' + visibleTotal + ' visible · ' + allChecked + ' of ' + allTotal + ' total enabled';
    } else {
      el.textContent = allChecked + ' of ' + allTotal + ' permissions enabled';
    }
  }
  window.updateCount = updateCount;

  var permTable = document.getElementById('permTable');
  if (permTable) permTable.addEventListener('change', updateCount);
  updateCount();

  syncGridHeight();
  window.addEventListener('resize', syncGridHeight);
  window.addEventListener('load', syncGridHeight);
  setTimeout(syncGridHeight, 150);
  setTimeout(syncGridHeight, 500);
})();

function toggleAll(state) {
  document.querySelectorAll('#permTable .perm-row input[type=checkbox]').forEach(function (cb) {
    var row = cb.closest('tr');
    if (row && row.style.display !== 'none' && !row.classList.contains('section-collapsed')) {
      cb.checked = state;
    }
  });
  if (typeof updateCount === 'function') updateCount();
}

function toggleCol(colClass, state) {
  document.querySelectorAll('#permTable .' + colClass).forEach(function (cb) {
    var row = cb.closest('tr');
    if (row && row.style.display !== 'none' && !row.classList.contains('section-collapsed')) {
      cb.checked = state;
    }
  });
  if (typeof updateCount === 'function') updateCount();
}

function toggleSection(sectionClass, state) {
  document.querySelectorAll('#permTable .' + sectionClass + ' input[type=checkbox]').forEach(function (cb) {
    cb.checked = state;
  });
  if (typeof updateCount === 'function') updateCount();
}

(function () {
  var form = document.getElementById('permForm');
  if (!form) {
    return;
  }
  form.addEventListener('submit', function () {
    var perms = {};
    document.querySelectorAll('#permTable .perm-row input[type=checkbox]').forEach(function (cb) {
      var match = cb.name.match(/^perms\[(\d+)\]\[([^\]]+)\]$/);
      if (!match) {
        return;
      }
      var rid = match[1];
      var key = match[2];
      if (!perms[rid]) {
        perms[rid] = {};
      }
      if (cb.checked) {
        perms[rid][key] = 1;
      }
    });

    var payload = document.getElementById('perms_json_payload');
    if (!payload) {
      payload = document.createElement('input');
      payload.type = 'hidden';
      payload.name = 'perms_json';
      payload.id = 'perms_json_payload';
      form.appendChild(payload);
    }
    payload.value = JSON.stringify(perms);

    document.querySelectorAll('#permTable input[type=checkbox]').forEach(function (cb) {
      cb.disabled = true;
    });
  });
})();
</script>

<?php $this->load->view('partials/footer'); ?>
