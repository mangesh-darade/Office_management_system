<?php $this->load->view('partials/header', ['title' => 'Email Templates']); ?>
<style>
.template-editor { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); }
.template-editor .form-label { font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
.template-editor .form-control { border-radius: 8px; border: 1px solid #d1d5db; }
.template-editor .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.template-editor .btn { border-radius: 8px; font-weight: 500; }
.template-preview { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1.5rem; }
.variable-tag { background: #dbeafe; color: #1e40af; padding: 0.25rem 0.5rem; border-radius: 4px; font-family: monospace; font-size: 0.875rem; }
</style>

<div class="template-editor">
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0">📧 Email Templates</h1>
  <a class="btn btn-secondary btn-sm" href="<?php echo site_url('announcements'); ?>">
    <i class="bi bi-arrow-left me-1"></i>Back to Announcements
  </a>
</div>

<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="row">
  <div class="col-md-8">
    <div class="card">
      <div class="card-header bg-white">
        <h5 class="mb-0">📝 Edit Announcement Email Template</h5>
      </div>
      <div class="card-body">
        <form method="post" action="<?php echo site_url('announcements/save_template'); ?>">
          <div class="mb-3">
            <label class="form-label">Email Subject</label>
            <input type="text" class="form-control" name="subject" value="<?php echo htmlspecialchars($templates->subject ?? '📢 New Announcement: {title}'); ?>" required />
          </div>
          
          <div class="mb-3">
            <label class="form-label">Email Body</label>
            <textarea class="form-control" name="body" rows="12" required><?php echo htmlspecialchars($templates->body ?? 'Hello {name},

A new announcement has been published:

📌 {title}
Priority: {priority}
Published: {date}

{content}

---
This is an automated announcement from <?php echo get_company_name(); ?>.
If you have any questions, please contact your administrator.'); ?></textarea>
          </div>
          
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save me-2"></i>Save Template
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="resetTemplate()">
              <i class="bi bi-arrow-clockwise me-2"></i>Reset to Default
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <div class="col-md-4">
    <div class="card mb-3">
      <div class="card-header bg-white">
        <h6 class="mb-0">🏷️ Available Variables</h6>
      </div>
      <div class="card-body">
        <div class="mb-2">
          <span class="variable-tag">{title}</span>
          <small class="d-block text-muted">Announcement title</small>
        </div>
        <div class="mb-2">
          <span class="variable-tag">{content}</span>
          <small class="d-block text-muted">Announcement content</small>
        </div>
        <div class="mb-2">
          <span class="variable-tag">{date}</span>
          <small class="d-block text-muted">Publication date</small>
        </div>
        <div class="mb-2">
          <span class="variable-tag">{priority}</span>
          <small class="d-block text-muted">Priority level</small>
        </div>
        <div class="mb-2">
          <span class="variable-tag">{name}</span>
          <small class="d-block text-muted">Recipient name</small>
        </div>
      </div>
    </div>
    
    <div class="card">
      <div class="card-header bg-white">
        <h6 class="mb-0">👁️ Template Preview</h6>
      </div>
      <div class="card-body">
        <div class="template-preview">
          <div class="mb-3">
            <strong>Subject:</strong> <span id="preview-subject">📢 New Announcement: Sample Title</span>
          </div>
          <div class="mb-2">
            <strong>Body:</strong>
          </div>
          <div id="preview-body" style="white-space: pre-wrap; font-size: 0.875rem;">
Hello John Doe,

A new announcement has been published:

📌 Sample Title
Priority: High
Published: 2025-11-26

This is a sample announcement content.

---
This is an automated announcement from <?php echo get_company_name(); ?>.
If you have any questions, please contact your administrator.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Live preview
const subjectInput = document.querySelector('input[name="subject"]');
const bodyTextarea = document.querySelector('textarea[name="body"]');
const previewSubject = document.getElementById('preview-subject');
const previewBody = document.getElementById('preview-body');

function updatePreview() {
    const sampleData = {
        title: 'Sample Title',
        content: 'This is a sample announcement content.',
        date: '2025-11-26',
        priority: 'High',
        name: 'John Doe'
    };
    
    let subject = subjectInput.value || '📢 New Announcement: {title}';
    let body = bodyTextarea.value || 'Hello {name},\n\nA new announcement has been published:\n\n📌 {title}\nPriority: {priority}\nPublished: {date}\n\n{content}\n\n---\nThis is an automated announcement from <?php echo get_company_name(); ?>.\nIf you have any questions, please contact your administrator.';
    
    // Replace variables
    Object.keys(sampleData).forEach(key => {
        const regex = new RegExp('\\{' + key + '\\}', 'g');
        subject = subject.replace(regex, sampleData[key]);
        body = body.replace(regex, sampleData[key]);
    });
    
    previewSubject.textContent = subject;
    previewBody.textContent = body;
}

if (subjectInput && bodyTextarea) {
    subjectInput.addEventListener('input', updatePreview);
    bodyTextarea.addEventListener('input', updatePreview);
    updatePreview(); // Initial preview
}

function resetTemplate() {
    if (confirm('Reset template to default values? This will discard your current changes.')) {
        subjectInput.value = '📢 New Announcement: {title}';
        bodyTextarea.value = 'Hello {name},\n\nA new announcement has been published:\n\n📌 {title}\nPriority: {priority}\nPublished: {date}\n\n{content}\n\n---\nThis is an automated announcement from <?php echo get_company_name(); ?>.\nIf you have any questions, please contact your administrator.';
        updatePreview();
    }
}
</script>

<?php $this->load->view('partials/footer'); ?>
