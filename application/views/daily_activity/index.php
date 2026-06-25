<?php $this->load->view('partials/header', ['title' => 'Daily Activity Log']); ?>

<style>
/* Custom tweaks for Summernote to look more professional and compact */
.note-editor.note-frame {
    border-radius: 0.375rem; /* Bootstrap rounded-2 */
    border-color: #dee2e6;
    box-shadow: none;
}
.note-editor.note-frame .note-toolbar {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
    padding: 5px;
}
.note-editor .note-toolbar .note-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
    color: #495057;
    background: transparent;
    border: none;
}
.note-editor .note-toolbar .note-btn:hover {
    background-color: #e9ecef;
    border-radius: 0.2rem;
}
.note-editor .note-toolbar .note-dropdown-menu {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
/* Ensure description images don't overflow */
.activity-description img {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
}
.activity-description {
    font-size: 0.95rem;
    color: #212529;
}
/* Mobile tweaks */
@media (max-width: 768px) {
    .note-editor .note-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Log Activity Form -->
        <div class="col-lg-4">
             <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-pencil-square me-2 text-primary"></i>Log Activity</h5>
                        <div class="text-muted small mt-1">For <span class="fw-semibold text-primary"><?php echo date('M d, Y', strtotime($date)); ?></span></div>
                    </div>
                    <a href="<?php echo site_url('daily_activity/list_all'); ?>" class="btn btn-sm btn-outline-primary d-lg-none" title="View History"><i class="bi bi-list-ul"></i></a>
                </div>
                <div class="card-body">
                    <?php echo form_open('daily_activity/save'); ?>
                        <input type="hidden" name="work_date" value="<?php echo $date; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Activity / Task</label>
                            <input class="form-control" list="taskOptions" name="activity_title" id="activityTitleInput" placeholder="search or type new activity..." autocomplete="off">
                            <datalist id="taskOptions">
                                <?php foreach($tasks as $t): ?>
                                    <option data-id="<?php echo $t->id; ?>" value="<?php echo esc_view($t->title); ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                            <input type="hidden" name="task_id" id="taskIdInput">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-uppercase text-muted">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="description" id="summernote" required></textarea>
                        </div>

                        <!-- Summernote & Datalist Logic -->
                        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
                        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
                        <script>
                            $(document).ready(function() {
                                $('#summernote').summernote({
                                    placeholder: 'Type your updates here...',
                                    tabsize: 2,
                                    height: 180,
                                    toolbar: [
                                        ['font', ['bold', 'underline', 'clear']],
                                        ['para', ['ul', 'ol']],
                                        ['insert', ['link']],
                                        ['view', ['fullscreen']]
                                    ],
                                    disableDragAndDrop: true
                                });
                                
                                // Map datalist value to ID
                                $('#activityTitleInput').on('input', function() {
                                    var val = $(this).val();
                                    var id = $('#taskOptions option[value="' + val + '"]').data('id');
                                    $('#taskIdInput').val(id ? id : '');
                                });
                            });
                        </script>

                        <div class="d-grid pt-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Save Log</button>
                        </div>
                    <?php echo form_close(); ?>
                </div>
             </div>
        </div>

        <!-- Activity Feed -->
        <div class="col-lg-8">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h4 class="mb-0 fw-bold">My Activities</h4>
                    <p class="text-muted small mb-0">Track your daily progress</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo site_url('daily_activity/list_all'); ?>" class="btn btn-sm btn-outline-secondary d-flex align-items-center"><i class="bi bi-list-ul me-1"></i><span class="d-none d-sm-inline">History</span></a>
                    <form class="d-flex position-relative" method="get">
                        <input type="date" class="form-control form-control-sm" name="date" value="<?php echo $date; ?>" onchange="this.form.submit()" style="min-width: 130px;">
                    </form>
                </div>
            </div>

            <?php if($this->session->flashdata('success')): ?>
                <div class="alert alert-success shadow-sm border-0 d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i><?php echo esc_view($this->session->flashdata('success')); ?>
                </div>
            <?php endif; ?>
            <?php if($this->session->flashdata('error')): ?>
                <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i><?php echo esc_view($this->session->flashdata('error')); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="list-group list-group-flush rounded-3">
                    <?php if(empty($logs)): ?>
                        <div class="list-group-item text-center text-muted py-5 border-0">
                            <div class="mb-3">
                                <div class="bg-light rounded-circle d-inline-flex p-3">
                                    <i class="bi bi-journal-plus fs-1 text-secondary"></i>
                                </div>
                            </div>
                            <h5>No activities yet</h5>
                            <p class="small mb-0">Use the form to log your first activity for today.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($logs as $log): ?>
                            <div class="list-group-item p-4 border-bottom action-hover-container">
                                <div class="d-flex justify-content-between">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="badge bg-light text-dark border me-2">
                                            <i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($log->created_at)); ?>
                                        </div>
                                        <?php if($log->activity_title): ?>
                                             <span class="fw-bold text-dark me-2"><?php echo esc_view($log->activity_title); ?></span>
                                        <?php elseif($log->task_title): ?>
                                            <span class="fw-bold text-dark me-2"><?php echo esc_view($log->task_title); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small fst-italic">General Update</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if(function_exists('has_module_access') && (has_module_access('daily_activity_delete') || has_module_access('daily_activity'))): ?>
                                    <?php echo form_open('daily_activity/delete/' . $log->id, ['onsubmit' => "return confirm('Delete this log?');", 'class' => 'opacity-50 hover-opacity-100']); ?>
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete"><i class="bi bi-x-lg"></i></button>
                                    <?php echo form_close(); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-description mt-2">
                                    <?php echo $log->description; // Already HTML safe from Summernote (but be careful of XSS if public input allowed. Internal tool -> acceptable risk if trusted users) ?> 
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
