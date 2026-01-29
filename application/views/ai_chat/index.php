<?php $this->load->view('partials/header', ['title' => 'AI Assistant']); ?>

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-sm" style="height: 80vh; display: flex; flex-direction: column;">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="mb-0 text-primary"><i class="bi bi-robot me-2"></i>AI Assistant</h5>
                        <small class="text-muted">Ask questions about your data (Employees, Attendance, Projects...)</small>
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="card-body p-3" id="chat-box" style="flex: 1; overflow-y: auto; background-color: #f8f9fa;">
                    <!-- Welcome Message -->
                    <div class="d-flex flex-row justify-content-start mb-3">
                        <div class="p-3 bg-white rounded shadow-sm" style="max-width: 75%;">
                            <p class="mb-0">Hello! I am your Office AI. I can help you find information about employees, attendance, projects, and more. Try asking:</p>
                            <ul class="mb-0 mt-2 small text-muted">
                                <li>"Show me all employees in the IT department"</li>
                                <li>"Who is absent today?"</li>
                                <li>"List pending high priority tasks"</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="card-footer bg-white border-top">
                    <form id="chat-form" class="d-flex align-items-center" onsubmit="return sendMessage(event)">
                        <input type="text" id="user-input" class="form-control me-2" placeholder="Type your question..." autocomplete="off">
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
    const csrfToken = '<?php echo $this->security->get_csrf_hash(); ?>';
    const csrfName = '<?php echo $this->security->get_csrf_token_name(); ?>';

    // Conversation history from session so chat survives page refresh
    const initialHistory = <?php echo json_encode(isset($conversation_history) ? $conversation_history : []); ?>;

    let lastAiResponseText = '';

    function appendMessage(text, isUser) {
        const div = document.createElement('div');
        div.className = isUser ? 'd-flex flex-row justify-content-end mb-3' : 'd-flex flex-row justify-content-start mb-3';
        
        const bubble = document.createElement('div');
        bubble.className = isUser ? 'p-3 bg-primary text-white rounded shadow-sm' : 'p-3 bg-white rounded shadow-sm';
        bubble.style.maxWidth = '75%';
        bubble.innerHTML = text.replace(/\n/g, '<br>');
        
        div.appendChild(bubble);
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;

        // Store plain text of last AI response for TTS
        if (!isUser) {
            const temp = document.createElement('div');
            temp.innerHTML = text;
            lastAiResponseText = temp.textContent || temp.innerText || '';
        }
    }

    function appendLoading() {
        const div = document.createElement('div');
        div.className = 'd-flex flex-row justify-content-start mb-3 loading-msg';
        div.innerHTML = '<div class="p-3 bg-white rounded shadow-sm"><span class="spinner-border spinner-border-sm text-primary" role="status"></span> Thinking...</div>';
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
        return div;
    }

    // Rebuild conversation from session history on page load
    (function restoreHistory() {
        if (!Array.isArray(initialHistory) || initialHistory.length === 0) {
            return;
        }

        initialHistory.forEach(function(entry) {
            if (entry.user) {
                appendMessage(entry.user, true);
            }
            if (entry.assistant) {
                appendMessage(entry.assistant, false);
            }
        });
    })();

    async function sendMessage(e) {
        e.preventDefault();
        const text = userInput.value.trim();
        if(!text) return;

        appendMessage(text, true);
        userInput.value = '';
        const loader = appendLoading();

        try {
            const formData = new FormData();
            formData.append('message', text);
            formData.append(csrfName, csrfToken);

            const response = await fetch('<?php echo site_url("ai_chat/send_message"); ?>', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            loader.remove();

            if (data.status === 'success') {
                appendMessage(data.response, false);
            } else {
                appendMessage('<small class="text-danger">Error: ' + data.message + '</small>', false);
            }

        } catch (error) {
            loader.remove();
            appendMessage('<small class="text-danger">Network Error. Please try again.</small>', false);
        }
    }

    // Speak last AI response using TTS endpoint
    document.getElementById('speak-last-btn').addEventListener('click', async function() {
        if (!lastAiResponseText) {
            alert('No AI response to speak yet.');
            return;
        }

        const btn = this;
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const formData = new FormData();
            formData.append('text', lastAiResponseText);
            formData.append('language', 'en-US'); // Change or detect language if needed
            formData.append(csrfName, csrfToken);

            const response = await fetch('<?php echo site_url("ai_chat/tts"); ?>', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.status === 'success' && data.audio_url) {
                const audio = document.getElementById('tts-audio');
                audio.src = data.audio_url;
                audio.play();
            } else {
                // If Azure Speech is not configured, fallback to browser TTS if available
                if (data.message && data.message.indexOf('Azure Speech is not configured') !== -1 && 'speechSynthesis' in window) {
                    const utter = new SpeechSynthesisUtterance(lastAiResponseText);
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

<?php $this->load->view('partials/footer'); ?>
