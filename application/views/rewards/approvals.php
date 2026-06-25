<?php $this->load->view('partials/header', ['title' => 'Reward Approvals']); ?>
<div class="container-fluid py-3">
<h1 class="h4 fw-bold mb-3">Pending reward approvals</h1>
<?php if ($this->session->flashdata('success')): ?><div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div><?php endif; ?>
<?php if ($this->session->flashdata('error')): ?><div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div><?php endif; ?>
<div class="card shadow-soft"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Submitted</th><th>Recipient</th><th>Activity</th><th>Points</th><th>By</th><th></th></tr></thead><tbody>
<?php if (empty($rows)): ?><tr><td colspan="6" class="text-muted text-center">No pending claims.</td></tr><?php else: foreach ($rows as $r): ?>
<tr>
  <td><?php echo esc_view($r->submitted_at); ?></td>
  <td><?php echo esc_view($r->recipient_name); ?></td>
  <td><?php echo esc_view($r->rule_name ?: $r->rule_code); ?></td>
  <td class="<?php echo ((float)$r->requested_points>=0)?'text-success':'text-danger'; ?>"><?php echo ((float)$r->requested_points>=0?'+':'').number_format((float)$r->requested_points,0); ?></td>
  <td><?php echo esc_view($r->submitter_name); ?></td>
  <td class="text-nowrap">
    <form method="post" action="<?php echo site_url('rewards/approve-claim/'.$r->id); ?>" class="d-inline"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?><button type="submit" class="btn btn-sm btn-success">Approve</button></form>
    <form method="post" action="<?php echo site_url('rewards/reject-claim/'.$r->id); ?>" class="d-inline ms-1" onsubmit="return confirm('Reject this claim?');"><?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?><button type="submit" class="btn btn-sm btn-outline-danger">Reject</button></form>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div></div></div>
<?php $this->load->view('partials/footer'); ?>
