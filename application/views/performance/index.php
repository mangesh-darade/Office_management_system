<?php $this->load->view('partials/header', ['title' => 'Performance Management']); ?>
<div class="d-flex justify-content-between mb-3">
    <h3>Employee Appraisals</h3>
    <a href="<?php echo site_url('performance/create'); ?>" class="btn btn-primary">New Appraisal</a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Manager</th>
                    <th>Period</th>
                    <th>Rating</th>
                    <th>KPI Score</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($appraisals as $p): ?>
                <tr>
                    <td><?php echo htmlspecialchars($p->first_name . ' ' . $p->last_name); ?></td>
                    <td><?php echo htmlspecialchars($p->manager_name); ?></td>
                    <td><?php echo htmlspecialchars($p->period); ?></td>
                    <td><?php echo (int)$p->rating; ?>/5</td>
                    <td><?php echo htmlspecialchars($p->kpi_score); ?></td>
                    <td><span class="badge bg-<?php echo ($p->status==='approved'?'success':($p->status==='submitted'?'info':'secondary')); ?>"><?php echo ucfirst($p->status); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($p->created_at)); ?></td>
                    <td>
                        <?php if(function_exists('has_module_access') && (has_module_access('performance_view') || has_module_access('performance'))): ?>
                        <a href="<?php echo site_url('performance/view/'.$p->id); ?>" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                        <?php endif; ?>
                        <?php if(function_exists('has_module_access') && has_module_access('performance_edit')): ?>
                        <a href="<?php echo site_url('performance/edit/'.$p->id); ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if(function_exists('has_module_access') && has_module_access('performance_delete')): ?>
                        <form method="post" action="<?php echo site_url('performance/delete/'.$p->id); ?>" class="d-inline" onsubmit="return confirm('Delete this appraisal?');">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->load->view('partials/footer'); ?>
