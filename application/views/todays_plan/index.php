<?php
$active_tab = isset($active_tab) ? $active_tab : 'today';
$is_history = ($active_tab === 'history');
$is_today = ($plan_date === date('Y-m-d'));
$yesterday = date('Y-m-d', strtotime('-1 day'));
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$repeat_labels = isset($repeat_types) ? $repeat_types : todays_plan_repeat_types();
$count_total = is_array($items) ? count($items) : 0;
$count_done = 0;
$count_pending = 0;
if (!empty($items)) {
  foreach ($items as $_it) {
    $st = isset($_it->status) ? $_it->status : 'pending';
    if ($st === 'done') { $count_done++; } else { $count_pending++; }
  }
}
$progress_pct = $count_total > 0 ? (int) round(($count_done / $count_total) * 100) : 0;
$history_days = isset($history_days) ? $history_days : array();
$history_date = isset($history_date) ? $history_date : '';
$history_items = isset($history_items) ? $history_items : array();

$repeat_opts_html = '';
foreach ($repeat_labels as $rk => $rl) {
  $repeat_opts_html .= '<option value="' . htmlspecialchars($rk, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($rl, ENT_QUOTES, 'UTF-8') . '</option>';
}

$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();

$this->load->view('partials/header', array(
  'title' => "Today's Plan",
  'extra_css' => array('assets/css/todays-plan.css'),
));
?>
<div class="container-fluid py-2 tp-page">

  <div class="tp-top">
    <h1 class="tp-title"><i class="bi bi-calendar2-check"></i> Today's Plan</h1>
    <?php if (!$is_history): ?>
      <div class="tp-chips">
        <span class="tp-chip-stat"><span>Total</span> <b><?php echo (int) $count_total; ?></b></span>
        <span class="tp-chip-stat is-pending"><span>Pending</span> <b><?php echo (int) $count_pending; ?></b></span>
        <span class="tp-chip-stat is-done"><span>Done</span> <b><?php echo (int) $count_done; ?></b></span>
        <span class="tp-chip-stat"><span>Progress</span> <b><?php echo (int) $progress_pct; ?>%</b></span>
      </div>
    <?php endif; ?>
    <a href="<?php echo site_url('my-works'); ?>" class="btn btn-light btn-sm border py-0 px-2" style="font-size:0.75rem;">Second Brain</a>
  </div>

  <ul class="nav nav-tabs tp-tabs mb-2">
    <li class="nav-item">
      <a class="nav-link <?php echo !$is_history ? 'active' : ''; ?>" href="<?php echo site_url('todays-plan'); ?>">Today</a>
    </li>
    <li class="nav-item">
      <a class="nav-link <?php echo $is_history ? 'active' : ''; ?>" href="<?php echo site_url('todays-plan/history'); ?>">History</a>
    </li>
  </ul>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger py-1 px-2 mb-2 small"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success py-1 px-2 mb-2 small"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

<?php if ($is_history): ?>
  <!-- HISTORY TAB -->
  <div class="tp-panel">
    <div class="tp-hist-layout">
      <div class="tp-hist-days">
        <div class="tp-hist-days-title">Past days</div>
        <?php if (empty($history_days)): ?>
          <div class="tp-empty py-3">No history yet.</div>
        <?php else: ?>
          <?php foreach ($history_days as $day): ?>
            <?php
              $d = isset($day->plan_date) ? $day->plan_date : '';
              $cnt = isset($day->item_count) ? (int) $day->item_count : 0;
              $done = isset($day->done_count) ? (int) $day->done_count : 0;
              $is_sel = ($history_date !== '' && $history_date === $d);
            ?>
            <a class="tp-hist-day <?php echo $is_sel ? 'active' : ''; ?>" href="<?php echo site_url('todays-plan/history?date=' . urlencode($d)); ?>">
              <span class="tp-hist-day-date"><?php echo esc_view(date('D, M j', strtotime($d))); ?></span>
              <span class="tp-hist-day-meta"><?php echo $done; ?>/<?php echo $cnt; ?> done</span>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="tp-hist-detail">
        <?php if ($history_date === ''): ?>
          <div class="tp-empty">Select a day to view plan history.</div>
        <?php else: ?>
          <div class="tp-hist-detail-head">
            <strong><?php echo esc_view(date('l, M j, Y', strtotime($history_date))); ?></strong>
            <span class="badge text-bg-light border"><?php echo count($history_items); ?> items</span>
            <a class="btn btn-outline-secondary btn-sm py-0 ms-auto" href="<?php echo site_url('todays-plan?date=' . urlencode($history_date)); ?>">Open day</a>
          </div>

          <div class="card-body p-2">
            <div class="table-responsive">
              <table id="todaysPlanHistoryTable" class="table table-hover table-sm align-middle mb-0 w-100" data-dt-manual="1" data-order-col="0" data-order-dir="asc" data-order-disable-cols="3">
                <thead>
                  <tr>
                    <th>Time</th>
                    <th>Title / Description</th>
                    <th>Type</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($history_items as $item): ?>
                    <?php
                      $time_label = substr((string) $item->plan_time, 0, 5);
                      $status = isset($item->status) ? $item->status : 'pending';
                      $repeat = isset($item->repeat_type) ? $item->repeat_type : 'once';
                      $repeat_label = isset($repeat_labels[$repeat]) ? $repeat_labels[$repeat] : ucfirst($repeat);
                      $details = isset($item->details) ? (string) $item->details : '';
                      $badge = $status === 'done' ? 'success' : ($status === 'skipped' ? 'warning text-dark' : 'secondary');
                    ?>
                    <tr class="<?php echo $status === 'done' ? 'is-done' : ''; ?>">
                      <td data-order="<?php echo esc_view($time_label, ENT_QUOTES, 'UTF-8'); ?>"><span class="tp-t-time"><?php echo esc_view($time_label); ?></span></td>
                      <td>
                        <div class="tp-t-title"><?php echo esc_view($item->title); ?></div>
                        <?php if ($details !== ''): ?>
                          <div class="tp-t-desc"><?php echo esc_view($details); ?></div>
                        <?php endif; ?>
                      </td>
                      <td><span class="badge text-bg-light border tp-badge"><?php echo esc_view($repeat_label); ?></span></td>
                      <td><span class="badge bg-<?php echo esc_view($badge); ?> tp-badge"><?php echo esc_view(isset($status_labels[$status]) ? $status_labels[$status] : $status); ?></span></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
          <script>
          (function () {
            var tbl = document.getElementById('todaysPlanHistoryTable');
            if (tbl && window.DataTable) {
              new DataTable(tbl, {
                paging: true,
                pageLength: 10,
                searching: true,
                lengthChange: true,
                info: true,
                ordering: true,
                order: [[0, 'asc']],
                columnDefs: [{ orderable: false, targets: [3] }],
                language: { emptyTable: 'No history items', zeroRecords: 'No matching records' }
              });
            }
          })();
          </script>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- TODAY TAB -->
  <form method="get" action="<?php echo site_url('todays-plan'); ?>" class="tp-toolbar">
    <input type="date" name="date" class="form-control form-control-sm tp-date-input" value="<?php echo esc_view($plan_date, ENT_QUOTES, 'UTF-8'); ?>">
    <button type="submit" class="btn btn-sm btn-outline-secondary">Go</button>
    <div class="tp-date-quick">
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('todays-plan?date=' . urlencode($yesterday)); ?>">Yest</a>
      <a class="btn btn-sm <?php echo $is_today ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo site_url('todays-plan'); ?>">Today</a>
      <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('todays-plan?date=' . urlencode($tomorrow)); ?>">Tom</a>
    </div>
  </form>

  <div class="tp-panel">
    <div class="card-body p-2">
      <div class="table-responsive">
        <table id="todaysPlanTable" class="table table-hover table-sm align-middle mb-0 w-100" data-dt-manual="1" data-order-col="0" data-order-dir="asc" data-order-disable-cols="4">
          <thead>
            <tr>
              <th>Time</th>
              <th>Title / Description</th>
              <th>Type</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($items)): ?>
              <?php foreach ($items as $item): ?>
                <?php
                  $time_label = substr((string) $item->plan_time, 0, 5);
                  $status = isset($item->status) ? $item->status : 'pending';
                  $repeat = isset($item->repeat_type) ? $item->repeat_type : 'once';
                  $repeat_label = isset($repeat_labels[$repeat]) ? $repeat_labels[$repeat] : ucfirst($repeat);
                  $details = isset($item->details) ? (string) $item->details : '';
                  $badge = $status === 'done' ? 'success' : ($status === 'skipped' ? 'warning text-dark' : 'secondary');
                ?>
                <tr class="<?php echo $status === 'done' ? 'is-done' : ''; ?>">
                  <td data-order="<?php echo esc_view($time_label, ENT_QUOTES, 'UTF-8'); ?>"><span class="tp-t-time"><?php echo esc_view($time_label); ?></span></td>
                  <td>
                    <div class="tp-t-title"><?php echo esc_view($item->title); ?></div>
                    <?php if ($details !== ''): ?>
                      <div class="tp-t-desc" data-desc>
                        <span class="tp-desc-text"><?php echo esc_view($details); ?></span>
                        <button type="button" class="tp-desc-more d-none" data-desc-more>more</button>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge text-bg-light border tp-badge"><?php echo esc_view($repeat_label); ?></span></td>
                  <td><span class="badge bg-<?php echo esc_view($badge); ?> tp-badge"><?php echo esc_view(isset($status_labels[$status]) ? $status_labels[$status] : $status); ?></span></td>
                  <td>
                    <div class="tp-act">
                      <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tpEditModal"
                        data-id="<?php echo (int) $item->id; ?>"
                        data-time="<?php echo esc_view($time_label, ENT_QUOTES, 'UTF-8'); ?>"
                        data-title="<?php echo esc_view($item->title, ENT_QUOTES, 'UTF-8'); ?>"
                        data-details="<?php echo esc_view($details, ENT_QUOTES, 'UTF-8'); ?>"
                        data-repeat="<?php echo esc_view($repeat, ENT_QUOTES, 'UTF-8'); ?>"
                        data-date="<?php echo esc_view($item->plan_date, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-pencil"></i></button>
                      <?php if ($status !== 'done'): ?>
                        <form method="post" action="<?php echo site_url('todays-plan/toggle'); ?>" class="d-inline">
                          <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
                          <input type="hidden" name="id" value="<?php echo (int) $item->id; ?>">
                          <input type="hidden" name="status" value="done">
                          <button type="submit" class="btn btn-outline-success btn-sm"><i class="bi bi-check-lg"></i></button>
                        </form>
                      <?php endif; ?>
                      <form method="post" action="<?php echo site_url('todays-plan/delete/' . (int) $item->id); ?>" class="d-inline" onsubmit="return confirm('Delete?');">
                        <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <form method="post" action="<?php echo site_url('todays-plan/save'); ?>" id="tpForm">
      <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="plan_date" value="<?php echo esc_view($plan_date, ENT_QUOTES, 'UTF-8'); ?>">

      <div class="tp-new-wrap">
        <div class="tp-new-head">
          <span>Add plan</span>
          <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" id="tpAddRow" style="font-size:0.72rem;"><i class="bi bi-plus"></i> Row</button>
        </div>
        <div id="tpNewBody"></div>
      </div>

      <div class="tp-footer">
        <div class="form-check mb-0">
          <input class="form-check-input" type="checkbox" name="sync_google" value="1" id="syncGoogle" checked>
          <label class="form-check-label" for="syncGoogle">Google alert</label>
        </div>
        <button type="submit" class="btn btn-primary btn-sm ms-auto"><i class="bi bi-check2"></i> Save</button>
      </div>
    </form>
  </div>

  <div class="modal fade" id="tpEditModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <form method="post" action="" id="tpEditForm" class="modal-content">
        <input type="hidden" name="<?php echo esc_view($csrf_name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo esc_view($csrf_hash, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="plan_date" id="editPlanDate" value="">
        <div class="modal-header py-2">
          <h6 class="modal-title mb-0">Edit plan</h6>
          <button type="button" class="btn-close btn-sm" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body py-2">
          <div class="row g-2">
            <div class="col-4">
              <label class="tp-lbl">Time</label>
              <input type="time" name="plan_time" id="editTime" class="form-control form-control-sm" required>
            </div>
            <div class="col-8">
              <label class="tp-lbl">Type</label>
              <select name="repeat_type" id="editRepeat" class="form-select form-select-sm"><?php echo $repeat_opts_html; ?></select>
            </div>
            <div class="col-12">
              <label class="tp-lbl">Title</label>
              <input type="text" name="title" id="editTitle" class="form-control form-control-sm" maxlength="255" required>
            </div>
            <div class="col-12">
              <label class="tp-lbl">Description</label>
              <textarea name="details" id="editDetails" class="form-control form-control-sm tp-desc-input" rows="4"></textarea>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="sync_google" value="1" id="editSyncGoogle">
                <label class="form-check-label small" for="editSyncGoogle">Google alert</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Update</button>
        </div>
      </form>
    </div>
  </div>

  <template id="tpRowTpl">
    <div class="tp-new-row" data-new-row>
      <div class="tp-new-row-head">
        <span class="tp-new-num" data-row-num>1</span>
        <button type="button" class="btn btn-link text-danger" data-remove-row>×</button>
      </div>
      <div class="row g-1">
        <div class="col-4 col-md-2">
          <label class="tp-lbl">Time</label>
          <input type="time" name="plan_time[]" class="form-control form-control-sm" required>
        </div>
        <div class="col-4 col-md-2">
          <label class="tp-lbl">Type</label>
          <select name="repeat_type[]" class="form-select form-select-sm"><?php echo $repeat_opts_html; ?></select>
        </div>
        <div class="col-12 col-md-8">
          <label class="tp-lbl">Title</label>
          <input type="text" name="title[]" class="form-control form-control-sm" maxlength="255" required placeholder="Title">
        </div>
        <div class="col-12">
          <label class="tp-lbl">Description</label>
          <textarea name="details[]" class="form-control form-control-sm tp-desc-input" rows="2" placeholder="Description (expand if long)"></textarea>
        </div>
      </div>
    </div>
  </template>

  <script>
  (function () {
    var updateBase = <?php echo json_encode(site_url('todays-plan/update')); ?>;
    var body = document.getElementById('tpNewBody');
    var tpl = document.getElementById('tpRowTpl');
    var addBtn = document.getElementById('tpAddRow');
    var editModal = document.getElementById('tpEditModal');
    var editForm = document.getElementById('tpEditForm');

    function renumber() {
      if (!body) { return; }
      body.querySelectorAll('[data-new-row]').forEach(function (row, idx) {
        var num = row.querySelector('[data-row-num]');
        if (num) { num.textContent = String(idx + 1); }
        var rem = row.querySelector('[data-remove-row]');
        if (rem) { rem.style.visibility = body.querySelectorAll('[data-new-row]').length > 1 ? 'visible' : 'hidden'; }
      });
    }

    function clearInputs(row) {
      row.querySelectorAll('input, textarea, select').forEach(function (el) {
        if (el.tagName === 'SELECT') { el.value = 'once'; }
        else { el.value = ''; }
      });
    }

    function bindRow(row) {
      var removeBtn = row.querySelector('[data-remove-row]');
      if (removeBtn) {
        removeBtn.addEventListener('click', function () {
          if (body.querySelectorAll('[data-new-row]').length <= 1) {
            clearInputs(row);
            return;
          }
          row.remove();
          renumber();
        });
      }
    }

    function addRow(focusTitle) {
      if (!body || !tpl) { return; }
      body.appendChild(tpl.content.cloneNode(true));
      bindRow(body.lastElementChild);
      renumber();
      if (focusTitle) {
        var title = body.lastElementChild.querySelector('input[name="title[]"]');
        if (title) { title.focus(); }
      }
    }

    if (addBtn) { addBtn.addEventListener('click', function () { addRow(true); }); }

    if (editModal && editForm) {
      editModal.addEventListener('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (!btn) { return; }
        editForm.action = updateBase + '/' + btn.getAttribute('data-id');
        document.getElementById('editTime').value = btn.getAttribute('data-time') || '';
        document.getElementById('editTitle').value = btn.getAttribute('data-title') || '';
        document.getElementById('editDetails').value = btn.getAttribute('data-details') || '';
        document.getElementById('editRepeat').value = btn.getAttribute('data-repeat') || 'once';
        document.getElementById('editPlanDate').value = btn.getAttribute('data-date') || '';
        document.getElementById('editSyncGoogle').checked = false;
      });
    }

    document.querySelectorAll('[data-desc]').forEach(function (wrap) {
      var text = wrap.querySelector('.tp-desc-text');
      var more = wrap.querySelector('[data-desc-more]');
      if (!text || !more) { return; }
      if (text.scrollHeight > 36) {
        wrap.classList.add('is-clamped');
        more.classList.remove('d-none');
        more.addEventListener('click', function () {
          var open = wrap.classList.toggle('is-open');
          more.textContent = open ? 'less' : 'more';
        });
      }
    });

    var planTbl = document.getElementById('todaysPlanTable');
    if (planTbl && window.DataTable) {
      new DataTable(planTbl, {
        paging: true,
        pageLength: 10,
        searching: true,
        lengthChange: true,
        info: true,
        ordering: true,
        order: [[0, 'asc']],
        columnDefs: [{ orderable: false, targets: [4] }],
        language: { emptyTable: 'No plan points. Add below.', zeroRecords: 'No matching records' }
      });
    }

    addRow(false);
  })();
  </script>
<?php endif; ?>

</div>
<?php $this->load->view('partials/footer'); ?>
