<?php $this->load->view('partials/header', ['title' => 'Payslip']); ?>
<?php
  // Employee basics
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

  // Pay / bank meta
  $payMode = isset($row->pay_mode) ? (string)$row->pay_mode : '';
  $bankName = isset($row->bank_name) ? (string)$row->bank_name : '';
  $bankAcNo = isset($row->bank_ac_no) ? (string)$row->bank_ac_no : '';
  $panNo = isset($row->pan_no) ? (string)$row->pan_no : '';
  $location = isset($row->location) ? (string)$row->location : '';

  // Employee address (multi-line)
  $addrLines = [];
  $addrMain = isset($row->address) ? trim((string)$row->address) : '';
  if ($addrMain !== '') {
    $addrLines[] = $addrMain;
  }
  $line2 = [];
  if (!empty($row->city))    { $line2[] = (string)$row->city; }
  if (!empty($row->state))   { $line2[] = (string)$row->state; }
  if (!empty($row->zipcode)) { $line2[] = (string)$row->zipcode; }
  if (!empty($line2)) {
    $addrLines[] = implode(', ', $line2);
  }
  if (!empty($row->country)) {
    $addrLines[] = (string)$row->country;
  }
  $empAddress = trim(implode("\n", $addrLines));

  // Company info from settings
  $companyName = isset($settings['company_name']) && $settings['company_name'] !== ''
    ? $settings['company_name'] : 'Office Management System';
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

  // Attendance / leave summary
  $paymentDays = isset($row->payment_days) ? (float)$row->payment_days : 0.0;
  $presentDays = isset($row->present_days) ? (float)$row->present_days : 0.0;
  $paidLeaves  = isset($row->paid_leaves) ? (float)$row->paid_leaves : 0.0;
  $lwp         = isset($row->leave_without_pay) ? (float)$row->leave_without_pay : 0.0;
  $balLeaves   = isset($row->balance_leaves) ? (float)$row->balance_leaves : 0.0;
?>

<style>
  .payslip-wrapper { max-width: 900px; margin: 0 auto 1.5rem auto; background: #fff; border: 2px solid #000; padding: 12px 16px; font-size: 12px; }
  .payslip-header-simple { width: 100%; border-collapse:collapse; }
  .payslip-header-simple td { vertical-align: top; }
  .payslip-company-name { font-weight: 600; font-size: 14px; color:#1f2a44; }
  .payslip-company-address { font-size: 11px; }
  .payslip-topbar { background:#1f2a44; color:#fff; padding:6px 10px; border-bottom:2px solid #000; }
  .payslip-topbar .payslip-header-simple { border:0; margin:0; width:100%; }
  .payslip-topbar .payslip-header-simple td { vertical-align:middle; }
  .payslip-address-box { background:#fff; color:#000; padding:4px 8px; font-size:11px; display:inline-block; text-align:left; min-width:220px; }
  .payslip-address-title { font-weight:600; margin-bottom:2px; }
  .payslip-logo-text { font-weight:600; font-size:16px; color:#fff; }
  .payslip-meta-title { text-align:center; font-weight:600; margin: 4px 0 6px 0; font-size: 13px; background:#e6e6e6; border-top:1px solid #000; border-bottom:1px solid #000; padding:4px 0; }
  .payslip-bordered-table { width:100%; border-collapse:collapse; }
  .payslip-bordered-table th,
  .payslip-bordered-table td { border:1px solid #555; padding:2px 4px; }
  .payslip-compact td,
  .payslip-compact th { padding:2px 4px; }
  .payslip-section-title { background:#e6e6e6; font-weight:600; text-align:center; }
  .payslip-totals-row { font-weight:600; background:#ffecec; }
  .payslip-totals-row td { border-top:1px solid #c00; border-bottom:1px solid #c00; }
  .payslip-total-earnings { color:#c00; }
  .payslip-total-deductions { color:#c00; }
  .payslip-netpay-box { margin-top:8px; border:2px solid #000; padding:6px 8px; font-weight:700; font-size:13px; background:#e6e6e6; }
  .payslip-netpay-label { text-transform:uppercase; }
  .payslip-note { font-size:10px; text-align:center; margin-top:6px; color:#555; }
  @media print {
    .no-print { display:none !important; }
    body { background:#fff; }
  }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <h1 class="h4 mb-0">Payslip</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('payroll/payslips'); ?>">Back</a>
    <button class="btn btn-outline-primary btn-sm" type="button" onclick="window.print();">Print</button>
  </div>
</div>

<div class="payslip-wrapper">
  <div class="payslip-topbar">
    <table class="payslip-header-simple">
      <tr>
        <td style="width:50%;">
          <?php if ($companyLogo): ?>
            <img src="<?php echo htmlspecialchars($companyLogo); ?>" alt="Logo" style="height:42px;" />
          <?php elseif ($companyName !== ''): ?>
            <span class="payslip-logo-text"><?php echo htmlspecialchars($companyName); ?></span>
          <?php endif; ?>
        </td>
        <td style="width:50%; text-align:right;">
          <div class="payslip-address-box">
            <div class="payslip-address-title">Address:</div>
            <?php if ($companyAddress !== ''): ?>
              <div class="payslip-company-address"><?php echo nl2br(htmlspecialchars($companyAddress)); ?></div>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    </table>
  </div>

  <div class="payslip-meta-title">PAY SLIP FOR THE MONTH OF&nbsp;<?php echo htmlspecialchars($formattedPeriod); ?></div>

  <table class="payslip-bordered-table payslip-compact mb-2" style="width:100%;">
    <tr>
      <td style="width:50%;">
        <table class="payslip-bordered-table payslip-compact" style="width:100%;">
          <tr>
            <th style="width:40%;">Name</th>
            <td style="width:60%;"><?php echo htmlspecialchars($empName); ?></td>
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
      <td style="width:50%;">
        <table class="payslip-bordered-table payslip-compact" style="width:100%;">
          <tr>
            <th style="width:40%;">Pay Mode</th>
            <td style="width:60%;"><?php echo htmlspecialchars($payMode !== '' ? $payMode : '-'); ?></td>
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
        </table>
      </td>
    </tr>
  </table>

  <table class="payslip-bordered-table payslip-compact mb-2" style="width:100%;">
    <tr class="payslip-section-title">
      <th style="width:30%;">&nbsp;</th>
      <th style="width:10%;">&nbsp;</th>
      <th style="width:20%;">&nbsp;</th>
      <th style="width:20%;">Leave</th>
      <th style="width:20%;">Bal Leave</th>
    </tr>
    <tr>
      <td>Payment for Number of days</td>
      <td class="text-end"><?php echo $paymentDays>0?number_format($paymentDays,2):'0.00'; ?></td>
      <td>Paid Leaves</td>
      <td class="text-end"><?php echo $paidLeaves>0?number_format($paidLeaves,2):'0.00'; ?></td>
      <td class="text-end"><?php echo $balLeaves>0?number_format($balLeaves,2):'0.00'; ?></td>
    </tr>
    <tr>
      <td>Present Days</td>
      <td class="text-end"><?php echo $presentDays>0?number_format($presentDays,2):'0.00'; ?></td>
      <td>Leave Without Pay</td>
      <td class="text-end"><?php echo $lwp>0?number_format($lwp,2):'0.00'; ?></td>
      <td class="text-end">&nbsp;</td>
    </tr>
  </table>

  <div class="row mt-1">
    <div class="col-md-12">
      <table class="payslip-bordered-table payslip-compact" style="width:100%;">
        <tr class="payslip-section-title">
          <th colspan="2">EARNINGS</th>
          <th colspan="2">DEDUCTION</th>
        </tr>
        <tr>
          <td style="width:25%;">BASIC [Rs.]</td>
          <td style="width:25%;" class="text-end"><?php echo number_format((float)$row->basic,2); ?></td>
          <td style="width:25%;">Professional Tax [Rs.]</td>
          <td style="width:25%;" class="text-end"><?php echo number_format((float)$row->professional_tax,2); ?></td>
        </tr>
        <tr>
          <td>HRA [Rs.]</td>
          <td class="text-end"><?php echo number_format((float)$row->hra,2); ?></td>
          <td>TDS [Rs.]</td>
          <td class="text-end"><?php echo number_format((float)$row->tds,2); ?></td>
        </tr>
        <tr>
          <td>Conveyance Allowance [Rs.]</td>
          <td class="text-end"><?php echo number_format((float)$row->conveyance_allow,2); ?></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td>Medical Allowance [Rs.]</td>
          <td class="text-end"><?php echo number_format((float)$row->medical_allow,2); ?></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td>Educational Allowance [Rs.]</td>
          <td class="text-end"><?php echo number_format((float)$row->education_allow,2); ?></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td>Special Allowance [Rs.]</td>
          <td class="text-end"><?php echo number_format((float)$row->special_allow,2); ?></td>
          <td></td>
          <td></td>
        </tr>
        <tr class="payslip-totals-row">
          <td class="payslip-total-earnings"><strong>Total Earnings [Rs.]</strong></td>
          <td class="text-end payslip-total-earnings"><strong><?php echo number_format((float)$row->gross,2); ?></strong></td>
          <td class="payslip-total-deductions"><strong>Total Deductions [Rs.]</strong></td>
          <td class="text-end payslip-total-deductions"><strong><?php echo number_format((float)$row->deductions,2); ?></strong></td>
        </tr>
      </table>
    </div>
  </div>

  <?php if (!empty($row->remarks)): ?>
  <div class="mt-2">
    <table class="payslip-bordered-table" style="width:100%;">
      <tr>
        <th style="width:20%;">Remarks</th>
        <td><?php echo nl2br(htmlspecialchars($row->remarks)); ?></td>
      </tr>
    </table>
  </div>
  <?php endif; ?>

  <div class="payslip-netpay-box">
    <span class="payslip-netpay-label">Net Pay [Rs.]</span>
    <span class="float-end"><?php echo number_format((float)$row->net,2); ?></span>
    <div style="clear:both;"></div>
  </div>

  <?php if ($empAddress !== ''): ?>
  <div class="payslip-note mt-2">
    <strong>Employee Address:</strong><br>
    <?php echo nl2br(htmlspecialchars($empAddress)); ?>
  </div>
  <?php endif; ?>

  <div class="payslip-note mt-2">
    THIS IS A SYSTEM GENERATED PAY SLIP AND DOES NOT REQUIRE SIGNATURE.
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
