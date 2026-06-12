<?php $this->load->view('partials/header', array('title' => 'My Works', 'extra_css' => array('assets/css/my-works.css'))); ?>

<?php
  $view_mode = 'overview';
  $uid = (int) $this->session->userdata('user_id');
  $feed = isset($feed) ? $feed : array();
  $overview_items = isset($overview_items) ? $overview_items : array();
  $first_id = 0;
  if (!empty($rows)) {
    $first_id = (int) $rows[0]->id;
  }
  $exportQs = array();
  foreach ($filters as $k => $v) {
    if ($k === 'current_user_id') {
      continue;
    }
    if ($v !== '' && $v !== 0 && $v !== '0') {
      $exportQs[$k] = $v;
    }
  }
  $exportUrl = site_url('my-works/export') . (empty($exportQs) ? '' : '?' . http_build_query($exportQs));
  $statusLabels = my_works_status_labels();
  $statusColors = my_works_status_colors();
  $previewAtts = array();
  if (!empty($rows) && isset($attachments_map)) {
    foreach ($rows as $pr) {
      $pid = (int) $pr->id;
      if (!empty($attachments_map[$pid])) {
        foreach ($attachments_map[$pid] as $att) {
          $previewAtts[] = $att;
          if (count($previewAtts) >= 4) {
            break 2;
          }
        }
      }
    }
  }
?>

<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success alert-dismissible fade show py-2 mx-3 mt-2 mb-0" role="alert">
    <?php echo htmlspecialchars($this->session->flashdata('success')); ?>
    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show py-2 mx-3 mt-2 mb-0" role="alert">
    <?php echo htmlspecialchars($this->session->flashdata('error')); ?>
    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="mw-mockup-canvas">

  <div class="mw-mockup-topbar">
    <div class="mw-mockup-topbar-title"><i class="bi bi-clipboard2-check me-2"></i>My Works</div>
    <div class="mw-mockup-topbar-views">
      <span class="mw-mockup-view-pill active">Overview</span>
      <a class="mw-mockup-view-pill" href="<?php echo site_url('my-works?view=list'); ?>">List</a>
      <a class="mw-mockup-view-pill" href="<?php echo site_url('my-works?view=board'); ?>">Board</a>
      <a class="mw-mockup-view-pill" href="<?php echo site_url('my-works?view=matrix'); ?>">Matrix</a>
      <?php if (!empty($can_add)): ?>
        <a class="btn btn-sm btn-primary ms-2" href="<?php echo site_url('my-works/create'); ?>"><i class="bi bi-plus-lg me-1"></i>New</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="mw-mockup-main">

    <!-- LEFT: Chat / Activity (mockup: Sateri Second Brain) -->
    <section class="mw-mockup-panel mw-mockup-chat">
      <header class="mw-mockup-panel-header">
        <h2 class="mw-mockup-panel-title">My Works Hub</h2>
        <a class="mw-mockup-icon-btn" href="<?php echo site_url('my-works?view=list'); ?>" title="List &amp; filters"><i class="bi bi-gear"></i></a>
      </header>
      <div class="mw-mockup-chat-body" id="mwOverviewFeed">
        <?php if (empty($feed)): ?>
          <div class="mw-mockup-chat-row mw-mockup-chat-ai">
            <div class="mw-mockup-bubble mw-mockup-bubble-ai">
              Welcome to My Works. Your activity and comments will appear here as chat messages.
            </div>
          </div>
          <div class="mw-mockup-chat-row mw-mockup-chat-user">
            <div class="mw-mockup-bubble mw-mockup-bubble-user">
              Create your first work item to get started.
            </div>
          </div>
        <?php else: ?>
          <?php foreach ($feed as $item): ?>
            <?php
              $isUser = ((int) $item['user_id'] === $uid);
              $rowClass = $isUser ? 'mw-mockup-chat-user' : 'mw-mockup-chat-ai';
              $bubbleClass = $isUser ? 'mw-mockup-bubble-user' : 'mw-mockup-bubble-ai';
            ?>
            <div class="mw-mockup-chat-row <?php echo $rowClass; ?>">
              <div class="mw-mockup-bubble <?php echo $bubbleClass; ?>">
                <?php echo htmlspecialchars((string) $item['text'], ENT_QUOTES, 'UTF-8'); ?>
                <a href="#" class="mw-mockup-chat-link" data-work-id="<?php echo (int) $item['work_id']; ?>">
                  <?php echo htmlspecialchars((string) $item['work_title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <footer class="mw-mockup-chat-footer">
        <div class="mw-mockup-chat-input-wrap">
          <input type="text" id="mwMockupChatInput" class="mw-mockup-chat-input" placeholder="Ask a question…" readonly>
          <button type="button" class="mw-mockup-chat-send" id="mwMockupChatSend" title="Focus search"><i class="bi bi-arrow-up-circle-fill"></i></button>
        </div>
        <?php if (!empty($can_add)): ?>
          <a class="mw-mockup-upload-btn" href="<?php echo site_url('my-works/quick-add'); ?>?redirect=my-works%3Fview%3Doverview">
            <i class="bi bi-plus-circle me-1"></i>Add Work Item
          </a>
        <?php endif; ?>
      </footer>
    </section>

    <!-- CENTER: Document Search -->
    <section class="mw-mockup-panel mw-mockup-search">
      <header class="mw-mockup-panel-header">
        <h2 class="mw-mockup-panel-title">Work Search</h2>
      </header>
      <div class="mw-mockup-search-input-wrap">
        <form method="get" action="<?php echo site_url('my-works'); ?>" id="mwMockupSearchForm">
          <input type="hidden" name="view" value="overview">
          <?php foreach ($filters as $fk => $fv): ?>
            <?php if ($fk === 'current_user_id' || $fk === 'q') { continue; } ?>
            <?php if ($fv !== '' && $fv !== 0 && $fv !== '0'): ?>
              <input type="hidden" name="<?php echo htmlspecialchars($fk); ?>" value="<?php echo htmlspecialchars((string) $fv); ?>">
            <?php endif; ?>
          <?php endforeach; ?>
          <input type="search" name="q" id="mwOverviewSearch" class="mw-mockup-search-input" placeholder="Search work items…" value="<?php echo htmlspecialchars(isset($filters['q']) ? $filters['q'] : ''); ?>">
        </form>
      </div>
      <div class="mw-mockup-search-results" id="mwOverviewResults">
        <?php if (empty($rows)): ?>
          <div class="mw-mockup-empty">No work items found.</div>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php
              $wid = (int) $r->id;
              $priority = my_works_priority_label($r);
              $priorityClass = my_works_priority_class($r);
              $st = isset($r->status) ? (string) $r->status : 'new';
              $stLabel = isset($statusLabels[$st]) ? $statusLabels[$st] : $st;
              $year = '';
              if (!empty($r->due_date)) {
                $year = substr((string) $r->due_date, 0, 4);
              } elseif (!empty($r->created_at)) {
                $year = substr((string) $r->created_at, 0, 4);
              }
              $typeLabel = !empty($r->work_type) ? my_works_type_label($r->work_type) : 'Work';
              $attCount = 0;
              if (isset($attachments_map[$wid])) {
                $attCount = count($attachments_map[$wid]);
              }
              $typeIcon = $attCount > 0 ? 'bi-file-earmark-pdf' : 'bi-clipboard';
            ?>
            <article class="mw-mockup-doc-card<?php echo $wid === $first_id ? ' is-active' : ''; ?>" data-work-id="<?php echo $wid; ?>" tabindex="0" role="button">
              <div class="mw-mockup-doc-top">
                <h3 class="mw-mockup-doc-title"><?php echo htmlspecialchars((string) $r->title, ENT_QUOTES, 'UTF-8'); ?></h3>
                <span class="mw-mockup-quality mw-mockup-quality-<?php echo htmlspecialchars($priorityClass); ?>"><?php echo htmlspecialchars($priority); ?></span>
              </div>
              <div class="mw-mockup-doc-meta">
                <span>Status <?php echo htmlspecialchars($stLabel); ?></span>
                <?php if ($year !== ''): ?><span class="mw-mockup-doc-dot">•</span><span><?php echo htmlspecialchars($year); ?></span><?php endif; ?>
                <span class="mw-mockup-doc-dot">•</span>
                <span class="mw-mockup-doc-type"><i class="bi <?php echo $typeIcon; ?>"></i> <?php echo htmlspecialchars($typeLabel); ?></span>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <footer class="mw-mockup-pagination" id="mwMockupPagination" aria-label="Results pages"></footer>
    </section>

    <!-- RIGHT: Generated Knowledge / Work Summary -->
    <section class="mw-mockup-panel mw-mockup-knowledge">
      <header class="mw-mockup-panel-header">
        <h2 class="mw-mockup-panel-title">Work Summary</h2>
      </header>
      <div class="mw-mockup-knowledge-body" id="mwOverviewDetail">
        <div class="mw-mockup-empty" id="mwOverviewDetailEmpty"<?php echo $first_id > 0 ? ' style="display:none;"' : ''; ?>>
          Select a work item to view its summary here.
        </div>
        <div class="mw-mockup-knowledge-content" id="mwOverviewDetailContent"<?php echo $first_id < 1 ? ' style="display:none;"' : ''; ?>></div>
      </div>
      <footer class="mw-mockup-export-bar" id="mwOverviewExportBar"<?php echo $first_id < 1 ? ' style="display:none;"' : ''; ?>>
        <a href="#" class="mw-mockup-export-btn mw-overview-action-view" title="Full view"><i class="bi bi-eye"></i><span>View</span></a>
        <?php if (!empty($can_edit)): ?>
          <a href="#" class="mw-mockup-export-btn mw-overview-action-edit" title="Edit"><i class="bi bi-pencil-square"></i><span>Edit</span></a>
        <?php endif; ?>
        <?php if (!empty($can_export)): ?>
          <a class="mw-mockup-export-btn" href="<?php echo htmlspecialchars($exportUrl); ?>" title="Export CSV"><i class="bi bi-filetype-csv"></i><span>CSV</span></a>
        <?php endif; ?>
        <a href="#" class="mw-mockup-export-btn mw-overview-action-link" style="display:none;" target="_blank" rel="noopener" title="Open link"><i class="bi bi-link-45deg"></i><span>Link</span></a>
      </footer>
    </section>

  </div>

  <!-- BOTTOM: 5 mini-screen row (mockup thumbnails) -->
  <div class="mw-mockup-thumbs">
    <div class="mw-mockup-thumb">
      <div class="mw-mockup-thumb-label">Activity</div>
      <div class="mw-mockup-thumb-body mw-mockup-thumb-chat">
        <?php $thumbFeed = array_slice($feed, 0, 2); ?>
        <?php if (empty($thumbFeed)): ?>
          <div class="mw-mockup-thumb-bubble sm ai"></div>
          <div class="mw-mockup-thumb-bubble sm user"></div>
        <?php else: ?>
          <?php foreach ($thumbFeed as $ti): ?>
            <div class="mw-mockup-thumb-bubble sm <?php echo ((int) $ti['user_id'] === $uid) ? 'user' : 'ai'; ?>"></div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="mw-mockup-thumb">
      <div class="mw-mockup-thumb-label">Works</div>
      <div class="mw-mockup-thumb-body">
        <?php foreach (array_slice($rows, 0, 3) as $tr): ?>
          <div class="mw-mockup-thumb-line"></div>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?><div class="mw-mockup-thumb-line muted"></div><?php endif; ?>
      </div>
    </div>
    <div class="mw-mockup-thumb">
      <div class="mw-mockup-thumb-label">Files</div>
      <div class="mw-mockup-thumb-body mw-mockup-thumb-files">
        <?php if (empty($previewAtts)): ?>
          <div class="mw-mockup-thumb-file"><i class="bi bi-file-earmark"></i></div>
        <?php else: ?>
          <?php foreach ($previewAtts as $att): ?>
            <?php
              $attLabel = 'File';
              if (!empty($att->original_name)) {
                $attLabel = (string) $att->original_name;
              } elseif (!empty($att->stored_name)) {
                $attLabel = (string) $att->stored_name;
              }
            ?>
            <div class="mw-mockup-thumb-file" title="<?php echo htmlspecialchars($attLabel, ENT_QUOTES, 'UTF-8'); ?>"><i class="bi bi-file-earmark-text"></i></div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="mw-mockup-thumb">
      <div class="mw-mockup-thumb-label">Analytics</div>
      <div class="mw-mockup-thumb-body mw-mockup-thumb-chart">
        <?php
          $chartVals = array(
            (int) $stats['new'],
            (int) $stats['in_progress'],
            (int) $stats['closed'],
            (int) $stats['urgent'],
          );
          $chartMax = max(1, max($chartVals));
        ?>
        <?php foreach ($chartVals as $cv): ?>
          <div class="mw-mockup-thumb-bar" style="height:<?php echo max(8, round(($cv / $chartMax) * 100)); ?>%;"></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="mw-mockup-thumb">
      <div class="mw-mockup-thumb-label">Views</div>
      <div class="mw-mockup-thumb-body mw-mockup-thumb-views">
        <a href="<?php echo site_url('my-works?view=list'); ?>" class="mw-mockup-thumb-pill">List</a>
        <a href="<?php echo site_url('my-works?view=board'); ?>" class="mw-mockup-thumb-pill">Board</a>
        <a href="<?php echo site_url('my-works?view=matrix'); ?>" class="mw-mockup-thumb-pill">Matrix</a>
      </div>
    </div>
  </div>

</div>

<script type="application/json" id="mwOverviewItemsJson"><?php echo json_encode($overview_items, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

<script>
(function () {
  var items = {};
  var raw = document.getElementById('mwOverviewItemsJson');
  if (raw && raw.textContent) {
    try { items = JSON.parse(raw.textContent); } catch (e) { items = {}; }
  }

  var detailEmpty = document.getElementById('mwOverviewDetailEmpty');
  var detailContent = document.getElementById('mwOverviewDetailContent');
  var exportBar = document.getElementById('mwOverviewExportBar');
  var actionView = document.querySelector('.mw-overview-action-view');
  var actionEdit = document.querySelector('.mw-overview-action-edit');
  var actionLink = document.querySelector('.mw-overview-action-link');
  var pageSize = 5;
  var currentPage = 1;

  function esc(s) {
    if (!s) { return ''; }
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function renderDetail(id) {
    var item = items[String(id)] || items[id];
    if (!item) { return; }

    var paragraphs = [];
    paragraphs.push('<p class="mw-mockup-knowledge-lead"><strong>' + esc(item.title) + '</strong></p>');
    paragraphs.push('<p>Status: <strong>' + esc(item.status_label) + '</strong> · Priority: <strong>' + esc(item.priority) + '</strong></p>');
    paragraphs.push('<p>Assigned to <strong>' + esc(item.assignee) + '</strong>, created by <strong>' + esc(item.creator) + '</strong>.</p>');
    if (item.due_date) {
      paragraphs.push('<p>Due date: <strong>' + esc(item.due_date) + '</strong>' + (item.is_overdue ? ' <em class="text-danger">(overdue)</em>' : '') + '.</p>');
    }
    if (item.client || item.project) {
      var ctx = [];
      if (item.client) { ctx.push('Client: ' + esc(item.client)); }
      if (item.project) { ctx.push('Project: ' + esc(item.project)); }
      paragraphs.push('<p>' + ctx.join(' · ') + '</p>');
    }
    if (item.details) {
      paragraphs.push('<p>' + esc(item.details) + '</p>');
    } else {
      paragraphs.push('<p class="text-muted">No additional description provided for this work item.</p>');
    }
    if (item.tags && item.tags.length) {
      paragraphs.push('<p>Tags: ' + esc(item.tags.join(', ')) + '</p>');
    }
    if (item.attachments && item.attachments.length) {
      paragraphs.push('<p><strong>Attachments:</strong> ' + esc(item.attachments.join(', ')) + '</p>');
    }
    paragraphs.push('<p class="mw-mockup-knowledge-foot">Last updated ' + esc(item.updated_label) + '.</p>');

    detailContent.innerHTML = paragraphs.join('');

    if (detailEmpty) { detailEmpty.style.display = 'none'; }
    detailContent.style.display = '';
    if (exportBar) { exportBar.style.display = ''; }
    if (actionView) { actionView.href = item.view_url; }
    if (actionEdit) { actionEdit.href = item.edit_url; }
    if (actionLink) {
      if (item.url) {
        actionLink.href = item.url;
        actionLink.style.display = '';
      } else {
        actionLink.style.display = 'none';
      }
    }
  }

  function getVisibleCards() {
    var all = document.querySelectorAll('.mw-mockup-doc-card');
    var visible = [];
    for (var i = 0; i < all.length; i++) {
      if (all[i].style.display !== 'none') {
        visible.push(all[i]);
      }
    }
    return visible;
  }

  function renderPagination() {
    var pag = document.getElementById('mwMockupPagination');
    if (!pag) { return; }
    var cards = getVisibleCards();
    var totalPages = Math.max(1, Math.ceil(cards.length / pageSize));
    if (currentPage > totalPages) { currentPage = totalPages; }
    var html = '';
    for (var p = 1; p <= totalPages && p <= 7; p++) {
      html += '<button type="button" class="mw-mockup-page-btn' + (p === currentPage ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
    }
    if (totalPages > 7) {
      html += '<span class="mw-mockup-page-dots">…</span>';
      html += '<button type="button" class="mw-mockup-page-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
    }
    pag.innerHTML = html;
    pag.style.display = cards.length > pageSize ? '' : 'none';

    for (var i = 0; i < cards.length; i++) {
      var page = Math.floor(i / pageSize) + 1;
      cards[i].style.display = (page === currentPage) ? '' : 'none';
    }
  }

  function selectCard(card) {
    var cards = document.querySelectorAll('.mw-mockup-doc-card');
    for (var i = 0; i < cards.length; i++) {
      cards[i].classList.remove('is-active');
    }
    card.classList.add('is-active');
    renderDetail(card.getAttribute('data-work-id'));
  }

  var results = document.getElementById('mwOverviewResults');
  if (results) {
    results.addEventListener('click', function (e) {
      var card = e.target.closest('.mw-mockup-doc-card');
      if (card) {
        e.preventDefault();
        selectCard(card);
      }
    });
    results.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        var card = e.target.closest('.mw-mockup-doc-card');
        if (card) {
          e.preventDefault();
          selectCard(card);
        }
      }
    });
  }

  var pag = document.getElementById('mwMockupPagination');
  if (pag) {
    pag.addEventListener('click', function (e) {
      var btn = e.target.closest('.mw-mockup-page-btn');
      if (!btn) { return; }
      currentPage = parseInt(btn.getAttribute('data-page'), 10) || 1;
      renderPagination();
    });
  }

  var feed = document.getElementById('mwOverviewFeed');
  if (feed) {
    feed.addEventListener('click', function (e) {
      var link = e.target.closest('.mw-mockup-chat-link');
      if (!link) { return; }
      e.preventDefault();
      var id = link.getAttribute('data-work-id');
      var card = document.querySelector('.mw-mockup-doc-card[data-work-id="' + id + '"]');
      if (card) {
        var cards = getVisibleCards();
        for (var i = 0; i < cards.length; i++) {
          if (cards[i] === card) {
            currentPage = Math.floor(i / pageSize) + 1;
            break;
          }
        }
        renderPagination();
        selectCard(card);
      } else {
        renderDetail(id);
      }
    });
  }

  var searchInput = document.getElementById('mwOverviewSearch');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      var q = searchInput.value.toLowerCase().trim();
      var cards = document.querySelectorAll('.mw-mockup-doc-card');
      for (var i = 0; i < cards.length; i++) {
        var title = cards[i].querySelector('.mw-mockup-doc-title');
        var text = title ? title.textContent.toLowerCase() : '';
        cards[i].dataset.filtered = (q !== '' && text.indexOf(q) === -1) ? '1' : '0';
        cards[i].style.display = cards[i].dataset.filtered === '1' ? 'none' : '';
      }
      currentPage = 1;
      renderPagination();
    });
  }

  var chatSend = document.getElementById('mwMockupChatSend');
  var chatInput = document.getElementById('mwMockupChatInput');
  if (chatSend && searchInput) {
    chatSend.addEventListener('click', function () {
      searchInput.focus();
      searchInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  }
  if (chatInput && searchInput) {
    chatInput.addEventListener('focus', function () {
      searchInput.focus();
    });
  }

  renderPagination();
  var firstCard = document.querySelector('.mw-mockup-doc-card.is-active');
  if (firstCard) {
    renderDetail(firstCard.getAttribute('data-work-id'));
  }
})();
</script>

<?php $this->load->view('partials/footer'); ?>
