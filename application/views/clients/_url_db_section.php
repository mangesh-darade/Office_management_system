<?php
/**
 * Client URLs & DB — same section style as Basic / Address / Business.
 * Add more repeats the same field block.
 */
$password_mode = (isset($password_mode) && $password_mode === 'password') ? 'password' : 'text';
$existing_urls = (isset($existing_urls) && is_array($existing_urls)) ? $existing_urls : array();

$empty_url = function () {
  return (object) array('id' => 0, 'url' => '', 'version' => '1.0');
};

$by_type = array('website' => array(), 'demo' => array(), 'pos' => array());
foreach ($existing_urls as $eu) {
  if (!is_object($eu)) {
    continue;
  }
  $t = isset($eu->url_type) ? (string) $eu->url_type : '';
  if (isset($by_type[$t])) {
    $by_type[$t][] = $eu;
  }
}

$max = max(1, count($by_type['website']), count($by_type['demo']), count($by_type['pos']));
$sets = array();
for ($i = 0; $i < $max; $i++) {
  $w = isset($by_type['website'][$i]) ? $by_type['website'][$i] : $empty_url();
  $d = isset($by_type['demo'][$i]) ? $by_type['demo'][$i] : $empty_url();
  $p = isset($by_type['pos'][$i]) ? $by_type['pos'][$i] : $empty_url();
  $db_src = null;
  foreach (array($w, $d, $p) as $cand) {
    if (!empty($cand->db_name) || !empty($cand->db_username) || !empty($cand->db_host) || !empty($cand->db_password)) {
      $db_src = $cand;
      break;
    }
  }
  $sets[] = array(
    'website' => $w,
    'demo' => $d,
    'pos' => $p,
    'db_name' => $db_src && isset($db_src->db_name) ? (string) $db_src->db_name : '',
    'db_username' => $db_src && isset($db_src->db_username) ? (string) $db_src->db_username : '',
    'db_password' => $db_src && isset($db_src->db_password) ? (string) $db_src->db_password : '',
    'db_host' => $db_src && isset($db_src->db_host) ? (string) $db_src->db_host : '',
    'db_port' => $db_src && isset($db_src->db_port) ? (string) $db_src->db_port : '',
  );
}
?>
<section id="client-urls" class="oms-form-section">
  <div class="oms-form-section-title oms-form-section-title-actions">
    <span><i class="bi bi-link-45deg me-1"></i>Client URLs &amp; Database</span>
    <button type="button" class="btn btn-outline-primary btn-sm" id="js-ce-add-set">
      <i class="bi bi-plus-lg me-1"></i>Add more
    </button>
  </div>

  <div id="js-ce-sets">
    <?php foreach ($sets as $si => $set): ?>
    <div class="js-ce-set cl-url-block" data-index="<?php echo (int) $si; ?>">
      <?php if ((int) $si > 0): ?>
      <div class="cl-url-block-head">
        <span class="small text-muted fw-semibold js-ce-set-label">Set <?php echo (int) $si + 1; ?></span>
        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 js-ce-remove" title="Remove" aria-label="Remove">
          <i class="bi bi-trash"></i>
        </button>
      </div>
      <?php endif; ?>

      <div class="row g-2 oms-form-grid">
        <?php
        $url_cols = array(
          'website' => array('label' => 'Website URL', 'ph' => 'https://example.com'),
          'demo' => array('label' => 'Demo URL', 'ph' => 'https://demo.example.com'),
          'pos' => array('label' => 'POS URL', 'ph' => 'https://pos.example.com'),
        );
        foreach ($url_cols as $key => $meta):
          $eu = $set[$key];
          $eid = isset($eu->id) ? (int) $eu->id : 0;
          $uval = isset($eu->url) ? (string) $eu->url : '';
        ?>
        <div class="col-lg-4 col-md-6">
          <input type="hidden" name="client_sets[<?php echo (int) $si; ?>][<?php echo esc_view($key); ?>_id]" value="<?php echo $eid; ?>" class="js-ce-id-<?php echo esc_view($key); ?>">
          <label class="form-label"><?php echo esc_view($meta['label']); ?></label>
          <input type="url" name="client_sets[<?php echo (int) $si; ?>][<?php echo esc_view($key); ?>_url]" class="form-control js-ce-url-<?php echo esc_view($key); ?>" maxlength="500" placeholder="<?php echo esc_view($meta['ph']); ?>" value="<?php echo esc_view($uval); ?>">
        </div>
        <?php endforeach; ?>

        <div class="col-lg-3 col-md-6">
          <label class="form-label">DB Name</label>
          <input type="text" name="client_sets[<?php echo (int) $si; ?>][db_name]" class="form-control js-ce-db-name" value="<?php echo esc_view($set['db_name']); ?>" autocomplete="off">
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label">DB User</label>
          <input type="text" name="client_sets[<?php echo (int) $si; ?>][db_username]" class="form-control js-ce-db-user" value="<?php echo esc_view($set['db_username']); ?>" autocomplete="off">
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label">DB Password</label>
          <input type="<?php echo esc_view($password_mode); ?>" name="client_sets[<?php echo (int) $si; ?>][db_password]" class="form-control js-ce-db-pass" value="<?php echo esc_view($set['db_password']); ?>" autocomplete="off">
        </div>
        <div class="col-lg-2 col-md-4">
          <label class="form-label">DB Host</label>
          <input type="text" name="client_sets[<?php echo (int) $si; ?>][db_host]" class="form-control js-ce-db-host" placeholder="Remote host" value="<?php echo esc_view($set['db_host']); ?>" autocomplete="off">
        </div>
        <div class="col-lg-1 col-md-2">
          <label class="form-label">Port</label>
          <input type="text" name="client_sets[<?php echo (int) $si; ?>][db_port]" class="form-control js-ce-db-port" placeholder="3306" value="<?php echo esc_view($set['db_port']); ?>" autocomplete="off">
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<template id="js-ce-set-template">
  <div class="js-ce-set cl-url-block" data-index="__I__">
    <div class="cl-url-block-head">
      <span class="small text-muted fw-semibold js-ce-set-label">Set __N__</span>
      <button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 js-ce-remove" title="Remove" aria-label="Remove">
        <i class="bi bi-trash"></i>
      </button>
    </div>
    <div class="row g-2 oms-form-grid">
      <div class="col-lg-4 col-md-6">
        <input type="hidden" name="client_sets[__I__][website_id]" value="0" class="js-ce-id-website">
        <label class="form-label">Website URL</label>
        <input type="url" name="client_sets[__I__][website_url]" class="form-control js-ce-url-website" maxlength="500" placeholder="https://example.com" value="">
      </div>
      <div class="col-lg-4 col-md-6">
        <input type="hidden" name="client_sets[__I__][demo_id]" value="0" class="js-ce-id-demo">
        <label class="form-label">Demo URL</label>
        <input type="url" name="client_sets[__I__][demo_url]" class="form-control js-ce-url-demo" maxlength="500" placeholder="https://demo.example.com" value="">
      </div>
      <div class="col-lg-4 col-md-6">
        <input type="hidden" name="client_sets[__I__][pos_id]" value="0" class="js-ce-id-pos">
        <label class="form-label">POS URL</label>
        <input type="url" name="client_sets[__I__][pos_url]" class="form-control js-ce-url-pos" maxlength="500" placeholder="https://pos.example.com" value="">
      </div>
      <div class="col-lg-3 col-md-6">
        <label class="form-label">DB Name</label>
        <input type="text" name="client_sets[__I__][db_name]" class="form-control js-ce-db-name" value="" autocomplete="off">
      </div>
      <div class="col-lg-3 col-md-6">
        <label class="form-label">DB User</label>
        <input type="text" name="client_sets[__I__][db_username]" class="form-control js-ce-db-user" value="" autocomplete="off">
      </div>
      <div class="col-lg-3 col-md-6">
        <label class="form-label">DB Password</label>
        <input type="<?php echo esc_view($password_mode); ?>" name="client_sets[__I__][db_password]" class="form-control js-ce-db-pass" value="" autocomplete="off">
      </div>
      <div class="col-lg-2 col-md-4">
        <label class="form-label">DB Host</label>
        <input type="text" name="client_sets[__I__][db_host]" class="form-control js-ce-db-host" placeholder="Remote host" value="" autocomplete="off">
      </div>
      <div class="col-lg-1 col-md-2">
        <label class="form-label">Port</label>
        <input type="text" name="client_sets[__I__][db_port]" class="form-control js-ce-db-port" placeholder="3306" value="" autocomplete="off">
      </div>
    </div>
  </div>
</template>
<script>
(function () {
  var wrap = document.getElementById('js-ce-sets');
  var tpl = document.getElementById('js-ce-set-template');
  var addBtn = document.getElementById('js-ce-add-set');
  if (!wrap || !tpl || !addBtn) { return; }

  function reindex() {
    wrap.querySelectorAll('.js-ce-set').forEach(function (set, i) {
      set.setAttribute('data-index', String(i));
      var label = set.querySelector('.js-ce-set-label');
      if (label) { label.textContent = 'Set ' + (i + 1); }
      set.querySelectorAll('[name^="client_sets["]').forEach(function (el) {
        var name = el.getAttribute('name') || '';
        el.setAttribute('name', name.replace(/client_sets\[\d+]/, 'client_sets[' + i + ']'));
      });
    });
  }

  addBtn.addEventListener('click', function () {
    var i = wrap.querySelectorAll('.js-ce-set').length;
    var html = tpl.innerHTML.replace(/__I__/g, String(i)).replace(/__N__/g, String(i + 1));
    var div = document.createElement('div');
    div.innerHTML = html.trim();
    wrap.appendChild(div.firstElementChild);
    reindex();
  });

  wrap.addEventListener('click', function (e) {
    var btn = e.target.closest('.js-ce-remove');
    if (!btn) { return; }
    var set = btn.closest('.js-ce-set');
    if (!set || wrap.querySelectorAll('.js-ce-set').length <= 1) { return; }
    set.remove();
    reindex();
  });

  reindex();
})();
</script>
