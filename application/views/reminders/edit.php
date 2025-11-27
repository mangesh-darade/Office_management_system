<?php $this->load->view('partials/header', ['title' => 'Edit Reminder']); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-bell"></i> Edit Reminder
        </h3>
        <a href="<?= site_url('reminders/dashboard') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square"></i> Reminder Details
                    </h5>
                </div>
                <div class="card-body">
                    <?php echo form_open('reminders/edit/'.$reminder->id); ?>
                    <?php $status = isset($reminder->status) ? $reminder->status : 'queued'; ?>
                        
                        <?php if($this->session->flashdata('error')): ?>
                            <div class="alert alert-danger">
                                <?= $this->session->flashdata('error') ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($this->session->flashdata('success')): ?>
                            <div class="alert alert-success">
                                <?= $this->session->flashdata('success') ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                <i class="bi bi-envelope"></i> Recipient Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($reminder->email) ?>" required <?= $status === 'sent' ? 'disabled' : '' ?>>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label fw-semibold">
                                <i class="bi bi-chat-left-text"></i> Subject <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="<?= htmlspecialchars($reminder->subject) ?>" required <?= $status === 'sent' ? 'disabled' : '' ?>>
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label fw-semibold">
                                <i class="bi bi-card-text"></i> Message
                            </label>
                            <textarea class="form-control" id="body" name="body" rows="8" <?= $status === 'sent' ? 'disabled' : '' ?>><?= htmlspecialchars($reminder->body) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_email" class="form-label fw-semibold">
                                        <i class="bi bi-envelope-arrow-right"></i> From Email
                                    </label>
                                    <input type="email" class="form-control" id="from_email" name="from_email" 
                                           value="<?= htmlspecialchars(isset($reminder->from_email) ? $reminder->from_email : '') ?>" <?= $status === 'sent' ? 'disabled' : '' ?>>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_name" class="form-label fw-semibold">
                                        <i class="bi bi-person-badge"></i> From Name
                                    </label>
                                    <input type="text" class="form-control" id="from_name" name="from_name" 
                                           value="<?= htmlspecialchars(isset($reminder->from_name) ? $reminder->from_name : '') ?>" <?= $status === 'sent' ? 'disabled' : '' ?>>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="send_at" class="form-label fw-semibold">
                                <i class="bi bi-calendar-event"></i> Schedule Send Time
                            </label>
                            <?php
                                $sendAtValue = '';
                                if (!empty($reminder->send_at)){
                                    // When coming from validation error, controller keeps raw datetime-local string
                                    if (strpos($reminder->send_at, 'T') !== false){
                                        $sendAtValue = $reminder->send_at;
                                    } else {
                                        $sendAtValue = date('Y-m-d\\TH:i', strtotime($reminder->send_at));
                                    }
                                }
                            ?>
                            <input type="datetime-local" class="form-control" id="send_at" name="send_at" 
                                   value="<?= htmlspecialchars($sendAtValue) ?>" <?= $status === 'sent' ? 'disabled' : '' ?>>
                            <small class="form-text text-muted">Leave empty to clear scheduling and send on next queue run. Time is interpreted using server time.</small>
                        </div>

                        <div class="alert alert-info d-flex align-items-start gap-2">
                            <i class="bi bi-info-circle"></i>
                            <div>
                                <strong>Status:</strong> <span class="badge bg-<?= $status === 'sent' ? 'success' : ($status === 'error' ? 'danger' : 'warning') ?>"><?= ucfirst($status) ?></span>
                                <?php if($status === 'sent'): ?>
                                    <br><small class="text-muted">This reminder has already been sent and cannot be edited.</small>
                                <?php elseif($status === 'error'): ?>
                                    <br><small class="text-muted">Previous send attempt failed. You can adjust the email details and schedule, then send again or wait for the queue.</small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if($status !== 'sent'): ?>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Update Reminder
                                </button>
                                <a href="<?= site_url('reminders/send-now/'.$reminder->id) ?>" class="btn btn-success" 
                                   onclick="return confirm('Send this reminder now to <?= htmlspecialchars($reminder->email) ?>?')">
                                    <i class="bi bi-send"></i> Send Now
                                </a>
                                <a href="<?= site_url('reminders/dashboard') ?>" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        <?php endif; ?>

                    <?php echo form_close(); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                            <i class="bi bi-info-circle"></i> Reminder Information
                        </h5>
                    </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th><i class="bi bi-key"></i> ID:</th>
                            <td><?= $reminder->id ?></td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-tag"></i> Type:</th>
                            <td><?= ucfirst(isset($reminder->type) ? $reminder->type : 'manual') ?></td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-flag"></i> Status:</th>
                            <td><span class="badge bg-<?= $status === 'sent' ? 'success' : ($status === 'error' ? 'danger' : 'warning') ?>"><?= ucfirst($status) ?></span></td>
                        </tr>
                        <tr>
                            <th><i class="bi bi-calendar-plus"></i> Created:</th>
                            <td><?= !empty($reminder->created_at) ? date('M j, Y H:i', strtotime($reminder->created_at)) : 'N/A' ?></td>
                        </tr>
                        <?php if(!empty($reminder->sent_at)): ?>
                        <tr>
                            <th><i class="bi bi-send-check"></i> Sent:</th>
                            <td><?= date('M j, Y H:i', strtotime($reminder->sent_at)) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if(!empty($reminder->send_at)): ?>
                        <tr>
                            <th><i class="bi bi-clock"></i> Scheduled:</th>
                            <td><?= date('M j, Y H:i', strtotime($reminder->send_at)) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if(!empty($reminder->user_id)): ?>
                        <tr>
                            <th><i class="bi bi-person"></i> User ID:</th>
                            <td><?= $reminder->user_id ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-code-slash"></i> Template Variables
                    </h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">You can use these variables in your message:</p>
                    <ul class="small">
                        <li><code>{name}</code> - Recipient name</li>
                        <li><code>{email}</code> - Recipient email</li>
                        <li><code>{date}</code> - Current date</li>
                        <li><code>{time}</code> - Current time</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('partials/footer'); ?>

