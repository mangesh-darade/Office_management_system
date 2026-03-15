<?php $this->load->view('partials/header', ['title' => 'New Appraisal']); ?>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Submit Employee Appraisal</h3>
            </div>
            <div class="card-body">
                <?php echo form_open('performance/create'); ?>
                <div class="mb-3">
                    <label>Employee</label>
                    <select name="employee_id" class="form-control" require>
                        <?php foreach($employees as $e): ?>
                        <option value="<?php echo (int)$e->id; ?>">
                            <?php echo htmlspecialchars($e->first_name . ' ' . $e->last_name . ' (' . $e->department . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Review Period (e.g. Q1 2024)</label>
                    <input type="text" name="period" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>KPI Score (0-100)</label>
                    <input type="number" step="0.1" name="kpi_score" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Manager Rating</label>
                    <select name="rating" class="form-control">
                        <option value="1">1 - Needs Improvement</option>
                        <option value="2">2 - Below Expectations</option>
                        <option value="3">3 - Meets Expectations</option>
                        <option value="4">4 - Exceeds Expectations</option>
                        <option value="5">5 - Outstanding</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Comments</label>
                    <textarea name="comments" rows="4" class="form-control"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Appraisal</button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('partials/footer'); ?>
