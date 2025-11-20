<?php $this->load->view('partials/header', ['title' => 'Client DB Panel']); ?>
<?php if (!has_module_access('db')) { echo '<div class="alert alert-danger">Forbidden</div>'; $this->load->view('partials/footer'); return; } ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Client DB Panel</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-secondary btn-sm" href="<?php echo site_url('db'); ?>">Back to DB Manager</a>
  </div>
</div>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <label class="form-label">SQL File Path (physical file)</label>
    <input class="form-control" id="clientSqlPath" value="<?php echo isset($sql_file_default)?htmlspecialchars($sql_file_default):''; ?>" placeholder="C:\\path\\to\\dump.sql" />
    <div class="small text-muted mt-1">
      Set the master SQL file path once, then use the table below to open DB Compare for each client.
    </div>
  </div>
</div>

<?php if (isset($clients) && is_array($clients) && !empty($clients)): ?>
<div class="card shadow-soft">
  <div class="card-body">
    <h2 class="h6 mb-2">Clients and Databases</h2>
    <div class="d-flex justify-content-between align-items-center mb-2">
      <div class="small text-muted">Total clients: <?php echo count($clients); ?></div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnBulkCompare">Compare Selected</button>
        <button type="button" class="btn btn-outline-success btn-sm" id="btnBulkMigrate">Migrate Selected</button>
        <button type="button" class="btn btn-outline-danger btn-sm" id="btnBulkRevert">Revert File for Selected</button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-sm align-middle" id="clientDbTable">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAllClients"></th>
            <th>Client</th>
            <th>POS URL</th>
            <th>DB Name</th>
            <th style="width:220px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $cl): ?>
          <tr>
            <td><input type="checkbox" class="form-check-input js-client-select" value="<?php echo (int)$cl->id; ?>"
                       data-db-name="<?php echo htmlspecialchars(isset($cl->db_name)?$cl->db_name:''); ?>"
                       data-db-user="<?php echo htmlspecialchars(isset($cl->db_username)?$cl->db_username:''); ?>"
                       data-db-pass="<?php echo htmlspecialchars(isset($cl->db_password)?$cl->db_password:''); ?>"
                       data-pos-url="<?php echo htmlspecialchars(isset($cl->pos_url)?$cl->pos_url:''); ?>"
                       data-name="<?php echo htmlspecialchars($cl->company_name); ?>" /></td>
            <td><?php echo htmlspecialchars($cl->company_name); ?></td>
            <td>
              <?php if (!empty($cl->pos_url)): ?>
                <div style="max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                  <a href="<?php echo htmlspecialchars($cl->pos_url); ?>" target="_blank" rel="noopener" title="<?php echo htmlspecialchars($cl->pos_url); ?>">
                    <?php echo htmlspecialchars($cl->pos_url); ?>
                  </a>
                </div>
              <?php else: ?>
                <span class="text-muted">-</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars(isset($cl->db_name)?$cl->db_name:''); ?></td>
            <td class="text-end">
              <div class="btn-group btn-group-sm" role="group">
                <button type="button"
                        class="btn btn-outline-primary js-client-compare"
                        data-client-id="<?php echo (int)$cl->id; ?>"
                        data-db-name="<?php echo htmlspecialchars(isset($cl->db_name)?$cl->db_name:''); ?>"
                        data-db-user="<?php echo htmlspecialchars(isset($cl->db_username)?$cl->db_username:''); ?>"
                        data-db-pass="<?php echo htmlspecialchars(isset($cl->db_password)?$cl->db_password:''); ?>"
                        data-pos-url="<?php echo htmlspecialchars(isset($cl->pos_url)?$cl->pos_url:''); ?>"
                        data-name="<?php echo htmlspecialchars($cl->company_name); ?>">
                  Compare
                </button>
                <button type="button"
                        class="btn btn-outline-success js-client-migrate"
                        data-client-id="<?php echo (int)$cl->id; ?>"
                        data-db-name="<?php echo htmlspecialchars(isset($cl->db_name)?$cl->db_name:''); ?>"
                        data-db-user="<?php echo htmlspecialchars(isset($cl->db_username)?$cl->db_username:''); ?>"
                        data-db-pass="<?php echo htmlspecialchars(isset($cl->db_password)?$cl->db_password:''); ?>"
                        data-name="<?php echo htmlspecialchars($cl->company_name); ?>">
                  Migrate
                </button>
                <button type="button"
                        class="btn btn-outline-danger js-client-revert"
                        data-client-id="<?php echo (int)$cl->id; ?>"
                        data-db-name="<?php echo htmlspecialchars(isset($cl->db_name)?$cl->db_name:''); ?>"
                        data-db-user="<?php echo htmlspecialchars(isset($cl->db_username)?$cl->db_username:''); ?>"
                        data-db-pass="<?php echo htmlspecialchars(isset($cl->db_password)?$cl->db_password:''); ?>"
                        data-name="<?php echo htmlspecialchars($cl->company_name); ?>">
                  Revert
                </button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php else: ?>
<div class="alert alert-info">No clients found. Add clients first to use this panel.</div>
<?php endif; ?>

<div class="modal fade" id="clientCompareModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="clientCompareTitle">DB Differences</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="clientCompareSummary" class="small text-muted mb-2"></div>
        <pre id="clientCompareSql" class="bg-light border rounded p-2 small" style="white-space:pre-wrap;max-height:320px;overflow:auto;"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="btnClientCopySql">Copy SQL</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function(){
    var fileInput = document.getElementById('clientSqlPath');
    var table = document.getElementById('clientDbTable');
    if (!table) return;

    function selectedClientRows(){
      return Array.prototype.slice.call(table.querySelectorAll('.js-client-select:checked'));
    }

    function runCompareForClient(clientEl, triggerBtn){
      var fp = fileInput ? (fileInput.value || '') : '';
      if (!fp){ alert('Set SQL File Path first.'); return; }
      var dbName = clientEl.getAttribute('data-db-name') || '';
      var dbUser = clientEl.getAttribute('data-db-user') || '';
      var dbPass = clientEl.getAttribute('data-db-pass') || '';
      var name = clientEl.getAttribute('data-name') || '';
      if (!dbName){ alert('Client '+name+' has no DB Name configured.'); return; }
      var btn = triggerBtn || null;
      var originalText = btn ? (btn.textContent || 'Compare') : 'Compare';
      if (btn){ btn.disabled = true; btn.textContent = 'Comparing...'; }
      var body = 'file_path='+encodeURIComponent(fp)+
                 '&database='+encodeURIComponent(dbName)+
                 '&user='+encodeURIComponent(dbUser)+
                 '&pass='+encodeURIComponent(dbPass)+
                 '&client_id='+encodeURIComponent(clientEl.value||'')+
                 '&client_name='+encodeURIComponent(name||'');
      fetch('<?php echo site_url('db/compare/scan'); ?>', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: body
      }).then(function(r){ return r.json(); })
        .then(function(j){ renderCompareResult(j, name); })
        .catch(function(){ alert('Failed to scan differences for '+name); })
        .finally(function(){
          if (btn){ btn.disabled = false; btn.textContent = originalText; }
        });
    }

    function renderCompareResult(j, clientName){
      var titleEl = document.getElementById('clientCompareTitle');
      var sumEl = document.getElementById('clientCompareSummary');
      var sqlEl = document.getElementById('clientCompareSql');
      if (!j || !j.success){
        if (sumEl) sumEl.textContent = (j && j.message) ? j.message : 'Failed to scan.';
        if (sqlEl) sqlEl.textContent = '';
      } else {
        var ops = j.ops || [];
        var dbOnly = j.db_only || {};
        var dbOnlyTables = dbOnly.tables || [];
        var dbOnlyCols = dbOnly.columns || [];
        var createCount = ops.filter(function(o){ return o.type==='create_table'; }).length;
        var addColCount = ops.filter(function(o){ return o.type==='add_column'; }).length;
        if (titleEl) titleEl.textContent = clientName ? ('DB Differences — '+clientName) : 'DB Differences';
        if (sumEl){
          sumEl.textContent = 'File: '+(j.file_path||'')+
            ' • Target DB: '+(j.database||'')+
            ' • Create tables: '+createCount+
            ' • Add columns: '+addColCount+
            ' • DB-only tables: '+dbOnlyTables.length+
            ' • DB-only columns: '+dbOnlyCols.length;
        }
        var sqlParts = [];
        ops.filter(function(o){return o.type==='create_table';}).forEach(function(o){ if (o.sql) sqlParts.push(o.sql.trim().replace(/;?\s*$/, ';')); });
        ops.filter(function(o){return o.type==='add_column';}).forEach(function(o){ if (o.sql) sqlParts.push(o.sql.trim().replace(/;?\s*$/, ';')); });
        if (sqlEl) sqlEl.textContent = sqlParts.join('\n\n');
      }
      try {
        var modalEl = document.getElementById('clientCompareModal');
        if (window.bootstrap && modalEl){
          var m = bootstrap.Modal.getOrCreateInstance(modalEl);
          m.show();
        } else if (modalEl){
          modalEl.style.display='block';
        }
      } catch(e){}
    }

    function runMergeForClient(clientEl, triggerBtn){
      var fp = fileInput ? (fileInput.value || '') : '';
      if (!fp){ alert('Set SQL File Path first.'); return; }
      var dbName = clientEl.getAttribute('data-db-name') || '';
      var dbUser = clientEl.getAttribute('data-db-user') || '';
      var dbPass = clientEl.getAttribute('data-db-pass') || '';
      var name = clientEl.getAttribute('data-name') || '';
      if (!dbName){ alert('Client '+name+' has no DB Name configured.'); return; }
      if (!confirm('Migrate schema to client DB '+name+' ('+dbName+') ?')) return;
      var btn = triggerBtn || null;
      var originalText = btn ? (btn.textContent || 'Migrate') : 'Migrate';
      if (btn){ btn.disabled = true; btn.textContent = 'Migrating...'; }
      var body = 'file_path='+encodeURIComponent(fp)+
                 '&database='+encodeURIComponent(dbName)+
                 '&user='+encodeURIComponent(dbUser)+
                 '&pass='+encodeURIComponent(dbPass)+
                 '&client_id='+encodeURIComponent(clientEl.value||'')+
                 '&client_name='+encodeURIComponent(name||'');
      fetch('<?php echo site_url('db/compare/merge'); ?>', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: body
      }).then(function(r){ return r.json(); })
        .then(function(j){
          if (j && j.success){ alert('Migrated '+(j.applied||0)+' change(s) for '+name); }
          else { alert((j && j.message) ? j.message : 'Failed to migrate '+name); }
        })
        .catch(function(){ alert('Failed to migrate '+name); })
        .finally(function(){ if (btn){ btn.disabled = false; btn.textContent = originalText; } });
    }

    function runRevertForClient(clientEl, triggerBtn){
      var fp = fileInput ? (fileInput.value || '') : '';
      if (!fp){ alert('Set SQL File Path first.'); return; }
      var dbName = clientEl.getAttribute('data-db-name') || '';
      var dbUser = clientEl.getAttribute('data-db-user') || '';
      var dbPass = clientEl.getAttribute('data-db-pass') || '';
      var name = clientEl.getAttribute('data-name') || '';
      if (!dbName){ alert('Client '+name+' has no DB Name configured.'); return; }
      if (!confirm('Revert client DB '+name+' ('+dbName+') to match the SQL file? This will DROP tables/columns that are not in the file.')) return;
      var btn = triggerBtn || null;
      var originalText = btn ? (btn.textContent || 'Revert') : 'Revert';
      if (btn){ btn.disabled = true; btn.textContent = 'Reverting...'; }
      var body = 'file_path='+encodeURIComponent(fp)+
                 '&database='+encodeURIComponent(dbName)+
                 '&user='+encodeURIComponent(dbUser)+
                 '&pass='+encodeURIComponent(dbPass)+
                 '&client_id='+encodeURIComponent(clientEl.value||'')+
                 '&client_name='+encodeURIComponent(name||'');
      fetch('<?php echo site_url('db/compare/drop-db-only'); ?>', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: body
      }).then(function(r){ return r.json(); })
        .then(function(j){
          if (j && j.success){
            alert('Reverted DB '+name+': dropped '+(j.tables||0)+' table(s), '+(j.columns||0)+' column(s).');
          } else {
            alert((j && j.message) ? j.message : 'Failed to revert DB '+name);
          }
        })
        .catch(function(){ alert('Failed to revert DB '+name); })
        .finally(function(){ if (btn){ btn.disabled = false; btn.textContent = originalText; } });
    }

    // Per-row buttons
    table.querySelectorAll('.js-client-compare').forEach(function(btn){
      btn.addEventListener('click', function(){
        var id = this.getAttribute('data-client-id') || '';
        var rowCb = table.querySelector('.js-client-select[value="'+id+'"]');
        if (rowCb) runCompareForClient(rowCb, this);
      });
    });
    table.querySelectorAll('.js-client-migrate').forEach(function(btn){
      btn.addEventListener('click', function(){
        var id = this.getAttribute('data-client-id') || '';
        var rowCb = table.querySelector('.js-client-select[value="'+id+'"]');
        if (rowCb) runMergeForClient(rowCb, this);
      });
    });
    table.querySelectorAll('.js-client-revert').forEach(function(btn){
      btn.addEventListener('click', function(){
        var id = this.getAttribute('data-client-id') || '';
        var rowCb = table.querySelector('.js-client-select[value="'+id+'"]');
        if (rowCb) runRevertForClient(rowCb, this);
      });
    });

    // Select all
    var selectAll = document.getElementById('selectAllClients');
    if (selectAll){
      selectAll.addEventListener('change', function(){
        table.querySelectorAll('.js-client-select').forEach(function(cb){ cb.checked = selectAll.checked; });
      });
    }

    // Bulk buttons
    function bulkAction(fn){
      var rows = selectedClientRows();
      if (!rows.length){ alert('Select at least one client.'); return; }
      if (!confirm('Run this action for '+rows.length+' client(s)?')) return;
      (function next(i){
        if (i >= rows.length) return;
        fn(rows[i], function(){ next(i+1); });
      })(0);
    }

    var btnBulkCompare = document.getElementById('btnBulkCompare');
    if (btnBulkCompare){ btnBulkCompare.addEventListener('click', function(){
      var rows = selectedClientRows();
      if (!rows.length){ alert('Select at least one client.'); return; }
      // Just run compare for the first selected and show popup
      runCompareForClient(rows[0], null);
    }); }

    var btnBulkMigrate = document.getElementById('btnBulkMigrate');
    if (btnBulkMigrate){ btnBulkMigrate.addEventListener('click', function(){
      bulkAction(function(row, done){ runMergeForClient(row, null); setTimeout(done, 500); });
    }); }

    var btnBulkRevert = document.getElementById('btnBulkRevert');
    if (btnBulkRevert){ btnBulkRevert.addEventListener('click', function(){
      bulkAction(function(row, done){ runRevertForClient(row, null); setTimeout(done, 500); });
    }); }

    var btnCopySql = document.getElementById('btnClientCopySql');
    if (btnCopySql){
      btnCopySql.addEventListener('click', function(){
        var sqlEl = document.getElementById('clientCompareSql');
        var text = sqlEl ? (sqlEl.textContent || '') : '';
        if (!text){ alert('No SQL to copy'); return; }
        try {
          navigator.clipboard.writeText(text);
          alert('SQL copied');
        } catch(e){
          var ta = document.createElement('textarea');
          ta.value = text;
          document.body.appendChild(ta);
          ta.select();
          try { document.execCommand('copy'); alert('SQL copied'); } catch(e2){}
          document.body.removeChild(ta);
        }
      });
    }

  })();
</script>
<?php $this->load->view('partials/footer'); ?>
