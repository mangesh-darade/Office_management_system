<?php
  $active_tab = isset($active_tab) ? (string) $active_tab : 'details';
  $history_count = isset($history_count) ? (int) $history_count : 0;
?>
<ul class="nav nav-tabs nav-tabs-sm oms-detail-tabs mb-2" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link<?php echo $active_tab === 'details' ? ' active' : ''; ?>" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="<?php echo $active_tab === 'details' ? 'true' : 'false'; ?>">Details</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link<?php echo $active_tab === 'history' ? ' active' : ''; ?>" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab" aria-controls="history" aria-selected="<?php echo $active_tab === 'history' ? 'true' : 'false'; ?>"><i class="bi bi-clock-history me-1"></i>History <span class="badge rounded-pill bg-light text-dark border ms-1"><?php echo $history_count; ?></span></button>
  </li>
</ul>
