<?php $this->load->view('partials/header', [
  'title' => 'Chat',
  'extra_css' => ['assets/css/chats.css'],
]); ?>

<!-- New Conversation Modal -->
<div class="modal fade" id="newConvoModal" tabindex="-1" aria-labelledby="newConvoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="newConvoModalLabel"><i class="bi bi-chat-plus me-2"></i>New Conversation</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <!-- Tab nav -->
        <ul class="nav nav-pills nav-fill mb-3" id="newConvoTabs">
          <li class="nav-item">
            <button class="nav-link active" data-tab="dm"><i class="bi bi-person me-1"></i>Direct Message</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-tab="group"><i class="bi bi-people me-1"></i>Group Chat</button>
          </li>
        </ul>
        <!-- DM panel -->
        <div id="tabDm">
          <form method="post" action="<?php echo site_url('chats/start-dm'); ?>">
            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
            <div class="mb-3">
              <label class="form-label fw-semibold">Search user by email or name</label>
              <input type="text" id="dmUserSearch" class="form-control mb-2" placeholder="Type to search...">
              <select name="email" class="form-select" id="dmUserSelect" size="5" required>
                <?php foreach ($users as $u): ?>
                  <option value="<?php echo esc_view($u->email); ?>">
                    <?php
                      $display = isset($u->full_name) && $u->full_name ? $u->full_name : (isset($u->name) && $u->name ? $u->name : $u->email);
                      echo esc_view($display . ' <' . $u->email . '>');
                    ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>Start Chat</button>
          </form>
        </div>
        <!-- Group panel -->
        <div id="tabGroup" class="d-none">
          <form method="post" action="<?php echo site_url('chats/create-group'); ?>">
            <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
            <div class="mb-3">
              <label class="form-label fw-semibold">Group Name</label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Project Alpha Team" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Add Participants</label>
              <input type="text" id="groupUserSearch" class="form-control mb-2" placeholder="Search users...">
              <select name="participants[]" class="form-select" id="groupUserSelect" multiple size="6" required>
                <?php foreach ($users as $u): ?>
                  <option value="<?php echo (int)$u->id; ?>">
                    <?php
                      $display = isset($u->full_name) && $u->full_name ? $u->full_name : (isset($u->name) && $u->name ? $u->name : $u->email);
                      echo esc_view($display . ' <' . $u->email . '>');
                    ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text">Hold Ctrl / Cmd to select multiple</div>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-people me-1"></i>Create Group</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-chat-dots text-primary me-2"></i>Chat</h4>
      <p class="text-muted mb-0 small">Real-time messaging &amp; video calls</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newConvoModal">
      <i class="bi bi-plus-lg me-1"></i><span class="d-none d-sm-inline">New Conversation</span>
    </button>
  </div>
</div>
<?php if (!empty($jitsi_config['enabled']) && !empty($jitsi_config['security_warning'])): ?>
<div class="container-fluid pt-0">
  <div class="alert alert-warning py-2 mb-2 small d-flex align-items-start gap-2">
    <i class="bi bi-shield-exclamation flex-shrink-0 mt-1"></i>
    <span><?php echo esc_view($jitsi_config['security_warning']); ?></span>
  </div>
</div>
<?php elseif (empty($jitsi_config['enabled'])): ?>
<div class="container-fluid pt-0">
  <div class="alert alert-info py-2 mb-2 small d-flex align-items-start gap-2">
    <i class="bi bi-camera-video-off flex-shrink-0 mt-1"></i>
    <span>Group video meetings need Jitsi configured under <strong>API Integrations</strong> (use <code>meet.jit.si</code> for testing). Quick 1:1 WebRTC calls still work in DMs.</span>
  </div>
</div>
<?php endif; ?>

<div class="container-fluid py-0 d-none" id="meetingMinimizedBar">
  <div class="alert alert-primary py-2 mb-2 d-flex align-items-center justify-content-between gap-2">
    <span class="small fw-semibold"><i class="bi bi-camera-video-fill me-1"></i> Meeting in progress</span>
    <div class="d-flex gap-1">
      <button type="button" class="btn btn-sm btn-primary" id="btnMeetingRestore">Return</button>
      <button type="button" class="btn btn-sm btn-outline-danger" id="btnMeetingLeaveFromBar">Leave</button>
    </div>
  </div>
</div>

<div class="container-fluid pb-3">
<div class="chat-app row g-3">
  <!-- Sidebar: conversation list -->
  <div class="col-12 col-md-4 col-lg-3">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header gradient d-flex align-items-center justify-content-between">
        <div class="fw-semibold"><i class="bi bi-chat-left-text me-1"></i> Conversations</div>
        <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#newConvoModal" title="New conversation">
          <i class="bi bi-plus-lg"></i>
        </button>
      </div>
      <!-- Search box -->
      <div class="p-2 border-bottom">
        <input type="search" id="convoSearch" class="form-control form-control-sm" placeholder="Search conversations...">
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush" id="convoList"
             data-initial-id="<?php
               $initial_convo = 0;
               if (!empty($open_id) && (int)$open_id > 0) {
                   $initial_convo = (int)$open_id;
               } elseif (empty($scheduled_meeting_id) && !empty($conversations)) {
                   $initial_convo = (int)$conversations[0]->id;
               }
               echo $initial_convo;
             ?>">
          <?php if (!empty($conversations)): ?>
            <?php
              $my_email = $this->session->userdata('email');
              foreach ($conversations as $c):
                if ($c->type === 'group') {
                    $label = $c->title ? $c->title : 'Untitled Group';
                } else {
                    // Prefer display names over raw emails for DM labels
                    $peer_emails = array_filter(array_map('trim', explode(',', $c->members)), function($e) use ($my_email) { return $e !== $my_email; });
                    if (!empty($c->member_names)) {
                        $name_parts = array_map('trim', explode(',', $c->member_names));
                        $email_parts = array_map('trim', explode(',', $c->members));
                        $peer_names = array();
                        foreach ($email_parts as $idx => $em) {
                            if ($em !== $my_email && isset($name_parts[$idx])) { $peer_names[] = $name_parts[$idx]; }
                        }
                        $label = !empty($peer_names) ? implode(', ', $peer_names) : (!empty($peer_emails) ? implode(', ', $peer_emails) : 'Direct Message');
                    } else {
                        $label = !empty($peer_emails) ? implode(', ', $peer_emails) : 'Direct Message';
                    }
                }
                $initial = strtoupper(substr(preg_replace('/[^A-Za-z]/','', $label), 0, 1));
                if ($initial === '') { $initial = '#'; }
                $preview = '';
                if (!empty($c->last_message)) {
                    $preview = mb_strimwidth(strip_tags($c->last_message), 0, 50, '...');
                }
            ?>
            <button type="button"
                    class="list-group-item list-group-item-action d-flex align-items-center convo-item"
                    data-id="<?php echo (int)$c->id; ?>"
                    data-type="<?php echo esc_view($c->type); ?>"
                    data-title="<?php echo esc_view($c->title ? $c->title : ''); ?>"
                    data-members="<?php echo esc_view($c->members ? $c->members : ''); ?>"
                    data-participant-ids="<?php echo esc_view(isset($c->member_ids) ? $c->member_ids : ''); ?>"
                    data-label="<?php echo esc_view(strtolower($label)); ?>">
              <span class="avatar flex-shrink-0"><?php echo esc_view($initial); ?></span>
              <div class="flex-grow-1 overflow-hidden">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="fw-semibold text-truncate" style="max-width:140px;"><?php echo esc_view($label); ?></div>
                  <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <span class="badge type-badge" data-type="<?php echo esc_view($c->type === 'group' ? 'group' : 'dm'); ?>"><?php echo esc_view(strtoupper($c->type)); ?></span>
                    <span class="badge rounded-pill bg-danger unread-badge d-none" data-cid="<?php echo (int)$c->id; ?>">0</span>
                  </div>
                </div>
                <div class="subtitle text-truncate"><?php echo $preview ? esc_view($preview) : '<span class="text-muted fst-italic">No messages yet</span>'; ?></div>
              </div>
            </button>
          <?php endforeach; ?>
          <?php endif; ?>
          <?php if (empty($conversations)): ?>
            <div class="empty-state py-4 text-center">
              <div class="empty-icon mx-auto mb-2" style="font-size:2.5rem;opacity:.3;"><i class="bi bi-chat-dots"></i></div>
              <h6 class="fw-semibold">No conversations yet</h6>
              <p class="text-muted small mb-2">Start a new conversation to begin chatting</p>
              <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newConvoModal">
                <i class="bi bi-plus-lg me-1"></i>New Conversation
              </button>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Main chat area -->
  <div class="col-12 col-md-8 col-lg-9">
    <div class="card shadow-sm border-0 h-100">
      <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
          <div class="fw-semibold" id="hdrConvTitle">Select a conversation</div>
          <div id="hdrConvMeta" class="header-sub text-muted small"></div>
        </div>
        <div class="d-flex flex-wrap gap-1 chat-header-toolbar align-items-center">
          <?php if (!empty($jitsi_config['enabled'])): ?>
          <button id="btnStartMeeting" class="btn btn-primary btn-sm" disabled title="Start or join video meeting">
            <i class="bi bi-camera-video-fill"></i> <span class="d-none d-sm-inline" id="btnStartMeetingLabel">Start Meeting</span>
          </button>
          <button id="btnScheduleMeeting" class="btn btn-outline-primary btn-sm" disabled title="Schedule meeting">
            <i class="bi bi-calendar-plus"></i> <span class="d-none d-sm-inline">Schedule</span>
          </button>
          <?php endif; ?>
          <button id="btnCallToggle" class="btn btn-outline-primary btn-sm" disabled title="Quick 1:1 call (WebRTC)"><i class="bi bi-telephone"></i> <span class="d-none d-sm-inline">Quick Call</span></button>
          <button id="btnAcceptCall" class="btn btn-success btn-sm d-none" title="Accept call"><i class="bi bi-telephone-inbound"></i></button>
          <button id="btnRejectCall" class="btn btn-outline-danger btn-sm d-none" title="Reject call"><i class="bi bi-telephone-x"></i></button>
          <div class="dropdown d-none" id="webrtcToolsDropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="In-call tools">
              <i class="bi bi-sliders"></i> <span class="d-none d-md-inline">In call</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
              <li><button type="button" class="dropdown-item" id="btnToggleMicDd"><i class="bi bi-mic me-2"></i>Toggle mic</button></li>
              <li><button type="button" class="dropdown-item" id="btnToggleSpeakerDd"><i class="bi bi-volume-up me-2"></i>Toggle speaker</button></li>
              <li><button type="button" class="dropdown-item" id="btnShareScreenDd"><i class="bi bi-display me-2"></i>Share screen</button></li>
              <li><button type="button" class="dropdown-item" id="btnRecordDd"><i class="bi bi-record-circle me-2"></i>Record</button></li>
              <li><button type="button" class="dropdown-item" id="btnFullscreenDd"><i class="bi bi-arrows-fullscreen me-2"></i>Full view</button></li>
              <li><hr class="dropdown-divider"></li>
              <li><button type="button" class="dropdown-item text-danger" id="btnEndCallDd"><i class="bi bi-telephone-x me-2"></i>End call</button></li>
            </ul>
          </div>
          <button id="btnToggleMic" class="btn btn-outline-secondary btn-sm d-none webrtc-tool-btn" disabled title="Toggle Microphone"><i class="bi bi-mic"></i></button>
          <button id="btnToggleSpeaker" class="btn btn-outline-secondary btn-sm d-none webrtc-tool-btn" disabled title="Toggle Speaker"><i class="bi bi-volume-up"></i></button>
          <button id="btnShareScreen" class="btn btn-outline-secondary btn-sm d-none webrtc-tool-btn" disabled title="Share Screen"><i class="bi bi-display"></i></button>
          <button id="btnRecord" class="btn btn-outline-secondary btn-sm d-none webrtc-tool-btn" disabled title="Record"><i class="bi bi-record-circle"></i></button>
          <button id="btnEndCall" class="btn btn-outline-danger btn-sm d-none" title="End call"><i class="bi bi-telephone-x"></i></button>
          <button id="btnFullscreen" class="btn btn-outline-secondary btn-sm d-none webrtc-tool-btn" disabled title="Full View"><i class="bi bi-arrows-fullscreen"></i></button>
          <button id="btnReminder" class="btn btn-outline-warning btn-sm" title="Send reminder" disabled><i class="bi bi-bell"></i></button>
        </div>
      </div>
      <div class="card-body p-2 p-md-3">
        <div class="row g-3">
          <!-- Messages + composer -->
          <div class="col-12 col-xl-7 d-flex flex-column">
            <div id="messages" class="border rounded flex-grow-1 mb-2"></div>
            <!-- Typing indicator bar -->
            <div id="typingBar" class="small text-muted fst-italic mb-1" style="min-height:1.2em;"></div>
            <form id="sendForm" class="composer d-flex gap-2" enctype="multipart/form-data">
              <input type="hidden" name="conversation_id" id="conversation_id">
              <div class="flex-grow-1 position-relative">
                <textarea name="body" id="msgTextarea" class="form-control" rows="2"
                          placeholder="Type a message… (Enter to send, Shift+Enter for newline)" disabled></textarea>
              </div>
              <div class="d-flex flex-column gap-1 flex-shrink-0">
                <button type="button" id="btnEmoji" class="btn btn-outline-secondary btn-sm" title="Emoji" disabled>
                  <i class="bi bi-emoji-smile"></i>
                </button>
                <button type="button" id="btnAttach" class="btn btn-outline-secondary btn-sm" title="Attach file" disabled>
                  <i class="bi bi-paperclip"></i>
                </button>
              </div>
              <input type="file" name="attachment" id="attachment" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.txt" class="visually-hidden" disabled>
              <button class="btn btn-primary align-self-end" type="submit" id="btnSend" disabled>
                <i class="bi bi-send"></i>
              </button>
            </form>
            <div id="attachmentLabel" class="attachment-label text-muted small mt-1 d-none"></div>
            <!-- Quick emoji picker -->
            <div id="quickEmojiPicker" class="d-none position-absolute bg-white border rounded shadow p-2" style="bottom:120px;left:0;z-index:200;max-width:260px;">
              <?php
                $quick_emojis = ['👍','❤️','😂','😮','😢','😡','🎉','👏','🔥','✅','❌','🙏'];
                foreach ($quick_emojis as $em): ?>
                <button type="button" class="btn btn-sm p-1 quick-emoji-btn" data-emoji="<?php echo esc_view($em); ?>"
                        style="font-size:1.3rem;line-height:1;"><?php echo $em; ?></button>
              <?php endforeach; ?>
            </div>
          </div>
          <!-- Video area -->
          <div class="col-12 col-xl-5">
            <div class="ratio ratio-16x9 bg-dark mb-2 rounded video-placeholder" id="remoteVideoContainer">
              <video id="remoteVideo" autoplay playsinline></video>
              <div class="video-status disconnected" id="remoteVideoStatus">
                <span class="status-dot"></span><span>Not Connected</span>
              </div>
              <div class="video-controls">
                <button class="btn" id="btnRemoteVideoToggle" title="Toggle Video"><i class="bi bi-camera-video"></i></button>
                <button class="btn" id="btnRemoteAudioToggle" title="Toggle Audio"><i class="bi bi-mic"></i></button>
                <button class="btn" id="btnRemoteFullscreen" title="Fullscreen"><i class="bi bi-arrows-fullscreen"></i></button>
              </div>
            </div>
            <div class="ratio ratio-16x9 bg-secondary mb-2 rounded video-placeholder" id="localVideoContainer">
              <video id="localVideo" autoplay playsinline muted></video>
              <div class="video-status disconnected" id="localVideoStatus">
                <span class="status-dot"></span><span>Camera Off</span>
              </div>
              <div class="video-controls">
                <button class="btn" id="btnLocalVideoToggle" title="Toggle Camera"><i class="bi bi-camera-video"></i></button>
                <button class="btn" id="btnLocalAudioToggle" title="Toggle Microphone"><i class="bi bi-mic"></i></button>
              </div>
            </div>
            <div id="callStatus" class="small text-muted mt-1">
              <span id="callStatusText">Idle</span>
              <span id="callModeBadge" class="badge bg-secondary ms-1 d-none">WebRTC</span>
              <span id="meetingModeBadge" class="badge bg-primary ms-1 d-none">Meeting</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Pre-join lobby modal (Google Meet style) -->
<div class="modal fade" id="preJoinLobbyModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold" id="lobbyMeetingTitle">Ready to join?</h5>
          <p class="text-muted small mb-0" id="lobbyMeetingMeta"></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3 align-items-center">
          <div class="col-md-7">
            <div class="lobby-preview-wrap ratio ratio-16x9 bg-dark rounded overflow-hidden">
              <video id="lobbyPreviewVideo" autoplay playsinline muted></video>
              <div id="lobbyPreviewPlaceholder" class="lobby-preview-placeholder">
                <i class="bi bi-camera-video-off"></i>
                <span>Camera off</span>
              </div>
            </div>
          </div>
          <div class="col-md-5">
            <div class="d-flex flex-column gap-3">
              <div>
                <label class="form-label small fw-semibold">Microphone</label>
                <select id="lobbyAudioInput" class="form-select form-select-sm"></select>
              </div>
              <div>
                <label class="form-label small fw-semibold">Camera</label>
                <select id="lobbyVideoInput" class="form-select form-select-sm"></select>
              </div>
              <div class="d-flex gap-2">
                <button type="button" id="lobbyToggleMic" class="btn btn-outline-secondary flex-fill"><i class="bi bi-mic-fill"></i> Mic on</button>
                <button type="button" id="lobbyToggleCam" class="btn btn-outline-secondary flex-fill"><i class="bi bi-camera-video-fill"></i> Cam on</button>
              </div>
              <button type="button" id="btnLobbyJoin" class="btn btn-primary btn-lg w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i>Join now
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Schedule meeting modal -->
<div class="modal fade" id="scheduleMeetingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-calendar-plus me-2"></i>Schedule Meeting</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="scheduleMeetingForm">
          <div class="mb-3">
            <label class="form-label fw-semibold">Title</label>
            <input type="text" id="schedTitle" class="form-control" required placeholder="Team standup">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Date &amp; time</label>
            <input type="datetime-local" id="schedDateTime" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Duration (minutes)</label>
            <input type="number" id="schedDuration" class="form-control" value="60" min="15" step="15">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Invite emails (comma-separated)</label>
            <input type="text" id="schedEmails" class="form-control" placeholder="user1@company.com, user2@company.com">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Notes</label>
            <textarea id="schedNotes" class="form-control" rows="2"></textarea>
          </div>
        </form>
        <div id="upcomingMeetingsWrap" class="d-none">
          <hr>
          <h6 class="fw-semibold">Upcoming meetings</h6>
          <ul id="upcomingMeetingsList" class="list-group list-group-flush small"></ul>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" id="btnSubmitSchedule" class="btn btn-primary">Schedule</button>
      </div>
    </div>
  </div>
</div>

<!-- Full-screen call overlay (legacy 1:1 WebRTC) -->
<div id="callOverlay" class="call-overlay" aria-hidden="true">
  <div class="overlay-toolbar">
    <button id="btnOverlayMinimize" class="btn btn-outline-light btn-sm" title="Back to chat"><i class="bi bi-chat-dots"></i></button>
    <button id="btnOverlayScreen" class="btn btn-outline-light btn-sm" title="Toggle screen share"><i class="bi bi-display"></i></button>
    <button id="btnOverlayMic" class="btn btn-outline-light btn-sm" title="Mute/unmute"><i class="bi bi-mic"></i></button>
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
        <div class="tile-label">Remote</div>
      </div>
    </div>
  </div>
  <div id="overlayStatus" class="overlay-status">Idle</div>
</div>

<!-- Jitsi meeting overlay (Zoom/Meet style) -->
<div id="meetingOverlay" class="meeting-overlay" aria-hidden="true">
  <div class="meeting-top-bar">
    <div class="meeting-top-left">
      <span id="meetingTitleBar" class="meeting-title-bar">Meeting</span>
      <span id="meetingTimerBar" class="meeting-timer-bar">00:00</span>
      <span id="meetingQualityBar" class="meeting-quality-bar d-none"><i class="bi bi-wifi"></i> <span>Good</span></span>
    </div>
    <div class="meeting-top-right">
      <button id="btnMeetingChatToggle" class="btn btn-sm btn-outline-light" title="Toggle chat"><i class="bi bi-chat-left-text"></i></button>
      <button id="btnMeetingMinimize" class="btn btn-sm btn-outline-light" title="Minimize"><i class="bi bi-dash-lg"></i></button>
    </div>
  </div>
  <div class="meeting-body">
    <div id="jitsiMeetContainer" class="jitsi-meet-container"></div>
    <aside id="meetingSidePanel" class="meeting-side-panel d-none">
      <div class="meeting-panel-tabs">
        <button type="button" class="active" data-panel="participants"><i class="bi bi-people"></i> People</button>
        <button type="button" data-panel="chat"><i class="bi bi-chat-dots"></i> Chat</button>
      </div>
      <div id="meetingPanelParticipants" class="meeting-panel-content">
        <ul id="meetingParticipantList" class="meeting-participant-list"></ul>
      </div>
      <div id="meetingPanelChat" class="meeting-panel-content d-none">
        <p class="text-muted small p-2 mb-0">Use the main chat panel when minimized, or Jitsi in-meeting chat.</p>
      </div>
    </aside>
  </div>
  <div class="meeting-bottom-bar">
    <div class="meeting-controls-center">
      <button id="btnMeetMic" class="meeting-ctrl-btn" title="Microphone (M)"><i class="bi bi-mic-fill"></i><span>Mic</span></button>
      <button id="btnMeetCam" class="meeting-ctrl-btn" title="Camera (V)"><i class="bi bi-camera-video-fill"></i><span>Camera</span></button>
      <button id="btnMeetScreen" class="meeting-ctrl-btn" title="Share screen (D)"><i class="bi bi-display"></i><span>Share</span></button>
      <button id="btnMeetParticipants" class="meeting-ctrl-btn" title="Participants"><i class="bi bi-people-fill"></i><span>People</span></button>
      <button id="btnMeetPip" class="meeting-ctrl-btn" title="Picture-in-Picture"><i class="bi bi-pip"></i><span>PiP</span></button>
      <button id="btnMeetRecord" class="meeting-ctrl-btn d-none moderator-only" title="Record"><i class="bi bi-record-circle"></i><span>Record</span></button>
      <button id="btnMeetMuteAll" class="meeting-ctrl-btn d-none moderator-only" title="Mute all"><i class="bi bi-mic-mute-fill"></i><span>Mute all</span></button>
      <button id="btnMeetLobby" class="meeting-ctrl-btn d-none moderator-only" title="Waiting room"><i class="bi bi-door-closed"></i><span>Lobby</span></button>
      <button id="btnMeetLeave" class="meeting-ctrl-btn meeting-ctrl-leave" title="Leave (L)"><i class="bi bi-telephone-x-fill"></i><span>Leave</span></button>
    </div>
  </div>
  <div id="meetingStatusBar" class="meeting-status-bar">Connecting…</div>
</div>

<!-- Recording footer -->
<div id="recordFooter" class="position-fixed bottom-0 start-0 end-0 py-2 px-3">
  <div class="d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <i id="recordIcon" class="bi bi-record-fill text-danger"></i>
      <span id="recordLabel" class="small text-uppercase text-muted">Recording</span>
      <strong id="recordTimer" class="ms-2">00:00</strong>
    </div>
    <button id="btnSaveRecording" class="btn btn-sm btn-light" title="Save partial copy"><i class="bi bi-save"></i></button>
  </div>
</div>

<!-- Toast container -->
<div id="chatToastContainer" class="position-fixed bottom-0 end-0 p-3" style="z-index:1090;">
  <div id="chatToast" class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true" style="min-width:280px;cursor:pointer;">
    <div class="toast-header bg-primary text-white border-0 py-2">
      <i class="bi bi-chat-dots-fill me-2"></i>
      <strong class="me-auto" id="toastTitle">New message</strong>
      <small id="toastConvoLabel" class="text-white-50 me-2"></small>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body py-2 px-3" id="toastBody">You have a new message</div>
  </div>
</div>

<!-- Notification permission banner (shown only if permission not yet granted) -->
<div id="notifPermBanner" class="d-none position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index:1095;max-width:520px;width:calc(100% - 2rem);">
  <div class="alert alert-info alert-dismissible d-flex align-items-center gap-2 shadow py-2 px-3 mb-0" role="alert">
    <i class="bi bi-bell-fill text-primary fs-5 flex-shrink-0"></i>
    <div class="flex-grow-1 small">
      <strong>Enable notifications</strong> to get chat and call alerts even when the browser tab is closed.
    </div>
    <button id="btnEnableNotif" class="btn btn-sm btn-primary flex-shrink-0">Enable</button>
    <button type="button" class="btn-close ms-1" data-bs-dismiss="alert"></button>
  </div>
</div>

<!-- Notification sound (short beep via Web Audio — no file needed) -->
<script>
window._chatPlaySound = function(){
  try {
    var AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;
    var ctx = new AudioCtx();
    var osc = ctx.createOscillator();
    var gain = ctx.createGain();
    osc.type = 'sine';
    osc.frequency.setValueAtTime(880, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.12);
    gain.gain.setValueAtTime(0.18, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18);
    osc.connect(gain); gain.connect(ctx.destination);
    osc.start(); osc.stop(ctx.currentTime + 0.18);
    setTimeout(function(){ try { ctx.close(); } catch(e){} }, 300);
  } catch(e){}
};
</script>

<script>
(function(){
  'use strict';
  var site    = '<?php echo rtrim(site_url(), "/"); ?>/';
  var userId  = <?php echo (int)$user_id; ?>;
  var initialAutoCallId  = <?php echo isset($auto_call_id) ? (int)$auto_call_id : 0; ?>;
  var initialAutoAccept  = <?php echo !empty($auto_accept) ? 'true' : 'false'; ?>;
  var autoAcceptCallId   = (initialAutoAccept && initialAutoCallId) ? initialAutoCallId : 0;
  var jitsiEnabled       = <?php echo !empty($jitsi_config['enabled']) ? 'true' : 'false'; ?>;
  var jitsiDomain        = <?php echo json_encode(!empty($jitsi_config['domain']) ? $jitsi_config['domain'] : ''); ?>;
  var userDisplayName    = <?php echo json_encode(isset($user_display_name) ? $user_display_name : 'User'); ?>;
  var scheduledMeetingId = <?php echo isset($scheduled_meeting_id) ? (int)$scheduled_meeting_id : 0; ?>;
  var jitsiSecurityWarning = <?php echo json_encode(!empty($jitsi_config['security_warning']) ? $jitsi_config['security_warning'] : ''); ?>;
  var pendingScheduledMeetingId = scheduledMeetingId;
  var hasActiveMeeting = false;
  var lobbyLobbyOn = false;

  // ── State ────────────────────────────────────────────────────────────────
  var convoId = 0, lastId = 0, pollTimer = null, signalTimer = null;
  var incomingSinceId = 0, incomingTimer = null;
  var lastNotified = {};
  var unreadCounts = {};
  var overlayOpen  = false;
  var cameraEnabled = true;
  var typingTimer  = null;
  var currentConvoType = '';
  var currentParticipantIds = '';
  var jitsiApi = null;
  var meetingActive = false;
  var meetingIsModerator = false;
  var meetingTimerInterval = null;
  var meetingStartedAt = 0;
  var lobbyStream = null;
  var lobbyMicOn = true;
  var lobbyCamOn = true;
  var lobbyIsStart = false;
  var presenceTimer = null;
  var onlineUserIds = {};

  // ── DOM refs ─────────────────────────────────────────────────────────────
  var convoList        = document.getElementById('convoList');
  var messagesEl       = document.getElementById('messages');
  var form             = document.getElementById('sendForm');
  var inputConvo       = document.getElementById('conversation_id');
  var hdrConvTitle     = document.getElementById('hdrConvTitle');
  var hdrConvMeta      = document.getElementById('hdrConvMeta');
  var typingBar        = document.getElementById('typingBar');
  var recordFooter     = document.getElementById('recordFooter');
  var recordTimerEl    = document.getElementById('recordTimer');
  var btnSaveRecording = document.getElementById('btnSaveRecording');
  var btnCallToggle    = document.getElementById('btnCallToggle');
  var btnAcceptCall    = document.getElementById('btnAcceptCall');
  var btnRejectCall    = document.getElementById('btnRejectCall');
  var btnEndCall       = document.getElementById('btnEndCall');
  var btnToggleMic     = document.getElementById('btnToggleMic');
  var btnToggleSpeaker = document.getElementById('btnToggleSpeaker');
  var callStatus       = document.getElementById('callStatus');
  var btnShareScreen   = document.getElementById('btnShareScreen');
  var btnRecord        = document.getElementById('btnRecord');
  var btnReminder      = document.getElementById('btnReminder');
  var btnAttach        = document.getElementById('btnAttach');
  var btnEmoji         = document.getElementById('btnEmoji');
  var btnSend          = document.getElementById('btnSend');
  var btnFullscreen    = document.getElementById('btnFullscreen');
  var callOverlay      = document.getElementById('callOverlay');
  var btnStartMeeting  = document.getElementById('btnStartMeeting');
  var btnScheduleMeeting = document.getElementById('btnScheduleMeeting');
  var preJoinLobbyModal = document.getElementById('preJoinLobbyModal');
  var lobbyPreviewVideo = document.getElementById('lobbyPreviewVideo');
  var lobbyPreviewPlaceholder = document.getElementById('lobbyPreviewPlaceholder');
  var lobbyMeetingTitle = document.getElementById('lobbyMeetingTitle');
  var lobbyMeetingMeta  = document.getElementById('lobbyMeetingMeta');
  var lobbyAudioInput   = document.getElementById('lobbyAudioInput');
  var lobbyVideoInput   = document.getElementById('lobbyVideoInput');
  var lobbyToggleMic    = document.getElementById('lobbyToggleMic');
  var lobbyToggleCam    = document.getElementById('lobbyToggleCam');
  var btnLobbyJoin      = document.getElementById('btnLobbyJoin');
  var meetingOverlay    = document.getElementById('meetingOverlay');
  var jitsiMeetContainer = document.getElementById('jitsiMeetContainer');
  var meetingTitleBar   = document.getElementById('meetingTitleBar');
  var meetingTimerBar   = document.getElementById('meetingTimerBar');
  var meetingStatusBar  = document.getElementById('meetingStatusBar');
  var meetingSidePanel  = document.getElementById('meetingSidePanel');
  var meetingParticipantList = document.getElementById('meetingParticipantList');
  var scheduleMeetingModal = document.getElementById('scheduleMeetingModal');
  var btnSubmitSchedule = document.getElementById('btnSubmitSchedule');
  var upcomingMeetingsList = document.getElementById('upcomingMeetingsList');
  var upcomingMeetingsWrap = document.getElementById('upcomingMeetingsWrap');
  var overlayRemoteVideo  = document.getElementById('overlayRemoteVideo');
  var overlayRemoteThumb  = document.getElementById('overlayRemoteThumb');
  var overlayLocalVideo   = document.getElementById('overlayLocalVideo');
  var overlayScreenWrap   = document.getElementById('overlayScreenWrap');
  var overlayScreenVideo  = document.getElementById('overlayScreenVideo');
  var overlayStatus       = document.getElementById('overlayStatus');
  var btnOverlayMinimize  = document.getElementById('btnOverlayMinimize');
  var btnOverlayScreen    = document.getElementById('btnOverlayScreen');
  var btnOverlayMic       = document.getElementById('btnOverlayMic');
  var btnOverlayCamera    = document.getElementById('btnOverlayCamera');
  var btnOverlayLeave     = document.getElementById('btnOverlayLeave');
  var btnOverlayClose     = document.getElementById('btnOverlayClose');
  var btnLocalVideoToggle  = document.getElementById('btnLocalVideoToggle');
  var btnLocalAudioToggle  = document.getElementById('btnLocalAudioToggle');
  var btnRemoteVideoToggle = document.getElementById('btnRemoteVideoToggle');
  var btnRemoteAudioToggle = document.getElementById('btnRemoteAudioToggle');
  var btnRemoteFullscreen  = document.getElementById('btnRemoteFullscreen');
  var attachmentInput  = document.getElementById('attachment');
  var attachmentLabel  = document.getElementById('attachmentLabel');
  var chatToastEl      = document.getElementById('chatToast');
  var toastTitleEl     = document.getElementById('toastTitle');
  var toastBodyEl      = document.getElementById('toastBody');
  var msgTextarea      = document.getElementById('msgTextarea');
  var quickEmojiPicker = document.getElementById('quickEmojiPicker');
  var convoSearch      = document.getElementById('convoSearch');

  var toastInstance = null;
  if (window.bootstrap && window.bootstrap.Toast) {
    toastInstance = new bootstrap.Toast(chatToastEl, { delay: 3500 });
  }

  // ── New conversation modal tab switching ──────────────────────────────────
  document.querySelectorAll('#newConvoTabs .nav-link').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('#newConvoTabs .nav-link').forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      var tab = btn.getAttribute('data-tab');
      document.getElementById('tabDm').classList.toggle('d-none', tab !== 'dm');
      document.getElementById('tabGroup').classList.toggle('d-none', tab !== 'group');
    });
  });

  // ── User search in modal ──────────────────────────────────────────────────
  function filterSelect(inputId, selectId) {
    var input = document.getElementById(inputId);
    var select = document.getElementById(selectId);
    if (!input || !select) return;
    input.addEventListener('input', function(){
      var q = input.value.toLowerCase();
      var opts = select.querySelectorAll('option');
      opts.forEach(function(o){
        o.style.display = o.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
      });
    });
  }
  filterSelect('dmUserSearch', 'dmUserSelect');
  filterSelect('groupUserSearch', 'groupUserSelect');

  // ── Conversation search ───────────────────────────────────────────────────
  if (convoSearch) {
    convoSearch.addEventListener('input', function(){
      var q = convoSearch.value.toLowerCase().trim();
      var items = convoList.querySelectorAll('.convo-item');
      items.forEach(function(item){
        var label = (item.getAttribute('data-label') || '').toLowerCase();
        var sub   = (item.querySelector('.subtitle') ? item.querySelector('.subtitle').textContent : '').toLowerCase();
        item.style.display = (!q || label.indexOf(q) !== -1 || sub.indexOf(q) !== -1) ? '' : 'none';
      });
    });
  }

  // ── Helpers ───────────────────────────────────────────────────────────────
  function isUnauthorized(r){ return r && r.status === 401; }
  function handleUnauthorized(r){ if (isUnauthorized(r)) { window.location = site + 'login'; return true; } return false; }
  function parseJsonSafe(r){ try { return r.json(); } catch(e){ return Promise.resolve(null); } }

  function setStatus(s){
    if (callStatus) {
      var t = callStatus.querySelector('#callStatusText');
      if (t) t.textContent = s;
      else callStatus.textContent = s;
    }
    setOverlayStatus(s);
  }

  function setWebRtcToolbarVisible(visible){
    var dd = document.getElementById('webrtcToolsDropdown');
    if (dd) dd.classList.toggle('d-none', !visible);
    document.querySelectorAll('.webrtc-tool-btn').forEach(function(el){
      el.classList.add('d-none');
    });
    var badge = document.getElementById('callModeBadge');
    if (badge) badge.classList.toggle('d-none', !visible);
    if (btnCallToggle) btnCallToggle.classList.toggle('d-none', visible);
  }

  function setMeetingModeBadge(visible){
    var badge = document.getElementById('meetingModeBadge');
    if (badge) badge.classList.toggle('d-none', !visible);
    if (btnStartMeeting && visible) btnStartMeeting.classList.add('d-none');
    else if (btnStartMeeting) btnStartMeeting.classList.remove('d-none');
  }

  function wireDropdownAction(id, fn){
    var el = document.getElementById(id);
    if (el) el.addEventListener('click', function(e){ e.preventDefault(); fn(); });
  }
  wireDropdownAction('btnToggleMicDd', function(){ toggleMic(); });
  wireDropdownAction('btnToggleSpeakerDd', function(){
    if (!remoteVideo) return;
    remoteVideo.muted = !remoteVideo.muted;
  });
  wireDropdownAction('btnShareScreenDd', function(){
    if (!pc) return;
    if (screenStream) stopScreenShare(); else startScreenShare();
  });
  wireDropdownAction('btnRecordDd', function(){
    if (btnRecord) btnRecord.click();
  });
  wireDropdownAction('btnFullscreenDd', function(){
    if (btnFullscreen) btnFullscreen.click();
  });
  wireDropdownAction('btnEndCallDd', endCall);
  function setOverlayStatus(t){ try { if (overlayStatus) overlayStatus.textContent = t || ''; } catch(e){} }

  // ── Ringing ───────────────────────────────────────────────────────────────
  var ringCtx = null, ringOsc = null, ringGain = null, ringLoopTimer = null;
  function startRinging(type){
    try {
      stopRinging();
      var AudioCtx = window.AudioContext || window.webkitAudioContext; if (!AudioCtx) return;
      ringCtx = new AudioCtx();
      ringOsc = ringCtx.createOscillator();
      ringGain = ringCtx.createGain();
      ringOsc.type = 'sine';
      var f1 = type === 'in' ? 800 : 1000, f2 = type === 'in' ? 600 : 750;
      var t0 = ringCtx.currentTime;
      ringOsc.frequency.setValueAtTime(f1, t0);
      ringOsc.frequency.linearRampToValueAtTime(f2, t0 + 0.25);
      ringOsc.frequency.linearRampToValueAtTime(f1, t0 + 0.5);
      ringGain.gain.setValueAtTime(0, t0);
      ringGain.gain.linearRampToValueAtTime(0.06, t0 + 0.02);
      ringGain.gain.linearRampToValueAtTime(0, t0 + 0.5);
      var loop = function(){
        if (!ringCtx || ringCtx.state === 'closed') return;
        var now = ringCtx.currentTime;
        ringOsc.frequency.setValueAtTime(f1, now);
        ringOsc.frequency.linearRampToValueAtTime(f2, now + 0.25);
        ringOsc.frequency.linearRampToValueAtTime(f1, now + 0.5);
        ringGain.gain.cancelScheduledValues(now);
        ringGain.gain.setValueAtTime(0, now);
        ringGain.gain.linearRampToValueAtTime(0.06, now + 0.02);
        ringGain.gain.linearRampToValueAtTime(0, now + 0.5);
        ringLoopTimer = setTimeout(loop, 700);
      };
      ringOsc.connect(ringGain); ringGain.connect(ringCtx.destination);
      ringOsc.start();
      ringLoopTimer = setTimeout(loop, 700);
    } catch(e){}
  }
  function stopRinging(){
    try { clearTimeout(ringLoopTimer); } catch(e){}
    try { if (ringOsc) { try { ringOsc.stop(); } catch(e){} ringOsc.disconnect(); } } catch(e){}
    try { if (ringGain) ringGain.disconnect(); } catch(e){}
    try { if (ringCtx && ringCtx.state !== 'closed') ringCtx.close(); } catch(e){}
    ringCtx = null; ringOsc = null; ringGain = null;
  }

  // ── Unread badges ─────────────────────────────────────────────────────────
  function getUnreadBadge(cid){ return document.querySelector('.unread-badge[data-cid="'+cid+'"]'); }
  function setUnread(cid, count){
    unreadCounts[cid] = Math.max(0, parseInt(count || 0, 10));
    var badge = getUnreadBadge(cid); if (!badge) return;
    if (unreadCounts[cid] > 0){
      badge.textContent = String(unreadCounts[cid]);
      badge.classList.remove('d-none');
    } else {
      badge.textContent = '0';
      badge.classList.add('d-none');
    }
  }
  function incrementUnread(cid){
    setUnread(cid, (unreadCounts[cid] || 0) + 1);
  }

  // ── Typing indicator ──────────────────────────────────────────────────────
  function sendTyping(isTyping){
    if (!convoId) return;
    fetch(site + 'chats/typing', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'conversation_id=' + convoId + '&is_typing=' + (isTyping ? '1' : '0')
    }).catch(function(){});
  }
  function setTyping(isTyping){
    if (typingTimer) { clearTimeout(typingTimer); typingTimer = null; }
    if (isTyping) {
      sendTyping(true);
      typingTimer = setTimeout(function(){ sendTyping(false); }, 3000);
    } else {
      sendTyping(false);
    }
  }
  function fetchTypingUsers(){
    if (!convoId) return;
    fetch(site + 'chats/typing?conversation_id=' + convoId)
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (!data || !data.ok) { if (typingBar) typingBar.textContent = ''; return; }
        var users = data.typing_users || [];
        if (typingBar) {
          typingBar.textContent = users.length === 1 ? 'Someone is typing…' :
                                  users.length > 1  ? users.length + ' people are typing…' : '';
        }
      }).catch(function(){});
  }

  // ── Online status ─────────────────────────────────────────────────────────
  function setOnlineStatus(isOnline){
    fetch(site + 'chats/online-status', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'is_online=' + (isOnline ? '1' : '0')
    }).catch(function(){});
  }
  setOnlineStatus(true);
  window.addEventListener('beforeunload', function(){ setOnlineStatus(false); sendTyping(false); });

  // ── Notification permission banner ───────────────────────────────────────
  var notifPermBanner = document.getElementById('notifPermBanner');
  var btnEnableNotif  = document.getElementById('btnEnableNotif');

  function checkNotifPermission(){
    if (!('Notification' in window)) return;
    if (Notification.permission === 'default') {
      // Show the friendly banner after 2s so it doesn't pop immediately on load
      setTimeout(function(){
        if (notifPermBanner) notifPermBanner.classList.remove('d-none');
      }, 2000);
    }
  }
  checkNotifPermission();

  if (btnEnableNotif) {
    btnEnableNotif.addEventListener('click', function(){
      if (!('Notification' in window)) return;
      try {
        var p = Notification.requestPermission();
        if (p && typeof p.then === 'function') {
          p.then(function(perm){
            if (notifPermBanner) notifPermBanner.classList.add('d-none');
            if (perm === 'granted') {
              if (typeof window.portalRequestPushPermission === 'function') {
                window.portalRequestPushPermission();
              }
              try {
                var n = new Notification('Notifications enabled!', {
                  body: 'You will receive alerts even when this browser tab is closed.'
                });
                setTimeout(function(){ try { n.close(); } catch(e){} }, 3000);
              } catch(e){}
            }
          });
        } else {
          if (notifPermBanner) notifPermBanner.classList.add('d-none');
        }
      } catch(e){}
    });
  }

  // ── Tab title badge ───────────────────────────────────────────────────────
  var _origTitle = document.title;
  var _unreadTotal = 0;
  function updateTabTitle(){
    try {
      if (_unreadTotal > 0) {
        document.title = '(' + _unreadTotal + ') ' + _origTitle;
      } else {
        document.title = _origTitle;
      }
    } catch(e){}
  }
  // Clear badge when tab becomes visible
  document.addEventListener('visibilitychange', function(){
    if (document.visibilityState === 'visible') {
      _unreadTotal = 0;
      updateTabTitle();
    }
  });

  // ── Toast click: jump to conversation ────────────────────────────────────
  chatToastEl && chatToastEl.addEventListener('click', function(){
    try { toastInstance && toastInstance.hide && toastInstance.hide(); } catch(e){}
    var cid = chatToastEl.dataset && chatToastEl.dataset.convoId ? parseInt(chatToastEl.dataset.convoId, 10) : 0;
    if (cid && convoId !== cid) { focusConversationById(cid); }
    if (cid) { setUnread(cid, 0); }
    try { messagesEl.scrollTop = messagesEl.scrollHeight; } catch(e){}
  });

  // ── Core notification function ────────────────────────────────────────────
  function notifyNewMessage(m){
    var senderName = m.full_name || m.name || m.email || 'New message';
    var bodyText   = m.body
      ? m.body.replace(/<[^>]+>/g, '').trim().slice(0, 120)
      : (m.attachment_path ? '📎 Sent an attachment' : '');
    if (!bodyText) bodyText = 'New message';

    // 1. Play notification sound
    try { if (typeof window._chatPlaySound === 'function') window._chatPlaySound(); } catch(e){}

    // 2. In-app Bootstrap toast
    try {
      if (toastTitleEl) toastTitleEl.textContent = senderName;
      if (toastBodyEl)  toastBodyEl.textContent  = bodyText;
      // Show conversation label in toast header
      var toastConvoLabel = document.getElementById('toastConvoLabel');
      if (toastConvoLabel) {
        var cid = m.conversation_id ? parseInt(m.conversation_id, 10) : 0;
        var btn = cid ? findConvoButtonById(cid) : null;
        var convoLabel = btn ? (btn.getAttribute('data-title') || btn.getAttribute('data-members') || ('#' + cid)) : '';
        toastConvoLabel.textContent = convoLabel ? convoLabel.slice(0, 20) : '';
      }
      if (chatToastEl) {
        chatToastEl.dataset.convoId = String(m.conversation_id ? m.conversation_id : (convoId || ''));
      }
      // Re-init toast each time so it always shows even if already visible
      if (window.bootstrap && window.bootstrap.Toast) {
        var t = new bootstrap.Toast(chatToastEl, { delay: 4500, autohide: true });
        t.show();
      } else if (toastInstance) {
        toastInstance.show();
      }
    } catch(e){}

    // 3. Tab title badge (always, even when tab is visible)
    _unreadTotal++;
    updateTabTitle();

    // 4. Desktop (OS) notification — only when tab is hidden
    if (document.visibilityState !== 'visible' && 'Notification' in window && Notification.permission === 'granted') {
      try {
        var n = new Notification(senderName, {
          body: bodyText,
          icon: '<?php echo base_url('assets/favicon.png'); ?>',
          tag: 'chat-msg-' + (m.conversation_id || convoId || 0),
          renotify: true
        });
        n.onclick = function(){
          try { window.focus(); } catch(e){}
          var cid2 = m.conversation_id ? parseInt(m.conversation_id, 10) : (convoId || 0);
          if (cid2) { focusConversationById(cid2); }
          try { messagesEl.scrollTop = messagesEl.scrollHeight; } catch(e){}
          n.close();
        };
        setTimeout(function(){ try { n.close(); } catch(e){} }, 5000);
      } catch(e){}
    }
  }

  // ── Message rendering ─────────────────────────────────────────────────────
  function scrollToBottom(){ try { messagesEl.scrollTop = messagesEl.scrollHeight; } catch(e){} }
  function clearMessages(){ messagesEl.innerHTML = ''; lastId = 0; ensureEmptyPlaceholder(); }
  function ensureEmptyPlaceholder(){
    if (!messagesEl || messagesEl.children.length > 0) return;
    var ph = document.createElement('div');
    ph.className = 'messages-empty-placeholder text-center text-muted mt-5 py-4';
    ph.innerHTML = '<i class="bi bi-chat-dots" style="font-size:2.5rem;opacity:.2;"></i><p class="mt-2">No messages yet. Say hello!</p>';
    messagesEl.appendChild(ph);
  }
  function clearEmptyPlaceholder(){
    var ph = messagesEl.querySelector('.messages-empty-placeholder');
    if (ph) { ph.remove(); }
  }

  function appendMessage(m){
    // Deduplication guard: skip if this message id is already in the DOM
    if (m.id && messagesEl.querySelector('[data-msg-id="'+m.id+'"]')) { return; }
    clearEmptyPlaceholder();

    var isMe = parseInt(m.sender_id, 10) === userId;
    var senderName = m.full_name || m.name || m.email || 'User';

    // Outer row — flex column, aligned left (others) or right (own)
    var wrap = document.createElement('div');
    wrap.className = 'message' + (isMe ? ' own' : '');
    wrap.setAttribute('data-msg-id', m.id);

    // Meta line: sender name + timestamp
    var meta = document.createElement('div');
    meta.className = 'msg-meta';
    meta.textContent = (isMe ? 'You' : senderName) + ' · ' + (m.created_at || '');
    wrap.appendChild(meta);

    // Bubble content
    if (m.body) {
      var body = document.createElement('div');
      body.className = 'bubble ' + (isMe ? 'me' : 'them');
      body.textContent = m.body;
      wrap.appendChild(body);
    }

    // Attachment
    if (m.attachment_path) {
      var ext = m.attachment_path.split('.').pop().toLowerCase();
      var isImg = ['jpg','jpeg','png','gif','webp'].indexOf(ext) !== -1;
      if (isImg) {
        var img = document.createElement('img');
        img.src = site + m.attachment_path;
        img.className = 'mt-1 rounded img-fluid';
        img.style.maxWidth = '220px';
        img.style.cursor = 'pointer';
        (function(path){ img.addEventListener('click', function(){ window.open(site + path, '_blank'); }); })(m.attachment_path);
        wrap.appendChild(img);
      } else {
        var a = document.createElement('a');
        a.className = 'file-attachment mt-1 d-inline-flex';
        a.target = '_blank';
        a.href = site + m.attachment_path;
        a.innerHTML = '<i class="bi bi-paperclip file-icon me-1"></i>' +
                      '<span class="file-name">' + htmlEsc(m.attachment_path.split('/').pop()) + '</span>';
        wrap.appendChild(a);
      }
    }

    // Reaction bar (empty until reactions are loaded/added)
    var reactBar = document.createElement('div');
    reactBar.className = 'message-reactions';
    reactBar.setAttribute('data-msg-id', m.id);
    wrap.appendChild(reactBar);

    // Message actions (react, edit, delete) — shown on hover via CSS .message-actions
    var actionsDiv = document.createElement('div');
    actionsDiv.className = 'message-actions';

    var reactBtn = document.createElement('button');
    reactBtn.type = 'button';
    reactBtn.className = 'btn reaction-btn';
    reactBtn.title = 'React';
    reactBtn.innerHTML = '<i class="bi bi-emoji-smile"></i>';
    reactBtn.setAttribute('data-msg-id', m.id);
    (function(mid, btn){ reactBtn.addEventListener('click', function(e){ e.stopPropagation(); showReactPicker(mid, btn); }); })(m.id, reactBtn);
    actionsDiv.appendChild(reactBtn);

    // Edit button — only for own messages
    if (isMe) {
      var editBtn = document.createElement('button');
      editBtn.type = 'button';
      editBtn.className = 'btn reaction-btn';
      editBtn.title = 'Edit message';
      editBtn.innerHTML = '<i class="bi bi-pencil"></i>';
      (function(mid, wEl){ editBtn.addEventListener('click', function(e){ e.stopPropagation(); startEditMessage(mid, wEl); }); })(m.id, wrap);
      actionsDiv.appendChild(editBtn);
    }

    // Delete button — for own messages
    if (isMe) {
      var delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'btn reaction-btn text-danger';
      delBtn.title = 'Delete message';
      delBtn.innerHTML = '<i class="bi bi-trash"></i>';
      (function(mid, wrapEl){ delBtn.addEventListener('click', function(e){ e.stopPropagation(); deleteMessage(mid, wrapEl); }); })(m.id, wrap);
      actionsDiv.appendChild(delBtn);
    }

    wrap.appendChild(actionsDiv);

    messagesEl.appendChild(wrap);
    scrollToBottom();
  }

  function htmlEsc(s){ var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

  // ── Emoji reaction picker ─────────────────────────────────────────────────
  var activeReactMsgId = 0;
  var reactPickerEl = null;
  var QUICK_EMOJIS = ['👍','❤️','😂','😮','😢','😡','🎉','👏','🔥','✅','❌','🙏'];

  function showReactPicker(msgId, anchor){
    if (reactPickerEl) { reactPickerEl.remove(); reactPickerEl = null; }
    if (activeReactMsgId === msgId) { activeReactMsgId = 0; return; }
    activeReactMsgId = msgId;
    var picker = document.createElement('div');
    picker.className = 'bg-white border rounded shadow p-2 d-flex flex-wrap gap-1';
    picker.style.cssText = 'position:absolute;z-index:300;bottom:calc(100% + 4px);left:0;min-width:200px;';
    QUICK_EMOJIS.forEach(function(em){
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'btn btn-sm p-1';
      b.style.cssText = 'font-size:1.3rem;line-height:1;';
      b.textContent = em;
      b.addEventListener('click', function(e){ e.stopPropagation(); sendReaction(msgId, em); picker.remove(); reactPickerEl = null; activeReactMsgId = 0; });
      picker.appendChild(b);
    });
    anchor.parentElement.style.position = 'relative';
    anchor.parentElement.appendChild(picker);
    reactPickerEl = picker;
  }
  document.addEventListener('click', function(){ if (reactPickerEl) { reactPickerEl.remove(); reactPickerEl = null; activeReactMsgId = 0; } });

  // ── Delete message ────────────────────────────────────────────────────────
  function deleteMessage(msgId, wrapEl){
    if (!confirm('Delete this message?')) { return; }
    fetch(site + 'chats/delete-message', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'message_id=' + msgId
    }).then(function(r){ return r.json(); })
      .then(function(data){
        if (data && data.ok) {
          // Replace bubble content with deleted indicator
          var bubble = wrapEl.querySelector('.bubble');
          if (bubble) {
            bubble.textContent = '🗑 Message deleted';
            bubble.style.opacity = '0.5';
            bubble.style.fontStyle = 'italic';
          }
          // Remove action buttons
          var actions = wrapEl.querySelector('.message-actions');
          if (actions) { actions.remove(); }
        }
      }).catch(function(){});
  }

  // ── Edit message ──────────────────────────────────────────────────────────
  function startEditMessage(msgId, wrapEl){
    var bubble = wrapEl.querySelector('.bubble');
    if (!bubble) { return; }
    var oldText = bubble.textContent;
    if (oldText === '🗑 Message deleted') { return; }

    // Replace bubble with inline editor
    var editWrap = document.createElement('div');
    editWrap.className = 'edit-message-wrap d-flex gap-1 align-items-end';
    var ta = document.createElement('textarea');
    ta.className = 'form-control form-control-sm';
    ta.rows = 2;
    ta.style.cssText = 'min-width:180px;resize:vertical;';
    ta.value = oldText;
    var saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.className = 'btn btn-sm btn-primary';
    saveBtn.innerHTML = '<i class="bi bi-check-lg"></i>';
    var cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'btn btn-sm btn-outline-secondary';
    cancelBtn.innerHTML = '<i class="bi bi-x-lg"></i>';

    editWrap.appendChild(ta);
    editWrap.appendChild(saveBtn);
    editWrap.appendChild(cancelBtn);
    bubble.replaceWith(editWrap);
    ta.focus();

    cancelBtn.addEventListener('click', function(){
      editWrap.replaceWith(bubble);
    });
    saveBtn.addEventListener('click', function(){
      var newBody = ta.value.trim();
      if (!newBody) { return; }
      fetch(site + 'chats/edit-message', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'message_id=' + msgId + '&body=' + encodeURIComponent(newBody)
      }).then(function(r){ return r.json(); })
        .then(function(data){
          if (data && data.ok) {
            bubble.textContent = newBody;
            // Add edited indicator
            var meta = wrapEl.querySelector('.msg-meta');
            if (meta && meta.textContent.indexOf('(edited)') === -1) {
              meta.textContent += ' (edited)';
            }
            editWrap.replaceWith(bubble);
          } else {
            alert(data && data.error ? data.error : 'Could not save edit.');
            editWrap.replaceWith(bubble);
          }
        }).catch(function(){ editWrap.replaceWith(bubble); });
    });
  }

  function sendReaction(msgId, emoji){
    if (!convoId) return;
    fetch(site + 'chats/reaction', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'message_id=' + msgId + '&reaction=' + encodeURIComponent(emoji)
    }).then(function(r){ return r.json(); })
      .then(function(data){
        if (data && data.ok) { renderReactions(msgId, data.reactions); }
      }).catch(function(){});
  }

  function renderReactions(msgId, reactions){
    var bar = messagesEl.querySelector('.message-reactions[data-msg-id="'+msgId+'"]');
    if (!bar) return;
    bar.innerHTML = '';
    if (!reactions || reactions.length === 0) return;
    reactions.forEach(function(r){
      var item = document.createElement('span');
      item.className = 'reaction-item';
      item.title = r.reaction;
      item.innerHTML = '<span class="reaction-emoji">' + htmlEsc(r.reaction) + '</span>' +
                       '<span class="reaction-count">' + htmlEsc(String(r.count)) + '</span>';
      item.addEventListener('click', function(){ sendReaction(msgId, r.reaction); });
      bar.appendChild(item);
    });
  }

  // ── Inline emoji picker for composer ─────────────────────────────────────
  if (btnEmoji) {
    btnEmoji.addEventListener('click', function(e){
      e.stopPropagation();
      quickEmojiPicker.classList.toggle('d-none');
    });
  }
  document.querySelectorAll('.quick-emoji-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      if (msgTextarea) {
        var pos = msgTextarea.selectionStart || msgTextarea.value.length;
        var val = msgTextarea.value;
        msgTextarea.value = val.slice(0, pos) + btn.getAttribute('data-emoji') + val.slice(pos);
        msgTextarea.focus();
      }
      quickEmojiPicker.classList.add('d-none');
    });
  });
  document.addEventListener('click', function(e){
    if (!quickEmojiPicker.contains(e.target) && e.target !== btnEmoji) {
      quickEmojiPicker.classList.add('d-none');
    }
  });

  // ── Form enable/disable ───────────────────────────────────────────────────
  function setFormEnabled(en){
    form.querySelectorAll('textarea, input, button').forEach(function(el){ el.disabled = !en; });
    if (btnEmoji) btnEmoji.disabled = !en;
  }

  // ── Header update ─────────────────────────────────────────────────────────
  function updateHeader(opts){
    var id      = opts && opts.id ? parseInt(opts.id, 10) : 0;
    var type    = opts && opts.type ? String(opts.type) : '';
    var title   = opts && opts.title ? String(opts.title) : '';
    var members = opts && opts.members ? String(opts.members) : '';
    if (hdrConvTitle) {
      hdrConvTitle.textContent = title || members || (id ? 'Conversation #' + id : 'Select a conversation');
    }
    if (hdrConvMeta) {
      hdrConvMeta.textContent = type ? '[' + type.toUpperCase() + ']' + (id ? ' #' + id : '') : '';
    }
    if (btnCallToggle) btnCallToggle.disabled = !id || type === 'group';
    if (btnStartMeeting) btnStartMeeting.disabled = !id || !jitsiEnabled;
    if (btnScheduleMeeting) btnScheduleMeeting.disabled = !id || !jitsiEnabled;
    if (btnReminder)   btnReminder.disabled   = !id;
    if (btnEndCall)    btnEndCall.disabled     = true;
    if (btnAcceptCall) btnAcceptCall.classList.add('d-none');
    if (btnRejectCall) btnRejectCall.classList.add('d-none');
    setStatus('Idle');
    setWebRtcToolbarVisible(false);
    setMeetingModeBadge(false);
    if (btnFullscreen) btnFullscreen.disabled  = !id;
  }

  // ── Select conversation ───────────────────────────────────────────────────
  function findConvoButtonById(id){ return document.querySelector('.convo-item[data-id="'+id+'"]'); }
  function focusConversationById(id){ var btn = findConvoButtonById(id); if (btn) selectConvo(btn); }

  function selectConvo(btn){
    try {
      var id      = parseInt(btn.getAttribute('data-id') || '0', 10);
      var type    = btn.getAttribute('data-type') || '';
      var title   = btn.getAttribute('data-title') || '';
      var members = btn.getAttribute('data-members') || '';
      var participantIds = btn.getAttribute('data-participant-ids') || '';
      if (!id) return;
      document.querySelectorAll('#convoList .convo-item.active').forEach(function(x){ x.classList.remove('active'); });
      btn.classList.add('active');
      convoId = id; lastId = 0; clearMessages();
      currentConvoType = type;
      currentParticipantIds = participantIds;
      if (inputConvo) inputConvo.value = id;
      updateHeader({ id: id, type: type, title: title, members: members });
      setUnread(id, 0);
      refreshPresenceDots(participantIds);
      fetchMeetingParticipantsPanel();
      // Clear tab title badge when opening a conversation
      _unreadTotal = 0; updateTabTitle();
      setFormEnabled(true);
      incomingSinceId = 0;
      fetchMessages();
      ensurePolling();
      ensureIncomingPolling();
      fetchMeetingStatus();
    } catch(e){ console.warn('selectConvo error', e); }
  }

  // ── Fetch & poll messages ─────────────────────────────────────────────────
  function fetchMessages(){
    if (!convoId) return;
    var url = site + 'chats/fetch_messages?conversation_id=' + convoId + '&since_id=' + lastId;
    fetch(url)
      .then(function(r){ if (handleUnauthorized(r)) throw new Error('unauth'); return r.json(); })
      .then(function(data){
        if (!data || !data.ok) { ensureEmptyPlaceholder(); return; }
        var msgs = data.messages || [];
        msgs.forEach(function(m){
          var mid  = parseInt(m.id, 10);
          // Ensure conversation_id is always an int (model returns it in m.*)
          var cid  = parseInt(m.conversation_id || convoId || 0, 10);
          var sid  = parseInt(m.sender_id, 10);

          // Always advance lastId cursor
          if (mid > lastId) { lastId = mid; }

          // Render message if it belongs to the active conversation
          if (cid === convoId) {
            appendMessage(m); // has built-in dedup guard
          }

          // Update sidebar preview for any conversation
          try { updateConvoPreview(m); } catch(e){}

          // Notify only for messages from other users that haven't been notified yet
          var fromOther = (sid !== userId);
          var inActiveConvo = (cid === convoId);
          var userSeeingIt  = inActiveConvo && isActivelyViewingCurrent();

          if (fromOther && !userSeeingIt && mid > (lastNotified[cid] || 0)) {
            notifyNewMessage(m);
            lastNotified[cid] = mid;
            // Increment unread badge only for conversations NOT currently open
            if (!inActiveConvo) { incrementUnread(cid); }
          }
        });
        ensureEmptyPlaceholder();
      }).catch(function(e){ if (String(e) !== 'Error: unauth') console.warn('fetchMessages error', e); });
  }

  function ensurePolling(){
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(function(){ fetchMessages(); fetchTypingUsers(); }, 2500);
  }

  function isActivelyViewingCurrent(){
    // User is "actively viewing" if: tab is visible AND they are near the bottom (within 80px)
    if (document.visibilityState !== 'visible' || !convoId) return false;
    var distFromBottom = messagesEl.scrollHeight - messagesEl.scrollTop - messagesEl.clientHeight;
    return distFromBottom < 80;
  }

  // ── Update conversation preview ───────────────────────────────────────────
  function updateConvoPreview(m){
    try {
      var cid = parseInt(m.conversation_id || convoId || 0, 10); if (!cid) return;
      var btn = findConvoButtonById(cid); if (!btn) return;
      var sub = btn.querySelector('.subtitle');
      var snippet = m.body ? m.body.replace(/<[^>]+>/g,'').slice(0, 50) : (m.attachment_path ? 'Attachment' : '');
      if (sub) { sub.textContent = snippet || ''; }
      var list = document.getElementById('convoList');
      if (list && btn.parentElement === list) { list.insertBefore(btn, list.firstElementChild); }
    } catch(e){}
  }

  // ── Send message ──────────────────────────────────────────────────────────
  form.addEventListener('submit', function(e){
    e.preventDefault();
    if (!convoId) return;
    var fd = new FormData(form);
    var hasAttachment = attachmentInput && attachmentInput.files && attachmentInput.files.length;
    if (hasAttachment) setUploadProgress(0);
    fetch(site + 'chats/send_message', { method: 'POST', body: fd })
      .then(function(r){ if (handleUnauthorized(r)) throw new Error('unauth'); return r.json(); })
      .then(function(data){
        if (data && data.ok) {
          try { form.reset(); } catch(ex){}
          updateAttachmentUI();
          if (data.message) {
            // Advance lastId BEFORE appending so the next poll skips this message
            var mid = parseInt(data.message.id, 10);
            if (mid > lastId) { lastId = mid; }
            appendMessage(data.message);
            updateConvoPreview(data.message);
          } else {
            fetchMessages();
          }
          ensurePolling();
          setUploadProgress(101);
        } else {
          alert(data && data.error ? data.error : 'Failed to send message.');
          setUploadProgress(101);
        }
      }).catch(function(e){ if (String(e) !== 'Error: unauth') alert('Failed to send message. Check your connection.'); setUploadProgress(101); });
  });

  // ── Textarea: Enter to send, Shift+Enter for newline, typing indicator ────
  if (msgTextarea) {
    msgTextarea.addEventListener('keydown', function(ev){
      if (ev.key === 'Enter' && !ev.shiftKey) { ev.preventDefault(); if (btnSend) btnSend.click(); }
    });
    msgTextarea.addEventListener('input', function(){ setTyping(true); });
  }

  // ── Attachment UI ─────────────────────────────────────────────────────────
  if (btnAttach) { btnAttach.addEventListener('click', function(){ if (!btnAttach.disabled) attachmentInput.click(); }); }
  function updateAttachmentUI(){
    try {
      var files = attachmentInput && attachmentInput.files;
      if (files && files.length) {
        attachmentLabel.textContent = files[0].name || 'Attachment selected';
        attachmentLabel.classList.remove('d-none');
        btnAttach.classList.add('btn-secondary');
      } else {
        attachmentLabel.textContent = '';
        attachmentLabel.classList.add('d-none');
        btnAttach.classList.remove('btn-secondary');
      }
    } catch(e){}
  }
  if (attachmentInput) { attachmentInput.addEventListener('change', updateAttachmentUI); }
  var uploadInProgress = false;
  function setUploadProgress(pct){
    try {
      if (!attachmentLabel) return;
      if (pct >= 0 && pct < 100) {
        uploadInProgress = true;
        var base = (attachmentInput && attachmentInput.files && attachmentInput.files[0]) ? attachmentInput.files[0].name : 'Uploading';
        attachmentLabel.textContent = base + ' · ' + pct + '%';
        attachmentLabel.classList.remove('d-none');
        attachmentLabel.classList.add('uploading');
      } else {
        uploadInProgress = false;
        attachmentLabel.classList.remove('uploading');
        updateAttachmentUI();
      }
    } catch(e){}
  }

  // ── Overlay helpers ───────────────────────────────────────────────────────
  function syncOverlayStreams(){
    try {
      if (overlayRemoteVideo) overlayRemoteVideo.srcObject = remoteVideo ? remoteVideo.srcObject : null;
      if (overlayRemoteThumb) overlayRemoteThumb.srcObject = remoteVideo ? remoteVideo.srcObject : null;
      if (overlayLocalVideo)  overlayLocalVideo.srcObject  = localStream || null;
      if (overlayScreenVideo) overlayScreenVideo.srcObject = screenStream || null;
      if (screenStream) { overlayScreenWrap && overlayScreenWrap.classList.remove('d-none'); }
      else              { overlayScreenWrap && overlayScreenWrap.classList.add('d-none'); }
    } catch(e){}
  }
  function setOverlayVisible(open){
    overlayOpen = !!open;
    if (!callOverlay) return;
    if (overlayOpen){
      callOverlay.classList.add('show'); callOverlay.setAttribute('aria-hidden','false');
      syncOverlayStreams();
      if (btnFullscreen) btnFullscreen.innerHTML = '<i class="bi bi-fullscreen-exit"></i>';
    } else {
      callOverlay.classList.remove('show'); callOverlay.setAttribute('aria-hidden','true');
      if (btnFullscreen) btnFullscreen.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
    }
  }

  // ── Video status helpers ──────────────────────────────────────────────────
  function updateVideoStatus(el, status, text){
    if (!el) return;
    el.className = 'video-status ' + status;
    var span = el.querySelector('span:last-child'); if (span) span.textContent = text;
  }
  function updateVideoContainer(container, hasStream){
    if (!container) return;
    container.classList.toggle('video-placeholder', !hasStream);
    container.classList.toggle('video-active', !!hasStream);
  }

  // ── WebRTC ────────────────────────────────────────────────────────────────
  var pc = null, localStream = null, callId = null, signalSince = 0;
  var screenStream = null, originalVideoTrack = null;
  var mediaRecorder = null, recordedChunks = [], isRecording = false;
  var localVideo  = document.getElementById('localVideo');
  var remoteVideo = document.getElementById('remoteVideo');
  var remoteVideoContainer = document.getElementById('remoteVideoContainer');
  var localVideoContainer  = document.getElementById('localVideoContainer');
  var remoteVideoStatus    = document.getElementById('remoteVideoStatus');
  var localVideoStatus     = document.getElementById('localVideoStatus');
  var pendingRemoteOffer   = null;
  var pendingIceCandidates = [];

  function resetCallMedia(keepPendingOffer){
    if (pc) {
      try { pc.getSenders().forEach(function(s){ try { s.track && s.track.stop(); } catch(e){} }); } catch(e){}
      try { pc.close(); } catch(e){}
      pc = null;
    }
    if (localStream) {
      try { localStream.getTracks().forEach(function(t){ t.stop(); }); } catch(e){}
      localStream = null;
    }
    if (screenStream) {
      try { screenStream.getTracks().forEach(function(t){ t.stop(); }); } catch(e){}
      screenStream = null;
    }
    originalVideoTrack = null;
    pendingIceCandidates = [];
    if (!keepPendingOffer) {
      pendingRemoteOffer = null;
    }
  }

  function flushPendingIce(){
    if (!pc || !pc.remoteDescription) return;
    while (pendingIceCandidates.length) {
      var cand = pendingIceCandidates.shift();
      try { pc.addIceCandidate(new RTCIceCandidate(cand)); } catch(e){}
    }
  }

  function setCallToggleUI(active){
    if (!btnCallToggle) return;
    if (active){
      btnCallToggle.classList.replace('btn-outline-primary','btn-danger');
      btnCallToggle.innerHTML = '<i class="bi bi-telephone-x"></i> <span class="d-none d-sm-inline">End Call</span>';
    } else {
      btnCallToggle.classList.replace('btn-danger','btn-outline-primary');
      btnCallToggle.innerHTML = '<i class="bi bi-camera-video"></i> <span class="d-none d-sm-inline">Start Call</span>';
    }
  }

  function initPeer(){
    pc = new RTCPeerConnection({ iceServers: [{ urls: 'stun:stun.l.google.com:19302' }] });
    pc.onicecandidate = function(ev){
      if (ev.candidate && callId) {
        fetch(site + 'calls/signal/' + callId, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: new URLSearchParams({ type: 'ice', payload: JSON.stringify(ev.candidate) })
        });
      }
    };
    pc.ontrack = function(ev){
      remoteVideo.srcObject = ev.streams[0];
      updateVideoContainer(remoteVideoContainer, true);
      updateVideoStatus(remoteVideoStatus, 'connected', 'Connected');
      try { remoteVideo.play().catch(function(){}); } catch(e){}
      syncOverlayStreams();
    };
    var mediaPromise;
    if (!localStream) {
      setStatus('Getting camera and microphone…');
      mediaPromise = navigator.mediaDevices.getUserMedia({ video: true, audio: true })
        .then(function(stream){
          localStream = stream;
          localVideo.srcObject = stream;
          updateVideoContainer(localVideoContainer, true);
          updateVideoStatus(localVideoStatus, 'connected', 'Camera On');
          try { originalVideoTrack = stream.getVideoTracks()[0] || null; } catch(e){}
        }).catch(function(){
          setStatus('Camera/Mic access denied — trying audio only');
          updateVideoStatus(localVideoStatus, 'disconnected', 'Camera Off');
          return navigator.mediaDevices.getUserMedia({ video: false, audio: true })
            .then(function(stream){
              localStream = stream;
              localVideo.srcObject = stream;
              updateVideoStatus(localVideoStatus, 'connected', 'Audio Only');
            }).catch(function(){ setStatus('Media access denied'); });
        });
    } else {
      mediaPromise = Promise.resolve();
    }
    return mediaPromise.then(function(){
      if (localStream) { localStream.getTracks().forEach(function(t){ pc.addTrack(t, localStream); }); }
      btnToggleMic.disabled = false;
      btnToggleSpeaker.disabled = false;
      remoteVideo.muted = false;
      btnShareScreen.disabled = false;
      btnRecord.disabled = false;
      if (btnFullscreen) btnFullscreen.disabled = false;
      cameraEnabled = true;
      syncOverlayStreams();
    });
  }

  function startCall(){
    if (jitsiApi || meetingActive) {
      showMeetingToast('Leave the video meeting before starting a quick call.', true);
      return;
    }
    if (!convoId) return;
    resetCallMedia(false);
    setStatus('Starting call…');
    updateVideoStatus(remoteVideoStatus, 'connecting', 'Connecting…');
    fetch(site + 'calls/start/' + convoId, { method: 'POST' })
      .then(function(r){ if (handleUnauthorized(r)) throw new Error('unauth'); return r.json(); })
      .then(function(j){
        if (!j || !j.ok) throw new Error('start failed');
        callId = j.call_id; signalSince = 0; btnEndCall.disabled = false;
        return initPeer();
      }).then(function(){
        return pc.createOffer();
      }).then(function(offer){
        return pc.setLocalDescription(offer).then(function(){ return offer; });
      }).then(function(offer){
        return fetch(site + 'calls/signal/' + callId, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: new URLSearchParams({ type: 'offer', payload: JSON.stringify(offer) })
        });
      }).then(function(){
        setStatus('Waiting for answer…');
        updateVideoStatus(remoteVideoStatus, 'connecting', 'Waiting for answer…');
        if (signalTimer) clearInterval(signalTimer);
        signalTimer = setInterval(pollSignals, 2000);
        setCallToggleUI(true);
        if (btnEndCall) btnEndCall.classList.add('d-none');
        setWebRtcToolbarVisible(true);
        startRinging('out');
        syncOverlayStreams();
      }).catch(function(e){ if (String(e) !== 'Error: unauth') setStatus('Call failed: ' + e.message); updateVideoStatus(remoteVideoStatus, 'disconnected', 'Call Failed'); });
  }

  function handleSignal(sig){
    if (sig.type === 'offer') { return; }
    if (sig.type === 'answer') {
      try { if (parseInt(sig.from_user_id || 0, 10) === userId) return; } catch(e){}
      try {
        pc.setRemoteDescription(new RTCSessionDescription(JSON.parse(sig.payload))).then(function(){
          flushPendingIce();
        });
      } catch(e){ return; }
      setStatus('Connected'); stopRinging();
    } else if (sig.type === 'ice') {
      try {
        var ice = JSON.parse(sig.payload);
        if (pc && pc.remoteDescription) {
          pc.addIceCandidate(new RTCIceCandidate(ice));
        } else {
          pendingIceCandidates.push(ice);
        }
      } catch(e){}
    } else if (sig.type === 'end') {
      try { if (parseInt(sig.from_user_id || 0, 10) === userId) return; } catch(e){}
      callId = null; endCall();
    }
  }

  function pollSignals(){
    if (!callId) return;
    var url = site + 'calls/poll/' + callId + '?since_id=' + signalSince;
    fetch(url)
      .then(function(r){ if (handleUnauthorized(r)) throw new Error('unauth'); return r.json(); })
      .then(function(j){
        if (j && j.ok && j.signals) {
          j.signals.forEach(function(s){ signalSince = Math.max(signalSince, parseInt(s.id, 10)); handleSignal(s); });
        }
      }).catch(function(){});
  }

  function pollIncomingOffers(){
    if (!convoId || callId) return;
    var url = site + 'calls/incoming/' + convoId + '?since_id=' + incomingSinceId;
    fetch(url)
      .then(function(r){ if (handleUnauthorized(r)) throw new Error('unauth'); return r.json(); })
      .then(function(j){
        if (j && j.ok && j.signals && j.signals.length) {
          j.signals.forEach(function(s){
            incomingSinceId = Math.max(incomingSinceId, parseInt(s.id, 10));
            callId = parseInt(s.call_id, 10) || null;
            signalSince = 0;
            pendingRemoteOffer = s.payload;
            setStatus('Incoming call from ' + (s.from_email || 'someone') + '…');
            if (btnAcceptCall) btnAcceptCall.classList.remove('d-none');
            if (btnRejectCall) btnRejectCall.classList.remove('d-none');
            if (btnCallToggle) btnCallToggle.classList.add('d-none');
            startRinging('in');
            if (signalTimer) clearInterval(signalTimer);
            signalTimer = setInterval(pollSignals, 2000);
            if (btnAcceptCall) {
              btnAcceptCall.classList.add('btn-pulse');
              btnAcceptCall.classList.remove('d-none');
            }
            if ('Notification' in window && Notification.permission === 'granted' && document.visibilityState !== 'visible') {
              try {
                var n = new Notification('Incoming call', {
                  body: 'From ' + (s.from_email || 'someone') + ' — tap Accept to enable camera',
                  tag: 'chat-incoming-' + (s.call_id || '0'),
                  requireInteraction: true
                });
                n.onclick = function(){ try { window.focus(); n.close(); } catch(e){} };
              } catch(e){}
            }
          });
        }
      }).catch(function(){});
  }
  function ensureIncomingPolling(){
    if (incomingTimer) clearInterval(incomingTimer);
    incomingTimer = setInterval(pollIncomingOffers, 2500);
  }

  function endCall(){
    if (callId) {
      fetch(site + 'calls/end/' + callId, { method: 'POST' }).catch(function(){});
    }
    resetCallMedia(false);
    callId = null;
    if (btnEndCall) btnEndCall.disabled = true;
    setStatus('Idle');
    if (signalTimer) clearInterval(signalTimer);
    if (incomingTimer) clearInterval(incomingTimer);
    stopRinging();
    localVideo.srcObject = null; remoteVideo.srcObject = null;
    updateVideoContainer(localVideoContainer, false);
    updateVideoContainer(remoteVideoContainer, false);
    updateVideoStatus(localVideoStatus, 'disconnected', 'Camera Off');
    updateVideoStatus(remoteVideoStatus, 'disconnected', 'Not Connected');
    btnToggleMic.disabled = true; btnToggleSpeaker.disabled = true;
    btnShareScreen.disabled = true; btnRecord.disabled = true;
    if (btnFullscreen) btnFullscreen.disabled = true;
    if (overlayRemoteVideo) overlayRemoteVideo.srcObject = null;
    if (overlayRemoteThumb) overlayRemoteThumb.srcObject = null;
    if (overlayLocalVideo)  overlayLocalVideo.srcObject  = null;
    if (overlayScreenVideo) overlayScreenVideo.srcObject = null;
    if (overlayScreenWrap)  overlayScreenWrap.classList.add('d-none');
    if (btnEndCall)    btnEndCall.classList.add('d-none');
    if (btnCallToggle) btnCallToggle.classList.remove('d-none');
    setCallToggleUI(false); setOverlayVisible(false); stopRecording();
    setWebRtcToolbarVisible(false);
    cameraEnabled = false;
  }

  // ── Screen share ──────────────────────────────────────────────────────────
  function startScreenShare(){
    if (!pc) return Promise.resolve();
    return navigator.mediaDevices.getDisplayMedia({ video: { cursor: 'always' }, audio: false })
      .then(function(stream){
        screenStream = stream;
        var screenTrack = stream.getVideoTracks()[0];
        if (!originalVideoTrack && localStream) { originalVideoTrack = localStream.getVideoTracks()[0] || null; }
        var sender = pc.getSenders().find(function(s){ return s.track && s.track.kind === 'video'; });
        var replacePromise = (sender && screenTrack) ? sender.replaceTrack(screenTrack) : Promise.resolve();
        return replacePromise.then(function(){
          localVideo.srcObject = screenStream;
          btnShareScreen.innerHTML = '<i class="bi bi-display-fill"></i>';
          if (btnOverlayScreen) btnOverlayScreen.innerHTML = '<i class="bi bi-display-fill"></i>';
          setStatus('Screen sharing');
          syncOverlayStreams();
          screenTrack.onended = function(){ stopScreenShare(); };
        });
      }).catch(function(){});
  }
  function stopScreenShare(){
    if (!pc) return Promise.resolve();
    var sender = pc.getSenders().find(function(s){ return s.track && s.track.kind === 'video'; });
    var replacePromise = (sender && originalVideoTrack) ? sender.replaceTrack(originalVideoTrack) : Promise.resolve();
    return replacePromise.then(function(){
      if (localStream) localVideo.srcObject = localStream;
      if (screenStream) { try { screenStream.getTracks().forEach(function(t){ t.stop(); }); } catch(e){} }
      screenStream = null;
      btnShareScreen.innerHTML = '<i class="bi bi-display"></i>';
      if (btnOverlayScreen) btnOverlayScreen.innerHTML = '<i class="bi bi-display"></i>';
      setStatus('Connected'); syncOverlayStreams();
    }).catch(function(){});
  }
  if (btnShareScreen) {
    btnShareScreen.addEventListener('click', function(){
      if (!pc) return;
      if (screenStream) { stopScreenShare(); } else { startScreenShare(); }
    });
  }

  // ── Recording ─────────────────────────────────────────────────────────────
  var recordTimerId = null, recordStartAt = 0;
  function fmt(t){ return (t < 10 ? '0' : '') + t; }
  function startTimer(){
    recordStartAt = Date.now(); clearInterval(recordTimerId);
    recordTimerId = setInterval(function(){
      var sec = Math.floor((Date.now() - recordStartAt) / 1000);
      recordTimerEl.textContent = fmt(Math.floor(sec / 60)) + ':' + fmt(sec % 60);
    }, 500);
  }
  function stopTimer(){ clearInterval(recordTimerId); recordTimerId = null; recordTimerEl.textContent = '00:00'; }
  function getSupportedMime(){
    var candidates = ['video/webm;codecs=vp9','video/webm;codecs=vp8','video/webm',''];
    for (var i = 0; i < candidates.length; i++) {
      if (candidates[i] && MediaRecorder.isTypeSupported && MediaRecorder.isTypeSupported(candidates[i])) return candidates[i];
    }
    return '';
  }
  function startRecording(){
    var src = screenStream || localStream; if (!src) return;
    recordedChunks = [];
    var mime = getSupportedMime();
    mediaRecorder = new MediaRecorder(src, mime ? { mimeType: mime } : {});
    mediaRecorder.ondataavailable = function(e){ if (e.data && e.data.size > 0) recordedChunks.push(e.data); };
    mediaRecorder.onstop = function(){
      try {
        var blob = new Blob(recordedChunks, { type: recordedChunks[0] ? recordedChunks[0].type : 'video/webm' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = 'recording-' + new Date().toISOString().replace(/[:.]/g,'-') + '.webm';
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(function(){ URL.revokeObjectURL(url); }, 5000);
      } catch(e){}
    };
    mediaRecorder.start(); isRecording = true; setStatus('Recording…');
    recordFooter.style.display = ''; startTimer();
    btnRecord.innerHTML = '<i class="bi bi-stop-circle"></i>';
  }
  function stopRecording(){
    if (!mediaRecorder) return;
    try { mediaRecorder.stop(); } catch(e){}
    isRecording = false; mediaRecorder = null;
    btnRecord.innerHTML = '<i class="bi bi-record-circle"></i>';
    if (pc) setStatus('Connected');
    recordFooter.style.display = 'none'; stopTimer();
  }
  function downloadFromChunks(chunks, postfix){
    try {
      if (!chunks || !chunks.length) return;
      var blob = new Blob(chunks, { type: chunks[0].type || 'video/webm' });
      var url = URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url; a.download = 'recording-' + new Date().toISOString().replace(/[:.]/g,'-') + (postfix||'') + '.webm';
      document.body.appendChild(a); a.click(); a.remove();
      setTimeout(function(){ URL.revokeObjectURL(url); }, 5000);
    } catch(e){}
  }
  if (btnSaveRecording) {
    btnSaveRecording.addEventListener('click', function(){
      if (!isRecording || !mediaRecorder) return;
      try { mediaRecorder.requestData(); } catch(e){}
      setTimeout(function(){ downloadFromChunks(recordedChunks.slice(0), '-partial'); }, 400);
    });
  }
  if (btnRecord) {
    btnRecord.addEventListener('click', function(){
      if (!pc) return;
      if (!isRecording) { startRecording(); } else { stopRecording(); }
    });
  }

  // ── Call button wiring ────────────────────────────────────────────────────
  if (btnCallToggle) btnCallToggle.addEventListener('click', function(){ if (callId) endCall(); else startCall(); });
  if (btnEndCall)    btnEndCall.addEventListener('click', endCall);

  if (btnAcceptCall) {
    btnAcceptCall.addEventListener('click', function(){
      if (!pendingRemoteOffer) return;
      var offerPayload = pendingRemoteOffer;
      resetCallMedia(true);
      initPeer().then(function(){
        return pc.setRemoteDescription(new RTCSessionDescription(JSON.parse(offerPayload)));
      }).then(function(){
        flushPendingIce();
        return pc.createAnswer();
      })
        .then(function(answer){ return pc.setLocalDescription(answer).then(function(){ return answer; }); })
        .then(function(answer){
          return fetch(site + 'calls/signal/' + callId, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({ type: 'answer', payload: JSON.stringify(answer) })
          });
        }).then(function(){
          setStatus('Connected'); stopRinging();
          if (signalTimer) clearInterval(signalTimer);
          signalTimer = setInterval(pollSignals, 2000);
          syncOverlayStreams(); setCallToggleUI(true);
          setWebRtcToolbarVisible(true);
          if (btnEndCall) btnEndCall.classList.add('d-none');
          if (!overlayOpen) setOverlayVisible(true);
        }).catch(function(err){
          setStatus('Accept failed — allow camera & microphone, then try again');
          console.warn('Accept call failed', err);
        })
        .then(function(){
          pendingRemoteOffer = null;
          if (btnAcceptCall) {
            btnAcceptCall.classList.add('d-none');
            btnAcceptCall.classList.remove('btn-pulse');
          }
          if (btnRejectCall) btnRejectCall.classList.add('d-none');
          if (btnCallToggle) btnCallToggle.classList.remove('d-none');
        });
    });
  }
  if (btnRejectCall) {
    btnRejectCall.addEventListener('click', function(){
      stopRinging(); setStatus('Call rejected'); endCall();
      pendingRemoteOffer = null;
      if (btnAcceptCall) btnAcceptCall.classList.add('d-none');
      if (btnRejectCall) btnRejectCall.classList.add('d-none');
      if (btnCallToggle) btnCallToggle.classList.remove('d-none');
    });
  }

  // ── Mic / Speaker / Camera controls ──────────────────────────────────────
  function toggleMic(){
    if (!localStream) return;
    var tracks = localStream.getAudioTracks(); if (!tracks.length) return;
    var enabled = tracks[0].enabled;
    tracks.forEach(function(t){ t.enabled = !enabled; });
    var on = !enabled;
    btnToggleMic.innerHTML = on ? '<i class="bi bi-mic"></i>' : '<i class="bi bi-mic-mute"></i>';
    if (btnOverlayMic) btnOverlayMic.innerHTML = on ? '<i class="bi bi-mic"></i>' : '<i class="bi bi-mic-mute"></i>';
  }
  function toggleCamera(){
    if (!localStream) return;
    var tracks = localStream.getVideoTracks(); if (!tracks.length) return;
    cameraEnabled = !cameraEnabled;
    tracks.forEach(function(t){ t.enabled = cameraEnabled; });
    if (!cameraEnabled && !screenStream) { localVideo.srcObject = null; }
    else if (cameraEnabled && !screenStream) { localVideo.srcObject = localStream; }
    var icon = cameraEnabled ? '<i class="bi bi-camera-video"></i>' : '<i class="bi bi-camera-video-off"></i>';
    if (btnOverlayCamera) btnOverlayCamera.innerHTML = icon;
    syncOverlayStreams();
  }
  if (btnToggleMic)    btnToggleMic.addEventListener('click', toggleMic);
  if (btnToggleSpeaker) {
    btnToggleSpeaker.addEventListener('click', function(){
      var muted = !!remoteVideo.muted;
      remoteVideo.muted = !muted;
      btnToggleSpeaker.innerHTML = muted ? '<i class="bi bi-volume-up"></i>' : '<i class="bi bi-volume-mute"></i>';
    });
  }
  if (btnLocalVideoToggle) {
    btnLocalVideoToggle.addEventListener('click', function(){
      if (!localStream) return;
      var track = localStream.getVideoTracks()[0]; if (!track) return;
      track.enabled = !track.enabled;
      btnLocalVideoToggle.querySelector('i').className = track.enabled ? 'bi bi-camera-video' : 'bi bi-camera-video-off';
      updateVideoStatus(localVideoStatus, 'connected', track.enabled ? 'Camera On' : 'Camera Off');
    });
  }
  if (btnLocalAudioToggle) {
    btnLocalAudioToggle.addEventListener('click', function(){
      if (!localStream) return;
      var track = localStream.getAudioTracks()[0]; if (!track) return;
      track.enabled = !track.enabled;
      btnLocalAudioToggle.querySelector('i').className = track.enabled ? 'bi bi-mic' : 'bi bi-mic-mute';
    });
  }
  if (btnRemoteFullscreen) {
    btnRemoteFullscreen.addEventListener('click', function(){
      if (!remoteVideo) return;
      var fn = remoteVideo.requestFullscreen || remoteVideo.webkitRequestFullscreen || remoteVideo.mozRequestFullScreen;
      if (fn) fn.call(remoteVideo);
    });
  }

  // ── Fullscreen overlay ────────────────────────────────────────────────────
  if (btnFullscreen)      btnFullscreen.addEventListener('click', function(){ if (!callId) return; setOverlayVisible(!overlayOpen); });
  if (btnOverlayMinimize) btnOverlayMinimize.addEventListener('click', function(){ setOverlayVisible(false); });
  if (btnOverlayClose)    btnOverlayClose.addEventListener('click', function(){ setOverlayVisible(false); });
  if (btnOverlayScreen)   btnOverlayScreen.addEventListener('click', function(){ if (!pc) return; if (screenStream) stopScreenShare(); else startScreenShare(); });
  if (btnOverlayMic)      btnOverlayMic.addEventListener('click', toggleMic);
  if (btnOverlayCamera)   btnOverlayCamera.addEventListener('click', toggleCamera);
  if (btnOverlayLeave)    btnOverlayLeave.addEventListener('click', endCall);

  // ── Jitsi Meeting Platform ────────────────────────────────────────────────
  var preJoinBsModal = null;
  var scheduleBsModal = null;
  try {
    if (preJoinLobbyModal && window.bootstrap && window.bootstrap.Modal) {
      preJoinBsModal = new bootstrap.Modal(preJoinLobbyModal);
    }
    if (scheduleMeetingModal && window.bootstrap && window.bootstrap.Modal) {
      scheduleBsModal = new bootstrap.Modal(scheduleMeetingModal);
    }
  } catch(e){}

  function formatMeetingTimer(ms){
    var totalSec = Math.floor(ms / 1000);
    var m = Math.floor(totalSec / 60);
    var s = totalSec % 60;
    return (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
  }

  function startMeetingTimer(){
    meetingStartedAt = Date.now();
    if (meetingTimerInterval) clearInterval(meetingTimerInterval);
    meetingTimerInterval = setInterval(function(){
      if (meetingTimerBar && meetingStartedAt) {
        meetingTimerBar.textContent = formatMeetingTimer(Date.now() - meetingStartedAt);
      }
    }, 1000);
  }

  function stopMeetingTimer(){
    if (meetingTimerInterval) { clearInterval(meetingTimerInterval); meetingTimerInterval = null; }
    if (meetingTimerBar) meetingTimerBar.textContent = '00:00';
    meetingStartedAt = 0;
  }

  function setMeetingOverlayVisible(show){
    if (!meetingOverlay) return;
    var minBar = document.getElementById('meetingMinimizedBar');
    if (show) {
      meetingActive = true;
      meetingOverlay.classList.add('show');
      meetingOverlay.setAttribute('aria-hidden', 'false');
      document.body.classList.add('meeting-active');
      if (minBar) minBar.classList.add('d-none');
      setMeetingModeBadge(true);
    } else {
      meetingOverlay.classList.remove('show');
      meetingOverlay.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('meeting-active');
      if (minBar && jitsiApi) minBar.classList.remove('d-none');
    }
  }

  function showMeetingToast(msg, isError){
    if (toastTitleEl) toastTitleEl.textContent = isError ? 'Meeting error' : 'Meeting';
    if (toastBodyEl) toastBodyEl.textContent = msg || '';
    if (toastInstance) toastInstance.show();
    setMeetingStatus(msg);
  }

  function updateMeetingButtonLabel(){
    var lbl = document.getElementById('btnStartMeetingLabel');
    if (!lbl || !btnStartMeeting) return;
    if (!convoId || !jitsiEnabled) return;
    lbl.textContent = hasActiveMeeting ? 'Join Meeting' : 'Start Meeting';
    btnStartMeeting.title = hasActiveMeeting ? 'Join ongoing meeting' : 'Start a new video meeting';
  }

  function fetchMeetingStatus(){
    if (!convoId || !jitsiEnabled) return Promise.resolve();
    return fetch(site + 'meetings/status/' + convoId)
      .then(function(r){ return r.json(); })
      .then(function(j){
        if (j && j.ok) {
          hasActiveMeeting = !!j.has_active;
          updateMeetingButtonLabel();
        }
      }).catch(function(){});
  }

  function setMeetingStatus(text){
    if (meetingStatusBar) meetingStatusBar.textContent = text || '';
    setStatus(text || 'Idle');
  }

  function showModeratorControls(show){
    document.querySelectorAll('.moderator-only').forEach(function(el){
      el.classList.toggle('d-none', !show);
    });
  }

  function loadJitsiScript(domain){
    return new Promise(function(resolve, reject){
      if (window.JitsiMeetExternalAPI) { resolve(); return; }
      var existing = document.getElementById('jitsiExternalApiScript');
      if (existing) {
        existing.addEventListener('load', function(){ resolve(); });
        existing.addEventListener('error', function(){ reject(new Error('Jitsi script failed')); });
        return;
      }
      var s = document.createElement('script');
      s.id = 'jitsiExternalApiScript';
      s.src = 'https://' + domain + '/external_api.js';
      s.async = true;
      s.onload = function(){ resolve(); };
      s.onerror = function(){ reject(new Error('Could not load Jitsi from ' + domain)); };
      document.head.appendChild(s);
    });
  }

  function stopLobbyStream(){
    if (lobbyStream) {
      try { lobbyStream.getTracks().forEach(function(t){ t.stop(); }); } catch(e){}
      lobbyStream = null;
    }
    if (lobbyPreviewVideo) lobbyPreviewVideo.srcObject = null;
  }

  function populateLobbyDevices(){
    if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return Promise.resolve();
    return navigator.mediaDevices.enumerateDevices().then(function(devices){
      if (lobbyAudioInput) {
        lobbyAudioInput.innerHTML = '';
        devices.filter(function(d){ return d.kind === 'audioinput'; }).forEach(function(d, i){
          var opt = document.createElement('option');
          opt.value = d.deviceId;
          opt.textContent = d.label || ('Microphone ' + (i + 1));
          lobbyAudioInput.appendChild(opt);
        });
      }
      if (lobbyVideoInput) {
        lobbyVideoInput.innerHTML = '';
        devices.filter(function(d){ return d.kind === 'videoinput'; }).forEach(function(d, i){
          var opt = document.createElement('option');
          opt.value = d.deviceId;
          opt.textContent = d.label || ('Camera ' + (i + 1));
          lobbyVideoInput.appendChild(opt);
        });
      }
    }).catch(function(){});
  }

  function startLobbyPreview(){
    stopLobbyStream();
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return Promise.resolve();
    var constraints = {
      audio: lobbyMicOn,
      video: lobbyCamOn ? { facingMode: 'user' } : false
    };
    return navigator.mediaDevices.getUserMedia(constraints).then(function(stream){
      lobbyStream = stream;
      if (lobbyPreviewVideo) {
        lobbyPreviewVideo.srcObject = stream;
        lobbyPreviewVideo.classList.toggle('d-none', !lobbyCamOn);
      }
      if (lobbyPreviewPlaceholder) {
        lobbyPreviewPlaceholder.classList.toggle('d-none', lobbyCamOn);
      }
      return populateLobbyDevices();
    }).catch(function(){
      if (lobbyPreviewPlaceholder) lobbyPreviewPlaceholder.classList.remove('d-none');
    });
  }

  function openPreJoinLobby(isStart){
    if (!jitsiEnabled) {
      showMeetingToast('Jitsi is not configured. Add it under API Integrations.', true);
      return;
    }
    if (!convoId && !pendingScheduledMeetingId) return;
    lobbyIsStart = !!isStart;
    var activeBtn = convoId ? findConvoButtonById(convoId) : null;
    var title = activeBtn ? (activeBtn.querySelector('.fw-semibold') || {}).textContent : 'Scheduled meeting';
    if (lobbyMeetingTitle) lobbyMeetingTitle.textContent = title || 'Ready to join?';
    if (lobbyMeetingMeta) {
      var mode = isStart && !hasActiveMeeting ? 'Starting meeting' : 'Joining meeting';
      lobbyMeetingMeta.textContent = (currentConvoType === 'group' ? 'Group video' : 'Video meeting') + ' — ' + mode;
      if (jitsiSecurityWarning) {
        lobbyMeetingMeta.textContent += '. Note: ' + jitsiSecurityWarning;
      }
    }
    lobbyMicOn = true;
    lobbyCamOn = true;
    updateLobbyToggleButtons();
    startLobbyPreview().then(function(){
      if (preJoinBsModal) preJoinBsModal.show();
      else if (preJoinLobbyModal) preJoinLobbyModal.classList.add('show');
    });
  }

  function updateLobbyToggleButtons(){
    if (lobbyToggleMic) {
      lobbyToggleMic.innerHTML = lobbyMicOn
        ? '<i class="bi bi-mic-fill"></i> Mic on'
        : '<i class="bi bi-mic-mute-fill"></i> Mic off';
      lobbyToggleMic.classList.toggle('btn-danger', !lobbyMicOn);
    }
    if (lobbyToggleCam) {
      lobbyToggleCam.innerHTML = lobbyCamOn
        ? '<i class="bi bi-camera-video-fill"></i> Cam on'
        : '<i class="bi bi-camera-video-off"></i> Cam off';
      lobbyToggleCam.classList.toggle('btn-danger', !lobbyCamOn);
    }
  }

  if (lobbyToggleMic) {
    lobbyToggleMic.addEventListener('click', function(){
      lobbyMicOn = !lobbyMicOn;
      if (lobbyStream) {
        var at = lobbyStream.getAudioTracks()[0];
        if (at) at.enabled = lobbyMicOn;
      }
      updateLobbyToggleButtons();
    });
  }
  if (lobbyToggleCam) {
    lobbyToggleCam.addEventListener('click', function(){
      lobbyCamOn = !lobbyCamOn;
      if (lobbyStream) {
        var vt = lobbyStream.getVideoTracks()[0];
        if (vt) vt.enabled = lobbyCamOn;
      }
      if (lobbyPreviewVideo) lobbyPreviewVideo.classList.toggle('d-none', !lobbyCamOn);
      if (lobbyPreviewPlaceholder) lobbyPreviewPlaceholder.classList.toggle('d-none', lobbyCamOn);
      updateLobbyToggleButtons();
    });
  }
  if (preJoinLobbyModal) {
    preJoinLobbyModal.addEventListener('hidden.bs.modal', stopLobbyStream);
  }

  function destroyJitsiApi(){
    if (jitsiApi) {
      try { jitsiApi.dispose(); } catch(e){}
      jitsiApi = null;
    }
    if (jitsiMeetContainer) jitsiMeetContainer.innerHTML = '';
  }

  function joinJitsiMeeting(payload){
    if (!payload || !payload.domain || !payload.room_name) return;
    destroyJitsiApi();
    setMeetingStatus('Connecting to meeting…');
    setMeetingOverlayVisible(true);
    if (meetingTitleBar) meetingTitleBar.textContent = payload.conversation_title || 'Meeting';
    meetingIsModerator = !!payload.is_moderator;
    showModeratorControls(meetingIsModerator);
    setMeetingModeBadge(true);

    return loadJitsiScript(payload.domain).then(function(){
      var options = {
        roomName: payload.room_name,
        parentNode: jitsiMeetContainer,
        width: '100%',
        height: '100%',
        userInfo: { displayName: payload.display_name || userDisplayName },
        configOverwrite: {
          prejoinPageEnabled: false,
          startWithAudioMuted: !lobbyMicOn,
          startWithVideoMuted: !lobbyCamOn,
          disableDeepLinking: true,
          toolbarButtons: [],
        },
        interfaceConfigOverwrite: {
          TOOLBAR_BUTTONS: [],
          SHOW_JITSI_WATERMARK: false,
          SHOW_WATERMARK_FOR_GUESTS: false,
          DISABLE_JOIN_LEAVE_NOTIFICATIONS: false,
          MOBILE_APP_PROMO: false,
        },
      };
      if (payload.jwt) options.jwt = payload.jwt;

      jitsiApi = new JitsiMeetExternalAPI(payload.domain, options);

      jitsiApi.addListener('videoConferenceJoined', function(){
        setMeetingStatus('Connected');
        startMeetingTimer();
        stopLobbyStream();
        if (preJoinBsModal) preJoinBsModal.hide();
        hasActiveMeeting = true;
        updateMeetingButtonLabel();
      });
      jitsiApi.addListener('videoConferenceLeft', function(){
        leaveJitsiMeeting();
      });
      jitsiApi.addListener('participantJoined', function(){
        fetchMeetingParticipantsPanel();
      });
      jitsiApi.addListener('participantLeft', function(){
        fetchMeetingParticipantsPanel();
      });
      jitsiApi.addListener('connectionQualityChanged', function(data){
        var bar = document.getElementById('meetingQualityBar');
        if (!bar) return;
        var q = (data && data.quality) ? parseInt(data.quality, 10) : 0;
        var label = q >= 70 ? 'Good' : (q >= 40 ? 'Fair' : 'Poor');
        bar.classList.remove('d-none');
        bar.querySelector('span').textContent = label;
      });
      jitsiApi.addListener('recordingStatusChanged', function(data){
        var btn = document.getElementById('btnMeetRecord');
        if (btn) btn.classList.toggle('active', !!(data && data.on));
      });
      jitsiApi.addListener('audioMuteStatusChanged', function(data){
        if (btnMeetMic) {
          btnMeetMic.classList.toggle('active', !!(data && data.muted));
          var ic = btnMeetMic.querySelector('i');
          if (ic) ic.className = (data && data.muted) ? 'bi bi-mic-mute-fill' : 'bi bi-mic-fill';
        }
      });
      jitsiApi.addListener('videoMuteStatusChanged', function(data){
        if (btnMeetCam) {
          btnMeetCam.classList.toggle('active', !!(data && data.muted));
          var ic = btnMeetCam.querySelector('i');
          if (ic) ic.className = (data && data.muted) ? 'bi bi-camera-video-off-fill' : 'bi bi-camera-video-fill';
        }
      });
      jitsiApi.addListener('screenSharingStatusChanged', function(data){
        if (btnMeetScreen) {
          btnMeetScreen.classList.toggle('active', !!(data && data.on));
        }
      });
    }).catch(function(err){
      showMeetingToast('Meeting failed: ' + (err.message || 'unknown'), true);
      setMeetingOverlayVisible(false);
    });
  }

  function requestMeetingJoin(isStart){
    if (pendingScheduledMeetingId) {
      return fetch(site + 'meetings/join-scheduled/' + pendingScheduledMeetingId, { method: 'GET' })
        .then(function(r){ if (handleUnauthorized(r)) throw new Error('unauth'); return r.json(); })
        .then(function(j){
          if (!j || !j.ok) throw new Error((j && j.error) ? j.error : 'Unable to join scheduled meeting');
          if (j.security_warning) showMeetingToast(j.security_warning, false);
          return j;
        });
    }
    var endpoint = (isStart && !hasActiveMeeting) ? 'meetings/start/' : 'meetings/join/';
    var method = (isStart && !hasActiveMeeting) ? 'POST' : 'GET';
    return fetch(site + endpoint + convoId, { method: method })
      .then(function(r){ if (handleUnauthorized(r)) throw new Error('unauth'); return r.json(); })
      .then(function(j){
        if (!j || !j.ok) {
          if (j && j.use_fallback && currentConvoType !== 'group') {
            startCall();
            return null;
          }
          throw new Error((j && j.error) ? j.error : 'Unable to join meeting');
        }
        if (j.security_warning) showMeetingToast(j.security_warning, false);
        if (isStart && !hasActiveMeeting) hasActiveMeeting = true;
        return j;
      });
  }

  function leaveJitsiMeeting(){
    destroyJitsiApi();
    stopMeetingTimer();
    meetingActive = false;
    setMeetingOverlayVisible(false);
    setMeetingStatus('Meeting ended');
    setMeetingModeBadge(false);
    hasActiveMeeting = false;
    updateMeetingButtonLabel();
    var minBar = document.getElementById('meetingMinimizedBar');
    if (minBar) minBar.classList.add('d-none');
    if (convoId && meetingIsModerator) {
      fetch(site + 'meetings/end/' + convoId, { method: 'POST' }).catch(function(){});
    }
    pendingScheduledMeetingId = 0;
    if (meetingSidePanel) meetingSidePanel.classList.add('d-none');
  }

  if (btnLobbyJoin) {
    btnLobbyJoin.addEventListener('click', function(){
      var origHtml = btnLobbyJoin.innerHTML;
      btnLobbyJoin.disabled = true;
      btnLobbyJoin.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Joining…';
      requestMeetingJoin(lobbyIsStart).then(function(payload){
        if (payload) joinJitsiMeeting(payload);
      }).catch(function(e){
        if (String(e) !== 'Error: unauth') showMeetingToast(e.message || 'Join failed', true);
      }).finally(function(){
        btnLobbyJoin.disabled = false;
        btnLobbyJoin.innerHTML = origHtml;
      });
    });
  }

  if (btnStartMeeting) {
    btnStartMeeting.addEventListener('click', function(){
      if (callId) {
        showMeetingToast('End the quick call before joining a video meeting.', true);
        return;
      }
      if (!jitsiEnabled) {
        showMeetingToast('Configure Jitsi under API Integrations first.', true);
        return;
      }
      if (currentConvoType === 'group' || jitsiEnabled) {
        fetchMeetingStatus().then(function(){
          openPreJoinLobby(!hasActiveMeeting);
        });
      } else {
        startCall();
      }
    });
  }

  function meetingExecute(cmd, args){
    if (!jitsiApi) return;
    try { jitsiApi.executeCommand(cmd, args); } catch(e){}
  }

  var btnMeetMic = document.getElementById('btnMeetMic');
  var btnMeetCam = document.getElementById('btnMeetCam');
  var btnMeetScreen = document.getElementById('btnMeetScreen');
  var btnMeetParticipants = document.getElementById('btnMeetParticipants');
  var btnMeetPip = document.getElementById('btnMeetPip');
  var btnMeetRecord = document.getElementById('btnMeetRecord');
  var btnMeetMuteAll = document.getElementById('btnMeetMuteAll');
  var btnMeetLobby = document.getElementById('btnMeetLobby');
  var btnMeetLeave = document.getElementById('btnMeetLeave');
  var btnMeetingMinimize = document.getElementById('btnMeetingMinimize');
  var btnMeetingChatToggle = document.getElementById('btnMeetingChatToggle');

  if (btnMeetMic) btnMeetMic.addEventListener('click', function(){ meetingExecute('toggleAudio'); });
  if (btnMeetCam) btnMeetCam.addEventListener('click', function(){ meetingExecute('toggleVideo'); });
  if (btnMeetScreen) btnMeetScreen.addEventListener('click', function(){ meetingExecute('toggleShareScreen'); });
  if (btnMeetParticipants) {
    btnMeetParticipants.addEventListener('click', function(){
      if (!meetingSidePanel) return;
      meetingSidePanel.classList.toggle('d-none');
      fetchMeetingParticipantsPanel();
    });
  }
  if (btnMeetPip) {
    btnMeetPip.addEventListener('click', function(){
      var iframe = jitsiMeetContainer ? jitsiMeetContainer.querySelector('iframe') : null;
      if (!iframe) return;
      if (iframe.requestPictureInPicture) {
        iframe.requestPictureInPicture().catch(function(){});
      }
    });
  }
  if (btnMeetRecord) {
    btnMeetRecord.addEventListener('click', function(){
      if (!meetingIsModerator) return;
      meetingExecute('toggleRecording');
    });
  }
  if (btnMeetMuteAll) {
    btnMeetMuteAll.addEventListener('click', function(){
      if (!meetingIsModerator) return;
      meetingExecute('muteEveryone');
    });
  }
  if (btnMeetLobby) {
    btnMeetLobby.addEventListener('click', function(){
      if (!meetingIsModerator) return;
      lobbyLobbyOn = !lobbyLobbyOn;
      meetingExecute('toggleLobby', lobbyLobbyOn);
      btnMeetLobby.classList.toggle('active', lobbyLobbyOn);
    });
  }
  if (btnMeetLeave) btnMeetLeave.addEventListener('click', leaveJitsiMeeting);
  if (btnMeetingMinimize) btnMeetingMinimize.addEventListener('click', function(){ setMeetingOverlayVisible(false); });
  var btnMeetingRestore = document.getElementById('btnMeetingRestore');
  var btnMeetingLeaveFromBar = document.getElementById('btnMeetingLeaveFromBar');
  if (btnMeetingRestore) btnMeetingRestore.addEventListener('click', function(){ setMeetingOverlayVisible(true); });
  if (btnMeetingLeaveFromBar) btnMeetingLeaveFromBar.addEventListener('click', leaveJitsiMeeting);
  if (btnMeetingChatToggle) {
    btnMeetingChatToggle.addEventListener('click', function(){
      if (!meetingSidePanel) return;
      meetingSidePanel.classList.remove('d-none');
      document.querySelectorAll('.meeting-panel-tabs button').forEach(function(b){
        b.classList.toggle('active', b.getAttribute('data-panel') === 'chat');
      });
      document.getElementById('meetingPanelParticipants').classList.add('d-none');
      document.getElementById('meetingPanelChat').classList.remove('d-none');
    });
  }

  document.querySelectorAll('.meeting-panel-tabs button').forEach(function(btn){
    btn.addEventListener('click', function(){
      var panel = btn.getAttribute('data-panel');
      document.querySelectorAll('.meeting-panel-tabs button').forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      document.getElementById('meetingPanelParticipants').classList.toggle('d-none', panel !== 'participants');
      document.getElementById('meetingPanelChat').classList.toggle('d-none', panel !== 'chat');
    });
  });

  document.addEventListener('keydown', function(e){
    if (!meetingActive) return;
    var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
    if (tag === 'input' || tag === 'textarea') return;
    if (e.key === 'm' || e.key === 'M') { meetingExecute('toggleAudio'); e.preventDefault(); }
    if (e.key === 'v' || e.key === 'V') { meetingExecute('toggleVideo'); e.preventDefault(); }
    if (e.key === 'd' || e.key === 'D') { meetingExecute('toggleShareScreen'); e.preventDefault(); }
    if (e.key === 'l' || e.key === 'L') { leaveJitsiMeeting(); e.preventDefault(); }
    if (e.key === 'Escape') { setMeetingOverlayVisible(false); }
  });

  function fetchMeetingParticipantsPanel(){
    if (!convoId || !meetingParticipantList) return;
    fetch(site + 'meetings/participants/' + convoId)
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (!data || !data.ok) return;
        meetingParticipantList.innerHTML = '';
        (data.participants || []).forEach(function(p){
          var li = document.createElement('li');
          var online = onlineUserIds[p.id];
          li.innerHTML = '<span class="presence-dot ' + (online ? 'online' : 'offline') + '"></span>' +
            '<span class="participant-name">' + htmlEsc(p.name || p.email) + '</span>' +
            (parseInt(p.id, 10) === userId ? ' <span class="badge bg-secondary">You</span>' : '');
          meetingParticipantList.appendChild(li);
        });
      }).catch(function(){});
  }

  function refreshPresenceDots(participantIds){
    if (!participantIds) return;
    fetch(site + 'chats/get-online-status?user_ids=' + encodeURIComponent(participantIds))
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (!data || !data.ok || !data.status) return;
        onlineUserIds = {};
        var rows = Array.isArray(data.status) ? data.status : [];
        rows.forEach(function(row){
          var uid = parseInt(row.user_id, 10);
          if (uid) onlineUserIds[uid] = !!parseInt(row.is_online, 10);
        });
        document.querySelectorAll('#convoList .convo-item').forEach(function(item){
          var ids = (item.getAttribute('data-participant-ids') || '').split(',');
          var avatar = item.querySelector('.avatar');
          if (!avatar) return;
          var anyOnline = ids.some(function(id){
            id = parseInt(id, 10);
            return id && id !== userId && data.status[id];
          });
          avatar.classList.toggle('online', anyOnline);
        });
        if (convoId) fetchMeetingParticipantsPanel();
      }).catch(function(){});
  }

  function ensurePresencePolling(){
    if (presenceTimer) clearInterval(presenceTimer);
    presenceTimer = setInterval(function(){
      var allIds = [];
      document.querySelectorAll('#convoList .convo-item').forEach(function(item){
        var ids = (item.getAttribute('data-participant-ids') || '').split(',');
        ids.forEach(function(id){
          id = parseInt(id, 10);
          if (id && allIds.indexOf(id) === -1) allIds.push(id);
        });
      });
      if (allIds.length) refreshPresenceDots(allIds.join(','));
    }, 15000);
  }
  ensurePresencePolling();
  setTimeout(function(){
    var allIds = [];
    document.querySelectorAll('#convoList .convo-item').forEach(function(item){
      (item.getAttribute('data-participant-ids') || '').split(',').forEach(function(id){
        id = parseInt(id, 10);
        if (id && allIds.indexOf(id) === -1) allIds.push(id);
      });
    });
    if (allIds.length) refreshPresenceDots(allIds.join(','));
  }, 1500);

  // ── Schedule meetings ─────────────────────────────────────────────────────
  function loadUpcomingMeetings(){
    if (!upcomingMeetingsList) return;
    fetch(site + 'meetings/list')
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (!data || !data.ok || !data.meetings || !data.meetings.length) {
          if (upcomingMeetingsWrap) upcomingMeetingsWrap.classList.add('d-none');
          return;
        }
        if (upcomingMeetingsWrap) upcomingMeetingsWrap.classList.remove('d-none');
        upcomingMeetingsList.innerHTML = '';
        data.meetings.forEach(function(m){
          var li = document.createElement('li');
          li.className = 'list-group-item d-flex justify-content-between align-items-center';
          li.innerHTML = '<div><strong>' + htmlEsc(m.title) + '</strong><br><small>' + htmlEsc(m.scheduled_at) + '</small></div>' +
            '<div class="d-flex gap-1">' +
            '<a href="' + htmlEsc(m.join_url) + '" class="btn btn-sm btn-outline-primary">Join</a>' +
            '<button type="button" class="btn btn-sm btn-outline-danger" data-cancel-id="' + m.id + '">Cancel</button></div>';
          upcomingMeetingsList.appendChild(li);
        });
        upcomingMeetingsList.querySelectorAll('[data-cancel-id]').forEach(function(btn){
          btn.addEventListener('click', function(){
            var mid = btn.getAttribute('data-cancel-id');
            fetch(site + 'meetings/cancel/' + mid, { method: 'POST' })
              .then(function(r){ return r.json(); })
              .then(function(j){ if (j && j.ok) loadUpcomingMeetings(); });
          });
        });
      }).catch(function(){});
  }

  if (btnScheduleMeeting) {
    btnScheduleMeeting.addEventListener('click', function(){
      var schedDt = document.getElementById('schedDateTime');
      if (schedDt) {
        var d = new Date();
        d.setMinutes(d.getMinutes() + 30 - (d.getMinutes() % 15));
        schedDt.value = d.toISOString().slice(0, 16);
      }
      var schedTitle = document.getElementById('schedTitle');
      if (schedTitle && !schedTitle.value) {
        var activeBtn = findConvoButtonById(convoId);
        schedTitle.value = activeBtn ? (activeBtn.querySelector('.fw-semibold') || {}).textContent : 'Team Meeting';
      }
      loadUpcomingMeetings();
      if (scheduleBsModal) scheduleBsModal.show();
    });
  }

  if (btnSubmitSchedule) {
    btnSubmitSchedule.addEventListener('click', function(){
      var title = (document.getElementById('schedTitle') || {}).value || '';
      var scheduled_at = (document.getElementById('schedDateTime') || {}).value || '';
      var duration = (document.getElementById('schedDuration') || {}).value || '60';
      var emails = (document.getElementById('schedEmails') || {}).value || '';
      var notes = (document.getElementById('schedNotes') || {}).value || '';
      if (!title || !scheduled_at) {
        showMeetingToast('Title and date/time are required.', true);
        return;
      }
      var fd = new FormData();
      fd.append('conversation_id', convoId);
      fd.append('title', title);
      fd.append('scheduled_at', scheduled_at);
      fd.append('duration_minutes', duration);
      fd.append('participant_emails', emails);
      fd.append('notes', notes);
      btnSubmitSchedule.disabled = true;
      fetch(site + 'meetings/schedule', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(j){
          if (j && j.ok) {
            if (scheduleBsModal) scheduleBsModal.hide();
            showMeetingToast('Meeting scheduled. Invite link: ' + (j.join_url || ''), false);
            loadUpcomingMeetings();
          } else {
            showMeetingToast((j && j.error) ? j.error : 'Schedule failed', true);
          }
        }).catch(function(){
          showMeetingToast('Schedule request failed', true);
        }).finally(function(){
          btnSubmitSchedule.disabled = false;
        });
    });
  }

  function handleScheduledDeepLink(){
    if (!pendingScheduledMeetingId) return;
    fetch(site + 'meetings/scheduled/' + pendingScheduledMeetingId)
      .then(function(r){ return r.json(); })
      .then(function(j){
        if (!j || !j.ok || !j.meeting) {
          showMeetingToast((j && j.error) ? j.error : 'Scheduled meeting not found', true);
          return;
        }
        var m = j.meeting;
        if (m.conversation_id) {
          focusConversationById(m.conversation_id);
        }
        if (lobbyMeetingTitle) lobbyMeetingTitle.textContent = m.title || 'Scheduled meeting';
        setTimeout(function(){ openPreJoinLobby(false); }, m.conversation_id ? 800 : 300);
      }).catch(function(){
        showMeetingToast('Could not load scheduled meeting', true);
      });
  }

  if (pendingScheduledMeetingId) {
    setTimeout(handleScheduledDeepLink, 1000);
  }

  // ── Reminder ──────────────────────────────────────────────────────────────
  if (btnReminder) {
    btnReminder.addEventListener('click', function(){
      if (!convoId) return;
      var to = prompt('Enter recipient email for reminder:');
      if (!to) return;
      var fd = new FormData();
      fd.append('to', to);
      fd.append('subject', 'Reminder for conversation #' + convoId);
      fd.append('message', 'Reminder for conversation #' + convoId + '.\nSent at ' + new Date().toLocaleString());
      fetch(site + 'mail/send', { method: 'POST', body: fd })
        .then(function(r){ if (r.ok || r.redirected) { toastTitleEl.textContent = 'Reminder sent'; toastBodyEl.textContent = 'Email sent to ' + to; if (toastInstance) toastInstance.show(); } })
        .catch(function(){ console.warn('Reminder failed'); });
    });
  }

  // ── Conversation list click ───────────────────────────────────────────────
  if (convoList) {
    convoList.addEventListener('click', function(e){
      var btn = e.target.closest('.convo-item');
      if (btn) selectConvo(btn);
    });
    // Auto-select on load
    function autoSelectFirst(){
      try {
        var initialId = parseInt(convoList.getAttribute('data-initial-id') || '0', 10);
        if (initialId) { focusConversationById(initialId); }
        if (!convoId) {
          var first = convoList.querySelector('.convo-item');
          if (first) selectConvo(first);
        }
      } catch(e){}
    }
    autoSelectFirst();
    setTimeout(autoSelectFirst, 200);
    setTimeout(function(){ if (!convoId) { var f = convoList.querySelector('.convo-item'); if (f) f.click(); } }, 800);
    document.addEventListener('DOMContentLoaded', autoSelectFirst);
    if (initialAutoCallId) {
      setTimeout(function(){
        setStatus('Incoming call — click Accept and allow camera & microphone');
      }, 500);
    }
  }

})();
</script>
<?php $this->load->view('partials/footer'); ?>
