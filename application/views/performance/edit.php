<?php $this->load->view('partials/header', ['title' => 'Edit Appraisal']); ?>
<div class="oms-form-compact">
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Edit Appraisal</h3>
            </div>
            <div class="card-body">
                <?php echo form_open('performance/edit/'.$appraisal->id); ?>
                <div class="mb-3">
                    <label>Employee</label>
                    <select name="employee_id" class="form-control" disabled>
                        <?php foreach($employees as $e): ?>
                        <option value="<?php echo $e->id; ?>" <?php echo ((int)$e->id === (int)$appraisal->employee_id) ? 'selected' : ''; ?>><?php echo esc_view($e->first_name . ' ' . $e->last_name . ' (' . $e->department . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Employee cannot be changed after creation.</small>
                </div>
                <div class="mb-3">
                    <label>Review Period (e.g. Q1 2024)</label>
                    <input type="text" name="period" class="form-control" required value="<?php echo esc_view($appraisal->period); ?>">
                </div>
                <div class="mb-3">
                    <label>KPI Score (0-100)</label>
                    <input type="number" step="0.1" name="kpi_score" class="form-control" required value="<?php echo esc_view($appraisal->kpi_score); ?>">
                </div>
                <div class="mb-3">
                    <label>Manager Rating</label>
                    <select name="rating" class="form-control">
                        <option value="1" <?php echo ((int)$appraisal->rating===1)?'selected':''; ?>>1 - Needs Improvement</option>
                        <option value="2" <?php echo ((int)$appraisal->rating===2)?'selected':''; ?>>2 - Below Expectations</option>
                        <option value="3" <?php echo ((int)$appraisal->rating===3)?'selected':''; ?>>3 - Meets Expectations</option>
                        <option value="4" <?php echo ((int)$appraisal->rating===4)?'selected':''; ?>>4 - Exceeds Expectations</option>
                        <option value="5" <?php echo ((int)$appraisal->rating===5)?'selected':''; ?>>5 - Outstanding</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="draft" <?php echo ($appraisal->status==='draft')?'selected':''; ?>>Draft</option>
                        <option value="submitted" <?php echo ($appraisal->status==='submitted')?'selected':''; ?>>Submitted</option>
                        <option value="approved" <?php echo ($appraisal->status==='approved')?'selected':''; ?>>Approved</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Comments</label>
                    <textarea name="comments" rows="4" class="form-control"><?php echo esc_view($appraisal->comments); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Appraisal</button>
                <a href="<?php echo site_url('performance'); ?>" class="btn btn-secondary">Cancel</a>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
</div>
<?php $this->load->view('partials/footer'); ?>
