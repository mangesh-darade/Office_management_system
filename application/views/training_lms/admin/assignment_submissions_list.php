<?php $this->load->view('partials/header', array('title' => 'Assignment submissions', 'extra_css' => array('assets/css/lms-ui.css'))); ?>
<?php
$total = is_array($rows) ? count($rows) : 0;
$pendingSubmitted = 0;
$assessed = 0;
if (is_array($rows)) {
    foreach ($rows as $r) {
        $st = isset($r->status) ? strtolower((string) $r->status) : '';
        if ($st === 'pending' || $st === 'submitted') {
            $pendingSubmitted++;
        } elseif ($st === 'assessed') {
            $assessed++;
        }
    }
}
$viewerEmail = (string) $this->session->userdata('email');
?>
<style>
  .lms-sub-root {
    font-family: Inter, Poppins, "Segoe UI", Roboto, Arial, sans-serif;
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px;
  }
  .lms-sub-hero {
    background: linear-gradient(135deg, rgba(59,130,246,0.14), rgba(255,255,255,0.85));
    border: 1px solid rgba(59,130,246,0.15);
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    padding: 18px;
    margin-bottom: 16px;
  }
  .lms-sub-title {
    margin: 0;
    font-weight: 700;
    color: #0f172a;
  }
  .lms-sub-subtitle {
    color: #334155;
    margin: 4px 0 0 0;
    font-size: 0.92rem;
  }
  .lms-sub-profile {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 6px 10px;
    color: #334155;
    font-size: .8rem;
  }
  .lms-kpi-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
    margin-top: 14px;
  }
  .lms-kpi-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 8px 18px rgba(15,23,42,.06);
    padding: 14px;
    transition: transform .2s ease, box-shadow .2s ease;
  }
  .lms-kpi-card:hover { transform: translateY(-1px); box-shadow: 0 12px 22px rgba(15,23,42,.1); }
  .lms-kpi-head { display:flex; align-items:center; gap:8px; color:#64748b; font-size:.78rem; }
  .lms-kpi-icon {
    width: 30px; height: 30px; border-radius: 10px; display:inline-flex; align-items:center; justify-content:center;
    background: #dbeafe; color: #1d4ed8;
  }
  .lms-kpi-value { font-weight: 800; font-size: 1.35rem; color:#0f172a; margin-top: 4px; }

  .lms-filters {
    position: sticky; top: 8px; z-index: 9;
    background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
    box-shadow: 0 8px 18px rgba(15,23,42,.05);
    padding: 10px; margin-bottom: 14px;
  }
  .lms-filters .form-control, .lms-filters .form-select {
    border-radius: 10px; border-color: #dbe2ea; min-height: 38px;
  }
  .lms-clear-btn {
    border-radius: 10px;
  }

  .lms-table-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 18px rgba(15,23,42,.05);
    background: #fff;
  }
  .lms-sub-table thead th {
    background: #f1f5f9;
    color: #334155;
    font-weight: 600;
    font-size: .82rem;
    border-bottom: 1px solid #e2e8f0;
  }
  .lms-sub-table tbody tr:nth-child(even) { background: #fafcff; }
  .lms-sub-table tbody tr:hover { background: #eef6ff; transition: background-color .2s ease; }
  .lms-sub-table td { color: #0f172a; font-size: .86rem; vertical-align: middle; }

  .lms-badge-submitted { background: #22c55e; color:#fff; }
  .lms-badge-pending { background: #f59e0b; color:#111827; }
  .lms-badge-assessed { background: #3b82f6; color:#fff; }
  .lms-review-btn {
    border-radius: 10px;
    transition: all .2s ease;
  }
  .lms-review-btn:hover { transform: translateY(-1px); }

  .lms-skeleton { display: none; }
  .lms-skeleton.show { display: block; }
  .lms-skel-row {
    height: 46px; border-radius: 10px; margin: 8px 0;
    background: linear-gradient(90deg, #eef2f7 25%, #f8fafc 37%, #eef2f7 63%);
    background-size: 400% 100%;
    animation: lmsSkel 1.25s ease infinite;
  }
  @keyframes lmsSkel { 0% {background-position: 100% 0;} 100% {background-position: 0 0;} }

  .lms-empty {
    text-align: center; padding: 34px 16px; color:#64748b;
  }

  .lms-mobile-cards { display: none; }
  .lms-m-card {
    border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 6px 14px rgba(15,23,42,.05);
    background: #fff; padding: 12px; margin-bottom: 10px;
  }
  .lms-m-title { font-weight: 700; color:#0f172a; margin-bottom:4px; }
  .lms-m-meta { font-size: .8rem; color:#64748b; margin-bottom: 8px; }

  .lms-pagination {
    display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top: 12px;
  }
  .lms-page-controls .btn { border-radius: 10px; }

  @media (max-width: 991.98px) {
    .lms-kpi-grid { grid-template-columns: 1fr; }
    .lms-filters { position: static; }
    .lms-table-wrap { display: none; }
    .lms-mobile-cards { display: block; }
  }
</style>

<div class="container-fluid py-4 lms-sub-root">
  <div class="lms-sub-hero">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <h1 class="h4 lms-sub-title">Assignment Submissions</h1>
        <p class="lms-sub-subtitle">
          <?php if (!empty($show_all_submissions)): ?>
            You are viewing all learners' uploads across topics
          <?php elseif (!empty($submissions_scope_team)): ?>
            You are viewing uploads for your team (mapped users or same department)
          <?php else: ?>
            You are viewing your own uploads across topics
          <?php endif; ?>
        </p>
      </div>
      <div class="lms-sub-profile" aria-label="Signed in user">
        <i class="bi bi-person-circle"></i>
        <span><?php echo esc_view($viewerEmail !== '' ? $viewerEmail : 'User'); ?></span>
      </div>
    </div>

    <div class="lms-kpi-grid">
      <div class="lms-kpi-card">
        <div class="lms-kpi-head"><span class="lms-kpi-icon"><i class="bi bi-collection"></i></span> Total Assignments</div>
        <div class="lms-kpi-value"><?php echo (int) $total; ?></div>
      </div>
      <div class="lms-kpi-card">
        <div class="lms-kpi-head"><span class="lms-kpi-icon"><i class="bi bi-hourglass-split"></i></span> Pending / Submitted</div>
        <div class="lms-kpi-value"><?php echo (int) $pendingSubmitted; ?></div>
      </div>
      <div class="lms-kpi-card">
        <div class="lms-kpi-head"><span class="lms-kpi-icon"><i class="bi bi-check2-circle"></i></span> Assessed</div>
        <div class="lms-kpi-value"><?php echo (int) $assessed; ?></div>
      </div>
    </div>
  </div>

  <div class="lms-filters">
    <div class="row g-2 align-items-center">
      <div class="col-lg-4 col-md-6">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="search" id="lmsSearch" class="form-control" placeholder="Search topic, assignment, learner">
        </div>
      </div>
      <div class="col-lg-2 col-md-6">
        <select id="lmsStatus" class="form-select">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="submitted">Submitted</option>
          <option value="assessed">Assessed</option>
        </select>
      </div>
      <div class="col-lg-2 col-md-6">
        <input type="date" id="lmsFrom" class="form-control" aria-label="From date">
      </div>
      <div class="col-lg-2 col-md-6">
        <input type="date" id="lmsTo" class="form-control" aria-label="To date">
      </div>
      <div class="col-lg-2 col-md-12 text-lg-end">
        <button type="button" id="lmsClear" class="btn btn-outline-secondary w-100 lms-clear-btn">
          <i class="bi bi-x-circle me-1"></i>Clear filters
        </button>
      </div>
    </div>
  </div>

  <div id="lmsSkeleton" class="lms-skeleton show" aria-hidden="true">
    <div class="lms-skel-row"></div>
    <div class="lms-skel-row"></div>
    <div class="lms-skel-row"></div>
    <div class="lms-skel-row"></div>
  </div>

  <div id="lmsContent" class="d-none">
    <div class="lms-table-wrap lms-table-card table-responsive">
      <table id="lmsTable" class="table lms-sub-table mb-0">
        <thead>
          <tr>
            <th>Topic / Module</th>
            <th>Assignment</th>
            <th>Submitted By</th>
            <th class="text-nowrap">Submitted At</th>
            <th>Attachment</th>
            <th>Score</th>
            <th>Assessed By</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="9">
                <div class="lms-empty">
                  <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                  No submissions yet
                </div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <?php
                $st = strtolower((string) (isset($r->status) ? $r->status : ''));
                $submittedAt = isset($r->submitted_at) ? (string) $r->submitted_at : '';
              ?>
              <tr class="lms-row"
                  data-status="<?php echo esc_view($st); ?>"
                  data-submitted="<?php echo esc_view($submittedAt); ?>"
                  data-search="<?php echo esc_view(strtolower(trim((string)$r->topic_name . ' ' . (string)$r->module_name . ' ' . (string)$r->assignment_name . ' ' . (string)$r->submitted_by_name . ' ' . (string)$r->submitted_by_email))); ?>">
                <td>
                  <div class="fw-semibold"><?php echo esc_view((string) $r->topic_name); ?></div>
                  <div class="text-muted small"><?php echo esc_view((string) $r->module_name); ?></div>
                </td>
                <td><?php echo esc_view((string) $r->assignment_name); ?></td>
                <td>
                  <div><?php echo esc_view(trim((string) $r->submitted_by_name)); ?></div>
                  <div class="text-muted small"><?php echo esc_view((string) $r->submitted_by_email); ?></div>
                </td>
                <td class="text-nowrap"><?php echo $submittedAt !== '' ? esc_view($submittedAt) : '—'; ?></td>
                <td>
                  <?php if (!empty($r->attachment_filename) && isset($r->submission_id)): ?>
                    <a href="<?php echo site_url('training-lms-admin/download/' . (int) $r->submission_id); ?>" data-bs-toggle="tooltip" data-bs-title="Download attachment">
                      <i class="bi bi-paperclip me-1"></i><?php echo esc_view((string) $r->attachment_filename); ?>
                    </a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
                <td><?php echo ($r->assignment_score !== null && $r->assignment_score !== '') ? esc_view((string) $r->assignment_score) : '—'; ?></td>
                <td><?php echo !empty($r->assessed_by_name) ? esc_view((string) $r->assessed_by_name) : '—'; ?></td>
                <td>
                  <?php if ($st === 'submitted'): ?>
                    <span class="badge lms-badge-submitted">Submitted</span>
                  <?php elseif ($st === 'pending'): ?>
                    <span class="badge lms-badge-pending">Pending</span>
                  <?php elseif ($st === 'assessed'): ?>
                    <span class="badge lms-badge-assessed">Assessed</span>
                  <?php else: ?>
                    <span class="badge bg-secondary"><?php echo esc_view((string) $st); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <?php if (!empty($r->topic_id) && !empty($can_review_submissions)): ?>
                    <a class="btn btn-sm btn-outline-primary lms-review-btn" href="<?php echo site_url('training-lms-admin/submissions/' . (int) $r->topic_id); ?>">
                      <i class="bi bi-eye me-1"></i>Review
                    </a>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div id="lmsMobileCards" class="lms-mobile-cards"></div>

    <div class="lms-pagination">
      <div id="lmsPageInfo" class="small text-muted">Showing 0 of 0</div>
      <div class="lms-page-controls">
        <button type="button" id="lmsPrev" class="btn btn-sm btn-outline-secondary me-1">Previous</button>
        <button type="button" id="lmsNext" class="btn btn-sm btn-outline-secondary">Next</button>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  var searchEl = document.getElementById('lmsSearch');
  var statusEl = document.getElementById('lmsStatus');
  var fromEl = document.getElementById('lmsFrom');
  var toEl = document.getElementById('lmsTo');
  var clearEl = document.getElementById('lmsClear');
  var rows = Array.prototype.slice.call(document.querySelectorAll('#lmsTable tbody .lms-row'));
  var mobileWrap = document.getElementById('lmsMobileCards');
  var infoEl = document.getElementById('lmsPageInfo');
  var prevEl = document.getElementById('lmsPrev');
  var nextEl = document.getElementById('lmsNext');
  var page = 1;
  var pageSize = 10;
  var filtered = rows.slice();

  function parseDate(v) {
    if (!v) return null;
    var ts = Date.parse(v.replace(' ', 'T'));
    return isNaN(ts) ? null : ts;
  }

  function passFilter(r) {
    var q = (searchEl.value || '').trim().toLowerCase();
    var st = (statusEl.value || '').toLowerCase();
    var from = fromEl.value ? Date.parse(fromEl.value + 'T00:00:00') : null;
    var to = toEl.value ? Date.parse(toEl.value + 'T23:59:59') : null;
    var text = r.getAttribute('data-search') || '';
    var rowSt = (r.getAttribute('data-status') || '').toLowerCase();
    var rowDate = parseDate(r.getAttribute('data-submitted') || '');
    if (q && text.indexOf(q) === -1) return false;
    if (st && st !== rowSt) return false;
    if (from && (!rowDate || rowDate < from)) return false;
    if (to && (!rowDate || rowDate > to)) return false;
    return true;
  }

  function statusBadgeHtml(st) {
    if (st === 'submitted') return '<span class="badge lms-badge-submitted">Submitted</span>';
    if (st === 'pending') return '<span class="badge lms-badge-pending">Pending</span>';
    if (st === 'assessed') return '<span class="badge lms-badge-assessed">Assessed</span>';
    return '<span class="badge bg-secondary">' + (st || '—') + '</span>';
  }

  function renderMobileCards(sliceRows) {
    if (!mobileWrap) return;
    if (!sliceRows.length) {
      mobileWrap.innerHTML = '<div class="lms-empty"><i class="bi bi-inbox fs-3 d-block mb-1"></i>No submissions yet</div>';
      return;
    }
    var html = '';
    sliceRows.forEach(function (r) {
      var cells = r.querySelectorAll('td');
      var topic = cells[0] ? cells[0].innerText.trim().split('\n')[0] : '—';
      var assignment = cells[1] ? cells[1].innerText.trim() : '—';
      var by = cells[2] ? cells[2].innerText.trim().split('\n')[0] : '—';
      var date = cells[3] ? cells[3].innerText.trim() : '—';
      var score = cells[5] ? cells[5].innerText.trim() : '—';
      var st = (r.getAttribute('data-status') || '').toLowerCase();
      var actionHtml = cells[8] ? cells[8].innerHTML : '—';
      html += '' +
        '<div class="lms-m-card">' +
          '<div class="lms-m-title">' + assignment + '</div>' +
          '<div class="lms-m-meta">' + topic + '</div>' +
          '<div class="small mb-1"><strong>User:</strong> ' + by + '</div>' +
          '<div class="small mb-1"><strong>Date:</strong> ' + date + '</div>' +
          '<div class="small mb-2"><strong>Score:</strong> ' + score + '</div>' +
          '<div class="d-flex justify-content-between align-items-center">' +
            statusBadgeHtml(st) +
            '<div>' + actionHtml + '</div>' +
          '</div>' +
        '</div>';
    });
    mobileWrap.innerHTML = html;
  }

  function render() {
    filtered = rows.filter(passFilter);
    var total = filtered.length;
    var maxPage = Math.max(1, Math.ceil(total / pageSize));
    if (page > maxPage) page = maxPage;
    var start = (page - 1) * pageSize;
    var end = start + pageSize;
    var sliceRows = filtered.slice(start, end);

    rows.forEach(function (r) { r.style.display = 'none'; });
    sliceRows.forEach(function (r) { r.style.display = ''; });

    renderMobileCards(sliceRows);
    infoEl.textContent = 'Showing ' + (total ? (start + 1) : 0) + '-' + Math.min(end, total) + ' of ' + total;
    prevEl.disabled = page <= 1;
    nextEl.disabled = page >= maxPage;
  }

  [searchEl, statusEl, fromEl, toEl].forEach(function (el) {
    if (!el) return;
    el.addEventListener('input', function () { page = 1; render(); });
    el.addEventListener('change', function () { page = 1; render(); });
  });
  if (clearEl) {
    clearEl.addEventListener('click', function () {
      searchEl.value = '';
      statusEl.value = '';
      fromEl.value = '';
      toEl.value = '';
      page = 1;
      render();
    });
  }
  if (prevEl) prevEl.addEventListener('click', function () { if (page > 1) { page--; render(); } });
  if (nextEl) nextEl.addEventListener('click', function () { page++; render(); });

  // Skeleton then show content
  window.setTimeout(function () {
    var sk = document.getElementById('lmsSkeleton');
    var ct = document.getElementById('lmsContent');
    if (sk) sk.classList.remove('show');
    if (ct) ct.classList.remove('d-none');
    render();
    if (window.bootstrap && bootstrap.Tooltip) {
      document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
      });
    }
  }, 350);
})();
</script>
<?php $this->load->view('partials/footer'); ?>
