<?php $this->load->view('partials/header', ['title' => 'Helpdesk']); ?>
<div class="container-fluid py-3">
<?php
$can_add = function_exists('has_module_access') && (has_module_access('helpdesk') || has_module_access('helpdesk_manage'));
$actions = $can_add ? '<a class="btn btn-primary btn-sm" href="'.site_url('helpdesk/create').'"><i class="bi bi-plus-lg me-1"></i>New Ticket</a>' : '';
$this->load->view('partials/oms_page_head', ['title' => 'Helpdesk', 'icon' => 'bi-life-preserver', 'subtitle' => 'Internal support tickets', 'actions_html' => $actions]);
?>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>#</th><th>Subject</th><th>Priority</th><th>Status</th><th>Requester</th><th></th></tr></thead><tbody>
<?php if (empty($rows)): ?><tr><td colspan="6" class="text-muted text-center">No tickets.</td></tr><?php else: foreach ($rows as $r): ?>
<tr><td><?php echo esc_view($r->ticket_number); ?></td><td><?php echo esc_view($r->subject); ?></td><td><?php echo esc_view($r->priority); ?></td><td><?php echo esc_view($r->status); ?></td><td><?php echo esc_view($r->requester_name); ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('helpdesk/edit/'.$r->id); ?>">Open</a></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
