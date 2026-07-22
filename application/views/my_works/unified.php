<?php $this->load->view('partials/header', array(
  'title' => 'Second Brain',
  'extra_css' => array('assets/css/my-works.css', 'assets/css/project-dashboard.css', 'assets/css/clients.css')
)); ?>
<script src="<?php echo base_url('assets/js/my-works-lane-status.js'); ?>"></script>

<style>
#unifiedDashboardTabs {
  border-bottom: none !important;
}
</style>

<div class="mw-unified-shell">
<div class="mw-unified-head card border-0 shadow-sm">
  <div class="mw-unified-head-inner">
    <div class="mw-unified-head-brand">
      <span class="mw-unified-head-icon" aria-hidden="true">
        <i class="oms-icon-brain"></i>
      </span>
      <div class="mw-unified-head-title-row">
        <h1 class="mw-unified-head-title">Second Brain</h1>
        <div class="mw-unified-head-week">
          <i class="bi bi-calendar3" aria-hidden="true"></i>
          <span><?php echo esc_view(my_works_dashboard_week_range_label(), ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php
          $this->load->helper('my_works_status');
          $unified_status_legend = my_works_status_records();
        ?>
        <?php if (!empty($unified_status_legend)): ?>
        <div class="mw-unified-head-status-legend" aria-label="Work item statuses">
          <?php foreach ($unified_status_legend as $st): ?>
            <?php
              // Closed/Complete is not shown on Overview planning columns — keep legend aligned.
              if (my_works_status_is_closed($st->code)) {
                  continue;
              }
              $hex_color = my_works_status_hex_color($st->code);
            ?>
            <span class="mw-unified-head-status-item"
                  style="background-color: <?php echo esc_view($hex_color, ENT_QUOTES, 'UTF-8'); ?>12;
                         color: <?php echo esc_view($hex_color, ENT_QUOTES, 'UTF-8'); ?>;
                         border-color: <?php echo esc_view($hex_color, ENT_QUOTES, 'UTF-8'); ?>28;">
              <?php echo esc_view((string) $st->name, ENT_QUOTES, 'UTF-8'); ?>
            </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <button type="button"
                class="btn btn-outline-secondary btn-sm mw-unified-reset-btn"
                id="mwUnifiedResetBtn"
                title="Reset Second Brain — clear filters and reload Overview"
                aria-label="Reset Second Brain">
          <i class="bi bi-arrow-clockwise me-1"></i>Reset
        </button>
      </div>
    </div>
  </div>
</div>

<div class="mw-unified-toolbar card border-0 shadow-sm">
  <div class="mw-unified-toolbar-inner">
  <?php
  $can_add = function_exists('has_module_access') && (has_module_access('my_works_add') || has_module_access('my_works'));
  $can_req_list = function_exists('has_module_access') && (has_module_access('requirements_list') || has_module_access('requirements'));
  $can_req_add = function_exists('has_module_access') && (has_module_access('requirements_add') || has_module_access('requirements'));
  $can_clients_tab = function_exists('has_module_access') && (has_module_access('clients_list') || has_module_access('clients_view') || has_module_access('clients'));
  $redirectBack = 'my-works' . safe_query_suffix();
  $quickAddUrl = site_url('my-works/quick-add') . '?redirect=' . rawurlencode($redirectBack);
  $reqCreateUrl = site_url('requirements/create') . '?redirect=' . rawurlencode('my-works');
  $complete_view_on = !empty($complete_view_on);
  $show_complete_toggle = in_array($active_tab, array('project-dashboard', 'team-dashboard'), true);
  ?>
  <ul class="nav nav-pills mw-unified-tabs" id="unifiedDashboardTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'overview' ? 'active' : ''; ?>" 
              id="tab-overview" data-tab="overview" type="button" role="tab">
        <i class="bi bi-speedometer2 me-1"></i>Overview
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'daily-pulse' ? 'active' : ''; ?>"
              id="tab-daily-pulse" data-tab="daily-pulse" type="button" role="tab"
              title="Daily Pulse">
        <i class="bi bi-activity me-1"></i>Daily Pulse
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'project-dashboard' ? 'active' : ''; ?>" 
              id="tab-project-dashboard" data-tab="project-dashboard" type="button" role="tab"
              title="Project Dashboard">
        <i class="bi bi-columns-gap me-1"></i>Projects
      </button>
    </li>
    <?php if ($can_clients_tab): ?>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'clients' ? 'active' : ''; ?>"
              id="tab-clients" data-tab="clients" type="button" role="tab"
              title="Clients">
        <i class="bi bi-building me-1"></i>Clients
      </button>
    </li>
    <?php endif; ?>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'team-dashboard' ? 'active' : ''; ?>" 
              id="tab-team-dashboard" data-tab="team-dashboard" type="button" role="tab"
              title="Team Dashboard">
        <i class="bi bi-people me-1"></i>Team
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'list' ? 'active' : ''; ?>" 
              id="tab-list" data-tab="list" type="button" role="tab">
        <i class="bi bi-list-ul me-1"></i>List
      </button>
    </li>
    <?php if ($can_req_list): ?>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'requirements' ? 'active' : ''; ?>" 
              id="tab-requirements" data-tab="requirements" type="button" role="tab"
              title="Requirements">
        <i class="bi bi-clipboard-check me-1"></i>Reqs
      </button>
    </li>
    <?php endif; ?>
  </ul>

  <div class="mw-unified-toolbar-actions">
    <div class="mw-unified-complete-toggle<?php echo $show_complete_toggle ? '' : ' d-none'; ?>" id="mwDashCompleteToggleWrap">
      <div class="form-check form-switch mb-0">
        <input class="form-check-input" type="checkbox" role="switch" id="mwDashCompleteToggle"<?php echo $complete_view_on ? ' checked' : ''; ?>>
        <label class="form-check-label" for="mwDashCompleteToggle">Completed</label>
      </div>
    </div>
    <?php if ($can_add): ?>
    <a class="btn btn-outline-primary btn-sm mw-unified-action-btn" href="<?php echo esc_view($quickAddUrl); ?>" title="Quick add — full screen with rich text and attachments">
      <i class="bi bi-lightning-charge-fill me-1"></i>Quick
    </a>
    <a class="btn btn-primary btn-sm mw-unified-action-btn" href="<?php echo site_url('my-works/create'); ?>" title="Create Task">
      <i class="bi bi-plus-lg me-1"></i>New Task
    </a>
    <a class="btn btn-outline-primary btn-sm mw-unified-action-btn" href="<?php echo site_url('my-works/template-tasks'); ?>" title="Template Task">
      <i class="bi bi-collection me-1"></i>Template
    </a>
    <?php endif; ?>
    <?php if ($can_req_add): ?>
    <a class="btn btn-outline-primary btn-sm mw-unified-action-btn" href="<?php echo esc_view($reqCreateUrl, ENT_QUOTES, 'UTF-8'); ?>" title="Add Requirement">
      <i class="bi bi-plus-lg me-1"></i>Add Req
    </a>
    <?php endif; ?>
  </div>
  </div>
</div>
</div>

<div class="tab-content mw-unified-tab-content" id="unifiedDashboardContent">
  <!-- Overview Tab -->
  <div class="tab-pane fade <?php echo $active_tab === 'overview' ? 'show active' : ''; ?>" id="pane-overview" role="tabpanel">
    <div class="tab-loading-spinner text-center py-5" style="display: none;">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div class="tab-pane-content"></div>
  </div>

  <!-- Daily Pulse Tab -->
  <div class="tab-pane fade <?php echo $active_tab === 'daily-pulse' ? 'show active' : ''; ?>" id="pane-daily-pulse" role="tabpanel">
    <div class="tab-loading-spinner text-center py-5" style="display: none;">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div class="tab-pane-content"></div>
  </div>
  
  <!-- Project Dashboard Tab -->
  <div class="tab-pane fade <?php echo $active_tab === 'project-dashboard' ? 'show active' : ''; ?>" id="pane-project-dashboard" role="tabpanel">
    <div class="tab-loading-spinner text-center py-5" style="display: none;">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div class="tab-pane-content"></div>
  </div>

  <?php if ($can_clients_tab): ?>
  <!-- Clients Tab -->
  <div class="tab-pane fade <?php echo $active_tab === 'clients' ? 'show active' : ''; ?>" id="pane-clients" role="tabpanel">
    <div class="tab-loading-spinner text-center py-5" style="display: none;">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div class="tab-pane-content"></div>
  </div>
  <?php endif; ?>
  
  <!-- Team Dashboard Tab -->
  <div class="tab-pane fade <?php echo $active_tab === 'team-dashboard' ? 'show active' : ''; ?>" id="pane-team-dashboard" role="tabpanel">
    <div class="tab-loading-spinner text-center py-5" style="display: none;">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div class="tab-pane-content"></div>
  </div>
  
  <!-- List Tab -->
  <div class="tab-pane fade <?php echo $active_tab === 'list' ? 'show active' : ''; ?>" id="pane-list" role="tabpanel">
    <div class="tab-loading-spinner text-center py-5" style="display: none;">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div class="tab-pane-content"></div>
  </div>

  <?php if ($can_req_list): ?>
  <!-- Requirements Tab -->
  <div class="tab-pane fade <?php echo $active_tab === 'requirements' ? 'show active' : ''; ?>" id="pane-requirements" role="tabpanel">
    <div class="tab-loading-spinner text-center py-5" style="display: none;">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div class="tab-pane-content"></div>
  </div>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var buttons = document.querySelectorAll('#unifiedDashboardTabs button');
  var panes = document.querySelectorAll('#unifiedDashboardContent .tab-pane');
  
  // Custom global form submit interception
  var originalSubmit = HTMLFormElement.prototype.submit;
  HTMLFormElement.prototype.submit = function() {
    var $form = $(this);
    var $pane = $form.closest('.tab-pane');
    if ($pane.length) {
      var event = new Event('submit', { cancelable: true, bubbles: true });
      this.dispatchEvent(event);
      if (!event.defaultPrevented) {
        originalSubmit.apply(this);
      }
    } else {
      originalSubmit.apply(this);
    }
  };

  function initDataTablesInPane($pane) {
    if (window.DataTable) {
      var mwTbl = $pane.find('#myWorksTable')[0];
      if (mwTbl && !mwTbl.classList.contains('dataTable') && mwTbl.dataset.dtInited !== '1') {
        new DataTable(mwTbl, {
          ordering: true,
          order: [[0, 'asc']],
          columnDefs: [
            { orderable: false, targets: [2, 6] }
          ]
        });
        mwTbl.dataset.dtInited = '1';
      }
      
      $pane.find('table.datatable, table.sortable-table').each(function() {
        var tbl = this;
        if (!tbl.classList.contains('dataTable') && tbl.dataset.dtInited !== '1') {
          new DataTable(tbl);
          tbl.dataset.dtInited = '1';
        }
      });
    }
  }

  function getActiveUnifiedTab() {
    var activeBtn = document.querySelector('#unifiedDashboardTabs button.active');
    return activeBtn ? activeBtn.getAttribute('data-tab') : '';
  }

  var MW_STATE_KEY = 'mw_unified_tab_state_v1';
  var MW_FILTER_KEYS = [
    'created_for', 'project_id', 'q', 'view', 'section', 'status', 'tag',
    'client_id', 'work_type', 'involvement', 'urgent_only', 'important_only',
    'overdue_only', 'complete_view', 'created_by', 'department_id', 'user_id',
    'client_type'
  ];
  // Tab-owned params — never copy these from the shell URL onto another tab.
  var MW_TAB_OWNED_KEYS = ['view', 'section'];

  function readPersistedState() {
    try {
      var raw = sessionStorage.getItem(MW_STATE_KEY);
      return raw ? JSON.parse(raw) : { tabs: {} };
    } catch (e) {
      return { tabs: {} };
    }
  }

  function writePersistedState(state) {
    try {
      sessionStorage.setItem(MW_STATE_KEY, JSON.stringify(state));
    } catch (e) {}
  }

  function saveTabPaneUrl(tabName, paneUrl) {
    if (!tabName || !paneUrl) {
      return;
    }
    var state = readPersistedState();
    state.activeTab = tabName;
    state.tabs = state.tabs || {};
    state.tabs[tabName] = paneUrl;
    writePersistedState(state);
  }

  function getSavedTabPaneUrl(tabName) {
    var state = readPersistedState();
    if (state.tabs && state.tabs[tabName]) {
      return state.tabs[tabName];
    }
    return '';
  }

  function mergeQueryIntoUrl(urlString, queryData) {
    try {
      var url = new URL(urlString, window.location.origin);
      if (queryData) {
        var params = (typeof queryData === 'string')
          ? new URLSearchParams(queryData)
          : new URLSearchParams(queryData);
        params.forEach(function(value, key) {
          if (key === 'embed' || key === 'parent_tab') {
            return;
          }
          url.searchParams.set(key, value);
        });
      }
      url.searchParams.delete('embed');
      url.searchParams.delete('parent_tab');
      return url.pathname + url.search + url.hash;
    } catch (e) {
      return urlString;
    }
  }

  function defaultUrlForTab(tabName) {
    var map = {
      'overview': '<?php echo site_url("my-works?view=overview"); ?>',
      'daily-pulse': '<?php echo site_url("my-works/daily-pulse"); ?>',
      'project-dashboard': '<?php echo site_url("projects/dashboard"); ?>',
      'clients': '<?php echo site_url("clients/dashboard"); ?>',
      'team-dashboard': '<?php echo site_url("tasks/my-dashboard"); ?>',
      'list': '<?php echo site_url("my-works?view=list"); ?>',
      'requirements': '<?php echo site_url("requirements"); ?>'
    };
    return map[tabName] || map.overview;
  }

  function applyParentFiltersToUrl(urlString) {
    try {
      var url = new URL(urlString, window.location.origin);
      var parent = new URLSearchParams(window.location.search);
      MW_FILTER_KEYS.forEach(function(key) {
        if (MW_TAB_OWNED_KEYS.indexOf(key) >= 0) {
          return;
        }
        if (!parent.has(key)) {
          return;
        }
        var value = parent.get(key);
        if (value === null || value === '') {
          return;
        }
        // Keep zeros only for flags that mean "all" when absent; skip empty selects.
        if ((key === 'created_for' || key === 'project_id' || key === 'client_id' || key === 'user_id' || key === 'department_id') && value === '0') {
          url.searchParams.delete(key);
          return;
        }
        url.searchParams.set(key, value);
      });
      return forceTabViewParams(url.pathname + url.search + url.hash, getActiveUnifiedTab());
    } catch (e) {
      return urlString;
    }
  }

  /**
   * Overview vs List share /my-works — always pin the correct view for the tab.
   * (Parent ?view=overview used to overwrite List and show Overview UI.)
   */
  function forceTabViewParams(urlString, tabName) {
    try {
      var url = new URL(urlString, window.location.origin);
      if (url.pathname.indexOf('/my-works') >= 0) {
        if (tabName === 'list') {
          url.searchParams.set('view', 'list');
          url.searchParams.delete('section');
        } else if (tabName === 'overview') {
          url.searchParams.set('view', 'overview');
        }
      }
      return url.pathname + url.search + url.hash;
    } catch (e) {
      return urlString;
    }
  }

  function resolveTabLoadUrl(tabName) {
    var saved = getSavedTabPaneUrl(tabName);
    var base = saved || applyParentFiltersToUrl(defaultUrlForTab(tabName));
    return forceTabViewParams(base, tabName);
  }

  function syncParentUrlFromPane(tabName, paneUrl) {
    var params = new URLSearchParams(window.location.search);
    if (tabName) {
      params.set('tab', tabName);
    }
    MW_FILTER_KEYS.forEach(function(key) {
      params.delete(key);
    });
    try {
      var loaded = new URL(paneUrl, window.location.origin);
      MW_FILTER_KEYS.forEach(function(key) {
        if (!loaded.searchParams.has(key)) {
          return;
        }
        var value = loaded.searchParams.get(key);
        if (value === null || value === '') {
          return;
        }
        if ((key === 'created_for' || key === 'project_id' || key === 'client_id') && value === '0') {
          return;
        }
        params.set(key, value);
      });
    } catch (e) {}
    applyCompleteViewParam(params);
    var query = params.toString();
    var nextUrl = window.location.pathname + (query ? '?' + query : '');
    window.history.replaceState({ path: nextUrl, tab: tabName }, '', nextUrl);
  }

  function getEmbedUrl(urlString) {
    try {
      var url = new URL(urlString, window.location.origin);
      url.searchParams.set('embed', '1');
      var tab = getActiveUnifiedTab();
      if (tab) {
        url.searchParams.set('parent_tab', tab);
      }
      applyCompleteViewParam(url);
      return url.toString();
    } catch(e) {
      var cleanUrl = urlString.replace(/([?&])embed=[^&]*/, '');
      var out = cleanUrl + (cleanUrl.indexOf('?') >= 0 ? '&' : '?') + 'embed=1';
      var tab = getActiveUnifiedTab();
      if (tab) {
        out += '&parent_tab=' + encodeURIComponent(tab);
      }
      var toggle = document.getElementById('mwDashCompleteToggle');
      if (toggle && toggle.checked) {
        out += '&complete_view=1';
      }
      return out;
    }
  }

  function applyCompleteViewParam(urlOrParams) {
    var toggle = document.getElementById('mwDashCompleteToggle');
    var params = urlOrParams && urlOrParams.searchParams ? urlOrParams.searchParams : urlOrParams;
    if (!params || typeof params.set !== 'function') {
      return;
    }
    if (toggle && toggle.checked) {
      params.set('complete_view', '1');
    } else {
      params.delete('complete_view');
    }
  }

  function updateCompleteToggleVisibility() {
    var tab = getActiveUnifiedTab();
    var wrap = document.getElementById('mwDashCompleteToggleWrap');
    if (wrap) {
      wrap.classList.toggle('d-none', tab !== 'project-dashboard' && tab !== 'team-dashboard');
    }
  }

  function syncToggleFromUrl() {
    var toggle = document.getElementById('mwDashCompleteToggle');
    if (!toggle) {
      return;
    }
    var params = new URLSearchParams(window.location.search);
    toggle.checked = params.get('complete_view') === '1';
  }

  function syncPageUrlWithCompleteToggle() {
    var toggle = document.getElementById('mwDashCompleteToggle');
    if (!toggle) {
      return;
    }
    var params = new URLSearchParams(window.location.search);
    var activeTab = getActiveUnifiedTab();
    if (activeTab) {
      params.set('tab', activeTab);
    }
    if (toggle.checked) {
      params.set('complete_view', '1');
    } else {
      params.delete('complete_view');
    }
    var query = params.toString();
    var nextUrl = window.location.pathname + (query ? '?' + query : '');
    window.history.replaceState({ path: nextUrl }, '', nextUrl);
  }

  function reloadActiveDashboardTab() {
    var tab = getActiveUnifiedTab();
    if (tab !== 'project-dashboard' && tab !== 'team-dashboard') {
      return;
    }
    var $pane = $('#pane-' + tab);
    var url = $pane.data('last-loaded-url');
    if (!url) {
      if (tab === 'project-dashboard') {
        url = '<?php echo site_url("projects/dashboard"); ?>';
      } else if (tab === 'team-dashboard') {
        url = '<?php echo site_url("tasks/my-dashboard"); ?>';
      }
    }
    if (url) {
      try {
        var parsed = new URL(url, window.location.origin);
        applyCompleteViewParam(parsed);
        url = parsed.pathname + parsed.search;
      } catch (e) {}
      loadTabContent($pane, url, 'GET');
    }
  }

  function resolveUnifiedBackUrl(href) {
    var activeTab = getActiveUnifiedTab();
    if (activeTab !== 'team-dashboard') {
      return href;
    }
    try {
      var url = new URL(href, window.location.origin);
      var path = url.pathname.replace(/\/+$/, '');
      var isMyWorksRoot = /\/my-works$/i.test(path);
      if (isMyWorksRoot) {
        return '<?php echo site_url("tasks/my-dashboard"); ?>';
      }
    } catch (e) {
      if (/\/my-works\/?(\?|$)/i.test(href) && href.indexOf('my-works/') < 0) {
        return '<?php echo site_url("tasks/my-dashboard"); ?>';
      }
    }
    return href;
  }

  function loadTabContent($pane, url, method, data) {
    var $content = $pane.find('.tab-pane-content');
    var $spinner = $pane.find('.tab-loading-spinner');
    var upperMethod = (method || 'GET').toUpperCase();
    var tabName = ($pane.attr('id') || '').replace(/^pane-/, '') || getActiveUnifiedTab();
    var resolvedUrl = url;

    if (upperMethod === 'GET') {
      resolvedUrl = forceTabViewParams(mergeQueryIntoUrl(url, data || {}), tabName);
      $pane.data('last-loaded-url', resolvedUrl);
      saveTabPaneUrl(tabName, resolvedUrl);
      syncParentUrlFromPane(tabName, resolvedUrl);
    }

    $content.hide();
    $spinner.show();

    $.ajax({
      url: getEmbedUrl(resolvedUrl),
      type: upperMethod,
      data: upperMethod === 'GET' ? {} : (data || {}),
      cache: false,
      success: function(html) {
        // After POST redirects, full layout HTML can land here if embed was dropped.
        // Reload last good embed URL instead of nesting chrome inside the tab.
        if (typeof html === 'string'
            && (html.indexOf('id="appSidebar"') >= 0 || html.indexOf('id="sidebarShell"') >= 0)
            && upperMethod !== 'GET') {
          var fallback = $pane.data('last-loaded-url');
          if (fallback) {
            loadTabContent($pane, fallback, 'GET');
            return;
          }
        }
        $content.html(html);
        $spinner.hide();
        $content.show();
        initDataTablesInPane($pane);
        if (typeof window.initPulseNoteTooltips === 'function') {
          window.initPulseNoteTooltips($pane[0]);
        }
      },
      error: function() {
        $content.html('<div class="alert alert-danger m-3">Failed to load content. Please try again.</div>');
        $spinner.hide();
        $content.show();
      }
    });
  }

  // Intercept forms inside the tab content natively
  document.getElementById('unifiedDashboardContent').addEventListener('submit', function(e) {
    var form = e.target;
    if (form.tagName === 'FORM') {
      if (form.classList && form.classList.contains('mw-dash-status-form')) {
        e.preventDefault();
        e.stopPropagation();
        return;
      }
      if (form.getAttribute('enctype') === 'multipart/form-data') {
        return;
      }
      e.preventDefault();
      var $form = $(form);
      var $pane = $form.closest('.tab-pane');
      var url = $form.attr('action') || window.location.href;
      var method = ($form.attr('method') || 'GET').toUpperCase();
      var data = $form.serialize();
      
      // If updating status, handle response as JSON and reload the current view
      if (url.indexOf('update-status') >= 0) {
        $.ajax({
          url: getEmbedUrl(url),
          type: method,
          data: data,
          success: function(resp) {
            var reloadUrl = $pane.data('last-loaded-url') || window.location.href;
            loadTabContent($pane, reloadUrl, 'GET');
          },
          error: function() {
            $pane.find('.tab-pane-content').html('<div class="alert alert-danger m-3">Failed to update status. Please try again.</div>');
          }
        });
        return;
      }
      
      loadTabContent($pane, url, method, data);
    }
  }, true);

  // Intercept dashboard links natively
  document.getElementById('unifiedDashboardContent').addEventListener('click', function(e) {
    var anchor = e.target.closest('a');
    if (anchor) {
      var href = anchor.getAttribute('href');
      if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
        return;
      }
      
      var isDashboardLink = href.indexOf('my-works') >= 0 || 
                            href.indexOf('projects/dashboard') >= 0 ||
                            href.indexOf('tasks/my-dashboard') >= 0 ||
                            href.indexOf('clients/dashboard') >= 0 ||
                            href.indexOf('requirements') >= 0 ||
                            href.indexOf('daily-pulse') >= 0;
                            
      if (isDashboardLink) {
        e.preventDefault();
        var $pane = $(anchor).closest('.tab-pane');
        loadTabContent($pane, resolveUnifiedBackUrl(href), 'GET');
      }
    }
  }, true);

  // Handle Tab Pill Buttons click
  buttons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      buttons.forEach(function(b) { b.classList.remove('active'); });
      panes.forEach(function(p) { p.classList.remove('active', 'show'); });

      btn.classList.add('active');
      var tabName = btn.getAttribute('data-tab');
      var $pane = $('#pane-' + tabName);
      $pane.addClass('active show');
      updateCompleteToggleVisibility();

      var $content = $pane.find('.tab-pane-content');
      var forceReload = btn.getAttribute('data-force-reload') === '1';
      btn.removeAttribute('data-force-reload');

      var shouldLoad = forceReload || $content.children().length === 0;
      if (shouldLoad) {
        var initialUrl = resolveTabLoadUrl(tabName);
        // Always refresh projects/team/clients/reqs so complete_view and remote state stay current.
        if (tabName === 'project-dashboard' || tabName === 'team-dashboard' || tabName === 'clients' || tabName === 'requirements') {
          initialUrl = applyParentFiltersToUrl(getSavedTabPaneUrl(tabName) || defaultUrlForTab(tabName));
        }
        loadTabContent($pane, initialUrl, 'GET');
      } else {
        var existingUrl = $pane.data('last-loaded-url') || getSavedTabPaneUrl(tabName) || defaultUrlForTab(tabName);
        saveTabPaneUrl(tabName, existingUrl);
        syncParentUrlFromPane(tabName, existingUrl);
      }
    });
  });

  var completeToggle = document.getElementById('mwDashCompleteToggle');
  if (completeToggle) {
    completeToggle.addEventListener('change', function() {
      syncPageUrlWithCompleteToggle();
      reloadActiveDashboardTab();
    });
  }

  syncToggleFromUrl();
  updateCompleteToggleVisibility();

  var resetBtn = document.getElementById('mwUnifiedResetBtn');
  if (resetBtn) {
    resetBtn.addEventListener('click', function() {
      try {
        sessionStorage.removeItem(MW_STATE_KEY);
      } catch (e) {}
      // Hard reset: Overview, no filters, no complete_view.
      window.location.href = '<?php echo site_url("my-works?tab=overview"); ?>';
    });
  }

  // Restore active tab + filters after refresh (tab already marked active by PHP from ?tab=).
  var initialTabBtn = document.querySelector('#unifiedDashboardTabs button.active');
  if (initialTabBtn) {
    initialTabBtn.setAttribute('data-force-reload', '1');
    initialTabBtn.click();
  }

  window.addEventListener('popstate', function() {
    var params = new URLSearchParams(window.location.search);
    var tabName = params.get('tab') || 'overview';
    var btn = document.querySelector('#unifiedDashboardTabs button[data-tab="' + tabName + '"]');
    if (btn) {
      btn.setAttribute('data-force-reload', '1');
      btn.click();
    }
  });
});
</script>

<?php $this->load->view('partials/footer'); ?>
