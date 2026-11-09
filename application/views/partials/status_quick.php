<?php
/**
 * Quick status dropdown for detail screens (header). Posts to <url> and refreshes the Details/History panes.
 *
 * @var string $module_type   statuses type (requirements|defects|releases)
 * @var int    $item_id
 * @var string $current       current status code
 * @var string $url           update-status endpoint
 */
$module_type = isset($module_type) ? (string) $module_type : '';
$current = isset($current) ? (string) $current : '';
$item_id = isset($item_id) ? (int) $item_id : 0;
$url = isset($url) ? (string) $url : '';
if (!function_exists('module_status_records')) {
    get_instance()->load->helper('module_status');
}
$records = module_status_records($module_type);
$cur_color = '#64748b';
foreach ($records as $r) {
    if ((string) $r->code === $current && !empty($r->color)) {
        $cur_color = (string) $r->color;
    }
}
?>
<select class="form-select form-select-sm oms-status-select" data-url="<?php echo esc_view($url); ?>" data-id="<?php echo $item_id; ?>" data-current="<?php echo esc_view($current); ?>" aria-label="Update status" style="--st-c: <?php echo esc_view($cur_color); ?>;">
  <?php foreach ($records as $r): ?>
    <option value="<?php echo esc_view((string) $r->code); ?>" data-color="<?php echo esc_view(!empty($r->color) ? (string) $r->color : '#64748b'); ?>"<?php echo ((string) $r->code === $current) ? ' selected' : ''; ?>><?php echo esc_view((string) $r->name); ?></option>
  <?php endforeach; ?>
  <?php if ($current !== '' && !in_array($current, array_map(function ($r) { return (string) $r->code; }, $records), true)): ?>
    <option value="<?php echo esc_view($current); ?>" selected><?php echo esc_view(ucfirst(str_replace('_', ' ', $current))); ?></option>
  <?php endif; ?>
</select>
<?php if (empty($GLOBALS['oms_status_quick_loaded'])): $GLOBALS['oms_status_quick_loaded'] = true; ?>
<style>
.oms-status-select { width: auto; min-width: 8.5rem; font-weight: 600; color: var(--st-c, #475569); border: 1px solid var(--st-c, #94a3b8); border-radius: 6px; padding-top: 0.2rem; padding-bottom: 0.2rem; }
.oms-status-select:focus { box-shadow: 0 0 0 0.15rem rgba(15, 23, 42, 0.08); border-color: var(--st-c, #94a3b8); }
.oms-status-select:disabled { opacity: 0.6; }
</style>
<script>
(function () {
  var csrfName = <?php echo json_encode($this->security->get_csrf_token_name()); ?>;
  var csrfHash = <?php echo json_encode($this->security->get_csrf_hash()); ?>;

  function swap(doc, id) {
    var fresh = doc.getElementById(id), cur = document.getElementById(id);
    if (fresh && cur) { cur.innerHTML = fresh.innerHTML; }
  }

  document.addEventListener('change', function (e) {
    var sel = e.target;
    if (!sel || !sel.classList || !sel.classList.contains('oms-status-select')) { return; }
    var prev = sel.getAttribute('data-current') || '';
    sel.disabled = true;
    var body = new URLSearchParams();
    body.append('id', sel.getAttribute('data-id'));
    body.append('status', sel.value);
    body.append(csrfName, csrfHash);
    fetch(sel.getAttribute('data-url'), { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: body.toString(), credentials: 'same-origin' })
      .then(function (res) { return res.json().then(function (j) { return { ok: res.ok, j: j }; }); })
      .then(function (r) {
        if (!r.j || !r.j.ok) { throw new Error((r.j && r.j.error) || 'Update failed'); }
        sel.setAttribute('data-current', sel.value);
        var opt = sel.options[sel.selectedIndex];
        sel.style.setProperty('--st-c', (opt && opt.getAttribute('data-color')) || '#64748b');
        return fetch(window.location.href, { credentials: 'same-origin' }).then(function (res) { return res.text(); }).then(function (html) {
          var doc = new DOMParser().parseFromString(html, 'text/html');
          swap(doc, 'details');
          swap(doc, 'history');
          var fb = doc.querySelector('#history-tab .badge'), cb = document.querySelector('#history-tab .badge');
          if (fb && cb) { cb.textContent = fb.textContent; }
          if (window.omsBindHistory) { window.omsBindHistory(); }
        });
      })
      .catch(function (err) {
        sel.value = prev;
        var po = sel.options[sel.selectedIndex];
        sel.style.setProperty('--st-c', (po && po.getAttribute('data-color')) || '#64748b');
        window.alert(err.message || 'Update failed');
      })
      .finally(function () { sel.disabled = false; });
  });
})();
</script>
<?php endif; ?>
