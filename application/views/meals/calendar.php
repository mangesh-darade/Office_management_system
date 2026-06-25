<?php $this->load->view('partials/header', ['title' => 'Meal Calendar', 'active' => 'meals']); ?>
<div class="container-fluid py-3 oms-fluid-pad">
<?php
$prev = date('Y-m', strtotime($from . ' -1 month'));
$next = date('Y-m', strtotime($from . ' +1 month'));
$this->load->view('partials/oms_page_head', [
  'title' => 'Meal Calendar',
  'icon' => 'bi-calendar3',
  'subtitle' => 'Override specific dates — or use Settings → Apply weekly menu',
]);
$this->load->view('meals/_nav', ['active_sub' => 'calendar']);
?>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('meals/calendar?month='.$prev); ?>">&larr; <?php echo date('M Y', strtotime($prev.'-01')); ?></a>
  <h2 class="h5 mb-0 fw-bold"><?php echo date('F Y', strtotime($from)); ?></h2>
  <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('meals/calendar?month='.$next); ?>"><?php echo date('M Y', strtotime($next.'-01')); ?> &rarr;</a>
</div>

<form method="post">
<?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
<div class="table-responsive">
<table class="table table-bordered table-sm align-middle bg-white shadow-soft">
<thead class="table-light"><tr>
  <th>Date</th><th>Breakfast</th><th>Menu note</th><th>Lunch</th><th>Menu note</th>
</tr></thead>
<tbody>
<?php
$c = strtotime($from);
$end = strtotime($to);
while ($c <= $end):
  $d = date('Y-m-d', $c);
  $row = isset($cal_map[$d]) ? $cal_map[$d] : null;
  $hasBf = $row ? (int)$row->has_breakfast : 0;
  $hasLu = $row ? (int)$row->has_lunch : 0;
  $dow = (int) date('N', $c);
  $isWeekend = ($dow >= 6);
?>
<tr class="<?php echo $isWeekend ? 'table-secondary' : ''; ?>">
  <td class="fw-semibold"><?php echo date('D, d M', $c); ?></td>
  <td class="text-center"><input type="checkbox" name="days[<?php echo $d; ?>][has_breakfast]" value="1" <?php echo $hasBf ? 'checked' : ''; ?>></td>
  <td><input type="text" class="form-control form-control-sm" name="days[<?php echo $d; ?>][breakfast_note]" value="<?php echo $row ? esc_view((string)$row->breakfast_note) : ''; ?>" placeholder="e.g. Poha, Tea"></td>
  <td class="text-center"><input type="checkbox" name="days[<?php echo $d; ?>][has_lunch]" value="1" <?php echo $hasLu ? 'checked' : ''; ?>></td>
  <td><input type="text" class="form-control form-control-sm" name="days[<?php echo $d; ?>][lunch_note]" value="<?php echo $row ? esc_view((string)$row->lunch_note) : ''; ?>" placeholder="e.g. Dal, Rice"></td>
</tr>
<?php $c = strtotime('+1 day', $c); endwhile; ?>
</tbody>
</table>
</div>
<p class="text-muted small">Saving updates company announcements for each date automatically.</p>
<button type="submit" class="btn btn-primary">Save calendar</button>
</form>
</div>
<?php $this->load->view('partials/footer'); ?>
