<?php
$status_colors = [
    'received' => 'bg-secondary',
    'under_review' => 'bg-info',
    'approved' => 'bg-primary',
    'in_progress' => 'bg-warning',
    'completed' => 'bg-success',
    'on_hold' => 'bg-dark',
    'rejected' => 'bg-danger',
    'cancelled' => 'bg-secondary'
];
$color_class = isset($status_colors[$status]) ? $status_colors[$status] : 'bg-secondary';
?>
<span class="badge <?php echo $color_class; ?>"><?php echo ucwords(str_replace('_', ' ', $status)); ?></span>
