<?php $this->load->view('partials/header', ['title' => 'Recruitment - Job Posts']); ?>
<div class="d-flex justify-content-between mb-3">
    <h3>Job Openings</h3>
    <?php if(in_array($role, [1,2])): ?>
    <a href="<?php echo site_url('recruitment/create_job'); ?>" class="btn btn-primary">Create Job</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Positions</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($jobs as $job): ?>
                <tr>
                    <td><?php echo htmlspecialchars($job->title); ?></td>
                    <td><?php echo $job->positions; ?></td>
                    <td><span class="badge bg-<?php echo $job->status=='open'?'success':'secondary'; ?>"><?php echo ucfirst($job->status); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($job->created_at)); ?></td>
                    <td>
                        <a href="<?php echo site_url('recruitment/apply/'.$job->id); ?>" class="btn btn-sm btn-info">View/Apply</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->load->view('partials/footer'); ?>
