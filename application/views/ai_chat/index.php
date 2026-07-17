<?php $this->load->view('partials/header', ['title' => 'AI Assistant']); ?>

<div class="container-fluid p-3">
    <div class="row g-3" style="min-height: 80vh;">
        <div class="col-lg-3 col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white py-2">
                    <strong class="small">Conversations</strong>
                </div>
                <div class="list-group list-group-flush overflow-auto" id="ai-conv-list" style="max-height: 70vh;">
                    <?php if (!empty($conversations)): foreach ($conversations as $c): ?>
                        <button type="button" class="list-group-item list-group-item-action ai-load-conv py-2"
                                data-id="<?php echo (int) $c['id']; ?>">
                            <div class="small fw-semibold text-truncate"><?php echo htmlspecialchars(isset($c['title']) ? $c['title'] : ('Chat #' . $c['id']), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="text-muted" style="font-size:0.7rem;"><?php echo htmlspecialchars(isset($c['updated_at']) ? $c['updated_at'] : '', ENT_QUOTES, 'UTF-8'); ?></div>
                        </button>
                    <?php endforeach; else: ?>
                        <div class="p-3 text-muted small">No saved chats yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-9 col-md-8">
            <div class="card shadow-sm" style="height: 80vh; display: flex; flex-direction: column;">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0 text-primary"><i class="bi bi-robot me-2"></i>AI Assistant</h5>
                        <small class="text-muted">Ask in any language — attendance, leave, tasks, SPL, and more</small>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" aria-label="Chat tools">
                        <button type="button" class="btn btn-outline-primary" id="ai-new-chat" title="New chat"><i class="bi bi-plus-lg"></i></button>
                        <button type="button" class="btn btn-outline-secondary" id="ai-clear-chat" title="Clear history"><i class="bi bi-trash"></i></button>
                        <?php if (!empty($is_admin)): ?>
                        <button type="button" class="btn btn-outline-secondary" id="ai-reindex" title="Reindex knowledge base"><i class="bi bi-arrow-repeat"></i></button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body p-3" id="chat-box" style="flex: 1; overflow-y: auto; background-color: #f8f9fa;">
                    <div class="d-flex flex-row justify-content-start mb-3" id="ai-welcome">
                        <div class="p-3 bg-white rounded shadow-sm" style="max-width: 90%;">
                            <p class="mb-2">Hello! I am your Office AI. Try a quick question:</p>
                            <div class="d-flex flex-wrap gap-1" id="ai-suggestion-chips">
                                <?php foreach ((!empty($suggestion_chips) ? $suggestion_chips : array()) as $chip): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary ai-chip"
                                            data-msg="<?php echo htmlspecialchars($chip['message'], ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($chip['label'], ENT_QUOTES, 'UTF-8'); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white border-top">
                    <form id="chat-form" class="d-flex align-items-center" onsubmit="return sendMessage(event)">
                        <input type="text" id="user-input" class="form-control me-2" placeholder="Type in English / Marathi / Hindi..." autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary me-2" id="speak-last-btn" title="Speak last AI reply">
                            <i class="bi bi-volume-up"></i>
                        </button>
                        <button type="submit" class="btn btn-primary px-4" id="send-btn">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </form>
                    <audio id="tts-audio" style="display:none;"></audio>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const chatBox = document.getElementById('chat-box');
    const userInput = document.getElementById('user-input');
    const sendBtn = document.getElementById('send-btn');
    let csrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
    const csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';
    const initialHistory = <?php echo json_encode(isset($conversation_history) ? $conversation_history : []); ?>;
    let lastAiResponseText = '';

    function syncCsrf(token) {
        if (!token) return;
        csrfToken = token;
        window.currentCsrfToken = token;
    }

    function appendMessage(text, isUser) {
        const div = document.createElement('div');
        div.className = isUser ? 'd-flex flex-row justify-content-end mb-3' : 'd-flex flex-row justify-content-start mb-3';
        const bubble = document.createElement('div');
        bubble.className = isUser ? 'p-3 bg-primary text-white rounded shadow-sm' : 'p-3 bg-white rounded shadow-sm';
        bubble.style.maxWidth = '85%';
        bubble.innerHTML = text.replace(/\n/g, '<br>');
        div.appendChild(bubble);
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
        if (!isUser) {
            const temp = document.createElement('div');
            temp.innerHTML = text;
            lastAiResponseText = temp.textContent || temp.innerText || '';
        }
    }

    function appendLoading() {
        const div = document.createElement('div');
        div.className = 'd-flex flex-row justify-content-start mb-3 loading-msg';
        div.innerHTML = '<div class="p-3 bg-white rounded shadow-sm"><span class="spinner-border spinner-border-sm text-primary"></span> <span id="loading-text">Thinking...</span></div>';
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
        let messages = ['Understanding…', 'Checking tools…', 'Working…'];
        let i = 0;
        const interval = setInterval(() => {
            const span = div.querySelector('#loading-text');
            if (span) { span.innerText = messages[i % messages.length]; i++; }
        }, 1600);
        return { div: div, interval: interval, setStage: function(s) {
            const span = div.querySelector('#loading-text');
            if (span && s) span.innerText = s;
        }};
    }

    (function restoreHistory() {
        if (!Array.isArray(initialHistory) || initialHistory.length === 0) return;
        initialHistory.forEach(function(entry) {
            if (entry.user) appendMessage(entry.user, true);
            if (entry.assistant) appendMessage(entry.assistant, false);
        });
    })();

    document.querySelectorAll('.ai-chip').forEach(function(btn) {
        btn.addEventListener('click', function() {
            userInput.value = btn.getAttribute('data-msg') || '';
            sendMessage({ preventDefault: function(){} });
        });
    });

    async function sendMessage(e) {
        e.preventDefault();
        const text = userInput.value.trim();
        if (!text) return;
        appendMessage(text, true);
        userInput.value = '';
        userInput.disabled = true;
        sendBtn.disabled = true;
        const loaderObj = appendLoading();
        try {
            const formData = new FormData();
            formData.append('message', text);
            formData.append(csrfName, csrfToken);
            const response = await fetch('<?php echo site_url("ai_chat/send_message"); ?>', { method: 'POST', body: formData });
            clearInterval(loaderObj.interval);
            loaderObj.div.remove();
            const data = await response.json();
            if (data.csrf_token) syncCsrf(data.csrf_token);
            if (data.status === 'success') appendMessage(data.response, false);
            else appendMessage('<small class="text-danger">Error: ' + (data.message || 'Failed') + '</small>', false);
        } catch (error) {
            clearInterval(loaderObj.interval);
            loaderObj.div.remove();
            appendMessage('<small class="text-danger">Network Error.</small>', false);
        } finally {
            userInput.disabled = false;
            sendBtn.disabled = false;
            userInput.focus();
        }
    }

    window.currentCsrfToken = csrfToken;

    chatBox.addEventListener('click', async function(e) {
        const conf = e.target.closest('.ai-confirm-sql');
        const cancel = e.target.closest('.ai-cancel-sql');
        if (conf || cancel) {
            e.preventDefault();
            const token = (conf || cancel).getAttribute('data-token');
            const fd = new FormData();
            fd.append(csrfName, csrfToken);
            fd.append('token', token || '');
            if (cancel) fd.append('cancel', '1');
            const res = await fetch('<?php echo site_url("ai-chat/confirm-sql"); ?>', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.csrf_token) syncCsrf(data.csrf_token);
            appendMessage(data.response || data.message || 'Done', false);
            return;
        }
        const btn = e.target.closest('.export-btn');
        if (!btn) return;
        e.preventDefault();
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo site_url("ai-chat/export"); ?>';
        form.target = '_blank';
        [['data', btn.getAttribute('data-export-data')],
         ['format', btn.getAttribute('data-export-format')],
         ['query', btn.getAttribute('data-export-query')],
         [csrfName, csrfToken]
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

    async function postAiAction(url, extra) {
        const formData = new FormData();
        formData.append(csrfName, csrfToken);
        if (extra) Object.keys(extra).forEach(function(k){ formData.append(k, extra[k]); });
        const res = await fetch(url, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        if (data.csrf_token) syncCsrf(data.csrf_token);
        return data;
    }

    function wipeChatKeepWelcome() {
        Array.prototype.slice.call(chatBox.children).forEach(function(el) {
            if (el.id !== 'ai-welcome') el.remove();
        });
        lastAiResponseText = '';
    }

    document.getElementById('ai-new-chat').addEventListener('click', async function() {
        const data = await postAiAction('<?php echo site_url("ai-chat/new"); ?>');
        if (data.status === 'success') wipeChatKeepWelcome();
    });

    document.getElementById('ai-clear-chat').addEventListener('click', async function() {
        if (!confirm('Clear all AI chat history?')) return;
        const data = await postAiAction('<?php echo site_url("ai-chat/clear-history"); ?>');
        if (data.status === 'success') wipeChatKeepWelcome();
    });

    document.querySelectorAll('.ai-load-conv').forEach(function(btn) {
        btn.addEventListener('click', async function() {
            const id = btn.getAttribute('data-id');
            const data = await postAiAction('<?php echo site_url("ai-chat/load"); ?>', { conversation_id: id });
            if (data.status !== 'success') return;
            wipeChatKeepWelcome();
            (data.history || []).forEach(function(entry) {
                if (entry.user) appendMessage(entry.user, true);
                if (entry.assistant) appendMessage(entry.assistant, false);
            });
        });
    });

    const reindexBtn = document.getElementById('ai-reindex');
    if (reindexBtn) {
        reindexBtn.addEventListener('click', async function() {
            reindexBtn.disabled = true;
            const data = await postAiAction('<?php echo site_url("ai-chat/reindex"); ?>');
            alert(data.message || (data.status === 'success' ? 'Done' : 'Failed'));
            reindexBtn.disabled = false;
        });
    }

    document.getElementById('speak-last-btn').addEventListener('click', async function() {
        if (!lastAiResponseText) { alert('No AI response to speak yet.'); return; }
        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        try {
            const formData = new FormData();
            formData.append('text', lastAiResponseText);
            formData.append('language', 'en-US');
            formData.append(csrfName, window.currentCsrfToken || csrfToken);
            const response = await fetch('<?php echo site_url("ai_chat/tts"); ?>', { method: 'POST', body: formData });
            const data = await response.json();
            if (data.status === 'success' && data.audio_url) {
                const audio = document.getElementById('tts-audio');
                audio.src = data.audio_url;
                audio.play();
            } else if (data.message && data.message.indexOf('Azure Speech is not configured') !== -1 && 'speechSynthesis' in window) {
                window.speechSynthesis.speak(new SpeechSynthesisUtterance(lastAiResponseText));
            } else {
                alert(data.message || 'Unable to generate speech.');
            }
        } catch (err) {
            alert('Network error while generating speech.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    });
</script>

<?php $this->load->view('partials/footer'); ?>
