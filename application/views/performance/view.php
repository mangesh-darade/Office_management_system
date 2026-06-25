<?php $this->load->view('partials/header', ['title' => 'View Appraisal']); ?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between mb-3">
            <h3>Appraisal Details</h3>
            <a href="<?php echo site_url('performance'); ?>" class="btn btn-outline-secondary">Back</a>
        </div>
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width:30%">Employee</th>
                        <td><?php echo esc_view($appraisal->first_name . ' ' . $appraisal->last_name); ?></td>
                    </tr>
                    <tr>
                        <th>Manager</th>
                        <td><?php echo esc_view($appraisal->manager_name); ?></td>
                    </tr>
                    <tr>
                        <th>Period</th>
                        <td><?php echo esc_view($appraisal->period); ?></td>
                    </tr>
                    <tr>
                        <th>KPI Score</th>
                        <td><?php echo esc_view($appraisal->kpi_score); ?></td>
                    </tr>
                    <tr>
                        <th>Rating</th>
                        <td><?php echo (int)$appraisal->rating; ?> / 5</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="badge bg-<?php echo ($appraisal->status==='approved'?'success':($appraisal->status==='submitted'?'info':'secondary')); ?>"><?php echo ucfirst($appraisal->status); ?></span></td>
                    </tr>
                    <tr>
                        <th>Comments</th>
                        <td><?php echo nl2br(esc_view($appraisal->comments)); ?></td>
                    </tr>
                    <tr>
                        <th>Created</th>
                        <td><?php echo date('d M Y H:i', strtotime($appraisal->created_at)); ?></td>
                    </tr>
                </table>
                <div class="mt-3">
                    <?php if(function_exists('has_module_access') && (has_module_access('performance_edit') || has_module_access('performance'))): ?>
                    <a href="<?php echo site_url('performance/edit/'.$appraisal->id); ?>" class="btn btn-warning">Edit</a>
                    <?php endif; ?>
                    <a href="<?php echo site_url('performance'); ?>" class="btn btn-secondary">Back to List</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('partials/footer'); ?>
