<?php $this->load->view('partials/header'); ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 mb-4">
            <h2 class="text-primary"><i class="bi bi-cpu"></i> AI Analytics & Integrations Hub</h2>
            <p class="text-muted">Predictive insights and system connectivity.</p>
        </div>
    </div>

    <!-- AI Insights Section -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-left-primary">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-graph-down-arrow text-danger"></i> Attrition Risk Radar</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($attrition_risks)): ?>
                        <div class="alert alert-success">No high-risk employees detected.</div>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                        <?php foreach($attrition_risks as $risk): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo $risk['name']; ?></strong><br>
                                    <small class="text-muted"><?php echo implode(', ', $risk['factors']); ?></small>
                                </div>
                                <span class="badge bg-<?php echo ($risk['risk_score'] > 70 ? 'danger' : 'warning'); ?> rounded-pill">
                                    <?php echo $risk['risk_score']; ?>% Risk
                                </span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-left-info">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history text-info"></i> Attendance Forecast</h5>
                </div>
                <div class="card-body text-center">
                    <h6>My Trend Analysis</h6>
                    <h3 class="display-6 text-<?php echo ($my_forecast['trend'] == 'Getting Later' ? 'warning' : 'success'); ?>">
                        <?php echo $my_forecast['trend']; ?>
                    </h3>
                    <p>Average Arrival: <strong><?php echo floor($my_forecast['avg_time']/60) . ':' . str_pad($my_forecast['avg_time']%60, 2, '0', STR_PAD_LEFT); ?></strong></p>
                    <small class="text-muted">Based on Linear Regression of last 30 days</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-left-success">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-emoji-smile text-success"></i> Sentiment Analyzer</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo site_url('analytics/analyze_feedback'); ?>" method="post">
                        <div class="mb-3">
                            <textarea name="feedback_text" class="form-control" rows="3" placeholder="Paste feedback text here..."><?php echo $this->session->flashdata('analyzed_text'); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Analyze Sentiment</button>
                    </form>

                    <?php if($this->session->flashdata('sentiment_result')): 
                        $res = $this->session->flashdata('sentiment_result'); ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <h6>Result: <span class="badge bg-<?php echo ($res['label']=='Positive'?'success':($res['label']=='Negative'?'danger':'secondary')); ?>"><?php echo $res['label']; ?></span></h6>
                            <small>Score: <?php echo $res['score']; ?></small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Resume Parsing Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-person"></i> Smart Resume Parser</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="<?php echo site_url('analytics/parse_resume'); ?>" method="post" enctype="multipart/form-data">
                                <div class="input-group mb-3">
                                    <input type="file" class="form-control" name="resume_file" accept=".txt,.pdf">
                                    <button class="btn btn-primary" type="submit">Auto-Extract Info</button>
                                </div>
                                <small class="text-muted">Upload a plain text resume to extract contact info and skills via Regex pattern matching.</small>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <?php if($this->session->flashdata('resume_result')): 
                                $cv = $this->session->flashdata('resume_result'); ?>
                                <div class="border p-3 rounded bg-light">
                                    <p><strong>Email:</strong> <?php echo $cv['email'] ? $cv['email'] : 'Not found'; ?></p>
                                    <p><strong>Phone:</strong> <?php echo $cv['phone'] ? $cv['phone'] : 'Not found'; ?></p>
                                    <p><strong>Skills Detected:</strong> 
                                        <?php foreach($cv['skills'] as $skill): ?>
                                            <span class="badge bg-secondary"><?php echo $skill; ?></span>
                                        <?php endforeach; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Integrations Section -->
    <div class="row">
        <div class="col-12">
            <h4 class="mb-3">System Integrations</h4>
        </div>
        
        <!-- Slack -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-slack"></i> Slack Notifications</h5>
                    <p class="card-text small text-muted">Receive system alerts in your Slack channels via Webhooks.</p>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#slackConfig">Configure</button>
                    <div class="collapse mt-2" id="slackConfig">
                        <form action="<?php echo site_url('analytics/save_integrations'); ?>" method="post">
                            <input type="text" name="slack_webhook" class="form-control form-control-sm mb-2" placeholder="Webhook URL" value="<?php echo $this->session->userdata('slack_webhook'); ?>">
                            <button class="btn btn-sm btn-primary w-100">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Call -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-camera-video"></i> Start Video Call</h5>
                    <p class="card-text small text-muted">Instantly video call a team member via Office Chat.</p>
                    <form action="<?php echo site_url('analytics/start_quick_call'); ?>" method="post">
                        <select name="target_user" class="form-select form-select-sm mb-2" required>
                            <option value="">Select a person...</option>
                            <?php foreach($chat_users as $u): ?>
                                <?php if($u->id != $this->session->userdata('user_id')): ?>
                                    <option value="<?php echo $u->id; ?>"><?php echo isset($u->name) ? $u->name : (isset($u->full_name) ? $u->full_name : $u->email); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-success w-100" type="submit"><i class="bi bi-telephone-outbound"></i> Call Now</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Google Calendar -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-calendar-google"></i> Google Calendar</h5>
                    <p class="card-text small text-muted">Sync your schedule using iCal feed.</p>
                    <div class="input-group input-group-sm mb-2">
                        <input type="text" class="form-control" value="<?php echo site_url('analytics/calendar_feed/' . md5($this->session->userdata('user_id'))); ?>" readonly id="icalUrl">
                        <button class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('icalUrl').value)">Copy</button>
                    </div>
                    <small class="text-muted d-block mt-2">
                        Add to Google Calendar: Settings > Add Calendar > From URL
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('partials/footer'); ?>
