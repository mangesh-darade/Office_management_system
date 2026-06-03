<?php $this->load->view('partials/header', ['title' => 'Session']); ?>
<?php $this->load->view('coaching/_subnav'); ?>
<div class="card shadow-soft"><div class="card-body">
<?php echo form_open($row ? 'coaching-sessions/edit/'.$row->id : 'coaching-sessions/create'); ?>
<div class="mb-3"><label class="form-label">Client</label><select name="coaching_client_id" class="form-select" required><?php foreach ($clients as $c): ?><option value="<?php echo (int)$c->id; ?>" <?php echo ($row && (int)$row->coaching_client_id===(int)$c->id)?'selected':''; ?>><?php echo htmlspecialchars($c->full_name); ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label">Coach</label><select name="coach_id" class="form-select" required><?php foreach ($coaches as $c): ?><option value="<?php echo (int)$c->id; ?>" <?php echo ($row && (int)$row->coach_id===(int)$c->id)?'selected':''; ?>><?php echo htmlspecialchars($c->name); ?></option><?php endforeach; ?></select></div>
<div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" value="<?php echo $row ? htmlspecialchars($row->title) : 'Review Session'; ?>"></div>
<div class="mb-3"><label class="form-label">Scheduled at</label><input type="datetime-local" name="scheduled_at" class="form-control" required value="<?php echo $row ? date('Y-m-d\TH:i', strtotime($row->scheduled_at)) : ''; ?>"></div>
<div class="mb-3"><label class="form-label">Duration (min)</label><input type="number" name="duration_minutes" class="form-control" value="<?php echo $row ? (int)$row->duration_minutes : 60; ?>"></div>
<div class="mb-3"><label class="form-label">Meeting link</label><input name="meeting_link" class="form-control" value="<?php echo $row ? htmlspecialchars($row->meeting_link) : ''; ?>"></div>
<div class="mb-3"><label class="form-label">Notes (client visible)</label><textarea name="notes_client" class="form-control" rows="2"><?php echo $row ? htmlspecialchars($row->notes_client) : ''; ?></textarea></div>
<div class="mb-3"><label class="form-label">Internal notes</label><textarea name="notes_internal" class="form-control" rows="2"><?php echo $row ? htmlspecialchars($row->notes_internal) : ''; ?></textarea></div>
<div class="mb-3"><label class="form-label">Homework summary</label><textarea name="homework_summary" class="form-control" rows="2"><?php echo $row ? htmlspecialchars($row->homework_summary) : ''; ?></textarea></div>
<div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (['scheduled','completed','cancelled','no_show'] as $st): ?><option <?php echo ($row && $row->status===$st)?'selected':''; ?>><?php echo $st; ?></option><?php endforeach; ?></select></div>
<button type="submit" class="btn btn-primary">Save</button>
<?php echo form_close(); ?>
</div></div>
<?php $this->load->view('partials/footer'); ?>
