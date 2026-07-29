<?php
$embed = !empty($embed);

if (!$embed) {
    $this->load->view('partials/header', array(
        'title' => 'Clients Dashboard',
        'extra_css' => array('assets/css/clients.css', 'assets/css/project-dashboard.css'),
    ));
}
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

  <?php
  $this->load->view('clients/_cart_filters', array(
      'filters'           => isset($filters) ? $filters : array(),
      'client_types'      => isset($client_types) ? $client_types : array(),
      'filter_projects'   => isset($filter_projects) ? $filter_projects : array(),
      'filter_project_id' => isset($filter_project_id) ? (int) $filter_project_id : 0,
      'embed'             => $embed,
      'form_action'       => site_url('clients/dashboard'),
      'cart_tab'          => false,
  ));
  ?>

  <?php $this->load->view('clients/_cart_body'); ?>
</div>

<?php if (!$embed) {
    $this->load->view('partials/footer');
} ?>
