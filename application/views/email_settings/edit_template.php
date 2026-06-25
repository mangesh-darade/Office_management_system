<?php $this->load->view('partials/header', ['title' => 'Edit Email Template']); ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Edit Email Template</h3>
                <h5 class="text-muted"><?php echo ucfirst($setting->module) . ' - ' . ucfirst(str_replace('_', ' ', $setting->event_type)); ?></h5>
            </div>
            <div class="card-body">
                <?php echo form_open('email_settings/edit_template/'.$setting->id); ?>
                
                <div class="alert alert-info">
                    <strong>Available Placeholders:</strong><br>
                    {candidate_name}, {job_title}, {date}, {type}, {company_name}
                </div>

                <div class="mb-3">
                    <label>Email Body Template</label>
                    <textarea name="email_template" class="form-control" rows="15" required><?php echo esc_view($setting->email_template); ?></textarea>
                    <small class="text-muted">Use the placeholders above to insert dynamic content.</small>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?php echo site_url('email-settings'); ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Template</button>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
