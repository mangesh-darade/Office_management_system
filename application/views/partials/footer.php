<?php
$embed = isset($embed) ? (bool)$embed : (isset($_GET['embed']) ? (bool)$_GET['embed'] : (bool)$this->input->get('embed'));
if ($embed) {
    return;
}
?>  <?php if (isset($__with_sidebar) && $__with_sidebar): ?>
    </main>
  </div>
</div>
<?php else: ?>
  <?php if (isset($__full_width) && !($__full_width)): ?>
    </div>
  <?php endif; ?>
</main>
<?php endif; ?>

<!-- AI Chat Widget Overlay (only show when user has AI permission) -->
<?php
$__can_ai_widget = function_exists('has_module_access') && (
    has_module_access('ai_widget') ||
    has_module_access('ai') ||
    has_module_access('ai_chat')
);
?>
<?php if ($__can_ai_widget): ?>
<style>
.ai-float-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 65px;
  height: 65px;
  border-radius: 50%;
  z-index: 9999;
  box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4);
  transition: transform 0.1s ease, box-shadow 0.3s ease; /* Faster transform for dragging */
  background: white;
  border: 4px solid white;
  padding: 0;
  overflow: hidden;
  animation: pulse-border 2s infinite;
  cursor: grab;
  touch-action: none; /* Prevent scrolling while dragging */
}
.ai-float-btn:active {
  cursor: grabbing;
  transform: scale(0.95);
}
.ai-float-btn.dragging {
  animation: none;
  transition: none;
}
@keyframes pulse-border {
  0% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0.7); }
  70% { box-shadow: 0 0 0 15px rgba(102, 126, 234, 0); }
  100% { box-shadow: 0 0 0 0 rgba(102, 126, 234, 0); }
}

.ai-chat-window {
  position: fixed;
  bottom: 110px;
  right: 30px;
  width: 400px; /* Slightly wider default */
  height: 600px;
  border-radius: 16px;
  z-index: 9999;
  display: none !important; /* Hidden by default, override flex */
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  border: 1px solid rgba(255,255,255,0.5);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  transition: opacity 0.3s ease, transform 0.3s ease, width 0.3s ease, height 0.3s ease, border-radius 0.3s ease;
}
.ai-chat-window.show {
  display: flex !important;
  animation: popUp 0.3s ease-out forwards;
}
/* Full Screen Mode */
.ai-chat-window.fullscreen {
  width: 100vw !important;
  height: 100vh !important;
  bottom: 0 !important;
  right: 0 !important;
  top: 0 !important;
  left: 0 !important;
  border-radius: 0 !important;
  transform: none !important;
}

@keyframes popUp {
  from { opacity: 0; transform: translateY(20px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

.user-msg-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 18px 18px 4px 18px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.ai-msg-bubble {
    background: #ffffff;
    color: #333;
    border-radius: 18px 18px 18px 4px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    border: 1px solid #f0f0f0;
}
.draggable-header {
    cursor: move;
}
</style>

<div id="aiChatWindow" class="ai-chat-window text-start font-sans">
  <div class="card-header bg-gradient bg-primary text-white p-3 d-flex justify-content-between align-items-center draggable-header" id="aiChatHeader" style="background: linear-gradient(to right, #4facfe 0%, #00f2fe 100%); user-select: none;">
     <div class="d-flex align-items-center">
        <div class="bg-white bg-opacity-25 rounded-circle p-2 me-2">
            <i class="bi bi-robot fs-5 text-white"></i>
        </div>
        <div>
            <h6 class="mb-0 fw-bold text-white">Office AI Assistant</h6>
            <small class="text-white text-opacity-75" style="font-size: 0.75rem;">Powered by Gemini</small>
        </div>
     </div>
     <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-link text-white p-0" onclick="aiWidgetNewChat()" title="New chat">
            <i class="bi bi-plus-lg"></i>
        </button>
        <button type="button" class="btn btn-sm btn-link text-white p-0" onclick="aiWidgetClearChat()" title="Clear history">
            <i class="bi bi-trash"></i>
        </button>
        <button type="button" class="btn btn-sm btn-link text-white p-0" onclick="toggleFullScreen()" title="Toggle Fullscreen">
            <i class="bi bi-arrows-fullscreen" id="fsIcon"></i>
        </button>
        <button type="button" class="btn-close btn-close-white" onclick="toggleAiWidget()" aria-label="Close"></button>
     </div>
  </div>
  <div class="card-body bg-light bg-opacity-50 p-3 flex-grow-1 overflow-auto" id="widgetChatBody" style="scroll-behavior: smooth;">
     <div class="d-flex flex-row justify-content-start mb-3">
        <div class="p-3 ai-msg-bubble shadow-sm text-dark" style="max-width: 85%;">
           <small>Hi there! 👋 I'm your AI assistant. Ask me anything about employees, projects, tasks, or attendance!</small>
        </div>
     </div>
  </div>
  <div class="card-footer bg-white p-3 border-top">
     <form id="widgetChatForm" onsubmit="handleWidgetSubmit(event)" class="d-flex gap-2 align-items-center">
        <input type="text" id="widgetInput" class="form-control form-control-sm rounded-pill px-3 py-2 bg-light border-0" placeholder="Type your question..." autocomplete="off">
        <button type="button" class="btn btn-outline-secondary rounded-circle shadow-sm" id="widgetSpeakBtn" title="Speak last AI reply" style="width: 36px; height: 36px; padding: 0; display: flex; align-items: center; justify-content: center;">
          <i class="bi bi-volume-up" style="font-size: 0.9rem;"></i>
        </button>
        <button type="submit" class="btn btn-primary rounded-circle shadow-sm" style="width: 40px; height: 40px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="bi bi-send-fill" style="font-size: 0.9rem; margin-left: 2px;"></i></button>
     </form>
     <audio id="widgetTtsAudio" style="display:none;"></audio>
  </div>
</div>

<button id="aiFloatBtn" class="ai-float-btn d-flex align-items-center justify-content-center p-0" onclick="handleBtnClick()">
  <img src="https://api.dicebear.com/7.x/bottts/svg?seed=OfficeAI&backgroundColor=transparent" alt="AI Avatar" style="width: 100%; height: 100%; object-fit: cover; pointer-events: none;">
</button>

<script>
// Escape HTML entities to prevent XSS when inserting user input
function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
// Sanitize AI response: remove script tags but allow markdown/HTML formatting
function sanitizeAiResponse(str) {
    if (!str || typeof str !== 'string') return '';
    var div = document.createElement('div');
    div.innerHTML = str;
    div.querySelectorAll('script,iframe,object,embed,link,meta,style,form').forEach(function(n) { n.remove(); });
    div.querySelectorAll('*').forEach(function(el) {
        Array.prototype.slice.call(el.attributes).forEach(function(attr) {
            var name = attr.name.toLowerCase();
            var val = (attr.value || '').toLowerCase();
            if (name.indexOf('on') === 0 || name === 'srcdoc' || (name === 'href' && val.indexOf('javascript:') === 0)
                || ((name === 'src' || name === 'xlink:href') && val.indexOf('javascript:') === 0)) {
                el.removeAttribute(attr.name);
            }
        });
    });
    return div.innerHTML;
}
// UI State management
let isAiOpen = false;
let isFullScreen = false;
let dragged = false;

function toggleAiWidget() {
   if(dragged) { dragged = false; return; } // Prevent toggle if it was a drag gesture
   
   const win = document.getElementById('aiChatWindow');
   isAiOpen = !isAiOpen;
   
   if(isAiOpen) {
       win.classList.add('show');
       // If standard mode, position relative to button or default
       if(!isFullScreen) {
           // Provide basic default reset if needed or keep last dragged position
       }
       setTimeout(() => document.getElementById('widgetInput').focus(), 100);
   } else {
       win.classList.remove('show');
   }
}

function toggleFullScreen() {
    const win = document.getElementById('aiChatWindow');
    const icon = document.getElementById('fsIcon');
    isFullScreen = !isFullScreen;
    
    if (isFullScreen) {
        win.classList.add('fullscreen');
        icon.classList.remove('bi-arrows-fullscreen');
        icon.classList.add('bi-fullscreen-exit');
        // Reset transform when fullscreen to fill screen properly
        win.style.transform = 'translate(0,0)'; 
        win.style.top = '0px';
        win.style.left = '0px';
    } else {
        win.classList.remove('fullscreen');
        icon.classList.remove('bi-fullscreen-exit');
        icon.classList.add('bi-arrows-fullscreen');
        // Reset to default corner position or last dragged position could be complex, 
        // for simple UX reset to bottom right area
        win.style.top = '';
        win.style.left = '';
        win.style.bottom = '110px';
        win.style.right = '30px';
        win.style.transform = 'translate(0,0)';
    }
}

// Draggable Logic
function makeDraggable(element, handle = null) {
    let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
    const dragHandle = handle || element;

    dragHandle.onmousedown = dragMouseDown;
    dragHandle.ontouchstart = dragMouseDown;

    function dragMouseDown(e) {
        if(isFullScreen && element.id === 'aiChatWindow') return; // Disable drag in full screen

        // e.preventDefault(); // Don't prevent default on touch start immediately or input focus breaks
        
        let clientX = e.clientX || e.touches[0].clientX;
        let clientY = e.clientY || e.touches[0].clientY;
        
        pos3 = clientX;
        pos4 = clientY;
        
        document.onmouseup = closeDragElement;
        document.onmousemove = elementDrag;
        document.ontouchend = closeDragElement;
        document.ontouchmove = elementDrag;
        
        // Mark as dragging if moved significantly
        element.dataset.startX = clientX;
        element.dataset.startY = clientY;
    }

    function elementDrag(e) {
        // e.preventDefault();
        let clientX = e.clientX || (e.touches ? e.touches[0].clientX : 0);
        let clientY = e.clientY || (e.touches ? e.touches[0].clientY : 0);

        pos1 = pos3 - clientX;
        pos2 = pos4 - clientY;
        pos3 = clientX;
        pos4 = clientY;

        // Calculate new position
        let newTop = (element.offsetTop - pos2);
        let newLeft = (element.offsetLeft - pos1);
        
        // Boundary checks (keep within window)
        const maxTop = window.innerHeight - element.offsetHeight;
        const maxLeft = window.innerWidth - element.offsetWidth;
        
        if(newTop < 0) newTop = 0;
        if(newLeft < 0) newLeft = 0;
        if(newTop > maxTop) newTop = maxTop;
        if(newLeft > maxLeft) newLeft = maxLeft;

        element.style.top = newTop + "px";
        element.style.left = newLeft + "px";
        element.style.bottom = "auto"; 
        element.style.right = "auto";
        element.classList.add('dragging');
        
        // Detect if it was a real drag vs a click
        if(Math.abs(clientX - element.dataset.startX) > 5 || Math.abs(clientY - element.dataset.startY) > 5) {
            dragged = true;
        }
    }

    function closeDragElement() {
        document.onmouseup = null;
        document.onmousemove = null;
        document.ontouchend = null;
        document.ontouchmove = null;
        element.classList.remove('dragging');
        
        // Reset dragged flag after a short delay to allow click events to process if it wasn't a drag
        setTimeout(() => { dragged = false; }, 100); 
    }
}

// Initialize Dragging
document.addEventListener('DOMContentLoaded', () => {
   const floatBtn = document.getElementById('aiFloatBtn');
   const chatWin = document.getElementById('aiChatWindow');
   const chatHeader = document.getElementById('aiChatHeader');

   if (floatBtn) {
     makeDraggable(floatBtn);
   }
   if (chatWin && chatHeader) {
     makeDraggable(chatWin, chatHeader);
   }

   // In mobile view, clicking the sidebar AI Assistant link should open the popup instead of navigating
   const sidebarAiLink = document.getElementById('sidebarAiLink');
   if (sidebarAiLink && typeof toggleAiWidget === 'function') {
      sidebarAiLink.addEventListener('click', function(e) {
          // Treat <= 768px width as mobile/tablet
          if (window.innerWidth <= 768) {
              e.preventDefault();
              toggleAiWidget();
              // Optionally close the offcanvas menu if Bootstrap is available
              const offcanvasEl = document.getElementById('mobileSidebar');
              if (offcanvasEl && window.bootstrap && bootstrap.Offcanvas) {
                  const instance = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
                  instance.hide();
              }
          }
      });
   }
});

// Button click wrapper to handle "Click vs Drag" distinction
function handleBtnClick() {
    if(!dragged) {
        toggleAiWidget();
    }
}

let widgetLastAiResponseText = '';
let widgetCsrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
const widgetCsrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';

function aiWidgetResetBodyKeepWelcome() {
   const box = document.getElementById('widgetChatBody');
   if (!box) return;
   const kids = Array.prototype.slice.call(box.children);
   kids.forEach(function(el, idx) { if (idx > 0) el.remove(); });
   widgetLastAiResponseText = '';
}

async function aiWidgetNewChat() {
   const fd = new FormData();
   fd.append(widgetCsrfName, widgetCsrfToken);
   try {
      const res = await fetch('<?php echo site_url("ai-chat/new"); ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      if (data.csrf_token) widgetCsrfToken = data.csrf_token;
      if (data.status === 'success') aiWidgetResetBodyKeepWelcome();
   } catch (e) { console.error(e); }
}

async function aiWidgetClearChat() {
   if (!confirm('Clear all AI chat history?')) return;
   const fd = new FormData();
   fd.append(widgetCsrfName, widgetCsrfToken);
   try {
      const res = await fetch('<?php echo site_url("ai-chat/clear-history"); ?>', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      if (data.csrf_token) widgetCsrfToken = data.csrf_token;
      if (data.status === 'success') aiWidgetResetBodyKeepWelcome();
   } catch (e) { console.error(e); }
}

async function handleWidgetSubmit(e) {
   e.preventDefault();
   const input = document.getElementById('widgetInput');
   const box = document.getElementById('widgetChatBody');
   const text = input.value.trim();
   
   if(!text) return;
   
   const userDiv = document.createElement('div');
   userDiv.className = 'd-flex flex-row justify-content-end mb-3';
   userDiv.innerHTML = `<div class="p-3 user-msg-bubble shadow-sm" style="max-width: 85%;"><small>${escapeHtml(text).replace(/\n/g, '<br>')}</small></div>`;
   box.appendChild(userDiv);
   box.scrollTop = box.scrollHeight;
   
   input.value = '';
   
   const loadDiv = document.createElement('div');
   loadDiv.id = 'ai-loading-indicator';
   loadDiv.className = 'd-flex flex-row justify-content-start mb-3';
   loadDiv.innerHTML = `<div class="p-3 ai-msg-bubble shadow-sm"><div class="d-flex align-items-center gap-2"><div class="spinner-grow spinner-grow-sm text-secondary" role="status" style="width: 0.5rem; height: 0.5rem;"></div><div class="spinner-grow spinner-grow-sm text-secondary" role="status" style="width: 0.5rem; height: 0.5rem; animation-delay: 0.2s"></div><div class="spinner-grow spinner-grow-sm text-secondary" role="status" style="width: 0.5rem; height: 0.5rem; animation-delay: 0.4s"></div></div></div>`;
   box.appendChild(loadDiv);
   box.scrollTop = box.scrollHeight;

   try {
        const formData = new FormData();
        formData.append('message', text);
        formData.append(widgetCsrfName, widgetCsrfToken);

        const response = await fetch('<?php echo site_url("ai_chat/send_message"); ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        if (data.csrf_token) widgetCsrfToken = data.csrf_token;
        const loader = document.getElementById('ai-loading-indicator');
        if(loader) loader.remove();

        if (data.status === 'success') {
            const aiDiv = document.createElement('div');
            aiDiv.className = 'd-flex flex-row justify-content-start mb-3';
            let cleanResponse = data.response.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            cleanResponse = cleanResponse.replace(/\n/g, '<br>');
            cleanResponse = sanitizeAiResponse(cleanResponse);
            
            aiDiv.innerHTML = `<div class="p-3 ai-msg-bubble shadow-sm" style="max-width: 85%;"><small>${cleanResponse}</small></div>`;
            box.appendChild(aiDiv);

            const temp = document.createElement('div');
            temp.innerHTML = cleanResponse;
            widgetLastAiResponseText = temp.textContent || temp.innerText || '';
        } else {
             const errDiv = document.createElement('div');
            errDiv.className = 'd-flex flex-row justify-content-start mb-3';
            errDiv.innerHTML = `<div class="p-2 bg-danger text-white rounded shadow-sm" style="max-width: 85%;"><small>Error: ${escapeHtml(data.message || '')}</small></div>`;
            box.appendChild(errDiv);
        }
        box.scrollTop = box.scrollHeight;
   } catch(err) {
        const loader = document.getElementById('ai-loading-indicator');
        if(loader) loader.remove();
        
        const errDiv = document.createElement('div');
        errDiv.className = 'd-flex flex-row justify-content-start mb-3';
        errDiv.innerHTML = `<div class="p-2 bg-danger text-white rounded shadow-sm" style="max-width: 85%;"><small>Network Error. Please try again.</small></div>`;
        box.appendChild(errDiv);
        console.error(err);
   }
}

document.getElementById('widgetChatBody').addEventListener('click', function(e) {
   const btn = e.target.closest('.export-btn');
   if (!btn) return;
   e.preventDefault();
   const form = document.createElement('form');
   form.method = 'POST';
   form.action = '<?php echo site_url("ai-chat/export"); ?>';
   form.target = '_blank';
   form.style.display = 'none';
   [['data', btn.getAttribute('data-export-data')],
    ['format', btn.getAttribute('data-export-format')],
    ['query', btn.getAttribute('data-export-query')],
    [widgetCsrfName, widgetCsrfToken]
   ].forEach(function(pair) {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = pair[0];
      input.value = pair[1] || '';
      form.appendChild(input);
   });
   document.body.appendChild(form);
   form.submit();
   form.remove();
});

// Widget Speak button (TTS)
document.getElementById('widgetSpeakBtn').addEventListener('click', async function() {
   if (!widgetLastAiResponseText) {
      alert('No AI response to speak yet.');
      return;
   }

   const btn = this;
   const originalHtml = btn.innerHTML;
   btn.disabled = true;
   btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

   try {
      const formData = new FormData();
      formData.append('text', widgetLastAiResponseText);
      formData.append('language', 'en-US'); // Adjust or detect if needed

      const csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
      const csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';
      formData.append(csrfName, csrfHash);

      const response = await fetch('<?php echo site_url("ai_chat/tts"); ?>', {
          method: 'POST',
          body: formData,
          headers: {
              'X-Requested-With': 'XMLHttpRequest'
          }
      });

      const data = await response.json();
      if (data.status === 'success' && data.audio_url) {
          const audio = document.getElementById('widgetTtsAudio');
          audio.src = data.audio_url;
          audio.play();
      } else {
          // If Azure Speech is not configured, fallback to browser TTS if available
          if (data.message && data.message.indexOf('Azure Speech is not configured') !== -1 && 'speechSynthesis' in window) {
              const utter = new SpeechSynthesisUtterance(widgetLastAiResponseText);
              window.speechSynthesis.speak(utter);
          } else {
              alert(data.message || 'Unable to generate speech.');
          }
      }
   } catch (err) {
      console.error(err);
      alert('Network error while generating speech.');
   } finally {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
   }
});
 </script>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.0.7/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.0.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.2/js/responsive.bootstrap5.min.js"></script>
<script src="<?php echo base_url('assets/js/form-validation.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/actual-hours-complete.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/app.js'); ?>"></script>
<?php $this->load->view('partials/actual_hours_modal'); ?>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function(){
      var swUrl = '<?php echo base_url('sw.js'); ?>';
      var swScope = '<?php echo base_url(); ?>';
      navigator.serviceWorker.register(swUrl, { scope: swScope })
        .then(function(reg){
          if (/localhost|127\.0\.0\.1/i.test(window.location.hostname)) {
            console.info('[Portal] Service worker OK, scope:', reg.scope);
          }
        })
        .catch(function(e){ console.warn('[Portal] SW registration failed:', e); });
    });
  }
</script>
<?php if ($this->session->userdata('user_id')): ?>
<script>
(function(){
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;

  var pushVapidUrl = '<?php echo site_url('push/vapid-public'); ?>';
  var pushSubscribeUrl = '<?php echo site_url('push/subscribe'); ?>';

  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    var raw = window.atob(base64);
    var output = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; ++i) {
      output[i] = raw.charCodeAt(i);
    }
    return output;
  }

  function subscribePortalPush() {
    if (!('Notification' in window) || Notification.permission !== 'granted') {
      return Promise.resolve(false);
    }
    return fetch(pushVapidUrl, { credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(cfg) {
        if (!cfg || !cfg.publicKey) return false;
        return navigator.serviceWorker.ready.then(function(reg) {
          return reg.pushManager.getSubscription().then(function(existing) {
            if (existing) return existing;
            return reg.pushManager.subscribe({
              userVisibleOnly: true,
              applicationServerKey: urlBase64ToUint8Array(cfg.publicKey)
            });
          });
        });
      })
      .then(function(sub) {
        if (!sub) return false;
        var j = sub.toJSON();
        var fd = new FormData();
        fd.append('endpoint', j.endpoint);
        fd.append('p256dh', j.keys.p256dh);
        fd.append('auth', j.keys.auth);
        return fetch(pushSubscribeUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function(r) { return r.json().then(function(j){ return j && j.ok; }); });
      })
      .then(function(ok) {
        if (ok && /localhost|127\.0\.0\.1/i.test(window.location.hostname)) {
          console.info('[Portal] Web Push subscribed — notifications work when tab is closed');
        }
        return !!ok;
      })
      .catch(function(e) {
        console.warn('[Portal] Push subscribe failed:', e);
        return false;
      });
  }

  window.portalRequestPushPermission = function() {
    if (!('Notification' in window)) return Promise.resolve(false);
    if (Notification.permission === 'granted') {
      return subscribePortalPush().then(function(ok){ return !!ok; });
    }
    if (Notification.permission === 'denied') {
      return Promise.resolve(false);
    }
    return Notification.requestPermission().then(function(perm) {
      if (perm === 'granted') {
        return subscribePortalPush().then(function(ok){ return !!ok; });
      }
      return false;
    });
  };

  window.addEventListener('load', function() {
    if (Notification.permission === 'granted') {
      setTimeout(subscribePortalPush, 2000);
    }
  });
})();
</script>
<?php endif; ?>
<?php if ($this->config->item('csrf_protection')): ?>
<script>
(function(){
  var csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
  var csrfHash = '<?php echo $this->security->get_csrf_hash(); ?>';

  function injectToken(form){
    if (!form || form.querySelector('input[name="'+csrfName+'"]')) return;
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = csrfName;
    input.value = csrfHash;
    form.appendChild(input);
  }

  var forms = document.querySelectorAll('form[method="post"], form[method="POST"]');
  for (var i = 0; i < forms.length; i++) { injectToken(forms[i]); }

  if (typeof MutationObserver !== 'undefined') {
    new MutationObserver(function(mutations){
      for (var i = 0; i < mutations.length; i++) {
        var nodes = mutations[i].addedNodes;
        for (var j = 0; j < nodes.length; j++) {
          if (nodes[j].nodeName === 'FORM') { injectToken(nodes[j]); }
          if (nodes[j].querySelectorAll) {
            var inner = nodes[j].querySelectorAll('form[method="post"], form[method="POST"]');
            for (var k = 0; k < inner.length; k++) { injectToken(inner[k]); }
          }
        }
      }
    }).observe(document.body, {childList: true, subtree: true});
  }

  var origOpen = XMLHttpRequest.prototype.open;
  var origSend = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open = function(method, url){
    this._csrfMethod = method;
    return origOpen.apply(this, arguments);
  };
  XMLHttpRequest.prototype.send = function(data){
    if (this._csrfMethod && this._csrfMethod.toUpperCase() === 'POST') {
      if (typeof data === 'string' && data.indexOf(csrfName) === -1) {
        data += (data ? '&' : '') + encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfHash);
      } else if (data instanceof FormData && !data.has(csrfName)) {
        data.append(csrfName, csrfHash);
      }
    }
    return origSend.call(this, data);
  };

  if (typeof jQuery !== 'undefined') {
    jQuery(document).ajaxSend(function(e, xhr, settings){
      if (settings.type && settings.type.toUpperCase() === 'POST' && settings.data) {
        if (typeof settings.data === 'string' && settings.data.indexOf(csrfName) === -1) {
          settings.data += '&' + encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfHash);
        }
      }
    });
  }

  // ── Global fetch() CSRF patch ─────────────────────────────────────────────
  // The XHR prototype patch above does NOT cover the native fetch() API.
  // This patch wraps fetch() so every same-origin POST automatically carries
  // the CSRF token — either in the body (form-encoded / FormData) or as a
  // custom header that CodeIgniter can read.
  (function(){
    var _origFetch = window.fetch;
    if (!_origFetch) return;

    // Expose a helper so individual scripts can read the current token
    window.getCsrfToken = function(){ return csrfHash; };
    window.getCsrfName  = function(){ return csrfName; };

    window.fetch = function(input, init) {
      init = init || {};
      var method = (init.method || 'GET').toUpperCase();
      if (method !== 'POST') return _origFetch.call(this, input, init);

      // Determine if this is a same-origin request
      var url = (typeof input === 'string') ? input : (input && input.url ? input.url : '');
      var isSameOrigin = !url || url.charAt(0) === '/' || url.indexOf(window.location.origin) === 0;
      if (!isSameOrigin) return _origFetch.call(this, input, init);

      var body = init.body;
      var headers = init.headers ? (init.headers instanceof Headers ? init.headers : new Headers(init.headers)) : new Headers();

      // Don't double-inject
      var alreadyHasToken = false;
      try {
        var ct = headers.get('Content-Type') || '';
        if (body instanceof FormData) {
          alreadyHasToken = body.has(csrfName);
        } else if (typeof body === 'string') {
          alreadyHasToken = body.indexOf(csrfName) !== -1;
        }
        // Also check if caller already set the header
        if (headers.has('X-CSRF-Token') || headers.has('X-Csrf-Token')) {
          alreadyHasToken = true;
        }
      } catch(e) {}

      if (!alreadyHasToken) {
        if (body instanceof FormData) {
          body.append(csrfName, csrfHash);
        } else if (body instanceof URLSearchParams) {
          if (!body.has(csrfName)) {
            body.append(csrfName, csrfHash);
          }
        } else if (typeof body === 'string') {
          // URL-encoded string body
          body += (body ? '&' : '') + encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfHash);
        } else if (body === null || body === undefined) {
          // Empty body — send as URL-encoded
          body = encodeURIComponent(csrfName) + '=' + encodeURIComponent(csrfHash);
          if (!headers.has('Content-Type')) {
            headers.set('Content-Type', 'application/x-www-form-urlencoded');
          }
        }
        // For JSON bodies we inject via header instead (can't modify JSON string safely)
        // CodeIgniter's CSRF check reads from POST body; JSON endpoints should be in csrf_exclude_uris
      }

      init.body    = body;
      init.headers = headers;
      return _origFetch.call(this, input, init);
    };
  })();
  // ─────────────────────────────────────────────────────────────────────────

})();
</script>
<?php endif; ?>
</body>
</html>
