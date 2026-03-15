<?php $this->load->view('partials/header', ['title' => 'Apply for ' . htmlspecialchars($job->title)]); ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-7 col-lg-6">

      <?php if (!empty($applied)): ?>
        <!-- Success state -->
        <div class="card shadow-sm border-0 text-center p-5">
          <div class="text-success mb-3"><i class="bi bi-check-circle-fill" style="font-size:3.5rem;"></i></div>
          <h4 class="fw-bold mb-2">Application Submitted!</h4>
          <p class="text-muted mb-4">Thank you for applying for <strong><?php echo htmlspecialchars($job->title); ?></strong>. We have received your application and will review it shortly. We will contact you if your profile matches our requirements.</p>
          <a href="<?php echo site_url('recruitment'); ?>" class="btn btn-outline-primary">View Other Openings</a>
        </div>
      <?php else: ?>
        <!-- Job info header -->
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($job->title); ?></h4>
            <?php if ($job->department): ?>
              <span class="badge bg-light text-dark border me-2"><?php echo htmlspecialchars($job->department); ?></span>
            <?php endif; ?>
            <?php if ($job->experience_level): ?>
              <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($job->experience_level); ?></span>
            <?php endif; ?>
            <?php if ($job->description): ?>
              <p class="text-muted mt-2 mb-0 small"><?php echo nl2br(htmlspecialchars($job->description)); ?></p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Application form -->
        <div class="card shadow-sm border-0">
          <div class="card-header bg-transparent fw-semibold">Your Application</div>
          <div class="card-body p-4">
            <form method="post" action="<?php echo site_url('recruitment/apply/' . $job->id); ?>" enctype="multipart/form-data">
              <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>

              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label fw-semibold">First Name <span class="text-danger">*</span></label>
                  <input type="text" name="first_name" class="form-control" required placeholder="John">
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold">Last Name <span class="text-danger">*</span></label>
                  <input type="text" name="last_name" class="form-control" required placeholder="Doe">
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="john@example.com">
              </div>

              <div class="mb-3">
                <label class="form-label fw-semibold">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="+1 555 000 0000">
              </div>

              <div class="mb-4">
                <label class="form-label fw-semibold">Resume <span class="text-muted small">(PDF, DOC, DOCX — max 2MB)</span></label>
                <input type="file" name="resume" class="form-control" accept=".pdf,.doc,.docx">
              </div>

              <button type="submit" class="btn btn-primary w-100 py-2">
                <i class="bi bi-send me-2"></i>Submit Application
              </button>
            </form>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
