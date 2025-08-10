<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#0d6efd">
  <title><?php echo isset($title) ? htmlspecialchars($title) : 'Office Management'; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/2.0.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/responsive/3.0.2/css/responsive.bootstrap5.min.css" rel="stylesheet">
  <link rel="manifest" href="<?php echo base_url('assets/pwa/manifest.webmanifest'); ?>">
  <link href="<?php echo base_url('assets/css/app.css'); ?>" rel="stylesheet">
  <!-- jQuery must be loaded early so that inline view scripts relying on it (e.g., chats/app.php) can use $.ajax and delegated events -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
  <style>
    body { background-color: #f7f8fa; }
    .navbar-brand { font-weight: 600; }
    .card { border: 0; border-radius: .75rem; }
    .shadow-soft { box-shadow: 0 0.5rem 1rem rgba(0,0,0,.08)!important; }
    /* Toasts container at top-right */
    #toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 1080; }
    #toast-container .toast { opacity: 1; margin-bottom: .5rem; }
  </style>
</head>
<body>
<?php if (empty($hide_navbar)): ?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar shadow-sm">
  <div class="container-fluid px-3">
    <a class="navbar-brand" href="<?php echo site_url('dashboard'); ?>">OfficeMgmt</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbars" aria-controls="navbars" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbars">
      <div class="me-auto"></div>
      <div class="d-flex">
        <?php if($this->session->userdata('user_id')): ?>
          <?php 
            $emailStr = strtolower(trim((string)$this->session->userdata('email')));
            $hash = md5($emailStr);
            $avatar = 'https://www.gravatar.com/avatar/' . $hash . '?s=64&d=identicon';
          ?>
          <div class="dropdown">
            <a class="d-flex align-items-center text-decoration-none" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="<?php echo $avatar; ?>" alt="Profile" class="rounded-circle me-2" width="32" height="32">
              <span class="d-none d-sm-inline small fw-semibold navbar-text"><?php echo htmlspecialchars($this->session->userdata('email')); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
              <li><a class="dropdown-item" href="<?php echo site_url('profile'); ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?php echo site_url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a class="btn btn-primary btn-sm" href="<?php echo site_url('login'); ?>">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>
<?php if (empty($hide_navbar)): ?>
<div id="toast-container">
  <?php if($this->session->flashdata('success')): ?>
    <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
  <?php if($this->session->flashdata('error')): ?>
    <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php
  // Global layout flags
  // Sidebar is ON by default; set $with_sidebar=false in a view to disable
  $__with_sidebar = array_key_exists('with_sidebar', get_defined_vars()) ? (bool)$with_sidebar : true;
  // When rendering with sidebar, we take over layout (full width)
  $__full_width = $__with_sidebar ? true : !empty($full_width);
?>
<?php if ($__with_sidebar): ?>
<div class="container-fluid px-0">
  <div class="row gx-0">
    <?php $this->load->view('partials/sidebar'); ?>
    <main class="col-12 col-md-9 col-lg-10 p-3 p-md-4">
<?php else: ?>
<main class="pt-1 pb-3">
  <?php if (!$__full_width): ?>
  <div class="container">
  <?php endif; ?>
<?php endif; ?>
