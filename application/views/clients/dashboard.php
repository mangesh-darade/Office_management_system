<?php
$embed = !empty($embed);

if (!$embed) {
    $this->load->view('partials/header', array(
        'title' => 'Clients Dashboard',
        'extra_css' => array('assets/css/clients.css', 'assets/css/project-dashboard.css'),
    ));
}

$filters = isset($filters) && is_array($filters) ? $filters : array();
$filter_status = isset($filters['status']) ? (string) $filters['status'] : '';
$filter_type = isset($filters['client_type']) ? (string) $filters['client_type'] : '';
$filter_q = isset($filters['search']) ? (string) $filters['search'] : '';
$client_types = isset($client_types) && is_array($client_types) ? $client_types : array();

if (!function_exists('module_status_records')) {
    $this->load->helper('module_status');
}
$client_status_rows = module_status_records('clients');
?>

<div class="container-fluid project-dash-compact client-dash-page">
  <?php if (!$embed): ?>
  <?php
  $this->load->view('partials/oms_page_head', array(
      'title'        => 'Clients',
      'icon'         => 'bi bi-building',
      'subtitle'     => 'Alphabetical client cards · click a project for tasks',
      'actions_html' => '<a class="btn btn-outline-secondary btn-sm" href="' . site_url('clients') . '"><i class="bi bi-list me-1"></i>All Clients</a>',
      'mb'           => 'mb-0',
  ));
  ?>
  <?php endif; ?>

  <div class="project-dash-toolbar">
    <form method="get" action="<?php echo site_url('clients/dashboard'); ?>" class="project-dash-filter-form" id="clientDashFilterForm">
      <?php if ($embed): ?>
      <input type="hidden" name="embed" value="1">
      <?php endif; ?>
      <?php if ($this->input->get('parent_tab')): ?>
      <input type="hidden" name="parent_tab" value="<?php echo esc_view($this->input->get('parent_tab'), ENT_QUOTES, 'UTF-8'); ?>">
      <?php endif; ?>

      <label class="project-dash-filter-label">
        <span class="project-dash-filter-label-text">Status</span>
        <select name="status" class="form-select form-select-sm project-dash-filter-select">
          <option value=""<?php echo $filter_status === '' ? ' selected' : ''; ?>>All statuses</option>
          <?php foreach ($client_status_rows as $sr): ?>
            <option value="<?php echo esc_view($sr->code, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filter_status === (string) $sr->code ? ' selected' : ''; ?>>
              <?php echo esc_view($sr->name, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <?php if (!empty($client_types)): ?>
      <label class="project-dash-filter-label">
        <span class="project-dash-filter-label-text">Type</span>
        <select name="client_type" class="form-select form-select-sm project-dash-filter-select">
          <option value=""<?php echo $filter_type === '' ? ' selected' : ''; ?>>All types</option>
          <?php foreach ($client_types as $code => $label): ?>
            <option value="<?php echo esc_view($code, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filter_type === (string) $code ? ' selected' : ''; ?>>
              <?php echo esc_view($label, ENT_QUOTES, 'UTF-8'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php endif; ?>

      <label class="project-dash-filter-label project-dash-filter-search">
        <span class="project-dash-filter-label-text">Search</span>
        <input type="search" id="clientDashSearch" name="q" value="<?php echo esc_view($filter_q, ENT_QUOTES, 'UTF-8'); ?>"
               class="form-control form-control-sm project-dash-filter-select"
               placeholder="Client, contact, phone…"
               autocomplete="off">
      </label>
    </form>
  </div>

  <?php $this->load->view('clients/_cart_body'); ?>
</div>

<script>
(function () {
  var form = document.getElementById('clientDashFilterForm');
  if (!form) {
    return;
  }
  form.querySelectorAll('select.project-dash-filter-select').forEach(function (select) {
    select.addEventListener('change', function () {
      form.submit();
    });
  });

  var searchInput = document.getElementById('clientDashSearch');
  var grid = document.querySelector('.client-dash-grid');
  function applyClientSearch() {
    if (!searchInput || !grid) {
      return;
    }
    var q = String(searchInput.value || '').trim().toLowerCase();
    grid.querySelectorAll('.project-dash-grid-col').forEach(function (col) {
      var hay = String(col.getAttribute('data-client-search') || col.textContent || '').toLowerCase();
      col.style.display = (q === '' || hay.indexOf(q) >= 0) ? '' : 'none';
    });
  }
  if (searchInput) {
    searchInput.addEventListener('input', applyClientSearch);
    searchInput.addEventListener('search', applyClientSearch);
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
      }
    });
    applyClientSearch();
  }
})();
</script>

<?php if (!$embed) {
    $this->load->view('partials/footer');
} ?>
