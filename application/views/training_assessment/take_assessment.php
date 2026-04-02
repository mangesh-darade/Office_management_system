<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->view('partials/header', array(
  'title' => 'Assessment — ' . $au->assessment_title,
  'with_sidebar' => false,
));
$ajaxLoad = site_url('training-assessment/ajax-load-question');
$ajaxSave = site_url('training-assessment/ajax-save-answer');
$ajaxRun = site_url('training-assessment/ajax-run-code');
$ajaxSync = site_url('training-assessment/ajax-timer-sync');
$resultTokenUrl = site_url('training-assessment/result-token/' . rawurlencode($token));
?>
<style>
  #ta-timer-panel.ta-timer-warn {
    background: linear-gradient(90deg, #fff3cd 0%, #ffecb5 100%) !important;
    border-color: #ffc107 !important;
    color: #664d03;
  }
  #ta-timer-panel.ta-timer-critical {
    background: linear-gradient(90deg, #f8d7da 0%, #f5c2c7 100%) !important;
    border-color: #dc3545 !important;
    color: #58151c;
    animation: ta-pulse 1s ease-in-out infinite;
  }
  @keyframes ta-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.88; }
  }
  #ta-submit-overlay {
    position: fixed;
    inset: 0;
    background: rgba(255,255,255,0.92);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 1rem;
  }
  #ta-mobile-bar { padding-bottom: max(0.5rem, env(safe-area-inset-bottom)); }
  @media (min-width: 992px) {
    #ta-main-col { padding-bottom: 0; }
  }
</style>
<div id="ta-submit-overlay" class="d-none" aria-live="assertive" aria-busy="true">
  <div class="spinner-border text-primary" style="width:3rem;height:3rem" role="status"></div>
  <div class="fw-semibold text-center px-3">Submitting your assessment…</div>
</div>

<div class="container-fluid py-3">
  <div class="row g-3">
    <div class="col-lg-3 col-xl-2 order-lg-1 order-2">
      <div class="card shadow-sm border-0 sticky-top" style="top:72px;z-index:100">
        <div class="card-header py-2 small fw-semibold">Questions</div>
        <div class="card-body p-2" id="ta-nav"></div>
        <div class="card-footer small text-muted">
          <span id="ta-progress">—</span>
        </div>
      </div>
    </div>
    <div class="col-lg-9 col-xl-10 order-lg-2 order-1" id="ta-main-col">
      <div id="ta-timer-panel" class="alert alert-primary d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 py-2 shadow-sm border" role="timer" aria-label="Assessment timer">
        <div><i class="bi bi-clock me-2" aria-hidden="true"></i><strong>Time remaining:</strong> <span id="ta-timer" class="fs-5 font-monospace" aria-live="polite" aria-atomic="true">--:--</span></div>
        <div class="small text-md-end"><?php echo htmlspecialchars($au->assessment_title); ?></div>
      </div>
      <div id="ta-time-hints" class="mb-2"></div>
      <div id="ta-warn" class="alert alert-warning d-none small mb-2"><strong>Time is up.</strong> Saving and submitting automatically…</div>
      <div class="card shadow-sm border-0">
        <div class="card-body" style="min-height:280px" id="ta-stage">
          <div class="text-center py-5 text-muted"><div class="spinner-border" role="status"></div><p class="mt-2 mb-0">Loading…</p></div>
        </div>
        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-2 d-none d-lg-flex">
          <button type="button" class="btn btn-outline-secondary" id="ta-prev" disabled aria-label="Previous question"><i class="bi bi-chevron-left me-1"></i>Previous</button>
          <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
            <span id="ta-save-status" class="small text-muted order-2 order-md-1" aria-live="polite">Answers save automatically</span>
            <button type="button" class="btn btn-primary order-1 order-md-2" id="ta-next" aria-label="Next question"><span id="ta-next-label">Next</span><i class="bi bi-chevron-right ms-1"></i></button>
          </div>
        </div>
        <div class="card-footer bg-white d-flex d-lg-none flex-wrap justify-content-between align-items-center gap-2">
          <button type="button" class="btn btn-outline-secondary" id="ta-prev-desktop-sm" disabled aria-label="Previous question"><i class="bi bi-chevron-left"></i></button>
          <span id="ta-save-status-sm" class="small text-muted flex-grow-1 text-center" aria-live="polite"></span>
          <button type="button" class="btn btn-primary" id="ta-next-desktop-sm" aria-label="Next question"><i class="bi bi-chevron-right"></i></button>
        </div>
      </div>
      <?php echo form_open('training-assessment/submit-assessment', array('id' => 'ta-finish-form', 'class' => 'd-none')); ?>
      <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
      <input type="hidden" name="access_token" value="<?php echo htmlspecialchars($token); ?>">
      <input type="hidden" name="submission_trigger" id="ta-submit-reason" value="manual">
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<div id="ta-mobile-bar" class="d-lg-none fixed-bottom bg-white border-top shadow py-2 px-3 d-flex flex-column gap-2" style="z-index:1050">
  <div class="d-flex justify-content-between align-items-center small">
    <span class="text-muted"><i class="bi bi-clock me-1"></i>Time left</span>
    <span id="ta-timer-mobile" class="font-monospace fw-semibold" aria-hidden="true">--:--</span>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-outline-secondary flex-grow-1" id="ta-prev-m" aria-label="Previous question">Previous</button>
    <button type="button" class="btn btn-primary flex-grow-1" id="ta-next-m" aria-label="Next or submit"><span id="ta-next-m-label">Next</span></button>
  </div>
</div>
<div class="d-lg-none" style="height:6.5rem" aria-hidden="true"></div>

<div class="modal fade" id="ta-confirm-submit" tabindex="-1" aria-labelledby="ta-confirm-submit-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="ta-confirm-submit-title">Submit assessment?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body small">You will not be able to change your answers after submitting. Unsaved changes on this question will be saved first.</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="ta-confirm-submit-yes">Yes, submit</button>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  var token = <?php echo json_encode($token); ?>;
  var ajaxLoad = <?php echo json_encode($ajaxLoad); ?>;
  var ajaxSave = <?php echo json_encode($ajaxSave); ?>;
  var ajaxRun = <?php echo json_encode($ajaxRun); ?>;
  var ajaxSync = <?php echo json_encode($ajaxSync); ?>;
  var resultTokenUrl = <?php echo json_encode($resultTokenUrl); ?>;
  var endsTs = <?php echo (int)$ends_ts; ?>;
  var total = <?php echo (int)$total_questions; ?>;
  var idx = 0;
  var timerIv = null;
  var syncIv = null;
  var autoFinishing = false;
  var warned5 = false;
  var warned1 = false;
  var baseTitle = document.title;
  var autoSaveTimer = null;
  var initialAnswered = <?php echo json_encode(isset($initial_answered) ? $initial_answered : array()); ?>;
  var answeredStates = [];
  for (var ai = 0; ai < total; ai++) {
    answeredStates.push(!!(initialAnswered[ai]));
  }
  var taDirty = false;
  var confirmModal = null;
  try {
    var elM = document.getElementById('ta-confirm-submit');
    if (elM && window.bootstrap && bootstrap.Modal) {
      confirmModal = new bootstrap.Modal(elM);
    }
  } catch (e) {}

  function clearAutoSaveDebounce() {
    if (autoSaveTimer) {
      clearTimeout(autoSaveTimer);
      autoSaveTimer = null;
    }
  }

  function setSaveStatus(text, kind) {
    var st = document.getElementById('ta-save-status');
    var st2 = document.getElementById('ta-save-status-sm');
    function apply(el) {
      if (!el) return;
      el.textContent = text;
      if (kind === 'ok') el.className = el.id === 'ta-save-status-sm' ? 'small text-success flex-grow-1 text-center' : 'small text-success order-2 order-md-1';
      else if (kind === 'warn') el.className = el.id === 'ta-save-status-sm' ? 'small text-warning flex-grow-1 text-center' : 'small text-warning order-2 order-md-1';
      else if (kind === 'err') el.className = el.id === 'ta-save-status-sm' ? 'small text-danger flex-grow-1 text-center' : 'small text-danger order-2 order-md-1';
      else if (kind === 'busy') el.className = el.id === 'ta-save-status-sm' ? 'small text-secondary flex-grow-1 text-center' : 'small text-secondary order-2 order-md-1';
      else el.className = el.id === 'ta-save-status-sm' ? 'small text-muted flex-grow-1 text-center' : 'small text-muted order-2 order-md-1';
    }
    apply(st);
    apply(st2);
  }

  function scheduleAutoSaveDebounced() {
    if (autoFinishing) return;
    taDirty = true;
    clearAutoSaveDebounce();
    setSaveStatus('Unsaved changes…', 'warn');
    autoSaveTimer = setTimeout(function() {
      autoSaveTimer = null;
      if (autoFinishing) return;
      saveCurrent({ statusMsg: true });
    }, 750);
  }

  function pad(n) { return n < 10 ? '0' + n : '' + n; }

  function finishAssessment(reason) {
    if (autoFinishing) return;
    autoFinishing = true;
    clearAutoSaveDebounce();
    if (timerIv) { clearInterval(timerIv); timerIv = null; }
    if (syncIv) { clearInterval(syncIv); syncIv = null; }
    var reasonVal = reason || 'manual';
    if (reasonVal === 'time_up') reasonVal = 'time_up';
    else if (reasonVal === 'server_time') reasonVal = 'server_time';
    else reasonVal = 'manual';
    document.getElementById('ta-submit-reason').value = reasonVal;
    document.getElementById('ta-submit-overlay').classList.remove('d-none');
    $('#ta-prev, #ta-next, #ta-prev-m, #ta-next-m, #ta-prev-desktop-sm, #ta-next-desktop-sm, .ta-nav-btn').prop('disabled', true);
    window.onbeforeunload = null;
    applyFinalizeFieldsToForm();
    document.getElementById('ta-finish-form').submit();
  }

  /** Pack current question into the finish form so the server saves it in the same POST as finalize (faster than AJAX + submit). */
  function applyFinalizeFieldsToForm() {
    var form = document.getElementById('ta-finish-form');
    if (!form) return;
    var old = form.querySelectorAll('input[name^="finalize_"]');
    for (var r = 0; r < old.length; r++) {
      old[r].parentNode.removeChild(old[r]);
    }
    var $wrap = $('#ta-stage .ta-question-body');
    if (!$wrap.length) return;
    function hid(name, val) {
      var i = document.createElement('input');
      i.type = 'hidden';
      i.name = name;
      i.value = (val === undefined || val === null) ? '' : String(val);
      form.appendChild(i);
    }
    var data = gatherPayload($wrap);
    var qid = parseInt(data.question_id, 10);
    if (!qid) return;
    hid('finalize_question_id', qid);
    var qt = $wrap.data('qtype');
    if (qt === 'mcq') {
      hid('finalize_selected_option_id', data.selected_option_id || '');
    } else if (qt === 'text') {
      hid('finalize_answer_text', data.answer_text || '');
    } else {
      hid('finalize_code_submitted', data.code_submitted || '');
      hid('finalize_execution_output', data.execution_output || '');
    }
  }

  function pushTimeHint(html, cls) {
    var box = document.getElementById('ta-time-hints');
    var d = document.createElement('div');
    d.className = 'alert alert-' + (cls || 'warning') + ' alert-dismissible fade show py-2 small mb-1';
    d.setAttribute('role', 'alert');
    d.innerHTML = html + '<button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>';
    box.appendChild(d);
  }

  function tickTimer() {
    if (autoFinishing) return;
    var now = Math.floor(Date.now() / 1000);
    var left = endsTs - now;
    var panel = document.getElementById('ta-timer-panel');

    if (left <= 300 && !warned5) {
      warned5 = true;
      pushTimeHint('<strong>5 minutes left.</strong> Answers are saved as you go — review and submit when ready.', 'warning');
    }
    if (left <= 60 && !warned1) {
      warned1 = true;
      pushTimeHint('<strong>1 minute left.</strong> Time will run out soon — answers will be submitted automatically.', 'danger');
      try {
        if (window.AudioContext || window.webkitAudioContext) {
          var ctx = new (window.AudioContext || window.webkitAudioContext)();
          var o = ctx.createOscillator();
          var g = ctx.createGain();
          o.connect(g); g.connect(ctx.destination);
          o.frequency.value = 880;
          g.gain.setValueAtTime(0.08, ctx.currentTime);
          g.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
          o.start(ctx.currentTime);
          o.stop(ctx.currentTime + 0.15);
        }
      } catch (e) {}
    }

    if (left <= 0) {
      document.getElementById('ta-timer').textContent = '00:00';
      var tm0 = document.getElementById('ta-timer-mobile');
      if (tm0) tm0.textContent = '00:00';
      document.getElementById('ta-warn').classList.remove('d-none');
      panel.classList.remove('ta-timer-warn', 'ta-timer-critical');
      document.title = baseTitle;
      finishAssessment('time_up');
      return;
    }

    var m = Math.floor(left / 60);
    var s = left % 60;
    var ts = pad(m) + ':' + pad(s);
    document.getElementById('ta-timer').textContent = ts;
    var tm = document.getElementById('ta-timer-mobile');
    if (tm) tm.textContent = ts;
    document.title = '(' + ts + ') ' + baseTitle;

    panel.classList.toggle('ta-timer-warn', left <= 300 && left > 60);
    panel.classList.toggle('ta-timer-critical', left <= 60);
  }

  function syncServerTime() {
    if (autoFinishing) return;
    $.post(ajaxSync, { access_token: token }, function(res) {
      if (!res || !res.ok) return;
      if (res.csrf) $('input[name="ci_csrf_token"]').val(res.csrf);
      if (res.completed) {
        window.location.href = resultTokenUrl;
        return;
      }
      if (res.expired) {
        document.getElementById('ta-warn').classList.remove('d-none');
        finishAssessment('server_time');
        return;
      }
      if (res.ends_ts) {
        var srvEnd = parseInt(res.ends_ts, 10);
        if (srvEnd > 0 && Math.abs(srvEnd - endsTs) > 5) {
          endsTs = srvEnd;
        }
      }
    }, 'json');
  }

  function refreshNavStyles() {
    var btns = document.querySelectorAll('.ta-nav-btn');
    for (var j = 0; j < btns.length; j++) {
      var i = parseInt(btns[j].getAttribute('data-i'), 10);
      btns[j].classList.remove('btn-primary', 'btn-outline-success', 'btn-outline-secondary', 'active');
      if (i === idx) {
        btns[j].classList.add('btn-primary');
      } else if (answeredStates[i]) {
        btns[j].classList.add('btn-outline-success');
      } else {
        btns[j].classList.add('btn-outline-secondary');
      }
      var label = 'Question ' + (i + 1);
      if (answeredStates[i]) {
        label += ', answered';
      } else {
        label += ', not answered';
      }
      btns[j].setAttribute('aria-label', label);
    }
  }

  function buildNav() {
    var nav = document.getElementById('ta-nav');
    nav.innerHTML = '';
    for (var i = 0; i < total; i++) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn btn-sm w-100 mb-1 text-start ta-nav-btn';
      b.setAttribute('data-i', i);
      b.innerHTML = 'Q' + (i + 1) + (answeredStates[i] ? ' <span class="badge bg-success ms-1" style="font-size:0.65rem">OK</span>' : ' <span class="badge bg-secondary ms-1" style="font-size:0.65rem">—</span>');
      nav.appendChild(b);
    }
    document.getElementById('ta-progress').textContent = 'Question ' + (idx + 1) + ' of ' + total;
    refreshNavStyles();
  }

  function setNavActive() {
    refreshNavStyles();
    document.getElementById('ta-progress').textContent = 'Question ' + (idx + 1) + ' of ' + total;
    var prevDis = idx <= 0;
    document.getElementById('ta-prev').disabled = prevDis;
    var psm = document.getElementById('ta-prev-m');
    if (psm) psm.disabled = prevDis;
    var pds = document.getElementById('ta-prev-desktop-sm');
    if (pds) pds.disabled = prevDis;
    var isLast = idx >= total - 1;
    var nextTxt = isLast ? 'Submit test' : 'Next';
    var nl = document.getElementById('ta-next-label');
    if (nl) nl.textContent = nextTxt;
    var nml = document.getElementById('ta-next-m-label');
    if (nml) nml.textContent = nextTxt;
    var nextEl = document.getElementById('ta-next');
    if (nextEl) nextEl.setAttribute('aria-label', isLast ? 'Submit assessment' : 'Next question');
    var nm = document.getElementById('ta-next-m');
    if (nm) nm.setAttribute('aria-label', isLast ? 'Submit assessment' : 'Next question');
    var nds = document.getElementById('ta-next-desktop-sm');
    if (nds) nds.setAttribute('aria-label', isLast ? 'Submit assessment' : 'Next question');
  }

  function gatherPayload($wrap) {
    var qid = parseInt($wrap.data('qid'), 10);
    var qtype = $wrap.data('qtype');
    var data = { access_token: token, question_id: qid };
    if (qtype === 'mcq') {
      var v = $wrap.find('.ta-mcq-opt:checked').val();
      data.selected_option_id = v ? v : '';
    } else if (qtype === 'text') {
      data.answer_text = $wrap.find('.ta-text-answer').val();
    } else {
      data.code_submitted = $wrap.find('.ta-code-input').val();
      data.execution_output = $wrap.find('.ta-code-output').val();
    }
    return data;
  }

  function payloadIsAnswered(data, qtype) {
    if (qtype === 'mcq') return !!(data.selected_option_id);
    if (qtype === 'text') return !!(data.answer_text && String(data.answer_text).trim());
    return !!((data.code_submitted && String(data.code_submitted).trim()) || (data.execution_output && String(data.execution_output).trim()));
  }

  function saveCurrent(opts) {
    opts = opts || {};
    var after = opts.after;
    var onOk = opts.onOk;
    var statusMsg = opts.statusMsg === true;
    var skipAfter = false;
    clearAutoSaveDebounce();
    var $wrap = $('#ta-stage .ta-question-body');
    if (!$wrap.length) {
      if (after) after();
      return;
    }
    if (statusMsg) setSaveStatus('Saving…', 'busy');
    var frozenPayload = gatherPayload($wrap);
    var frozenIdx = idx;
    var frozenQt = $wrap.data('qtype');
    $.ajax({
      url: ajaxSave,
      type: 'POST',
      data: frozenPayload,
      dataType: 'json',
      timeout: 25000
    }).done(function(res) {
      if (autoFinishing) return;
      if (res && res.csrf) $('input[name="ci_csrf_token"]').val(res.csrf);
      if (res && res.force_submit) {
        skipAfter = true;
        document.getElementById('ta-warn').classList.remove('d-none');
        finishAssessment('server_time');
        return;
      }
      if (res && res.redirect && res.error === 'Closed') {
        skipAfter = true;
        window.location.href = res.redirect;
        return;
      }
      if (statusMsg) {
        if (res && res.ok) {
          setSaveStatus('Saved', 'ok');
          taDirty = false;
          if (frozenPayload && frozenIdx >= 0 && frozenIdx < answeredStates.length) {
            answeredStates[frozenIdx] = payloadIsAnswered(frozenPayload, frozenQt);
            refreshNavStyles();
          }
        } else {
          setSaveStatus('Could not save', 'err');
        }
      }
      if (onOk && res && res.ok) onOk(res);
    }).fail(function(xhr, textStatus) {
      if (autoFinishing) return;
      if (statusMsg) {
        if (textStatus === 'timeout') setSaveStatus('Save timed out — retrying when you move on', 'err');
        else setSaveStatus('Could not save', 'err');
      }
    }).always(function() {
      if (autoFinishing || skipAfter) return;
      if (after) after();
    });
  }

  function loadQ(i, afterSave) {
    function doLoad() {
      if (autoFinishing) return;
      $.post(ajaxLoad, { access_token: token, q_index: i }, function(res) {
        if (autoFinishing) return;
        if (!res || !res.ok) {
          if (res && res.force_submit) {
            document.getElementById('ta-warn').classList.remove('d-none');
            finishAssessment('server_time');
            return;
          }
          if (res && res.redirect) { window.location.href = res.redirect; return; }
          $('#ta-stage').html('<div class="alert alert-danger">Could not load question.</div>');
          return;
        }
        if (res.ends_ts) { endsTs = parseInt(res.ends_ts, 10); }
        if (res.csrf) { $('input[name="ci_csrf_token"]').val(res.csrf); }
        idx = res.q_index;
        $('#ta-stage').html(res.html);
        setNavActive();
        bindRunCode();
        bindAutoSave();
        taDirty = false;
        setSaveStatus('Answers save automatically', 'neutral');
      }, 'json');
    }
    if (afterSave) {
      saveCurrent({ after: doLoad });
    } else {
      doLoad();
    }
  }

  function runJsInBrowser(code) {
    var output = [];
    var fake = {
      log: function() {
        var parts = [];
        for (var k = 0; k < arguments.length; k++) parts.push(String(arguments[k]));
        output.push(parts.join(' '));
      }
    };
    try {
      var fn = new Function('console', String(code));
      fn(fake);
    } catch (e) {
      output.push('Error: ' + e.message);
    }
    return output.join('\n');
  }

  function bindRunCode() {
    $('.ta-run-code').off('click').on('click', function() {
      if (autoFinishing) return;
      var $wrap = $(this).closest('.ta-question-body');
      var lang = String($wrap.data('codeLang') || 'php').toLowerCase();
      var code = $wrap.find('.ta-code-input').val();
      if (lang.indexOf('js') >= 0) {
        var out = runJsInBrowser(code);
        $wrap.find('.ta-code-output').val(out);
        saveCurrent({ statusMsg: true });
        return;
      }
      $.post(ajaxRun, { access_token: token, language: 'php', code: code }, function(res) {
        if (autoFinishing) return;
        if (res && res.force_submit) {
          document.getElementById('ta-warn').classList.remove('d-none');
          finishAssessment('server_time');
          return;
        }
        if (!res || !res.ok) return;
        if (res.client_js) {
          $wrap.find('.ta-code-output').val(runJsInBrowser($wrap.find('.ta-code-input').val()));
          saveCurrent({ statusMsg: true });
          return;
        }
        var t = res.output || '';
        if (res.error) t += (t ? '\n' : '') + res.error;
        $wrap.find('.ta-code-output').val(t.trim());
        saveCurrent({ statusMsg: true });
      }, 'json');
    });
  }

  /** Auto-save: MCQ on pick; text/code debounced on typing + save on blur. */
  function bindAutoSave() {
    var $stage = $('#ta-stage');
    $stage.find('.ta-mcq-opt').off('change.taAuto').on('change.taAuto', function() {
      if (autoFinishing) return;
      taDirty = true;
      saveCurrent({ statusMsg: true });
    });
    $stage.find('.ta-text-answer').off('input.taAuto blur.taAuto').on('input.taAuto', function() {
      if (autoFinishing) return;
      scheduleAutoSaveDebounced();
    }).on('blur.taAuto', function() {
      if (autoFinishing) return;
      clearAutoSaveDebounce();
      saveCurrent({ statusMsg: true });
    });
    $stage.find('.ta-code-input, .ta-code-output').off('input.taAuto blur.taAuto').on('input.taAuto', function() {
      if (autoFinishing) return;
      scheduleAutoSaveDebounced();
    }).on('blur.taAuto', function() {
      if (autoFinishing) return;
      clearAutoSaveDebounce();
      saveCurrent({ statusMsg: true });
    });
  }

  function handleNextClick() {
    if (autoFinishing) return;
    if (idx >= total - 1) {
      if (confirmModal) {
        confirmModal.show();
      } else if (window.confirm('Submit assessment? You cannot change answers after submitting.')) {
        finishAssessment('manual');
      }
      return;
    }
    loadQ(idx + 1, true);
  }

  $('#ta-next, #ta-next-m, #ta-next-desktop-sm').on('click', function() {
    handleNextClick();
  });
  $('#ta-prev, #ta-prev-m, #ta-prev-desktop-sm').on('click', function() {
    if (autoFinishing) return;
    if (idx <= 0) return;
    loadQ(idx - 1, true);
  });
  var btnYes = document.getElementById('ta-confirm-submit-yes');
  if (btnYes) {
    btnYes.addEventListener('click', function() {
      if (confirmModal) confirmModal.hide();
      finishAssessment('manual');
    });
  }
  $(document).on('click', '.ta-nav-btn', function() {
    if (autoFinishing) return;
    var ni = parseInt($(this).data('i'), 10);
    if (ni === idx) return;
    loadQ(ni, true);
  });

  window.addEventListener('beforeunload', function(e) {
    if (autoFinishing) return;
    if (!taDirty && !autoSaveTimer) return;
    e.preventDefault();
    e.returnValue = '';
  });

  history.pushState(null, null, location.href);
  window.addEventListener('popstate', function() {
    history.pushState(null, null, location.href);
    alert('Back navigation is disabled during the assessment.');
  });

  buildNav();
  tickTimer();
  timerIv = setInterval(tickTimer, 1000);
  syncIv = setInterval(syncServerTime, 45000);
  syncServerTime();
  loadQ(0, false);
})();
</script>
<?php $this->load->view('partials/footer'); ?>
