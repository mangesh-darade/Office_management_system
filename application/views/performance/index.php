<?php $this->load->view('partials/header', ['title' => 'Performance Management']); ?>
<div class="container-fluid py-4">
<div class="oms-page-head d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
    <div>
        <h1 class="h4 mb-1 fw-bold"><i class="bi bi-award text-primary me-2"></i>Employee Appraisals</h1>
        <p class="text-muted small mb-0">Manage performance reviews and KPI scores</p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-sm-0 flex-wrap">
      <?php if(function_exists('has_module_access') && (has_module_access('performance_self_assess') || has_module_access('performance'))): ?>
      <a href="<?php echo site_url('performance/self-assess'); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-person-check me-1"></i>Self-Assess
      </a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && (has_module_access('performance_export') || has_module_access('performance') || is_admin_group())): ?>
      <a href="<?php echo site_url('performance/export'); ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-download me-1"></i>Export
      </a>
      <?php endif; ?>
      <?php if(function_exists('has_module_access') && (has_module_access('performance_create') || has_module_access('performance'))): ?>
      <a href="<?php echo site_url('performance/create'); ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Appraisal</a>
      <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee</th>
                    <th>Manager</th>
                    <th>Period</th>
                    <th>Rating</th>
                    <th>KPI Score</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appraisals)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <i class="bi bi-clipboard-x text-muted" style="font-size:2.5rem;"></i>
                        <p class="text-muted mt-2 mb-0">No appraisals found. Create the first one to get started.</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach($appraisals as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p->first_name . ' ' . $p->last_name); ?></td>
                    <td><?php echo htmlspecialchars($p->manager_name); ?></td>
                    <td><?php echo htmlspecialchars($p->period); ?></td>
                    <td><?php echo (int)$p->rating; ?>/5</td>
                    <td><?php echo htmlspecialchars($p->kpi_score); ?></td>
                    <td><span class="badge bg-<?php echo ($p->status==='approved'?'success':($p->status==='submitted'?'info':'secondary')); ?>"><?php echo htmlspecialchars(ucfirst($p->status), ENT_QUOTES, 'UTF-8'); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($p->created_at)); ?></td>
                    <td>
                        <?php if(function_exists('has_module_access') && (has_module_access('performance_view') || has_module_access('performance'))): ?>
                        <a href="<?php echo site_url('performance/view/'.$p->id); ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                        <?php endif; ?>
                        <?php if(function_exists('has_module_access') && (has_module_access('performance_edit') || has_module_access('performance'))): ?>
                        <a href="<?php echo site_url('performance/edit/'.$p->id); ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if(function_exists('has_module_access') && (has_module_access('performance_delete') || has_module_access('performance'))): ?>
                        <form method="post" action="<?php echo site_url('performance/delete/'.$p->id); ?>" class="d-inline" onsubmit="return confirm('Delete this appraisal?');">
                            <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
