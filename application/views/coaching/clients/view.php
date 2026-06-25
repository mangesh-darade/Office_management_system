<?php $this->load->view('partials/header', ['title' => $row->full_name]); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<h1 class="h4 mb-3"><?php echo esc_view($row->full_name); ?> <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('coaching-clients/edit/'.$row->id); ?>">Edit</a></h1>
<p class="text-muted"><?php echo esc_view($row->email); ?> · <?php echo esc_view($row->phone); ?>
<?php if (!empty($row->crm_client_id) && (has_module_access('clients') || (int) $this->session->userdata('role_id') === 1)): ?>
 · CRM: <a href="<?php echo site_url('clients/view/' . (int) $row->crm_client_id); ?>"><?php
    $crm_label = trim((string) $row->crm_company_name);
    if ($crm_label === '' && !empty($row->crm_client_code)) {
        $crm_label = $row->crm_client_code;
    }
    if ($crm_label === '') {
        $crm_label = 'Client #' . (int) $row->crm_client_id;
    }
    echo esc_view($crm_label);
?></a>
<?php elseif (!empty($row->crm_client_id)): ?>
 · CRM linked (#<?php echo (int) $row->crm_client_id; ?>)
<?php endif; ?>
</p>
<div class="row g-3">
<div class="col-lg-4"><div class="card shadow-soft"><div class="card-header">Sessions</div><ul class="list-group list-group-flush"><?php foreach ($sessions as $s): ?><li class="list-group-item small"><?php echo date('d M H:i', strtotime($s->scheduled_at)); ?> — <?php echo esc_view($s->title); ?></li><?php endforeach; if (empty($sessions)): ?><li class="list-group-item text-muted">None</li><?php endif; ?></ul></div></div>
<div class="col-lg-4"><div class="card shadow-soft"><div class="card-header">Goals</div><ul class="list-group list-group-flush"><?php foreach ($goals as $g): ?><li class="list-group-item small"><?php echo esc_view($g->title); ?> (<?php echo (int)$g->progress_pct; ?>%)</li><?php endforeach; ?></ul></div></div>
<div class="col-lg-4"><div class="card shadow-soft"><div class="card-header">Invoices</div><ul class="list-group list-group-flush"><?php foreach ($invoices as $i): ?><li class="list-group-item small"><a href="<?php echo site_url('coaching-billing/invoice/'.$i->id); ?>"><?php echo esc_view($i->invoice_no); ?></a> — <?php echo esc_view($i->status); ?></li><?php endforeach; ?></ul></div></div>
</div>
<?php $this->load->view('partials/footer'); ?>
