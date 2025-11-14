<?php $this->load->view('partials/header', ['title' => 'Chat']); ?>
<style>
  /* Chat App Theming */
  .chat-app {
    --bg-gradient: linear-gradient(135deg, #6ea8fe 0%, #b197fc 35%, #fb8b24 100%);
    --accent: #6ea8fe; /* primary */
    --accent-2: #b197fc; /* purple */
    --accent-3: #20c997; /* teal */
    --muted: #6c757d;
  }
  .chat-app .card-header.gradient {
    background: var(--bg-gradient);
    color: #fff;
  }
  .chat-app .convo-item.active { background-color: rgba(110,168,254,.12); border-left: 3px solid var(--accent); }
  .chat-app .avatar {
    width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items:center; justify-content:center;
    color:#fff; font-weight:600; margin-right:.75rem;
    background: radial-gradient( circle at 30% 30%, var(--accent), var(--accent-2));
  }
  .chat-app .list-group-item .subtitle { font-size:.8rem; color: var(--muted); }
  .chat-app .message { margin-bottom: .75rem; }
  .chat-app .bubble { display:inline-block; max-width:85%; padding:.5rem .75rem; border-radius: .75rem; text-align:left; }
  .chat-app .bubble.me { background-color:#0d6efd; color:#fff; border-top-right-radius: .2rem; }
  .chat-app .bubble.them { background-color:#f8f9fa; color:#212529; border:1px solid #e9ecef; border-top-left-radius: .2rem; }
  .chat-app #messages { background: linear-gradient(180deg, #f8f9ff 0%, #ffffff 40%); }
  .chat-app .composer .btn-primary { background: var(--accent); border-color: var(--accent); }
  .chat-app .type-badge[data-type="group"] { background-color: rgba(32,201,151,.15); color:#198754; }
  .chat-app .type-badge[data-type="dm"] { background-color: rgba(110,168,254,.15); color:#0d6efd; }
  .chat-app .header-sub { font-size:.85rem; opacity:.9; }
  @media (max-width: 767.98px) {
    .chat-app .avatar { width: 30px; height:30px; font-size:.85rem; }
  }
  /* Call overlay (full view) */
  .call-overlay { position: fixed; inset: 0; z-index: 1085; background: radial-gradient(circle at top, rgba(17,25,40,.92) 0%, rgba(2,6,23,.95) 50%, rgba(2,6,23,.98) 100%); color:#fff; display:none; flex-direction: column; padding: 1.25rem; }
  .call-overlay.show { display:flex; }
  .call-overlay .overlay-toolbar { display:flex; flex-wrap:wrap; gap:.5rem; justify-content:flex-end; margin-bottom:1rem; }
  .call-overlay .overlay-toolbar .btn { min-width:42px; }
  .call-overlay .overlay-body { flex:1; display:grid; grid-template-columns: minmax(0,2fr) minmax(260px,1fr); gap:1rem; align-items:stretch; }
  .call-overlay .overlay-stage { position:relative; background:#0f172a; border-radius:16px; padding: .5rem; display:flex; align-items:center; justify-content:center; }
  .call-overlay .overlay-stage video { width:100%; height:100%; object-fit:cover; border-radius:12px; background:#111827; }
  .call-overlay .overlay-side { display:flex; flex-direction:column; gap:1rem; }
  .call-overlay .overlay-tile { background:#111827; border-radius:12px; padding:.5rem; display:flex; flex-direction:column; }
  .call-overlay .overlay-tile video { width:100%; border-radius:10px; background:#000; object-fit:cover; }
  .call-overlay .overlay-tile .tile-label { font-size:.8rem; color:rgba(226,232,240,.85); margin-top:.35rem; text-align:center; }
  .call-overlay .overlay-status { margin-top:1rem; text-align:center; font-size:.95rem; color:rgba(226,232,240,.8); }
  .call-overlay .overlay-stage .screen-share-active { position:absolute; inset:0; border-radius:12px; overflow:hidden; background:#020617; }
  .call-overlay .overlay-stage .screen-share-active video { width:100%; height:100%; object-fit:contain; background:#020617; }
  @media (max-width: 992px) {
    .call-overlay .overlay-body { grid-template-columns: 1fr; }
    .call-overlay .overlay-side { flex-direction:row; flex-wrap:wrap; }
    .call-overlay .overlay-tile { flex:1 1 45%; }
  }
</style>
<div class="chat-app row g-3">
  <div class="col-12 col-md-4 col-lg-3">
    <div class="card h-100">
      <div class="card-header gradient d-flex align-items-center justify-content-between">
        <div class="fw-semibold">Conversations</div>
        <a class="btn btn-sm btn-light" href="<?php echo site_url('chats'); ?>" title="New"><i class="bi bi-plus-lg"></i></a>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush" id="convoList" style="max-height: 70vh; overflow-y: auto;" data-initial-id="<?php echo (int) ((isset($open_id) && $open_id) ? $open_id : (!empty($conversations) ? (int)$conversations[0]->id : 0)); ?>">
          <?php if (!empty($conversations)) foreach ($conversations as $c): ?>
            <?php
              $label = ($c->type === 'group') ? ($c->title ?: 'Untitled Group') : ($c->members ?: 'Direct Message');
              $initial = strtoupper(substr(preg_replace('/[^A-Za-z]/','', $label), 0, 1));
              if ($initial === '') { $initial = '#'; }
            ?>
            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center convo-item" data-id="<?php echo (int)$c->id; ?>" data-type="<?php echo htmlspecialchars($c->type); ?>" data-title="<?php echo htmlspecialchars($c->title ?: ''); ?>" data-members="<?php echo htmlspecialchars($c->members ?: ''); ?>">
              <span class="avatar flex-shrink-0"><?php echo htmlspecialchars($initial); ?></span>
              <div class="flex-grow-1">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="fw-semibold"><?php echo htmlspecialchars($label); ?></div>
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge type-badge" data-type="<?php echo htmlspecialchars($c->type==='group'?'group':'dm'); ?>"><?php echo htmlspecialchars(strtoupper($c->type)); ?></span>
                    <span class="badge rounded-pill bg-danger unread-badge d-none" data-cid="<?php echo (int)$c->id; ?>">0</span>
                  </div>
                </div>
                <div class="subtitle">ID #<?php echo (int)$c->id; ?></div>
              </div>
            </button>
          <?php endforeach; ?>
          <?php if (empty($conversations)): ?>
            <div class="p-3 text-center text-muted">No conversations</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-md-8 col-lg-9">
    <div class="card h-100">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <div>
          <div class="fw-semibold">Conversation <span id="hdrConvId" class="text-muted"></span></div>
          <div id="hdrConvMeta" class="header-sub"></div>
        </div>
        <div class="d-flex gap-2">
          <button id="btnToggleMic" class="btn btn-outline-secondary btn-sm" disabled title="Toggle Microphone">
            <i class="bi bi-mic"></i>
          </button>
          <button id="btnToggleSpeaker" class="btn btn-outline-secondary btn-sm" disabled title="Toggle Speaker">
            <i class="bi bi-volume-up"></i>
          </button>
          <button id="btnCallToggle" class="btn btn-outline-primary btn-sm" disabled><i class="bi bi-camera-video"></i> Start Call</button>
          <button id="btnAcceptCall" class="btn btn-success btn-sm d-none" title="Accept incoming call"><i class="bi bi-telephone-inbound"></i></button>
          <button id="btnRejectCall" class="btn btn-outline-danger btn-sm d-none" title="Reject incoming call"><i class="bi bi-telephone-x"></i></button>
          <button id="btnReminder" class="btn btn-outline-warning btn-sm" title="Send reminder" disabled><i class="bi bi-bell"></i></button>
          <button id="btnShareScreen" class="btn btn-outline-secondary btn-sm" disabled title="Share Screen"><i class="bi bi-display"></i></button>
          <button id="btnRecord" class="btn btn-outline-secondary btn-sm" disabled title="Record (screen/camera)"><i class="bi bi-record-circle"></i></button>
          <button id="btnEndCall" class="btn btn-outline-danger btn-sm d-none"><i class="bi bi-telephone-x"></i></button>
          <button id="btnFullscreen" class="btn btn-outline-secondary btn-sm" disabled title="Full View"><i class="bi bi-arrows-fullscreen"></i></button>
        </div>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12 col-xl-7">
            <div id="messages" class="border rounded p-3" style="height: 55vh; overflow-y: auto;"></div>
            <form id="sendForm" class="composer d-flex gap-2 mt-2" enctype="multipart/form-data">
              <input type="hidden" name="conversation_id" id="conversation_id">
              <textarea name="body" class="form-control" rows="2" placeholder="Type a message... (Enter to send, Shift+Enter for newline)" disabled></textarea>
              <button type="button" id="btnAttach" class="btn btn-outline-secondary" title="Attach file" disabled>
                <i class="bi bi-paperclip"></i>
              </button>
              <input type="file" name="attachment" id="attachment" accept="image/*,.pdf,.doc,.docx" class="visually-hidden" disabled>
              <button class="btn btn-primary" type="submit" disabled><i class="bi bi-send"></i></button>
            </form>
          </div>
          <div class="col-12 col-xl-5">
            <div class="ratio ratio-16x9 bg-dark mb-2 rounded"><video id="remoteVideo" autoplay playsinline style="width:100%; height:100%;"></video></div>
            <div class="ratio ratio-16x9 bg-secondary mb-2 rounded"><video id="localVideo" autoplay playsinline muted style="width:100%; height:100%;"></video></div>
            <div id="callStatus" class="small text-muted">Idle</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="callOverlay" class="call-overlay" aria-hidden="true">
  <div class="overlay-toolbar">
    <button id="btnOverlayMinimize" class="btn btn-outline-light btn-sm" title="Back to chat"><i class="bi bi-chat-dots"></i></button>
    <button id="btnOverlayScreen" class="btn btn-outline-light btn-sm" title="Toggle screen share"><i class="bi bi-display"></i></button>
    <button id="btnOverlayMic" class="btn btn-outline-light btn-sm" title="Mute / unmute microphone"><i class="bi bi-mic"></i></button>
    <button id="btnOverlayCamera" class="btn btn-outline-light btn-sm" title="Toggle camera"><i class="bi bi-camera-video"></i></button>
    <button id="btnOverlayLeave" class="btn btn-danger btn-sm" title="End call"><i class="bi bi-telephone-x"></i></button>
    <button id="btnOverlayClose" class="btn btn-outline-light btn-sm" title="Close full view"><i class="bi bi-fullscreen-exit"></i></button>
  </div>
  <div class="overlay-body">
    <div class="overlay-stage">
      <div id="overlayScreenWrap" class="screen-share-active d-none">
        <video id="overlayScreenVideo" autoplay playsinline></video>
      </div>
      <video id="overlayRemoteVideo" autoplay playsinline></video>
    </div>
    <div class="overlay-side">
      <div class="overlay-tile">
        <video id="overlayLocalVideo" autoplay playsinline muted></video>
        <div class="tile-label">You</div>
      </div>
      <div class="overlay-tile">
        <video id="overlayRemoteThumb" autoplay playsinline></video>
        <div class="tile-label">Remote Preview</div>
      </div>
    </div>
  </div>
  <div id="overlayStatus" class="overlay-status">Idle</div>
</div>

<!-- Recording footer (shown only while recording) -->
<div id="recordFooter" class="position-fixed bottom-0 start-0 end-0 py-2 px-3" style="display:none; background:rgba(33,37,41,.95); color:#fff; z-index:1080;">
  <div class="d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <i id="recordIcon" class="bi bi-record-fill text-danger" style="font-size:1.25rem"></i>
      <span id="recordLabel" class="small text-uppercase text-muted">Recording</span>
      <strong id="recordTimer" class="ms-2">00:00</strong>
    </div>
    <div class="d-flex align-items-center gap-2">
      <button id="btnSaveRecording" class="btn btn-sm btn-light" title="Save a copy (so far)"><i class="bi bi-save"></i></button>
    </div>
  </div>
  <style>
    @media (max-width: 576px){ #recordLabel{ display:none; } }
  </style>
</div>
<!-- Toast container for in-app notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
  <div id="chatToast" class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body">
        <div class="fw-semibold" id="toastTitle">New message</div>
        <div class="small" id="toastBody">You have a new message</div>
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
  <audio id="chatSound">
    <source src="data:audio/mp3;base64,//uQZAAAAAAAAAAAAAAAAAAAA..." type="audio/mp3">
  </audio>
  <!-- Note: embedded tiny silent/placeholder sound can be replaced with a real sound file in assets -->
</div>

<script>
(function(){
  const site = '<?php echo rtrim(site_url(), "/"); ?>/';
  const userId = <?php echo (int)$user_id; ?>;
  let convoId = 0; let lastId = 0; let pollTimer = null; let signalTimer = null;
  // Incoming call polling state
  let incomingSinceId = 0; let incomingTimer = null;
  const lastNotified = {}; // per-conversation last notified message id

  const convoList = document.getElementById('convoList');
  const messagesEl = document.getElementById('messages');
  const form = document.getElementById('sendForm');
  const inputConvo = document.getElementById('conversation_id');
  const hdrConvId = document.getElementById('hdrConvId');
  const hdrConvMeta = document.getElementById('hdrConvMeta');
  // Recording footer elements (must be declared before use)
  const recordFooter = document.getElementById('recordFooter');
  const recordTimerEl = document.getElementById('recordTimer');
  const btnSaveRecording = document.getElementById('btnSaveRecording');
  const btnCallToggle = document.getElementById('btnCallToggle');
  const btnAcceptCall = document.getElementById('btnAcceptCall');
  const btnRejectCall = document.getElementById('btnRejectCall');
  const btnEndCall = document.getElementById('btnEndCall');
  const btnToggleMic = document.getElementById('btnToggleMic');
  const btnToggleSpeaker = document.getElementById('btnToggleSpeaker');
  const callStatus = document.getElementById('callStatus');
  const btnShareScreen = document.getElementById('btnShareScreen');
  const btnRecord = document.getElementById('btnRecord');
  const btnReminder = document.getElementById('btnReminder');
  const btnAttach = document.getElementById('btnAttach');
  const btnFullscreen = document.getElementById('btnFullscreen');
  const callOverlay = document.getElementById('callOverlay');
  const overlayRemoteVideo = document.getElementById('overlayRemoteVideo');
  const overlayRemoteThumb = document.getElementById('overlayRemoteThumb');
  const overlayLocalVideo = document.getElementById('overlayLocalVideo');
  const overlayScreenWrap = document.getElementById('overlayScreenWrap');
  const overlayScreenVideo = document.getElementById('overlayScreenVideo');
  const overlayStatus = document.getElementById('overlayStatus');
  const btnOverlayMinimize = document.getElementById('btnOverlayMinimize');
  const btnOverlayScreen = document.getElementById('btnOverlayScreen');
  const btnOverlayMic = document.getElementById('btnOverlayMic');
  const btnOverlayCamera = document.getElementById('btnOverlayCamera');
  const btnOverlayLeave = document.getElementById('btnOverlayLeave');
  const btnOverlayClose = document.getElementById('btnOverlayClose');
  // Helpers for remote server robustness
  function isUnauthorizedResponse(r){ try { return r && r.status===401; } catch(e){ return false; } }
  async function parseJsonSafe(r){ try { return await r.json(); } catch(e){ return null; } }
  function handleUnauthorized(r){ if (isUnauthorizedResponse(r)) { try { window.location = site + 'login'; } catch(e){} return true; } return false; }

  // Ringing (WebAudio) helpers
  let ringCtx = null, ringOsc = null, ringGain = null;
  function startRinging(type){ // type: 'in' | 'out'
    try {
      stopRinging();
      const AudioCtx = window.AudioContext || window.webkitAudioContext; if (!AudioCtx) return;
      ringCtx = new AudioCtx();
      ringOsc = ringCtx.createOscillator();
      ringGain = ringCtx.createGain();
      ringOsc.type = 'sine';
      let f1 = type==='in' ? 800 : 1000; let f2 = type==='in' ? 600 : 750;
      // Simple repeating chirp pattern
      let t0 = ringCtx.currentTime;
      ringOsc.frequency.setValueAtTime(f1, t0);
      ringOsc.frequency.linearRampToValueAtTime(f2, t0+0.25);
      ringOsc.frequency.linearRampToValueAtTime(f1, t0+0.5);
      ringGain.gain.setValueAtTime(0.0, t0);
      ringGain.gain.linearRampToValueAtTime(0.06, t0+0.02);
      ringGain.gain.linearRampToValueAtTime(0.0, t0+0.5);
      // Loop the envelope
      const loop = () => {
        if (!ringCtx || ringCtx.state==='closed') return;
        const now = ringCtx.currentTime;
        ringOsc.frequency.setValueAtTime(f1, now);
        ringOsc.frequency.linearRampToValueAtTime(f2, now+0.25);
        ringOsc.frequency.linearRampToValueAtTime(f1, now+0.5);
        ringGain.gain.cancelScheduledValues(now);
        ringGain.gain.setValueAtTime(0.0, now);
        ringGain.gain.linearRampToValueAtTime(0.06, now+0.02);
        ringGain.gain.linearRampToValueAtTime(0.0, now+0.5);
        ringLoopTimer = setTimeout(loop, 700);
      };
      ringOsc.connect(ringGain); ringGain.connect(ringCtx.destination);
      ringOsc.start();
      var ringLoopTimer = setTimeout(loop, 700);
    } catch(e){}
  }
  function stopRinging(){
    try { if (ringOsc) { try { ringOsc.stop(); } catch(e){} ringOsc.disconnect(); } } catch(e){}
    try { if (ringGain) ringGain.disconnect(); } catch(e){}
    try { if (ringCtx && ringCtx.state!=='closed') ringCtx.close(); } catch(e){}
    ringCtx = null; ringOsc = null; ringGain = null;
  }
  const attachmentInput = document.getElementById('attachment');
  const chatToastEl = document.getElementById('chatToast');
  const toastTitleEl = document.getElementById('toastTitle');
  const toastBodyEl = document.getElementById('toastBody');
  const chatSoundEl = document.getElementById('chatSound');
  const unreadCounts = {}; // cid -> count
  let overlayOpen = false;
  let cameraEnabled = true;
  let toastInstance = null;
  if (window.bootstrap && window.bootstrap.Toast) {
    toastInstance = new bootstrap.Toast(chatToastEl, { delay: 3500 });
  }
  // Helper: are we actively viewing the current conversation and scrolled to bottom?
  function isActivelyViewingCurrent(){
    if (document.visibilityState !== 'visible') return false;
    if (!convoId) return false;
    const nearBottom = (messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight) < 8;
    return nearBottom;
  }
  // Helper: find and select conversation by id
  function findConvoButtonById(id){
    return document.querySelector('.convo-item[data-id="'+id+'"]');
  }

  function getUnreadBadge(cid){
    return document.querySelector('.unread-badge[data-cid="'+cid+'"]');
  }
  function setUnread(cid, count){
    unreadCounts[cid] = Math.max(0, parseInt(count||0,10));
    const badge = getUnreadBadge(cid);
    if (!badge) return;
    if (unreadCounts[cid] > 0){
      badge.textContent = String(unreadCounts[cid]);
      badge.classList.remove('d-none');
    } else {
      badge.textContent = '0';
      badge.classList.add('d-none');
    }
  }
  function incrementUnread(cid){ setUnread(cid, (unreadCounts[cid]||0)+1); }

  function focusConversationById(id){
    const btn = findConvoButtonById(id);
    if (btn) selectConvo(btn);
  }

  // Update the conversation header area based on metadata
  function updateHeader(opts){
    try {
      const id = (opts && opts.id) ? parseInt(opts.id,10) : 0;
      const type = (opts && opts.type) ? String(opts.type) : '';
      const title = (opts && opts.title) ? String(opts.title) : '';
      const members = (opts && opts.members) ? String(opts.members) : '';
      if (hdrConvId) { hdrConvId.textContent = id ? ('#'+id) : ''; }
      let meta = '';
      if (type) { meta += '['+type.toUpperCase()+'] '; }
      meta += title ? title : (members || '');
      if (hdrConvMeta) { hdrConvMeta.textContent = meta; }
      // Enable call actions when a conversation is active
      if (btnCallToggle) btnCallToggle.disabled = (id?false:true);
      if (btnReminder) btnReminder.disabled = (id?false:true);
      if (btnEndCall) btnEndCall.disabled = true;
      if (btnAcceptCall) btnAcceptCall.classList.add('d-none');
      if (btnRejectCall) btnRejectCall.classList.add('d-none');
      if (callStatus) callStatus.textContent = 'Idle';
      setOverlayStatus('Idle');
      if (btnFullscreen) btnFullscreen.disabled = !id;
    } catch(e) { /* noop */ }
  }

  // Centralized conversation selection routine
  function selectConvo(btn){
    try {
      const id = parseInt(btn.getAttribute('data-id') || (btn.dataset && btn.dataset.id) || '0', 10);
      const type = btn.getAttribute('data-type') || (btn.dataset && btn.dataset.type) || '';
      const title = btn.getAttribute('data-title') || (btn.dataset && btn.dataset.title) || '';
      const members = btn.getAttribute('data-members') || (btn.dataset && btn.dataset.members) || '';
      if (!id) return;
      // Visual active state
      document.querySelectorAll('#convoList .convo-item.active').forEach(x=>x.classList.remove('active'));
      btn.classList.add('active');
      // Reset state
      convoId = id; lastId = 0; messagesEl.innerHTML = '';
      if (typeof inputConvo !== 'undefined' && inputConvo) { inputConvo.value = id; }
      // Update header and unread
      updateHeader({ id, type, title, members });
      setUnread(id, 0);
      if (typeof setFormEnabled === 'function') { setFormEnabled(true); }
      // Reset incoming offer polling state for this convo
      incomingSinceId = 0;
      // Fetch & poll
      fetchMessages();
      if (typeof ensurePolling === 'function') { ensurePolling(); }
      if (typeof ensureIncomingPolling === 'function') { ensureIncomingPolling(); }
    } catch(e) {
      console.warn('selectConvo error', e);
    }
  }

  // Clicking toast focuses relevant conversation and hides the toast
  chatToastEl && chatToastEl.addEventListener('click', ()=>{
    try { toastInstance && toastInstance.hide && toastInstance.hide(); } catch(e) {}
    const cid = chatToastEl && chatToastEl.dataset && chatToastEl.dataset.convoId ? parseInt(chatToastEl.dataset.convoId,10) : 0;
    if (cid && (!convoId || convoId !== cid)) { focusConversationById(cid); }
    if (cid) { setUnread(cid, 0); }
    try { messagesEl.scrollTop = messagesEl.scrollHeight; } catch(e) {}
  });

  // Overlay helpers
  function setOverlayStatus(text){
    try { if (overlayStatus) overlayStatus.textContent = text || ''; } catch(e){}
  }

  function syncOverlayStreams(){
    try {
      if (overlayRemoteVideo) { overlayRemoteVideo.srcObject = remoteVideo ? remoteVideo.srcObject : null; }
      if (overlayRemoteThumb) { overlayRemoteThumb.srcObject = remoteVideo ? remoteVideo.srcObject : null; }
      if (overlayLocalVideo) { overlayLocalVideo.srcObject = localStream || null; }
      if (overlayScreenVideo) { overlayScreenVideo.srcObject = screenStream || null; }
      if (screenStream) {
        overlayScreenWrap && overlayScreenWrap.classList.remove('d-none');
      } else {
        overlayScreenWrap && overlayScreenWrap.classList.add('d-none');
      }
    } catch(e){}
  }

  function setOverlayVisible(open){
    overlayOpen = !!open;
    if (!callOverlay) return;
    if (overlayOpen){
      callOverlay.classList.add('show');
      callOverlay.setAttribute('aria-hidden','false');
      syncOverlayStreams();
      if (btnFullscreen) { btnFullscreen.innerHTML = '<i class="bi bi-fullscreen-exit"></i>'; }
    } else {
      callOverlay.classList.remove('show');
      callOverlay.setAttribute('aria-hidden','true');
      if (btnFullscreen) { btnFullscreen.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>'; }
    }
  }

  // Screen share helpers
  async function startScreenShare(){
    if (!pc) return;
    try {
      screenStream = await navigator.mediaDevices.getDisplayMedia({ video: { cursor: 'always' }, audio: false });
      const screenTrack = screenStream.getVideoTracks()[0];

      if (!originalVideoTrack && localStream) { originalVideoTrack = localStream.getVideoTracks()[0] || null; }
      const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
      if (sender && screenTrack) {
        await sender.replaceTrack(screenTrack);
      }
      // Show screen in local preview
      localVideo.srcObject = screenStream;
      btnShareScreen.innerHTML = '<i class="bi bi-display-fill"></i>';
      if (btnOverlayScreen) btnOverlayScreen.innerHTML = '<i class="bi bi-display-fill"></i>';
      setStatus('Screen sharing');
      syncOverlayStreams();
      // If user stops from browser UI, auto-restore
      screenTrack.onended = () => { stopScreenShare().catch(()=>{}); };
    } catch(e) {
      // silently ignore if user cancels
    }
  }

  async function stopScreenShare(){
    if (!pc) return;

    try {
      const sender = pc.getSenders().find(s => s.track && s.track.kind === 'video');
      if (sender && originalVideoTrack) {
        await sender.replaceTrack(originalVideoTrack);
      }
      // Restore camera preview
      if (localStream) { localVideo.srcObject = localStream; }
      if (screenStream) {
        try { screenStream.getTracks().forEach(t=>{ try { t.stop(); } catch(e){} }); } catch(e) {}
      }
      screenStream = null;
      btnShareScreen.innerHTML = '<i class="bi bi-display"></i>';
      if (btnOverlayScreen) btnOverlayScreen.innerHTML = '<i class="bi bi-display"></i>';
      setStatus('Connected');
      syncOverlayStreams();
    } catch(e) {}
  }

  btnShareScreen.addEventListener('click', async ()=>{
    if (!pc) return;
    if (screenStream) { await stopScreenShare(); }
    else { await startScreenShare(); }
  });

  // Recording helpers
  function getStreamToRecord(){ return screenStream || localStream; }
  function getSupportedMime(){
    const candidates = [ 'video/webm;codecs=vp9', 'video/webm;codecs=vp8', 'video/webm', '' ];
    for (const t of candidates){ if (t && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(t)) return t; }
    return '';
  }
  async function startRecording(){
    const src = getStreamToRecord(); if (!src) return;
    recordedChunks = [];
    const mime = getSupportedMime();
    mediaRecorder = new MediaRecorder(src, mime ? { mimeType: mime } : {});
    mediaRecorder.ondataavailable = (e)=>{ if (e.data && e.data.size>0) recordedChunks.push(e.data); };
    mediaRecorder.onstop = ()=>{
      try {
        const blob = new Blob(recordedChunks, { type: recordedChunks[0] ? recordedChunks[0].type : 'video/webm' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const ts = new Date().toISOString().replace(/[:.]/g,'-');
        a.href = url; a.download = 'recording-'+ts+'.webm';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(()=>URL.revokeObjectURL(url), 5000);
      } catch(e) {}
    };
    mediaRecorder.start(); isRecording = true; setStatus('Recording...');
    // Show footer & start timer
    recordFooter.style.display = '';
    startTimer();
    btnRecord.innerHTML = '<i class="bi bi-stop-circle"></i>';
  }
  async function stopRecording(){
    if (!mediaRecorder) return;
    try { mediaRecorder.stop(); } catch(e) {}
    isRecording = false; mediaRecorder = null;
    btnRecord.innerHTML = '<i class=\"bi bi-record-circle\"></i>';
    if (pc) setStatus('Connected');
    // Hide footer & stop timer
    recordFooter.style.display = 'none';
    stopTimer();
  }
  // Save a copy without stopping recording
  function downloadFromChunks(chunks, postfix){
    try{
      if (!chunks || chunks.length===0) return;
      const type = chunks[0].type || 'video/webm';
      const blob = new Blob(chunks, { type });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      const ts = new Date().toISOString().replace(/[:.]/g,'-');
      a.href = url; a.download = 'recording-'+ts+(postfix||'')+'.webm';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(()=>URL.revokeObjectURL(url), 5000);
    }catch(e){}
  }
  btnSaveRecording.addEventListener('click', ()=>{
    if (!isRecording || !mediaRecorder) return;
    // Request current data, then download a copy shortly after
    const beforeLen = recordedChunks.length;
    try { mediaRecorder.requestData(); } catch(e){}
    setTimeout(()=>{
      // Create a copy to avoid mutation while recording continues
      const copy = recordedChunks.slice(0);
      downloadFromChunks(copy, '-partial');
    }, 400);
  });
  btnRecord.addEventListener('click', async ()=>{
    if (!pc) return;
    if (!isRecording) { await startRecording(); }
    else { await stopRecording(); }
  });

  // Attachment icon opens hidden file input
  btnAttach.addEventListener('click', ()=>{ if (!btnAttach.disabled) attachmentInput.click(); });
  // Optional: visual feedback when a file is selected
  attachmentInput.addEventListener('change', ()=>{
    try {
      if (attachmentInput.files && attachmentInput.files.length) {
        btnAttach.classList.add('btn-secondary');
      } else {
        btnAttach.classList.remove('btn-secondary');
      }
    } catch(e) {}
  });

  // Ask permission for desktop notifications
  if ('Notification' in window && Notification.permission === 'default') {
    try { Notification.requestPermission(); } catch(e) {}
  }

  function notifyNewMessage(m) {
    // In-app toast
    try {
      toastTitleEl.textContent = (m.full_name || m.name || m.email || 'New message');
      const bodyText = (m.body ? (m.body.replace(/<[^>]+>/g,'').slice(0,120)) : (m.attachment_path ? 'Sent an attachment' : '')) || 'New message';
      toastBodyEl.textContent = bodyText;
      // attach conversation id for click behavior (fallback to current convoId)
      if (chatToastEl) { chatToastEl.dataset.convoId = String(m.conversation_id ? m.conversation_id : (convoId || '')); }
      if (toastInstance) { toastInstance.show(); }
    } catch(e) {}
    // Sound
    try { chatSoundEl && chatSoundEl.play && chatSoundEl.play().catch(()=>{}); } catch(e) {}
    // Desktop notification (only when tab not visible)
    if (document.visibilityState !== 'visible' && 'Notification' in window && Notification.permission === 'granted') {
      try {
        const n = new Notification((m.full_name || m.name || m.email || 'New message'), {
          body: (m.body ? m.body.replace(/<[^>]+>/g,'').slice(0,120) : 'New message'),
          icon: '<?php echo base_url('assets/favicon.png'); ?>'
        });
        // On click, focus window, switch to conversation and scroll to bottom
        n.onclick = () => {
          try { window.focus(); } catch(e) {}
          try {
            const cid = m.conversation_id ? parseInt(m.conversation_id,10) : (convoId || 0);
            if (cid) { focusConversationById(cid); }
            messagesEl.scrollTop = messagesEl.scrollHeight;
          } catch(e) {}
        };
        setTimeout(()=>{ n && n.close && n.close(); }, 4000);
      } catch(e) {}
    }
  }

  function setFormEnabled(en) {
    form.querySelectorAll('textarea, input, button').forEach(el => { el.disabled = !en; });
  }

  function setCallToggleUI(active){
    try {
      if (!btnCallToggle) return;
      if (active) {
        btnCallToggle.classList.remove('btn-outline-primary');
        btnCallToggle.classList.add('btn-danger');
        btnCallToggle.innerHTML = '<i class="bi bi-telephone-x"></i> End Call';
      } else {
        btnCallToggle.classList.remove('btn-danger');
        btnCallToggle.classList.add('btn-outline-primary');
        btnCallToggle.innerHTML = '<i class="bi bi-camera-video"></i> Start Call';
      }
    } catch(e){}
  }

  function clearMessages(){ messagesEl.innerHTML=''; lastId = 0; }

  function appendMessage(m) {
    const wrap = document.createElement('div');
    const isMe = parseInt(m.sender_id,10) === userId;
    wrap.className = 'message' + (isMe ? ' text-end' : '');
    const meta = document.createElement('div');
    meta.className = 'small text-muted mb-1';
    meta.textContent = (m.full_name || m.name || m.email || 'User') + ' · ' + (m.created_at || '');
    wrap.appendChild(meta);
    if (m.body) {
      const body = document.createElement('div');
      body.className = 'bubble ' + (isMe ? 'me' : 'them');
      body.innerHTML = m.body; // server sanitizes
      wrap.appendChild(body);
    }
    if (m.attachment_path) {
      const a = document.createElement('a');
      a.className = 'btn btn-sm btn-outline-secondary mt-1';
      a.target = '_blank';
      a.href = site + m.attachment_path;
      a.innerHTML = '<i class="bi bi-paperclip"></i> Attachment';
      wrap.appendChild(a);
    }
    messagesEl.appendChild(wrap);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  // Click to select conversation - jQuery delegated
  if (window.$ && $.fn && document.getElementById('convoList')) {
    $(document).on('click', '#convoList .convo-item', function(e){
      e.preventDefault();
      try { console.log('Convo click', {
        id: parseInt($(this).data('id'),10),
        type: String($(this).data('type')||''),
        title: String($(this).data('title')||''),
        members: String($(this).data('members')||'')
      }); } catch(e){}
      selectConvo(this);
    });
  } else if (convoList) {
    // Fallback to native event if jQuery not available
    convoList.addEventListener('click', (e) => {
      const btn = e.target.closest('.convo-item');
      if (!btn) return;
      e.preventDefault();
      const id = parseInt(btn.getAttribute('data-id'),10);
      const type = btn.getAttribute('data-type')||'';
      const title = btn.getAttribute('data-title')||'';
      const members = btn.getAttribute('data-members')||'';
      if (!id) return;
      document.querySelectorAll('#convoList .convo-item.active').forEach(x=>x.classList.remove('active'));
      btn.classList.add('active');
      convoId = id; lastId = 0; messages.innerHTML = '';
      if (typeof inputConvo !== 'undefined' && inputConvo) { inputConvo.value = id; }
      updateHeader({ id, type, title, members });
      setUnread(id, 0);
      if (typeof setFormEnabled === 'function') { setFormEnabled(true); }
      fetchMessages();
      ensurePolling();
    });
  }

  async function fetchMessages(){
    if (!convoId) return;
    try {
      // Debug
      if (!convoId) { console.warn('fetchMessages called with no convoId'); return; }
      if (window.$ && $.ajax) {
        $.ajax({
          url: site + 'chats/fetch_messages',
          method: 'GET',
          data: { conversation_id: convoId, since_id: lastId },
          dataType: 'json'
        }).done(function(data){
          console.log('fetch_messages OK', { convoId, lastId_before: lastId, payload: data });
          if (data && data.ok && data.messages) {
            data.messages.forEach(function(m){
              const mid = parseInt(m.id,10);
              const cid = parseInt(m.conversation_id || convoId || 0, 10);
              lastId = Math.max(lastId, mid);
              const sameThread = (cid === convoId);
              const fromOther = parseInt(m.sender_id,10) !== userId;
              const lastNoti = (lastNotified[cid] || 0);
              if (sameThread) { appendMessage(m); }
              // Update list preview and order for any message in this conversation
              try { updateConvoPreview(m); } catch(e){}
              const seen = sameThread && isActivelyViewingCurrent();
              if (fromOther && !seen && mid > lastNoti) {
                notifyNewMessage(m);
                lastNotified[cid] = mid;
                if (!sameThread) { incrementUnread(cid); }
              }
            });
          } else {
            console.warn('Chats fetch_messages: not ok', data);
          }
        }).fail(function(xhr){
          console.error('Chats fetch_messages AJAX fail', { status: xhr.status, text: xhr.statusText, resp: xhr && xhr.responseText });
          setStatus('Failed to load messages');
        });
      } else {
        // Fallback to fetch if jQuery not available
        const url = new URL(site + 'chats/fetch_messages');
        url.searchParams.set('conversation_id', convoId);
        url.searchParams.set('since_id', lastId);
        const res = await fetch(url);
        const data = await res.json();
        if (data && data.ok && data.messages) {
          data.messages.forEach((m)=>{ try { updateConvoPreview(m); } catch(e){} appendMessage(m); lastId = Math.max(lastId, parseInt(m.id,10)); });
        }
      }
    } catch(e) {
      console.error('Chats fetch_messages error', e);
    }
  }

  // Start/refresh polling loop for messages
  function ensurePolling(){
    try {
      if (pollTimer) { clearInterval(pollTimer); }
      pollTimer = setInterval(fetchMessages, 2500);
    } catch(e) { console.warn('ensurePolling error', e); }
  }

  // Poll for incoming call offers when no active call is established
  async function pollIncomingOffers(){
    if (!convoId || callId) return; // only when idle
    try {
      const url = new URL(site + 'calls/incoming/' + convoId);
      url.searchParams.set('since_id', incomingSinceId);
      const r = await fetch(url);
      if (handleUnauthorized(r)) return;
      const j = await parseJsonSafe(r);
      if (j && j.ok && j.signals && j.signals.length) {
        j.signals.forEach(function(s){
          const sid = parseInt(s.id,10);
          incomingSinceId = Math.max(incomingSinceId, sid);
          // Prepare UI and state for incoming offer
          callId = parseInt(s.call_id,10) || null;
          pendingRemoteOffer = s.payload; // JSON string
          if (s.from_email) { setStatus('Incoming call from ' + s.from_email + '...'); }
          else { setStatus('Incoming call...'); }
          if (btnAcceptCall) btnAcceptCall.classList.remove('d-none');
          if (btnRejectCall) btnRejectCall.classList.remove('d-none');
          if (btnCallToggle) btnCallToggle.classList.add('d-none');
          startRinging('in');
          // Start polling full signaling for this call so we can receive ICE, etc.
          if (signalTimer) clearInterval(signalTimer);
          signalTimer = setInterval(pollSignals, 2000);
        });
      }
    } catch(e) { /* ignore transient errors */ }
  }
  function ensureIncomingPolling(){
    try {
      if (incomingTimer) clearInterval(incomingTimer);
      incomingTimer = setInterval(pollIncomingOffers, 2500);
    } catch(e) { console.warn('ensureIncomingPolling error', e); }
  }

  // Update conversation list preview and reorder to top on new activity
  function updateConvoPreview(m){
    try {
      const cid = parseInt(m.conversation_id || convoId || 0, 10);
      if (!cid) return;
      const btn = findConvoButtonById(cid);
      if (!btn) return;
      const sub = btn.querySelector('.subtitle');
      const snippet = (m.body ? m.body.replace(/<[^>]+>/g,'').slice(0, 50) : (m.attachment_path ? 'Attachment' : '') ) || '';
      if (sub) { sub.textContent = 'ID #' + cid + (snippet ? ' • ' + snippet : ''); }
      // Move to top of the list for recency
      const list = document.getElementById('convoList');
      if (list && btn.parentElement === list) {
        list.insertBefore(btn, list.firstElementChild);
      }
    } catch(e) { /* no-op */ }
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!convoId) return;
    const fd = new FormData(form);
    if (window.$ && $.ajax) {
      $.ajax({
        url: site + 'chats/send_message',
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        dataType: 'json'
      }).done(function(data){
        if (data && data.ok) {
          try { form.reset(); } catch(e){}
          // If API returns the created message, append immediately; else fetch
          if (data.message) {
            try { appendMessage(data.message); updateConvoPreview(data.message); } catch(e){}
          } else {
            fetchMessages();
          }
          ensurePolling();
        }
        else { console.warn('Chats send_message: not ok', data); }
      }).fail(function(xhr){
        console.error('Chats send_message AJAX fail', xhr && xhr.responseText);
      });
    } else {
      try {
        const res = await fetch(site + 'chats/send_message', { method:'POST', body: fd });
        const data = await res.json();
        if (data && data.ok) {
          try { form.reset(); } catch(e){}
          if (data.message) { try { appendMessage(data.message); updateConvoPreview(data.message); } catch(e){} }
          else { fetchMessages(); }
          ensurePolling();
        }
      } catch(e){ console.error('Chats send_message error', e); }
    }
  });

  // Enter to send, Shift+Enter for newline
  const textarea = form.querySelector('textarea[name="body"]');
  textarea.addEventListener('keydown', (ev)=>{
    if (ev.key === 'Enter' && !ev.shiftKey) {
      ev.preventDefault();
      form.requestSubmit();
    }
  });

  // WebRTC
  let pc=null, localStream=null, callId=null, signalSince=0;
  let screenStream=null; // active screen share stream
  let originalVideoTrack=null; // cached camera track for restore
  let mediaRecorder=null; let recordedChunks=[]; let isRecording=false;
  const localVideo = document.getElementById('localVideo');
  const remoteVideo = document.getElementById('remoteVideo');
  function setStatus(s){
    callStatus && (callStatus.textContent = s);
    setOverlayStatus(s);
  }

  let recordTimerId = null; let recordStartAt = 0;
  function fmt(t){ return (t<10?'0':'')+t; }
  function startTimer(){
    recordStartAt = Date.now();
    clearInterval(recordTimerId);
    recordTimerId = setInterval(()=>{
      const sec = Math.floor((Date.now()-recordStartAt)/1000);
      const mm = Math.floor(sec/60), ss = sec%60;
      recordTimerEl.textContent = fmt(mm)+":"+fmt(ss);
    }, 500);
  }
  function stopTimer(){ clearInterval(recordTimerId); recordTimerId=null; recordTimerEl.textContent = '00:00'; }

  async function initPeer(){
    pc = new RTCPeerConnection({ iceServers:[{urls:'stun:stun.l.google.com:19302'}] });
    pc.onicecandidate = async (ev)=>{
      if (ev.candidate && callId) {
        await fetch(site + 'calls/signal/' + callId, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ type:'ice', payload: JSON.stringify(ev.candidate) }) });
      }
    };
    pc.ontrack = (ev)=>{
      remoteVideo.srcObject = ev.streams[0];
      try { remoteVideo.play && remoteVideo.play(); } catch(e) {}
      syncOverlayStreams();
    };
    if (!localStream) {
      localStream = await navigator.mediaDevices.getUserMedia({ video:true, audio:true });
      localVideo.srcObject = localStream;
      try { originalVideoTrack = localStream.getVideoTracks()[0] || null; } catch(e) { originalVideoTrack = null; }
    }
    localStream.getTracks().forEach(t=>pc.addTrack(t, localStream));

    // Enable mic control once media is ready
    btnToggleMic.disabled = false;
    // Enable speaker control
    btnToggleSpeaker.disabled = false;
    remoteVideo.muted = false;
    btnShareScreen.disabled = false;
    btnRecord.disabled = false;
    btnFullscreen && (btnFullscreen.disabled = false);
    cameraEnabled = true;
    syncOverlayStreams();
  }

  async function startCall(){
    if (!convoId) return;
    try {
      setStatus('Starting call...');
      const r = await fetch(site + 'calls/start/' + convoId, { method:'POST' });
      if (handleUnauthorized(r)) return;
      const j = await parseJsonSafe(r); if (!j || !j.ok) throw new Error('start failed');
      callId = j.call_id; signalSince = 0; btnEndCall.disabled = false;
      await initPeer();
      const offer = await pc.createOffer();
      await pc.setLocalDescription(offer);
      const rs = await fetch(site + 'calls/signal/' + callId, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ type:'offer', payload: JSON.stringify(offer) }) });
      if (handleUnauthorized(rs)) return;
      setStatus('Waiting for answer...');
      if (signalTimer) clearInterval(signalTimer);
      signalTimer = setInterval(pollSignals, 2000);
      setCallToggleUI(true);
      if (btnEndCall) btnEndCall.classList.add('d-none');
      startRinging('out');
      syncOverlayStreams();
    } catch(e){ setStatus('Call failed: '+e.message); }
  }

  let pendingRemoteOffer = null;
  async function handleSignal(sig){
    if (sig.type==='offer') {
      // Ignore our own offer so caller doesn't see Accept/Reject on their device
      try { if (parseInt(sig.from_user_id||0,10) === userId) { return; } } catch(e){}
      // Incoming call: prompt accept/reject, play ring
      try {
        pendingRemoteOffer = sig.payload;
        setStatus('Incoming call...');
        if (btnAcceptCall) btnAcceptCall.classList.remove('d-none');
        if (btnRejectCall) btnRejectCall.classList.remove('d-none');
        if (btnCallToggle) btnCallToggle.classList.add('d-none');
        startRinging('in');
      } catch(e){}
    } else if (sig.type==='answer') {
      try { await pc.setRemoteDescription(new RTCSessionDescription(JSON.parse(sig.payload))); } catch(e){ return; }
      setStatus('Connected');
      stopRinging();
    } else if (sig.type==='ice') {
      try { await pc.addIceCandidate(new RTCIceCandidate(JSON.parse(sig.payload))); } catch(e){}
    }
  }

  async function pollSignals(){
    if (!callId) return;
    try {
      const url = new URL(site + 'calls/poll/' + callId);
      url.searchParams.set('since_id', signalSince);
      const r = await fetch(url);
      if (handleUnauthorized(r)) return;
      const j = await parseJsonSafe(r);
      if (j.ok && j.signals) { j.signals.forEach(s=>{ signalSince = Math.max(signalSince, parseInt(s.id,10)); handleSignal(s); }); }
    } catch(e){}
  }

  async function endCall(){
    if (callId) { try { const re = await fetch(site + 'calls/end/' + callId, { method:'POST' }); if (handleUnauthorized(re)) return; } catch(e){} }
    if (pc) { pc.getSenders().forEach(s=>{ try { s.track && s.track.stop(); } catch(e){} }); pc.close(); pc=null; }
    callId = null; btnEndCall.disabled = true; setStatus('Idle');
    if (signalTimer) clearInterval(signalTimer);
    if (incomingTimer) clearInterval(incomingTimer);
    stopRinging();
    // Reset mic state
    btnToggleMic.disabled = true;
    btnToggleMic.innerHTML = '<i class="bi bi-mic"></i>';
    if (btnOverlayMic) btnOverlayMic.innerHTML = '<i class="bi bi-mic"></i>';
    // Reset speaker state
    btnToggleSpeaker.disabled = true;
    btnToggleSpeaker.innerHTML = '<i class="bi bi-volume-up"></i>';
    // Stop screen share if active and restore camera
    try { await stopScreenShare(); } catch(e) {}
    // Stop recording if active
    try { if (isRecording) await stopRecording(); } catch(e) {}
    btnShareScreen.disabled = true;
    btnRecord.disabled = true;
    setCallToggleUI(false);
    setOverlayVisible(false);
    if (overlayRemoteVideo) overlayRemoteVideo.srcObject = null;
    if (overlayRemoteThumb) overlayRemoteThumb.srcObject = null;
    if (overlayLocalVideo) overlayLocalVideo.srcObject = null;
    if (overlayScreenVideo) overlayScreenVideo.srcObject = null;
    // Restore header controls
    if (btnAcceptCall) btnAcceptCall.classList.add('d-none');
    if (btnRejectCall) btnRejectCall.classList.add('d-none');
    if (btnCallToggle) btnCallToggle.classList.remove('d-none');
  }

  if (btnCallToggle) btnCallToggle.addEventListener('click', ()=>{ if (callId) endCall(); else startCall(); });
  if (btnAcceptCall) btnAcceptCall.addEventListener('click', async ()=>{
    try {
      if (!pendingRemoteOffer) return;
      if (!pc) await initPeer();
      await pc.setRemoteDescription(new RTCSessionDescription(JSON.parse(pendingRemoteOffer)));
      const answer = await pc.createAnswer();
      await pc.setLocalDescription(answer);
      const ra = await fetch(site + 'calls/signal/' + callId, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ type:'answer', payload: JSON.stringify(answer) }) });
      if (handleUnauthorized(ra)) return;
      setStatus('Connected');
      stopRinging();
      if (signalTimer) { clearInterval(signalTimer); }
      signalTimer = setInterval(pollSignals, 2000);
      syncOverlayStreams();
      setCallToggleUI(true);
      if (btnEndCall) btnEndCall.classList.add('d-none');
      if (!overlayOpen) { setOverlayVisible(true); }
    } catch(e) { setStatus('Accept failed'); }
    finally {
      pendingRemoteOffer = null;
      if (btnAcceptCall) btnAcceptCall.classList.add('d-none');
      if (btnRejectCall) btnRejectCall.classList.add('d-none');
      if (btnCallToggle) btnCallToggle.classList.remove('d-none');
    }
  });
  if (btnRejectCall) btnRejectCall.addEventListener('click', async ()=>{
    try {
      stopRinging();
      setStatus('Call rejected');
      await endCall();
    } finally {
      pendingRemoteOffer = null;
      if (btnAcceptCall) btnAcceptCall.classList.add('d-none');
      if (btnRejectCall) btnRejectCall.classList.add('d-none');
      if (btnCallToggle) btnCallToggle.classList.remove('d-none');
    }
  });
  if (btnEndCall) btnEndCall.addEventListener('click', endCall);
  btnToggleMic.addEventListener('click', ()=>{
    if (!localStream) return;
    const audioTracks = localStream.getAudioTracks();
    if (!audioTracks || audioTracks.length === 0) return;
    const enabled = audioTracks[0].enabled;
    audioTracks.forEach(t => t.enabled = !enabled);
    btnToggleMic.innerHTML = enabled ? '<i class="bi bi-mic-mute"></i>' : '<i class="bi bi-mic"></i>';
    if (btnOverlayMic) btnOverlayMic.innerHTML = enabled ? '<i class="bi bi-mic-mute"></i>' : '<i class="bi bi-mic"></i>';
  });
  // Speaker toggle controls remote audio playback
  btnToggleSpeaker.addEventListener('click', ()=>{
    const muted = !!remoteVideo.muted;
    remoteVideo.muted = !muted;
    btnToggleSpeaker.innerHTML = muted ? '<i class="bi bi-volume-up"></i>' : '<i class="bi bi-volume-mute"></i>';
    if (btnOverlayMinimize) {
      const newIcon = muted ? '<i class="bi bi-volume-up"></i>' : '<i class="bi bi-volume-mute"></i>';
      try { btnOverlayMinimize.dataset.volumeIcon = newIcon; } catch(e){}
    }
  });

  function toggleMic(){
    if (!localStream) return;
    const audioTracks = localStream.getAudioTracks();
    if (!audioTracks || audioTracks.length === 0) return;
    const enabled = audioTracks[0].enabled;
    audioTracks.forEach(t => t.enabled = !enabled);
    const micOn = !enabled;
    btnToggleMic.innerHTML = micOn ? '<i class="bi bi-mic"></i>' : '<i class="bi bi-mic-mute"></i>';
    if (btnOverlayMic) btnOverlayMic.innerHTML = micOn ? '<i class="bi bi-mic"></i>' : '<i class="bi bi-mic-mute"></i>';
  }
  btnToggleMic.addEventListener('click', toggleMic);

  function toggleCamera(){
    if (!localStream) return;
    const videoTracks = localStream.getVideoTracks();
    if (!videoTracks || videoTracks.length === 0) return;
    cameraEnabled = !cameraEnabled;
    videoTracks.forEach(t => t.enabled = cameraEnabled);
    if (!cameraEnabled && !screenStream) {
      localVideo.srcObject = null;
    } else if (cameraEnabled && !screenStream) {
      localVideo.srcObject = localStream;
    }
    const icon = cameraEnabled ? '<i class="bi bi-camera-video"></i>' : '<i class="bi bi-camera-video-off"></i>';
    if (btnOverlayCamera) btnOverlayCamera.innerHTML = icon;
    syncOverlayStreams();
  }

  if (btnFullscreen) {
    btnFullscreen.addEventListener('click', ()=>{
      if (!callId) return;
      setOverlayVisible(!overlayOpen);
    });
  }
  if (btnOverlayMinimize) btnOverlayMinimize.addEventListener('click', ()=> setOverlayVisible(false));
  if (btnOverlayClose) btnOverlayClose.addEventListener('click', ()=> setOverlayVisible(false));
  if (btnOverlayScreen) btnOverlayScreen.addEventListener('click', async ()=>{
    if (!pc) return;
    if (screenStream) { await stopScreenShare(); }
    else { await startScreenShare(); }
  });
  if (btnOverlayMic) btnOverlayMic.addEventListener('click', toggleMic);
  if (btnOverlayCamera) btnOverlayCamera.addEventListener('click', toggleCamera);
  if (btnOverlayLeave) btnOverlayLeave.addEventListener('click', endCall);

  // Bind convo selection (guard for pages without convoList)
  if (convoList) {
    convoList.addEventListener('click', (e)=>{
      const btn = e.target.closest('.convo-item');
      if (btn) selectConvo(btn);
    });
    // Auto-select preferred conversation: try data-initial-id, else the first item
    const autoSelectFirst = ()=>{
      try {
        const initialId = parseInt(convoList.dataset.initialId||'0',10);
        if (initialId) {
          focusConversationById(initialId);
        }
        // If still none active, pick the first visible item
        const active = convoList.querySelector('.convo-item.active');
        if (!active) {
          const first = convoList.querySelector('.convo-item');
          if (first) selectConvo(first);
        }
      } catch(e){}
    };
    // Run now, and retry a few times to cover timing
    autoSelectFirst();
    setTimeout(autoSelectFirst, 200);
    setTimeout(autoSelectFirst, 600);
    setTimeout(()=>{
      // Final safeguard: if still no selection, click the first item
      try {
        if (!convoId) {
          const first = convoList.querySelector('.convo-item');
          if (first) first.click();
        }
      } catch(e){}
    }, 1000);
    // Also try on DOMContentLoaded as a fallback
    document.addEventListener('DOMContentLoaded', autoSelectFirst);
  }

  // Reminder: send an email reminder via Mail controller
  if (btnReminder) {
    btnReminder.addEventListener('click', async ()=>{
      try {
        if (!convoId) return;
        const to = prompt('Enter recipient email address for reminder:');
        if (!to) return;
        const subject = 'Reminder for conversation #' + convoId;
        const message = 'This is a reminder related to conversation #' + convoId + '.\n\nSent at ' + new Date().toLocaleString();
        const fd = new FormData();
        fd.append('to', to);
        fd.append('subject', subject);
        fd.append('message', message);
        const res = await fetch(site + 'mail/send', { method:'POST', body: fd });
        if (res.redirected || res.ok) {
          try { toastTitleEl.textContent = 'Reminder sent'; toastBodyEl.textContent = 'Email sent to ' + to; toastInstance && toastInstance.show(); } catch(e){}
        }
      } catch(e){ console.warn('Reminder failed', e); }
    });
  }
})();
</script>
<?php $this->load->view('partials/footer'); ?>
