<?php $this->load->view('partials/header', ['title' => 'Employee Shifts']); ?>

<div class="">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Employee Shifts</h1>
        <a href="<?php echo base_url('shifts/create'); ?>" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add New Shift</a>
    </div>

    <?php if($this->session->flashdata('success')): ?>
        <div class="alert alert-success"><?php echo $this->session->flashdata('success'); ?></div>
    <?php endif; ?>
    <?php if($this->session->flashdata('error')): ?>
        <div class="alert alert-danger"><?php echo $this->session->flashdata('error'); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Shift Name</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Late Grace (Min)</th>
                            <th>Early Exit Grace (Min)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($shifts)): ?>
                            <?php foreach($shifts as $shift): ?>
                                <tr>
                                    <td><?php echo $shift->id; ?></td>
                                    <td><?php echo $shift->name; ?></td>
                                    <td><?php echo date('h:i A', strtotime($shift->start_time)); ?></td>
                                    <td><?php echo date('h:i A', strtotime($shift->end_time)); ?></td>
                                    <td><?php echo $shift->late_grace_period; ?></td>
                                    <td><?php echo $shift->early_exit_grace_period; ?></td>
                                    <td>
                                        <?php if($shift->is_active): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="btn btn-info btn-sm text-white" href="<?php echo base_url('shifts/edit/'.$shift->id); ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <?php if($shift->id != 1): ?>
                                            <a class="btn btn-danger btn-sm" href="<?php echo base_url('shifts/delete/'.$shift->id); ?>" onclick="return confirm('Are you sure you want to delete this shift?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-3">No shifts found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
