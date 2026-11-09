<?php
/**
 * Shared History table (Date | Comments | Added By) used by every detail screen.
 * Also makes the "Save note" form submit via AJAX (no redirect flash, esp. inside popups).
 *
 * @var array $history_rows  list of array('date' => string, 'who' => string, 'parts' => string[], 'text' => string)
 */
$history_rows = isset($history_rows) && is_array($history_rows) ? $history_rows : array();
?>
<div class="oms-history-host">
<?php if (empty($history_rows)): ?>
  <p class="text-muted small mb-0">No history yet.</p>
<?php else: ?>
<div class="table-responsive oms-history-scroll">
  <table class="table table-sm table-bordered mb-0 align-middle oms-history-grid">
    <thead class="table-light">
      <tr>
        <th class="text-start oms-history-col-date">Date</th>
        <th>Comments</th>
        <th class="oms-history-col-by">Added By</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($history_rows as $hr): ?>
      <tr class="oms-history-row" title="Click to view full details">
        <td class="small text-nowrap text-muted text-start"><?php echo esc_view($hr['date']); ?></td>
        <td class="small">
          <span class="oms-history-preview"></span>
          <div class="oms-history-full d-none">
            <?php if (count($hr['parts']) > 1): ?>
              <ul class="mb-0 ps-3">
                <?php foreach ($hr['parts'] as $part): ?>
                  <?php if (trim($part) === '') { continue; } ?>
                  <li><?php echo esc_view(trim($part)); ?></li>
                <?php endforeach; ?>
              </ul>
            <?php else: ?>
              <?php echo nl2br(esc_view($hr['text'])); ?>
            <?php endif; ?>
          </div>
        </td>
        <td class="small fw-semibold"><?php echo esc_view($hr['who']); ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
</div>

<?php if (empty($GLOBALS['oms_history_assets_loaded'])): $GLOBALS['oms_history_assets_loaded'] = true; ?>
<style>
.oms-history-scroll { max-height: 55vh; overflow: auto; }
.oms-history-grid { width: 100% !important; }
.oms-history-grid thead th { position: sticky; top: 0; z-index: 1; background: #f8f9fa; }
.oms-history-col-date { width: 9.5rem; }
.oms-history-col-by { width: 9rem; }
.oms-history-row { cursor: pointer; }
.oms-history-row:hover { background: #f1f5f9; }
</style>
<div class="modal fade" id="omsHistoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header py-2 px-3">
        <div class="min-w-0">
          <h6 class="modal-title mb-1"><i class="bi bi-clock-history me-1 text-primary"></i>History Details</h6>
          <div class="d-flex flex-wrap gap-2">
            <span class="badge rounded-pill text-bg-light border fw-normal"><i class="bi bi-calendar3 me-1"></i><span id="omsHmDate"></span></span>
            <span class="badge rounded-pill text-bg-light border fw-normal"><i class="bi bi-person me-1"></i><span id="omsHmUser"></span></span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body px-3 py-3"><div id="omsHmBody"></div></div>
      <div class="modal-footer py-2 px-3"><button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button></div>
    </div>
  </div>
</div>
<script>
(function () {
  function notifyParent(open) {
    if (window.parent && window.parent !== window) {
      try {
        var pm = window.parent.document.getElementById('mwScreenPopup');
        if (pm) { pm.classList.toggle('mw-child-modal-open', open); }
      } catch (e) {}
      window.parent.postMessage({ type: 'mwChildModal', open: open }, '*');
    }
  }

  function bindHistory() {
    var hm = document.getElementById('omsHistoryModal');
    if (!hm) { return; }
    if (!hm.__bound) {
      hm.__bound = true;
      hm.addEventListener('show.bs.modal', function () { notifyParent(true); });
      hm.addEventListener('hidden.bs.modal', function () { notifyParent(false); });
    }
    document.querySelectorAll('.oms-history-row').forEach(function (r) {
      if (r.__bound) { return; }
      r.__bound = true;
      var full = r.querySelector('.oms-history-full');
      var prev = r.querySelector('.oms-history-preview');
      if (!full || !prev) { return; }
      var txt = (full.textContent || '').replace(/\s+/g, ' ').trim();
      prev.textContent = txt.length > 140 ? txt.slice(0, 140) + '…' : txt;
      r.addEventListener('click', function (e) {
        if (e.target.closest('a,button')) { return; }
        document.getElementById('omsHmDate').textContent = r.cells[0].textContent.trim();
        document.getElementById('omsHmUser').textContent = r.cells[2].textContent.trim();
        document.getElementById('omsHmBody').innerHTML = full.innerHTML;
        bootstrap.Modal.getOrCreateInstance(hm).show();
      });
    });
  }
  window.omsBindHistory = bindHistory;
  document.addEventListener('DOMContentLoaded', bindHistory);

  // "Save note" without a page redirect: post in the background, then refresh only the History pane.
  document.addEventListener('submit', function (e) {
    var form = e.target;
    var pane = form && form.closest ? form.closest('#history') : null;
    if (!pane || !pane.querySelector('.oms-history-host') || !form.querySelector('textarea[name="note"]')) { return; }
    e.preventDefault();
    var ta = form.querySelector('textarea[name="note"]');
    if (!ta.value.trim()) { ta.focus(); return; }
    var btn = form.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; }
    fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function (res) { return res.text(); })
      .then(function (html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var fresh = doc.getElementById('history');
        if (!fresh) { window.location.reload(); return; }
        var cur = document.getElementById('history');
        var oldModal = document.getElementById('omsHistoryModal');
        var keepModal = oldModal && cur.contains(oldModal) ? null : oldModal;
        cur.innerHTML = fresh.innerHTML;
        if (keepModal && cur.querySelector('#omsHistoryModal') && cur.querySelector('#omsHistoryModal') !== keepModal) {
          cur.querySelector('#omsHistoryModal').remove();
        }
        var freshBadge = doc.querySelector('#history-tab .badge');
        var curBadge = document.querySelector('#history-tab .badge');
        if (freshBadge && curBadge) { curBadge.textContent = freshBadge.textContent; }
        var f = cur.querySelector('form textarea[name="note"]');
        if (f) { f.value = ''; }
        bindHistory();
      })
      .catch(function () { window.location.reload(); });
  });
})();
</script>
<?php endif; ?>
