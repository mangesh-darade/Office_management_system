<?php
$filters = isset($filters) && is_array($filters) ? $filters : array();
$filter_status = isset($filters['status']) ? (string) $filters['status'] : '';
$filter_type = isset($filters['client_type']) ? (string) $filters['client_type'] : '';
$filter_q = isset($filters['search']) ? (string) $filters['search'] : '';
$filter_project_id = isset($filter_project_id) ? (int) $filter_project_id : (isset($filters['project_id']) ? (int) $filters['project_id'] : 0);
$filter_projects = isset($filter_projects) && is_array($filter_projects) ? $filter_projects : array();
$client_types = isset($client_types) && is_array($client_types) ? $client_types : array();
$embed = !empty($embed) || (bool) $this->input->get('embed');
$form_action = isset($form_action) ? (string) $form_action : site_url('clients/dashboard');
$cart_tab = !empty($cart_tab);
$filters_on = ($filter_status !== '' || $filter_type !== '' || $filter_q !== '' || $filter_project_id > 0);

if (!function_exists('module_status_records')) {
    $this->load->helper('module_status');
}
$client_status_rows = module_status_records('clients');
?>

<div class="project-dash-toolbar">
  <form method="get" action="<?php echo esc_view($form_action, ENT_QUOTES, 'UTF-8'); ?>" class="project-dash-filter-form" id="clientDashFilterForm">
    <?php if ($embed): ?>
    <input type="hidden" name="embed" value="1">
    <?php endif; ?>
    <?php if ($this->input->get('parent_tab')): ?>
    <input type="hidden" name="parent_tab" value="<?php echo esc_view($this->input->get('parent_tab'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>
    <?php if ($cart_tab): ?>
    <input type="hidden" name="tab" value="cart">
    <?php endif; ?>

    <?php if (!empty($filter_projects)): ?>
    <label class="project-dash-filter-label">
      <span class="project-dash-filter-label-text">Project</span>
      <select name="project_id" class="form-select form-select-sm project-dash-filter-select">
        <option value="0"<?php echo $filter_project_id < 1 ? ' selected' : ''; ?>>All Projects</option>
        <?php foreach ($filter_projects as $fp): ?>
          <?php
            $fp_id = (int) $fp->id;
            $fp_name = isset($fp->name) ? trim((string) $fp->name) : '';
            $fp_label = $fp_name !== '' ? $fp_name : ('Project #' . $fp_id);
          ?>
          <option value="<?php echo $fp_id; ?>"<?php echo $filter_project_id === $fp_id ? ' selected' : ''; ?>>
            <?php echo esc_view($fp_label, ENT_QUOTES, 'UTF-8'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>
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

    <?php if ($filters_on): ?>
    <?php
      $reset_params = array();
      if ($embed) {
          $reset_params['embed'] = 1;
      }
      if ($this->input->get('parent_tab')) {
          $reset_params['parent_tab'] = $this->input->get('parent_tab');
      }
      if ($cart_tab) {
          $reset_params['tab'] = 'cart';
      }
      $reset_url = $form_action . (!empty($reset_params) ? ('?' . http_build_query($reset_params)) : '');
    ?>
    <a class="btn btn-outline-secondary btn-sm align-self-end mb-1" href="<?php echo esc_view($reset_url, ENT_QUOTES, 'UTF-8'); ?>">Reset</a>
    <?php endif; ?>
  </form>
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
  var searchTimer = null;
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () {
        form.submit();
      }, 450);
    });
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        clearTimeout(searchTimer);
        form.submit();
      }
    });
  }
})();
</script>
