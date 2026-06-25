<?php $this->load->view('partials/header', array('title' => 'Training LMS — Admin')); ?>
<div class="container-fluid py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0 fw-bold">Training LMS (admin)</h1>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-dark btn-sm" href="<?php echo site_url('training-lms-admin/assignment-submissions'); ?>"><i class="bi bi-table me-1"></i>All submissions</a>
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('training-lms-admin/office-feed'); ?>"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Office CSV feeds</a>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('training-lms-admin/module/create'); ?>"><i class="bi bi-plus-lg me-1"></i>New module</a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>

  <div class="table-responsive card shadow-sm border-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Title</th>
          <th>Topics</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($modules)): ?>
          <tr><td colspan="4" class="text-center py-4 text-muted">No modules. Create one.</td></tr>
        <?php else: ?>
          <?php foreach ($modules as $m): ?>
            <tr>
              <td><strong><?php echo esc_view($m->title); ?></strong></td>
              <td><?php echo (int) $m->topic_count; ?></td>
              <td><span class="badge bg-<?php echo $m->status === 'active' ? 'success' : 'secondary'; ?>"><?php echo esc_view($m->status); ?></span></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('training-lms-admin/topics/' . (int) $m->id); ?>">Topics</a>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('training-lms-admin/module/edit/' . (int) $m->id); ?>">Edit</a>
                <?php echo form_open('training-lms-admin/module/delete/' . (int) $m->id, array('class' => 'd-inline', 'onsubmit' => 'return confirm(\'Delete this module and all topics?\');')); ?>
                <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                <?php echo form_close(); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <p class="small text-muted mt-3 mb-0"><a href="<?php echo site_url('training'); ?>">View learner catalog</a></p>
</div>
<?php $this->load->view('partials/footer'); ?>
