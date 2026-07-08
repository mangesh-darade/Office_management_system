<?php
$this->load->view('partials/header', array(
    'title' => 'SPL',
    'extra_css' => array('assets/css/spl.css'),
));

$active_tab = isset($active_tab) ? (string) $active_tab : 'overview';
$approval_counts = isset($approval_counts) ? $approval_counts : array('pending' => 0, 'approved' => 0, 'rejected' => 0);
?>

<div class="spl-unified-shell">
  <div class="spl-dash-page spl-dash-page--compact">
    <header class="spl-dash-header">
      <div class="spl-dash-header-brand">
        <div class="spl-dash-logo"><i class="bi bi-trophy-fill"></i></div>
        <div>
          <div class="spl-dash-brand-title">SATERI PREMIER LEAGUE</div>
          <div class="spl-dash-brand-sub"><?php echo esc_view($season['name'], ENT_QUOTES, 'UTF-8'); ?> &bull; <?php echo esc_view($season['label'], ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      </div>
    </header>

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

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-2 mt-2 mb-0"><?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-2 mt-2 mb-0"><?php echo esc_view((string) $this->session->flashdata('error'), ENT_QUOTES, 'UTF-8'); ?></div>
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
    rules: <?php echo json_encode(site_url('spl?tab=rules')); ?>,
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
      if (tabName === 'my-reward') {
        var rewardPeriod = pageParams.get('reward_period');
        if (rewardPeriod) {
          url.searchParams.set('reward_period', rewardPeriod);
        }
      }
      return url.toString();
    } catch (e) {
      return base;
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
    if (tabName === 'overview') {
      if ($content.children().length === 0) {
        loadTabContent($pane, tabUrls.overview, 'GET');
      }
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
  }

  buttons.forEach(function(btn) {
    btn.addEventListener('click', function() {
      switchToTab(btn.getAttribute('data-tab'), true);
    });
  });

  document.getElementById('splUnifiedContent').addEventListener('submit', function(e) {
    var form = e.target;
    if (form.tagName !== 'FORM') {
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
  if (initialTab && initialTab !== 'overview') {
    switchToTab(initialTab, false);
  }
});
</script>

<?php $this->load->view('partials/footer'); ?>
