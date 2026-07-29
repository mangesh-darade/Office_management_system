/**
 * Overview lane drag-and-drop (Second Brain).
 * Bound once on document so AJAX filter reloads keep DnD working.
 */
(function (window, document) {
  'use strict';
  if (window.mwDashLaneDnDInited) {
    return;
  }
  window.mwDashLaneDnDInited = true;

  var dragRow = null;
  var dragFromBody = null;
  var dragActive = false;

  function updateUrl() {
    var page = document.querySelector('.mw-dash-page[data-lane-update-url]');
    if (page) {
      var u = page.getAttribute('data-lane-update-url');
      if (u) {
        return u;
      }
    }
    return window.mwDashLaneUpdateUrl || '';
  }

  function csrfName() {
    return window.csrfTokenName || 'ci_csrf_token';
  }

  function csrfHash() {
    if (typeof window.getCsrfToken === 'function') {
      var t = window.getCsrfToken();
      if (t) {
        return t;
      }
    }
    return '';
  }

  function taskRow(el) {
    return el && el.closest ? el.closest('.mw-dash-task-row-draggable') : null;
  }

  function laneBody(el) {
    return el && el.closest ? el.closest('.mw-dash-lane-body') : null;
  }

  function laneCountEl(section, lane) {
    var laneEl = document.querySelector('.mw-dash-lane[data-section="' + section + '"][data-lane="' + lane + '"]');
    return laneEl ? laneEl.querySelector('.mw-dash-lane-count') : null;
  }

  function refreshLaneCount(section, lane) {
    var countEl = laneCountEl(section, lane);
    var body = document.querySelector('.mw-dash-lane-body[data-section="' + section + '"][data-lane="' + lane + '"]');
    if (!countEl || !body) {
      return;
    }
    var n = body.querySelectorAll('.mw-dash-task-row').length;
    countEl.textContent = String(n);
  }

  function ensureEmptyRow(body) {
    if (!body.querySelector('.mw-dash-task-row')) {
      var colspan = body.getAttribute('data-colspan') || '4';
      var tr = document.createElement('tr');
      tr.className = 'mw-dash-lane-empty-row';
      tr.innerHTML = '<td colspan="' + colspan + '" class="mw-dash-lane-empty">No tasks</td>';
      body.appendChild(tr);
    }
  }

  function removeEmptyRow(body) {
    var empty = body.querySelector('.mw-dash-lane-empty-row');
    if (empty) {
      empty.remove();
    }
  }

  function formatLaneDate(raw) {
    if (!raw) {
      return { label: '—', title: 'No due date' };
    }
    var parts = String(raw).substring(0, 10).split('-');
    if (parts.length !== 3) {
      return { label: String(raw), title: String(raw) };
    }
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var m = parseInt(parts[1], 10) - 1;
    var d = parseInt(parts[2], 10);
    var y = parts[0];
    if (m < 0 || m > 11 || !d) {
      return { label: String(raw), title: String(raw) };
    }
    return {
      label: months[m] + ' ' + d + ', ' + y,
      title: parts[0] + '-' + parts[1] + '-' + parts[2]
    };
  }

  function adaptRowToLane(row, body, dueDate) {
    if (!row || !body) {
      return;
    }
    var laneEl = body.closest('.mw-dash-lane');
    var showsDate = !!(laneEl && laneEl.classList.contains('mw-dash-lane-has-date'));
    var dateCell = row.querySelector('.mw-dash-col-date-cell');
    if (!showsDate) {
      if (dateCell) {
        dateCell.remove();
      }
      return;
    }
    var formatted = formatLaneDate(dueDate || '');
    if (!dateCell) {
      dateCell = document.createElement('td');
      dateCell.className = 'mw-dash-col-date-cell';
      row.appendChild(dateCell);
    }
    dateCell.textContent = formatted.label;
    dateCell.setAttribute('title', formatted.title);
  }

  function clearDropTargets() {
    document.querySelectorAll('.mw-dash-drop-target').forEach(function (el) {
      el.classList.remove('mw-dash-drop-target');
    });
  }

  function markDropTarget(body) {
    clearDropTargets();
    body.classList.add('mw-dash-drop-target');
    var scroll = body.closest('.mw-dash-lane-body-scroll');
    if (scroll) {
      scroll.classList.add('mw-dash-drop-target');
    }
  }

  document.addEventListener('dragstart', function (e) {
    var handle = e.target.closest ? e.target.closest('.mw-dash-drag-handle') : null;
    if (!handle) {
      return;
    }
    dragRow = taskRow(handle);
    if (!dragRow) {
      return;
    }
    dragFromBody = dragRow.parentElement;
    dragActive = true;
    dragRow.classList.add('mw-dash-dragging');
    if (e.dataTransfer) {
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', dragRow.getAttribute('data-id') || 'move');
    }
  }, true);

  document.addEventListener('dragend', function (e) {
    var row = taskRow(e.target);
    if (row) {
      row.classList.remove('mw-dash-dragging');
    }
    clearDropTargets();
    window.setTimeout(function () {
      dragRow = null;
      dragFromBody = null;
      dragActive = false;
    }, 0);
  }, true);

  document.addEventListener('dragover', function (e) {
    if (!dragRow) {
      return;
    }
    var body = laneBody(e.target);
    if (!body) {
      return;
    }
    e.preventDefault();
    if (e.dataTransfer) {
      e.dataTransfer.dropEffect = 'move';
    }
    markDropTarget(body);
  });

  document.addEventListener('dragleave', function (e) {
    var body = laneBody(e.target);
    if (!body) {
      return;
    }
    var related = e.relatedTarget;
    if (related && body.contains(related)) {
      return;
    }
    body.classList.remove('mw-dash-drop-target');
    var scroll = body.closest('.mw-dash-lane-body-scroll');
    if (scroll && (!related || !scroll.contains(related))) {
      scroll.classList.remove('mw-dash-drop-target');
    }
  });

  document.addEventListener('drop', function (e) {
    if (!dragRow || !dragFromBody) {
      return;
    }
    var body = laneBody(e.target);
    if (!body) {
      return;
    }
    e.preventDefault();
    clearDropTargets();

    var newLane = body.getAttribute('data-lane');
    var newSection = body.getAttribute('data-section');
    var oldLane = dragRow.getAttribute('data-lane');
    var oldSection = dragRow.getAttribute('data-section');
    var id = dragRow.getAttribute('data-id');
    var movingRow = dragRow;
    var fromBody = dragFromBody;
    var url = updateUrl();

    if (!newLane || !newSection || !id || newLane === oldLane || !url) {
      return;
    }
    if (newSection !== oldSection) {
      return;
    }

    removeEmptyRow(body);
    body.appendChild(movingRow);
    movingRow.setAttribute('data-lane', newLane);
    adaptRowToLane(movingRow, body, '');
    ensureEmptyRow(fromBody);
    refreshLaneCount(oldSection, oldLane);
    refreshLaneCount(newSection, newLane);

    var payload = new URLSearchParams();
    payload.append('id', id);
    payload.append('lane', newLane);
    var token = csrfHash();
    if (token) {
      payload.append(csrfName(), token);
    }

    fetch(url, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: payload.toString()
    }).then(function (r) {
      return r.json();
    }).then(function (data) {
      if (!data || !data.ok || (data.computed_lane && data.computed_lane !== newLane)) {
        window.location.reload();
        return;
      }
      adaptRowToLane(movingRow, body, data.due_date || '');
    }).catch(function () {
      window.location.reload();
    });
  });

  document.addEventListener('click', function (e) {
    if (dragActive && e.target.closest && e.target.closest('.mw-dash-task-link')) {
      e.preventDefault();
    }
  }, true);
})(window, document);
