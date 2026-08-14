<?php
$conversations = isset($conversations) ? $conversations : array();
$conversation = isset($conversation) ? $conversation : null;
$messages = isset($messages) ? $messages : array();
$templates = isset($templates) ? $templates : array();
$webhook_url = isset($webhook_url) ? $webhook_url : site_url('whatsapp/webhook');
$employees = isset($employees) ? $employees : array();
$window = isset($window) ? $window : array('open' => false, 'hours_left' => 0, 'expires_at' => null);
$wa_palette = array('#00a884', '#53bdeb', '#06cf9c', '#e67e22', '#9b59b6', '#3498db', '#1abc9c', '#d35400');
if (!function_exists('wa_inbox_initials')) {
    function wa_inbox_initials($name, $phone)
    {
        $name = trim((string) $name);
        if ($name !== '') {
            $parts = preg_split('/\s+/', $name);
            $ini = strtoupper(substr($parts[0], 0, 1));
            if (!empty($parts[1])) {
                $ini .= strtoupper(substr($parts[1], 0, 1));
            }
            return $ini;
        }
        $d = preg_replace('/\D/', '', (string) $phone);
        return $d !== '' ? substr($d, -2) : '?';
    }
}
if (!function_exists('wa_inbox_time')) {
    function wa_inbox_time($dt)
    {
        if (!$dt) {
            return '';
        }
        $ts = strtotime($dt);
        if ($ts === false) {
            return '';
        }
        if (date('Y-m-d', $ts) === date('Y-m-d')) {
            return date('H:i', $ts);
        }
        if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) {
            return 'Yesterday';
        }
        return date('d/m/Y', $ts);
    }
}
$this->load->view('partials/header', [
  'title' => 'WhatsApp',
  'active' => 'whatsapp',
  'extra_css' => ['assets/css/whatsapp.css'],
]);
?>

<div class="wa-app<?php echo $conversation ? ' thread-open' : ''; ?>">
  <div class="wa-toast-stack" id="waToastStack" aria-live="polite" aria-atomic="true">
    <?php
    $flash_success = $this->session->flashdata('success');
    $flash_error = $this->session->flashdata('error');
    $flash_warning = $this->session->flashdata('warning');
    ?>
    <?php if ($flash_success): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-1"></i><?php echo esc_view($flash_success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if ($flash_warning): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i><?php echo esc_view($flash_warning); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-1"></i><?php echo esc_view($flash_error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
  </div>
  <div class="wa-pane-list">
    <div class="wa-list-head">
      <div class="wa-avatar sm wa-me" title="WhatsApp"><i class="bi bi-whatsapp"></i></div>
      <h1>Chats</h1>
      <button type="button" class="wa-icon-btn" title="New chat" data-bs-toggle="modal" data-bs-target="#waNewChat">
        <i class="bi bi-chat-left-text"></i>
      </button>
      <div class="dropdown">
        <button type="button" class="wa-icon-btn" title="Menu" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end wa-menu">
          <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#waNewChat">New chat</button></li>
          <li><a class="dropdown-item" href="<?php echo site_url('whatsapp/templates'); ?>">Templates</a></li>
          <li>
            <?php echo form_open('whatsapp/test-connection'); ?>
              <input type="hidden" name="back" value="inbox">
              <button type="submit" class="dropdown-item">Test connection</button>
            <?php echo form_close(); ?>
          </li>
          <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#waToolsModal">Staff tools</button></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?php echo site_url('api-integrations'); ?>">API settings</a></li>
        </ul>
      </div>
    </div>
    <?php if (!$config_configured): ?>
      <div class="wa-banner">
        Phone not connected.
        <a href="<?php echo site_url('api-integrations'); ?>">Add Meta credentials</a>
      </div>
    <?php elseif (empty($templates)): ?>
      <div class="wa-banner">
        No templates cached. First contact needs a Meta template name.
        <a href="<?php echo site_url('whatsapp/templates'); ?>">Add or sync templates</a>
      </div>
    <?php endif; ?>
    <div class="wa-tabs" role="tablist">
      <button type="button" class="wa-tab active" data-wa-tab="chats">Chats</button>
      <button type="button" class="wa-tab" data-wa-tab="templates">Templates<?php echo count($templates) ? ' (' . count($templates) . ')' : ''; ?></button>
    </div>
    <div class="wa-search" id="waSearchWrap">
      <i class="bi bi-search"></i>
      <input type="search" id="waChatSearch" placeholder="Search or start a new chat" autocomplete="off">
    </div>
    <div class="wa-conv-scroll" id="waConvList">
      <?php if (empty($conversations)): ?>
        <div class="wa-empty-list">No chats yet</div>
      <?php else: ?>
        <?php foreach ($conversations as $c): ?>
          <?php
            $is_active = $conversation && (int) $conversation->id === (int) $c->id;
            $unread = (int) $c->unread_count;
            $color = $wa_palette[abs(crc32((string) $c->wa_id)) % count($wa_palette)];
            $label = $c->profile_name ? $c->profile_name : $c->wa_id;
          ?>
          <a href="<?php echo site_url('whatsapp?c=' . (int) $c->id); ?>"
             class="wa-conv-item<?php echo $is_active ? ' active' : ''; ?><?php echo $unread > 0 ? ' has-unread' : ''; ?>"
             data-search="<?php echo esc_view(strtolower($label . ' ' . $c->wa_id)); ?>">
            <div class="wa-avatar" style="background:<?php echo esc_view($color); ?>">
              <?php echo esc_view(wa_inbox_initials($c->profile_name, $c->wa_id)); ?>
            </div>
            <div class="wa-conv-body">
              <div class="wa-conv-top">
                <div class="wa-conv-name"><?php echo esc_view($label); ?></div>
                <div class="wa-conv-time"><?php echo esc_view(wa_inbox_time($c->last_at)); ?></div>
              </div>
              <div class="wa-conv-bottom">
                <div class="wa-conv-preview"><?php echo esc_view($c->last_message ? $c->last_message : ''); ?></div>
                <?php if ($unread > 0): ?>
                  <span class="wa-unread"><?php echo $unread; ?></span>
                <?php endif; ?>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="wa-conv-scroll d-none" id="waTplList">
      <?php if (empty($templates)): ?>
        <div class="wa-empty-list">
          No approved templates.
          <a href="<?php echo site_url('whatsapp/templates'); ?>">Sync from Meta</a>
        </div>
      <?php else: ?>
        <?php foreach ($templates as $tpl): ?>
          <button type="button" class="wa-conv-item wa-tpl-item" data-tpl="<?php echo esc_view($tpl['name']); ?>" data-lang="<?php echo esc_view($tpl['language']); ?>" data-vars="<?php echo (int) whatsapp_template_placeholder_count(isset($tpl['body']) ? $tpl['body'] : ''); ?>" data-body="<?php echo esc_view(isset($tpl['body']) ? $tpl['body'] : ''); ?>">
            <div class="wa-avatar" style="background:#00a884"><i class="bi bi-file-earmark-text"></i></div>
            <div class="wa-conv-body">
              <div class="wa-conv-top">
                <div class="wa-conv-name"><?php echo esc_view($tpl['name']); ?></div>
                <div class="wa-conv-time"><?php echo esc_view($tpl['language']); ?></div>
              </div>
              <div class="wa-conv-preview"><?php echo esc_view(!empty($tpl['body']) ? mb_substr($tpl['body'], 0, 80) : $tpl['category']); ?></div>
            </div>
          </button>
        <?php endforeach; ?>
        <div class="p-2 text-center">
          <a class="small" href="<?php echo site_url('whatsapp/templates'); ?>">View all templates</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="wa-pane-chat">
    <?php if (!$conversation): ?>
      <div class="wa-empty-chat">
        <div class="wa-logo"><i class="bi bi-whatsapp"></i></div>
        <h2>WhatsApp Web</h2>
        <p>Send and receive messages without keeping your phone online.<br>Select a chat from the left to start messaging.</p>
        <div class="wa-empty-foot"><i class="bi bi-lock-fill"></i> End-to-end encrypted</div>
      </div>
    <?php else: ?>
      <?php
        $chat_label = $conversation->profile_name ? $conversation->profile_name : $conversation->wa_id;
        $chat_color = $wa_palette[abs(crc32((string) $conversation->wa_id)) % count($wa_palette)];
      ?>
      <div class="wa-chat-head">
        <a href="<?php echo site_url('whatsapp'); ?>" class="wa-icon-btn d-md-none" title="Back"><i class="bi bi-arrow-left"></i></a>
        <div class="wa-avatar sm" style="background:<?php echo esc_view($chat_color); ?>">
          <?php echo esc_view(wa_inbox_initials($conversation->profile_name, $conversation->wa_id)); ?>
        </div>
        <div class="wa-chat-meta">
          <strong><?php echo esc_view($chat_label); ?></strong>
          <span><?php echo esc_view($conversation->wa_id); ?></span>
        </div>
        <?php if (!empty($window['open'])): ?>
          <span class="wa-window open" title="Customer messaged within 24 hours — free text allowed">
            <i class="bi bi-chat-dots"></i> <?php echo esc_view((string) $window['hours_left']); ?>h left
          </span>
        <?php else: ?>
          <span class="wa-window closed" title="Outside 24-hour window — use an approved template">
            <i class="bi bi-lock"></i> Template only
          </span>
        <?php endif; ?>
      </div>
      <div class="wa-thread-msgs" id="waThreadMsgs">
        <?php if (empty($messages)): ?>
          <div class="wa-day-wrap"><span class="wa-day">No messages yet</span></div>
        <?php else: ?>
          <?php
            $last_day = '';
            foreach ($messages as $m):
              $day = $m->created_at ? date('Y-m-d', strtotime($m->created_at)) : '';
              if ($day !== '' && $day !== $last_day):
                $last_day = $day;
                if ($day === date('Y-m-d')) {
                    $day_label = 'Today';
                } elseif ($day === date('Y-m-d', strtotime('-1 day'))) {
                    $day_label = 'Yesterday';
                } else {
                    $day_label = date('d M Y', strtotime($day));
                }
          ?>
            <div class="wa-day-wrap"><span class="wa-day"><?php echo esc_view($day_label); ?></span></div>
          <?php endif; ?>
            <div class="wa-row <?php echo $m->direction === 'out' ? 'out' : 'in'; ?>">
              <div class="wa-bubble">
                <div class="wa-bubble-text"><?php echo nl2br(esc_view($m->body)); ?></div>
                <div class="wa-bubble-meta">
                  <span><?php echo $m->created_at ? date('H:i', strtotime($m->created_at)) : ''; ?></span>
                  <?php if ($m->direction === 'out'): ?>
                    <?php
                      $st = strtolower((string) $m->status);
                      $tick_class = in_array($st, array('delivered', 'read', 'played'), true) ? 'wa-ticks' : 'wa-ticks pending';
                      $tick_icon = ($st === 'read' || $st === 'played') ? 'bi-check2-all' : (in_array($st, array('delivered', 'accepted', 'sent', 'queued'), true) ? 'bi-check2-all' : 'bi-check2');
                      if ($st === 'read') {
                          $tick_icon = 'bi-check2-all';
                          $tick_class = 'wa-ticks';
                      } elseif (in_array($st, array('delivered', 'accepted', 'sent'), true)) {
                          $tick_icon = 'bi-check2-all';
                          $tick_class = 'wa-ticks pending';
                      } else {
                          $tick_icon = 'bi-check2';
                          $tick_class = 'wa-ticks pending';
                      }
                    ?>
                    <i class="bi <?php echo $tick_icon; ?> <?php echo $tick_class; ?>" title="<?php echo esc_view($m->status); ?>"></i>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <form class="wa-composer" method="post" action="<?php echo site_url('whatsapp/reply'); ?>" id="waReplyForm" data-window-open="<?php echo !empty($window['open']) ? '1' : '0'; ?>">
        <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
        <input type="hidden" name="conversation_id" value="<?php echo (int) $conversation->id; ?>">
        <input type="hidden" name="template_language" id="waComposerLang" value="en_US">
        <select name="template_name" id="waComposerTpl" title="Template (required outside 24h window)">
          <option value="" data-lang="en_US" data-vars="0">Text</option>
          <?php foreach ($templates as $tpl): ?>
            <option value="<?php echo esc_view($tpl['name']); ?>"
                    data-lang="<?php echo esc_view($tpl['language']); ?>"
                    data-vars="<?php echo (int) whatsapp_template_placeholder_count(isset($tpl['body']) ? $tpl['body'] : ''); ?>"
                    data-body="<?php echo esc_view(isset($tpl['body']) ? $tpl['body'] : ''); ?>">
              <?php echo esc_view($tpl['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (empty($templates)): ?>
          <input type="text" name="template_name_custom" id="waComposerTplCustom" class="wa-tpl-custom" placeholder="hello_world" title="Meta template name" autocomplete="off" <?php echo !$config_configured ? 'disabled' : ''; ?>>
        <?php endif; ?>
        <input type="text" name="message" id="waComposerMsg" placeholder="<?php echo !empty($window['open']) ? 'Type a message' : 'Select a template (24h closed)'; ?>" autocomplete="off" <?php echo !$config_configured ? 'disabled' : ''; ?>>
        <button type="submit" class="wa-send" <?php echo !$config_configured ? 'disabled' : ''; ?> title="Send">
          <i class="bi bi-send-fill"></i>
        </button>
        <div id="waComposerVars" class="wa-tpl-vars d-none"></div>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="modal fade" id="waNewChat" tabindex="-1" aria-labelledby="waNewChatLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="waNewChatLabel">New chat</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <?php echo form_open('whatsapp/start'); ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="9198xxxxxxxx" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Template</label>
            <input type="hidden" name="template_language" id="waNewTplLang" value="en_US">
            <select name="template_name" id="waNewTplSelect" class="form-select">
              <option value="" data-lang="en_US" data-vars="0">Text (24h window only)</option>
              <?php foreach ($templates as $tpl): ?>
                <option value="<?php echo esc_view($tpl['name']); ?>"
                        data-lang="<?php echo esc_view($tpl['language']); ?>"
                        data-vars="<?php echo (int) whatsapp_template_placeholder_count(isset($tpl['body']) ? $tpl['body'] : ''); ?>">
                  <?php echo esc_view($tpl['name']); ?> (<?php echo esc_view($tpl['language']); ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Or type Meta template name</label>
            <input type="text" name="template_name_custom" class="form-control" placeholder="hello_world">
            <div class="form-text">Required for first contact if the list above is empty. Name must match WhatsApp Manager.</div>
          </div>
          <div class="mb-3 d-none" id="waNewTplVarsWrap">
            <label class="form-label">Template variables</label>
            <div id="waNewTplVars"></div>
          </div>
          </div>
          <div class="mb-0">
            <label class="form-label">Message</label>
            <input type="text" name="message" class="form-control" placeholder="Optional if using a template">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success" <?php echo !$config_configured ? 'disabled' : ''; ?>>Send</button>
        </div>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>

<div class="modal fade" id="waToolsModal" tabindex="-1" aria-labelledby="waToolsLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="waToolsLabel">Staff tools</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
    <div id="waToolsAlert" class="wa-tools-alert mb-2" role="status" aria-live="polite"></div>
    <p class="small text-muted mb-3">Webhook: <code><?php echo esc_view($webhook_url); ?></code></p>
    <div class="alert alert-warning small py-2">
      Task / report alerts use free text. They only deliver if the employee messaged this number within the last 24 hours. Outside that window, send an approved utility template from Templates instead.
    </div>
    <h6>Send to employee</h6>
    <form id="whatsapp-form" class="mb-4">
      <div class="mb-2">
        <select name="employee_id" id="employee-select" class="form-select form-select-sm" required>
          <option value="">Select employee</option>
          <?php foreach ($employees as $emp): ?>
            <option value="<?php echo $emp->id; ?>">
              <?php echo esc_view(trim((isset($emp->first_name) ? $emp->first_name : '') . ' ' . (isset($emp->last_name) ? $emp->last_name : ''))); ?>
              — <?php echo esc_view($emp->phone); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-2">
        <select name="template_name" id="staff-tpl-select" class="form-select form-select-sm">
          <option value="">Text (24h window)</option>
          <?php foreach ($templates as $tpl): ?>
            <option value="<?php echo esc_view($tpl['name']); ?>" data-lang="<?php echo esc_view($tpl['language']); ?>"><?php echo esc_view($tpl['name']); ?> (<?php echo esc_view($tpl['language']); ?>)</option>
          <?php endforeach; ?>
        </select>
        <input type="hidden" id="staff-tpl-lang" value="en_US">
      </div>
      <div class="mb-2">
        <input type="text" id="staff-tpl-custom" class="form-control form-control-sm" placeholder="Or type template name, e.g. hello_world">
      </div>
      <div class="mb-2">
        <textarea name="message" id="message-input" class="form-control form-control-sm" rows="3" placeholder="Message (optional if using a template)"></textarea>
      </div>
      <button type="submit" class="btn btn-success btn-sm" id="send-whatsapp-btn" <?php echo !$config_configured ? 'disabled' : ''; ?>>Send</button>
    </form>
    <h6>Task notification</h6>
    <form id="task-whatsapp-form" class="mb-4">
      <input type="number" name="task_id" class="form-control form-control-sm mb-2" placeholder="Task ID" required>
      <select name="employee_id" class="form-select form-select-sm mb-2">
        <option value="">Assigned employee</option>
        <?php foreach ($employees as $emp): ?>
          <option value="<?php echo $emp->id; ?>">
            <?php echo esc_view(trim((isset($emp->first_name) ? $emp->first_name : '') . ' ' . (isset($emp->last_name) ? $emp->last_name : ''))); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-outline-primary btn-sm" id="send-task-btn" <?php echo !$config_configured ? 'disabled' : ''; ?>>Send task</button>
    </form>
    <h6>Send report</h6>
    <form id="report-whatsapp-form">
      <select name="employee_id" class="form-select form-select-sm mb-2" required>
        <option value="">Employee</option>
        <?php foreach ($employees as $emp): ?>
          <option value="<?php echo $emp->id; ?>">
            <?php echo esc_view(trim((isset($emp->first_name) ? $emp->first_name : '') . ' ' . (isset($emp->last_name) ? $emp->last_name : ''))); ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="report_type" class="form-select form-select-sm mb-2" required>
        <option value="attendance">Attendance</option>
        <option value="tasks">Tasks</option>
      </select>
      <select name="period" class="form-select form-select-sm mb-2" required>
        <option value="week">This week</option>
        <option value="month">This month</option>
      </select>
      <button type="submit" class="btn btn-outline-info btn-sm" id="send-report-btn" <?php echo !$config_configured ? 'disabled' : ''; ?>>Send report</button>
    </form>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  function waShowAlert(type, message, targetId) {
    var text = (message || '').toString();
    if (!text) { return; }
    var box = targetId ? document.getElementById(targetId) : document.getElementById('waToastStack');
    if (!box) {
      window.alert(text);
      return;
    }
    var cls = 'alert-danger';
    var icon = 'bi-exclamation-triangle';
    if (type === 'success') {
      cls = 'alert-success';
      icon = 'bi-check-circle';
    } else if (type === 'warning') {
      cls = 'alert-warning';
      icon = 'bi-exclamation-circle';
    } else if (type === 'info') {
      cls = 'alert-info';
      icon = 'bi-info-circle';
    }
    var el = document.createElement('div');
    el.className = 'alert ' + cls + ' alert-dismissible fade show';
    el.setAttribute('role', 'alert');
    el.innerHTML = '<i class="bi ' + icon + ' me-1"></i><span></span>'
      + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    el.querySelector('span').textContent = text;
    if (targetId) {
      box.innerHTML = '';
    }
    box.appendChild(el);
    if (!targetId) {
      setTimeout(function() {
        if (el.parentNode && window.bootstrap && bootstrap.Alert) {
          try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch (err) {}
        }
      }, 8000);
    }
  }
  window.waShowAlert = waShowAlert;

  var thread = document.getElementById('waThreadMsgs');
  if (thread) { thread.scrollTop = thread.scrollHeight; }
  var search = document.getElementById('waChatSearch');
  if (search) {
    search.addEventListener('input', function() {
      var q = (search.value || '').toLowerCase().trim();
      document.querySelectorAll('#waConvList .wa-conv-item').forEach(function(el) {
        var hay = el.getAttribute('data-search') || '';
        el.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
      });
    });
  }
  document.querySelectorAll('.wa-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
      document.querySelectorAll('.wa-tab').forEach(function(t) { t.classList.remove('active'); });
      tab.classList.add('active');
      var which = tab.getAttribute('data-wa-tab');
      var chats = document.getElementById('waConvList');
      var tpls = document.getElementById('waTplList');
      var searchWrap = document.getElementById('waSearchWrap');
      if (which === 'templates') {
        if (chats) chats.classList.add('d-none');
        if (tpls) tpls.classList.remove('d-none');
        if (searchWrap) searchWrap.classList.add('d-none');
      } else {
        if (chats) chats.classList.remove('d-none');
        if (tpls) tpls.classList.add('d-none');
        if (searchWrap) searchWrap.classList.remove('d-none');
      }
    });
  });
  function waFillTplVars(container, count) {
    if (!container) { return; }
    container.innerHTML = '';
    count = parseInt(count, 10) || 0;
    if (count < 1) {
      container.classList.add('d-none');
      return;
    }
    container.classList.remove('d-none');
    for (var i = 1; i <= count; i++) {
      var input = document.createElement('input');
      input.type = 'text';
      input.name = 'template_var[]';
      input.className = 'form-control form-control-sm mb-1';
      input.placeholder = '{{' + i + '}}';
      input.setAttribute('aria-label', 'Template variable ' + i);
      input.required = true;
      container.appendChild(input);
    }
  }
  document.querySelectorAll('.wa-tpl-item').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var sel = document.getElementById('waNewTplSelect');
      var lang = document.getElementById('waNewTplLang');
      if (sel) { sel.value = btn.getAttribute('data-tpl') || ''; }
      if (lang) { lang.value = btn.getAttribute('data-lang') || 'en_US'; }
      var wrap = document.getElementById('waNewTplVarsWrap');
      var varsBox = document.getElementById('waNewTplVars');
      waFillTplVars(varsBox, btn.getAttribute('data-vars') || 0);
      if (wrap) {
        if ((parseInt(btn.getAttribute('data-vars'), 10) || 0) > 0) { wrap.classList.remove('d-none'); }
        else { wrap.classList.add('d-none'); }
      }
      var modalEl = document.getElementById('waNewChat');
      if (modalEl && window.bootstrap) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      }
    });
  });
  var composerTpl = document.getElementById('waComposerTpl');
  if (composerTpl) {
    composerTpl.addEventListener('change', function() {
      var opt = composerTpl.options[composerTpl.selectedIndex];
      var lang = document.getElementById('waComposerLang');
      if (lang) { lang.value = (opt && opt.getAttribute('data-lang')) || 'en_US'; }
      waFillTplVars(document.getElementById('waComposerVars'), opt ? opt.getAttribute('data-vars') : 0);
    });
  }
  var newTpl = document.getElementById('waNewTplSelect');
  if (newTpl) {
    newTpl.addEventListener('change', function() {
      var opt = newTpl.options[newTpl.selectedIndex];
      var lang = document.getElementById('waNewTplLang');
      if (lang) { lang.value = (opt && opt.getAttribute('data-lang')) || 'en_US'; }
      var wrap = document.getElementById('waNewTplVarsWrap');
      var varsBox = document.getElementById('waNewTplVars');
      var n = opt ? (opt.getAttribute('data-vars') || 0) : 0;
      waFillTplVars(varsBox, n);
      if (wrap) {
        if ((parseInt(n, 10) || 0) > 0) { wrap.classList.remove('d-none'); }
        else { wrap.classList.add('d-none'); }
      }
    });
  }
  var staffTpl = document.getElementById('staff-tpl-select');
  if (staffTpl) {
    staffTpl.addEventListener('change', function() {
      var opt = staffTpl.options[staffTpl.selectedIndex];
      var lang = document.getElementById('staff-tpl-lang');
      if (lang) { lang.value = (opt && opt.getAttribute('data-lang')) || 'en_US'; }
    });
  }

  var replyForm = document.getElementById('waReplyForm');
  if (replyForm) {
    replyForm.addEventListener('submit', function(e) {
      var open = replyForm.getAttribute('data-window-open') === '1';
      var tpl = document.getElementById('waComposerTpl');
      var custom = document.getElementById('waComposerTplCustom');
      var msg = document.getElementById('waComposerMsg');
      var tplName = (tpl && tpl.value) ? tpl.value : '';
      if (!tplName && custom) { tplName = (custom.value || '').trim(); }
      var body = msg ? (msg.value || '').trim() : '';
      if (!tplName && !body) {
        e.preventDefault();
        waShowAlert('warning', 'Enter a message or choose a template.');
        return false;
      }
      if (!open && !tplName) {
        e.preventDefault();
        waShowAlert('warning', 'Outside the 24-hour window. Select an approved template (or type the Meta template name).');
        return false;
      }
      var vars = replyForm.querySelectorAll('#waComposerVars input[name="template_var[]"]');
      for (var i = 0; i < vars.length; i++) {
        if (!(vars[i].value || '').trim()) {
          e.preventDefault();
          waShowAlert('warning', 'Fill all template variables ({{' + (i + 1) + '}}).');
          vars[i].focus();
          return false;
        }
      }
      return true;
    });
  }

  var newChatForm = document.querySelector('#waNewChat form');
  if (newChatForm) {
    newChatForm.addEventListener('submit', function(e) {
      var phone = newChatForm.querySelector('input[name="phone"]');
      var tpl = document.getElementById('waNewTplSelect');
      var custom = newChatForm.querySelector('input[name="template_name_custom"]');
      var msg = newChatForm.querySelector('input[name="message"]');
      var phoneVal = phone ? (phone.value || '').trim() : '';
      var tplName = (tpl && tpl.value) ? tpl.value : '';
      if (!tplName && custom) { tplName = (custom.value || '').trim(); }
      var body = msg ? (msg.value || '').trim() : '';
      if (!phoneVal) {
        e.preventDefault();
        waShowAlert('warning', 'Enter a valid phone number (with country code).');
        return false;
      }
      if (!tplName && body) {
        e.preventDefault();
        waShowAlert('warning', 'First contact needs a template. Select or type a Meta template name (e.g. hello_world).');
        return false;
      }
      if (!tplName && !body) {
        e.preventDefault();
        waShowAlert('warning', 'Choose a template or enter a template name for the first message.');
        return false;
      }
      var vars = newChatForm.querySelectorAll('#waNewTplVars input[name="template_var[]"]');
      for (var i = 0; i < vars.length; i++) {
        if (!(vars[i].value || '').trim()) {
          e.preventDefault();
          waShowAlert('warning', 'Fill all template variables ({{' + (i + 1) + '}}).');
          vars[i].focus();
          return false;
        }
      }
      return true;
    });
  }
})();
if (typeof jQuery !== 'undefined') {
  $(document).ready(function() {
    function toolsAlert(type, message) {
      if (window.waShowAlert) {
        window.waShowAlert(type, message, 'waToolsAlert');
      } else {
        window.alert(message);
      }
    }
    <?php if ($config_configured): ?>
    $('#send-whatsapp-btn, #send-task-btn, #send-report-btn').prop('disabled', false);
    <?php endif; ?>
    $('#whatsapp-form').on('submit', function(e) {
      e.preventDefault();
      var formData = {
        employee_id: $('#employee-select').val(),
        message: $('#message-input').val(),
        template_name: $('#staff-tpl-select').val() || $('#staff-tpl-custom').val() || '',
        template_language: $('#staff-tpl-lang').val() || 'en_US'
      };
      if (!formData.employee_id || (!formData.message && !formData.template_name)) {
        toolsAlert('warning', 'Select an employee and enter a message or template.');
        return;
      }
      var $btn = $('#send-whatsapp-btn');
      var originalText = $btn.html();
      $btn.prop('disabled', true).html('Sending…');
      $.ajax({
        url: '<?php echo site_url('whatsapp/send'); ?>', method: 'POST', data: formData, dataType: 'json',
        success: function(response) {
          toolsAlert(response.success ? 'success' : 'danger', response.message || (response.success ? 'Sent.' : 'Send failed.'));
          if (response.success) { $('#whatsapp-form')[0].reset(); }
        },
        error: function(xhr) {
          var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Send failed. Check connection and try again.';
          toolsAlert('danger', msg);
        },
        complete: function() { $btn.prop('disabled', false).html(originalText); }
      });
    });
    $('#task-whatsapp-form').on('submit', function(e) {
      e.preventDefault();
      var formData = { task_id: $('input[name="task_id"]', this).val(), employee_id: $('select[name="employee_id"]', this).val() || '' };
      if (!formData.task_id) {
        toolsAlert('warning', 'Enter a task ID.');
        return;
      }
      var $btn = $('#send-task-btn');
      var originalText = $btn.html();
      $btn.prop('disabled', true).html('Sending…');
      $.ajax({
        url: '<?php echo site_url('whatsapp/send-task'); ?>', method: 'POST', data: formData, dataType: 'json',
        success: function(response) {
          toolsAlert(response.success ? 'success' : 'danger', response.message || (response.success ? 'Sent.' : 'Send failed.'));
          if (response.success) { $('#task-whatsapp-form')[0].reset(); }
        },
        error: function(xhr) {
          var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Task notification failed.';
          toolsAlert('danger', msg);
        },
        complete: function() { $btn.prop('disabled', false).html(originalText); }
      });
    });
    $('#report-whatsapp-form').on('submit', function(e) {
      e.preventDefault();
      var formData = {
        employee_id: $('select[name="employee_id"]', this).val(),
        report_type: $('select[name="report_type"]', this).val(),
        period: $('select[name="period"]', this).val()
      };
      if (!formData.employee_id) {
        toolsAlert('warning', 'Select an employee.');
        return;
      }
      var $btn = $('#send-report-btn');
      var originalText = $btn.html();
      $btn.prop('disabled', true).html('Sending…');
      $.ajax({
        url: '<?php echo site_url('whatsapp/send-report'); ?>', method: 'POST', data: formData, dataType: 'json',
        success: function(response) {
          toolsAlert(response.success ? 'success' : 'danger', response.message || (response.success ? 'Sent.' : 'Send failed.'));
          if (response.success) { $('#report-whatsapp-form')[0].reset(); }
        },
        error: function(xhr) {
          var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Report send failed.';
          toolsAlert('danger', msg);
        },
        complete: function() { $btn.prop('disabled', false).html(originalText); }
      });
    });
  });
}
</script>

<?php $this->load->view('partials/footer'); ?>
