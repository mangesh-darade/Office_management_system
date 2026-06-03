<?php $this->load->view('partials/header', ['title' => 'DB Difference']); ?>
<?php if (!has_module_access('db')) { echo '<div class="alert alert-danger">Forbidden</div>'; $this->load->view('partials/footer'); return; } ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">DB Difference</h1>
  <div class="d-flex gap-2 flex-wrap">
    <button type="button" class="btn btn-success btn-sm" id="btnEnsureSchemas"><i class="bi bi-lightning-charge"></i> Ensure OMS Schema</button>
    <a class="btn btn-secondary btn-sm" href="<?php echo site_url('db'); ?>">Back to DB Manager</a>
  </div>
</div>

<?php if (!empty($module_tables)): ?>
<div class="alert alert-light border small mb-3">
  <strong>Module tables on master (<?php echo htmlspecialchars($master_db); ?>):</strong>
  <?php echo htmlspecialchars(implode(', ', $module_tables)); ?>
</div>
<?php endif; ?>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <h5 class="card-title mb-3">Compare Two Databases</h5>
    <form id="dbDifferenceForm">
      <div class="mb-3">
        <label class="form-label small fw-semibold">Client database connection</label>
        <div class="d-flex flex-wrap gap-3">
          <div class="form-check">
            <input class="form-check-input" type="radio" name="connection_mode" id="connModeLocal" value="local" checked>
            <label class="form-check-label" for="connModeLocal">Local WAMP (root, same DB name)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="connection_mode" id="connModeLive" value="live">
            <label class="form-check-label" for="connModeLive">Live server (stored client username/password)</label>
          </div>
        </div>
      </div>
      <div id="localConnHints" class="small text-muted mb-3">
        Uses WAMP <code>root</code> with the client’s database name. Use <strong>Create local DB</strong> if the database is not on WAMP yet, then <strong>Test connection</strong>.
      </div>
      <div id="liveConnHints" class="alert alert-info small py-2 mb-3" style="display:none;">
        <strong>Live server:</strong> Uses the client’s DB username/password against the remote MySQL host.
        In cPanel open <em>Remote MySQL</em>, add your WAMP public IP, and copy the server hostname into
        <strong>DB Host</strong> (<a href="<?php echo site_url('clients'); ?>">CRM → Clients → Edit</a>) or the override field below.
      </div>
      <input type="hidden" id="useLocalCredentials" name="use_local_credentials" value="0">
      <input type="hidden" id="allowLocalFallback" name="allow_local_fallback" value="1">
      <input type="hidden" id="useLiveServer" name="use_live_server" value="0">
      <div class="row g-2 mb-3">
        <div class="col-md-4">
          <label class="form-label small">MySQL host <span id="hostRequiredMark" class="text-danger" style="display:none;">*</span></label>
          <input type="text" class="form-control form-control-sm" id="dbHostOverride" placeholder="e.g. server123.host.com">
        </div>
        <div class="col-md-2">
          <label class="form-label small">Port</label>
          <input type="text" class="form-control form-control-sm" id="dbPortOverride" placeholder="3306">
        </div>
        <div class="col-md-5 d-flex align-items-end gap-2 flex-wrap">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTestClientConn">Test connection</button>
          <button type="button" class="btn btn-outline-success btn-sm" id="btnCreateLocalDb" title="Create empty database on WAMP">Create local DB</button>
          <button type="button" class="btn btn-outline-warning btn-sm" id="btnSaveClientDbHost" style="display:none;" title="Save MySQL host to client record">Save host to client</button>
          <a class="btn btn-outline-primary btn-sm" id="btnEditClientDb" href="<?php echo site_url('clients'); ?>" style="display:none;">Edit client</a>
        </div>
      </div>
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="ensureMasterSchema" name="ensure_master_schema" value="1" checked>
        <label class="form-check-label" for="ensureMasterSchema">Auto-run schema automation on master before compare (coaching, training, reminders, …)</label>
      </div>
      <div class="row">
        <div class="col-md-5">
          <label class="form-label">Source Database (Section A)</label>
          <select class="form-select" id="sourceDb" name="source_db" required>
            <option value="">-- Select Source Database --</option>
            <option value="<?php echo htmlspecialchars($master_db); ?>" data-client-id="0" data-is-master="1" data-client-name="OMS Master">
              OMS Master (<?php echo htmlspecialchars($master_db); ?>)
            </option>
            <?php foreach ($clients as $cl): ?>
              <?php if (!empty($cl->db_name)): ?>
                <option value="<?php echo htmlspecialchars($cl->db_name); ?>" 
                        data-client-id="<?php echo (int)$cl->id; ?>"
                        data-is-master="0"
                        data-client-name="<?php echo htmlspecialchars($cl->company_name); ?>"
                        data-db-host="<?php echo htmlspecialchars(isset($cl->db_host) ? $cl->db_host : ''); ?>"
                        data-db-port="<?php echo htmlspecialchars(isset($cl->db_port) ? $cl->db_port : ''); ?>">
                  <?php echo htmlspecialchars($cl->company_name . ' (' . $cl->db_name . ')'); ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label">Target Database (Section B)</label>
          <select class="form-select" id="targetDb" name="target_db" required>
            <option value="">-- Select Target Database --</option>
            <option value="<?php echo htmlspecialchars($master_db); ?>" data-client-id="0" data-is-master="1" data-client-name="OMS Master">
              OMS Master (<?php echo htmlspecialchars($master_db); ?>)
            </option>
            <?php foreach ($clients as $cl): ?>
              <?php if (!empty($cl->db_name)): ?>
                <option value="<?php echo htmlspecialchars($cl->db_name); ?>" 
                        data-client-id="<?php echo (int)$cl->id; ?>"
                        data-is-master="0"
                        data-client-name="<?php echo htmlspecialchars($cl->company_name); ?>"
                        data-db-host="<?php echo htmlspecialchars(isset($cl->db_host) ? $cl->db_host : ''); ?>"
                        data-db-port="<?php echo htmlspecialchars(isset($cl->db_port) ? $cl->db_port : ''); ?>">
                  <?php echo htmlspecialchars($cl->company_name . ' (' . $cl->db_name . ')'); ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100" id="btnCompare">
            <i class="bi bi-arrow-left-right"></i> Compare
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<div id="resultsContainer" style="display:none;">
  <!-- Section A (Left) and Section B (Right) Comparison -->
  <div class="row mb-4">
    <!-- Section A: Source Database -->
    <div class="col-md-6">
      <div class="card shadow-soft h-100 border-primary">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0">
            <i class="bi bi-database"></i> Section A: Source Database
          </h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <strong class="text-primary">Database:</strong> <span id="sectionADbName" class="fw-bold"></span>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <div class="card bg-warning bg-opacity-10">
                <div class="card-body text-center p-2">
                  <div class="h4 mb-0 text-warning" id="sectionAMissingTables">0</div>
                  <small class="text-muted">Missing Tables</small>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="card bg-info bg-opacity-10">
                <div class="card-body text-center p-2">
                  <div class="h4 mb-0 text-info" id="sectionAMissingColumns">0</div>
                  <small class="text-muted">Missing Columns</small>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Missing Tables List -->
          <div class="mb-3">
            <h6 class="text-warning">
              <i class="bi bi-table"></i> Missing Tables (in A, not in B):
            </h6>
            <div class="table-responsive" style="max-height:200px;overflow-y:auto;">
              <table class="table table-sm table-bordered table-hover">
                <thead class="table-warning">
                  <tr>
                    <th width="40">#</th>
                    <th>Table Name</th>
                  </tr>
                </thead>
                <tbody id="sectionAMissingTablesList">
                  <tr>
                    <td colspan="2" class="text-center text-muted">No missing tables</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Missing Columns List -->
          <div class="mb-3">
            <h6 class="text-info">
              <i class="bi bi-list-columns"></i> Missing Columns (in A, not in B):
            </h6>
            <div class="table-responsive" style="max-height:200px;overflow-y:auto;">
              <table class="table table-sm table-bordered table-hover">
                <thead class="table-info">
                  <tr>
                    <th width="40">#</th>
                    <th>Table Name</th>
                    <th>Column Name</th>
                  </tr>
                </thead>
                <tbody id="sectionAMissingColumnsList">
                  <tr>
                    <td colspan="3" class="text-center text-muted">No missing columns</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <div class="alert alert-info mb-0">
            <small><strong>Note:</strong> These are items in Section A that are missing in Section B</small>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Section B: Target Database -->
    <div class="col-md-6">
      <div class="card shadow-soft h-100 border-secondary">
        <div class="card-header bg-secondary text-white">
          <h5 class="mb-0">
            <i class="bi bi-database"></i> Section B: Target Database
          </h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <strong class="text-secondary">Database:</strong> <span id="sectionBDbName" class="fw-bold"></span>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <div class="card bg-warning bg-opacity-10">
                <div class="card-body text-center p-2">
                  <div class="h4 mb-0 text-warning" id="sectionBMissingTables">0</div>
                  <small class="text-muted">Missing Tables</small>
                </div>
              </div>
            </div>
            <div class="col-6">
              <div class="card bg-info bg-opacity-10">
                <div class="card-body text-center p-2">
                  <div class="h4 mb-0 text-info" id="sectionBMissingColumns">0</div>
                  <small class="text-muted">Missing Columns</small>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Missing Tables List -->
          <div class="mb-3">
            <h6 class="text-warning">
              <i class="bi bi-table"></i> Missing Tables (in B, not in A):
            </h6>
            <div class="table-responsive" style="max-height:200px;overflow-y:auto;">
              <table class="table table-sm table-bordered table-hover">
                <thead class="table-warning">
                  <tr>
                    <th width="40">#</th>
                    <th>Table Name</th>
                  </tr>
                </thead>
                <tbody id="sectionBMissingTablesList">
                  <tr>
                    <td colspan="2" class="text-center text-muted">No missing tables</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <!-- Missing Columns List -->
          <div class="mb-3">
            <h6 class="text-info">
              <i class="bi bi-list-columns"></i> Missing Columns (in B, not in A):
            </h6>
            <div class="table-responsive" style="max-height:200px;overflow-y:auto;">
              <table class="table table-sm table-bordered table-hover">
                <thead class="table-info">
                  <tr>
                    <th width="40">#</th>
                    <th>Table Name</th>
                    <th>Column Name</th>
                  </tr>
                </thead>
                <tbody id="sectionBMissingColumnsList">
                  <tr>
                    <td colspan="3" class="text-center text-muted">No missing columns</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          
          <div class="alert alert-secondary mb-0">
            <small><strong>Note:</strong> These are items in Section B that are missing in Section A</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SQL Query Section: A Compare B (Add to B) -->
  <div class="card shadow-soft mb-3 border-primary">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="bi bi-arrow-right"></i> SQL Query: A → B (Add to Section B)
      </h5>
      <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-warning" id="btnApplyAToB" title="Execute SQL on target database">
          <i class="bi bi-play-fill"></i> Apply to B
        </button>
        <button type="button" class="btn btn-light" id="btnCopyAToBSql">
          <i class="bi bi-clipboard"></i> Copy SQL
        </button>
        <button type="button" class="btn btn-light" id="btnDownloadAToBSql">
          <i class="bi bi-download"></i> Download SQL
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="alert alert-primary mb-3">
        <strong>Purpose:</strong> Adds missing tables/columns/indexes from Section A to Section B using proper <code>CREATE TABLE</code>, <code>ALTER TABLE ADD COLUMN</code>, and <code>MODIFY COLUMN</code> statements (ordered in a transaction).
      </div>
      <pre id="aToBSqlOutput" class="bg-light border rounded p-3 small" style="white-space:pre-wrap;max-height:400px;overflow:auto;font-family: 'Courier New', monospace;"></pre>
    </div>
  </div>

  <!-- SQL Query Section: B Compare A (Add to A) -->
  <div class="card shadow-soft mb-3 border-secondary">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        <i class="bi bi-arrow-left"></i> SQL Query: B → A (Add to Section A)
      </h5>
      <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-warning" id="btnApplyBToA" title="Execute SQL on source database">
          <i class="bi bi-play-fill"></i> Apply to A
        </button>
        <button type="button" class="btn btn-light" id="btnCopyBToASql">
          <i class="bi bi-clipboard"></i> Copy SQL
        </button>
        <button type="button" class="btn btn-light" id="btnDownloadBToASql">
          <i class="bi bi-download"></i> Download SQL
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="alert alert-secondary mb-3">
        <strong>Purpose:</strong> Adds missing tables/columns/indexes from Section B to Section A (reverse sync).
      </div>
      <pre id="bToASqlOutput" class="bg-light border rounded p-3 small" style="white-space:pre-wrap;max-height:400px;overflow:auto;font-family: 'Courier New', monospace;"></pre>
    </div>
  </div>
</div>

<script>
  var csrf_token = '<?php echo $csrf_token; ?>';
(function(){
  function parseJsonResponse(response) {
    return response.text().then(function(text) {
      if (!text) {
        throw new Error('Empty response from server.');
      }
      try {
        return JSON.parse(text);
      } catch (e) {
        if (text.indexOf('Access Denied') !== -1) {
          throw new Error('Access denied. Refresh the page and ensure you are logged in with DB module access.');
        }
        if (text.indexOf('login') !== -1 || text.indexOf('Login') !== -1) {
          throw new Error('Session expired. Please log in again and retry.');
        }
        throw new Error('Server returned an HTML page instead of JSON (often a PHP/DB error). Refresh and check that the client database exists on WAMP.');
      }
    });
  }

  var form = document.getElementById('dbDifferenceForm');
  var resultsContainer = document.getElementById('resultsContainer');
  var btnCompare = document.getElementById('btnCompare');
  var btnCopyAToBSql = document.getElementById('btnCopyAToBSql');
  var btnDownloadAToBSql = document.getElementById('btnDownloadAToBSql');
  var btnCopyBToASql = document.getElementById('btnCopyBToASql');
  var btnDownloadBToASql = document.getElementById('btnDownloadBToASql');
  var btnApplyAToB = document.getElementById('btnApplyAToB');
  var btnApplyBToA = document.getElementById('btnApplyBToA');
  var aToBSqlOutput = document.getElementById('aToBSqlOutput');
  var bToASqlOutput = document.getElementById('bToASqlOutput');
  var currentAToBSql = '';
  var currentBToASql = '';
  var lastCompareParams = null;

  function isLiveConnectionMode() {
    var live = document.getElementById('connModeLive');
    return !!(live && live.checked);
  }

  function syncConnectionModeFields() {
    var live = isLiveConnectionMode();
    var useLocal = document.getElementById('useLocalCredentials');
    var allowFallback = document.getElementById('allowLocalFallback');
    var useLive = document.getElementById('useLiveServer');
    var localHints = document.getElementById('localConnHints');
    var liveHints = document.getElementById('liveConnHints');
    var hostMark = document.getElementById('hostRequiredMark');
    if (useLocal) { useLocal.value = live ? '0' : '0'; }
    if (allowFallback) { allowFallback.value = live ? '0' : '1'; }
    if (useLive) { useLive.value = live ? '1' : '0'; }
    if (localHints) { localHints.style.display = live ? 'none' : 'block'; }
    if (liveHints) { liveHints.style.display = live ? 'block' : 'none'; }
    if (hostMark) { hostMark.style.display = live ? 'inline' : 'none'; }
    var btnCreate = document.getElementById('btnCreateLocalDb');
    var btnSaveHost = document.getElementById('btnSaveClientDbHost');
    if (btnCreate) { btnCreate.style.display = live ? 'none' : 'inline-block'; }
    if (btnSaveHost) { btnSaveHost.style.display = live ? 'inline-block' : 'none'; }
  }

  function getActiveClientOption() {
    var targetSelect = document.getElementById('targetDb');
    var sourceSelect = document.getElementById('sourceDb');
    var targetOption = targetSelect ? targetSelect.options[targetSelect.selectedIndex] : null;
    var sourceOption = sourceSelect ? sourceSelect.options[sourceSelect.selectedIndex] : null;
    if (targetOption && targetOption.getAttribute('data-is-master') !== '1' && targetOption.getAttribute('data-client-id')) {
      return targetOption;
    }
    if (sourceOption && sourceOption.getAttribute('data-is-master') !== '1' && sourceOption.getAttribute('data-client-id')) {
      return sourceOption;
    }
    return null;
  }

  function syncHostFieldsFromClient() {
    var opt = getActiveClientOption();
    var hostInput = document.getElementById('dbHostOverride');
    var portInput = document.getElementById('dbPortOverride');
    var editBtn = document.getElementById('btnEditClientDb');
    if (!opt) {
      if (editBtn) { editBtn.style.display = 'none'; }
      return;
    }
    var clientId = opt.getAttribute('data-client-id');
    var storedHost = opt.getAttribute('data-db-host') || '';
    var storedPort = opt.getAttribute('data-db-port') || '';
    if (hostInput && storedHost && (!hostInput.value || hostInput.dataset.autoFilled === '1')) {
      hostInput.value = storedHost;
      hostInput.dataset.autoFilled = '1';
    }
    if (portInput && storedPort && (!portInput.value || portInput.dataset.autoFilled === '1')) {
      portInput.value = storedPort;
      portInput.dataset.autoFilled = '1';
    }
    if (editBtn && clientId) {
      editBtn.href = '<?php echo site_url('clients/edit/'); ?>' + clientId;
      editBtn.style.display = 'inline-block';
    }
  }
  
  function getCompareParamsFromForm() {
    var sourceSelect = document.getElementById('sourceDb');
    var targetSelect = document.getElementById('targetDb');
    var sourceOption = sourceSelect ? sourceSelect.options[sourceSelect.selectedIndex] : null;
    var targetOption = targetSelect ? targetSelect.options[targetSelect.selectedIndex] : null;
    var live = isLiveConnectionMode();
    return {
      source_db: sourceSelect ? sourceSelect.value : '',
      target_db: targetSelect ? targetSelect.value : '',
      source_client_id: sourceOption ? (sourceOption.getAttribute('data-client-id') || '') : '',
      target_client_id: targetOption ? (targetOption.getAttribute('data-client-id') || '') : '',
      source_is_master: sourceOption && sourceOption.getAttribute('data-is-master') === '1' ? '1' : '0',
      target_is_master: targetOption && targetOption.getAttribute('data-is-master') === '1' ? '1' : '0',
      source_client_name: sourceOption ? (sourceOption.getAttribute('data-client-name') || '') : '',
      target_client_name: targetOption ? (targetOption.getAttribute('data-client-name') || '') : '',
      ensure_master_schema: document.getElementById('ensureMasterSchema') && document.getElementById('ensureMasterSchema').checked ? '1' : '0',
      use_local_credentials: live ? '0' : (document.getElementById('useLocalCredentials') ? document.getElementById('useLocalCredentials').value : '0'),
      allow_local_fallback: live ? '0' : (document.getElementById('allowLocalFallback') ? document.getElementById('allowLocalFallback').value : '1'),
      use_live_server: live ? '1' : '0',
      db_host: document.getElementById('dbHostOverride') ? document.getElementById('dbHostOverride').value : '',
      db_port: document.getElementById('dbPortOverride') ? document.getElementById('dbPortOverride').value : ''
    };
  }

  function appendConnectionOptions(formData) {
    var p = getCompareParamsFromForm();
    formData.append('use_local_credentials', p.use_local_credentials);
    formData.append('allow_local_fallback', p.allow_local_fallback);
    formData.append('use_live_server', p.use_live_server);
    formData.append('db_host', p.db_host);
    formData.append('db_port', p.db_port);
  }

  ['connModeLocal', 'connModeLive'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('change', function() {
        syncConnectionModeFields();
        syncHostFieldsFromClient();
      });
    }
  });
  ['sourceDb', 'targetDb'].forEach(function(id) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener('change', syncHostFieldsFromClient);
    }
  });
  var hostOverrideEl = document.getElementById('dbHostOverride');
  if (hostOverrideEl) {
    hostOverrideEl.addEventListener('input', function() {
      hostOverrideEl.dataset.autoFilled = '0';
    });
  }
  syncConnectionModeFields();
  syncHostFieldsFromClient();

  function applyDatabaseDiff(direction, btn) {
    if (!lastCompareParams) {
      alert('Please run Compare first.');
      return;
    }
    var sql = direction === 'a_to_b' ? currentAToBSql : currentBToASql;
    if (!sql || sql.trim() === '' || sql.indexOf('No SQL generated') === 0) {
      alert('No SQL statements to apply for this direction.');
      return;
    }
    var targetLabel = direction === 'a_to_b' ? lastCompareParams.target_db : lastCompareParams.source_db;
    var count = dataStatementCount(direction);
    var countLabel = count ? String(count) : 'all';
    if (!confirm('Apply ' + countLabel + ' SQL statement(s) to database "' + targetLabel + '"?\n\nThis will run CREATE/ALTER statements in a transaction.')) {
      return;
    }
    if (btn) {
      btn.disabled = true;
      btn.dataset.originalHtml = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Applying...';
    }
    var formData = new FormData();
    Object.keys(lastCompareParams).forEach(function(k){ formData.append(k, lastCompareParams[k]); });
    formData.append('direction', direction);
    formData.append('csrf_token', csrf_token);
    fetch('<?php echo site_url('db/apply-database-diff'); ?>', { method: 'POST', body: formData })
      .then(parseJsonResponse)
      .then(function(data){
        if (data && data.success) {
          alert(data.message || ('Applied ' + data.applied + ' statement(s).'));
          if (form) { form.dispatchEvent(new Event('submit', { cancelable: true })); }
        } else {
          alert((data && data.message) ? data.message : 'Apply failed.');
        }
      })
      .catch(function(err){ alert('Error: ' + (err.message || 'Apply failed.')); })
      .finally(function(){
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = btn.dataset.originalHtml || btn.innerHTML;
        }
      });
  }

  function dataStatementCount(direction) {
    return direction === 'a_to_b' ? (window.lastStatementCountA || 0) : (window.lastStatementCountB || 0);
  }
  
  // Helper function to copy text to clipboard
  function copyToClipboard(text, successMessage){
    if (!text || text.trim() === ''){
      alert('No SQL to copy.');
      return;
    }
    try {
      navigator.clipboard.writeText(text);
      alert(successMessage || 'SQL copied to clipboard!');
    } catch(e){
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.opacity = '0';
      document.body.appendChild(ta);
      ta.select();
      try { 
        document.execCommand('copy'); 
        alert(successMessage || 'SQL copied to clipboard!');
      } catch(e2){
        alert('Failed to copy. Please select and copy manually from the SQL output above.');
      }
      document.body.removeChild(ta);
    }
  }
  
  if (form){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var sourceSelect = document.getElementById('sourceDb');
      var targetSelect = document.getElementById('targetDb');
      var sourceDb = sourceSelect ? sourceSelect.value : '';
      var targetDb = targetSelect ? targetSelect.value : '';
      
      if (!sourceDb || !targetDb){
        alert('Please select both source and target databases.');
        return;
      }
      
      if (sourceDb === targetDb){
        alert('Source and target databases cannot be the same.');
        return;
      }

      var sourceOption = sourceSelect.options[sourceSelect.selectedIndex];
      var targetOption = targetSelect.options[targetSelect.selectedIndex];
      var sourceClientId = sourceOption ? (sourceOption.getAttribute('data-client-id') || '') : '';
      var targetClientId = targetOption ? (targetOption.getAttribute('data-client-id') || '') : '';

      if (isLiveConnectionMode() && (sourceClientId !== '0' || targetClientId !== '0')) {
        var hostVal = (document.getElementById('dbHostOverride') && document.getElementById('dbHostOverride').value) || '';
        var opt = getActiveClientOption();
        var storedHost = opt ? (opt.getAttribute('data-db-host') || '') : '';
        if (!hostVal.trim() && !storedHost.trim()) {
          alert('Live server mode: enter the MySQL hostname (from cPanel → Remote MySQL) or save it on the client record first.');
          return;
        }
      }
      var sourceIsMaster = sourceOption && sourceOption.getAttribute('data-is-master') === '1' ? '1' : '0';
      var targetIsMaster = targetOption && targetOption.getAttribute('data-is-master') === '1' ? '1' : '0';
      var ensureMaster = document.getElementById('ensureMasterSchema') && document.getElementById('ensureMasterSchema').checked ? '1' : '0';
      
      if (btnCompare){ 
        btnCompare.disabled = true; 
        btnCompare.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Comparing...';
      }
      
      var formData = new FormData();
      formData.append('source_db', sourceDb);
      formData.append('target_db', targetDb);
      formData.append('source_client_id', sourceClientId);
      formData.append('target_client_id', targetClientId);
      formData.append('source_is_master', sourceIsMaster);
      formData.append('target_is_master', targetIsMaster);
      formData.append('ensure_master_schema', ensureMaster);
      appendConnectionOptions(formData);
      formData.append('csrf_token', csrf_token);

      fetch('<?php echo site_url('db/compare-databases'); ?>', {
        method: 'POST',
        body: formData
      })
      .then(parseJsonResponse)
      .then(function(data){
        if (data && data.success){
          lastCompareParams = getCompareParamsFromForm();
          displayResults(data);
        } else {
          alert((data && data.message) ? data.message : 'Failed to compare databases.');
        }
      })
      .catch(function(err){
        alert('Error: ' + (err.message || 'Failed to compare databases.'));
      })
      .finally(function(){
        if (btnCompare){ 
          btnCompare.disabled = false; 
          btnCompare.innerHTML = '<i class="bi bi-arrow-left-right"></i> Compare';
        }
      });
    });
  }
  
  function displayResults(data){
    // A→B: What's missing in B (from A)
    var missingTablesAToB = data.missing_tables || [];
    var missingColumnsAToB = data.missing_columns || [];
    currentAToBSql = data.apply_sql || data.sql || '';
    window.lastStatementCountA = data.statement_count || 0;
    
    // B→A: What's missing in A (from B)
    var missingTablesBToA = data.reverse_missing_tables || [];
    var missingColumnsBToA = data.reverse_missing_columns || [];
    currentBToASql = data.reverse_apply_sql || data.reverse_sql || '';
    window.lastStatementCountB = data.reverse_statement_count || 0;
    
    // Update Section A (Source) counts
    var sectionADbName = document.getElementById('sectionADbName');
    var sectionAMissingTables = document.getElementById('sectionAMissingTables');
    var sectionAMissingColumns = document.getElementById('sectionAMissingColumns');
    
    if (sectionADbName) sectionADbName.textContent = data.source_db || '';
    if (sectionAMissingTables) sectionAMissingTables.textContent = missingTablesAToB.length;
    if (sectionAMissingColumns) sectionAMissingColumns.textContent = missingColumnsAToB.length;
    
    // Update Section A Missing Tables List
    var sectionAMissingTablesList = document.getElementById('sectionAMissingTablesList');
    if (sectionAMissingTablesList){
      sectionAMissingTablesList.innerHTML = '';
      if (missingTablesAToB.length === 0){
        sectionAMissingTablesList.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No missing tables</td></tr>';
      } else {
        missingTablesAToB.forEach(function(tableName, index){
          var row = document.createElement('tr');
          row.className = 'table-warning';
          row.innerHTML = '<td>' + (index + 1) + '</td>' +
                         '<td><code>' + escapeHtml(tableName) + '</code></td>';
          sectionAMissingTablesList.appendChild(row);
        });
      }
    }
    
    // Update Section A Missing Columns List
    var sectionAMissingColumnsList = document.getElementById('sectionAMissingColumnsList');
    if (sectionAMissingColumnsList){
      sectionAMissingColumnsList.innerHTML = '';
      if (missingColumnsAToB.length === 0){
        sectionAMissingColumnsList.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No missing columns</td></tr>';
      } else {
        missingColumnsAToB.forEach(function(item, index){
          var row = document.createElement('tr');
          row.className = 'table-info';
          row.innerHTML = '<td>' + (index + 1) + '</td>' +
                         '<td><code>' + escapeHtml(item.table || '') + '</code></td>' +
                         '<td><code>' + escapeHtml(item.column || '') + '</code></td>';
          sectionAMissingColumnsList.appendChild(row);
        });
      }
    }
    
    // Update Section B (Target) counts
    var sectionBDbName = document.getElementById('sectionBDbName');
    var sectionBMissingTables = document.getElementById('sectionBMissingTables');
    var sectionBMissingColumns = document.getElementById('sectionBMissingColumns');
    
    if (sectionBDbName) sectionBDbName.textContent = data.target_db || '';
    if (sectionBMissingTables) sectionBMissingTables.textContent = missingTablesBToA.length;
    if (sectionBMissingColumns) sectionBMissingColumns.textContent = missingColumnsBToA.length;
    
    // Update Section B Missing Tables List
    var sectionBMissingTablesList = document.getElementById('sectionBMissingTablesList');
    if (sectionBMissingTablesList){
      sectionBMissingTablesList.innerHTML = '';
      if (missingTablesBToA.length === 0){
        sectionBMissingTablesList.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No missing tables</td></tr>';
      } else {
        missingTablesBToA.forEach(function(tableName, index){
          var row = document.createElement('tr');
          row.className = 'table-warning';
          row.innerHTML = '<td>' + (index + 1) + '</td>' +
                         '<td><code>' + escapeHtml(tableName) + '</code></td>';
          sectionBMissingTablesList.appendChild(row);
        });
      }
    }
    
    // Update Section B Missing Columns List
    var sectionBMissingColumnsList = document.getElementById('sectionBMissingColumnsList');
    if (sectionBMissingColumnsList){
      sectionBMissingColumnsList.innerHTML = '';
      if (missingColumnsBToA.length === 0){
        sectionBMissingColumnsList.innerHTML = '<tr><td colspan="3" class="text-center text-muted">No missing columns</td></tr>';
      } else {
        missingColumnsBToA.forEach(function(item, index){
          var row = document.createElement('tr');
          row.className = 'table-info';
          row.innerHTML = '<td>' + (index + 1) + '</td>' +
                         '<td><code>' + escapeHtml(item.table || '') + '</code></td>' +
                         '<td><code>' + escapeHtml(item.column || '') + '</code></td>';
          sectionBMissingColumnsList.appendChild(row);
        });
      }
    }
    
    // Update A→B SQL output
    if (aToBSqlOutput){
      if (currentAToBSql && currentAToBSql.trim() !== ''){
        aToBSqlOutput.textContent = currentAToBSql;
      } else {
        aToBSqlOutput.textContent = '-- No SQL generated. All tables and columns from Section A already exist in Section B.';
      }
    }
    
    // Update B→A SQL output
    if (bToASqlOutput){
      if (currentBToASql && currentBToASql.trim() !== ''){
        bToASqlOutput.textContent = currentBToASql;
      } else {
        bToASqlOutput.textContent = '-- No SQL generated. All tables and columns from Section B already exist in Section A.';
      }
    }
    
    // Show results container
    if (resultsContainer) resultsContainer.style.display = 'block';
    if (resultsContainer) resultsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });

    if (data.module_missing_in_target && data.module_missing_in_target.length) {
      alert('Module tables missing in target: ' + data.module_missing_in_target.join(', '));
    }
  }
  
  function getActiveClientIdFromForm() {
    var p = getCompareParamsFromForm();
    if (p.target_client_id && p.target_is_master !== '1') { return p.target_client_id; }
    if (p.source_client_id && p.source_is_master !== '1') { return p.source_client_id; }
    return '';
  }

  var btnTestClientConn = document.getElementById('btnTestClientConn');
  if (btnTestClientConn) {
    btnTestClientConn.addEventListener('click', function(){
      var clientId = getActiveClientIdFromForm();
      if (!clientId) {
        alert('Select a client database (not OMS Master) in Source or Target to test.');
        return;
      }
      var fd = new FormData();
      fd.append('client_id', clientId);
      fd.append('csrf_token', csrf_token);
      appendConnectionOptions(fd);
      btnTestClientConn.disabled = true;
      fetch('<?php echo site_url('db/test-client-connection'); ?>', { method: 'POST', body: fd })
        .then(parseJsonResponse)
        .then(function(data){ alert(data && data.message ? data.message : (data.success ? 'OK' : 'Failed')); })
        .catch(function(err){ alert(err.message || 'Connection test failed.'); })
        .finally(function(){ btnTestClientConn.disabled = false; });
    });
  }

  var btnCreateLocalDb = document.getElementById('btnCreateLocalDb');
  if (btnCreateLocalDb) {
    btnCreateLocalDb.addEventListener('click', function(){
      var clientId = getActiveClientIdFromForm();
      if (!clientId) {
        alert('Select Eergic (or another client) in Source or Target first.');
        return;
      }
      var fd = new FormData();
      fd.append('client_id', clientId);
      fd.append('csrf_token', csrf_token);
      btnCreateLocalDb.disabled = true;
      fetch('<?php echo site_url('db/create-local-client-db'); ?>', { method: 'POST', body: fd })
        .then(parseJsonResponse)
        .then(function(data){
          alert(data && data.message ? data.message : (data.success ? 'Done' : 'Failed'));
        })
        .catch(function(err){ alert(err.message || 'Failed'); })
        .finally(function(){ btnCreateLocalDb.disabled = false; });
    });
  }

  var btnSaveClientDbHost = document.getElementById('btnSaveClientDbHost');
  if (btnSaveClientDbHost) {
    btnSaveClientDbHost.addEventListener('click', function(){
      var clientId = getActiveClientIdFromForm();
      var host = document.getElementById('dbHostOverride') ? document.getElementById('dbHostOverride').value.trim() : '';
      var port = document.getElementById('dbPortOverride') ? document.getElementById('dbPortOverride').value.trim() : '';
      if (!clientId) {
        alert('Select a client in Source or Target.');
        return;
      }
      if (!host) {
        alert('Enter the live MySQL hostname from cPanel → Remote MySQL.');
        return;
      }
      var fd = new FormData();
      fd.append('client_id', clientId);
      fd.append('db_host', host);
      fd.append('db_port', port || '3306');
      fd.append('csrf_token', csrf_token);
      btnSaveClientDbHost.disabled = true;
      fetch('<?php echo site_url('db/save-client-db-host'); ?>', { method: 'POST', body: fd })
        .then(parseJsonResponse)
        .then(function(data){
          if (data && data.success) {
            var opt = getActiveClientOption();
            if (opt) {
              opt.setAttribute('data-db-host', data.db_host || host);
              opt.setAttribute('data-db-port', data.db_port || port);
            }
            var hostInput = document.getElementById('dbHostOverride');
            if (hostInput) {
              hostInput.value = data.db_host || host;
              hostInput.dataset.autoFilled = '1';
            }
          }
          alert(data && data.message ? data.message : (data.success ? 'Saved' : 'Failed'));
        })
        .catch(function(err){ alert(err.message || 'Save failed.'); })
        .finally(function(){ btnSaveClientDbHost.disabled = false; });
    });
  }

  var btnEnsureSchemas = document.getElementById('btnEnsureSchemas');
  if (btnEnsureSchemas) {
    btnEnsureSchemas.addEventListener('click', function(){
      btnEnsureSchemas.disabled = true;
      var fd = new FormData();
      fd.append('csrf_token', csrf_token);
      fetch('<?php echo site_url('db/ensure-schemas'); ?>', { method: 'POST', body: fd })
        .then(parseJsonResponse)
        .then(function(data){
          alert(data && data.message ? data.message : 'Schema ensure completed.');
          if (data && data.success) { location.reload(); }
        })
        .catch(function(){ alert('Failed to run schema automation.'); })
        .finally(function(){ btnEnsureSchemas.disabled = false; });
    });
  }
  
  function escapeHtml(text){
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  if (btnApplyAToB) {
    btnApplyAToB.addEventListener('click', function(){ applyDatabaseDiff('a_to_b', btnApplyAToB); });
  }
  if (btnApplyBToA) {
    btnApplyBToA.addEventListener('click', function(){ applyDatabaseDiff('b_to_a', btnApplyBToA); });
  }

  // Copy A→B SQL
  if (btnCopyAToBSql){
    btnCopyAToBSql.addEventListener('click', function(){
      copyToClipboard(currentAToBSql, 'A→B SQL copied to clipboard!');
    });
  }
  
  // Download A→B SQL
  if (btnDownloadAToBSql){
    btnDownloadAToBSql.addEventListener('click', function(){
      if (!currentAToBSql || currentAToBSql.trim() === ''){
        alert('No SQL to download.');
        return;
      }
      var sourceDb = document.getElementById('sourceDb') ? document.getElementById('sourceDb').value : 'source';
      var targetDb = document.getElementById('targetDb') ? document.getElementById('targetDb').value : 'target';
      var filename = 'A_to_B_' + sourceDb + '_to_' + targetDb + '_' + new Date().toISOString().slice(0,10) + '.sql';
      var blob = new Blob([currentAToBSql], { type: 'text/plain' });
      var url = window.URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    });
  }
  
  // Copy B→A SQL
  if (btnCopyBToASql){
    btnCopyBToASql.addEventListener('click', function(){
      copyToClipboard(currentBToASql, 'B→A SQL copied to clipboard!');
    });
  }
  
  // Download B→A SQL
  if (btnDownloadBToASql){
    btnDownloadBToASql.addEventListener('click', function(){
      if (!currentBToASql || currentBToASql.trim() === ''){
        alert('No SQL to download.');
        return;
      }
      var sourceDb = document.getElementById('sourceDb') ? document.getElementById('sourceDb').value : 'source';
      var targetDb = document.getElementById('targetDb') ? document.getElementById('targetDb').value : 'target';
      var filename = 'B_to_A_' + targetDb + '_to_' + sourceDb + '_' + new Date().toISOString().slice(0,10) + '.sql';
      var blob = new Blob([currentBToASql], { type: 'text/plain' });
      var url = window.URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    });
  }
  
})();
</script>
<?php $this->load->view('partials/footer'); ?>

