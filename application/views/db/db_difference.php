<?php $this->load->view('partials/header', ['title' => 'DB Difference']); ?>
<?php if (!has_module_access('db')) { echo '<div class="alert alert-danger">Forbidden</div>'; $this->load->view('partials/footer'); return; } ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">DB Difference</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-secondary btn-sm" href="<?php echo site_url('db'); ?>">Back to DB Manager</a>
  </div>
</div>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <h5 class="card-title mb-3">Compare Two Databases</h5>
    <form id="dbDifferenceForm">
      <div class="row">
        <div class="col-md-5">
          <label class="form-label">Source Database (1st Dropdown)</label>
          <select class="form-select" id="sourceDb" name="source_db" required>
            <option value="">-- Select Source Database --</option>
            <?php foreach ($clients as $cl): ?>
              <?php if (!empty($cl->db_name)): ?>
                <option value="<?php echo htmlspecialchars($cl->db_name); ?>" 
                        data-client-id="<?php echo (int)$cl->id; ?>"
                        data-client-name="<?php echo htmlspecialchars($cl->company_name); ?>">
                  <?php echo htmlspecialchars($cl->company_name . ' (' . $cl->db_name . ')'); ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label">Target Database (2nd Dropdown)</label>
          <select class="form-select" id="targetDb" name="target_db" required>
            <option value="">-- Select Target Database --</option>
            <?php foreach ($clients as $cl): ?>
              <?php if (!empty($cl->db_name)): ?>
                <option value="<?php echo htmlspecialchars($cl->db_name); ?>" 
                        data-client-id="<?php echo (int)$cl->id; ?>"
                        data-client-name="<?php echo htmlspecialchars($cl->company_name); ?>">
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
        <strong>Purpose:</strong> This SQL will add missing tables and columns from Section A to Section B.
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
        <strong>Purpose:</strong> This SQL will add missing tables and columns from Section B to Section A.
      </div>
      <pre id="bToASqlOutput" class="bg-light border rounded p-3 small" style="white-space:pre-wrap;max-height:400px;overflow:auto;font-family: 'Courier New', monospace;"></pre>
    </div>
  </div>
</div>

<script>
(function(){
  var form = document.getElementById('dbDifferenceForm');
  var resultsContainer = document.getElementById('resultsContainer');
  var btnCompare = document.getElementById('btnCompare');
  var btnCopyAToBSql = document.getElementById('btnCopyAToBSql');
  var btnDownloadAToBSql = document.getElementById('btnDownloadAToBSql');
  var btnCopyBToASql = document.getElementById('btnCopyBToASql');
  var btnDownloadBToASql = document.getElementById('btnDownloadBToASql');
  var aToBSqlOutput = document.getElementById('aToBSqlOutput');
  var bToASqlOutput = document.getElementById('bToASqlOutput');
  var currentAToBSql = '';
  var currentBToASql = '';
  
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
      
      if (btnCompare){ 
        btnCompare.disabled = true; 
        btnCompare.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Comparing...';
      }
      
      var formData = new FormData();
      formData.append('source_db', sourceDb);
      formData.append('target_db', targetDb);
      formData.append('source_client_id', sourceClientId);
      formData.append('target_client_id', targetClientId);
      
      fetch('<?php echo site_url('db/compare-databases'); ?>', {
        method: 'POST',
        body: formData
      })
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (data && data.success){
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
    currentAToBSql = data.sql || '';
    
    // B→A: What's missing in A (from B)
    var missingTablesBToA = data.reverse_missing_tables || [];
    var missingColumnsBToA = data.reverse_missing_columns || [];
    currentBToASql = data.reverse_sql || '';
    
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
  }
  
  function escapeHtml(text){
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
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

