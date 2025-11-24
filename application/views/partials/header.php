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
  <?php
    if (isset($extra_css) && is_array($extra_css)) {
        foreach ($extra_css as $cssFile) {
            echo '<link href="'.base_url($cssFile).'" rel="stylesheet">' . PHP_EOL;
        }
    }
  ?>
  <!-- jQuery must be loaded early so that inline view scripts relying on it (e.g., chats/app.php) can use $.ajax and delegated events -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</head>
<body>
<?php if (empty($hide_navbar)): ?>
<nav class="navbar navbar-expand-lg navbar-dark app-navbar shadow-sm">
  <div class="container-fluid px-3">
    <a class="navbar-brand" href="<?php echo site_url('dashboard'); ?>">OfficeMgmt</a>
    <?php if ((int)$this->session->userdata('user_id')): ?>
    <!-- Mobile sidebar toggle (single button on mobile) -->
    <button class="btn btn-outline-light d-inline-flex d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Open menu">
      <i class="bi bi-list"></i>
    </button>
    <?php endif; ?>
    <div class="navbar-collapse">
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
<?php
// Render mobile offcanvas sidebar when user is logged in and sidebar is enabled
$__with_sidebar = array_key_exists('with_sidebar', get_defined_vars()) ? (bool)$with_sidebar : true;
if ((int)$this->session->userdata('user_id') && $__with_sidebar): ?>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="mobileSidebarLabel">Menu</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <nav class="nav flex-column gap-1 p-3">
      <?php $active = strtolower($this->uri->segment(1) ?: 'dashboard'); ?>
      <a class="nav-link sidebar-link <?php echo $active==='dashboard'?'active':''; ?>" href="<?php echo site_url('dashboard'); ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>

      <?php if(function_exists('has_module_access') && has_module_access('users')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='users'?'active':''; ?>" href="<?php echo site_url('users'); ?>"><i class="bi bi-people me-2"></i>Users</a>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('mail')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='mail'?'active':''; ?>" href="<?php echo site_url('mail'); ?>"><i class="bi bi-envelope me-2"></i>Mail</a>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('projects')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='projects'?'active':''; ?>" href="<?php echo site_url('projects'); ?>"><i class="bi bi-kanban me-2"></i>Projects</a>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('employees')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='employees'?'active':''; ?>" href="<?php echo site_url('employees'); ?>"><i class="bi bi-people me-2"></i>Employees</a>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('tasks')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='tasks'?'active':''; ?>" href="<?php echo site_url('tasks/board'); ?>"><i class="bi bi-list-check me-2"></i>Tasks</a>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('chats')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='chats'?'active':''; ?>" href="<?php echo site_url('chats/app'); ?>"><i class="bi bi-chat-dots me-2"></i>Chats</a>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('attendance')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='attendance'?'active':''; ?>" href="<?php echo site_url('attendance'); ?>"><i class="bi bi-calendar-check me-2"></i>Attendance</a>
      <?php endif; ?>

      <?php if(function_exists('has_module_access') && has_module_access('reports')): ?>
      <a class="nav-link sidebar-link <?php echo $active==='reports'?'active':''; ?>" href="<?php echo site_url('reports'); ?>"><i class="bi bi-graph-up me-2"></i>Reports</a>
      <?php endif; ?>
      <hr class="my-2 border-secondary">
      <a class="nav-link sidebar-link text-danger" href="<?php echo site_url('logout'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>
  </div>
  <div class="offcanvas-footer small text-muted px-3 pb-3">OfficeMgmt</div>
</div>
<?php endif; ?>
<!-- Global Incoming Call Modal -->
<div class="modal fade" id="incomingCallModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title"><i class="bi bi-telephone-inbound me-2"></i>Incoming Call</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
            <i class="bi bi-person-video3"></i>
          </div>
          <div>
            <div class="fw-semibold" id="incomingCallFrom">Someone is calling…</div>
            <div class="text-muted small">Conversation <span id="incomingConvId"></span></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger" id="btnGlobalReject"><i class="bi bi-telephone-x me-1"></i>Reject</button>
        <a href="#" class="btn btn-success" id="btnGlobalAccept"><i class="bi bi-telephone-inbound me-1"></i>Accept</a>
      </div>
    </div>
  </div>
  <audio id="incomingRingAudio" loop>
    <source src="data:audio/mp3;base64,//uQZAAAAAAAAAAAAAAAAAAAA..." type="audio/mp3">
  </audio>
  <script>
  (function(){
    try{
      const site = '<?php echo rtrim(site_url(), "/"); ?>/';
      const me = <?php echo (int)$this->session->userdata('user_id'); ?>;
      if (!me) return; // not logged in
      // Persist last processed signal id across refresh to avoid replaying old offers
      var sinceKey = 'globalIncomingSinceId';
      var seenCallsKey = 'globalSeenCallIds';
      function loadSince(){ try { return parseInt(localStorage.getItem(sinceKey)||'0',10)||0; } catch(e){ return 0; } }
      function saveSince(v){ try { localStorage.setItem(sinceKey, String(v||0)); } catch(e){} }
      function loadSeen(){ try { var a = JSON.parse(localStorage.getItem(seenCallsKey)||'[]'); return Array.isArray(a)? new Set(a.slice(-50)) : new Set(); } catch(e){ return new Set(); } }
      function saveSeen(set){ try { localStorage.setItem(seenCallsKey, JSON.stringify(Array.from(set).slice(-50))); } catch(e){} }
      var sinceId = loadSince(); var globalTimer = null; var lastSignal = null; var seenCallIds = loadSeen();
      var modalEl = document.getElementById('incomingCallModal');
      var incomingConvIdEl = document.getElementById('incomingConvId');
      var incomingFromEl = document.getElementById('incomingCallFrom');
      var btnAccept = document.getElementById('btnGlobalAccept');
      var btnReject = document.getElementById('btnGlobalReject');
      var ringEl = document.getElementById('incomingRingAudio');
      var bsModal = null;
      function ensureModal(){
        try { if (!bsModal && window.bootstrap && window.bootstrap.Modal) { bsModal = new bootstrap.Modal(modalEl, { backdrop:'static', keyboard:false }); } } catch(e){}
      }
      function startRing(){ try { ringEl && ringEl.play && ringEl.play().catch(()=>{}); } catch(e){} }
      function stopRing(){ try { ringEl && ringEl.pause && (ringEl.currentTime=0); } catch(e){} }
      function showIncoming(sig){
        lastSignal = sig;
        incomingConvIdEl.textContent = String(sig.conversation_id || '');
        incomingFromEl.textContent = sig.from_email ? ('Incoming call from ' + sig.from_email) : 'Incoming call';
        var convId = sig.conversation_id || '';
        var callId = sig.call_id || '';
        btnAccept.href = site + 'chats/app?open=' + convId + (callId ? ('&call=' + callId + '&auto_accept=1') : '');
        ensureModal();
        try { if (bsModal) bsModal.show(); else modalEl.style.display='block'; } catch(e){}
        startRing();
      }
      function hideIncoming(){
        stopRing();
        try { if (bsModal) bsModal.hide(); else modalEl.style.display='none'; } catch(e){}
      }
      async function poll(){
        if (document.hidden) { /* still poll to be responsive */ }
        try{
          const url = new URL(site + 'calls/incoming-any');
          url.searchParams.set('since_id', sinceId);
          const r = await fetch(url);
          const j = await r.json();
          if (j && j.ok && j.signals && j.signals.length){
            j.signals.forEach(function(s){
              var sid = parseInt(s.id,10)||0; if (sid>sinceId) { sinceId=sid; saveSince(sinceId); }
              // Dedupe per call_id so popup doesn't repeat
              var cid = parseInt(s.call_id||0,10)||0;
              if (cid && seenCallIds.has(cid)) { return; }
              // only show one at a time
              if (!lastSignal) { showIncoming(s); }
            });
          }
        }catch(e){}
      }
      function ensurePolling(){
        try { if (globalTimer) clearInterval(globalTimer); globalTimer = setInterval(poll, 3000); } catch(e){}
      }
      ensurePolling(); poll();
      btnReject && btnReject.addEventListener('click', async function(){
        try{
          if (lastSignal && lastSignal.call_id){ await fetch(site + 'calls/end/' + lastSignal.call_id, { method:'POST' }); }
        }catch(e){}
        // Mark this call as seen so it won't pop again
        try { if (lastSignal && lastSignal.call_id){ seenCallIds.add(parseInt(lastSignal.call_id,10)); saveSeen(seenCallIds); } } catch(e){}
        hideIncoming(); lastSignal=null;
      });
      btnAccept && btnAccept.addEventListener('click', function(){
        // Mark as seen; Chats app will handle accept
        try { if (lastSignal && lastSignal.call_id){ seenCallIds.add(parseInt(lastSignal.call_id,10)); saveSeen(seenCallIds); } } catch(e){}
      });
      // If user navigates to Chats via Accept, the Chats app will handle actual accept signaling
      modalEl.addEventListener('hidden.bs.modal', function(){ stopRing(); });
    }catch(e){}
  })();
  </script>
</div>
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
