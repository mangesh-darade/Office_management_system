<?php
$back_url = site_url('requirements/view/' . (int) $row->id);
$this->load->view('partials/header', array(
    'title' => 'Edit Requirement',
    'extra_css' => array('assets/css/defects-form.css'),
));
?>
<div class="oms-form-compact defect-form-page">
<div class="container-fluid py-2 py-md-3 px-2 px-md-3">

  <nav aria-label="breadcrumb" class="defect-form-crumb small mb-2 d-none d-md-block">
    <ol class="breadcrumb mb-0">
      <li class="breadcrumb-item"><a href="<?php echo site_url('requirements'); ?>">Requirements</a></li>
      <li class="breadcrumb-item active" aria-current="page">Edit</li>
    </ol>
  </nav>

  <div class="defect-form-hero oms-form-page-head d-flex align-items-center gap-2 gap-md-3 mb-3">
    <a href="<?php echo esc_view($back_url); ?>" class="btn btn-light border defect-form-back oms-form-back" title="Back">
      <i class="bi bi-arrow-left"></i><span class="d-none d-sm-inline ms-1">Back</span>
    </a>
    <div class="defect-form-hero-icon d-none d-sm-flex" aria-hidden="true">
      <i class="bi bi-clipboard-check"></i>
    </div>
    <div class="oms-form-page-titles min-w-0 flex-grow-1">
      <h1 class="defect-form-title mb-0">Edit Requirement</h1>
      <p class="defect-form-sub text-muted mb-0 d-none d-md-block">Update details on the left, description on the right.</p>
    </div>
  </div>

  <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger border-0 shadow-sm"><?php echo esc_view($this->session->flashdata('error')); ?></div>
  <?php endif; ?>
  <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success border-0 shadow-sm"><?php echo esc_view($this->session->flashdata('success')); ?></div>
  <?php endif; ?>

  <form method="post" action="" class="defect-form" data-validate="true" id="reqEditForm">
    <div class="row g-3 defect-form-split">
      <div class="col-12 col-md-4 defect-form-meta">
        <aside class="defect-form-panel defect-form-panel--meta h-100">
          <header class="defect-form-panel-head">
            <span class="defect-form-panel-icon"><i class="bi bi-sliders"></i></span>
            <div>
              <h2 class="defect-form-panel-title">Details</h2>
              <p class="defect-form-panel-hint mb-0">Client, status &amp; assignment</p>
            </div>
          </header>

          <div class="defect-form-group">
            <div class="defect-form-group-label">Context</div>
            <div class="vstack gap-2">
              <div class="defect-field">
                <label class="form-label">Client</label>
                <select name="client_id" class="form-select">
                  <option value="" <?php echo empty($row->client_id) ? 'selected' : ''; ?>>-- Optional --</option>
                  <?php if (isset($clients) && is_array($clients)) foreach ($clients as $c): ?>
                    <option value="<?php echo (int) $c->id; ?>" <?php echo ((int) $row->client_id === (int) $c->id) ? 'selected' : ''; ?>><?php echo esc_view($c->company_name); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label">Project</label>
                <select name="project_id" class="form-select">
                  <option value="">-- None --</option>
                  <?php if (isset($projects) && is_array($projects)) foreach ($projects as $p): ?>
                    <option value="<?php echo (int) $p->id; ?>" <?php echo ((int) $row->project_id === (int) $p->id) ? 'selected' : ''; ?>><?php echo esc_view($p->name); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label">Type</label>
                <?php $this->load->view('partials/module_type_select', array(
                    'field_name' => 'requirement_type',
                    'options' => isset($requirement_types) ? $requirement_types : array(),
                    'current' => isset($row->requirement_type) ? (string) $row->requirement_type : 'new_feature',
                    'required' => true,
                    'placeholder' => '— Select type —',
                )); ?>
              </div>
            </div>
          </div>

          <div class="defect-form-group">
            <div class="defect-form-group-label">Status</div>
            <div class="vstack gap-2">
              <div class="defect-field">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-select">
                  <?php $priorities = array('low', 'medium', 'high', 'critical'); foreach ($priorities as $pr): ?>
                    <option value="<?php echo esc_view($pr); ?>" <?php echo ($row->priority === $pr) ? 'selected' : ''; ?>><?php echo ucfirst($pr); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <?php
                  if (isset($statuses) && is_array($statuses) && !empty($statuses)):
                      foreach ($statuses as $st):
                          $selected = ($row->status === $st->code) ? 'selected' : '';
                  ?>
                    <option value="<?php echo esc_view($st->code); ?>" <?php echo $selected; ?>><?php echo esc_view($st->name); ?></option>
                  <?php
                      endforeach;
                  else:
                      $fallback_statuses = array('received', 'under_review', 'approved', 'in_progress', 'completed', 'on_hold', 'rejected', 'cancelled');
                      foreach ($fallback_statuses as $st):
                          $selected = ($row->status === $st) ? 'selected' : '';
                  ?>
                    <option value="<?php echo esc_view($st); ?>" <?php echo $selected; ?>><?php echo ucfirst(str_replace('_', ' ', $st)); ?></option>
                  <?php
                      endforeach;
                  endif;
                  ?>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label">Received Date</label>
                <input type="date" name="received_date" id="received_date" class="form-control" value="<?php echo esc_view(isset($row->received_date) ? $row->received_date : ''); ?>">
              </div>
              <div class="defect-field">
                <label class="form-label">Expected Delivery</label>
                <input type="date" name="expected_delivery_date" id="expected_delivery_date" class="form-control" value="<?php echo esc_view(isset($row->expected_delivery_date) ? $row->expected_delivery_date : ''); ?>">
                <div class="form-text text-danger" id="date-error" style="display: none;">
                  <small>Expected delivery date must be on or after received date</small>
                </div>
              </div>
            </div>
          </div>

          <div class="defect-form-group">
            <div class="defect-form-group-label">People</div>
            <div class="vstack gap-2">
              <div class="defect-field">
                <label class="form-label">Owner <span class="text-danger">*</span></label>
                <select name="owner_id" class="form-select" required>
                  <option value="">-- Select Owner --</option>
                  <?php if (isset($members) && is_array($members)) foreach ($members as $m): ?>
                    <?php
                      $label = '';
                      if (isset($m->full_label) && $m->full_label !== '') { $label = $m->full_label; }
                      elseif (isset($m->full_name) && $m->full_name !== '') { $label = $m->full_name; }
                      elseif (isset($m->name) && $m->name !== '') { $label = $m->name; }
                      elseif (isset($m->email)) { $label = $m->email; }
                    ?>
                    <option value="<?php echo (int) $m->id; ?>" <?php echo ((int) $row->owner_id === (int) $m->id) ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="defect-field">
                <label class="form-label">Assigned To</label>
                <?php
                  $curAssignedIds = array();
                  if (isset($assigned_user_ids) && is_array($assigned_user_ids)) {
                      $curAssignedIds = array_map('intval', $assigned_user_ids);
                  } elseif (isset($row) && isset($row->assigned_to) && $row->assigned_to !== null) {
                      $curAssignedIds = array((int) $row->assigned_to);
                  }
                ?>
                <select name="assigned_to[]" id="req-assigned-to-select" class="form-select oms-select2-multi" multiple style="width: 100%;">
                  <?php
                  if (isset($members) && is_array($members)) foreach ($members as $m):
                      $label = '';
                      if (isset($m->full_label) && $m->full_label !== '') { $label = $m->full_label; }
                      elseif (isset($m->full_name) && $m->full_name !== '') { $label = $m->full_name; }
                      elseif (isset($m->name) && $m->name !== '') { $label = $m->name; }
                      elseif (isset($m->email)) { $label = $m->email; }
                  ?>
                    <option value="<?php echo (int) $m->id; ?>" <?php echo in_array((int) $m->id, $curAssignedIds, true) ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
                  <?php endforeach; ?>
                </select>
                <div class="form-text">Type to search. Click × on a tag to remove that user. First selected is primary.</div>
              </div>
            </div>
          </div>
        </aside>
      </div>

      <div class="col-12 col-md-8 defect-form-main">
        <section class="defect-form-panel defect-form-panel--main h-100">
          <header class="defect-form-panel-head">
            <span class="defect-form-panel-icon defect-form-panel-icon--main"><i class="bi bi-card-text"></i></span>
            <div>
              <h2 class="defect-form-panel-title">Requirement</h2>
              <p class="defect-form-panel-hint mb-0">Title, description &amp; reference link</p>
            </div>
          </header>
          <div class="vstack gap-3 defect-form-main-body">
            <div class="defect-field">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control defect-title-input" value="<?php echo esc_view($row->title); ?>" required>
            </div>
            <div class="defect-field">
              <label class="form-label" for="req-description">Description</label>
              <textarea id="req-description" name="description" class="form-control" rows="6" placeholder="Describe the requirement…"><?php
                if (!empty($row->description)) {
                    $allowed = '<p><br><strong><em><b><i><u><s><del><ul><ol><li><a><h1><h2><h3><h4><h5><h6><blockquote><code><pre><span>';
                    echo strip_tags((string) $row->description, $allowed);
                }
              ?></textarea>
            </div>
            <div class="defect-field">
              <label class="form-label"><i class="bi bi-link-45deg me-1"></i>URL / Link</label>
              <input type="url" name="reference_url" class="form-control" value="<?php echo !empty($row->reference_url) ? esc_view($row->reference_url) : ''; ?>" placeholder="https://example.com/requirement-spec">
              <div class="form-text">Optional. Paste any web link related to this requirement.</div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <div class="defect-form-actions">
      <div class="defect-form-actions-inner">
        <button type="submit" class="btn btn-primary defect-btn-save"><i class="bi bi-check-lg me-1"></i>Save</button>
        <a class="btn btn-light border" href="<?php echo esc_view($back_url); ?>">Cancel</a>
      </div>
    </div>
  </form>
</div>
</div>

<?php $this->load->view('partials/footer'); ?>
<?php
$this->load->view('partials/oms_select2_multi', array(
    'oms_select2_selectors' => array('#req-assigned-to-select'),
    'oms_select2_placeholder' => 'Select assignee(s)…',
));
?>
<script src="https://cdn.jsdelivr.net/npm/tinymce@6.8.3/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function(){
  var isNarrow = window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
  tinymce.init({
    selector: '#req-description',
    menubar: false,
    statusbar: true,
    plugins: [
      'advlist', 'autolink', 'lists', 'link', 'charmap', 'preview',
      'searchreplace', 'visualblocks', 'code', 'fullscreen',
      'insertdatetime', 'table', 'help', 'wordcount'
    ],
    toolbar: 'undo redo | bold italic underline | bullist numlist | link | removeformat',
    branding: false,
    height: isNarrow ? 180 : 260,
    width: '100%',
    convert_urls: false,
    default_link_target: '_blank',
    placeholder: 'Describe the requirement…',
    toolbar_mode: isNarrow ? 'scrolling' : 'wrap',
    content_style: 'body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; font-size: 14px; line-height: 1.5; }',
    formats: {
      bold: { inline: 'strong' },
      italic: { inline: 'em' },
      underline: { inline: 'u' },
      strikethrough: { inline: 'del' }
    }
  });

  document.addEventListener('DOMContentLoaded', function() {
    var receivedDateInput = document.getElementById('received_date');
    var expectedDateInput = document.getElementById('expected_delivery_date');
    var dateError = document.getElementById('date-error');
    var form = document.getElementById('reqEditForm') || document.querySelector('form');

    function validateDates() {
      if (receivedDateInput && receivedDateInput.value && expectedDateInput && expectedDateInput.value) {
        var receivedDate = new Date(receivedDateInput.value);
        var expectedDate = new Date(expectedDateInput.value);
        if (expectedDate < receivedDate) {
          if (dateError) dateError.style.display = 'block';
          if (expectedDateInput) expectedDateInput.classList.add('is-invalid');
          return false;
        } else {
          if (dateError) dateError.style.display = 'none';
          if (expectedDateInput) expectedDateInput.classList.remove('is-invalid');
          return true;
        }
      }
      if (dateError) dateError.style.display = 'none';
      if (expectedDateInput) expectedDateInput.classList.remove('is-invalid');
      return true;
    }

    if (receivedDateInput) receivedDateInput.addEventListener('change', validateDates);
    if (expectedDateInput) expectedDateInput.addEventListener('change', validateDates);
    if (form) {
      form.addEventListener('submit', function(e) {
        if (window.tinymce) {
          var ed = tinymce.get('req-description');
          if (ed) { ed.save(); }
        }
        if (!validateDates()) {
          e.preventDefault();
          return false;
        }
      });
    }
  });
})();
</script>
