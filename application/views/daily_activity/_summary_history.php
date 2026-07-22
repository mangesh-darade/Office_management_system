<?php
  $history = isset($history) && is_array($history) ? $history : array();
  if (empty($history)):
?>
  <div class="da-summary-history-empty">No comments / history today</div>
<?php else: ?>
  <ul class="da-summary-history">
    <?php foreach ($history as $entry): ?>
      <?php
        $kind = isset($entry['kind']) ? (string) $entry['kind'] : 'history';
        $time = isset($entry['time']) ? (string) $entry['time'] : '';
        $user = isset($entry['user']) ? (string) $entry['user'] : '';
        $text = isset($entry['text']) ? (string) $entry['text'] : '';
        $badge = ($kind === 'comment') ? 'Comment' : 'History';
        $kind_class = ($kind === 'comment') ? 'comment' : 'history';
      ?>
      <li class="da-summary-history-item da-summary-history-item--<?php echo esc_view($kind_class); ?>">
        <span class="da-summary-history-meta">
          <span class="da-summary-history-badge"><?php echo esc_view($badge); ?></span>
          <?php if ($time !== ''): ?>
            <span class="da-summary-history-time"><?php echo esc_view($time); ?></span>
          <?php endif; ?>
          <?php if ($user !== ''): ?>
            <span class="da-summary-history-user"><?php echo esc_view($user); ?></span>
          <?php endif; ?>
        </span>
        <div class="da-summary-history-text"><?php echo esc_view($text); ?></div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
