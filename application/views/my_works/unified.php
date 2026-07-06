<?php $this->load->view('partials/header', array(
  'title' => 'Second Brain',
  'extra_css' => array('assets/css/my-works.css', 'assets/css/project-dashboard.css')
)); ?>

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
            <?php $hex_color = my_works_status_hex_color($st->code); ?>
            <span class="mw-unified-head-status-item"
                  style="background-color: <?php echo esc_view($hex_color, ENT_QUOTES, 'UTF-8'); ?>12;
                         color: <?php echo esc_view($hex_color, ENT_QUOTES, 'UTF-8'); ?>;
                         border-color: <?php echo esc_view($hex_color, ENT_QUOTES, 'UTF-8'); ?>28;">
              <?php echo esc_view((string) $st->name, ENT_QUOTES, 'UTF-8'); ?>
            </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="mw-unified-toolbar card border-0 shadow-sm">
  <div class="mw-unified-toolbar-inner">
  <ul class="nav nav-pills flex-nowrap mw-unified-tabs" id="unifiedDashboardTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'overview' ? 'active' : ''; ?>" 
              id="tab-overview" data-tab="overview" type="button" role="tab">
        <i class="bi bi-speedometer2 me-1"></i>Overview
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'project-dashboard' ? 'active' : ''; ?>" 
              id="tab-project-dashboard" data-tab="project-dashboard" type="button" role="tab">
        <i class="bi bi-columns-gap me-1"></i>Project Dashboard
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'team-dashboard' ? 'active' : ''; ?>" 
              id="tab-team-dashboard" data-tab="team-dashboard" type="button" role="tab">
        <i class="bi bi-people me-1"></i>Team Dashboard
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php echo $active_tab === 'list' ? 'active' : ''; ?>" 
              id="tab-list" data-tab="list" type="button" role="tab">
        <i class="bi bi-list-ul me-1"></i>List
      </button>
    </li>
  </ul>

  <?php 
  $can_add = function_exists('has_module_access') && (has_module_access('my_works_add') || has_module_access('my_works'));
  $redirectBack = 'my-works';
  if (!empty($_SERVER['QUERY_STRING'])) {
    $redirectBack .= '?' . $_SERVER['QUERY_STRING'];
  }
  $quickAddUrl = site_url('my-works/quick-add') . '?redirect=' . rawurlencode($redirectBack);
  ?>
  <div class="mw-unified-toolbar-actions">
    <?php if ($can_add): ?>
    <a class="btn btn-outline-primary btn-sm mw-unified-action-btn" href="<?php echo esc_view($quickAddUrl); ?>" title="Quick add — full screen with rich text and attachments">
      <i class="bi bi-lightning-charge-fill me-1"></i>Quick add
    </a>
    <a class="btn btn-primary btn-sm mw-unified-action-btn" href="<?php echo site_url('my-works/create'); ?>">
      <i class="bi bi-plus-lg me-1"></i>Create Task
    </a>
    <a class="btn btn-outline-primary btn-sm mw-unified-action-btn" href="<?php echo site_url('my-works/template-tasks'); ?>">
      <i class="bi bi-collection me-1"></i>Template Task
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
  
  <!-- Project Dashboard Tab -->
  <div class="tab-pane fade <?php echo $active_tab === 'project-dashboard' ? 'show active' : ''; ?>" id="pane-project-dashboard" role="tabpanel">
    <div class="tab-loading-spinner text-center py-5" style="display: none;">
      <div class="spinner-border text-primary" role="status"></div>
    </div>
    <div class="tab-pane-content"></div>
  </div>
  
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
            { orderable: false, targets: [2, 5] }
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

  function getEmbedUrl(urlString) {
    try {
      // Use standard URL API to safely append/overwrite query parameters
      var url = new URL(urlString, window.location.origin);
      url.searchParams.set('embed', '1');
      return url.toString();
    } catch(e) {
      var cleanUrl = urlString.replace(/([?&])embed=[^&]*/, '');
      return cleanUrl + (cleanUrl.indexOf('?') >= 0 ? '&' : '?') + 'embed=1';
    }
  }

  function loadTabContent($pane, url, method, data) {
    var $content = $pane.find('.tab-pane-content');
    var $spinner = $pane.find('.tab-loading-spinner');
    
    // Store the last loaded GET URL so we can reload the page after form actions
    var upperMethod = (method || 'GET').toUpperCase();
    if (upperMethod === 'GET') {
      $pane.data('last-loaded-url', url);
    }
    
    $content.hide();
    $spinner.show();
    
    $.ajax({
      url: getEmbedUrl(url),
      type: upperMethod,
      data: data || {},
      success: function(html) {
        $content.html(html);
        $spinner.hide();
        $content.show();
        initDataTablesInPane($pane);
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
                            href.indexOf('tasks/my-dashboard') >= 0;
                            
      if (isDashboardLink) {
        e.preventDefault();
        var $pane = $(anchor).closest('.tab-pane');
        loadTabContent($pane, href, 'GET');
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
      
      // Lazy load only if empty
      var $content = $pane.find('.tab-pane-content');
      if ($content.children().length === 0) {
        var initialUrl = '';
        if (tabName === 'overview') {
          initialUrl = '<?php echo site_url("my-works?view=overview"); ?>';
        } else if (tabName === 'project-dashboard') {
          initialUrl = '<?php echo site_url("projects/dashboard"); ?>';
        } else if (tabName === 'team-dashboard') {
          initialUrl = '<?php echo site_url("tasks/my-dashboard"); ?>';
        } else if (tabName === 'list') {
          initialUrl = '<?php echo site_url("my-works?view=list"); ?>';
        }
        if (initialUrl) {
          loadTabContent($pane, initialUrl, 'GET');
        }
      }
      
      var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?tab=' + tabName;
      window.history.pushState({ path: newUrl }, '', newUrl);
    });
  });

  // Load the active tab initially
  var initialTabBtn = document.querySelector('#unifiedDashboardTabs button.active');
  if (initialTabBtn) {
    initialTabBtn.click();
  }
});
</script>

<?php $this->load->view('partials/footer'); ?>
