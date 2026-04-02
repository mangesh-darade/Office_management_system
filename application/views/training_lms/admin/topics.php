<?php $this->load->view('partials/header', array('title' => 'Topics — ' . $module->title)); ?>
<div class="container-fluid py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-lms-admin'); ?>">LMS Admin</a></li>
      <li class="breadcrumb-item active"><?php echo htmlspecialchars($module->title); ?></li>
    </ol>
  </nav>
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Topics</h1>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?php echo site_url('training-lms-admin/enrollments/' . (int) $module->id); ?>"><i class="bi bi-people me-1"></i>Enrollments<?php if (!empty($enrollment_count)): ?><span class="badge bg-secondary ms-1"><?php echo (int) $enrollment_count; ?></span><?php endif; ?></a>
      <a class="btn btn-primary btn-sm" href="<?php echo site_url('training-lms-admin/topic/create/' . (int) $module->id); ?>"><i class="bi bi-plus-lg me-1"></i>Add topic</a>
    </div>
  </div>

  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <div class="table-responsive card shadow-sm border-0">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Name</th>
          <th>Hours</th>
          <th>Assignment</th>
          <th>Assessment</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($topics)): ?>
          <tr><td colspan="5" class="text-center py-4 text-muted">No topics.</td></tr>
        <?php else: ?>
          <?php foreach ($topics as $t): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($t->name); ?></strong></td>
              <td><?php echo htmlspecialchars(number_format((float) $t->duration_hours, 1)); ?></td>
              <td><?php echo (int) $t->has_assignment ? 'Yes' : '—'; ?></td>
              <td><?php echo (int) $t->has_assessment ? 'Yes' : '—'; ?></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('training/topic/' . (int) $t->id); ?>" target="_blank" rel="noopener">View</a>
                <?php if ((int) $t->has_assignment): ?>
                  <a class="btn btn-sm btn-outline-info" href="<?php echo site_url('training-lms-admin/submissions/' . (int) $t->id); ?>">Submissions</a>
                <?php endif; ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?php echo site_url('training-lms-admin/topic/edit/' . (int) $module->id . '/' . (int) $t->id); ?>">Edit</a>
                <?php echo form_open('training-lms-admin/topic/delete/' . (int) $t->id, array('class' => 'd-inline', 'onsubmit' => 'return confirm(\'Delete this topic?\');')); ?>
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
</div>
<?php $this->load->view('partials/footer'); ?>
