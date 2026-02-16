<?php $this->load->view('partials/header', ['title' => isset($action) && $action === 'edit' ? 'Edit Shift' : 'Create New Shift']); ?>

<div class="">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo isset($action) && $action === 'edit' ? 'Edit Shift' : 'Create New Shift'; ?></h1>
        <a href="<?php echo base_url('shifts'); ?>" class="btn btn-secondary">Back to List</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">Shift Details</h5>
        </div>
        
        <?php echo form_open(''); ?>
            <div class="card-body">
                <div class="mb-3">
                    <label for="name" class="form-label">Shift Name</label>
                    <input type="text" class="form-control" name="name" id="name" placeholder="Enter shift name" value="<?php echo isset($shift->name) ? $shift->name : set_value('name'); ?>" required>
                    <?php echo form_error('name', '<div class="text-danger mt-1">', '</div>'); ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_time" class="form-label">Start Time</label>
                        <input type="time" class="form-control" name="start_time" id="start_time" value="<?php echo isset($shift->start_time) ? $shift->start_time : set_value('start_time'); ?>" required>
                        <?php echo form_error('start_time', '<div class="text-danger mt-1">', '</div>'); ?>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="end_time" class="form-label">End Time</label>
                        <input type="time" class="form-control" name="end_time" id="end_time" value="<?php echo isset($shift->end_time) ? $shift->end_time : set_value('end_time'); ?>" required>
                        <?php echo form_error('end_time', '<div class="text-danger mt-1">', '</div>'); ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="late_grace_period" class="form-label">Late Grace Period (Minutes)</label>
                        <input type="number" class="form-control" name="late_grace_period" id="late_grace_period" value="<?php echo isset($shift->late_grace_period) ? $shift->late_grace_period : (set_value('late_grace_period') ? set_value('late_grace_period') : 15); ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="early_exit_grace_period" class="form-label">Early Exit Grace Period (Minutes)</label>
                        <input type="number" class="form-control" name="early_exit_grace_period" id="early_exit_grace_period" value="<?php echo isset($shift->early_exit_grace_period) ? $shift->early_exit_grace_period : (set_value('early_exit_grace_period') ? set_value('early_exit_grace_period') : 0); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo (isset($shift->is_active) && $shift->is_active) || !isset($shift) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active Shift</label>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="<?php echo base_url('shifts'); ?>" class="btn btn-light ms-2">Cancel</a>
            </div>
        <?php echo form_close(); ?>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
