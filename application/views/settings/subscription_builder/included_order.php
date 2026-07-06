<?php
$plans = isset($plans) ? $plans : array();
$industries = isset($industries) ? $industries : array();
$plan = isset($plan) ? $plan : '';
$industry = isset($industry) ? $industry : '';
$included_by_module = isset($included_by_module) ? $included_by_module : array();
$save_url = isset($save_url) ? $save_url : '';
$module_count = count($included_by_module);
$feature_count = 0;
foreach ($included_by_module as $items) {
    $feature_count += count($items);
}
?>
<?php $this->load->view('partials/header', ['title' => 'Included Section Display Order']); ?>
<div class="sb-order-page">
<div class="sb-order-topbar mb-2">
  <div class="sb-order-topbar-title">
    <h1 class="h5 mb-0"><i class="bi bi-sort-down me-1"></i>Included Display Order</h1>
    <p class="text-muted mb-0 sb-order-subtitle">Module &amp; feature order for <strong>Included in ElintOm …</strong> only</p>
  </div>
  <div class="sb-order-topbar-controls">
    <form method="get" action="<?php echo site_url('settings/subscription-builder/included-order'); ?>" class="sb-order-filter-form">
      <div class="sb-order-filter-item">
        <label class="sb-order-filter-label" for="sb-order-plan">Plan</label>
        <select class="form-select form-select-sm sb-order-select" id="sb-order-plan" name="plan" required>
          <?php foreach ($plans as $p): ?>
          <option value="<?php echo esc_view($p); ?>" <?php echo strcasecmp($p, $plan) === 0 ? 'selected' : ''; ?>><?php echo esc_view($p); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="sb-order-filter-item">
        <label class="sb-order-filter-label" for="sb-order-industry">Industry</label>
        <select class="form-select form-select-sm sb-order-select" id="sb-order-industry" name="industry" required>
          <?php foreach ($industries as $ind): ?>
          <option value="<?php echo esc_view($ind); ?>" <?php echo strcasecmp($ind, $industry) === 0 ? 'selected' : ''; ?>><?php echo esc_view($ind); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm sb-order-ctrl-btn">
        <i class="bi bi-funnel"></i> Load
      </button>
    </form>
    <div class="sb-order-topbar-actions">
      <a href="<?php echo site_url('settings/subscription-builder'); ?>" class="btn btn-outline-secondary btn-sm sb-order-ctrl-btn">
        <i class="bi bi-arrow-left"></i> Catalog
      </a>
      <a href="<?php echo site_url('subscription-builder'); ?>" class="btn btn-outline-primary btn-sm sb-order-ctrl-btn" target="_blank" rel="noopener">
        <i class="bi bi-box-arrow-up-right"></i> Builder
      </a>
    </div>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
<div class="alert alert-danger py-2"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
<div class="alert alert-success py-2"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<?php if ($plan !== '' && $industry !== ''): ?>
<div class="card shadow-sm sb-order-list-card">
  <div class="card-header bg-light sb-order-list-header">
    <div class="sb-order-list-title">
      <span class="fw-semibold">ElintOm <?php echo esc_view($plan); ?></span>
      <span class="text-muted">· <?php echo esc_view($industry); ?></span>
    </div>
    <div class="sb-order-toolbar">
      <span class="badge bg-secondary sb-order-badge"><?php echo (int) $module_count; ?> mod</span>
      <span class="badge bg-success sb-order-badge"><?php echo (int) $feature_count; ?> feat</span>
      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none sb-order-expand-all" id="sb-order-expand-all">Expand</button>
      <span class="text-muted sb-order-dot">·</span>
      <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none sb-order-collapse-all" id="sb-order-collapse-all">Collapse</button>
      <span id="sb-order-save-status" class="sb-order-status text-muted">Auto-save on drag</span>
      <button type="button" class="btn btn-outline-success btn-sm d-none" id="sb-order-save-btn" aria-hidden="true">
        <i class="bi bi-check2 me-1"></i>Save Order
      </button>
    </div>
  </div>
  <div class="card-body p-1 px-2">
    <?php if (empty($included_by_module)): ?>
    <div class="text-center text-muted py-3 sb-order-empty">No included features for this plan and industry.</div>
    <?php else: ?>
    <div id="sb-order-modules" class="sb-order-modules">
      <?php foreach ($included_by_module as $module_name => $items): ?>
      <div class="sb-order-module is-collapsed" data-module="<?php echo esc_view($module_name); ?>">
        <div class="sb-order-module-head d-flex align-items-center gap-1">
          <span class="sb-order-drag text-muted" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
          <button type="button" class="btn btn-link p-0 sb-order-toggle" aria-expanded="false" title="Expand or collapse">
            <i class="bi bi-chevron-right sb-order-chevron" aria-hidden="true"></i>
          </button>
          <button type="button" class="btn btn-link p-0 text-start flex-grow-1 sb-order-module-title text-body text-decoration-none">
            <?php echo esc_view($module_name); ?>
          </button>
          <span class="badge sb-order-count-badge"><?php echo count($items); ?></span>
        </div>
        <div class="sb-order-module-body">
          <div class="sb-order-module-body-inner">
            <ul class="sb-order-features list-unstyled mb-0">
              <?php foreach ($items as $item): ?>
              <li class="sb-order-feature d-flex align-items-center gap-1" data-id="<?php echo (int) $item['id']; ?>">
                <span class="sb-order-drag text-muted" aria-hidden="true"><i class="bi bi-grip-vertical"></i></span>
                <span class="sb-order-feature-text"><?php echo esc_view($item['feature']); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

</div>

<?php if (!empty($included_by_module)): ?>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
  var saveUrl = <?php echo json_encode($save_url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  var plan = <?php echo json_encode($plan, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  var industry = <?php echo json_encode($industry, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  var modulesEl = document.getElementById('sb-order-modules');
  var saveBtn = document.getElementById('sb-order-save-btn');
  var statusEl = document.getElementById('sb-order-save-status');
  var saveTimer = null;
  var saving = false;
  var pendingSave = false;
  if (!modulesEl || typeof Sortable === 'undefined') {
    return;
  }

  function collectPayload() {
    var moduleOrder = [];
    var featureOrder = {};
    modulesEl.querySelectorAll('.sb-order-module').forEach(function (modEl) {
      var moduleName = modEl.getAttribute('data-module') || '';
      if (!moduleName) {
        return;
      }
      moduleOrder.push(moduleName);
      var ids = [];
      modEl.querySelectorAll('.sb-order-feature[data-id]').forEach(function (featEl) {
        var id = parseInt(featEl.getAttribute('data-id'), 10);
        if (id > 0) {
          ids.push(id);
        }
      });
      featureOrder[moduleName] = ids;
    });
    return {
      module_order: moduleOrder,
      feature_order: featureOrder
    };
  }

  function setStatus(text, isError) {
    if (!statusEl) {
      return;
    }
    statusEl.textContent = text || '';
    statusEl.classList.toggle('text-danger', !!isError);
    statusEl.classList.toggle('text-success', !isError && !!text);
  }

  function saveOrder() {
    if (saving) {
      pendingSave = true;
      return;
    }
    saving = true;
    pendingSave = false;
    var payload = collectPayload();
    setStatus('Saving…', false);
    var body = new URLSearchParams();
    body.append('plan', plan);
    body.append('industry', industry);
    body.append('module_order', JSON.stringify(payload.module_order));
    body.append('feature_order', JSON.stringify(payload.feature_order));
    if (typeof window.getCsrfName === 'function' && typeof window.getCsrfToken === 'function') {
      body.append(window.getCsrfName(), window.getCsrfToken());
    }

    return fetch(saveUrl, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: body.toString(),
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); }).then(function (res) {
      if (res && res.status === 'success') {
        setStatus('Saved automatically', false);
        window.setTimeout(function () {
          if (statusEl && statusEl.textContent === 'Saved automatically') {
            setStatus('Auto-save on drag', false);
          }
        }, 2000);
      } else {
        setStatus((res && res.message) ? res.message : 'Save failed', true);
      }
    }).catch(function () {
      setStatus('Save failed — try again', true);
    }).finally(function () {
      saving = false;
      if (pendingSave) {
        scheduleAutoSave(100);
      }
    });
  }

  function scheduleAutoSave(delay) {
    if (saveTimer) {
      window.clearTimeout(saveTimer);
    }
    saveTimer = window.setTimeout(function () {
      saveTimer = null;
      saveOrder();
    }, typeof delay === 'number' ? delay : 400);
  }

  function onSortEnd() {
    scheduleAutoSave(400);
  }

  function setModuleExpanded(modEl, expanded) {
    if (!modEl) {
      return;
    }
    modEl.classList.toggle('is-expanded', expanded);
    modEl.classList.toggle('is-collapsed', !expanded);
    var toggle = modEl.querySelector('.sb-order-toggle');
    if (toggle) {
      toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
    var chevron = modEl.querySelector('.sb-order-chevron');
    if (chevron) {
      chevron.classList.toggle('bi-chevron-down', expanded);
      chevron.classList.toggle('bi-chevron-right', !expanded);
    }
  }

  function toggleModule(modEl) {
    setModuleExpanded(modEl, !modEl.classList.contains('is-expanded'));
  }

  modulesEl.addEventListener('click', function (e) {
    var toggleBtn = e.target.closest('.sb-order-toggle, .sb-order-module-title');
    if (!toggleBtn || e.target.closest('.sb-order-drag')) {
      return;
    }
    var modEl = toggleBtn.closest('.sb-order-module');
    if (modEl) {
      e.preventDefault();
      toggleModule(modEl);
    }
  });

  var expandAllBtn = document.getElementById('sb-order-expand-all');
  var collapseAllBtn = document.getElementById('sb-order-collapse-all');
  if (expandAllBtn) {
    expandAllBtn.addEventListener('click', function () {
      modulesEl.querySelectorAll('.sb-order-module').forEach(function (modEl) {
        setModuleExpanded(modEl, true);
      });
    });
  }
  if (collapseAllBtn) {
    collapseAllBtn.addEventListener('click', function () {
      modulesEl.querySelectorAll('.sb-order-module').forEach(function (modEl) {
        setModuleExpanded(modEl, false);
      });
    });
  }

  new Sortable(modulesEl, {
    handle: '.sb-order-module-head',
    filter: '.sb-order-toggle, .sb-order-module-title',
    preventOnFilter: true,
    animation: 150,
    draggable: '.sb-order-module',
    onEnd: onSortEnd
  });

  modulesEl.querySelectorAll('.sb-order-features').forEach(function (listEl) {
    new Sortable(listEl, {
      draggable: '.sb-order-feature',
      animation: 150,
      group: { name: listEl.closest('.sb-order-module').getAttribute('data-module'), pull: false, put: false },
      onEnd: onSortEnd
    });
  });

  if (saveBtn) {
    saveBtn.addEventListener('click', function () {
      if (saveTimer) {
        window.clearTimeout(saveTimer);
        saveTimer = null;
      }
      saveOrder();
    });
  }
})();
</script>
<style>
.sb-order-page {
  width: 100%;
  max-width: none;
}
.sb-order-topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem 1rem;
}
.sb-order-topbar-title {
  flex: 0 1 auto;
  min-width: 0;
}
.sb-order-subtitle {
  font-size: 0.7rem;
  margin-top: 0.1rem;
}
.sb-order-topbar-controls {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem 0.75rem;
  margin-left: auto;
}
.sb-order-filter-form {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin: 0;
}
.sb-order-filter-item {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}
.sb-order-filter-label {
  font-size: 0.65rem;
  color: #6b7280;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  margin: 0;
  white-space: nowrap;
  line-height: 1;
}
.sb-order-select {
  width: auto;
  min-width: 7.5rem;
  max-width: 10rem;
  height: 1.875rem;
  font-size: 0.75rem;
  padding: 0 1.75rem 0 0.5rem;
  line-height: 1.875rem;
}
.sb-order-ctrl-btn {
  height: 1.875rem;
  font-size: 0.75rem;
  padding: 0 0.55rem;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  white-space: nowrap;
}
.sb-order-topbar-actions {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  padding-left: 0.75rem;
  border-left: 1px solid #e5e7eb;
}
.sb-order-page .alert {
  padding: 0.35rem 0.65rem;
  font-size: 0.8rem;
  margin-bottom: 0.5rem;
}
.sb-order-label {
  font-size: 0.65rem;
  color: #6b7280;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.sb-order-list-card {
  width: 100%;
}
.sb-order-list-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.35rem 0.75rem;
  min-height: 1.875rem;
  padding: 0.35rem 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}
.sb-order-list-title {
  font-size: 0.8rem;
  line-height: 1.875rem;
  white-space: nowrap;
}
.sb-order-list-title .text-muted {
  font-size: 0.72rem;
}
.sb-order-toolbar {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.35rem 0.5rem;
  margin-left: auto;
  font-size: 0.7rem;
  line-height: 1.875rem;
}
.sb-order-badge {
  font-size: 0.6rem;
  font-weight: 600;
  padding: 0.15em 0.4em;
  line-height: 1.2;
  vertical-align: middle;
}
.sb-order-status {
  font-size: 0.65rem;
  white-space: nowrap;
  line-height: 1.875rem;
}
.sb-order-dot {
  font-size: 0.65rem;
  line-height: 1.875rem;
}
.sb-order-expand-all,
.sb-order-collapse-all {
  font-size: 0.7rem;
  line-height: 1.875rem;
  height: 1.875rem;
  display: inline-flex;
  align-items: center;
}
.sb-order-modules {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  width: 100%;
}
.sb-order-module {
  border: 1px solid #e5e7eb;
  border-radius: 0.375rem;
  background: #fff;
  overflow: hidden;
  width: 100%;
}
.sb-order-module-head {
  cursor: grab;
  user-select: none;
  padding: 0.28rem 0.5rem;
  background: #fafbfc;
  min-height: 1.65rem;
  width: 100%;
}
.sb-order-module-head:active {
  cursor: grabbing;
}
.sb-order-module-title {
  cursor: pointer;
  line-height: 1.2;
  user-select: none;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0 !important;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sb-order-toggle {
  color: #6b7280;
  flex-shrink: 0;
  line-height: 1;
  min-width: 1rem;
}
.sb-order-toggle:hover,
.sb-order-module-title:hover {
  color: #4338ca;
}
.sb-order-chevron {
  font-size: 0.7rem;
}
.sb-order-count-badge {
  font-size: 0.6rem;
  font-weight: 700;
  padding: 0.1em 0.35em;
  background: #f3f4f6;
  color: #4b5563;
  border: 1px solid #e5e7eb;
  border-radius: 999px;
  flex-shrink: 0;
}
.sb-order-drag {
  opacity: 0.35;
  pointer-events: none;
  font-size: 0.7rem;
  line-height: 1;
  flex-shrink: 0;
}
.sb-order-module-body {
  display: grid;
  grid-template-rows: 1fr;
  transition: grid-template-rows 0.2s ease;
}
.sb-order-module.is-collapsed .sb-order-module-body {
  grid-template-rows: 0fr;
}
.sb-order-module-body-inner {
  overflow: hidden;
  min-height: 0;
}
.sb-order-features {
  padding: 0.15rem 0.35rem 0.25rem 1.1rem;
}
.sb-order-feature {
  cursor: grab;
  user-select: none;
  padding: 0.12rem 0.25rem;
  border-radius: 0.25rem;
  line-height: 1.25;
}
.sb-order-feature:hover {
  background: #f8fafc;
}
.sb-order-feature:active {
  cursor: grabbing;
}
.sb-order-feature-text {
  font-size: 0.68rem;
  color: #374151;
  min-width: 0;
  word-break: break-word;
}
.sb-order-empty {
  font-size: 0.75rem;
}
.sb-order-module.sortable-ghost,
.sb-order-feature.sortable-ghost {
  opacity: 0.5;
  background: #f1f5f9;
}
@media (max-width: 991.98px) {
  .sb-order-topbar-controls {
    margin-left: 0;
    width: 100%;
  }
  .sb-order-topbar-actions {
    padding-left: 0;
    border-left: 0;
    width: 100%;
    justify-content: flex-end;
  }
  .sb-order-list-header {
    align-items: flex-start;
  }
  .sb-order-toolbar {
    margin-left: 0;
    width: 100%;
    justify-content: flex-end;
  }
}
</style>
<?php endif; ?>

<?php $this->load->view('partials/footer'); ?>
