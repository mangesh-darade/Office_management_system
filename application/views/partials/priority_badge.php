<?php
$priority_colors = [
    'low' => 'bg-info',
    'medium' => 'bg-warning', 
    'high' => 'bg-danger',
    'critical' => 'bg-dark'
];
$color_class = isset($priority_colors[$priority]) ? $priority_colors[$priority] : 'bg-secondary';
?>
<span class="badge <?php echo $color_class; ?>"><?php echo ucfirst($priority); ?></span>
