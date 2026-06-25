<?php $this->load->view('partials/header', ['title' => 'Meal History', 'active' => 'meals']); ?>
<div class="container-fluid py-3 oms-fluid-pad">
<?php $this->load->view('partials/oms_page_head', ['title' => 'Meal Order History', 'icon' => 'bi-clock-history', 'subtitle' => 'Audit trail of changes']); ?>
<?php $this->load->view('meals/_nav', ['active_sub' => 'history']); ?>
<form method="get" class="row g-2 mb-3">
<div class="col-auto"><input type="date" name="date" class="form-control form-control-sm" value="<?php echo esc_view($filters['meal_date']); ?>"></div>
<div class="col-auto"><select name="user_id" class="form-select form-select-sm"><option value="">All users</option><?php foreach ($users as $u): ?><option value="<?php echo (int)$u->id; ?>" <?php echo ((int)$filters['user_id'] === (int)$u->id) ? 'selected' : ''; ?>><?php echo esc_view($u->name); ?></option><?php endforeach; ?></select></div>
<div class="col-auto"><button class="btn btn-sm btn-primary">Filter</button></div>
</form>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>When</th><th>Date</th><th>User</th><th>Field</th><th>Old</th><th>New</th><th>By</th></tr></thead><tbody>
<?php if (empty($rows)): ?><tr><td colspan="7" class="text-muted text-center">No history.</td></tr>
<?php else: foreach ($rows as $r): ?>
<tr><td><?php echo esc_view($r->changed_at); ?></td><td><?php echo esc_view($r->meal_date); ?></td><td><?php echo esc_view($r->user_name); ?></td><td><?php echo esc_view(meal_format_log_field($r->field_name)); ?></td><td><?php echo esc_view(meal_format_log_value($r->field_name, $r->old_value)); ?></td><td><?php echo esc_view(meal_format_log_value($r->field_name, $r->new_value)); ?></td><td><?php echo esc_view($r->changed_by_name); ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div></div>
</div>
<?php $this->load->view('partials/footer'); ?>
