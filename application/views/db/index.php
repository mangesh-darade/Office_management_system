<?php $this->load->view('partials/header', ['title' => 'DB Manager']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">DB Manager</h1>
  <a class="btn btn-secondary btn-sm" href="<?php echo site_url('dashboard'); ?>">Back</a>
</div>
<?php if (!has_module_access('db')) { echo '<div class="alert alert-danger">Forbidden</div>'; $this->load->view('partials/footer'); return; } ?>
<?php if ($error): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($info): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($info); ?></div>
<?php endif; ?>
<style>
  /* Compact SQL preview cells */
  .sql-cell{white-space:pre-wrap;background:#f8f9fa;border-left:3px solid #0d6efd;padding:6px 8px;margin:0;max-width:600px;max-height:60px;overflow:auto;font-family:ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;font-size:12px;line-height:1.35;}
  @media (max-width: 768px){ .sql-cell{max-width:100%;} }
</style>
<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="post" action="<?php echo site_url('db/queries/save'); ?>">
      <div class="row g-2 align-items-end">
        <div class="col-lg-3 col-md-4">
          <label class="form-label">Project</label>
          <select class="form-select" name="project_id" id="projectSelectTop">
            <option value="">-- Select --</option>
            <?php foreach ($projects as $p): ?>
              <option value="<?php echo (int)$p->id; ?>" <?php if(isset($p->db_name)&&$p->db_name){ echo 'data-dbname="'.htmlspecialchars($p->db_name).'"'; } ?>><?php echo htmlspecialchars($p->name); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-lg-3 col-md-4">
          <label class="form-label">Assign To</label>
          <select class="form-select" name="assigned_to" id="assignSelectTop">
            <option value="">-- Select --</option>
            <?php foreach (($assignees?:[]) as $u): ?>
              <?php $label = !empty($u->emp_name) ? $u->emp_name : (!empty($u->full_name)?$u->full_name:(!empty($u->name)?$u->name:$u->email)); ?>
              <option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($label); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-lg-2 col-md-4">
          <label class="form-label">Version</label>
          <input class="form-control" name="version" placeholder="e.g. 18.02" />
        </div>
        <div class="col-lg-4 col-md-12">
          <label class="form-label">Title</label>
          <input class="form-control" name="title" placeholder="e.g. Job Works" />
        </div>
      </div>
      <div class="row g-2 align-items-end mt-1">
        <div class="col-12">
          <label class="form-label">SQL (paste here)</label>
          <textarea class="form-control" name="sql_text" rows="2" placeholder="ALTER TABLE ..." required></textarea>
        </div>
      </div>
      <div class="mt-2 d-flex gap-2">
        <div class="form-check align-self-center">
          <input class="form-check-input" type="checkbox" value="1" id="validateSqlCheck" name="validate_sql">
          <label class="form-check-label" for="validateSqlCheck">Validate SQL before save</label>
        </div>
        <button class="btn btn-primary">Save Query</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <h2 class="h6 mb-2">Saved Queries</h2>
    <form method="get" action="<?php echo site_url('db'); ?>" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label">Filter Project</label>
        <select class="form-select" name="q_project_id">
          <option value="">-- Any --</option>
          <?php foreach ($projects as $p): ?>
            <option value="<?php echo (int)$p->id; ?>" <?php echo (!empty($filter_project_id) && (int)$filter_project_id===(int)$p->id)?'selected':''; ?>><?php echo htmlspecialchars($p->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Filter Assignee</label>
        <select class="form-select" name="q_assigned_to">
          <option value="">-- Any --</option>
          <?php foreach (($assignees?:[]) as $u): ?>
            <?php $label = !empty($u->emp_name) ? $u->emp_name : (!empty($u->full_name)?$u->full_name:(!empty($u->name)?$u->name:$u->email)); ?>
            <option value="<?php echo (int)$u->id; ?>" <?php echo (!empty($filter_assigned_to) && (int)$filter_assigned_to===(int)$u->id)?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Filter Version</label>
        <input class="form-control" name="q_version" value="<?php echo htmlspecialchars(isset($filter_version)?$filter_version:''); ?>" placeholder="e.g. 18.02" />
      </div>
      <div class="col-md-3 text-end">
        <button class="btn btn-outline-primary">Apply Filters</button>
        <a class="btn btn-link text-decoration-none ms-2" href="<?php echo site_url('db'); ?>">Clear</a>
      </div>
    </form>
    <div class="d-flex justify-content-between align-items-center mt-2">
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" id="btnCopySelected">Copy Selected</button>
        <form id="bulkExportForm" method="post" action="<?php echo site_url('db/queries/export-bulk'); ?>" class="d-inline">
          <input type="hidden" name="ids[]" value="" />
          <button type="submit" class="btn btn-outline-dark btn-sm" id="btnExportSelected">Export Selected (.sql)</button>
        </form>
      </div>
      <div class="small text-muted">Total: <?php echo is_array($saved_queries)?count($saved_queries):0; ?></div>
    </div>
    <div class="table-responsive mt-2">
      <table class="table table-sm align-middle datatable-ajax" id="savedQueriesTable">
        <thead>
          <tr>
            <th><input type="checkbox" id="selectAll"></th>
            <th>#</th>
            <th>Project</th>
            <th>Version</th>
            <th>Title</th>
            <th>Query</th>
            <th style="width:180px">Actions</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
</div>
<!-- Modals -->
<div class="modal fade" id="showSqlModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Saved Query</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 small text-muted" id="showMeta"></div>
        <pre class="bg-light p-3 rounded small" id="showSql" style="white-space:pre-wrap; word-break:break-word;"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" id="showCopyBtn" class="btn btn-outline-secondary">Copy</button>
        <a id="showExportLink" href="#" class="btn btn-dark">Export</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
  

<div class="modal fade" id="editSqlModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" id="editForm" action="#">
        <div class="modal-header">
          <h5 class="modal-title">Edit Saved Query</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Title</label>
              <input class="form-control" name="title" id="editTitle" required />
            </div>
            <div class="col-md-3">
              <label class="form-label">Version</label>
              <input class="form-control" name="version" id="editVersion" />
            </div>
            <div class="col-md-3">
              <label class="form-label">Project</label>
              <select class="form-select" name="project_id" id="editProject">
                <option value="">-- None --</option>
                <?php foreach ($projects as $p): ?>
                  <option value="<?php echo (int)$p->id; ?>"><?php echo htmlspecialchars($p->name); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Assign To</label>
              <select class="form-select" name="assigned_to" id="editAssigned">
                <option value="">-- None --</option>
                <?php foreach (($assignees?:[]) as $u): ?>
                  <?php $label = !empty($u->emp_name) ? $u->emp_name : (!empty($u->full_name)?$u->full_name:(!empty($u->name)?$u->name:$u->email)); ?>
                  <option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">SQL</label>
              <textarea class="form-control" name="sql_text" id="editSql" rows="6" required></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function addCol(){
  var container = document.getElementById('cols');
  var row = document.createElement('div');
  row.className = 'row g-2 mb-2 col-row';
  row.innerHTML = document.querySelector('#cols .col-row').innerHTML;
  container.appendChild(row);
}
function exportQuery(){ return false; }
// Auto-generate title from SQL on submit
(function(){
  var topForm = document.querySelector('form[action$="db/queries/save"]');
  if (topForm){
    topForm.addEventListener('submit', function(){
      try{
        var ta = topForm.querySelector('textarea[name="sql_text"]');
        var ver = topForm.querySelector('input[name="version"]').value.trim();
        var sql = (ta && ta.value || '').trim();
        var title = '';
        if (sql){
          title = sql.split(/\s+/).slice(0,6).join(' ');
        }
        if (!title){ title = 'Saved Query'; }
        if (ver){ title = '['+ver+'] ' + title; }
        var hid = document.getElementById('topTitleHidden');
        if (hid){ hid.value = title; }
      }catch(e){}
    });
  }
})();
// Simple toast helper (Bootstrap Toast)
function showToast(msg, variant){
  try{
    var cont = document.getElementById('toast-container');
    if (!cont){ cont = document.createElement('div'); cont.id='toast-container'; document.body.appendChild(cont); }
    var el = document.createElement('div');
    el.className = 'toast text-bg-'+(variant||'success')+' border-0';
    el.setAttribute('role','alert'); el.setAttribute('aria-live','assertive'); el.setAttribute('aria-atomic','true');
    el.innerHTML = '<div class="d-flex"><div class="toast-body">'+ (msg||'Done') +'</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    cont.appendChild(el);
    var t = new bootstrap.Toast(el, { delay: 2000 });
    el.addEventListener('hidden.bs.toast', function(){ try{ el.remove(); }catch(e){} });
    t.show();
  }catch(e){}
}
// Row actions: Show and Edit
// AJAX DataTable for saved queries
(function(){
  var showModal = document.getElementById('showSqlModal');
  var editModal = document.getElementById('editSqlModal');
  var bsShow = null, bsEdit = null;
  function ensure(){ try { if (!bsShow && window.bootstrap) bsShow = new bootstrap.Modal(showModal); if (!bsEdit && window.bootstrap) bsEdit = new bootstrap.Modal(editModal); } catch(e){} }
  function initSavedDT(){
    var tbl = new DataTable('#savedQueriesTable', {
      responsive: true,
      paging: true,
      searching: true,
      lengthChange: true,
      order: [],
      ajax: {
        url: '<?php echo site_url('index.php/db/queries/list'); ?>',
        data: function(d){
          var f = document.querySelector('form[action$="db"]');
          if (f){
            d.q_project_id = (f.querySelector('[name="q_project_id"]').value||'');
            d.q_assigned_to = (f.querySelector('[name="q_assigned_to"]').value||'');
            d.q_version = (f.querySelector('[name="q_version"]').value||'');
          }
        },
        error: function(xhr){
          try{ console.error('DT AJAX error', xhr.responseText); alert('Failed to load data for grid. See console for details.'); }catch(e){}
        }
      }
    });
    // Update total count after load
    tbl.on('xhr', function(){
      try{ var data = tbl.ajax.json(); var n = (data && data.data)? data.data.length : 0; document.querySelector('.small.text-muted').textContent = 'Total: '+n; }catch(e){}
    });
    // Bind row actions on each draw
    tbl.on('draw', function(){
      document.querySelectorAll('#savedQueriesTable .btn-show').forEach(function(btn){
        btn.onclick = function(){
          ensure();
          var id = this.getAttribute('data-id');
          var title = this.getAttribute('data-title')||'';
          var ver = this.getAttribute('data-version')||'';
          var sql = this.getAttribute('data-sql')||'';
          document.getElementById('showMeta').textContent = (title?title:'') + (ver?(' • v'+ver):'') + ' • #'+id;
          document.getElementById('showSql').textContent = sql;
          document.getElementById('showExportLink').href = '<?php echo site_url('db/queries/export/'); ?>'+id;
          var copyBtn = document.getElementById('showCopyBtn');
          if (copyBtn){
            copyBtn.onclick = function(){
              var text = document.getElementById('showSql').textContent || '';
              try{ navigator.clipboard.writeText(text); showToast('Query copied'); }catch(e){ var ta=document.createElement('textarea'); ta.value=text; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); showToast('Query copied'); }catch(e2){} document.body.removeChild(ta);}        
            };
          }
          try{ bsShow.show(); }catch(e){ showModal.style.display='block'; }
        };
      });
      document.querySelectorAll('#savedQueriesTable .btn-edit').forEach(function(btn){
        btn.onclick = function(){
          ensure();
          var id = this.getAttribute('data-id');
          document.getElementById('editForm').action = '<?php echo site_url('db/queries/update/'); ?>'+id;
          document.getElementById('editTitle').value = this.getAttribute('data-title')||'';
          document.getElementById('editVersion').value = this.getAttribute('data-version')||'';
          document.getElementById('editSql').value = this.getAttribute('data-sql')||'';
          var pid = parseInt(this.getAttribute('data-project')||'0',10)||'';
          var asg = parseInt(this.getAttribute('data-assigned')||'0',10)||'';
          document.getElementById('editProject').value = pid;
          document.getElementById('editAssigned').value = asg;
          try{ bsEdit.show(); }catch(e){ editModal.style.display='block'; }
        };
      });
      // Rebind copy buttons
      document.querySelectorAll('#savedQueriesTable .btn-copy').forEach(function(btn){
        btn.onclick = function(){
          var sql = this.getAttribute('data-sql') || '';
          try{ navigator.clipboard.writeText(sql); showToast('Query copied'); }catch(e){ var ta=document.createElement('textarea'); ta.value=sql; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); showToast('Query copied'); }catch(e2){} document.body.removeChild(ta);}        
        };
      });
    });
    // Filter form submit hooks to reload
    var filterForm = document.querySelector('form[action$="db"]');
    if (filterForm){ filterForm.addEventListener('submit', function(e){ /* allow normal query params but also ajax reload */ setTimeout(function(){ tbl.ajax.reload(); }, 10); }); }
    // Clear link should reload via ajax after nav
  }
  // Wait until DataTables library is loaded (it's included in footer after this script)
  (function waitDT(){ if (window.DataTable) { try{ initSavedDT(); }catch(e){} } else { setTimeout(waitDT, 50); } })();
  // Delegated handlers so buttons work after redraws
  document.addEventListener('click', function(ev){
    var btnShow = ev.target.closest('#savedQueriesTable .btn-show');
    if (btnShow){
      ev.preventDefault(); ensure();
      var id = btnShow.getAttribute('data-id');
      var title = btnShow.getAttribute('data-title')||'';
      var ver = btnShow.getAttribute('data-version')||'';
      var sql = btnShow.getAttribute('data-sql')||'';
      document.getElementById('showMeta').textContent = (title?title:'') + (ver?(' • v'+ver):'') + ' • #'+id;
      document.getElementById('showSql').textContent = sql;
      document.getElementById('showExportLink').href = '<?php echo site_url('db/queries/export/'); ?>'+id;
      var copyBtn = document.getElementById('showCopyBtn');
      if (copyBtn){ copyBtn.onclick = function(){ var text = document.getElementById('showSql').textContent || ''; try{ navigator.clipboard.writeText(text); showToast('Query copied'); }catch(e){ var ta=document.createElement('textarea'); ta.value=text; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); showToast('Query copied'); }catch(e2){} document.body.removeChild(ta);} }; }
      try{ bsShow.show(); }catch(e){ showModal.style.display='block'; }
    }
    var btnEdit = ev.target.closest('#savedQueriesTable .btn-edit');
    if (btnEdit){
      ev.preventDefault(); ensure();
      var id2 = btnEdit.getAttribute('data-id');
      document.getElementById('editForm').action = '<?php echo site_url('db/queries/update/'); ?>'+id2;
      document.getElementById('editTitle').value = btnEdit.getAttribute('data-title')||'';
      document.getElementById('editVersion').value = btnEdit.getAttribute('data-version')||'';
      document.getElementById('editSql').value = btnEdit.getAttribute('data-sql')||'';
      var pid = parseInt(btnEdit.getAttribute('data-project')||'0',10)||'';
      var asg = parseInt(btnEdit.getAttribute('data-assigned')||'0',10)||'';
      document.getElementById('editProject').value = pid;
      document.getElementById('editAssigned').value = asg;
      try{ bsEdit.show(); }catch(e){ editModal.style.display='block'; }
    }
  });
})();
// Selection and copy/export bulk
(function(){
  var tbl = document.getElementById('savedQueriesTable');
  if (!tbl) return;
  var selectAll = document.getElementById('selectAll');
  function rows(){ return Array.prototype.slice.call(tbl.querySelectorAll('tbody tr')); }
  function sels(){ return Array.prototype.slice.call(tbl.querySelectorAll('tbody .rowSel')); }
  if (selectAll){
    selectAll.addEventListener('change', function(){
      sels().forEach(function(cb){ cb.checked = selectAll.checked; });
    });
  }
  // Per-row Copy button
  tbl.querySelectorAll('.btn-copy').forEach(function(btn){
    btn.addEventListener('click', function(){
      var sql = this.getAttribute('data-sql') || '';
      try{ navigator.clipboard.writeText(sql); }catch(e){
        var ta = document.createElement('textarea'); ta.value = sql; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); }catch(e2){} document.body.removeChild(ta);
      }
    });
  });
  // Copy Selected
  var btnCopySel = document.getElementById('btnCopySelected');
  if (btnCopySel){
    btnCopySel.addEventListener('click', function(ev){
      ev.preventDefault();
      var ids = sels().filter(function(cb){ return cb.checked; }).map(function(cb){ return cb.value; });
      if (!ids.length) return;
      var sqls = [];
      rows().forEach(function(tr){
        var cb = tr.querySelector('.rowSel');
        if (!cb || ids.indexOf(cb.value) === -1) return;
        var btnShow = tr.querySelector('.btn-show');
        var fullSql = btnShow ? (btnShow.getAttribute('data-sql')||'') : '';
        if (fullSql) sqls.push(fullSql);
      });
      var all = sqls.join('\n\n');
      try{ navigator.clipboard.writeText(all); showToast('Selected queries copied'); }catch(e){
        var ta = document.createElement('textarea'); ta.value = all; document.body.appendChild(ta); ta.select(); try{ document.execCommand('copy'); showToast('Selected queries copied'); }catch(e2){} document.body.removeChild(ta);
      }
    });
  }
  // Export Selected
  var bulkForm = document.getElementById('bulkExportForm');
  var btnExportSel = document.getElementById('btnExportSelected');
  if (bulkForm && btnExportSel){
    btnExportSel.addEventListener('click', function(){
      // Clear previous ids
      Array.prototype.slice.call(bulkForm.querySelectorAll('input[name="ids[]"]')).forEach(function(n, idx){ if (idx>0) n.parentNode.removeChild(n); });
      var ids = sels().filter(function(cb){ return cb.checked; }).map(function(cb){ return cb.value; });
      if (!ids.length){ return false; }
      ids.forEach(function(id, i){ var input = document.createElement('input'); input.type='hidden'; input.name='ids[]'; input.value = id; bulkForm.appendChild(input); });
      // allow submit to proceed
    });
  }
})();
</script>
<?php $this->load->view('partials/footer'); ?>
