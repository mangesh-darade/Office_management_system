<?php $this->load->view('partials/header', ['title' => 'Payslip']); ?>
<?php
  $fn = isset($row->first_name) ? trim((string)$row->first_name) : '';
  $ln = isset($row->last_name) ? trim((string)$row->last_name) : '';
  if ($fn !== '' || $ln !== '') {
    $empName = trim($fn.' '.$ln);
  } elseif (isset($row->name) && $row->name !== '') {
    $empName = (string)$row->name;
  } elseif (isset($row->email) && $row->email !== '') {
    $empName = (string)$row->email;
  } else {
    $empName = '';
  }
  $empCode = isset($row->emp_code) ? (string)$row->emp_code : '';
  $designation = isset($row->designation) ? (string)$row->designation : '';
  $department = isset($row->department) ? (string)$row->department : '';
  $doj = isset($row->join_date) ? (string)$row->join_date : '';

  $payMode = isset($row->pay_mode) ? (string)$row->pay_mode : '';
  $bankName = isset($row->bank_name) ? (string)$row->bank_name : '';
  $bankAcNo = isset($row->bank_ac_no) ? (string)$row->bank_ac_no : '';
  $panNo = isset($row->pan_no) ? (string)$row->pan_no : '';
  $location = isset($row->location) ? (string)$row->location : '';

  $addrLines = [];
  $addrMain = isset($row->address) ? trim((string)$row->address) : '';
  if ($addrMain !== '') { $addrLines[] = $addrMain; }
  $line2 = [];
  if (!empty($row->city))    { $line2[] = (string)$row->city; }
  if (!empty($row->state))   { $line2[] = (string)$row->state; }
  if (!empty($row->zipcode)) { $line2[] = (string)$row->zipcode; }
  if (!empty($line2)) { $addrLines[] = implode(', ', $line2); }
  if (!empty($row->country)) { $addrLines[] = (string)$row->country; }
  $empAddress = trim(implode("\n", $addrLines));

  $companyName = isset($settings['company_name']) && $settings['company_name'] !== ''
    ? $settings['company_name'] : get_company_name();
  $companyAddress = isset($settings['company_address']) ? trim((string)$settings['company_address']) : '';
  $companyLogo = isset($settings['company_logo']) && $settings['company_logo'] !== ''
    ? base_url($settings['company_logo']) : '';

  $rawPeriod = isset($row->period) ? (string)$row->period : '';
  $formattedPeriod = $rawPeriod;
  if (preg_match('/^(\d{4})-(\d{2})$/', $rawPeriod, $m)) {
    $monthNum = (int)$m[2];
    $year = $m[1];
    $monthNames = [
      1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
      5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
      9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
    if (isset($monthNames[$monthNum])) {
      $formattedPeriod = $monthNames[$monthNum].'-'.$year;
    }
  }

  $paymentDays = isset($row->payment_days) ? (float)$row->payment_days : 0.0;
  $presentDays = isset($row->present_days) ? (float)$row->present_days : 0.0;
  $paidLeaves  = isset($row->paid_leaves) ? (float)$row->paid_leaves : 0.0;
  $lwp         = isset($row->leave_without_pay) ? (float)$row->leave_without_pay : 0.0;
  $balLeaves   = isset($row->balance_leaves) ? (float)$row->balance_leaves : 0.0;

  $basic      = (float)(isset($row->basic) ? $row->basic : 0);
  $hra        = (float)(isset($row->hra) ? $row->hra : 0);
  $convAllow  = (float)(isset($row->conveyance_allow) ? $row->conveyance_allow : 0);
  $medAllow   = (float)(isset($row->medical_allow) ? $row->medical_allow : 0);
  $eduAllow   = (float)(isset($row->education_allow) ? $row->education_allow : 0);
  $specAllow  = (float)(isset($row->special_allow) ? $row->special_allow : 0);
  $otherAllow = (float)(isset($row->allowances) ? $row->allowances : 0);
  $gross      = (float)(isset($row->gross) ? $row->gross : 0);

  $profTax    = (float)(isset($row->professional_tax) ? $row->professional_tax : 0);
  $tds        = (float)(isset($row->tds) ? $row->tds : 0);
  $pf         = (float)(isset($row->pf_amount) ? $row->pf_amount : 0);
  $esi        = (float)(isset($row->esi_amount) ? $row->esi_amount : 0);
  $otherDed   = (float)(isset($row->deductions) ? $row->deductions : 0);
  $net        = (float)(isset($row->net) ? $row->net : 0);
  $totalDed   = $gross - $net;
?>

<style>
  @media print {
    .no-print { display:none !important; }
    body, html { background:#fff !important; margin:0; padding:0; }
    .payslip-page { border:none !important; box-shadow:none !important; margin:0 !important; }
  }

  .payslip-page {
    max-width: 820px;
    margin: 0 auto 2rem auto;
    background: #fff;
    border: 2px solid #1a237e;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 12px;
    color: #222;
    box-shadow: 0 4px 24px rgba(0,0,0,0.10);
  }

  /* Company header band */
  .ps-company-band {
    background: #1a237e;
    color: #fff;
    padding: 14px 20px;
  }
  .ps-company-name {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
  }
  .ps-company-addr {
    font-size: 11px;
    opacity: 0.85;
    margin-top: 2px;
  }
  .ps-company-logo {
    height: 48px;
    max-width: 160px;
    object-fit: contain;
  }

  /* Month title bar */
  .ps-month-bar {
    background: #e8eaf6;
    border-top: 2px solid #1a237e;
    border-bottom: 2px solid #1a237e;
    text-align: center;
    font-weight: 700;
    font-size: 13px;
    padding: 6px 0;
    color: #1a237e;
    letter-spacing: 0.5px;
  }

  /* Info tables */
  .ps-info-table {
    width: 100%;
    border-collapse: collapse;
  }
  .ps-info-table th,
  .ps-info-table td {
    border: 1px solid #bbb;
    padding: 5px 8px;
    font-size: 11.5px;
  }
  .ps-info-table th {
    background: #f5f5f5;
    font-weight: 600;
    color: #333;
    width: 38%;
    white-space: nowrap;
  }
  .ps-info-table td {
    color: #111;
  }

  /* Attendance / Leave table */
  .ps-attend-table {
    width: 100%;
    border-collapse: collapse;
  }
  .ps-attend-table th,
  .ps-attend-table td {
    border: 1px solid #bbb;
    padding: 4px 8px;
    font-size: 11.5px;
  }
  .ps-attend-table .ps-attend-header {
    background: #e8eaf6;
    font-weight: 700;
    color: #1a237e;
    text-align: center;
    font-size: 11px;
    text-transform: uppercase;
  }

  /* Earnings / Deductions */
  .ps-ed-table {
    width: 100%;
    border-collapse: collapse;
  }
  .ps-ed-table th,
  .ps-ed-table td {
    border: 1px solid #bbb;
    padding: 5px 8px;
    font-size: 11.5px;
  }
  .ps-ed-header {
    background: #1a237e;
    color: #fff;
    font-weight: 700;
    text-align: center;
    font-size: 12px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
  }
  .ps-ed-label {
    color: #333;
  }
  .ps-ed-val {
    text-align: right;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
  }

  /* Totals */
  .ps-total-row td {
    background: #fff3e0;
    font-weight: 700;
    border-top: 2px solid #e65100;
    border-bottom: 2px solid #e65100;
    font-size: 12px;
  }
  .ps-total-earning { color: #2e7d32; }
  .ps-total-deduction { color: #c62828; }

  /* Net pay */
  .ps-netpay {
    margin: 0;
    padding: 10px 20px;
    background: #1a237e;
    color: #fff;
    font-size: 15px;
    font-weight: 700;
    display: flex;
    justify-content: space-between;
    align-items: center;
    letter-spacing: 0.5px;
  }

  /* Remarks */
  .ps-remarks {
    padding: 6px 20px;
    border-top: 1px solid #bbb;
    font-size: 11px;
  }
  .ps-remarks strong { color: #333; }

  /* Footer note */
  .ps-footer-note {
    text-align: center;
    font-size: 10px;
    color: #777;
    padding: 8px 20px;
    border-top: 1px solid #ddd;
    background: #fafafa;
  }

  /* Body section spacing */
  .ps-body { padding: 12px 20px; }
  .ps-body-section { margin-bottom: 10px; }

  /* Responsive: slightly smaller on mobile */
  @media (max-width: 600px) {
    .payslip-page { font-size: 10px; }
    .ps-company-band { padding: 10px 12px; }
    .ps-company-name { font-size: 14px; }
    .ps-body { padding: 8px 10px; }
    .ps-netpay { padding: 8px 12px; font-size: 13px; }
    .ps-info-table th, .ps-info-table td,
    .ps-attend-table th, .ps-attend-table td,
    .ps-ed-table th, .ps-ed-table td { padding: 3px 5px; font-size: 10px; }
  }
</style>

<div class="container-fluid py-3 no-print">
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-1 fw-bold"><i class="bi bi-receipt text-primary me-2"></i>Payslip</h4>
      <p class="text-muted mb-0 small">Payslip for <?php echo htmlspecialchars($empName ?: 'Employee'); ?> &mdash; <?php echo htmlspecialchars($formattedPeriod); ?></p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-sm-0">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('payroll/payslips'); ?>"><i class="bi bi-arrow-left me-1"></i>Back</a>
      <button class="btn btn-primary btn-sm" type="button" onclick="window.print();"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
  </div>
</div>

<div class="payslip-page">

  <!-- ===== COMPANY HEADER ===== -->
  <div class="ps-company-band">
    <table style="width:100%; border:0;">
      <tr>
        <td style="vertical-align:middle;">
          <?php if ($companyLogo): ?>
            <img src="<?php echo htmlspecialchars($companyLogo); ?>" alt="Logo" class="ps-company-logo" />
          <?php endif; ?>
          <div class="ps-company-name"><?php echo htmlspecialchars($companyName); ?></div>
        </td>
        <td style="vertical-align:middle; text-align:right;">
          <?php if ($companyAddress !== ''): ?>
            <div class="ps-company-addr"><?php echo nl2br(htmlspecialchars($companyAddress)); ?></div>
          <?php endif; ?>
        </td>
      </tr>
    </table>
  </div>

  <!-- ===== MONTH TITLE BAR ===== -->
  <div class="ps-month-bar">
    PAY SLIP FOR THE MONTH OF &nbsp;<?php echo htmlspecialchars($formattedPeriod); ?>
  </div>

  <div class="ps-body">

    <!-- ===== EMPLOYEE INFO (two-column) ===== -->
    <div class="ps-body-section">
      <table style="width:100%; border-collapse:collapse;">
        <tr>
          <td style="width:50%; vertical-align:top; padding-right:4px;">
            <table class="ps-info-table">
              <tr>
                <th>Name</th>
                <td><?php echo htmlspecialchars($empName ?: '-'); ?></td>
              </tr>
              <tr>
                <th>Employee Code</th>
                <td><?php echo htmlspecialchars($empCode !== '' ? $empCode : '-'); ?></td>
              </tr>
              <tr>
                <th>Designation</th>
                <td><?php echo htmlspecialchars($designation !== '' ? $designation : '-'); ?></td>
              </tr>
              <tr>
                <th>Location</th>
                <td><?php echo htmlspecialchars($location !== '' ? $location : '-'); ?></td>
              </tr>
              <tr>
                <th>Date of Joining</th>
                <td><?php echo htmlspecialchars($doj !== '' ? $doj : '-'); ?></td>
              </tr>
            </table>
          </td>
          <td style="width:50%; vertical-align:top; padding-left:4px;">
            <table class="ps-info-table">
              <tr>
                <th>Pay Mode</th>
                <td><?php echo htmlspecialchars($payMode !== '' ? $payMode : '-'); ?></td>
              </tr>
              <tr>
                <th>Bank Name</th>
                <td><?php echo htmlspecialchars($bankName !== '' ? $bankName : '-'); ?></td>
              </tr>
              <tr>
                <th>Bank A/C No</th>
                <td><?php echo htmlspecialchars($bankAcNo !== '' ? $bankAcNo : '-'); ?></td>
              </tr>
              <tr>
                <th>PAN No</th>
                <td><?php echo htmlspecialchars($panNo !== '' ? $panNo : '-'); ?></td>
              </tr>
              <tr>
                <th>Department</th>
                <td><?php echo htmlspecialchars($department !== '' ? $department : '-'); ?></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </div>

    <!-- ===== ATTENDANCE / LEAVE ===== -->
    <div class="ps-body-section">
      <table class="ps-attend-table">
        <tr>
          <td class="ps-attend-header" colspan="2">&nbsp;</td>
          <td class="ps-attend-header">&nbsp;</td>
          <td class="ps-attend-header">Leave</td>
          <td class="ps-attend-header">Bal Leave</td>
        </tr>
        <tr>
          <td style="width:30%;">Payment for Number of days</td>
          <td style="width:12%; text-align:right;"><?php echo number_format($paymentDays, 2); ?></td>
          <td style="width:26%;">Paid Leaves</td>
          <td style="width:16%; text-align:right;"><?php echo number_format($paidLeaves, 2); ?></td>
          <td style="width:16%; text-align:right;"><?php echo number_format($balLeaves, 2); ?></td>
        </tr>
        <tr>
          <td>Present Days</td>
          <td style="text-align:right;"><?php echo number_format($presentDays, 2); ?></td>
          <td>Leave Without Pay</td>
          <td style="text-align:right;"><?php echo number_format($lwp, 2); ?></td>
          <td>&nbsp;</td>
        </tr>
      </table>
    </div>

    <!-- ===== EARNINGS & DEDUCTIONS ===== -->
    <div class="ps-body-section">
      <table class="ps-ed-table">
        <tr>
          <td class="ps-ed-header" colspan="2">EARNINGS</td>
          <td class="ps-ed-header" colspan="2">DEDUCTION</td>
        </tr>
        <tr>
          <td class="ps-ed-label" style="width:25%;">BASIC [Rs.]</td>
          <td class="ps-ed-val" style="width:25%;"><?php echo number_format($basic, 2); ?></td>
          <td class="ps-ed-label" style="width:25%;">Professional Tax [Rs.]</td>
          <td class="ps-ed-val" style="width:25%;"><?php echo number_format($profTax, 2); ?></td>
        </tr>
        <tr>
          <td class="ps-ed-label">HRA [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($hra, 2); ?></td>
          <td class="ps-ed-label">TDS [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($tds, 2); ?></td>
        </tr>
        <tr>
          <td class="ps-ed-label">Conveyance Allowance [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($convAllow, 2); ?></td>
          <td class="ps-ed-label">Provident Fund [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($pf, 2); ?></td>
        </tr>
        <tr>
          <td class="ps-ed-label">Medical Allowance [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($medAllow, 2); ?></td>
          <td class="ps-ed-label">ESI [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($esi, 2); ?></td>
        </tr>
        <tr>
          <td class="ps-ed-label">Educational Allowance [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($eduAllow, 2); ?></td>
          <td class="ps-ed-label">Other Deductions [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($otherDed, 2); ?></td>
        </tr>
        <tr>
          <td class="ps-ed-label">Special Allowance [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($specAllow, 2); ?></td>
          <td class="ps-ed-label">&nbsp;</td>
          <td class="ps-ed-val">&nbsp;</td>
        </tr>
        <?php if ($otherAllow > 0): ?>
        <tr>
          <td class="ps-ed-label">Other Allowances [Rs.]</td>
          <td class="ps-ed-val"><?php echo number_format($otherAllow, 2); ?></td>
          <td class="ps-ed-label">&nbsp;</td>
          <td class="ps-ed-val">&nbsp;</td>
        </tr>
        <?php endif; ?>
        <tr class="ps-total-row">
          <td class="ps-total-earning">Total Earnings [Rs.]</td>
          <td class="ps-ed-val ps-total-earning"><?php echo number_format($gross, 2); ?></td>
          <td class="ps-total-deduction">Total Deductions [Rs.]</td>
          <td class="ps-ed-val ps-total-deduction"><?php echo number_format($totalDed, 2); ?></td>
        </tr>
      </table>
    </div>

  </div><!-- .ps-body -->

  <!-- ===== NET PAY BAR ===== -->
  <div class="ps-netpay">
    <span>NET PAY [Rs.]</span>
    <span><?php echo number_format($net, 2); ?></span>
  </div>

  <!-- ===== REMARKS (if any) ===== -->
  <?php if (!empty($row->remarks)): ?>
  <div class="ps-remarks">
    <strong>Remarks:</strong> <?php echo nl2br(htmlspecialchars($row->remarks)); ?>
  </div>
  <?php endif; ?>

  <!-- ===== EMPLOYEE ADDRESS (if any) ===== -->
  <?php if ($empAddress !== ''): ?>
  <div class="ps-remarks">
    <strong>Employee Address:</strong> <?php echo nl2br(htmlspecialchars($empAddress)); ?>
  </div>
  <?php endif; ?>

  <!-- ===== FOOTER NOTE ===== -->
  <div class="ps-footer-note">
    THIS IS A SYSTEM GENERATED PAY SLIP AND DOES NOT REQUIRE SIGNATURE
  </div>

</div><!-- .payslip-page -->

<?php $this->load->view('partials/footer'); ?>
