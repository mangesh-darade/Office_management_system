<?php $this->load->view('partials/header', ['title' => 'Team Leave Calendar']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Team Leave Calendar</h1>
  <form method="get" class="d-flex align-items-center gap-2">
    <label class="form-label mb-0">Month</label>
    <input type="month" class="form-control" style="width: 200px" name="month" value="<?php echo htmlspecialchars($month); ?>" />
    <button class="btn btn-outline-secondary btn-sm">Go</button>
    <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('leave/team'); ?>">Back to List</a>
  </form>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <?php
      // Build day => events map for the selected month
      $first = $month.'-01';
      $last = date('Y-m-t', strtotime($first));
      $events = [];
      $d = $first;
      while (strtotime($d) <= strtotime($last)) { $events[$d] = []; $d = date('Y-m-d', strtotime($d.' +1 day')); }
      if (!empty($rows)){
        foreach ($rows as $r){
          $s = $r->start_date; $e = $r->end_date;
          $cur = (strtotime($s) > strtotime($first)) ? $s : $first;
          $stop = (strtotime($e) < strtotime($last)) ? $e : $last;
          while (strtotime($cur) <= strtotime($stop)){
            if (isset($events[$cur])){
              $events[$cur][] = $r;
            }
            $cur = date('Y-m-d', strtotime($cur.' +1 day'));
          }
        }
      }
    ?>
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead>
          <tr>
            <th style="width:120px">Date</th>
            <th>Leaves</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($events as $day => $list): ?>
            <tr>
              <td class="text-nowrap fw-semibold"><?php echo htmlspecialchars(date('D, d M Y', strtotime($day))); ?></td>
              <td>
                <?php if (empty($list)): ?>
                  <span class="text-muted">No leaves</span>
                <?php else: ?>
                  <ul class="mb-0">
                    <?php foreach ($list as $r): ?>
                      <li>
                        <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars(isset($r->type_name) ? $r->type_name : ''); ?></span>
                        <strong><?php echo htmlspecialchars(isset($r->user_email) ? $r->user_email : ''); ?></strong>
                        <span class="text-muted">(<?php echo htmlspecialchars($r->start_date.' to '.$r->end_date); ?>)</span>
                        <?php
                          $daysVal = isset($r->days) ? (float)$r->days : 0.0;
                          if ($daysVal > 0) {
                            $daysText = (fmod($daysVal, 1.0) === 0.0)
                              ? (string)(int)$daysVal
                              : rtrim(rtrim(number_format($daysVal, 2, '.', ''), '0'), '.');
                            if ($daysVal === 0.5) {
                              $daysText .= ' (Half Day)';
                            }
                            echo ' <span class="badge bg-secondary-subtle text-dark ms-1">'.htmlspecialchars($daysText.' d').'</span>';
                          }
                        ?>
                        <?php if (!empty($r->reason)): ?>
                          <span class="text-muted">— <?php echo htmlspecialchars($r->reason); ?></span>
                        <?php endif; ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php $this->load->view('partials/footer'); ?>
