<?php $this->load->view('partials/header', ['title' => 'DB Compare']); ?>
<?php if (!has_module_access('db')) { echo '<div class="alert alert-danger">Forbidden</div>'; $this->load->view('partials/footer'); return; } ?>
<style>
  #globalLoader[hidden]{ display:none !important; }
  #globalLoader{ position:fixed; inset:0; background:rgba(255,255,255,.6); z-index: 2000; display:flex; align-items:center; justify-content:center; }
</style>
<div id="globalLoader" hidden>
  <div class="text-center">
    <div class="spinner-border text-primary" role="status" aria-label="Loading"></div>
    <div class="small text-muted mt-2">Please wait…</div>
  </div>
</div>
<div class="card shadow-soft">
  <div class="card-header d-flex align-items-center justify-content-between">
    <h1 class="h5 mb-0">DB Compare</h1>
    <a class="btn btn-secondary btn-sm" href="<?php echo site_url('db'); ?>">Back</a>
  </div>
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-8 col-xl-3">
        <label class="form-label">SQL File Path</label>
        <input class="form-control" id="cmpFilePath" value="<?php echo isset($sql_file_default)?htmlspecialchars($sql_file_default):''; ?>" placeholder="C:\\path\\to\\dump.sql" />
      </div>
      <div class="col-4 col-md-4 col-xl-2">
        <label class="form-label">Hostname</label>
        <input class="form-control" id="cmpHost" placeholder="localhost" value="localhost" />
      </div>
      <div class="col-4 col-md-4 col-xl-2">
        <label class="form-label">Username</label>
        <input class="form-control" id="cmpUser" placeholder="root" value="root" />
      </div>
      <div class="col-4 col-md-4 col-xl-2">
        <label class="form-label">Password</label>
        <input class="form-control" id="cmpPass" type="password" placeholder="" />
      </div>
      <div class="col-8 col-md-4 col-xl-3">
        <label class="form-label">Database Name</label>
        <div class="input-group">
          <select class="form-select" id="cmpDatabase"></select>
          <button class="btn btn-outline-secondary" type="button" id="btnRefreshDbs" title="Refresh list">Refresh</button>
        </div>
      </div>
    </div>
    <div class="row g-2 align-items-center mt-2">
      <div class="col-md-auto">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <button class="btn btn-outline-secondary btn-sm" type="button" id="btnCheckConn">Check Connection</button>
          <span id="connBadge" class="badge bg-secondary">Not checked</span>
        </div>
      </div>
      <div class="col text-md-end">
      <button class="btn btn-outline-secondary" id="btnLoadFileDb">Autofill DB From File</button>
      <button class="btn btn-success ms-auto" id="btnMergeAll" disabled>Merge Missing (Create/Add)</button>

        <button class="btn btn-primary" id="btnScanDiff">Scan Differences</button>
      </div>
    </div>
    <!-- <div class="mt-3 d-flex align-items-center gap-2">
      <button class="btn btn-outline-secondary" id="btnLoadFileDb">Autofill DB From File</button>
      <button class="btn btn-success ms-auto" id="btnMergeAll" disabled>Merge Missing (Create/Add)</button>
    </div> -->
    <hr />
    <div id="cmpResults" class="row g-3">
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="mb-2">Missing in master DB (physical file) <span class="badge bg-secondary" id="leftBadgeTables" title="Tables">0</span> <span class="badge bg-secondary" id="leftBadgeCols" title="Columns">0</span></h6>
            <div class="small text-muted" id="leftMeta"></div>
            <div class="row mt-2">
              <div class="col-6 text-center">
                <div class="h1 mb-0" id="leftTableCount">-</div>
                <div class="text-muted small">Tables</div>
              </div>
              <div class="col-6 text-center">
                <div class="h1 mb-0" id="leftColCount">-</div>
                <div class="text-muted small">Columns</div>
              </div>
            </div>
            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-outline-primary btn-sm" id="btnShowSqlLeft" type="button">Show SQL</button>
              <button class="btn btn-outline-secondary btn-sm" id="btnCopySqlLeft" type="button">Copy SQL</button>
              <button class="btn btn-outline-dark btn-sm" id="btnDownloadSqlLeft" type="button">Download SQL</button>
            </div>
            <pre id="leftSql" class="bg-light border rounded p-2 small mt-2" style="white-space:pre-wrap; display:none;"></pre>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="mb-2">Missing in target DB <span class="badge bg-primary" id="rightBadgeTables" title="Tables to create">0</span> <span class="badge bg-primary" id="rightBadgeCols" title="Columns to add">0</span></h6>
            <div class="small text-muted" id="rightMeta"></div>
            <div class="row mt-2">
              <div class="col-6 text-center">
                <div class="h1 mb-0" id="rightTableCount">-</div>
                <div class="text-muted small">Tables to create</div>
              </div>
              <div class="col-6 text-center">
                <div class="h1 mb-0" id="rightColCount">-</div>
                <div class="text-muted small">Columns to add</div>
              </div>
            </div>
            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-outline-primary btn-sm" id="btnShowSql" type="button">Show SQL</button>
              <button class="btn btn-outline-secondary btn-sm" id="btnCopySql" type="button">Copy SQL</button>
              <button class="btn btn-outline-dark btn-sm" id="btnDownloadSql" type="button">Download SQL</button>
            </div>
            <pre id="rightSql" class="bg-light border rounded p-2 small mt-2" style="white-space:pre-wrap; display:none;"></pre>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function showLoader(){ try{ var el=document.getElementById('globalLoader'); if (el) el.hidden=false; }catch(e){} }
function hideLoader(){ try{ var el=document.getElementById('globalLoader'); if (el) el.hidden=true; }catch(e){} }
function showToast(msg, variant){ try{ alert(msg); }catch(e){} }
(function(){
  var btnScan = document.getElementById('btnScanDiff');
  var btnMerge = document.getElementById('btnMergeAll');
  var inputFile = document.getElementById('cmpFilePath');
  var inputDb = document.getElementById('cmpDatabase');
  var btnRefresh = document.getElementById('btnRefreshDbs');
  var inHost = document.getElementById('cmpHost');
  var inUser = document.getElementById('cmpUser');
  var inPass = document.getElementById('cmpPass');
  var connBadge = document.getElementById('connBadge');
  var btnCheckConn = document.getElementById('btnCheckConn');
  var btnShowSql = document.getElementById('btnShowSql');
  var btnCopySql = document.getElementById('btnCopySql');
  var rightSql = document.getElementById('rightSql');
  var btnDownloadSql = document.getElementById('btnDownloadSql');
  var btnDownloadSqlLeft = document.getElementById('btnDownloadSqlLeft');
  var btnShowSqlLeft = document.getElementById('btnShowSqlLeft');
  var btnCopySqlLeft = document.getElementById('btnCopySqlLeft');
  var leftSql = document.getElementById('leftSql');
  function loadDatabases(){
    if (!inputDb) return;
    inputDb.innerHTML = '<option value="">-- Loading... --</option>';
    if (connBadge){ connBadge.textContent = 'Checking…'; connBadge.className = 'badge bg-warning text-dark'; }
    var body = 'host='+encodeURIComponent((inHost&&inHost.value)||'')+
               '&user='+encodeURIComponent((inUser&&inUser.value)||'')+
               '&pass='+encodeURIComponent((inPass&&inPass.value)||'');
    fetch('<?php echo site_url('db/databases'); ?>', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body })
      .then(function(r){ return r.json(); })
      .then(function(j){
        var opts = ['<option value="">-- Select database --</option>'];
        if (j && j.success && Array.isArray(j.databases)){
          if (connBadge){ connBadge.textContent = 'Connected'; connBadge.className = 'badge bg-success'; }
          j.databases.forEach(function(name){ opts.push('<option value="'+name+'">'+name+'</option>'); });
        } else {
          if (connBadge){ connBadge.textContent = 'Failed'; connBadge.className = 'badge bg-danger'; }
          opts = ['<option value="">-- Failed to load --</option>'];
        }
        inputDb.innerHTML = opts.join('');
      })
      .catch(function(){ inputDb.innerHTML = '<option value="">-- Failed to load --</option>'; if (connBadge){ connBadge.textContent = 'Failed'; connBadge.className = 'badge bg-danger'; } });
  }
  if (btnRefresh){ btnRefresh.addEventListener('click', function(){ loadDatabases(); }); }
  var results = document.getElementById('cmpResults');
  function renderOps(j){
    if (!j || !j.success){
      if (results) results.innerHTML = '<div class="text-danger">'+ (j && j.message ? j.message : 'Failed to scan') +'</div>';
      return;
    }
    var ops = j.ops||[];
    var dbOnly = (j.db_only||{});
    var dbOnlyTables = dbOnly.tables || [];
    var dbOnlyCols = dbOnly.columns || [];
    var leftMeta = document.getElementById('leftMeta');
    var rightMeta = document.getElementById('rightMeta');
    var leftTableCount = document.getElementById('leftTableCount');
    var leftColCount = document.getElementById('leftColCount');
    var rightTableCount = document.getElementById('rightTableCount');
    var rightColCount = document.getElementById('rightColCount');
    var createCount = (ops.filter(function(o){ return o.type==='create_table'; }).length);
    var addColCount = (ops.filter(function(o){ return o.type==='add_column'; }).length);
    if (leftMeta) leftMeta.textContent = 'File: '+(j.file_path||'')+' • Target DB: '+(j.database||'');
    if (rightMeta) rightMeta.textContent = 'File: '+(j.file_path||'')+' • Target DB: '+(j.database||'');
    if (leftTableCount) leftTableCount.textContent = dbOnlyTables.length;
    if (leftColCount) leftColCount.textContent = dbOnlyCols.length;
    if (rightTableCount) rightTableCount.textContent = createCount;
    if (rightColCount) rightColCount.textContent = addColCount;
    var leftBadgeTables = document.getElementById('leftBadgeTables');
    var leftBadgeCols = document.getElementById('leftBadgeCols');
    var rightBadgeTables = document.getElementById('rightBadgeTables');
    var rightBadgeCols = document.getElementById('rightBadgeCols');
    if (leftBadgeTables) leftBadgeTables.textContent = dbOnlyTables.length;
    if (leftBadgeCols) leftBadgeCols.textContent = dbOnlyCols.length;
    if (rightBadgeTables) rightBadgeTables.textContent = createCount;
    if (rightBadgeCols) rightBadgeCols.textContent = addColCount;
    btnMerge.disabled = !(ops && ops.length);
    // Build concatenated SQL for right side
    var sqlParts = [];
    // Create tables first
    ops.filter(function(o){return o.type==='create_table';}).forEach(function(o){ if (o.sql) { sqlParts.push(o.sql.trim().replace(/;?\s*$/, ';')); } });
    // Then columns
    ops.filter(function(o){return o.type==='add_column';}).forEach(function(o){ if (o.sql) { sqlParts.push(o.sql.trim().replace(/;?\s*$/, ';')); } });
    var fullSql = sqlParts.join('\n\n');
    if (rightSql) rightSql.textContent = fullSql;
    // Build concatenated SQL for left side (DB-only => update master file)
    var leftParts = [];
    var leftSqlObj = (j.db_only_sql || {});
    var leftTbl = leftSqlObj.tables || [];
    var leftCols = leftSqlObj.columns || [];
    leftTbl.forEach(function(s){ if (s) leftParts.push((s+'').trim().replace(/;?\s*$/, ';')); });
    leftCols.forEach(function(s){ if (s) leftParts.push((s+'').trim().replace(/;?\s*$/, ';')); });
    if (leftSql) leftSql.textContent = leftParts.join('\n\n');
  }
  if (btnShowSql && rightSql){ btnShowSql.addEventListener('click', function(){ rightSql.style.display = rightSql.style.display==='none' ? 'block' : 'none'; }); }
  if (btnCopySql){ btnCopySql.addEventListener('click', function(){ try{ var txt = (rightSql && rightSql.textContent)||''; if (!txt){ showToast('No SQL to copy'); return; } navigator.clipboard.writeText(txt); showToast('SQL copied'); }catch(e){ var ta=document.createElement('textarea'); ta.value=(rightSql&&rightSql.textContent)||''; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); showToast('SQL copied'); }catch(e2){} document.body.removeChild(ta);} }); }
  if (btnDownloadSql && rightSql){ btnDownloadSql.addEventListener('click', function(){ var txt=(rightSql&&rightSql.textContent)||''; if(!txt){ showToast('No SQL to download'); return; } try{ var blob=new Blob([txt+'\n'],{type:'application/sql'}); var a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='db_update_'+(Date.now())+'.sql'; document.body.appendChild(a); a.click(); setTimeout(function(){ URL.revokeObjectURL(a.href); a.remove(); }, 0); }catch(e){} }); }
  if (btnShowSqlLeft && leftSql){ btnShowSqlLeft.addEventListener('click', function(){ leftSql.style.display = leftSql.style.display==='none' ? 'block' : 'none'; }); }
  if (btnCopySqlLeft){ btnCopySqlLeft.addEventListener('click', function(){ try{ var txt = (leftSql && leftSql.textContent)||''; if (!txt){ showToast('No SQL to copy'); return; } navigator.clipboard.writeText(txt); showToast('SQL copied'); }catch(e){ var ta=document.createElement('textarea'); ta.value=(leftSql&&leftSql.textContent)||''; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); showToast('SQL copied'); }catch(e2){} document.body.removeChild(ta);} }); }
  if (btnDownloadSqlLeft && leftSql){ btnDownloadSqlLeft.addEventListener('click', function(){ var txt=(leftSql&&leftSql.textContent)||''; if(!txt){ showToast('No SQL to download'); return; } try{ var blob=new Blob([txt+'\n'],{type:'application/sql'}); var a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='file_update_'+(Date.now())+'.sql'; document.body.appendChild(a); a.click(); setTimeout(function(){ URL.revokeObjectURL(a.href); a.remove(); }, 0); }catch(e){} }); }
  document.getElementById('btnLoadFileDb').addEventListener('click', function(){
    var fp = (inputFile && inputFile.value)||'';
    if (!fp){ showToast('Provide SQL file path'); return; }
    showLoader();
    // Step 1: detect database name from file
    fetch('<?php echo site_url('db/file_tables'); ?>', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'file_path='+encodeURIComponent(fp) })
      .then(function(r){ return r.json(); })
      .then(function(j){
        if (j && j.database){ inputDb.value = j.database; }
        var dbName = (inputDb && inputDb.value)||'';
        if (!dbName){ return; }
        // Step 2: append DB-only tables/columns to the SQL file
        var bodyUpd = 'file_path='+encodeURIComponent(fp)+
                      '&database='+encodeURIComponent(dbName)+
                      '&host='+encodeURIComponent((inHost&&inHost.value)||'')+
                      '&user='+encodeURIComponent((inUser&&inUser.value)||'')+
                      '&pass='+encodeURIComponent((inPass&&inPass.value)||'');
        return fetch('<?php echo site_url('db/compare/update-file-missing'); ?>', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: bodyUpd })
          .then(function(r){ return r.json(); })
          .then(function(res){
            if (res && res.success){
              showToast('Updated file: added '+(res.tables||0)+' table(s), '+(res.columns||0)+' column(s)');
            } else {
              showToast((res&&res.message)||'Failed to update file','danger');
            }
          });
      })
      .finally(function(){ hideLoader(); });
  });
  if (btnScan){ btnScan.addEventListener('click', function(){
    var fp = (inputFile && inputFile.value)||''; var db = (inputDb && inputDb.value)||'';
    if (!fp || !db){ showToast('Provide file path and database','danger'); return; }
    showLoader();
    var bodyScan = 'file_path='+encodeURIComponent(fp)+
                   '&database='+encodeURIComponent(db)+
                   '&host='+encodeURIComponent((inHost&&inHost.value)||'')+
                   '&user='+encodeURIComponent((inUser&&inUser.value)||'')+
                   '&pass='+encodeURIComponent((inPass&&inPass.value)||'');
    fetch('<?php echo site_url('db/compare/scan'); ?>', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: bodyScan })
      .then(function(r){ return r.json(); })
      .then(function(j){ renderOps(j); })
      .catch(function(){ results && (results.innerHTML = '<div class="text-danger">Failed</div>'); })
      .finally(function(){ hideLoader(); });
  }); }
  if (btnMerge){ btnMerge.addEventListener('click', function(){
    var fp = (inputFile && inputFile.value)||''; var db = (inputDb && inputDb.value)||'';
    if (!fp || !db){ showToast('Provide file path and database','danger'); return; }
    if (!confirm('Apply all missing changes? This will CREATE tables and ADD columns.')) return;
    showLoader();
    var bodyMerge = 'file_path='+encodeURIComponent(fp)+
                    '&database='+encodeURIComponent(db)+
                    '&host='+encodeURIComponent((inHost&&inHost.value)||'')+
                    '&user='+encodeURIComponent((inUser&&inUser.value)||'')+
                    '&pass='+encodeURIComponent((inPass&&inPass.value)||'');
    fetch('<?php echo site_url('db/compare/merge'); ?>', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: bodyMerge })
      .then(function(r){ return r.json(); })
      .then(function(j){ if (j && j.success){ showToast('Applied '+(j.applied||0)+' change(s)'); } else { showToast((j&&j.message)||'Failed','danger'); } })
      .catch(function(){ showToast('Failed','danger'); })
      .finally(function(){ hideLoader(); });
  }); }
  // Initial load
  loadDatabases();
  if (btnCheckConn){ btnCheckConn.addEventListener('click', function(){ loadDatabases(); }); }
})();
</script>
<?php $this->load->view('partials/footer'); ?>
