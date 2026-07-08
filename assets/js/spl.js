(function () {
  'use strict';

  var cfg = window.SPL_CONFIG || {};
  var categorySelect = document.getElementById('splCategorySelect');
  var activitySelect = document.getElementById('splActivitySelect');
  var pointsHint = document.getElementById('splActivityPointsHint');
  var rulesTable = document.getElementById('splRulesTable');
  var addRuleBtn = document.getElementById('splAddRuleRow');
  var rulesImportBtn = document.getElementById('splRulesImportBtn');
  var rulesImportFile = document.getElementById('splRulesImportFile');
  var rulesImportForm = document.getElementById('splRulesImportForm');

  function getRulesDataTable() {
    if (!rulesTable || !window.DataTable) {
      return null;
    }
    try {
      return DataTable.get(rulesTable);
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
    var tbody = rulesTable ? rulesTable.querySelector('tbody') : null;
    if (tbody) {
      tbody.appendChild(row);
    }
  }

  if (rulesImportBtn && rulesImportFile && rulesImportForm) {
    rulesImportBtn.addEventListener('click', function () {
      rulesImportFile.click();
    });
    rulesImportFile.addEventListener('change', function () {
      if (!rulesImportFile.files || !rulesImportFile.files.length) {
        return;
      }
      rulesImportForm.submit();
    });
  }

  function loadActivities(categoryId) {
    if (!activitySelect || !cfg.rulesUrl) {
      return;
    }
    var url = cfg.rulesUrl;
    if (categoryId) {
      url += (url.indexOf('?') >= 0 ? '&' : '?') + 'category_id=' + encodeURIComponent(categoryId);
    }
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res || res.status !== 'success' || !Array.isArray(res.data)) {
          renderActivities([]);
          return;
        }
        renderActivities(res.data);
      })
      .catch(function () {
        renderActivities([]);
      });
  }

  function renderActivities(items) {
    if (!activitySelect) {
      return;
    }
    activitySelect.innerHTML = '<option value="">— Select activity —</option>';
    items.forEach(function (item) {
      var opt = document.createElement('option');
      opt.value = item.code;
      opt.textContent = item.name + ' (' + (item.points >= 0 ? '+' : '') + item.points + ' pts)';
      opt.dataset.points = item.points;
      activitySelect.appendChild(opt);
    });
    if (pointsHint) {
      pointsHint.textContent = '';
    }
  }

  if (categorySelect) {
    categorySelect.addEventListener('change', function () {
      loadActivities(categorySelect.value || '');
      if (pointsHint) {
        pointsHint.textContent = '';
      }
    });
    loadActivities(categorySelect.value || '');
  }

  if (activitySelect && pointsHint) {
    activitySelect.addEventListener('change', function () {
      var opt = activitySelect.options[activitySelect.selectedIndex];
      if (!opt || !opt.dataset.points) {
        pointsHint.textContent = '';
        return;
      }
      pointsHint.textContent = 'Selected activity: ' + (parseFloat(opt.dataset.points) >= 0 ? '+' : '') + opt.dataset.points + ' points';
    });
  }

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
    var body = new URLSearchParams();
    Object.keys(data).forEach(function (k) {
      body.append(k, data[k]);
    });
    if (cfg.csrfName && cfg.csrfHash) {
      body.append(cfg.csrfName, cfg.csrfHash);
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

  if (rulesTable) {
    rulesTable.querySelectorAll('tbody tr.spl-rule-row').forEach(function (row) {
      setRuleRowEditing(row, false);
    });

    rulesTable.addEventListener('click', function (e) {
      var toggleBtn = e.target.closest('.spl-toggle-rule');
      var delBtn = e.target.closest('.spl-delete-rule');
      if (toggleBtn) {
        var row = toggleBtn.closest('tr');
        if (!row.classList.contains('is-editing')) {
          setRuleRowEditing(row, true);
          return;
        }
        ensureRuleCode(row);
        postForm(cfg.saveRuleUrl, ruleRowPayload(row)).then(function (res) {
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
        postForm(cfg.deleteRuleUrlBase + id, {}).then(function (res) {
          if (res && res.status === 'success') {
            removeRuleRow(delRow);
          } else {
            alert((res && res.message) ? res.message : 'Could not delete rule.');
          }
        });
      }
    });
  }

  if (addRuleBtn && rulesTable) {
    addRuleBtn.addEventListener('click', function () {
      var tr = document.createElement('tr');
      tr.className = 'spl-rule-row is-editing';
      tr.setAttribute('data-rule-id', '');
      var catOptions = '<option value="">—</option>';
      (cfg.categories || []).forEach(function (c) {
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

  document.addEventListener('DOMContentLoaded', function () {
    initRulesDataTable();
    bindSplTabAdjust();
    initLevelsTable();
  });

  function initRulesDataTable() {
    if (!rulesTable || !window.DataTable || rulesTable.dataset.dtInited === '1') {
      return;
    }
    var rulesTab = document.getElementById('spl-tab-rules');
    if (!rulesTab || !rulesTab.classList.contains('show')) {
      return;
    }
    try {
      new DataTable(rulesTable, {
        responsive: false,
        autoWidth: true,
        paging: false,
        searching: false,
        lengthChange: false,
        info: false,
        ordering: true,
        order: [[0, 'asc']],
        columnDefs: [
          { orderable: false, targets: [5] },
          { visible: false, targets: [6] },
          { className: 'text-end', targets: [3, 5] },
          { className: 'text-center', targets: [4] }
        ]
      });
      rulesTable.dataset.dtInited = '1';
      getRulesDataTable().columns.adjust().draw(false);
    } catch (e) {
      console.warn('SPL rules DataTable init failed:', e);
    }
  }

  function adjustRulesDataTable() {
    if (!rulesTable) {
      return;
    }
    if (rulesTable.dataset.dtInited !== '1') {
      initRulesDataTable();
      return;
    }
    var dt = getRulesDataTable();
    if (dt) {
      dt.columns.adjust().draw(false);
    }
  }

  function bindSplTabAdjust() {
    document.querySelectorAll('[data-bs-target="#spl-tab-rules"]').forEach(function (btn) {
      btn.addEventListener('shown.bs.tab', adjustRulesDataTable);
    });
    if (document.getElementById('spl-tab-rules') && document.getElementById('spl-tab-rules').classList.contains('show')) {
      window.requestAnimationFrame(adjustRulesDataTable);
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
  var board = document.getElementById('splBoard');
  var editBtn = document.getElementById('splBoardEditBtn');
  var saveBtn = document.getElementById('splBoardSaveBtn');
  var cancelBtn = document.getElementById('splBoardCancelBtn');

  function setBoardEditing(on) {
    if (!board) {
      return;
    }
    if (on) {
      board.classList.add('is-editing');
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
      board.classList.remove('is-editing');
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

  if (boardCfg.canManage && editBtn) {
    editBtn.addEventListener('click', function () {
      setBoardEditing(true);
    });
  }

  if (cancelBtn) {
    cancelBtn.addEventListener('click', function () {
      window.location.reload();
    });
  }

  document.querySelectorAll('.spl-board-poster-input').forEach(function (input) {
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
})();
