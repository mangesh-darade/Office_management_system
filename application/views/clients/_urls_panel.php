<?php
/**
 * Client URLs tab — client cards with env sets (Website + Demo + POS + DB).
 */
$url_rows = (isset($url_rows) && is_array($url_rows)) ? $url_rows : array();
$clients_list = (isset($clients_list) && is_array($clients_list)) ? $clients_list : array();
$url_filters = (isset($url_filters) && is_array($url_filters)) ? $url_filters : array();
$url_types = (isset($url_types) && is_array($url_types)) ? $url_types : array();
$url_versions = (isset($url_versions) && is_array($url_versions)) ? $url_versions : array();
$embed = !empty($embed) || (bool) $this->input->get('embed');
$parent_tab = isset($parent_tab) ? trim((string) $parent_tab) : trim((string) $this->input->get('parent_tab'));
$filter_client_id = isset($url_filters['client_id']) ? (int) $url_filters['client_id'] : 0;
$filter_url_type = isset($url_filters['url_type']) ? (string) $url_filters['url_type'] : '';
$filter_version = isset($url_filters['version']) ? (string) $url_filters['version'] : '';
$filter_q = isset($url_filters['q']) ? (string) $url_filters['q'] : '';
$has_filters = ($filter_client_id > 0 || $filter_url_type !== '' || $filter_version !== '' || $filter_q !== '');
$urls_clear_href = site_url('clients?tab=urls' . ($embed ? ('&embed=1' . ($parent_tab !== '' ? '&parent_tab=' . rawurlencode($parent_tab) : '')) : ''));

$can_edit_url = function_exists('has_module_access') && (
    has_module_access('clients_edit') || has_module_access('clients')
);
$can_delete_url = function_exists('has_module_access') && (
    has_module_access('clients_delete') || has_module_access('clients')
);
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();

$grouped = array();
foreach ($url_rows as $r) {
  $cid = (int) (isset($r->client_id) ? $r->client_id : 0);
  if ($cid < 1) {
    continue;
  }
  if (!isset($grouped[$cid])) {
    $grouped[$cid] = array(
      'client_id' => $cid,
      'company_name' => isset($r->company_name) ? $r->company_name : ('#' . $cid),
      'client_code' => isset($r->client_code) ? $r->client_code : '',
      'envs' => array(),
    );
  }
  $ver = isset($r->version) && trim((string) $r->version) !== '' ? trim((string) $r->version) : '1.0';
  if (!isset($grouped[$cid]['envs'][$ver])) {
    $grouped[$cid]['envs'][$ver] = array(
      'version' => $ver,
      'website' => null,
      'demo' => null,
      'pos' => null,
      'other' => array(),
      'ids' => array(),
      'db_name' => '',
      'db_username' => '',
      'db_host' => '',
      'db_port' => '',
    );
  }
  $type = isset($r->url_type) ? (string) $r->url_type : 'other';
  $rid = isset($r->id) ? (int) $r->id : 0;
  if ($rid > 0) {
    $grouped[$cid]['envs'][$ver]['ids'][] = $rid;
  }
  if ($type === 'website' || $type === 'demo' || $type === 'pos') {
    if ($grouped[$cid]['envs'][$ver][$type] === null) {
      $grouped[$cid]['envs'][$ver][$type] = $r;
    }
  } else {
    $grouped[$cid]['envs'][$ver]['other'][] = $r;
  }
  if ($grouped[$cid]['envs'][$ver]['db_name'] === '' && $grouped[$cid]['envs'][$ver]['db_host'] === '') {
    if (!empty($r->db_name) || !empty($r->db_username) || !empty($r->db_host)) {
      $grouped[$cid]['envs'][$ver]['db_name'] = isset($r->db_name) ? (string) $r->db_name : '';
      $grouped[$cid]['envs'][$ver]['db_username'] = isset($r->db_username) ? (string) $r->db_username : '';
      $grouped[$cid]['envs'][$ver]['db_host'] = isset($r->db_host) ? (string) $r->db_host : '';
      $grouped[$cid]['envs'][$ver]['db_port'] = isset($r->db_port) ? (string) $r->db_port : '';
    }
  }
}

$total_envs = 0;
foreach ($grouped as $g) {
  $total_envs += count($g['envs']);
}

$link_cell = function ($row, $label, $badge) {
  echo '<span class="cl-url-env-item">';
  echo '<span class="cl-url-type ' . esc_view($badge) . '">' . esc_view($label) . '</span>';
  if (!$row || empty($row->url)) {
    echo '<span class="text-muted">—</span>';
  } else {
    echo '<a href="' . esc_view($row->url) . '" target="_blank" rel="noopener" title="' . esc_view($row->url) . '">' . esc_view($row->url) . '</a>';
  }
  echo '</span>';
};
?>
<div class="cl-urls">
  <form class="cl-urls-toolbar" method="get" action="<?php echo site_url('clients'); ?>">
    <input type="hidden" name="tab" value="urls">
    <?php if ($embed): ?>
    <input type="hidden" name="embed" value="1">
    <?php if ($parent_tab !== ''): ?>
    <input type="hidden" name="parent_tab" value="<?php echo esc_view($parent_tab, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php endif; ?>
    <div class="cl-urls-filter">
      <select class="form-select form-select-sm" name="client_id" id="filter_client_id" aria-label="Client" onchange="this.form.submit()">
        <option value="">All clients</option>
        <?php foreach ($clients_list as $c): ?>
        <option value="<?php echo (int) $c->id; ?>" <?php echo ($filter_client_id === (int) $c->id) ? 'selected' : ''; ?>>
          <?php echo esc_view($c->company_name); ?><?php if (!empty($c->client_code)): ?> (<?php echo esc_view($c->client_code); ?>)<?php endif; ?>
        </option>
        <?php endforeach; ?>
      </select>
      <select class="form-select form-select-sm" name="url_type" id="filter_url_type" aria-label="Type" onchange="this.form.submit()">
        <option value="">All types</option>
        <?php foreach ($url_types as $code => $label): ?>
        <option value="<?php echo esc_view($code); ?>" <?php echo ($filter_url_type === $code) ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
        <?php endforeach; ?>
      </select>
      <select class="form-select form-select-sm" name="version" id="filter_version" aria-label="Version" onchange="this.form.submit()">
        <option value="">All versions</option>
        <?php foreach ($url_versions as $ver): ?>
        <option value="<?php echo esc_view($ver); ?>" <?php echo ($filter_version === $ver) ? 'selected' : ''; ?>><?php echo esc_view($ver); ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($has_filters): ?>
      <a class="btn btn-light btn-sm" href="<?php echo esc_view($urls_clear_href, ENT_QUOTES, 'UTF-8'); ?>">Clear</a>
      <?php endif; ?>
    </div>
    <div class="cl-urls-search">
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="search" class="form-control" name="q" value="<?php echo esc_view($filter_q); ?>" placeholder="Search client, URL, DB…" aria-label="Search">
        <button type="submit" class="btn btn-outline-secondary">Go</button>
      </div>
      <div class="cl-urls-meta text-muted small d-none d-md-block">
        <?php echo (int) count($grouped); ?> client<?php echo count($grouped) === 1 ? '' : 's'; ?>
        · <?php echo (int) $total_envs; ?> set<?php echo $total_envs === 1 ? '' : 's'; ?>
      </div>
    </div>
  </form>

  <?php if (empty($grouped)): ?>
  <div class="cl-urls-empty">
    <i class="bi bi-link-45deg"></i>
    <p class="mb-1"><?php echo $has_filters ? 'No matching URL / DB sets.' : 'No URL / DB sets yet.'; ?></p>
    <p class="small text-muted mb-0">
      <?php if ($has_filters): ?>
      Try clearing filters or a different search.
      <?php else: ?>
      Use <strong>Add Client</strong> and add sets in URLs &amp; Database.
      <?php endif; ?>
    </p>
  </div>
  <?php else: foreach ($grouped as $g):
    $cid = (int) $g['client_id'];
    $env_count = count($g['envs']);
  ?>
  <article class="cl-url-client">
    <header class="cl-url-client-head">
      <div class="cl-url-client-title">
        <a href="<?php echo site_url('clients/view/' . $cid); ?>"><?php echo esc_view($g['company_name']); ?></a>
        <?php if ($g['client_code'] !== '' && $g['client_code'] !== null): ?>
        <span class="cl-url-code"><?php echo esc_view($g['client_code']); ?></span>
        <?php endif; ?>
        <span class="cl-url-count"><?php echo (int) $env_count; ?> set<?php echo $env_count === 1 ? '' : 's'; ?></span>
      </div>
      <div class="btn-group btn-group-sm" role="group" aria-label="Client actions">
        <a href="<?php echo site_url('clients/view/' . $cid); ?>" class="btn btn-outline-secondary" title="View" aria-label="View"><i class="bi bi-eye"></i></a>
        <?php if ($can_edit_url): ?>
        <a href="<?php echo site_url('clients/edit/' . $cid . '#client-urls'); ?>" class="btn btn-outline-primary" title="Edit URLs" aria-label="Edit"><i class="bi bi-pencil"></i></a>
        <?php endif; ?>
      </div>
    </header>

    <div class="cl-url-envs">
      <?php foreach ($g['envs'] as $env):
        $ids = isset($env['ids']) ? $env['ids'] : array();
        $has_db = ($env['db_name'] !== '' || $env['db_username'] !== '' || $env['db_host'] !== '');
        $form_id = 'cu-del-set-' . $cid . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $env['version']);
      ?>
      <div class="cl-url-env">
        <div class="cl-url-env-row">
          <span class="cl-url-ver-badge">v<?php echo esc_view($env['version']); ?></span>
          <div class="cl-url-env-links">
            <?php
            $link_cell($env['website'], 'Web', 'cl-url-type-website');
            $link_cell($env['demo'], 'Demo', 'cl-url-type-demo');
            $link_cell($env['pos'], 'POS', 'cl-url-type-pos');
            ?>
          </div>
          <?php if ($has_db): ?>
          <div class="cl-url-db" title="Database">
            <i class="bi bi-database"></i>
            <span><?php echo esc_view($env['db_name'] !== '' ? $env['db_name'] : '—'); ?></span>
            <?php if ($env['db_username'] !== ''): ?><span class="text-muted">/</span><span><?php echo esc_view($env['db_username']); ?></span><?php endif; ?>
            <?php if ($env['db_host'] !== ''): ?><span class="text-muted">@</span><span><?php echo esc_view($env['db_host']); ?><?php if ($env['db_port'] !== ''): ?>:<?php echo esc_view($env['db_port']); ?><?php endif; ?></span><?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if ($can_delete_url && !empty($ids)): ?>
          <button type="button" class="btn btn-outline-danger btn-sm cl-url-env-del" title="Delete this set" aria-label="Delete set"
            onclick="if(confirm('Delete this URL / DB set?')){ document.getElementById('<?php echo esc_view($form_id); ?>').submit(); }">
            <i class="bi bi-trash"></i>
          </button>
          <form id="<?php echo esc_view($form_id); ?>" method="post" action="<?php echo site_url('clients/urls/delete-set'); ?>" class="d-none">
            <input type="hidden" name="<?php echo esc_view($csrf_name); ?>" value="<?php echo esc_view($csrf_hash); ?>">
            <input type="hidden" name="client_id" value="<?php echo $cid; ?>">
            <?php foreach ($ids as $del_id): ?>
            <input type="hidden" name="ids[]" value="<?php echo (int) $del_id; ?>">
            <?php endforeach; ?>
          </form>
          <?php endif; ?>
        </div>
        <?php if (!empty($env['other'])): ?>
        <div class="cl-url-env-other">
          <?php foreach ($env['other'] as $o): ?>
          <span class="cl-url-env-item">
            <span class="cl-url-type cl-url-type-other">Other</span>
            <?php if (!empty($o->url)): ?>
            <a href="<?php echo esc_view($o->url); ?>" target="_blank" rel="noopener"><?php echo esc_view($o->url); ?></a>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </article>
  <?php endforeach; endif; ?>
</div>
