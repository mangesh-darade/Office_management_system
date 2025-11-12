<?php $this->load->view('partials/header', ['title' => 'Requirements']); ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Requirements</h1>
  <div class="d-flex gap-2">
    <a class="btn btn-primary btn-sm" href="<?php echo site_url('requirements/create'); ?>"><i class="bi bi-plus-lg"></i> New</a>
    <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('requirements/board'); ?>"><i class="bi bi-columns"></i> Board</a>
    <a class="btn btn-outline-dark btn-sm" href="<?php echo site_url('requirements/calendar'); ?>"><i class="bi bi-calendar3"></i> Calendar</a>
    <a class="btn btn-success btn-sm" href="<?php echo site_url('requirements/export'); ?>"><i class="bi bi-download"></i> Export</a>
  </div>
</div>

<div class="card shadow-soft mb-3">
  <div class="card-body">
    <form method="get" action="<?php echo site_url('requirements'); ?>" class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label">Status</label>
        <?php $fs = isset($filters['status']) ? (string)$filters['status'] : ''; ?>
        <select name="status" class="form-select">
          <?php $statuses = array('', 'received','under_review','approved','in_progress','completed','on_hold','rejected','cancelled');
          foreach ($statuses as $st): ?>
            <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($fs===$st)?'selected':''; ?>><?php echo $st===''?'All':ucfirst(str_replace('_',' ',$st)); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Priority</label>
        <?php $fp = isset($filters['priority']) ? (string)$filters['priority'] : ''; ?>
        <select name="priority" class="form-select">
          <?php $priorities = array('', 'low','medium','high','critical');
          foreach ($priorities as $pr): ?>
            <option value="<?php echo htmlspecialchars($pr); ?>" <?php echo ($fp===$pr)?'selected':''; ?>><?php echo $pr===''?'All':ucfirst($pr); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Client</label>
        <?php $fc = isset($filters['client_id']) ? (string)$filters['client_id'] : ''; ?>
        <select name="client_id" class="form-select">
          <option value="">All</option>
          <?php if (isset($clients) && is_array($clients)) foreach ($clients as $c): ?>
            <option value="<?php echo (int)$c->id; ?>" <?php echo ($fc===(string)$c->id)?'selected':''; ?>><?php echo htmlspecialchars($c->company_name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Assigned To</label>
        <?php $fa = isset($filters['assigned_to']) ? (string)$filters['assigned_to'] : ''; ?>
        <select name="assigned_to" class="form-select">
          <option value="">All</option>
          <?php if (isset($members) && is_array($members)) foreach ($members as $m): ?>
            <?php $label = '';
              if (isset($m->full_label) && $m->full_label!=='') { $label = $m->full_label; }
              else if (isset($m->full_name) && $m->full_name!=='') { $label = $m->full_name; }
              else if (isset($m->name) && $m->name!=='') { $label = $m->name; }
              else if (isset($m->email)) { $label = $m->email; }
            ?>
            <option value="<?php echo (int)$m->id; ?>" <?php echo ($fa===(string)$m->id)?'selected':''; ?>><?php echo htmlspecialchars($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Search</label>
        <input type="text" name="q" value="<?php echo htmlspecialchars(isset($filters['search'])?$filters['search']:''); ?>" class="form-control" placeholder="Req#, title">
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-primary w-100" type="submit">Filter</button>
      </div>
    </form>
  </div>
</div>

<div class="card shadow-soft">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Req#</th>
            <th>Client</th>
            <th>Title</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Expected</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted">No requirements found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
          <tr>
            <td><?php echo htmlspecialchars(isset($r->req_number)?$r->req_number:''); ?></td>
            <td><?php echo htmlspecialchars(isset($r->client_name)?$r->client_name:''); ?></td>
            <td><?php echo htmlspecialchars($r->title); ?></td>
            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars(isset($r->status)?$r->status:'received'); ?></span></td>
            <td><span class="badge bg-secondary"><?php echo htmlspecialchars(isset($r->priority)?$r->priority:'medium'); ?></span></td>
            <td><?php echo htmlspecialchars(isset($r->expected_delivery_date)?$r->expected_delivery_date:''); ?></td>
            <td class="text-end">
              <a class="btn btn-light btn-sm" href="<?php echo site_url('requirements/view/'.(int)$r->id); ?>"><i class="bi bi-eye"></i></a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php $this->load->view('partials/footer'); ?>
