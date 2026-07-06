// App JS: toasts and small UX helpers
(function(){
  function initSidebarCollapse() {
    var toggle = document.getElementById('sidebarCollapseToggle');
    var shell = document.getElementById('sidebarShell');
    if (!toggle || !shell) {
      return;
    }

    var storageKey = 'oms_sidebar_collapsed';

    function applyCollapsed(collapsed) {
      document.body.classList.toggle('sidebar-collapsed', collapsed);
      toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      toggle.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    }

    var saved = false;
    try {
      saved = localStorage.getItem(storageKey) === '1';
    } catch (e) {}
    applyCollapsed(saved);

    toggle.addEventListener('click', function(ev) {
      ev.preventDefault();
      var collapsed = !document.body.classList.contains('sidebar-collapsed');
      applyCollapsed(collapsed);
      try {
        localStorage.setItem(storageKey, collapsed ? '1' : '0');
      } catch (e) {}
    });
  }

  function parseTableOrder(tbl) {
    var raw = tbl.getAttribute('data-order');
    if (raw) {
      try {
        var parsed = JSON.parse(raw);
        if (Array.isArray(parsed) && parsed.length) {
          return parsed;
        }
      } catch (e) {}
    }

    var col = tbl.getAttribute('data-order-col');
    var dir = (tbl.getAttribute('data-order-dir') || 'asc').toLowerCase();
    if (dir !== 'asc' && dir !== 'desc') {
      dir = 'asc';
    }
    if (col !== null && col !== '') {
      var cidx = parseInt(col, 10);
      if (!isNaN(cidx)) {
        return [[cidx, dir]];
      }
    }

    return [[0, 'asc']];
  }

  function detectNonSortableColumns(tbl) {
    var targets = [];
    var disableRaw = tbl.getAttribute('data-order-disable-cols');
    if (disableRaw) {
      disableRaw.split(',').forEach(function(part) {
        var n = parseInt(part.trim(), 10);
        if (!isNaN(n)) {
          targets.push(n);
        }
      });
    }

    var headers = tbl.querySelectorAll('thead th');
    headers.forEach(function(th, idx) {
      var txt = (th.textContent || '').trim().toLowerCase();
      if (txt === 'actions' || th.classList.contains('no-sort')) {
        targets.push(idx);
      }
      if (txt === '' && idx === headers.length - 1) {
        targets.push(idx);
      }
    });

    return targets.filter(function(v, i, arr) {
      return arr.indexOf(v) === i;
    });
  }

  function shouldSkipDataTable(tbl) {
    if (!tbl || !tbl.querySelector('thead')) {
      return true;
    }
    if (tbl.classList.contains('dataTable') || tbl.dataset.dtInited === '1') {
      return true;
    }
    if (tbl.hasAttribute('data-dt-manual') || tbl.hasAttribute('data-no-datatable')) {
      return true;
    }
    if (tbl.classList.contains('project-inline-table') || tbl.classList.contains('datatable-ajax')) {
      return true;
    }
    if (tbl.classList.contains('project-dash-task-table')) {
      return true;
    }
    if (tbl.hasAttribute('data-inline-type')) {
      return true;
    }
    return false;
  }

  function buildDataTableConfig(tbl, overrides) {
    overrides = overrides || {};
    var nonSort = detectNonSortableColumns(tbl);
    var cfg = {
      responsive: true,
      paging: false,
      searching: false,
      lengthChange: false,
      info: false,
      ordering: true,
      order: parseTableOrder(tbl),
      language: {
        emptyTable: 'No data available',
        zeroRecords: 'No matching records found'
      }
    };

    if (nonSort.length) {
      cfg.columnDefs = [{ orderable: false, targets: nonSort }];
    }

    if (overrides.columnDefs && overrides.columnDefs.length) {
      cfg.columnDefs = (cfg.columnDefs || []).concat(overrides.columnDefs);
    }

    Object.keys(overrides).forEach(function(key) {
      if (key !== 'columnDefs') {
        cfg[key] = overrides[key];
      }
    });

    return cfg;
  }

  function stripColspanPlaceholderRows(tbl) {
    tbl.querySelectorAll('tbody tr').forEach(function(tr) {
      var cells = tr.querySelectorAll('td');
      if (cells.length === 1 && cells[0].hasAttribute('colspan')) {
        tr.remove();
      }
    });
  }

  function initDataTable(tbl, overrides) {
    if (shouldSkipDataTable(tbl)) {
      return;
    }
    stripColspanPlaceholderRows(tbl);
    try {
      new DataTable(tbl, buildDataTableConfig(tbl, overrides));
      tbl.dataset.dtInited = '1';
    } catch (e) {
      try {
        var fallbackCfg = buildDataTableConfig(tbl, overrides);
        fallbackCfg.responsive = false;
        new DataTable(tbl, fallbackCfg);
        tbl.dataset.dtInited = '1';
      } catch (e2) {
        console.warn('DataTable init failed:', e2);
      }
    }
  }

  function collectSortableTables() {
    var seen = [];
    var selector = [
      'table.datatable',
      '.card table.table',
      '.table-responsive.card > table.table',
      '.card .table-responsive > table.table',
      '.card-body .table-responsive > table.table'
    ].join(', ');

    document.querySelectorAll(selector).forEach(function(tbl) {
      if (seen.indexOf(tbl) === -1) {
        seen.push(tbl);
      }
    });

    return seen;
  }

  document.addEventListener('DOMContentLoaded', function(){
    initSidebarCollapse();

    // Auto-show bootstrap toasts
    var toastEls = [].slice.call(document.querySelectorAll('.toast'))
    toastEls.forEach(function(el){
      var t = new bootstrap.Toast(el, { delay: 3500 })
      t.show()
    })

    // Initialize DataTables if available (single source of truth). Avoid re-init.
    if (window.DataTable) {
      var mwTbl = document.getElementById('myWorksTable');
      if (mwTbl) {
        initDataTable(mwTbl, {
          ordering: true,
          order: [[0, 'asc']],
          columnDefs: [
            { orderable: false, targets: [2, 5] }
          ]
        });
      }

      var tasksTbl = document.getElementById('tasksTable');
      if (tasksTbl) {
        initDataTable(tasksTbl, {
          responsive: true,
          paging: true,
          searching: true,
          lengthChange: true,
          info: true,
          pageLength: 25,
          lengthMenu: [10, 25, 50, 100],
          ordering: true,
          order: parseTableOrder(tasksTbl),
          columnDefs: [
            { responsivePriority: 1, targets: 2 },
            { responsivePriority: 2, targets: 0 },
            { responsivePriority: 3, targets: -1 }
          ]
        });
      }

      collectSortableTables().forEach(function(tbl){
        initDataTable(tbl);
      });
    }
  })
})();
