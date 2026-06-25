<?php $this->load->view('partials/header', ['title' => 'Approval Workflows']); ?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3>Approval Workflows</h3>
            <?php if(function_exists('has_module_access') && (has_module_access('approvals') || is_admin_group())): ?>
            <a href="<?php echo site_url('approvals/create'); ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Create New Flow
            </a>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Name</th>
                            <th>Active</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($flows)): ?>
                            <tr><td colspan="5" class="text-center">No approval flows found.</td></tr>
                        <?php else: ?>
                            <?php foreach($flows as $flow): ?>
                                <tr>
                                    <td><span class="badge bg-info"><?php echo ucfirst($flow->module); ?></span></td>
                                    <td><?php echo esc_view($flow->name); ?></td>
                                    <td>
                                        <?php if($flow->is_active): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($flow->created_at)); ?></td>
                                    <td>
                                        <?php if(function_exists('has_module_access') && (has_module_access('approvals') || is_admin_group())): ?>
                                        <a href="<?php echo site_url('approvals/edit/'.$flow->id); ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
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
