/**
 * Prompt for actual hours when status moves to complete/closed.
 * window.omsActualHours.prompt({ estimate, isCompleteStatus }).then(hours|null)
 */
(function (window, document) {
  'use strict';
  if (window.omsActualHours) {
    return;
  }

  var COMPLETE_RE = /^(closed|complete[ds]?|done|finished)$/i;
  var COMPLETE_FUZZY_RE = /(closed|complet|finished|\bdone\b)/i;
  var INCOMPLETE_RE = /incomplete/i;

  function isCompleteStatus(status) {
    var s = String(status || '').trim().toLowerCase();
    if (!s) {
      return false;
    }
    if (INCOMPLETE_RE.test(s)) {
      return false;
    }
    if (COMPLETE_RE.test(s)) {
      return true;
    }
    return COMPLETE_FUZZY_RE.test(s);
  }

  function ensureModal() {
    var el = document.getElementById('omsActualHoursModal');
    if (el) {
      return el;
    }
    var wrap = document.createElement('div');
    wrap.innerHTML =
      '<div class="modal fade" id="omsActualHoursModal" tabindex="-1" aria-hidden="true">' +
      '<div class="modal-dialog modal-dialog-centered modal-sm"><div class="modal-content">' +
      '<div class="modal-header py-2"><h5 class="modal-title h6"><i class="bi bi-clock-history me-1"></i>Actual hours</h5>' +
      '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancel"></button></div>' +
      '<div class="modal-body py-3">' +
      '<p class="small text-muted mb-2">Enter actual hours before marking complete.</p>' +
      '<p class="small mb-2 d-none" id="omsActualHoursEstimateWrap">Estimate: <strong id="omsActualHoursEstimate">—</strong> hr</p>' +
      '<label class="form-label small mb-1" for="omsActualHoursInput">Actual (hrs) <span class="text-danger">*</span></label>' +
      '<input type="number" class="form-control form-control-sm" id="omsActualHoursInput" min="0" max="9999.99" step="0.25" required placeholder="e.g. 2.5" autocomplete="off">' +
      '<div class="invalid-feedback">Enter a number between 0 and 9999.99.</div></div>' +
      '<div class="modal-footer py-2">' +
      '<button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>' +
      '<button type="button" class="btn btn-primary btn-sm" id="omsActualHoursConfirm"><i class="bi bi-check-lg me-1"></i>Save &amp; complete</button>' +
      '</div></div></div></div>';
    document.body.appendChild(wrap.firstChild);
    return document.getElementById('omsActualHoursModal');
  }

  function parseHours(raw) {
    var s = String(raw == null ? '' : raw).trim();
    if (s === '' || !isFinite(Number(s))) {
      return null;
    }
    var v = Number(s);
    if (v < 0 || v > 9999.99) {
      return null;
    }
    return Math.round(v * 100) / 100;
  }

  function prompt(opts) {
    opts = opts || {};
    return new Promise(function (resolve) {
      if (!window.bootstrap || !bootstrap.Modal) {
        var fallback = window.prompt('Actual hours (required):', opts.estimate != null ? String(opts.estimate) : '');
        resolve(parseHours(fallback));
        return;
      }
      var modalEl = ensureModal();
      var input = document.getElementById('omsActualHoursInput');
      var confirmBtn = document.getElementById('omsActualHoursConfirm');
      var estWrap = document.getElementById('omsActualHoursEstimateWrap');
      var estEl = document.getElementById('omsActualHoursEstimate');
      var settled = false;

      function finish(val) {
        if (settled) {
          return;
        }
        settled = true;
        resolve(val);
      }

      if (estWrap && estEl) {
        var est = opts.estimate;
        if (est != null && est !== '' && isFinite(Number(est))) {
          estEl.textContent = String(est);
          estWrap.classList.remove('d-none');
          if (input && (!input.value || input.value === '')) {
            input.value = String(est);
          }
        } else {
          estWrap.classList.add('d-none');
        }
      }

      if (input) {
        input.classList.remove('is-invalid');
        if (!opts.estimate) {
          input.value = '';
        }
      }

      var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

      function onConfirm() {
        var hours = parseHours(input ? input.value : '');
        if (hours === null) {
          if (input) {
            input.classList.add('is-invalid');
            input.focus();
          }
          return;
        }
        modal.hide();
        finish(hours);
      }

      function onHidden() {
        confirmBtn && confirmBtn.removeEventListener('click', onConfirm);
        modalEl.removeEventListener('hidden.bs.modal', onHidden);
        input && input.removeEventListener('keydown', onKey);
        if (!settled) {
          finish(null);
        }
      }

      function onKey(e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          onConfirm();
        }
      }

      confirmBtn && confirmBtn.addEventListener('click', onConfirm);
      modalEl.addEventListener('hidden.bs.modal', onHidden);
      input && input.addEventListener('keydown', onKey);
      modal.show();
      window.setTimeout(function () {
        if (input) {
          input.focus();
          input.select();
        }
      }, 200);
    });
  }

  /**
   * If moving into a complete status, prompt and return hours; otherwise resolve undefined (no change).
   * Resolves null on cancel.
   */
  function maybePrompt(newStatus, oldStatus, estimate) {
    if (!isCompleteStatus(newStatus)) {
      return Promise.resolve(undefined);
    }
    if (isCompleteStatus(oldStatus)) {
      return Promise.resolve(undefined);
    }
    return prompt({ estimate: estimate });
  }

  function readEstimateFrom(el) {
    if (!el) {
      return null;
    }
    var v = el.getAttribute('data-estimate-hours');
    if (v != null && v !== '') {
      return v;
    }
    var row = el.closest('[data-estimate-hours]');
    if (row) {
      return row.getAttribute('data-estimate-hours');
    }
    var form = el.closest('form');
    if (form) {
      var estInput = form.querySelector('[name="estimate_hours"]');
      if (estInput && estInput.value) {
        return estInput.value;
      }
    }
    return null;
  }

  function ensureHiddenActual(form) {
    if (!form) {
      return null;
    }
    var input = form.querySelector('input[name="actual_hours"]');
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'actual_hours';
      form.appendChild(input);
    }
    return input;
  }

  // Delegated: .project-dash-status-select → complete
  document.addEventListener('change', function (e) {
    var sel = e.target.closest('.project-dash-status-select');
    if (!sel || sel.dataset.omsActualSkip === '1') {
      return;
    }
    var newStatus = sel.value;
    var oldStatus = sel.getAttribute('data-prev-status') || '';
    if (!sel.getAttribute('data-prev-status')) {
      // first bind: store current as prev after handling
    }
    if (!isCompleteStatus(newStatus) || isCompleteStatus(oldStatus)) {
      sel.setAttribute('data-prev-status', newStatus);
      return;
    }
    e.stopImmediatePropagation();
    e.preventDefault();
    var estimate = readEstimateFrom(sel);
    maybePrompt(newStatus, oldStatus, estimate).then(function (hours) {
      if (hours === null) {
        sel.value = oldStatus || sel.getAttribute('data-prev-status') || '';
        return;
      }
      sel.setAttribute('data-actual-hours', String(hours));
      sel.setAttribute('data-prev-status', newStatus);
      sel.dataset.omsActualSkip = '1';
      var ev = new Event('change', { bubbles: true });
      sel.dispatchEvent(ev);
      window.setTimeout(function () {
        delete sel.dataset.omsActualSkip;
      }, 0);
    });
  }, true);

  // Init prev status on selects
  function stampPrevStatuses(root) {
    (root || document).querySelectorAll('.project-dash-status-select').forEach(function (sel) {
      if (!sel.getAttribute('data-prev-status')) {
        sel.setAttribute('data-prev-status', sel.value || '');
      }
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { stampPrevStatuses(document); });
  } else {
    stampPrevStatuses(document);
  }

  // Forms: tasks / my works / projects with status → complete on submit
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM' || form.dataset.omsActualSkip === '1') {
      return;
    }
    if (form.classList && form.classList.contains('mw-dash-status-form')) {
      return; // handled by lane-status AJAX
    }
    var method = (form.getAttribute('method') || 'get').toLowerCase();
    if (method !== 'post') {
      return;
    }
    var statusEl = form.querySelector('select[name="status"], input[name="status"]');
    if (!statusEl) {
      return;
    }
    var newStatus = statusEl.value;
    if (!isCompleteStatus(newStatus)) {
      return;
    }
    var oldStatus = form.getAttribute('data-current-status')
      || statusEl.getAttribute('data-original-status')
      || statusEl.getAttribute('data-prev-status')
      || '';
    if (isCompleteStatus(oldStatus)) {
      return;
    }
    if (form.dataset.omsActualReady === '1') {
      var existing = form.querySelector('input[name="actual_hours"]');
      if (existing && parseHours(existing.value) !== null) {
        return;
      }
    }
    e.preventDefault();
    e.stopPropagation();
    var estimate = readEstimateFrom(statusEl) || readEstimateFrom(form);
    prompt({ estimate: estimate }).then(function (hours) {
      if (hours === null) {
        if (statusEl.tagName === 'SELECT' && oldStatus) {
          statusEl.value = oldStatus;
        }
        return;
      }
      var hidden = ensureHiddenActual(form);
      if (hidden) {
        hidden.value = String(hours);
      }
      form.dataset.omsActualReady = '1';
      form.dataset.omsActualSkip = '1';
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
      } else {
        HTMLFormElement.prototype.submit.call(form);
      }
      window.setTimeout(function () {
        delete form.dataset.omsActualSkip;
      }, 0);
    });
  }, true);

  function stampOriginalStatuses(root) {
    (root || document).querySelectorAll('form[method="post"] select[name="status"], form select[name="status"]').forEach(function (sel) {
      var form = sel.closest('form');
      if (!form) {
        return;
      }
      var method = (form.getAttribute('method') || 'get').toLowerCase();
      if (method !== 'post') {
        return;
      }
      if (!sel.getAttribute('data-original-status')) {
        sel.setAttribute('data-original-status', sel.value || '');
      }
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { stampOriginalStatuses(document); });
  } else {
    stampOriginalStatuses(document);
  }

  window.omsActualHours = {
    isCompleteStatus: isCompleteStatus,
    prompt: prompt,
    maybePrompt: maybePrompt,
    parseHours: parseHours,
    readEstimateFrom: readEstimateFrom,
    ensureHiddenActual: ensureHiddenActual,
    stampPrevStatuses: stampPrevStatuses,
    stampOriginalStatuses: stampOriginalStatuses
  };
})(window, document);
