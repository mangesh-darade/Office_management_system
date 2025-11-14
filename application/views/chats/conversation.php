<?php $this->load->view('partials/header', ['title' => 'Conversation']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h1 class="h5 mb-0">Conversation #<?php echo (int)$conversation->id; ?>
      <?php if ($conversation->type === 'group'): ?>
        <small class="text-muted">— <?php echo htmlspecialchars($conversation->title ?: 'Group'); ?></small>
      <?php endif; ?>
    </h1>
    <div class="small text-muted">Participants:
      <?php foreach ($participants as $p):
        $n = $p->email;
        if (isset($p->name) && $p->name !== '') { $n = $p->name.' ('.$p->email.')'; }
        if (isset($p->full_name) && $p->full_name !== '') { $n = $p->full_name.' ('.$p->email.')'; }
      ?>
        <span class="badge bg-light text-dark me-1 mb-1"> <?php echo htmlspecialchars($n); ?> </span>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="d-flex gap-2">
    <button id="btnToggleMic" class="btn btn-outline-secondary btn-sm" disabled title="Toggle Microphone"><i class="bi bi-mic"></i></button>
    <button id="btnToggleSpeaker" class="btn btn-outline-secondary btn-sm" disabled title="Toggle Speakers"><i class="bi bi-volume-up"></i></button>
    <button id="btnShareScreen" class="btn btn-outline-success btn-sm d-none"><i class="bi bi-display"></i> Share Screen</button>
    <button id="btnStartCall" class="btn btn-outline-primary btn-sm"><i class="bi bi-camera-video"></i> Start Call</button>
    <a class="btn btn-light btn-sm" href="<?php echo site_url('chats'); ?>"><i class="bi bi-arrow-left"></i></a>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-8">
    <div class="card h-100">
      <div id="messages" class="card-body" style="height: 520px; overflow-y: auto;">
        <?php $last_id = 0; if (empty($messages)): ?>
          <div class="text-center text-muted mt-5">No messages yet. Say hello 👋</div>
        <?php endif; foreach ($messages as $m): $last_id = (int)$m->id; ?>
          <?php
            $isMe = ($m->sender_id == $user_id);
            $name = (isset($m->name) && $m->name) ? $m->name : $m->email;
            if (isset($m->full_name) && $m->full_name) { $name = $m->full_name; }
            $time = !empty($m->created_at) ? date('Y-m-d H:i', strtotime($m->created_at)) : '';
          ?>
          <div class="mb-3 <?php echo $isMe ? 'text-end' : '';?>">
            <div class="small text-muted mb-1"><?php echo htmlspecialchars($name); ?> · <?php echo htmlspecialchars($time); ?></div>
            <?php if (!empty($m->body)): ?>
              <div class="d-inline-block px-3 py-2 rounded <?php echo $isMe ? 'bg-primary text-white' : 'bg-light border'; ?>" style="max-width: 85%; text-align: left;">
                <?php echo strip_tags($m->body, '<p><br><strong><em><b><i><ul><ol><li><a>'); ?>
              </div>
            <?php endif; ?>
            <?php if (!empty($m->attachment_path)): ?>
              <div class="mt-1">
                <a class="btn btn-sm btn-outline-secondary" target="_blank" href="<?php echo site_url($m->attachment_path); ?>">
                  <i class="bi bi-paperclip"></i> Attachment
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="card-footer">
        <form id="sendForm" class="row g-2 align-items-center" enctype="multipart/form-data">
          <input type="hidden" name="conversation_id" value="<?php echo (int)$conversation->id; ?>">
          <div class="col-12 col-md-8">
            <textarea name="body" class="form-control" rows="2" placeholder="Type a message... (Enter to send)"></textarea>
          </div>
          <div class="col-6 col-md-3">
            <input class="form-control" type="file" name="attachment" accept="image/*,.pdf,.doc,.docx">
          </div>
          <div class="col-6 col-md-1 d-grid">
            <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i></button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold">Video Call</div>
      <div class="card-body">
        <div class="ratio ratio-16x9 bg-dark mb-2 rounded"><video id="remoteVideo" autoplay playsinline style="width:100%; height:100%;"></video></div>
        <div class="ratio ratio-16x9 bg-secondary mb-2 rounded"><video id="localVideo" autoplay playsinline muted style="width:100%; height:100%;"></video></div>
        <div class="d-flex gap-2">
          <button id="btnEndCall" class="btn btn-outline-danger btn-sm" disabled><i class="bi bi-telephone-x"></i> End</button>
        </div>
        <div id="callStatus" class="small text-muted mt-2">Idle</div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const convoId = <?php echo (int)$conversation->id; ?>;
  let lastId = <?php echo (int)$last_id; ?>;
  const messagesEl = document.getElementById('messages');
  const form = document.getElementById('sendForm');

  function appendMessage(m) {
    const wrap = document.createElement('div');
    wrap.className = 'mb-3' + (m.sender_id == <?php echo (int)$user_id; ?> ? ' text-end' : '');
    const meta = document.createElement('div');
    meta.className = 'small text-muted mb-1';
    meta.textContent = ((m.full_name || m.name || m.email || 'User')) + ' · ' + (m.created_at || '');
    wrap.appendChild(meta);
    if (m.body) {
      const body = document.createElement('div');
      const isMe = (m.sender_id == <?php echo (int)$user_id; ?>);
      body.className = 'd-inline-block px-3 py-2 rounded ' + (isMe ? 'bg-primary text-white' : 'bg-light border');
      body.style.maxWidth = '85%';
      body.style.textAlign = 'left';
      body.innerHTML = m.body; // server sanitizes to allowed tags
      wrap.appendChild(body);
    }
    if (m.attachment_path) {
      const a = document.createElement('a');
      a.className = 'btn btn-sm btn-outline-secondary mt-1';
      a.target = '_blank';
      a.href = '<?php echo site_url(); ?>' + m.attachment_path;
      a.innerHTML = '<i class="bi bi-paperclip"></i> Attachment';
      wrap.appendChild(a);
    }
    messagesEl.appendChild(wrap);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  // Polling for new messages
  setInterval(async () => {
    try {
      const url = new URL('<?php echo site_url('chats/fetch'); ?>');
      url.searchParams.set('conversation_id', convoId);
      url.searchParams.set('since_id', lastId);
      const res = await fetch(url);
      const data = await res.json();
      if (data.ok && data.messages) {
        data.messages.forEach(m => { lastId = Math.max(lastId, parseInt(m.id,10)); appendMessage(m); });
      }
    } catch(e) { /* silent */ }
  }, 2500);

  // Send message
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(form);
    const res = await fetch('<?php echo site_url('chats/send'); ?>', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) { form.reset(); }
  });

  // Simple WebRTC over AJAX signaling
  let pc, localStream, callId = null, signalSince = 0;
  const localVideo = document.getElementById('localVideo');
  const remoteVideo = document.getElementById('remoteVideo');
  const btnStartCall = document.getElementById('btnStartCall');
  const btnEndCall = document.getElementById('btnEndCall');
  const btnToggleMic = document.getElementById('btnToggleMic');
  const btnToggleSpeaker = document.getElementById('btnToggleSpeaker');
  const callStatus = document.getElementById('callStatus');

  function setStatus(s){ callStatus.textContent = s; }

  async function initPeer(){
    pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
    pc.onicecandidate = async (ev) => {
      if (ev.candidate && callId) {
        await fetch('<?php echo site_url('calls/signal/'); ?>'+callId, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: new URLSearchParams({ type:'ice', payload: JSON.stringify(ev.candidate) }) });
      }
    };
    pc.ontrack = (ev) => {
      remoteVideo.srcObject = ev.streams[0];
      try { remoteVideo.play && remoteVideo.play(); } catch(e) {}
    };
    if (!localStream) {
      localStream = await navigator.mediaDevices.getUserMedia({ video:true, audio:true });
      localVideo.srcObject = localStream;
    }
    localStream.getTracks().forEach(t => pc.addTrack(t, localStream));
    // enable audio controls
    btnToggleMic.disabled = false;
    btnToggleSpeaker.disabled = false;
    remoteVideo.muted = false;
  }

  async function startCall(){
    try {
      setStatus('Starting call...');
      const startRes = await fetch('<?php echo site_url('calls/start/'); ?>'+convoId, { method:'POST' });
      const startData = await startRes.json();
      if (!startData.ok) throw new Error('Cannot start call');
      callId = startData.call_id; signalSince = 0; btnEndCall.disabled = false;
      await initPeer();
      const offer = await pc.createOffer();
      await pc.setLocalDescription(offer);
      await fetch('<?php echo site_url('calls/signal/'); ?>'+callId, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({ type:'offer', payload: JSON.stringify(offer) }) });
      setStatus('Waiting for answer...');
    } catch(e){ setStatus('Call failed: '+e.message); }
  }

  async function handleSignal(sig){
    if (!pc) await initPeer();
    if (sig.type === 'offer') {
      await pc.setRemoteDescription(new RTCSessionDescription(JSON.parse(sig.payload)));
      const answer = await pc.createAnswer();
      await pc.setLocalDescription(answer);
      await fetch('<?php echo site_url('calls/signal/'); ?>'+callId, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams({ type:'answer', payload: JSON.stringify(answer) }) });
      setStatus('Connected');
    } else if (sig.type === 'answer') {
      await pc.setRemoteDescription(new RTCSessionDescription(JSON.parse(sig.payload)));
      setStatus('Connected');
    } else if (sig.type === 'ice') {
      try { await pc.addIceCandidate(new RTCIceCandidate(JSON.parse(sig.payload))); } catch(e) {}
    }
  }

  async function pollSignals(){
    if (!callId) return;
    try {
      const url = new URL('<?php echo site_url('calls/poll/'); ?>'+callId);
      url.searchParams.set('since_id', signalSince);
      const res = await fetch(url);
      const data = await res.json();
      if (data.ok && data.signals) {
        data.signals.forEach(s => { signalSince = Math.max(signalSince, parseInt(s.id,10)); handleSignal(s); });
      }
    } catch(e) { /* ignore */ }
  }
  setInterval(pollSignals, 2000);

  async function endCall(){
    if (callId) { await fetch('<?php echo site_url('calls/end/'); ?>'+callId, { method:'POST' }); }
    if (pc) { pc.getSenders().forEach(s => { try{ s.track && s.track.stop(); }catch(e){} }); pc.close(); pc = null; }
    callId = null; btnEndCall.disabled = true; setStatus('Idle');
    // reset controls
    btnToggleMic.disabled = true; btnToggleMic.innerHTML = '<i class="bi bi-mic"></i>';
    btnToggleSpeaker.disabled = true; btnToggleSpeaker.innerHTML = '<i class="bi bi-volume-up"></i>';
  }

  btnStartCall.addEventListener('click', startCall);
  btnEndCall.addEventListener('click', endCall);
  btnToggleMic.addEventListener('click', ()=>{
    if (!localStream) return;
    const tracks = localStream.getAudioTracks();
    if (!tracks || tracks.length === 0) return;
    const enabled = tracks[0].enabled;
    tracks.forEach(t=> t.enabled = !enabled);
    btnToggleMic.innerHTML = enabled ? '<i class="bi bi-mic-mute"></i>' : '<i class="bi bi-mic"></i>';
  });
  btnToggleSpeaker.addEventListener('click', ()=>{
    const muted = !!remoteVideo.muted;
    remoteVideo.muted = !muted;
    btnToggleSpeaker.innerHTML = muted ? '<i class="bi bi-volume-up"></i>' : '<i class="bi bi-volume-mute"></i>';
  });
})();
</script>
<?php $this->load->view('partials/footer'); ?>
