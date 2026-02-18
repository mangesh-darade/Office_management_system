<?php $this->load->view('partials/header', ['title' => 'Apply for Job']); ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>Apply for: <?php echo htmlspecialchars($job->title); ?></h3>
                <h5 class="text-muted"><?php echo htmlspecialchars($job->description); ?></h5>
            </div>
            <div class="card-body">
                <?php echo form_open_multipart('recruitment/apply/'.$job->id); ?>
                <div class="mb-3">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Upload Resume (PDF/DOC)</label>
                    <input type="file" name="resume" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary w-100">Submit Application</button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('partials/footer'); ?>
