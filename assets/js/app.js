// App JS: toasts and small UX helpers
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    // Auto-show bootstrap toasts
    var toastEls = [].slice.call(document.querySelectorAll('.toast'))
    toastEls.forEach(function(el){
      var t = new bootstrap.Toast(el, { delay: 3500 })
      t.show()
    })

    // Initialize DataTables if available (single source of truth). Avoid re-init.
    if (window.DataTable) {
      document.querySelectorAll('table.datatable').forEach(function(tbl){
        try {
          // Guard: detect if already initialized by checking .dataTable class or DT instance flag
          if (tbl.classList.contains('dataTable') || tbl.dataset.dtInited === '1') return;
          var col = tbl.getAttribute('data-order-col');
          var dir = (tbl.getAttribute('data-order-dir') || 'asc');
          var cfg = {
            responsive: true,
            paging: true,
            searching: true,
            lengthChange: true,
            order: []
          };
          if (col !== null && col !== '') {
            var cidx = parseInt(col, 10);
            if (!isNaN(cidx)) { cfg.order = [[cidx, dir]]; }
          }
          new DataTable(tbl, cfg);
          tbl.dataset.dtInited = '1';
        } catch(e) { console.warn('DataTable init failed:', e) }
      });
    }
  })
})();
