<?php
// $title (string), optional $chips = [['text'=>'Real Estate CRM','icon'=>'bi bi-bookmark'], ...], optional $action (['label'=>'','href'=>'','class'=>''])
$title = isset($title) ? $title : '';
$chips = isset($chips) && is_array($chips) ? $chips : [];
$action = isset($action) && is_array($action) ? $action : null;
?>
<div class="page-header d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-2">
    <?php foreach ($chips as $c): ?>
      <span class="chip">
        <?php if (!empty($c['icon'])): ?><i class="<?php echo htmlspecialchars($c['icon']); ?>"></i><?php endif; ?>
        <?php echo htmlspecialchars(isset($c['text']) ? $c['text'] : ''); ?>
      </span>
    <?php endforeach; ?>
    <h1 class="h5 mb-0 title"><?php echo htmlspecialchars($title); ?></h1>
  </div>
  <?php if ($action): ?>
    <?php 
      $actionClass = trim((string)(isset($action['class']) ? $action['class'] : 'btn-primary'));
    ?>
    <a href="<?php echo htmlspecialchars(isset($action['href']) ? $action['href'] : '#'); ?>" class="btn btn-sm <?php echo htmlspecialchars($actionClass); ?>">
      <?php echo htmlspecialchars(isset($action['label']) ? $action['label'] : ''); ?>
    </a>
  <?php endif; ?>
</div>
