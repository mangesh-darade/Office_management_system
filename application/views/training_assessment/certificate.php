<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$isCand = empty($au->user_id);
$displayName = $isCand ? $au->candidate_name : (!empty($au->assignee_user_name) ? $au->assignee_user_name : 'Employee');
$this->load->view('partials/header', array(
  'title' => 'Certificate of completion',
  'with_sidebar' => false,
));
?>
<style>
  @media print {
    .no-print { display: none !important; }
    .ta-cert { box-shadow: none !important; border: 1px solid #ccc !important; }
  }
</style>
<div class="container py-4">
  <p class="no-print mb-3"><a href="javascript:window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i>Print</a>
    <a href="<?php echo site_url('training-assessment/result/' . (int) $au->id); ?>" class="btn btn-outline-secondary btn-sm ms-1">Back to result</a></p>
  <div class="ta-cert card border-0 shadow-sm mx-auto" style="max-width:640px">
    <div class="card-body p-4 p-md-5 text-center">
      <div class="text-muted small text-uppercase letter-spacing mb-2">Certificate of completion</div>
      <h1 class="h3 mb-4"><?php echo htmlspecialchars($assessment && !empty($assessment->title) ? $assessment->title : $au->assessment_title); ?></h1>
      <p class="mb-1 text-muted">This certifies that</p>
      <p class="fs-4 fw-semibold mb-3"><?php echo htmlspecialchars($displayName); ?></p>
      <p class="text-muted small mb-4">has completed the assessment<?php if ($result && $result->submitted_at): ?> on <?php echo htmlspecialchars($result->submitted_at); ?><?php endif; ?>.</p>
      <div class="row g-2 justify-content-center mb-4">
        <div class="col-6 col-md-4">
          <div class="border rounded p-3 bg-light">
            <div class="small text-muted">Score</div>
            <div class="fs-5 fw-bold"><?php echo htmlspecialchars(number_format((float) $result->score_percent, 1)); ?>%</div>
          </div>
        </div>
        <div class="col-6 col-md-4">
          <div class="border rounded p-3 bg-light">
            <div class="small text-muted">Outcome</div>
            <div class="fs-6 fw-bold"><?php echo (int) $result->passed === 1 ? 'Passed' : 'Completed'; ?></div>
          </div>
        </div>
      </div>
      <p class="small text-muted mb-0">Issued by <?php echo function_exists('get_company_name') ? htmlspecialchars(get_company_name()) : 'Office Management System'; ?></p>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
