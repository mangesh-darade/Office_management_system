<?php $this->load->view('partials/header', ['title' => 'Create Job Post']); ?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Create New Job Post</h3>
            </div>
            <div class="card-body">
                <?php echo form_open('recruitment/create_job'); ?>
                <div class="mb-3">
                    <label>Job Title</label>
                    <input type="text" name="title" class="form-control" require>
                </div>
                <div class="mb-3">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="5"></textarea>
                </div>
                <div class="mb-3">
                    <label>Number of Positions</label>
                    <input type="number" name="positions" class="form-control" value="1">
                </div>
                <button type="submit" class="btn btn-primary">Publish Job</button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('partials/footer'); ?>
