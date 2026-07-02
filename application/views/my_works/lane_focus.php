<?php
  $lane_key = isset($lane_key) ? (string) $lane_key : 'todays_plan';
  $lane_pages = my_works_dashboard_lane_focus_pages();
  $lane_meta = isset($lane_pages[$lane_key]) ? $lane_pages[$lane_key] : $lane_pages['todays_plan'];
  $page_title = isset($page_title) ? (string) $page_title : $lane_meta['page_title'];
  $body_class = isset($body_class) ? (string) $body_class : $lane_meta['body_class'];
  $lane_labels = my_works_dashboard_lane_labels();
  $lane_label = isset($lane_label) ? (string) $lane_label : (isset($lane_labels[$lane_key]) ? $lane_labels[$lane_key] : $lane_key);
  $dashboard_sections = isset($dashboard_sections) ? $dashboard_sections : array();
  $back_url = site_url('my-works?view=overview');
?>
<?php $this->load->view('partials/header', array('title' => $page_title, 'extra_css' => array('assets/css/my-works.css'), 'body_class' => $body_class . ' mw-body-lane-focus-immersive')); ?>

<div class="mw-dash-page mw-dash-page-focus mw-dash-page-focus-immersive">

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2 mb-2" role="alert">
      <?php echo esc_view((string) $this->session->flashdata('success'), ENT_QUOTES, 'UTF-8'); ?>
      <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <header class="mw-focus-screen-head">
    <a class="btn btn-outline-secondary btn-sm mw-focus-back-btn" href="<?php echo esc_view($back_url, ENT_QUOTES, 'UTF-8'); ?>" title="Back to My Work Overview">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
    <h1 class="mw-focus-screen-title"><?php echo esc_view($page_title, ENT_QUOTES, 'UTF-8'); ?></h1>
  </header>

  <section class="mw-dash-section mw-dash-section-focus">
    <?php $this->load->view('my_works/_dashboard_lanes', array(
      'section_key' => 'focus',
      'dashboard_sections' => $dashboard_sections,
      'lane_keys_filter' => array($lane_key),
      'single_lane_layout' => true,
      'fullscreen_lane' => true,
      'disable_lane_drag' => true,
      'hide_drag_column' => true,
      'force_show_date' => true,
      'can_view_all' => !empty($can_view_all),
    )); ?>
  </section>

</div>

<?php $this->load->view('partials/footer'); ?>
