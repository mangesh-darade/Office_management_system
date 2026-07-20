<?php
$this->load->view('partials/header', array(
    'title' => 'SPL',
    'extra_css' => array('assets/css/spl.css'),
));

$active_tab = isset($active_tab) ? (string) $active_tab : 'overview';
$approval_counts = isset($approval_counts) ? $approval_counts : array('pending' => 0, 'approved' => 0, 'rejected' => 0);
$reward_period = isset($reward_period) ? $reward_period : spl_normalize_reward_period($this->input->get('reward_period') ?: 'week');
$reward_bounds = isset($reward_bounds) ? $reward_bounds : spl_reward_period_bounds($reward_period);
?>

<div class="spl-unified-shell">
  <div class="spl-dash-page spl-dash-page--compact spl-dash-page--layout">
    <header class="spl-dash-header">
      <div class="spl-dash-header-brand">
        <div class="spl-dash-logo"><i class="bi bi-trophy-fill"></i></div>
        <div>
          <div class="spl-dash-brand-title">SATERI PREMIER LEAGUE</div>
          <div class="spl-dash-brand-sub"><?php echo esc_view($season['name'], ENT_QUOTES, 'UTF-8'); ?> &bull; <?php echo esc_view($season['label'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </div>
    </header>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 mb-2" role="alert">
      <?php echo esc_view($this->session->flashdata('success')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2 mb-2" role="alert">
      <?php echo esc_view($this->session->flashdata('error')); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="spl-unified-toolbar card border-0 shadow-sm">
      <ul class="nav nav-pills flex-nowrap spl-unified-tabs" id="splUnifiedTabs" role="tablist">
        <?php if (spl_tab_allowed('overview')): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $active_tab === 'overview' ? 'active' : ''; ?>" type="button" role="tab" data-tab="overview" id="spl-tab-btn-overview">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </button>
        </li>
        <?php endif; ?>
        <?php if (!empty($can_my_reward)): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $active_tab === 'my-reward' ? 'active' : ''; ?>" type="button" role="tab" data-tab="my-reward" id="spl-tab-btn-my-reward">
            <i class="bi bi-star me-1"></i>My Reward
          </button>
        </li>
        <?php endif; ?>
        <?php if (!empty($can_levels)): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $active_tab === 'levels' ? 'active' : ''; ?>" type="button" role="tab" data-tab="levels" id="spl-tab-btn-levels">
            <i class="bi bi-bar-chart-steps me-1"></i>Leaderboard
          </button>
        </li>
        <?php endif; ?>
        <?php if (!empty($can_submit)): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $active_tab === 'activity' ? 'active' : ''; ?>" type="button" role="tab" data-tab="activity" id="spl-tab-btn-activity">
            <i class="bi bi-plus-circle me-1"></i>Submit Activity
          </button>
        </li>
        <?php endif; ?>
        <?php if (!empty($can_approve)): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $active_tab === 'approvals' ? 'active' : ''; ?>" type="button" role="tab" data-tab="approvals" id="spl-tab-btn-approvals">
            <i class="bi bi-check2-square me-1"></i>Approvals
            <?php if (!empty($approval_counts['pending'])): ?>
            <span class="badge bg-danger ms-1"><?php echo (int) $approval_counts['pending']; ?></span>
            <?php endif; ?>
          </button>
        </li>
        <?php endif; ?>
        <?php if (!empty($can_rules)): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $active_tab === 'rules' ? 'active' : ''; ?>" type="button" role="tab" data-tab="rules" id="spl-tab-btn-rules">
            <i class="bi bi-list-check me-1"></i>Rules
          </button>
        </li>
        <?php endif; ?>
        <?php if (!empty($can_groups)): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?php echo $active_tab === 'groups' ? 'active' : ''; ?>" type="button" role="tab" data-tab="groups" id="spl-tab-btn-groups">
            <i class="bi bi-collection me-1"></i>Groups
          </button>
        </li>
        <?php endif; ?>
      </ul>
    </div>

    <?php if (spl_tab_allowed('overview') || !empty($can_groups)): ?>
    <div class="spl-unified-period-bar card border-0 shadow-sm<?php echo in_array($active_tab, array('overview', 'groups'), true) ? '' : ' d-none'; ?>" id="splUnifiedPeriodBar">
      <div class="spl-unified-period-bar-inner">
        <div class="spl-period-filter-heading">
          <span class="spl-period-filter-heading__icon" aria-hidden="true"><i class="bi bi-calendar3"></i></span>
          <div class="spl-period-filter-heading__text">
            <span class="spl-period-filter-heading__label">Score period</span>
            <span class="spl-period-filter-heading__value" id="splPeriodLabelText"><?php echo esc_view($reward_bounds['label'], ENT_QUOTES, 'UTF-8'); ?></span>
            <span class="spl-period-filter-heading__note">Scores count approved points only</span>
          </div>
        </div>
        <?php $this->load->view('spl/_period_filter', array('reward_period' => $reward_period)); ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($can_manage)): ?>
    <div class="spl-unified-groups-bar card border-0 shadow-sm<?php echo $active_tab === 'groups' ? '' : ' d-none'; ?>" id="splUnifiedGroupsBar">
      <div class="spl-unified-groups-bar-inner d-flex flex-wrap gap-2 align-items-center">
        <button type="button" class="btn btn-outline-primary btn-sm" id="splBoardEditBtn">
          <i class="bi bi-pencil-square me-1"></i>Edit board
        </button>
        <button type="submit" form="splBoardForm" class="btn btn-warning btn-sm d-none" id="splBoardSaveBtn">
          <i class="bi bi-check2-circle me-1"></i>Save all
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="splBoardCancelBtn">Cancel</button>
      </div>
    </div>
    <?php endif; ?>

    <div class="tab-content spl-unified-tab-content" id="splUnifiedContent">
      <?php if (spl_tab_allowed('overview')): ?>
      <div class="tab-pane fade <?php echo $active_tab === 'overview' ? 'show active' : ''; ?>" id="pane-overview" role="tabpanel">
        <div class="tab-loading-spinner text-center py-5" style="display:none;">
          <div class="spinner-border text-primary" role="status"></div>
        </div>
        <div class="tab-pane-content">
          <?php if ($active_tab === 'overview'): ?>
          <?php $this->load->view('spl/_dashboard_body', get_defined_vars()); ?>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($can_my_reward)): ?>
      <div class="tab-pane fade <?php echo $active_tab === 'my-reward' ? 'show active' : ''; ?>" id="pane-my-reward" role="tabpanel">
        <div class="tab-loading-spinner text-center py-5" style="display:none;"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="tab-pane-content"></div>
      </div>
      <?php endif; ?>

      <?php if (!empty($can_levels)): ?>
      <div class="tab-pane fade <?php echo $active_tab === 'levels' ? 'show active' : ''; ?>" id="pane-levels" role="tabpanel">
        <div class="tab-loading-spinner text-center py-5" style="display:none;"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="tab-pane-content"></div>
      </div>
      <?php endif; ?>

      <?php if (!empty($can_submit)): ?>
      <div class="tab-pane fade <?php echo $active_tab === 'activity' ? 'show active' : ''; ?>" id="pane-activity" role="tabpanel">
        <div class="tab-loading-spinner text-center py-5" style="display:none;"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="tab-pane-content"></div>
      </div>
      <?php endif; ?>

      <?php if (!empty($can_approve)): ?>
      <div class="tab-pane fade <?php echo $active_tab === 'approvals' ? 'show active' : ''; ?>" id="pane-approvals" role="tabpanel">
        <div class="tab-loading-spinner text-center py-5" style="display:none;"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="tab-pane-content"></div>
      </div>
      <?php endif; ?>

      <?php if (!empty($can_rules)): ?>
      <div class="tab-pane fade <?php echo $active_tab === 'rules' ? 'show active' : ''; ?>" id="pane-rules" role="tabpanel">
        <div class="tab-loading-spinner text-center py-5" style="display:none;"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="tab-pane-content"></div>
      </div>
      <?php endif; ?>

      <?php if (!empty($can_groups)): ?>
      <div class="tab-pane fade <?php echo $active_tab === 'groups' ? 'show active' : ''; ?>" id="pane-groups" role="tabpanel">
        <div class="tab-loading-spinner text-center py-5" style="display:none;"><div class="spinner-border text-primary" role="status"></div></div>
        <div class="tab-pane-content"></div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php $this->load->view('spl/_activity_detail_modal'); ?>

<?php
$spl_csrf_name = $this->security->get_csrf_token_name();
$spl_csrf_hash = $this->security->get_csrf_hash();
?>
<script>window.SPL_BOARD_CONFIG = { canManage: <?php echo !empty($can_manage) ? 'true' : 'false'; ?> };</script>
<script>
window.SPL_CONFIG = Object.assign(window.SPL_CONFIG || {}, {
  rulesUrl: <?php echo json_encode(site_url('spl/rules-by-category')); ?>,
  saveRuleUrl: <?php echo json_encode(site_url('spl/save-rule')); ?>,
  deleteRuleUrlBase: <?php echo json_encode(site_url('spl/delete-rule/')); ?>,
  importRulesUrl: <?php echo json_encode(site_url('spl/rules-import')); ?>,
  saveLevelUrl: <?php echo json_encode(site_url('spl/save-level')); ?>,
  canManageLevels: <?php echo !empty($can_rules) ? 'true' : 'false'; ?>,
  csrfName: <?php echo json_encode($spl_csrf_name); ?>,
  csrfHash: <?php echo json_encode($spl_csrf_hash); ?>,
  approveActivityUrlBase: <?php echo json_encode(site_url('spl/approve-activity/')); ?>,
  rejectActivityUrlBase: <?php echo json_encode(site_url('spl/reject-activity/')); ?>,
  updatePendingActivityUrlBase: <?php echo json_encode(site_url('spl/update-pending-activity/')); ?>,
  deletePendingActivityUrlBase: <?php echo json_encode(site_url('spl/delete-pending-activity/')); ?>,
  categories: []
});
</script>
<script src="<?php echo base_url('assets/js/spl.js?v=' . (is_file(FCPATH . 'assets/js/spl.js') ? filemtime(FCPATH . 'assets/js/spl.js') : '1')); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var buttons = document.querySelectorAll('#splUnifiedTabs button[data-tab]');
  var panes = document.querySelectorAll('#splUnifiedContent .tab-pane');
  var tabUrls = {
    overview: <?php echo json_encode(site_url('spl/dashboard?tab=overview')); ?>,
    'my-reward': <?php echo json_encode(site_url('spl?tab=my-reward')); ?>,
    levels: <?php echo json_encode(site_url('spl?tab=levels')); ?>,
    activity: <?php echo json_encode(site_url('spl?tab=activity')); ?>,
    approvals: <?php echo json_encode(site_url('spl?tab=approvals')); ?>,
    rules: <?php echo json_encode(site_url('spl?tab=rules' . ((!empty($rules_view) && $rules_view === 'categories') ? '&rules_view=categories' : ''))); ?>,
    groups: <?php echo json_encode(site_url('spl/groups')); ?>
  };

  function getActiveTab() {
    var activeBtn = document.querySelector('#splUnifiedTabs button.active');
    return activeBtn ? activeBtn.getAttribute('data-tab') : '';
  }

  function getEmbedUrl(urlString) {
    try {
      var url = new URL(urlString, window.location.origin);
      url.searchParams.set('embed', '1');
      return url.toString();
    } catch (e) {
      return urlString + (urlString.indexOf('?') >= 0 ? '&' : '?') + 'embed=1';
    }
  }

  function buildTabUrl(tabName) {
    var base = tabUrls[tabName] || '';
    try {
      var url = new URL(base, window.location.origin);
      var pageParams = new URLSearchParams(window.location.search);
      if (tabName === 'approvals') {
        var approvalView = pageParams.get('approval_view');
        if (approvalView) {
          url.searchParams.set('approval_view', approvalView);
        }
      }
      if (tabName === 'rules') {
        var rulesView = pageParams.get('rules_view');
        if (rulesView === 'categories') {
          url.searchParams.set('rules_view', 'categories');
        }
      }
      if (tabName === 'overview' || tabName === 'groups' || tabName === 'my-reward') {
        var rewardPeriod = pageParams.get('reward_period');
        if (rewardPeriod) {
          url.searchParams.set('reward_period', rewardPeriod);
        } else if (tabName === 'overview' || tabName === 'groups') {
          url.searchParams.set('reward_period', 'week');
        }
      }
      return url.toString();
    } catch (e) {
      return base;
    }
  }

  function updateGroupsBarVisibility() {
    var bar = document.getElementById('splUnifiedGroupsBar');
    if (!bar) {
      return;
    }
    var tab = getActiveTab();
    bar.classList.toggle('d-none', tab !== 'groups');
  }

  var periodLabels = <?php echo json_encode(spl_reward_period_options()); ?>;

  function getRewardPeriod() {
    var params = new URLSearchParams(window.location.search);
    return params.get('reward_period') || 'week';
  }

  function updatePeriodBarVisibility() {
    var bar = document.getElementById('splUnifiedPeriodBar');
    if (!bar) {
      return;
    }
    var tab = getActiveTab();
    bar.classList.toggle('d-none', tab !== 'overview' && tab !== 'groups');
  }

  function syncPeriodTabs(period) {
    document.querySelectorAll('[data-spl-period]').forEach(function(btn) {
      var isActive = btn.getAttribute('data-spl-period') === period;
      btn.classList.toggle('is-active', isActive);
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
    var labelNode = document.getElementById('splPeriodLabelText');
    if (labelNode && periodLabels[period]) {
      labelNode.textContent = periodLabels[period];
    }
  }

  function setRewardPeriod(period, reload) {
    if (!period || !periodLabels[period]) {
      period = 'week';
    }
    var params = new URLSearchParams(window.location.search);
    params.set('reward_period', period);
    var query = params.toString();
    var newUrl = window.location.pathname + (query ? '?' + query : '');
    window.history.pushState({ path: newUrl }, '', newUrl);
    syncPeriodTabs(period);
    if (reload !== false) {
      var tab = getActiveTab();
      if (tab === 'overview' || tab === 'groups') {
        var $pane = $('#pane-' + tab);
        $pane.find('.tab-pane-content').empty();
        loadTabContent($pane, buildTabUrl(tab), 'GET');
      }
    }
  }

  function runSplEmbeddedScripts($content) {
    $content.find('script').each(function() {
      if (this.src) {
        return;
      }
      var code = this.textContent || this.innerHTML || '';
      if (code.trim() === '') {
        return;
      }
      try {
        if (typeof jQuery !== 'undefined' && jQuery.globalEval) {
          jQuery.globalEval(code);
        } else {
          (new Function(code))();
        }
      } catch (e) {
        console.warn('SPL embed script failed:', e);
      }
    });
  }

  function initSplEmbeddedPane($content) {
    runSplEmbeddedScripts($content);
    if (window.initSplBoard && $content.find('#splBoard').length) {
      window.initSplBoard(document);
    }
    if (window.initSplRulesPanel && $content.find('#splRulesTable').length) {
      window.initSplRulesPanel($content[0]);
      if (window.adjustSplRulesTable) {
        window.adjustSplRulesTable($content[0]);
      }
    }
    if (window.initSplApprovalsPanel && $content.find('#spl-tab-approvals').length) {
      window.initSplApprovalsPanel($content[0]);
    }
    if (window.initSplActivityPanel && $content.find('#splSubmitForm').length) {
      window.initSplActivityPanel($content[0]);
    }
    if (window.initSplActivityTable) {
      window.initSplActivityTable($content[0]);
    }
    if (window.initSplLevelsPanel && $content.find('#splLevelsTable').length) {
      window.initSplLevelsPanel($content[0]);
    }
  }

  function loadTabContent($pane, url, method, data) {
    var $content = $pane.find('.tab-pane-content');
    var $spinner = $pane.find('.tab-loading-spinner');
    var upperMethod = (method || 'GET').toUpperCase();
    if (upperMethod === 'GET') {
      $pane.data('last-loaded-url', url);
    }
    $pane.addClass('is-tab-loading');
    $content.hide();
    $.ajax({
      url: getEmbedUrl(url),
      type: upperMethod,
      data: data || {},
      cache: false,
      success: function(html) {
        $content.html(html);
        $pane.removeClass('is-tab-loading');
        $content.show();
        initSplEmbeddedPane($content);
        if (window.DataTable) {
          $content.find('table.datatable, table.sortable-table').each(function() {
            var tbl = this;
            if (!tbl.classList.contains('dataTable') && tbl.dataset.dtInited !== '1') {
              new DataTable(tbl);
              tbl.dataset.dtInited = '1';
            }
          });
        }
      },
      error: function() {
        $content.html('<div class="alert alert-danger m-3">Failed to load content. Please try again.</div>');
        $pane.removeClass('is-tab-loading');
        $content.show();
      }
    });
  }

  function switchToTab(tabName, pushState) {
    var $btn = $('#spl-tab-btn-' + tabName);
    if (!$btn.length) {
      return;
    }
    buttons.forEach(function(b) { b.classList.remove('active'); });
    panes.forEach(function(p) { p.classList.remove('active', 'show'); });
    $btn.addClass('active');
    var $pane = $('#pane-' + tabName);
    $pane.addClass('active show');
    var $content = $pane.find('.tab-pane-content');
    if (tabName === 'overview' || tabName === 'groups') {
      loadTabContent($pane, buildTabUrl(tabName), 'GET');
    } else if ($content.children().length === 0) {
      loadTabContent($pane, buildTabUrl(tabName), 'GET');
    }
    if (pushState !== false) {
      var params = new URLSearchParams(window.location.search);
      if (tabName === 'overview') {
        params.delete('tab');
      } else {
        params.set('tab', tabName);
      }
      params.delete('embed');
      params.delete('parent_tab');
      var query = params.toString();
      var newUrl = window.location.pathname + (query ? '?' + query : '');
      window.history.pushState({ path: newUrl }, '', newUrl);
    }
    updateGroupsBarVisibility();
    updatePeriodBarVisibility();
    if (tabName === 'rules' && window.adjustSplRulesTable) {
      window.requestAnimationFrame(function () {
        var pane = document.getElementById('pane-rules');
        window.adjustSplRulesTable(pane || document);
      });
    }
    if (tabName === 'activity' && window.initSplActivityPanel) {
      window.requestAnimationFrame(function () {
        var pane = document.getElementById('pane-activity');
        var content = pane ? pane.querySelector('.tab-pane-content') : null;
        if (content && content.querySelector('#splSubmitForm')) {
          window.initSplActivityPanel(content);
        }
      });
    }
  }

  buttons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      switchToTab(btn.getAttribute('data-tab'), true);
    });
  });

  document.addEventListener('click', function(e) {
    var periodBtn = e.target.closest('[data-spl-period]');
    if (!periodBtn) {
      return;
    }
    e.preventDefault();
    setRewardPeriod(periodBtn.getAttribute('data-spl-period'));
  });

  document.getElementById('splUnifiedContent').addEventListener('submit', function(e) {
    var form = e.target;
    if (form.tagName !== 'FORM') {
      return;
    }
    if (form.getAttribute('enctype') === 'multipart/form-data') {
      return;
    }
    if (form.classList.contains('spl-approval-inline-form') || form.id === 'splApprovalModalActionForm') {
      return;
    }
    if (form.classList.contains('spl-category-save-form') || form.classList.contains('spl-category-toggle-form')) {
      return;
    }
    var formAction = (form.getAttribute('action') || '').toLowerCase();
    if (formAction.indexOf('save-category') !== -1 || formAction.indexOf('categories/toggle') !== -1) {
      return;
    }
    e.preventDefault();
    var $form = $(form);
    var $pane = $form.closest('[id^="pane-"]');
    if (!$pane.length) {
      $pane = $form.closest('.tab-pane');
    }
    var url = $form.attr('action') || window.location.href;
    var method = ($form.attr('method') || 'GET').toUpperCase();
    loadTabContent($pane, url, method, $form.serialize());
  }, true);

  document.getElementById('splUnifiedContent').addEventListener('click', function(e) {
    var anchor = e.target.closest('a');
    if (!anchor) {
      return;
    }
    var href = anchor.getAttribute('href');
    if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) {
      return;
    }
    try {
      var url = new URL(href, window.location.origin);
      var path = url.pathname.replace(/\/+$/, '');
      var isSplDashboard = /\/spl\/dashboard$/i.test(path);
      var isSplIndex = /\/spl$/i.test(path);
      var isSplGroups = /\/spl\/groups$/i.test(path);
      if (isSplDashboard || isSplIndex) {
        var tab = url.searchParams.get('tab');
        if (tab) {
          e.preventDefault();
          var pageParams = new URLSearchParams(window.location.search);
          url.searchParams.forEach(function(val, key) {
            if (key !== 'embed' && key !== 'parent_tab') {
              pageParams.set(key, val);
            }
          });
          if (tab === 'overview') {
            pageParams.delete('tab');
          } else {
            pageParams.set('tab', tab);
          }
          var query = pageParams.toString();
          var newUrl = window.location.pathname + (query ? '?' + query : '');
          window.history.pushState({ path: newUrl }, '', newUrl);
          switchToTab(tab, false);
          var $pane = $('#pane-' + tab);
          if (tab !== 'overview' || $pane.find('.tab-pane-content').children().length === 0) {
            loadTabContent($pane, buildTabUrl(tab), 'GET');
          }
          return;
        }
      }
      if (isSplGroups && !url.searchParams.get('embed')) {
        e.preventDefault();
        var $pane = $(anchor).closest('.tab-pane');
        loadTabContent($pane.length ? $pane : $('#pane-groups'), href, 'GET');
      }
    } catch (err) {}
  }, true);

  var initialTab = <?php echo json_encode($active_tab); ?>;
  syncPeriodTabs(getRewardPeriod());
  updateGroupsBarVisibility();
  updatePeriodBarVisibility();
  if (window.initSplBoard && document.getElementById('splBoardEditBtn')) {
    window.initSplBoard(document);
  }
  if (window.initSplRulesPanel && document.getElementById('splRulesTable')) {
    window.initSplRulesPanel(document);
  }
  if (initialTab && initialTab !== 'overview') {
    switchToTab(initialTab, false);
  }
});
</script>

<?php $this->load->view('partials/footer'); ?>
