<?php $this->load->view('partials/header', ['title' => 'Candidates']); ?>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Job</th>
            <th>Email</th>
            <th>Resume</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($candidates as $c): ?>
        <tr>
            <td><?php echo $c->first_name . ' ' . $c->last_name; ?></td>
            <td><?php echo $c->job_title; ?></td>
            <td><?php echo $c->email; ?></td>
            <td><a href="<?php echo base_url($c->resume_path); ?>" target="_blank">Download</a></td>
            <td><?php echo ucfirst($c->status); ?></td>
            <td>
                <?php if($c->status != 'hired'): ?>
                <a href="<?php echo site_url('recruitment/schedule_interview/'.$c->id); ?>" class="btn btn-sm btn-warning">Schedule Interview</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php $this->load->view('partials/footer'); ?>
