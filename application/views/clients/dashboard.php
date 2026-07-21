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

  <?php $this->load->view('clients/_cart_body'); ?>
</div>

<?php if (!$embed) {
    $this->load->view('partials/footer');
} ?>
