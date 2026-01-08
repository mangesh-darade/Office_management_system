<?php 
// Helper function to format values for display
function format_activity_value($value) {
  if ($value === null) {
    return '<span class="text-muted">(empty)</span>';
  }
  if ($value === '') {
    return '<span class="text-muted">(blank)</span>';
  }
  if (is_bool($value)) {
    return $value ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>';
  }
  if (is_array($value)) {
    return '<code style="font-size:0.75rem;">' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</code>';
  }
  if (is_object($value)) {
    return '<code style="font-size:0.75rem;">' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</code>';
  }
  // Format dates if they look like dates
  if (preg_match('/^\d{4}-\d{2}-\d{2}/', (string)$value)) {
    return '<span class="text-info">' . htmlspecialchars((string)$value) . '</span>';
  }
  // Default: show as-is
  return htmlspecialchars((string)$value);
}
$this->load->view('partials/header', ['title' => 'Activity Logs']); ?>
<style>
.activity-user-name { font-weight: 600; color: #374151; }
.activity-user-email { color: #6b7280; font-size: 0.875rem; }
.activity-unknown { color: #9ca3af; font-style: italic; }
.activity-badge { 
  display: inline-block; 
  padding: 0.25rem 0.5rem; 
  font-size: 0.75rem; 
  font-weight: 500; 
  border-radius: 0.25rem;
}
.activity-badge-module { background: #dbeafe; color: #1e40af; }
.activity-badge-action { background: #f3f4f6; color: #374151; }
.change-item { 
  background: #f8f9fa; 
  border: 1px solid #dee2e6; 
  border-radius: 0.25rem;
}
.change-item .old-value { 
  word-break: break-word; 
  padding: 0.25rem;
  background: #fff5f5;
  border-left: 3px solid #dc3545;
  border-radius: 0.125rem;
}
.change-item .new-value { 
  word-break: break-word; 
  padding: 0.25rem;
  background: #f0fff4;
  border-left: 3px solid #28a745;
  border-radius: 0.125rem;
}
.bg-danger-light { background: #fff5f5 !important; }
.bg-success-light { background: #f0fff4 !important; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h4 mb-0">📊 Activity Logs</h1>
    <?php if (isset($total_rows)): ?>
      <small class="text-muted">Total: <?php echo number_format($total_rows); ?> records</small>
    <?php endif; ?>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('activity/export?' . http_build_query($filters)); ?>">
    <i class="bi bi-download me-1"></i>Export CSV
  </a>
</div>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" class="row g-2">
      <div class="col-md-3">
        <label class="form-label">User</label>
        <select class="form-select" name="user_id">
          <option value="">All</option>
          <?php foreach ($users as $u): ?>
            <option value="<?php echo (int)$u->id; ?>" <?php echo (!empty($filters['user_id']) && (int)$filters['user_id']===(int)$u->id)?'selected':''; ?>><?php echo htmlspecialchars(isset($u->display_name) ? $u->display_name : $u->email); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Module</label>
        <select class="form-select" name="module">
          <option value="">All</option>
          <?php foreach ($modules as $m): ?>
            <option value="<?php echo htmlspecialchars($m); ?>" <?php echo (!empty($filters['module']) && $filters['module']===$m)?'selected':''; ?>><?php echo ucfirst($m); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Action</label>
        <select class="form-select" name="action">
          <option value="">All</option>
          <?php foreach ($actions as $a): ?>
            <option value="<?php echo htmlspecialchars($a); ?>" <?php echo (!empty($filters['action']) && $filters['action']===$a)?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ', $a)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label">From</label>
        <input type="date" class="form-control" name="from" value="<?php echo htmlspecialchars(isset($filters['from'])?$filters['from']:''); ?>" />
      </div>
      <div class="col-md-1">
        <label class="form-label">To</label>
        <input type="date" class="form-control" name="to" value="<?php echo htmlspecialchars(isset($filters['to'])?$filters['to']:''); ?>" />
      </div>
      <div class="col-md-1 align-self-end">
        <button class="btn btn-outline-secondary">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Log ID</th>
            <th>User</th>
            <th>Module</th>
            <th>Action</th>
            <th>Record ID</th>
            <th>Description</th>
            <th>IP</th>
            <th>When</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="text-center text-muted">No activity found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr class="activity-row" 
                data-log-id="<?php echo (int)$r->id; ?>"
                data-entity-id="<?php echo (int)$r->entity_id; ?>"
                data-entity-type="<?php echo htmlspecialchars($r->entity_type, ENT_QUOTES); ?>"
                data-action="<?php echo htmlspecialchars($r->action, ENT_QUOTES); ?>"
                data-changes="<?php echo htmlspecialchars($r->changes ? $r->changes : '{}', ENT_QUOTES); ?>"
                style="cursor: pointer;" 
                onclick="showActivityDetails(<?php echo (int)$r->id; ?>, <?php echo (int)$r->entity_id; ?>, '<?php echo htmlspecialchars($r->entity_type, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($r->action, ENT_QUOTES); ?>', <?php echo htmlspecialchars(json_encode($r->changes ? json_decode($r->changes, true) : null), ENT_QUOTES); ?>)">
              <td><span class="badge bg-secondary">#<?php echo (int)$r->id; ?></span></td>
              <td>
                <?php 
                  if (!empty($r->user_name)) {
                    echo '<div class="activity-user-name">' . htmlspecialchars($r->user_name) . '</div>';
                    if (!empty($r->user_email)) {
                      echo '<div class="activity-user-email">' . htmlspecialchars($r->user_email) . '</div>';
                    }
                  } elseif (!empty($r->user_email)) {
                    echo '<div class="activity-user-name">' . htmlspecialchars($r->user_email) . '</div>';
                  } else {
                    echo '<div class="activity-unknown">Unknown User (ID: ' . (int)$r->actor_id . ')</div>';
                  }
                ?>
              </td>
              <td><span class="activity-badge activity-badge-module"><?php echo htmlspecialchars(ucfirst($r->entity_type)); ?></span></td>
              <td>
                <?php 
                  $action = strtolower($r->action);
                  $action_display = ucfirst(str_replace('_',' ', $r->action));
                  
                  // Color code actions
                  $action_class = 'activity-badge-action';
                  $action_icon = '';
                  
                  if ($action === 'created' || $action === 'added' || $action === 'inserted') {
                    $action_class = 'bg-success text-white';
                    $action_icon = '<i class="bi bi-plus-circle me-1"></i>';
                  } elseif ($action === 'updated' || $action === 'edited' || $action === 'modified' || $action === 'changed') {
                    $action_class = 'bg-primary text-white';
                    $action_icon = '<i class="bi bi-pencil-square me-1"></i>';
                  } elseif ($action === 'deleted' || $action === 'removed') {
                    $action_class = 'bg-danger text-white';
                    $action_icon = '<i class="bi bi-trash me-1"></i>';
                  } elseif ($action === 'viewed' || $action === 'read') {
                    $action_class = 'bg-info text-white';
                    $action_icon = '<i class="bi bi-eye me-1"></i>';
                  }
                  
                  echo '<span class="activity-badge ' . $action_class . '">' . $action_icon . htmlspecialchars($action_display) . '</span>';
                ?>
              </td>
              <td>
                <?php if (!empty($r->entity_id)): ?>
                  <span class="badge bg-dark" title="Record ID: <?php echo (int)$r->entity_id; ?>">
                    <i class="bi bi-hash me-1"></i><?php echo (int)$r->entity_id; ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td style="max-width:500px;" onclick="event.stopPropagation();">
                <?php 
                  // Extract description and changes from JSON field
                  $description = '';
                  $has_changes = false;
                  
                  if (!empty($r->changes)) {
                    $changes_data = json_decode($r->changes, true);
                    if (is_array($changes_data)) {
                      // Get description
                      if (isset($changes_data['description'])) {
                        $description = $changes_data['description'];
                      }
                      
                      // Check if there are changes
                      if (isset($changes_data['changes']['fields']) && is_array($changes_data['changes']['fields']) && !empty($changes_data['changes']['fields'])) {
                        $has_changes = true;
                      } elseif (isset($changes_data['changes']['old']) || isset($changes_data['changes']['new'])) {
                        $has_changes = true;
                      }
                    } elseif (is_string($r->changes)) {
                      // Fallback: if changes is a plain string (old format)
                      $description = $r->changes;
                    }
                  }
                  
                  // Display description with operation summary
                  if ($description) {
                    echo '<div class="mb-1 font-weight-bold">' . htmlspecialchars($description) . '</div>';
                  }
                  
                  // Show summary of changes
                  if ($has_changes && isset($changes_data['changes']['fields'])) {
                    $changed_fields = $changes_data['changes']['fields'];
                    $field_count = count($changed_fields);
                    $field_names = array_slice(array_keys($changed_fields), 0, 3);
                    $field_display = array_map(function($f) {
                      return ucwords(str_replace('_', ' ', $f));
                    }, $field_names);
                    
                    echo '<div class="small text-muted mt-1">';
                    echo '<i class="bi bi-arrow-left-right me-1"></i>';
                    echo $field_count . ' field' . ($field_count > 1 ? 's' : '') . ' changed';
                    if ($field_count > 0) {
                      echo ': ' . implode(', ', $field_display);
                      if ($field_count > 3) {
                        echo ' and ' . ($field_count - 3) . ' more';
                      }
                    }
                    echo '</div>';
                    echo '<span class="badge bg-info text-white mt-1"><i class="bi bi-eye me-1"></i>Click to view details</span>';
                  } elseif ($has_changes) {
                    echo '<span class="badge bg-info text-white"><i class="bi bi-eye me-1"></i>Click to view changes</span>';
                  } elseif (!$description) {
                    echo '<span class="text-muted">—</span>';
                  }
                ?>
              </td>
              <td class="text-muted small"><?php echo htmlspecialchars(isset($r->ip_address)?$r->ip_address:''); ?></td>
              <td class="text-muted small"><?php echo htmlspecialchars(isset($r->created_at)?$r->created_at:''); ?></td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
    <?php if (isset($pagination) && !empty($pagination)): ?>
      <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <?php if (isset($total_rows) && isset($per_page) && isset($offset)): ?>
              <small class="text-muted">
                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $per_page, $total_rows); ?> of <?php echo number_format($total_rows); ?> entries
              </small>
            <?php endif; ?>
          </div>
          <div>
            <?php echo $pagination; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Activity Details Modal -->
<div class="modal fade" id="activityDetailsModal" tabindex="-1" aria-labelledby="activityDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="activityDetailsModalLabel">
          <i class="bi bi-clock-history me-2"></i>Activity Log Details
          <span id="modalRecordId" class="badge bg-dark ms-2" style="display:none;"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="activityDetailsContent">
        <div class="text-center">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
// Format value for display in modal
function formatActivityValue(value) {
  if (value === null) {
    return '<span class="text-muted">(empty)</span>';
  }
  if (value === '') {
    return '<span class="text-muted">(blank)</span>';
  }
  if (typeof value === 'boolean') {
    return value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
  }
  if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
    return '<pre class="mb-0" style="font-size:0.75rem; max-height:200px; overflow:auto;"><code>' + 
           escapeHtml(JSON.stringify(value, null, 2)) + '</code></pre>';
  }
  // Format dates if they look like dates
  if (typeof value === 'string' && /^\d{4}-\d{2}-\d{2}/.test(value)) {
    return '<span class="text-info"><i class="bi bi-calendar me-1"></i>' + escapeHtml(value) + '</span>';
  }
  // Default: show as-is
  return escapeHtml(String(value));
}

function escapeHtml(text) {
  var map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

// Show activity details in modal
function showActivityDetails(logId, entityId, entityType, action, changesData) {
  var modal = document.getElementById('activityDetailsModal');
  var content = document.getElementById('activityDetailsContent');
  var recordIdBadge = document.getElementById('modalRecordId');
  
  if (!modal || !content) return;
  
  // Update record ID badge in modal header
  if (recordIdBadge) {
    if (entityId && entityId > 0) {
      recordIdBadge.textContent = 'Record ID: ' + entityId;
      recordIdBadge.style.display = 'inline-block';
    } else {
      recordIdBadge.style.display = 'none';
    }
  }
  
  // Show loading
  content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><div class="mt-2 text-muted">Loading record data...</div></div>';
  
  // Determine if this is a delete operation
  var isDelete = action === 'deleted';
  
  // Fetch actual record data if entity ID and type are available (skip for deleted records)
  var recordDataPromise = Promise.resolve(null);
  if (entityId && entityId > 0 && entityType && !isDelete) {
    var site = '<?php echo rtrim(site_url(), "/"); ?>/';
    recordDataPromise = fetch(site + 'activity/get_record_data?entity_type=' + encodeURIComponent(entityType) + '&entity_id=' + entityId)
      .then(function(res) { 
        if (!res.ok) throw new Error('Failed to fetch');
        return res.json(); 
      })
      .then(function(result) {
        return result.success ? result.data : null;
      })
      .catch(function(err) {
        console.error('Error fetching record data:', err);
        return null;
      });
  }
  
  // Wait for record data, then render
  recordDataPromise.then(function(recordData) {
    // Clear content
    content.innerHTML = '';
    var html = '';
    
    // Get deleted data if this is a delete operation
    var deletedData = (isDelete && changesData && changesData.changes && changesData.changes.old) ? changesData.changes.old : null;
    
    // Show actual record data section (or deleted data if record was deleted)
    if (recordData && typeof recordData === 'object') {
      html += '<div class="mb-4">';
      html += '<h6 class="text-muted mb-3"><i class="bi bi-database me-2"></i>Current Record Data</h6>';
      html += '<div class="table-responsive">';
      html += '<table class="table table-sm table-bordered">';
      html += '<thead class="table-light"><tr><th style="width:30%;">Field</th><th>Value</th></tr></thead>';
      html += '<tbody>';
      
      var recordKeys = Object.keys(recordData);
      recordKeys.sort();
      recordKeys.forEach(function(key) {
        var value = recordData[key];
        var fieldDisplay = key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
        html += '<tr>';
        html += '<td><strong>' + escapeHtml(fieldDisplay) + '</strong></td>';
        html += '<td>' + formatActivityValue(value) + '</td>';
        html += '</tr>';
      });
      
      html += '</tbody></table>';
      html += '</div>';
      html += '</div>';
    } else if (deletedData && typeof deletedData === 'object') {
      // Show deleted data if record doesn't exist anymore
      html += '<div class="mb-4">';
      html += '<div class="alert alert-danger mb-3"><i class="bi bi-exclamation-triangle me-2"></i><strong>This record has been deleted.</strong> Showing data from deletion log:</div>';
      html += '<h6 class="text-muted mb-3"><i class="bi bi-trash me-2"></i>Deleted Record Data</h6>';
      html += '<div class="table-responsive">';
      html += '<table class="table table-sm table-bordered">';
      html += '<thead class="table-light"><tr><th style="width:30%;">Field</th><th>Value</th></tr></thead>';
      html += '<tbody>';
      
      var deletedKeys = Object.keys(deletedData);
      deletedKeys.sort();
      deletedKeys.forEach(function(key) {
        var value = deletedData[key];
        var fieldDisplay = key.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
        html += '<tr>';
        html += '<td><strong>' + escapeHtml(fieldDisplay) + '</strong></td>';
        html += '<td>' + formatActivityValue(value) + '</td>';
        html += '</tr>';
      });
      
      html += '</tbody></table>';
      html += '</div>';
      html += '</div>';
    }
    
    // Show description
    if (changesData && changesData.description) {
      html += '<div class="mb-4">';
      html += '<h6 class="text-muted mb-2"><i class="bi bi-file-text me-2"></i>Description</h6>';
      html += '<div class="p-3 bg-light rounded">' + escapeHtml(changesData.description) + '</div>';
      html += '</div>';
    }
    
    // Show changes if available
    if (changesData && changesData.changes) {
      html += '<div class="mb-3">';
      html += '<h6 class="text-muted mb-3"><i class="bi bi-arrow-left-right me-2"></i>Field Changes (Before → After)</h6>';
      
      // Field-by-field changes
      if (changesData.changes.fields && typeof changesData.changes.fields === 'object') {
        var fields = Object.keys(changesData.changes.fields);
        if (fields.length > 0) {
          fields.forEach(function(field) {
            var fieldData = changesData.changes.fields[field];
            var beforeValue = fieldData.before !== undefined ? fieldData.before : null;
            var afterValue = fieldData.after !== undefined ? fieldData.after : null;
            
            // Format field name (convert snake_case to Title Case)
            var fieldDisplay = field.replace(/_/g, ' ').replace(/\b\w/g, function(l) { return l.toUpperCase(); });
            
            html += '<div class="change-item mb-3 p-3 border rounded">';
            html += '<div class="font-weight-bold text-primary mb-3">' + escapeHtml(fieldDisplay) + '</div>';
            html += '<div class="row g-3">';
            
            // Previous Value
            html += '<div class="col-md-6">';
            html += '<div class="small text-muted mb-2"><i class="bi bi-arrow-left text-danger me-1"></i>Previous Value</div>';
            html += '<div class="old-value p-2 rounded">' + formatActivityValue(beforeValue) + '</div>';
            html += '</div>';
            
            // New Value
            html += '<div class="col-md-6">';
            html += '<div class="small text-muted mb-2"><i class="bi bi-arrow-right text-success me-1"></i>New Value</div>';
            html += '<div class="new-value p-2 rounded">' + formatActivityValue(afterValue) + '</div>';
            html += '</div>';
            
            html += '</div>'; // row
            html += '</div>'; // change-item
          });
        }
      }
      
      // Old data (for deletions) - Show in table format if not already shown above
      if (changesData.changes.old && !deletedData) {
        html += '<div class="change-item mb-3 p-3 border rounded bg-danger-light">';
        html += '<div class="font-weight-bold text-danger mb-2"><i class="bi bi-trash me-2"></i>Removed Data</div>';
        if (Array.isArray(changesData.changes.old) || typeof changesData.changes.old === 'object') {
          var oldKeys = Object.keys(changesData.changes.old);
          oldKeys.forEach(function(key) {
            html += '<div class="mb-2">';
            html += '<strong>' + escapeHtml(key) + ':</strong> ';
            html += '<span class="ms-2">' + formatActivityValue(changesData.changes.old[key]) + '</span>';
            html += '</div>';
          });
        } else {
          html += '<div>' + formatActivityValue(changesData.changes.old) + '</div>';
        }
        html += '</div>';
      }
      
      // New data (for insertions)
      if (changesData.changes.new) {
        html += '<div class="change-item mb-3 p-3 border rounded bg-success-light">';
        html += '<div class="font-weight-bold text-success mb-2"><i class="bi bi-plus-circle me-2"></i>Added Data</div>';
        if (Array.isArray(changesData.changes.new) || typeof changesData.changes.new === 'object') {
          var newKeys = Object.keys(changesData.changes.new);
          newKeys.forEach(function(key) {
            html += '<div class="mb-2">';
            html += '<strong>' + escapeHtml(key) + ':</strong> ';
            html += '<span class="ms-2">' + formatActivityValue(changesData.changes.new[key]) + '</span>';
            html += '</div>';
          });
        } else {
          html += '<div>' + formatActivityValue(changesData.changes.new) + '</div>';
        }
        html += '</div>';
      }
      
      html += '</div>'; // mb-3
    }
    
    // If no data at all, show message
    if (!recordData && (!changesData || (!changesData.changes && !changesData.description))) {
      html = '<div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>No data available for this activity log.</div>';
    }
    
    content.innerHTML = html;
  });
  
  // Show modal using Bootstrap
  if (window.bootstrap && window.bootstrap.Modal) {
    var bsModal = new bootstrap.Modal(modal);
    bsModal.show();
  } else {
    // Fallback if Bootstrap JS not available
    modal.style.display = 'block';
    modal.classList.add('show');
    modal.removeAttribute('aria-hidden');
    document.body.classList.add('modal-open');
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    document.body.appendChild(backdrop);
  }
}
</script>

<style>
.activity-row:hover {
  background-color: #f8f9fa !important;
}
.change-item .old-value {
  background: #fff5f5;
  border-left: 3px solid #dc3545;
  min-height: 40px;
}
.change-item .new-value {
  background: #f0fff4;
  border-left: 3px solid #28a745;
  min-height: 40px;
}
</style>

<?php $this->load->view('partials/footer'); ?>
