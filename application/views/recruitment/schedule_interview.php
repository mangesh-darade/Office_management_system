<?php $this->load->view('partials/header', ['title' => 'Schedule Interview']); ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3>Schedule Interview for <?php echo esc_view($candidate->first_name); ?></h3>
            </div>
            <div class="card-body">
                <?php echo form_open('recruitment/schedule_interview/'.$candidate->id); ?>
                <div class="mb-3">
                    <label>Interviewer</label>
                    <select name="interviewer_id" class="form-control">
                        <?php foreach($interviewers as $u): ?>
                        <option value="<?php echo (int)$u->id; ?>"><?php echo esc_view($u->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Date & Time</label>
                    <input type="datetime-local" name="interview_date" class="form-control" require>
                </div>
                <div class="mb-3">
                    <label>Interview Type</label>
                    <select name="type" class="form-control">
                        <option value="Face to Face">Face-to-Face</option>
                        <option value="Video Call">Video Call</option>
                        <option value="Phone">Phone</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success">Schedule</button>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('partials/footer'); ?>
