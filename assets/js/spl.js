(function () {
  'use strict';

  var cfg = window.SPL_CONFIG || {};
  var addRuleBtn = document.getElementById('splAddRuleRow');
  var rulesImportBtn = document.getElementById('splRulesImportBtn');
  var rulesImportFile = document.getElementById('splRulesImportFile');
  var rulesImportForm = document.getElementById('splRulesImportForm');

  function getActivityForm(root) {
    var scope = root && root.querySelector ? root : document;
    return {
      categorySelect: scope.querySelector('#splCategorySelect'),
      activitySelect: scope.querySelector('#splActivitySelect'),
      pointsHint: scope.querySelector('#splActivityPointsHint'),
      form: scope.querySelector('#splSubmitForm')
    };
  }

  function getSplCfg() {
    return window.SPL_CONFIG || cfg || {};
  }

  function getRulesTable(root) {
    if (root && root.querySelector) {
      var scoped = root.querySelector('#splRulesTable');
      if (scoped) {
        return scoped;
      }
    }
    return document.getElementById('splRulesTable');
  }

  function getRulesDataTable(tableEl) {
    tableEl = tableEl || getRulesTable();
    if (!tableEl || !window.DataTable) {
      return null;
    }
    try {
      return DataTable.get(tableEl);
    } catch (e) {
      return null;
    }
  }

  function syncRuleRowDisplay(row) {
    if (!row) {
      return;
    }
    var codeInput = row.querySelector('.spl-rule-code');
    var nameInput = row.querySelector('.spl-rule-name');
    var catSelect = row.querySelector('.spl-rule-category');
    var triggerInput = row.querySelector('.spl-rule-trigger');
    var pointsInput = row.querySelector('.spl-rule-points');
    var activeInput = row.querySelector('.spl-rule-active');
    var dispCode = row.querySelector('.spl-rule-display-code');
    var dispName = row.querySelector('.spl-rule-display-name');
    var dispCat = row.querySelector('.spl-rule-display-category');
    var dispTrigger = row.querySelector('.spl-rule-display-trigger');
    var dispPoints = row.querySelector('.spl-rule-display-points');
    var dispActive = row.querySelector('.spl-rule-display-active');

    if (dispCode && codeInput) {
      dispCode.textContent = codeInput.value.trim();
      dispCode.setAttribute('title', codeInput.value.trim());
    }
    if (dispName && nameInput) {
      dispName.textContent = nameInput.value.trim();
      dispName.setAttribute('title', nameInput.value.trim());
    }
    if (dispCat && catSelect) {
      var catLabel = catSelect.options[catSelect.selectedIndex] ? catSelect.options[catSelect.selectedIndex].textContent.trim() : '—';
      dispCat.textContent = catLabel || '—';
    }
    if (dispTrigger && triggerInput) {
      dispTrigger.textContent = '';
      var codeEl = document.createElement('code');
      codeEl.textContent = triggerInput.value.trim();
      dispTrigger.appendChild(codeEl);
    }
    if (dispPoints && pointsInput) {
      dispPoints.textContent = pointsInput.value || '0';
    }
    if (dispActive && activeInput) {
      dispActive.innerHTML = activeInput.checked
        ? '<span class="spl-rule-status spl-rule-status--on">Active</span>'
        : '<span class="spl-rule-status spl-rule-status--off">Inactive</span>';
    }
  }

  function setRuleCellOrder(row, selector, value) {
    var field = row.querySelector(selector);
    if (!field) {
      return;
    }
    var cell = field.closest('td');
    if (cell) {
      cell.setAttribute('data-order', value);
    }
  }

  function syncRuleRowOrder(row) {
    if (!row) {
      return;
    }
    var codeInput = row.querySelector('.spl-rule-code');
    var nameInput = row.querySelector('.spl-rule-name');
    var catSelect = row.querySelector('.spl-rule-category');
    var triggerInput = row.querySelector('.spl-rule-trigger');
    var pointsInput = row.querySelector('.spl-rule-points');
    var activeInput = row.querySelector('.spl-rule-active');
    if (catSelect) {
      var catLabel = catSelect.options[catSelect.selectedIndex] ? catSelect.options[catSelect.selectedIndex].textContent.trim() : '';
      setRuleCellOrder(row, '.spl-rule-category', catLabel);
    }
    if (nameInput) {
      setRuleCellOrder(row, '.spl-rule-name', nameInput.value.trim());
    }
    if (triggerInput) {
      setRuleCellOrder(row, '.spl-rule-trigger', triggerInput.value.trim());
    }
    if (pointsInput) {
      setRuleCellOrder(row, '.spl-rule-points', pointsInput.value || '0');
    }
    if (activeInput) {
      setRuleCellOrder(row, '.spl-rule-active', activeInput.checked ? '1' : '0');
    }
    if (codeInput) {
      setRuleCellOrder(row, '.spl-rule-code', codeInput.value.trim());
    }
  }

  function drawRulesTable() {
    var dt = getRulesDataTable();
    if (dt) {
      dt.draw(false);
    }
  }

  function removeRuleRow(row) {
    if (!row) {
      return;
    }
    var table = row.closest('table') || getRulesTable();
    var dt = getRulesDataTable(table);
    if (dt) {
      try {
        dt.row(row).remove().draw(false);
        return;
      } catch (e) {
        // fall through to DOM remove
      }
    }
    row.remove();
  }

  function appendRuleRow(row) {
    var dt = getRulesDataTable();
    if (dt) {
      dt.row.add(row).draw(false);
      return;
    }
    var tbody = getRulesTable() ? getRulesTable().querySelector('tbody') : null;
    if (tbody) {
      tbody.appendChild(row);
    }
  }

  function bindSplRulesImport(root) {
    var scope = root && root.querySelector ? root : document;
    var importBtn = scope.querySelector ? scope.querySelector('#splRulesImportBtn') : document.getElementById('splRulesImportBtn');
    var importFile = scope.querySelector ? scope.querySelector('#splRulesImportFile') : document.getElementById('splRulesImportFile');
    var importForm = scope.querySelector ? scope.querySelector('#splRulesImportForm') : document.getElementById('splRulesImportForm');
    if (!importBtn || !importFile || !importForm || importBtn.getAttribute('data-spl-import-bound') === '1') {
      return;
    }
    importBtn.setAttribute('data-spl-import-bound', '1');
    importBtn.addEventListener('click', function () {
      importFile.click();
    });
    importFile.addEventListener('change', function () {
      if (!importFile.files || !importFile.files.length) {
        return;
      }
      importForm.submit();
    });
  }

  function bindSplRulesTable(table) {
    if (!table) {
      return;
    }
    ensureSplRulesClickDelegation();
    table.querySelectorAll('tbody tr.spl-rule-row').forEach(function (row) {
      setRuleRowEditing(row, false);
    });
  }

  function ensureSplRulesClickDelegation() {
    if (document.documentElement.getAttribute('data-spl-rules-click') === '1') {
      return;
    }
    document.documentElement.setAttribute('data-spl-rules-click', '1');
    document.addEventListener('click', function (e) {
      if (!e.target.closest('#splRulesTable')) {
        return;
      }
      handleSplRulesTableClick(e);
    });
  }

  function handleSplRulesTableClick(e) {
    var activeCfg = getSplCfg();
    var toggleBtn = e.target.closest('.spl-toggle-rule');
    var delBtn = e.target.closest('.spl-delete-rule');
    if (toggleBtn) {
      e.preventDefault();
      var row = toggleBtn.closest('tr');
      if (!row) {
        return;
      }
      if (!row.classList.contains('is-editing')) {
        setRuleRowEditing(row, true);
        return;
      }
      if (!activeCfg.saveRuleUrl) {
        showSplToast('Cannot save rule. Reload the Rules tab and try again.', 'danger');
        return;
      }
      ensureRuleCode(row);
      var payload = ruleRowPayload(row);
      if (!payload) {
        showSplToast('Name is required.', 'danger');
        return;
      }
      postForm(activeCfg.saveRuleUrl, payload).then(function (res) {
        var savedId = res && res.data ? parseInt(res.data.id, 10) : 0;
        if (res && res.status === 'success' && savedId > 0) {
          row.setAttribute('data-rule-id', String(savedId));
          if (payload.code) {
            row.setAttribute('data-rule-code', payload.code);
          }
          row.classList.add('spl-rule-row');
          syncRuleRowOrder(row);
          syncRuleRowDisplay(row);
          setRuleRowEditing(row, false);
          drawRulesTable();
          var ruleName = row.querySelector('.spl-rule-name');
          var label = ruleName && ruleName.value.trim() ? ruleName.value.trim() : 'Rule';
          showRuleSaveMessage(label + ' saved successfully.');
        } else {
          showSplToast((res && res.message) ? res.message : 'Could not save rule.', 'danger');
        }
      }).catch(function (err) {
        showSplToast((err && err.message) ? err.message : 'Could not save rule. Please try again.', 'danger');
      });
      return;
    }
    if (delBtn) {
      e.preventDefault();
      e.stopPropagation();
      var delRow = delBtn.closest('tr');
      var id = delRow ? String(delRow.getAttribute('data-rule-id') || '').trim() : '';
      if (!id) {
        removeRuleRow(delRow);
        return;
      }
      if (!confirm('Delete this rule permanently?')) {
        return;
      }
      if (!activeCfg.deleteRuleUrlBase) {
        showSplToast('Cannot delete rule. Reload the Rules tab and try again.', 'danger');
        return;
      }
      var deleteBase = String(activeCfg.deleteRuleUrlBase || '').replace(/\/?$/, '/');
      postForm(deleteBase + id, {}).then(function (res) {
        if (res && res.status === 'success') {
          removeRuleRow(delRow);
          showRuleSaveMessage('Rule deleted successfully.');
        } else {
          showSplToast((res && res.message) ? res.message : 'Could not delete rule.', 'danger');
        }
      }).catch(function (err) {
        showSplToast((err && err.message) ? err.message : 'Could not delete rule. Please refresh and try again.', 'danger');
      });
    }
  }

  function bindSplAddRuleButton(root, table) {
    var scope = root && root.querySelector ? root : document;
    var addBtn = scope.querySelector ? scope.querySelector('#splAddRuleRow') : document.getElementById('splAddRuleRow');
    if (!addBtn || !table || addBtn.getAttribute('data-spl-add-bound') === '1') {
      return;
    }
    addBtn.setAttribute('data-spl-add-bound', '1');
    addBtn.addEventListener('click', function () {
      var activeCfg = getSplCfg();
      var tr = document.createElement('tr');
      tr.className = 'spl-rule-row is-editing';
      tr.setAttribute('data-rule-id', '');
      var catOptions = '<option value="">—</option>';
      (activeCfg.categories || []).forEach(function (c) {
        catOptions += '<option value="' + c.id + '">' + c.name + '</option>';
      });
      tr.innerHTML = ''
        + '<td data-order=""><span class="spl-rule-display spl-rule-display-category d-none">—</span>'
        + '<select class="form-select form-select-sm spl-rule-field spl-rule-category">' + catOptions + '</select></td>'
        + '<td data-order=""><span class="spl-rule-display spl-rule-display-name d-none"></span>'
        + '<input class="form-control form-control-sm spl-rule-field spl-rule-name" placeholder="Name"></td>'
        + '<td data-order="reward_claim"><span class="spl-rule-display spl-rule-display-trigger d-none"><code>reward_claim</code></span>'
        + '<input class="form-control form-control-sm spl-rule-field spl-rule-trigger" value="reward_claim"></td>'
        + '<td class="text-end" data-order="10"><span class="spl-rule-display spl-rule-display-points d-none">10</span>'
        + '<input type="number" step="1" class="form-control form-control-sm spl-rule-field spl-rule-points text-end" value="10"></td>'
        + '<td class="text-center" data-order="1"><span class="spl-rule-display spl-rule-display-active d-none">'
        + '<span class="spl-rule-status spl-rule-status--on">Active</span></span>'
        + '<div class="spl-rule-field form-check form-switch justify-content-center mb-0">'
        + '<input type="checkbox" class="form-check-input spl-rule-active" role="switch" checked></div></td>'
        + '<td class="text-end text-nowrap spl-rules-actions-cell no-sort">'
        + '<button type="button" class="btn btn-sm btn-primary spl-toggle-rule">Save</button> '
        + '<button type="button" class="btn btn-sm btn-outline-danger spl-delete-rule">Delete</button></td>'
        + '<td class="d-none spl-rule-code-col" aria-hidden="true" data-order="">'
        + '<span class="spl-rule-display spl-rule-display-code d-none"></span>'
        + '<input class="form-control form-control-sm spl-rule-field spl-rule-code" placeholder="code"></td>';
      appendRuleRow(tr);
      var nameField = tr.querySelector('.spl-rule-name');
      if (nameField) {
        nameField.focus();
      }
    });
  }

  function initRulesDataTable(root) {
    var table = getRulesTable(root);
    if (!table || !window.DataTable || table.dataset.dtInited === '1') {
      return;
    }
    var orderCol = parseInt(table.getAttribute('data-order-col') || '0', 10);
    var orderDir = (table.getAttribute('data-order-dir') || 'asc').toLowerCase() === 'desc' ? 'desc' : 'asc';
    if (isNaN(orderCol)) {
      orderCol = 0;
    }
    try {
      new DataTable(table, {
        responsive: false,
        autoWidth: true,
        paging: false,
        searching: true,
        dom: 't',
        lengthChange: false,
        info: false,
        ordering: true,
        order: [[orderCol, orderDir]],
        columnDefs: [
          { orderable: false, searchable: false, targets: [5] },
          { visible: false, searchable: true, targets: [6] },
          { className: 'text-end', targets: [3, 5] },
          { className: 'text-center', targets: [4] }
        ]
      });
      table.dataset.dtInited = '1';
      var dt = getRulesDataTable(table);
      if (dt) {
        dt.columns.adjust().draw(false);
      }
    } catch (e) {
      console.warn('SPL rules DataTable init failed:', e);
    }
  }

  function adjustRulesDataTable(root) {
    var table = getRulesTable(root);
    if (!table) {
      return;
    }
    if (table.dataset.dtInited !== '1') {
      initRulesDataTable(root);
      return;
    }
    var dt = getRulesDataTable(table);
    if (dt) {
      dt.columns.adjust().draw(false);
    }
  }

  function bindSplRulesSearch(root) {
    var scope = root && root.querySelector ? root : document;
    var input = scope.querySelector ? scope.querySelector('#splRulesSearch') : document.getElementById('splRulesSearch');
    var clearBtn = scope.querySelector ? scope.querySelector('#splRulesSearchClear') : document.getElementById('splRulesSearchClear');
    if (!input || input.getAttribute('data-spl-search-bound') === '1') {
      return;
    }
    input.setAttribute('data-spl-search-bound', '1');

    function applyRulesSearch() {
      var table = getRulesTable(root);
      var dt = getRulesDataTable(table);
      var term = (input.value || '').trim();
      if (clearBtn) {
        clearBtn.classList.toggle('d-none', term === '');
      }
      if (!table) {
        return;
      }
      if (dt) {
        dt.search(term, false, false, true).draw();
        return;
      }
      var q = term.toLowerCase();
      table.querySelectorAll('tbody tr.spl-rule-row').forEach(function (row) {
        var haystack = row.textContent.toLowerCase();
        row.style.display = (q === '' || haystack.indexOf(q) !== -1) ? '' : 'none';
      });
    }

    input.addEventListener('input', applyRulesSearch);
    input.addEventListener('search', applyRulesSearch);
    if (clearBtn) {
      clearBtn.addEventListener('click', function () {
        input.value = '';
        applyRulesSearch();
        input.focus();
      });
    }
  }

  function initSplRulesPanel(root) {
    if (window.SPL_CONFIG) {
      cfg = window.SPL_CONFIG;
    }
    ensureSplRulesClickDelegation();
    var table = getRulesTable(root);
    if (!table) {
      return;
    }
    bindSplRulesImport(root || document);
    bindSplRulesTable(table);
    bindSplAddRuleButton(root || document, table);
    initRulesDataTable(root);
    bindSplRulesSearch(root || document);
  }

  window.initSplRulesPanel = initSplRulesPanel;
  window.adjustSplRulesTable = adjustRulesDataTable;

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
  }

  function formatApprovalPoints(pts) {
    var n = parseFloat(pts);
    if (isNaN(n)) {
      n = 0;
    }
    return (n >= 0 ? '+' : '') + Math.round(n).toLocaleString();
  }

  function syncApprovalPtsHidden(row) {
    if (!row) {
      return;
    }
    var input = row.querySelector('.spl-approval-pts-input');
    var hidden = row.querySelector('.spl-approval-pts-hidden');
    if (!input || !hidden) {
      return;
    }
    hidden.value = input.value;
  }

  function getApprovalPtsFromRow(approvalId, root) {
    var scope = root && root.querySelector ? root : document;
    var input = scope.querySelector('.spl-approval-pts-input[data-approval-id="' + approvalId + '"]');
    if (!input) {
      return null;
    }
    return input.value;
  }

  function getApprovalModal(root) {
    if (root && root.querySelector) {
      var scoped = root.querySelector('#splApprovalDetailModal');
      if (scoped) {
        return scoped;
      }
    }
    return document.getElementById('splApprovalDetailModal');
  }

  function buildApprovalDetailBody(payload) {
    var html = '<dl class="spl-approval-modal-details">';
    html += '<div class="spl-approval-modal-row"><dt>Employee</dt><dd>' + escapeHtml(payload.recipient_name || '—') + '</dd></div>';
    if (payload.submitter_name && payload.submitter_name !== payload.recipient_name) {
      html += '<div class="spl-approval-modal-row"><dt>Submitted by</dt><dd>' + escapeHtml(payload.submitter_name) + '</dd></div>';
    }
    html += '<div class="spl-approval-modal-row"><dt>Submitted</dt><dd>' + escapeHtml(payload.submitted_at || '—') + '</dd></div>';
    html += '<div class="spl-approval-modal-row"><dt>Category</dt><dd>' + escapeHtml(payload.category_name || '—') + '</dd></div>';
    html += '<div class="spl-approval-modal-row"><dt>Activity</dt><dd>' + escapeHtml(payload.rule_name || payload.rule_code || '—') + '</dd></div>';
    if (payload.rule_code) {
      html += '<div class="spl-approval-modal-row"><dt>Rule code</dt><dd><code>' + escapeHtml(payload.rule_code) + '</code></dd></div>';
    }
    if (payload.view !== 'pending') {
      html += '<div class="spl-approval-modal-row"><dt>Points</dt><dd class="spl-approval-modal-points">' + escapeHtml(formatApprovalPoints(payload.requested_points)) + '</dd></div>';
    }
    html += '</dl>';
    html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Description / notes</div>';
    html += '<div class="spl-approval-modal-note">' + (payload.reference_label ? payload.reference_label : '—') + '</div></div>';
    if (payload.evidence_preview_url || payload.evidence_download_url || payload.evidence_file) {
      html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Attachment</div>';
      if (payload.evidence_is_image && payload.evidence_preview_url) {
        html += '<a href="' + escapeHtml(payload.evidence_preview_url) + '" target="_blank" rel="noopener" class="spl-activity-detail-preview-link">';
        html += '<img src="' + escapeHtml(payload.evidence_preview_url) + '" alt="" class="spl-activity-detail-preview-img">';
        html += '</a>';
      }
      html += '<div class="spl-activity-detail-attachment-actions">';
      if (payload.evidence_preview_url) {
        html += '<a href="' + escapeHtml(payload.evidence_preview_url) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a>';
      } else if (payload.evidence_file) {
        html += '<a href="' + escapeHtml(payload.evidence_file) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a>';
      }
      if (payload.evidence_download_url) {
        html += '<a href="' + escapeHtml(payload.evidence_download_url) + '" class="btn btn-sm btn-primary"><i class="bi bi-download me-1"></i>Download</a>';
      }
      if (payload.evidence_name) {
        html += '<span class="spl-activity-detail-file-name"><i class="bi bi-paperclip me-1"></i>' + escapeHtml(payload.evidence_name) + '</span>';
      }
      html += '</div></div>';
    }
    if (payload.view !== 'pending' && payload.decided_at) {
      html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Decision</div>';
      html += '<div class="spl-approval-modal-decision">';
      html += payload.view === 'approved' ? 'Approved' : 'Rejected';
      if (payload.approver_name) {
        html += ' by ' + escapeHtml(payload.approver_name);
      }
      html += ' · ' + escapeHtml(payload.decided_at);
      html += '</div></div>';
    }
    if (payload.view !== 'pending') {
      html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">' + (payload.view === 'approved' ? 'Approval comment' : 'Rejection reason') + '</div>';
      html += '<div class="spl-approval-modal-comment-display">' + escapeHtml(payload.decision_comment || 'No comment provided.') + '</div></div>';
    }
    return html;
  }

  function getApprovalPayloadMap() {
    var map = {};
    if (window.SPL_APPROVAL_PAYLOADS) {
      Object.assign(map, window.SPL_APPROVAL_PAYLOADS);
    }
    return map;
  }

  function showSplApprovalModal(payload, root) {
    if (!payload) {
      return;
    }
    var modalEl = getApprovalModal(root);
    if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
      return;
    }
    var cfgLocal = getSplCfg();
    var titleEl = modalEl.querySelector('#splApprovalDetailModalLabel');
    var bodyEl = modalEl.querySelector('#splApprovalDetailBody');
    var actionForm = modalEl.querySelector('#splApprovalModalActionForm');
    var readonlyFooter = modalEl.querySelector('#splApprovalDetailFooterReadonly');
    var commentInput = modalEl.querySelector('#splApprovalModalComment');
    var pointsInput = modalEl.querySelector('#splApprovalModalPoints');
    var csrfInput = modalEl.querySelector('#splApprovalModalCsrf');
    var approveBtn = modalEl.querySelector('#splApprovalModalApproveBtn');
    var rejectBtn = modalEl.querySelector('#splApprovalModalRejectBtn');
    if (!bodyEl || !actionForm || !readonlyFooter) {
      return;
    }
    if (titleEl) {
      titleEl.textContent = payload.view === 'pending' ? 'Review activity' : 'Activity details';
    }
    bodyEl.innerHTML = buildApprovalDetailBody(payload);
    if (payload.view === 'pending') {
      actionForm.classList.remove('d-none');
      readonlyFooter.classList.add('d-none');
      if (commentInput) {
        commentInput.value = '';
      }
      if (pointsInput) {
        var rowPts = getApprovalPtsFromRow(payload.id, root);
        var ptsVal = rowPts !== null && rowPts !== '' ? rowPts : payload.requested_points;
        pointsInput.value = ptsVal !== null && ptsVal !== undefined && ptsVal !== '' ? Math.round(parseFloat(ptsVal)) : '';
      }
      if (csrfInput && cfgLocal.csrfName && cfgLocal.csrfHash) {
        csrfInput.name = cfgLocal.csrfName;
        csrfInput.value = cfgLocal.csrfHash;
      }
      if (approveBtn) {
        approveBtn.onclick = function () {
          if (pointsInput && pointsInput.value !== '' && isNaN(parseFloat(pointsInput.value))) {
            window.alert('Points must be a valid number.');
            return;
          }
          var base = cfgLocal.approveActivityUrlBase || '';
          actionForm.action = base + payload.id;
          actionForm.submit();
        };
      }
      if (rejectBtn) {
        rejectBtn.onclick = function () {
          if (!window.confirm('Reject this activity?')) {
            return;
          }
          var base = cfgLocal.rejectActivityUrlBase || '';
          actionForm.action = base + payload.id;
          if (pointsInput) {
            pointsInput.disabled = true;
          }
          actionForm.submit();
          if (pointsInput) {
            pointsInput.disabled = false;
          }
        };
      }
    } else {
      actionForm.classList.add('d-none');
      readonlyFooter.classList.remove('d-none');
    }
    var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }

  function getApprovalEditRules() {
    var cfg = getSplCfg();
    return Array.isArray(cfg.approvalEditRules) ? cfg.approvalEditRules : [];
  }

  function fillApprovalRuleSelect(selectEl, selectedRuleId) {
    if (!selectEl) {
      return;
    }
    var rules = getApprovalEditRules();
    var selected = String(selectedRuleId || '');
    selectEl.innerHTML = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '— Select activity —';
    selectEl.appendChild(placeholder);
    rules.forEach(function (rule) {
      var opt = document.createElement('option');
      opt.value = String(rule.id);
      opt.textContent = rule.name + ' (' + (rule.points >= 0 ? '+' : '') + Math.round(rule.points) + ')';
      opt.dataset.points = String(rule.points);
      opt.dataset.name = rule.name || '';
      opt.dataset.code = rule.code || '';
      opt.dataset.category = rule.category_name || '';
      if (String(rule.id) === selected) {
        opt.selected = true;
      }
      selectEl.appendChild(opt);
    });
    if (selected && !selectEl.value) {
      selectEl.value = selected;
    }
  }

  function setApprovalRowEditing(row, editing) {
    if (!row) {
      return;
    }
    row.classList.toggle('is-editing', editing);
    row.classList.toggle('is-clickable', !editing);
    var display = row.querySelector('.spl-approval-activity-display');
    var selectEl = row.querySelector('.spl-approval-rule-select');
    var noteDisplay = row.querySelector('.spl-approval-note-display');
    var notesInput = row.querySelector('.spl-approval-notes-input');
    var editBtn = row.querySelector('.spl-approval-edit-btn');
    var approvalId = row.getAttribute('data-approval-id');
    var map = getApprovalPayloadMap();
    var payload = map[approvalId] || map[parseInt(approvalId, 10)] || {};

    if (display) {
      display.classList.toggle('d-none', editing);
    }
    if (selectEl) {
      selectEl.classList.toggle('d-none', !editing);
      if (editing) {
        fillApprovalRuleSelect(selectEl, row.getAttribute('data-rule-id') || payload.rule_id || '');
      }
    }
    if (noteDisplay) {
      noteDisplay.classList.toggle('d-none', editing);
    }
    if (notesInput) {
      notesInput.classList.toggle('d-none', !editing);
      if (editing) {
        notesInput.value = payload.reference_label_raw || payload.reference_label || '';
      }
    }
    if (editBtn) {
      editBtn.title = editing ? 'Done editing' : 'Edit activity';
      editBtn.setAttribute('aria-label', editBtn.title);
      editBtn.classList.toggle('btn-outline-secondary', !editing);
      editBtn.classList.toggle('btn-primary', editing);
      var icon = editBtn.querySelector('i');
      if (icon) {
        icon.className = editing ? 'bi bi-check-lg' : 'bi bi-pencil';
      }
    }
  }

  function showApprovalSaveStatus(row, ok) {
    if (!row) {
      return;
    }
    var el = row.querySelector('.spl-approval-save-status');
    if (!el) {
      return;
    }
    el.textContent = ok ? 'Saved' : 'Error';
    el.classList.toggle('text-success', ok);
    el.classList.toggle('text-danger', !ok);
    el.classList.remove('d-none');
    clearTimeout(row._splSaveStatusTimer);
    row._splSaveStatusTimer = setTimeout(function () {
      el.classList.add('d-none');
    }, 1600);
  }

  function applyApprovalSaveResult(row, data) {
    if (!row || !data) {
      return;
    }
    var approvalId = String(data.id || row.getAttribute('data-approval-id') || '');
    var map = getApprovalPayloadMap();
    var payload = map[approvalId] || map[parseInt(approvalId, 10)] || {};
    if (data.rule_id) {
      row.setAttribute('data-rule-id', String(data.rule_id));
      payload.rule_id = data.rule_id;
    }
    if (typeof data.rule_name === 'string') {
      payload.rule_name = data.rule_name;
      var title = row.querySelector('.spl-approval-activity-title');
      if (title) {
        title.textContent = data.rule_name || '—';
        title.setAttribute('title', data.rule_name || '—');
      }
    }
    if (typeof data.rule_code === 'string') {
      payload.rule_code = data.rule_code;
    }
    if (typeof data.category_name === 'string') {
      payload.category_name = data.category_name;
      var meta = row.querySelector('.spl-approval-activity-meta');
      if (meta) {
        meta.textContent = data.category_name;
      } else if (data.category_name) {
        var display = row.querySelector('.spl-approval-activity-display');
        if (display) {
          meta = document.createElement('span');
          meta.className = 'spl-approval-activity-meta';
          meta.textContent = data.category_name;
          display.appendChild(meta);
        }
      }
    }
    if (typeof data.requested_points !== 'undefined') {
      payload.requested_points = data.requested_points;
      var ptsInput = row.querySelector('.spl-approval-pts-input');
      if (ptsInput) {
        ptsInput.value = Math.round(parseFloat(data.requested_points) || 0);
        ptsInput.classList.toggle('is-positive', parseFloat(data.requested_points) >= 0);
        ptsInput.classList.toggle('is-negative', parseFloat(data.requested_points) < 0);
      }
      syncApprovalPtsHidden(row);
    }
    if (typeof data.reference_label === 'string' && data.reference_label !== null) {
      payload.reference_label = data.reference_label;
      payload.reference_label_raw = data.reference_label;
      var noteDisplay = row.querySelector('.spl-approval-note-display');
      if (noteDisplay) {
        var preview = (data.reference_label || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        if (preview) {
          noteDisplay.innerHTML = '<span class="spl-approval-note-icon" title="' + escapeHtml(preview.substring(0, 120)) + '"><i class="bi bi-chat-left-text"></i></span>';
        } else {
          noteDisplay.innerHTML = '<span class="text-muted">—</span>';
        }
      }
    }
    map[approvalId] = payload;
    map[parseInt(approvalId, 10)] = payload;
    window.SPL_APPROVAL_PAYLOADS = map;
  }

  function savePendingApprovalRow(row, opts) {
    opts = opts || {};
    if (!row || row.getAttribute('data-spl-saving') === '1') {
      return Promise.resolve(null);
    }
    var approvalId = row.getAttribute('data-approval-id');
    var cfg = getSplCfg();
    var base = String(cfg.updatePendingActivityUrlBase || '').replace(/\/?$/, '/');
    if (!approvalId || !base) {
      return Promise.reject(new Error('Missing update URL'));
    }
    var ptsInput = row.querySelector('.spl-approval-pts-input');
    var selectEl = row.querySelector('.spl-approval-rule-select');
    var notesInput = row.querySelector('.spl-approval-notes-input');
    var points = ptsInput ? ptsInput.value : '0';
    if (points === '' || isNaN(parseFloat(points))) {
      showApprovalSaveStatus(row, false);
      return Promise.reject(new Error('Invalid points'));
    }
    var payload = {
      requested_points: points
    };
    if (selectEl && selectEl.value) {
      payload.rule_id = selectEl.value;
    } else if (row.getAttribute('data-rule-id')) {
      payload.rule_id = row.getAttribute('data-rule-id');
    }
    if (notesInput && (opts.includeNotes || row.classList.contains('is-editing'))) {
      payload.reference_label = notesInput.value;
    }
    row.setAttribute('data-spl-saving', '1');
    return postForm(base + approvalId, payload).then(function (res) {
      row.removeAttribute('data-spl-saving');
      if (!res || res.status !== 'success') {
        showApprovalSaveStatus(row, false);
        return Promise.reject(new Error((res && res.message) || 'Save failed'));
      }
      applyApprovalSaveResult(row, res.data || {});
      showApprovalSaveStatus(row, true);
      return res;
    }).catch(function (err) {
      row.removeAttribute('data-spl-saving');
      showApprovalSaveStatus(row, false);
      return Promise.reject(err);
    });
  }

  function toggleSplApprovalInlineEdit(approvalId) {
    if (!approvalId) {
      return;
    }
    var row = document.querySelector('.spl-approval-row[data-approval-id="' + approvalId + '"]');
    if (!row) {
      return;
    }
    var editing = !row.classList.contains('is-editing');
    if (!editing) {
      savePendingApprovalRow(row, { includeNotes: true }).finally(function () {
        setApprovalRowEditing(row, false);
      });
      return;
    }
    document.querySelectorAll('.spl-approval-row.is-editing').forEach(function (other) {
      if (other !== row) {
        setApprovalRowEditing(other, false);
      }
    });
    setApprovalRowEditing(row, true);
  }

  function openSplApprovalEditFromId(approvalId) {
    toggleSplApprovalInlineEdit(approvalId);
  }

  function openSplApprovalFromId(approvalId, root) {
    if (!approvalId) {
      return;
    }
    var map = getApprovalPayloadMap();
    var payload = map[approvalId] || map[parseInt(approvalId, 10)];
    if (!payload) {
      return;
    }
    showSplApprovalModal(payload, root);
  }

  function openSplApprovalModal(card, root) {
    if (!card) {
      return;
    }
    var approvalId = card.getAttribute('data-approval-id');
    if (approvalId) {
      openSplApprovalFromId(approvalId, root);
      return;
    }
    var raw = card.getAttribute('data-spl-approval');
    if (!raw) {
      return;
    }
    var payload;
    try {
      payload = JSON.parse(raw);
    } catch (e) {
      return;
    }
    showSplApprovalModal(payload, root);
  }

  function initSplApprovalTable(root) {
    root = root || document;
    var tables = root.querySelectorAll ? root.querySelectorAll('.spl-approval-table') : [];
    tables.forEach(function (table) {
      if (!table || table.dataset.dtInited === '1' || !window.DataTable) {
        return;
      }
      var pageLength = parseInt(table.getAttribute('data-page-length') || '25', 10);
      if (isNaN(pageLength) || pageLength <= 0) {
        pageLength = 25;
      }
      var headerCells = table.querySelectorAll('thead th');
      var actionColIndex = headerCells.length - 1;
      var compact = table.classList.contains('spl-approval-table--compact');
      var nonSortable = [actionColIndex];
      if (compact) {
        headerCells.forEach(function (th, idx) {
          if (th.classList.contains('spl-approval-col-icon')) {
            nonSortable.push(idx);
          }
        });
      }
      var config = {
        responsive: !compact,
        autoWidth: false,
        pageLength: pageLength,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[1, 'desc']],
        columnDefs: [
          { orderable: false, searchable: false, targets: nonSortable },
          { className: 'text-end', targets: [actionColIndex] },
          { className: 'text-nowrap', targets: [actionColIndex] }
        ]
      };
      try {
        new DataTable(table, config);
        table.dataset.dtInited = '1';
      } catch (e) {
        console.warn('SPL approval DataTable init failed:', e);
      }
    });

    var scopes = [];
    if (root.querySelectorAll) {
      root.querySelectorAll('.spl-approval-table-wrap').forEach(function (wrap) {
        scopes.push(wrap);
      });
    }
    scopes.forEach(function (scope) {
      if (scope.getAttribute('data-spl-approval-bound') === '1') {
        return;
      }
      scope.setAttribute('data-spl-approval-bound', '1');
      scope.addEventListener('click', function (e) {
        var viewBtn = e.target.closest('.spl-approval-view-btn');
        if (viewBtn) {
          e.preventDefault();
          openSplApprovalFromId(viewBtn.getAttribute('data-approval-id'), root);
          return;
        }
        var editBtn = e.target.closest('.spl-approval-edit-btn');
        if (editBtn) {
          e.preventDefault();
          e.stopPropagation();
          openSplApprovalEditFromId(editBtn.getAttribute('data-approval-id'));
          return;
        }
        if (e.target.closest('a, button, input, textarea, select, label, form')) {
          return;
        }
        var row = e.target.closest('.spl-approval-row');
        if (!row || row.classList.contains('is-editing')) {
          return;
        }
        openSplApprovalFromId(row.getAttribute('data-approval-id'), root);
      });
      scope.addEventListener('change', function (e) {
        var ruleSelect = e.target.closest('.spl-approval-rule-select');
        if (ruleSelect) {
          var ruleRow = ruleSelect.closest('.spl-approval-row');
          if (!ruleRow) {
            return;
          }
          var opt = ruleSelect.options[ruleSelect.selectedIndex];
          var ptsInput = ruleRow.querySelector('.spl-approval-pts-input');
          if (opt && opt.dataset.points && ptsInput) {
            ptsInput.value = Math.round(parseFloat(opt.dataset.points) || 0);
            syncApprovalPtsHidden(ruleRow);
          }
          savePendingApprovalRow(ruleRow, { includeNotes: true });
          return;
        }
        var ptsChange = e.target.closest('.spl-approval-pts-input');
        if (ptsChange) {
          var ptsRow = ptsChange.closest('.spl-approval-row');
          syncApprovalPtsHidden(ptsRow);
          savePendingApprovalRow(ptsRow, { includeNotes: ptsRow && ptsRow.classList.contains('is-editing') });
        }
      });
      scope.addEventListener('blur', function (e) {
        var notesInput = e.target.closest('.spl-approval-notes-input');
        if (!notesInput) {
          return;
        }
        var notesRow = notesInput.closest('.spl-approval-row');
        if (notesRow && notesRow.classList.contains('is-editing')) {
          savePendingApprovalRow(notesRow, { includeNotes: true });
        }
      }, true);
      scope.addEventListener('input', function (e) {
        var ptsInput = e.target.closest('.spl-approval-pts-input');
        if (!ptsInput) {
          return;
        }
        var row = ptsInput.closest('.spl-approval-row');
        syncApprovalPtsHidden(row);
        var n = parseFloat(ptsInput.value);
        ptsInput.classList.toggle('is-positive', !isNaN(n) && n >= 0);
        ptsInput.classList.toggle('is-negative', !isNaN(n) && n < 0);
        var cell = ptsInput.closest('td');
        if (cell && !isNaN(n)) {
          cell.setAttribute('data-order', n.toFixed(2));
        }
      });
      scope.addEventListener('keydown', function (e) {
        if (!e.target.closest('.spl-approval-pts-input, .spl-approval-notes-input, .spl-approval-rule-select')) {
          return;
        }
        e.stopPropagation();
      });
      scope.querySelectorAll('.spl-approval-row').forEach(function (row) {
        row.addEventListener('keydown', function (e) {
          if (e.target.closest('.spl-approval-pts-input, .spl-approval-notes-input, .spl-approval-rule-select')) {
            return;
          }
          if (row.classList.contains('is-editing')) {
            return;
          }
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openSplApprovalFromId(row.getAttribute('data-approval-id'), root);
          }
        });
      });
      scope.querySelectorAll('.spl-approval-approve-form').forEach(function (form) {
        form.addEventListener('submit', function () {
          syncApprovalPtsHidden(form.closest('.spl-approval-row'));
        });
      });
      scope.querySelectorAll('.spl-approval-reject-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
          if (!window.confirm('Reject this activity?')) {
            e.preventDefault();
          }
        });
      });
    });
  }

  function initSplApprovalsPanel(root) {
    var scope = root && root.querySelector ? root : document;
    var tab = scope.querySelector ? scope.querySelector('#spl-tab-approvals') : document.getElementById('spl-tab-approvals');
    if (!tab) {
      return;
    }
    initSplApprovalTable(scope);
    if (tab.getAttribute('data-spl-approvals-bound') === '1') {
      return;
    }
    tab.setAttribute('data-spl-approvals-bound', '1');
    var grid = tab.querySelector('.spl-approval-grid');
    if (grid) {
      grid.addEventListener('click', function (e) {
        if (e.target.closest('a, button, input, textarea, select, label')) {
          return;
        }
        var card = e.target.closest('[data-spl-approval]');
        if (!card) {
          return;
        }
        openSplApprovalModal(card, scope);
      });
    }
    tab.querySelectorAll('[data-spl-approval]').forEach(function (card) {
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openSplApprovalModal(card, scope);
        }
      });
    });
  }

  window.initSplApprovalsPanel = initSplApprovalsPanel;
  window.initSplApprovalTable = initSplApprovalTable;

  function getActivityDetailModal(root) {
    if (root && root.querySelector) {
      var scoped = root.querySelector('#splActivityDetailModal');
      if (scoped) {
        return scoped;
      }
    }
    return document.getElementById('splActivityDetailModal');
  }

  function buildActivityDetailBody(payload) {
    var html = '<dl class="spl-approval-modal-details">';
    html += '<div class="spl-approval-modal-row"><dt>Activity</dt><dd>' + escapeHtml(payload.title || '—') + '</dd></div>';
    html += '<div class="spl-approval-modal-row"><dt>Date</dt><dd>' + escapeHtml(payload.created_at || '—') + '</dd></div>';
    html += '<div class="spl-approval-modal-row"><dt>Category</dt><dd>' + escapeHtml(payload.category_name || '—') + '</dd></div>';
    html += '<div class="spl-approval-modal-row"><dt>Source</dt><dd>' + escapeHtml(payload.source_label || '—') + '</dd></div>';
    if (payload.rule_code) {
      html += '<div class="spl-approval-modal-row"><dt>Rule code</dt><dd><code>' + escapeHtml(payload.rule_code) + '</code></dd></div>';
    }
    html += '<div class="spl-approval-modal-row"><dt>Points</dt><dd class="spl-approval-modal-points">' + escapeHtml(payload.points_label || formatApprovalPoints(payload.points)) + '</dd></div>';
    html += '<div class="spl-approval-modal-row"><dt>Status</dt><dd><span class="badge rounded-pill text-bg-' + escapeHtml(payload.status_class || 'secondary') + '">' + escapeHtml(payload.status_label || payload.status || '—') + '</span></dd></div>';
    if (payload.approved_at) {
      html += '<div class="spl-approval-modal-row"><dt>Approved</dt><dd>' + escapeHtml(payload.approved_at) + '</dd></div>';
    }
    html += '</dl>';
    html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Description / notes</div>';
    html += '<div class="spl-approval-modal-note">' + (payload.reference_label ? payload.reference_label : '—') + '</div></div>';
    if (payload.notes) {
      html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Internal notes</div>';
      html += '<div class="spl-approval-modal-comment-display">' + escapeHtml(payload.notes) + '</div></div>';
    }
    if (payload.evidence_preview_url || payload.evidence_download_url) {
      html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Attachment</div>';
      if (payload.evidence_is_image && payload.evidence_preview_url) {
        html += '<a href="' + escapeHtml(payload.evidence_preview_url) + '" target="_blank" rel="noopener" class="spl-activity-detail-preview-link">';
        html += '<img src="' + escapeHtml(payload.evidence_preview_url) + '" alt="" class="spl-activity-detail-preview-img">';
        html += '</a>';
      }
      html += '<div class="spl-activity-detail-attachment-actions">';
      if (payload.evidence_preview_url) {
        html += '<a href="' + escapeHtml(payload.evidence_preview_url) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a>';
      }
      if (payload.evidence_download_url) {
        html += '<a href="' + escapeHtml(payload.evidence_download_url) + '" class="btn btn-sm btn-primary"><i class="bi bi-download me-1"></i>Download</a>';
      }
      if (payload.evidence_name) {
        html += '<span class="spl-activity-detail-file-name"><i class="bi bi-paperclip me-1"></i>' + escapeHtml(payload.evidence_name) + '</span>';
      }
      html += '</div></div>';
    }
    if (payload.decided_at) {
      html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Decision</div>';
      html += '<div class="spl-approval-modal-decision">';
      html += escapeHtml(payload.status_label || payload.status || '—');
      if (payload.approver_name) {
        html += ' by ' + escapeHtml(payload.approver_name);
      }
      html += ' · ' + escapeHtml(payload.decided_at);
      html += '</div></div>';
    }
    if (payload.decision_comment) {
      html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Decision comment</div>';
      html += '<div class="spl-approval-modal-comment-display">' + escapeHtml(payload.decision_comment) + '</div></div>';
    }
    return html;
  }

  function showSplActivityDetail(payload, root) {
    if (!payload) {
      return;
    }
    var modalEl = getActivityDetailModal(root);
    if (!modalEl || !window.bootstrap || !window.bootstrap.Modal) {
      return;
    }
    var titleEl = modalEl.querySelector('#splActivityDetailModalLabel');
    var bodyEl = modalEl.querySelector('#splActivityDetailBody');
    if (!bodyEl) {
      return;
    }
    if (titleEl) {
      titleEl.textContent = payload.title || 'Activity details';
    }
    bodyEl.innerHTML = buildActivityDetailBody(payload);
    var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }

  function getActivityPayloadMap() {
    var map = {};
    if (window.SPL_ACTIVITY_PAYLOADS) {
      Object.assign(map, window.SPL_ACTIVITY_PAYLOADS);
    }
    if (window.SPL_MEMBER_ACTIVITIES) {
      Object.assign(map, window.SPL_MEMBER_ACTIVITIES);
    }
    if (window.SPL_DASHBOARD_ACTIVITY_PAYLOADS) {
      Object.assign(map, window.SPL_DASHBOARD_ACTIVITY_PAYLOADS);
    }
    return map;
  }

  function openSplActivityDetailFromId(activityId, root) {
    if (!activityId) {
      return;
    }
    var map = getActivityPayloadMap();
    var payload = map[activityId] || map[parseInt(activityId, 10)];
    showSplActivityDetail(payload, root);
  }

  function openSplActivityDetailModal(card, root) {
    if (!card) {
      return;
    }
    openSplActivityDetailFromId(card.getAttribute('data-activity-id'), root);
  }

  function initSplActivityTable(root) {
    root = root || document;
    var tables = root.querySelectorAll ? root.querySelectorAll('.spl-activity-table') : [];
    tables.forEach(function (table) {
      if (!table || table.dataset.dtInited === '1' || !window.DataTable) {
        return;
      }
      var compact = table.classList.contains('spl-activity-table--compact');
      var pageLength = parseInt(table.getAttribute('data-page-length') || '25', 10);
      if (isNaN(pageLength) || pageLength <= 0) {
        pageLength = 25;
      }
      var actionColIndex = table.querySelectorAll('thead th').length - 1;
      var config = {
        responsive: true,
        autoWidth: false,
        pageLength: pageLength,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        order: [[0, 'desc']],
        columnDefs: [
          { orderable: false, searchable: false, targets: [actionColIndex] },
          { className: 'text-end', targets: [actionColIndex] }
        ]
      };
      if (compact) {
        config.paging = false;
        config.searching = false;
        config.info = false;
        config.lengthChange = false;
        config.dom = 't';
      }
      try {
        new DataTable(table, config);
        table.dataset.dtInited = '1';
      } catch (e) {
        console.warn('SPL activity DataTable init failed:', e);
      }
    });

    var scopes = [];
    if (root.querySelectorAll) {
      root.querySelectorAll('.spl-activity-table-wrap').forEach(function (wrap) {
        scopes.push(wrap);
      });
    }
    if (!scopes.length && root.classList && root.classList.contains('spl-activity-table-wrap')) {
      scopes.push(root);
    }
    scopes.forEach(function (scope) {
      if (scope.getAttribute('data-spl-activity-bound') === '1') {
        return;
      }
      scope.setAttribute('data-spl-activity-bound', '1');
      scope.addEventListener('click', function (e) {
        var viewBtn = e.target.closest('.spl-activity-view-btn');
        if (viewBtn) {
          e.preventDefault();
          openSplActivityDetailFromId(viewBtn.getAttribute('data-activity-id'), root);
          return;
        }
        if (e.target.closest('a, button, input, textarea, select, label')) {
          return;
        }
        var row = e.target.closest('tr[data-activity-id]');
        if (!row) {
          return;
        }
        openSplActivityDetailFromId(row.getAttribute('data-activity-id'), root);
      });
      scope.querySelectorAll('tr[data-activity-id]').forEach(function (row) {
        row.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            openSplActivityDetailFromId(row.getAttribute('data-activity-id'), root);
          }
        });
      });
    });
  }

  window.initSplActivityTable = initSplActivityTable;
  window.initSplMemberActivityPage = initSplActivityTable;

  function loadActivities(categoryId, root) {
    var els = getActivityForm(root);
    if (!els.activitySelect || !getSplCfg().rulesUrl) {
      return;
    }
    var url = getSplCfg().rulesUrl;
    if (categoryId) {
      url += (url.indexOf('?') >= 0 ? '&' : '?') + 'category_id=' + encodeURIComponent(categoryId);
    }
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || res.status !== 'success' || !Array.isArray(res.data)) {
          renderActivities([], els);
          return;
        }
        renderActivities(res.data, els);
      })
      .catch(function () {
        renderActivities([], els);
      });
  }

  function renderActivities(items, els) {
    if (!els || !els.activitySelect) {
      return;
    }
    els.activitySelect.innerHTML = '<option value="">— Select activity —</option>';
    if (!items.length) {
      var emptyOpt = document.createElement('option');
      emptyOpt.value = '';
      emptyOpt.textContent = '— No manual activities in this category —';
      emptyOpt.disabled = true;
      els.activitySelect.appendChild(emptyOpt);
    }
    items.forEach(function (item) {
      var opt = document.createElement('option');
      opt.value = item.code;
      opt.textContent = item.name + ' (' + (item.points >= 0 ? '+' : '') + item.points + ' pts)';
      opt.dataset.points = item.points;
      els.activitySelect.appendChild(opt);
    });
    if (els.pointsHint) {
      els.pointsHint.textContent = '';
    }
  }

  var SPL_TINYMCE_SRC = 'https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js';

  function loadSplTinyMceScript() {
    if (window.tinymce) {
      return Promise.resolve(window.tinymce);
    }
    if (window.__splTinyMceLoading) {
      return window.__splTinyMceLoading;
    }
    window.__splTinyMceLoading = new Promise(function (resolve, reject) {
      var existing = document.querySelector('script[data-spl-tinymce="1"]');
      if (existing) {
        if (window.tinymce) {
          resolve(window.tinymce);
          return;
        }
        existing.addEventListener('load', function () { resolve(window.tinymce); });
        existing.addEventListener('error', function () { reject(new Error('TinyMCE load failed')); });
        return;
      }
      var script = document.createElement('script');
      script.src = SPL_TINYMCE_SRC;
      script.referrerPolicy = 'origin';
      script.setAttribute('data-spl-tinymce', '1');
      script.onload = function () { resolve(window.tinymce); };
      script.onerror = function () { reject(new Error('TinyMCE load failed')); };
      document.head.appendChild(script);
    });
    return window.__splTinyMceLoading;
  }

  function restoreSplNoteTextarea(textarea) {
    if (!textarea) {
      return;
    }
    textarea.style.removeProperty('display');
    textarea.removeAttribute('aria-hidden');
    textarea.removeAttribute('data-spl-note-editor');
    var wrap = textarea.closest('.spl-note-field-wrap');
    if (wrap) {
      wrap.classList.remove('is-editor-ready');
    }
  }

  function hideSplNoteTextarea(textarea) {
    if (!textarea) {
      return;
    }
    textarea.style.display = 'none';
    textarea.setAttribute('aria-hidden', 'true');
    textarea.setAttribute('data-spl-note-editor', 'ready');
    var wrap = textarea.closest('.spl-note-field-wrap');
    if (wrap) {
      wrap.classList.add('is-editor-ready');
    }
  }

  function bindSplNoteFormSubmit(scope) {
    var form = scope.querySelector ? scope.querySelector('#splSubmitForm') : document.getElementById('splSubmitForm');
    if (!form || form.getAttribute('data-spl-note-submit-bound') === '1') {
      return;
    }
    form.setAttribute('data-spl-note-submit-bound', '1');
    form.addEventListener('submit', function () {
      if (window.tinymce && tinymce.get('splNoteInput')) {
        tinymce.get('splNoteInput').save();
      }
    });
  }

  function initSplNoteEditor(root) {
    var scope = root && root.querySelector ? root : document;
    var textarea = scope.querySelector ? scope.querySelector('#splNoteInput') : document.getElementById('splNoteInput');
    if (!textarea) {
      return;
    }
    bindSplNoteFormSubmit(scope);

    var existing = window.tinymce ? tinymce.get('splNoteInput') : null;
    if (existing && !existing.removed) {
      hideSplNoteTextarea(textarea);
      existing.show();
      window.requestAnimationFrame(function () {
        if (typeof existing.fire === 'function') {
          existing.fire('ResizeEditor');
        }
      });
      return;
    }

    if (textarea.getAttribute('data-spl-note-editor') === 'loading') {
      return;
    }

    if (window.__splTinyMceLoading && !window.tinymce) {
      textarea.setAttribute('data-spl-note-editor', 'loading');
      window.__splTinyMceLoading.then(function () {
        textarea.removeAttribute('data-spl-note-editor');
        initSplNoteEditor(root);
      }).catch(function () {
        textarea.removeAttribute('data-spl-note-editor');
        restoreSplNoteTextarea(textarea);
      });
      return;
    }

    textarea.setAttribute('data-spl-note-editor', 'loading');
    loadSplTinyMceScript().then(function () {
      var current = document.getElementById('splNoteInput');
      if (!current) {
        return;
      }
      if (window.tinymce && tinymce.get('splNoteInput')) {
        tinymce.get('splNoteInput').remove();
      }
      var isMobile = window.matchMedia('(max-width: 767.98px)').matches;
      tinymce.init({
        selector: '#splNoteInput',
        menubar: false,
        statusbar: !isMobile,
        plugins: 'lists link autolink code wordcount',
        toolbar: isMobile
          ? 'bold italic underline | bullist numlist | link | removeformat'
          : 'undo redo | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link | removeformat | code',
        toolbar_mode: isMobile ? 'scrolling' : 'wrap',
        branding: false,
        height: isMobile ? 140 : 180,
        width: '100%',
        resize: !isMobile,
        convert_urls: false,
        default_link_target: '_blank',
        content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
        formats: {
          bold: { inline: 'strong' },
          italic: { inline: 'em' },
          underline: { inline: 'u' },
          strikethrough: { inline: 'del' }
        },
        setup: function (editor) {
          editor.on('init', function () {
            hideSplNoteTextarea(current);
            current.removeAttribute('data-spl-note-editor');
            current.setAttribute('data-spl-note-editor', 'ready');
            window.requestAnimationFrame(function () {
              if (typeof editor.fire === 'function') {
                editor.fire('ResizeEditor');
              }
            });
          });
        }
      });
    }).catch(function () {
      textarea.removeAttribute('data-spl-note-editor');
      restoreSplNoteTextarea(textarea);
    });
  }

  window.initSplNoteEditor = initSplNoteEditor;

  function initSplActivityPanel(root) {
    var scope = root && root.querySelector ? root : document;
    var els = getActivityForm(scope);
    if (!els.form || !els.categorySelect || !els.activitySelect) {
      return;
    }
    initSplNoteEditor(scope);
    if (els.form.getAttribute('data-spl-activity-bound') === '1') {
      return;
    }
    els.form.setAttribute('data-spl-activity-bound', '1');
    els.categorySelect.addEventListener('change', function () {
      loadActivities(els.categorySelect.value || '', scope);
      if (els.pointsHint) {
        els.pointsHint.textContent = '';
      }
    });
    els.activitySelect.addEventListener('change', function () {
      if (!els.pointsHint) {
        return;
      }
      var opt = els.activitySelect.options[els.activitySelect.selectedIndex];
      if (!opt || !opt.dataset.points) {
        els.pointsHint.textContent = '';
        return;
      }
      els.pointsHint.textContent = 'Selected activity: ' + (parseFloat(opt.dataset.points) >= 0 ? '+' : '') + opt.dataset.points + ' points';
    });
    loadActivities(els.categorySelect.value || '', scope);
  }

  window.initSplActivityPanel = initSplActivityPanel;

  function setRuleRowEditing(row, editing) {
    if (!row) {
      return;
    }
    row.classList.toggle('is-editing', editing);
    row.querySelectorAll('.spl-rule-display').forEach(function (el) {
      el.classList.toggle('d-none', editing);
    });
    row.querySelectorAll('.spl-rule-field').forEach(function (el) {
      if (el.classList.contains('spl-rule-code')) {
        el.classList.add('d-none');
        el.disabled = !editing;
        return;
      }
      el.classList.toggle('d-none', !editing);
    });
    row.querySelectorAll('.spl-rule-code, .spl-rule-name, .spl-rule-category, .spl-rule-trigger, .spl-rule-points, .spl-rule-active').forEach(function (el) {
      el.disabled = !editing;
    });
    var toggleBtn = row.querySelector('.spl-toggle-rule');
    if (toggleBtn) {
      toggleBtn.textContent = editing ? 'Save' : 'Edit';
      toggleBtn.classList.toggle('btn-outline-primary', !editing);
      toggleBtn.classList.toggle('btn-primary', editing);
    }
    if (!editing) {
      syncRuleRowDisplay(row);
    }
  }

  function slugifyRuleCode(value) {
    return String(value || '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');
  }

  function ensureRuleCode(row) {
    var codeInput = row.querySelector('.spl-rule-code');
    var nameInput = row.querySelector('.spl-rule-name');
    if (!codeInput || codeInput.value.trim() !== '') {
      return;
    }
    var fromName = nameInput ? slugifyRuleCode(nameInput.value.trim()) : '';
    var code = fromName || ('rule_' + Date.now());
    codeInput.value = code;
    row.setAttribute('data-rule-code', code);
  }

  function ruleRowPayload(row) {
    if (!row) {
      return null;
    }
    var codeEl = row.querySelector('.spl-rule-code');
    var nameEl = row.querySelector('.spl-rule-name');
    var catEl = row.querySelector('.spl-rule-category');
    var triggerEl = row.querySelector('.spl-rule-trigger');
    var pointsEl = row.querySelector('.spl-rule-points');
    var activeEl = row.querySelector('.spl-rule-active');
    if (!nameEl) {
      return null;
    }
    var name = nameEl.value.trim();
    if (name === '') {
      return null;
    }
    var code = codeEl ? codeEl.value.trim() : '';
    if (code === '') {
      code = String(row.getAttribute('data-rule-code') || '').trim();
    }
    return {
      id: row.getAttribute('data-rule-id') || '',
      code: code,
      name: name,
      category_id: catEl ? catEl.value : '',
      trigger_event: triggerEl && triggerEl.value.trim() ? triggerEl.value.trim() : 'reward_claim',
      points: pointsEl ? pointsEl.value : '0',
      requires_approval: 1,
      is_active: activeEl && activeEl.checked ? 1 : 0,
      condition_json: ''
    };
  }

  function postForm(url, data) {
    var activeCfg = getSplCfg();
    var body = new URLSearchParams();
    Object.keys(data || {}).forEach(function (k) {
      body.append(k, data[k]);
    });
    if (activeCfg.csrfName && activeCfg.csrfHash) {
      body.append(activeCfg.csrfName, activeCfg.csrfHash);
    }
    return fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
      body: body.toString()
    }).then(function (r) {
      return r.text().then(function (text) {
        var payload = null;
        try {
          payload = text ? JSON.parse(text) : null;
        } catch (e) {
          payload = null;
        }
        if (!r.ok) {
          var errMsg = (payload && payload.message) ? payload.message : ('Request failed (' + r.status + ')');
          return Promise.reject(new Error(errMsg));
        }
        if (!payload) {
          return Promise.reject(new Error('Invalid server response'));
        }
        if (payload.csrfHash && activeCfg) {
          activeCfg.csrfHash = payload.csrfHash;
          window.SPL_CONFIG = activeCfg;
        }
        return payload;
      });
    });
  }

  function showSplToast(message, type) {
    type = type || 'success';
    var container = document.getElementById('toast-container');
    if (!container || !window.bootstrap || !bootstrap.Toast) {
      return false;
    }
    var bg = type === 'danger' ? 'danger' : (type === 'warning' ? 'warning' : 'success');
    var toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center text-bg-' + bg + ' border-0';
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.innerHTML = '<div class="d-flex"><div class="toast-body">' + escapeHtml(message || '') + '</div>'
      + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
    container.appendChild(toastEl);
    var toast = new bootstrap.Toast(toastEl, { delay: 3500 });
    toastEl.addEventListener('hidden.bs.toast', function () {
      toastEl.remove();
    });
    toast.show();
    return true;
  }

  function showRuleSaveMessage(message) {
    if (showSplToast(message, 'success')) {
      return;
    }
    var el = document.getElementById('splRuleSaveMsg');
    if (!el) {
      return;
    }
    el.textContent = message || 'Rule saved successfully.';
    el.classList.remove('d-none');
    if (showRuleSaveMessage._timer) {
      clearTimeout(showRuleSaveMessage._timer);
    }
    showRuleSaveMessage._timer = setTimeout(function () {
      el.classList.add('d-none');
      el.textContent = '';
    }, 3000);
  }

  document.addEventListener('DOMContentLoaded', function () {
    initSplRulesPanel(document);
    initSplApprovalsPanel(document);
    initSplActivityPanel(document);
    bindSplTabAdjust();
    initLevelsTable();
  });

  function bindSplTabAdjust() {
    document.querySelectorAll('[data-bs-target="#spl-tab-rules"]').forEach(function (btn) {
      btn.addEventListener('shown.bs.tab', function () {
        adjustRulesDataTable(document);
      });
    });
    if (document.getElementById('spl-tab-rules') && document.getElementById('spl-tab-rules').classList.contains('show')) {
      window.requestAnimationFrame(function () {
        adjustRulesDataTable(document);
      });
    }
  }

  var levelsTable = document.getElementById('splLevelsTable');

  function formatLevelPointsRange(minVal, maxVal) {
    var min = parseFloat(minVal);
    if (isNaN(min)) {
      min = 0;
    }
    if (maxVal === '' || maxVal === null || typeof maxVal === 'undefined') {
      return String(Math.round(min)) + '+';
    }
    var max = parseFloat(maxVal);
    if (isNaN(max)) {
      return String(Math.round(min)) + '+';
    }
    return String(Math.round(min)) + '–' + String(Math.round(max));
  }

  function syncLevelRowDisplay(row) {
    if (!row) {
      return;
    }
    var nameInput = row.querySelector('.spl-level-name');
    var minInput = row.querySelector('.spl-level-min');
    var maxInput = row.querySelector('.spl-level-max');
    var activeInput = row.querySelector('.spl-level-active');
    var dispName = row.querySelector('.spl-level-display-name .spl-level-title');
    var dispPoints = row.querySelector('.spl-level-display-points');
    var dispActive = row.querySelector('.spl-level-display-active');
    if (dispName && nameInput) {
      dispName.textContent = nameInput.value.trim();
    }
    if (dispPoints && minInput) {
      dispPoints.textContent = formatLevelPointsRange(minInput.value, maxInput ? maxInput.value : '');
    }
    if (dispActive && activeInput) {
      dispActive.innerHTML = activeInput.checked
        ? '<span class="spl-rule-status spl-rule-status--on">Active</span>'
        : '<span class="spl-rule-status spl-rule-status--off">Inactive</span>';
    }
  }

  function setLevelRowEditing(row, editing) {
    if (!row) {
      return;
    }
    row.classList.toggle('is-editing', editing);
    row.querySelectorAll('.spl-level-display').forEach(function (el) {
      el.classList.toggle('d-none', editing);
    });
    row.querySelectorAll('.spl-level-field').forEach(function (el) {
      el.classList.toggle('d-none', !editing);
    });
    row.querySelectorAll('.spl-level-name, .spl-level-min, .spl-level-max, .spl-level-active').forEach(function (el) {
      el.disabled = !editing;
    });
    var toggleBtn = row.querySelector('.spl-toggle-level');
    if (toggleBtn) {
      toggleBtn.textContent = editing ? 'Save' : 'Edit';
      toggleBtn.classList.toggle('btn-outline-primary', !editing);
      toggleBtn.classList.toggle('btn-primary', editing);
    }
    if (!editing) {
      syncLevelRowDisplay(row);
    }
  }

  function levelRowPayload(row) {
    var maxInput = row.querySelector('.spl-level-max');
    return {
      id: row.getAttribute('data-level-id') || '',
      name: row.querySelector('.spl-level-name').value.trim(),
      min_lifetime_points: row.querySelector('.spl-level-min').value,
      max_lifetime_points: maxInput ? maxInput.value : '',
      is_active: row.querySelector('.spl-level-active').checked ? 1 : 0
    };
  }

  function showLevelSaveMessage(message) {
    if (showSplToast(message, 'success')) {
      return;
    }
    var el = document.getElementById('splLevelSaveMsg');
    if (!el) {
      return;
    }
    el.textContent = message || 'Level saved successfully.';
    el.classList.remove('d-none');
    if (showLevelSaveMessage._timer) {
      clearTimeout(showLevelSaveMessage._timer);
    }
    showLevelSaveMessage._timer = setTimeout(function () {
      el.classList.add('d-none');
      el.textContent = '';
    }, 3000);
  }

  function initLevelsTable() {
    if (!levelsTable || !cfg.canManageLevels) {
      return;
    }
    levelsTable.querySelectorAll('tbody tr.spl-level-row').forEach(function (row) {
      setLevelRowEditing(row, false);
    });
    levelsTable.addEventListener('click', function (e) {
      var toggleBtn = e.target.closest('.spl-toggle-level');
      if (!toggleBtn) {
        return;
      }
      var row = toggleBtn.closest('tr');
      if (!row.classList.contains('is-editing')) {
        setLevelRowEditing(row, true);
        return;
      }
      if (!cfg.saveLevelUrl) {
        return;
      }
      postForm(cfg.saveLevelUrl, levelRowPayload(row)).then(function (res) {
        if (res && res.status === 'success') {
          syncLevelRowDisplay(row);
          setLevelRowEditing(row, false);
          var label = row.querySelector('.spl-level-name');
          showLevelSaveMessage((label && label.value.trim() ? label.value.trim() : 'Level') + ' saved successfully.');
        } else {
          showSplToast((res && res.message) ? res.message : 'Could not save level.', 'danger');
        }
      }).catch(function (err) {
        showSplToast((err && err.message) ? err.message : 'Could not save level. Please try again.', 'danger');
      });
    });
  }

  var boardCfg = window.SPL_BOARD_CONFIG || {};

  function initSplBoard(root) {
    root = root || document;
    var cfg = window.SPL_BOARD_CONFIG || boardCfg || {};
    var editBtn = document.getElementById('splBoardEditBtn');
    var saveBtn = document.getElementById('splBoardSaveBtn');
    var cancelBtn = document.getElementById('splBoardCancelBtn');
    var board = document.getElementById('splBoard');

    function setBoardEditing(on) {
      var boardEl = document.getElementById('splBoard');
      if (!boardEl) {
        return;
      }
      if (on) {
        boardEl.classList.add('is-editing');
        if (editBtn) {
          editBtn.classList.add('d-none');
        }
        if (saveBtn) {
          saveBtn.classList.remove('d-none');
        }
        if (cancelBtn) {
          cancelBtn.classList.remove('d-none');
        }
      } else {
        boardEl.classList.remove('is-editing');
        if (editBtn) {
          editBtn.classList.remove('d-none');
        }
        if (saveBtn) {
          saveBtn.classList.add('d-none');
        }
        if (cancelBtn) {
          cancelBtn.classList.add('d-none');
        }
      }
    }

    if (editBtn && editBtn.getAttribute('data-spl-board-bound') !== '1') {
      editBtn.setAttribute('data-spl-board-bound', '1');
      editBtn.addEventListener('click', function () {
        setBoardEditing(true);
      });
    }

    if (cancelBtn && cancelBtn.getAttribute('data-spl-board-bound') !== '1') {
      cancelBtn.setAttribute('data-spl-board-bound', '1');
      cancelBtn.addEventListener('click', function () {
        window.location.reload();
      });
    }

    if (!board) {
      return;
    }

    root.querySelectorAll('.spl-board-poster-input').forEach(function (input) {
      if (input.getAttribute('data-spl-poster-bound') === '1') {
        return;
      }
      input.setAttribute('data-spl-poster-bound', '1');
      input.addEventListener('change', function () {
        if (!this.files || !this.files[0]) {
          return;
        }
        var wrap = this.closest('.spl-group-card-poster');
        if (!wrap) {
          return;
        }
        var img = wrap.querySelector('.spl-group-card-poster-img');
        var empty = wrap.querySelector('.spl-group-card-poster-fallback');
        var dims = wrap.querySelector('[data-spl-poster-dims]');
        if (!img) {
          if (empty) {
            empty.remove();
          }
          img = document.createElement('img');
          img.className = 'spl-group-card-poster-img';
          img.alt = '';
          wrap.insertBefore(img, wrap.firstChild);
        }
        if (!dims) {
          dims = document.createElement('span');
          dims.className = 'spl-poster-dimensions';
          dims.setAttribute('data-spl-poster-dims', '');
          wrap.insertBefore(dims, wrap.querySelector('.spl-group-card-poster-shade'));
        }
        dims.classList.remove('d-none');
        dims.textContent = 'Loading…';
        img.onload = function () {
          if (img.naturalWidth > 0 && img.naturalHeight > 0) {
            dims.textContent = img.naturalWidth + ' × ' + img.naturalHeight + ' px';
          }
        };
        img.src = URL.createObjectURL(this.files[0]);
      });
    });
  }

  window.initSplBoard = initSplBoard;

  if (document.getElementById('splBoardEditBtn') || document.getElementById('splBoard')) {
    initSplBoard(document);
  }
})();
