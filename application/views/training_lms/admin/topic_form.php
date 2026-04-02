<?php
$ed = isset($topic) && $topic;
$this->load->view('partials/header', array('title' => $ed ? 'Edit topic' : 'New topic'));
$hasAssessments = !empty($assessments);
?>
<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb small mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-lms-admin'); ?>">LMS Admin</a></li>
      <li class="breadcrumb-item"><a href="<?php echo site_url('training-lms-admin/topics/' . (int) $module->id); ?>"><?php echo htmlspecialchars($module->title); ?></a></li>
      <li class="breadcrumb-item active"><?php echo $ed ? 'Edit topic' : 'New topic'; ?></li>
    </ol>
  </nav>
  <h1 class="h4 mb-3"><?php echo $ed ? 'Edit topic' : 'New topic'; ?></h1>

  <?php echo form_open('training-lms-admin/save-topic'); ?>
  <input type="hidden" name="<?php echo $this->security->get_csrf_token_name(); ?>" value="<?php echo $this->security->get_csrf_hash(); ?>">
  <input type="hidden" name="module_id" value="<?php echo (int) $module->id; ?>">
  <?php if ($ed): ?><input type="hidden" name="topic_id" value="<?php echo (int) $topic->id; ?>"><?php endif; ?>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white fw-semibold">Topic</div>
    <div class="card-body">
      <div class="mb-3">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required maxlength="255" value="<?php echo $ed ? htmlspecialchars($topic->name) : ''; ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="3"><?php echo $ed ? htmlspecialchars($topic->description) : ''; ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Prerequisites</label>
        <textarea name="prerequisites" class="form-control" rows="2"><?php echo $ed ? htmlspecialchars($topic->prerequisites) : ''; ?></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Enforced prerequisite topic</label>
        <select name="prerequisite_topic_id" class="form-select">
          <option value="0">— None —</option>
          <?php foreach ($sibling_topics as $st): ?>
            <?php if ($ed && (int) $st->id === (int) $topic->id) { continue; } ?>
            <option value="<?php echo (int) $st->id; ?>" <?php echo ($ed && isset($topic->prerequisite_topic_id) && (int) $topic->prerequisite_topic_id === (int) $st->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($st->name); ?></option>
          <?php endforeach; ?>
        </select>
        <p class="small text-muted mb-0">Learners must mark the prerequisite topic complete before opening this topic.</p>
      </div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Duration (hours)</label>
          <input type="number" name="duration_hours" class="form-control" step="0.1" min="0" value="<?php echo $ed ? htmlspecialchars($topic->duration_hours) : '0'; ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Sort order</label>
          <input type="number" name="sort_order" class="form-control" value="<?php echo $ed ? (int) $topic->sort_order : 0; ?>">
        </div>
      </div>
      <div class="row g-3 mt-1">
        <div class="col-md-6">
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="has_assignment" value="1" id="ha" <?php echo ($ed && (int) $topic->has_assignment) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="ha">Has file assignment</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="has_assessment" value="1" id="hs" <?php echo ($ed && (int) $topic->has_assessment) ? 'checked' : ''; ?>>
            <label class="form-check-label" for="hs">Link assessment (Training &amp; Assessment module)</label>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Assessment</label>
          <select name="assessment_id" class="form-select" <?php echo $hasAssessments ? '' : 'disabled'; ?>>
            <option value="0">— Select —</option>
            <?php foreach ($assessments as $a): ?>
              <option value="<?php echo (int) $a->id; ?>" <?php echo ($ed && (int) $topic->assessment_id === (int) $a->id) ? 'selected' : ''; ?>><?php echo htmlspecialchars($a->title); ?></option>
            <?php endforeach; ?>
          </select>
          <?php if (!$hasAssessments): ?>
            <p class="small text-muted mb-0">Create assessments under Training &amp; Assessment first.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-header bg-white fw-semibold">Assignment (if enabled)</div>
    <div class="card-body">
      <div class="mb-2">
        <label class="form-label">Assignment title</label>
        <input type="text" name="assignment_name" class="form-control" maxlength="255" value="<?php echo ($ed && $assignment) ? htmlspecialchars($assignment->name) : ''; ?>" placeholder="e.g. Week 1 reflection">
      </div>
      <div class="mb-3">
        <label class="form-label">Instructions / details</label>
        <textarea name="assignment_details" class="form-control" rows="4" placeholder="What learners should submit"><?php echo ($ed && $assignment) ? htmlspecialchars($assignment->details) : ''; ?></textarea>
      </div>
      <div class="mb-0">
        <label class="form-label">Max submissions per user</label>
        <input type="number" name="max_submissions" class="form-control" min="0" value="<?php echo ($ed && $assignment && isset($assignment->max_submissions)) ? (int) $assignment->max_submissions : 0; ?>">
        <p class="small text-muted mb-0">0 = unlimited uploads.</p>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Save topic</button>
  <a href="<?php echo site_url('training-lms-admin/topics/' . (int) $module->id); ?>" class="btn btn-outline-secondary">Cancel</a>
  <?php echo form_close(); ?>
</div>
<?php $this->load->view('partials/footer'); ?>
