<?php $this->load->view('partials/header', ['title' => 'Events']); ?>
<div class="container-fluid py-3">
<?php
$can_event_add = function_exists('has_module_access') && (has_module_access('events_add') || has_module_access('events'));
$this->load->view('partials/oms_page_head', ['title' => 'Company Events', 'icon' => 'bi-calendar-event', 'subtitle' => 'Workshops, town halls, and team events', 'actions_html' => $can_event_add ? '<a class="btn btn-primary btn-sm" href="'.site_url('events/create').'"><i class="bi bi-plus-lg me-1"></i>New Event</a>' : '']);
?>
<div class="card shadow-soft"><div class="list-group list-group-flush">
<?php if (empty($rows)): ?><div class="list-group-item text-muted">No events scheduled.</div><?php else: foreach ($rows as $r): ?>
<div class="list-group-item"><div class="fw-semibold"><?php echo esc_view($r->title); ?></div><div class="small text-muted"><?php echo esc_view($r->start_at); ?> <?php echo $r->location?'· '.esc_view($r->location):''; ?></div></div>
<?php endforeach; endif; ?>
</div></div></div>
<?php $this->load->view('partials/footer'); ?>
