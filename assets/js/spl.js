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
    var dt = getRulesDataTable();
    if (dt) {
      dt.row(row).remove().draw(false);
      return;
    }
    if (row) {
      row.remove();
    }
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
    if (!table || table.getAttribute('data-spl-rules-bound') === '1') {
      return;
    }
    table.setAttribute('data-spl-rules-bound', '1');
    table.querySelectorAll('tbody tr.spl-rule-row').forEach(function (row) {
      setRuleRowEditing(row, false);
    });
    table.addEventListener('click', function (e) {
      var activeCfg = getSplCfg();
      var toggleBtn = e.target.closest('.spl-toggle-rule');
      var delBtn = e.target.closest('.spl-delete-rule');
      if (toggleBtn) {
        var row = toggleBtn.closest('tr');
        if (!row.classList.contains('is-editing')) {
          setRuleRowEditing(row, true);
          return;
        }
        ensureRuleCode(row);
        postForm(activeCfg.saveRuleUrl, ruleRowPayload(row)).then(function (res) {
          if (res && res.status === 'success' && res.data && res.data.id) {
            row.setAttribute('data-rule-id', res.data.id);
            row.classList.add('spl-rule-row');
            syncRuleRowOrder(row);
            syncRuleRowDisplay(row);
            setRuleRowEditing(row, false);
            drawRulesTable();
            var ruleName = row.querySelector('.spl-rule-name');
            var label = ruleName && ruleName.value.trim() ? ruleName.value.trim() : 'Rule';
            showRuleSaveMessage(label + ' saved successfully.');
          } else {
            alert((res && res.message) ? res.message : 'Could not save rule.');
          }
        });
      }
      if (delBtn) {
        var delRow = delBtn.closest('tr');
        var id = delRow.getAttribute('data-rule-id');
        if (!id) {
          removeRuleRow(delRow);
          return;
        }
        if (!confirm('Deactivate this rule?')) {
          return;
        }
        postForm(activeCfg.deleteRuleUrlBase + id, {}).then(function (res) {
          if (res && res.status === 'success') {
            removeRuleRow(delRow);
          } else {
            alert((res && res.message) ? res.message : 'Could not delete rule.');
          }
        });
      }
    });
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
    html += '<div class="spl-approval-modal-row"><dt>Points</dt><dd class="spl-approval-modal-points">' + escapeHtml(formatApprovalPoints(payload.requested_points)) + '</dd></div>';
    html += '</dl>';
    html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Description / notes</div>';
    html += '<div class="spl-approval-modal-note">' + (payload.reference_label ? payload.reference_label : '—') + '</div></div>';
    if (payload.evidence_file) {
      html += '<div class="spl-approval-modal-section"><div class="spl-approval-modal-section-label">Attachment</div>';
      html += '<a href="' + escapeHtml(payload.evidence_file) + '" target="_blank" rel="noopener" class="spl-approval-modal-file"><i class="bi bi-paperclip me-1"></i>' + escapeHtml(payload.evidence_name || 'Attachment') + '</a></div>';
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

  function openSplApprovalModal(card, root) {
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
      if (csrfInput && cfgLocal.csrfName && cfgLocal.csrfHash) {
        csrfInput.name = cfgLocal.csrfName;
        csrfInput.value = cfgLocal.csrfHash;
      }
      if (approveBtn) {
        approveBtn.onclick = function () {
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
          actionForm.submit();
        };
      }
    } else {
      actionForm.classList.add('d-none');
      readonlyFooter.classList.remove('d-none');
    }
    var modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
  }

  function initSplApprovalsPanel(root) {
    var scope = root && root.querySelector ? root : document;
    var tab = scope.querySelector ? scope.querySelector('#spl-tab-approvals') : document.getElementById('spl-tab-approvals');
    if (!tab || tab.getAttribute('data-spl-approvals-bound') === '1') {
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

  function initSplActivityPanel(root) {
    var scope = root && root.querySelector ? root : document;
    var els = getActivityForm(scope);
    if (!els.form || !els.categorySelect || !els.activitySelect) {
      return;
    }
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
    codeInput.value = fromName || ('rule_' + Date.now());
  }

  function ruleRowPayload(row) {
    return {
      id: row.getAttribute('data-rule-id') || '',
      code: row.querySelector('.spl-rule-code').value.trim(),
      name: row.querySelector('.spl-rule-name').value.trim(),
      category_id: row.querySelector('.spl-rule-category').value,
      trigger_event: row.querySelector('.spl-rule-trigger').value.trim() || 'reward_claim',
      points: row.querySelector('.spl-rule-points').value,
      requires_approval: 1,
      is_active: row.querySelector('.spl-rule-active').checked ? 1 : 0,
      condition_json: ''
    };
  }

  function postForm(url, data) {
    var activeCfg = getSplCfg();
    var body = new URLSearchParams();
    Object.keys(data).forEach(function (k) {
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
    }).then(function (r) { return r.json(); });
  }

  function showRuleSaveMessage(message) {
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
          alert((res && res.message) ? res.message : 'Could not save level.');
        }
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
