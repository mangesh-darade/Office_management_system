<?php $this->load->view('partials/header', ['title' => 'ElintOm Proposals']); ?>
<?php
$can_export = !empty($can_export);
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <h1 class="h3 mb-0">
    <i class="bi bi-file-earmark-text me-2"></i>ElintOm Proposals
  </h1>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?php echo site_url('subscription-builder'); ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-sliders me-1"></i>Open Subscription Builder
    </a>
  </div>
</div>

<?php if ($this->session->flashdata('error')): ?>
  <div class="alert alert-danger"><?php echo esc_view($this->session->flashdata('error')); ?></div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
  <div class="alert alert-success"><?php echo esc_view($this->session->flashdata('success')); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
  <div class="card-header bg-light d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0"><i class="bi bi-list-ul me-2"></i>Saved Proposals</h5>
    <span class="badge text-bg-secondary"><?php echo (int) $total; ?> proposal(s)</span>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-sm table-striped mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Client Name</th>
            <th>Client Business</th>
            <th>Document</th>
            <th>Created By</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $row): ?>
            <tr>
              <td><?php echo (int) $row->id; ?></td>
              <td><?php echo esc_view($row->client_name ?: '—'); ?></td>
              <td><?php echo esc_view($row->client_business ?: '—'); ?></td>
              <td>
                <?php if (!empty($row->document_path)): ?>
                  <?php
                    $doc_name = basename((string) $row->document_path);
                    $doc_display = preg_replace('/\.html?$/i', '.pdf', $doc_name);
                  ?>
                  <span class="text-muted small"><?php echo esc_view($doc_display); ?></span>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td><?php echo esc_view($row->created_by_name ?: ('User #' . (int) $row->created_by)); ?></td>
              <td><?php echo !empty($row->created_at) ? esc_view(date('d M Y, h:i A', strtotime($row->created_at))) : '—'; ?></td>
              <td class="text-nowrap">
                <?php if (!empty($row->document_path) && $can_export): ?>
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo site_url('elintom-proposals/' . (int) $row->id . '/export/pdf'); ?>" class="btn btn-outline-primary">
                    <i class="bi bi-download"></i> Export
                  </a>
                  <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Export formats">
                    <span class="visually-hidden">Toggle export formats</span>
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                      <a class="dropdown-item" href="<?php echo site_url('elintom-proposals/' . (int) $row->id . '/export/pdf'); ?>">
                        <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>PDF
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="<?php echo site_url('elintom-proposals/' . (int) $row->id . '/export/excel'); ?>">
                        <i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel
                      </a>
                    </li>
                    <li>
                      <a class="dropdown-item" href="<?php echo site_url('elintom-proposals/' . (int) $row->id . '/export/doc'); ?>">
                        <i class="bi bi-file-earmark-word me-2 text-primary"></i>Word (DOC)
                      </a>
                    </li>
                  </ul>
                </div>
                <?php elseif (!empty($row->document_path)): ?>
                  <span class="text-muted small">No export access</span>
                <?php else: ?>
                  <span class="text-muted small">No file</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">No proposals saved yet. Use <strong>Print / Save as PDF</strong> on the proposal preview or download PDF from Subscription Builder.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php if ($total_pages > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <span class="small text-muted">Page <?php echo (int) $page; ?> of <?php echo (int) $total_pages; ?></span>
    <div class="btn-group btn-group-sm">
      <?php if ($page > 1): ?>
      <a class="btn btn-outline-secondary" href="<?php echo site_url('elintom-proposals?page=' . ((int) $page - 1)); ?>">Previous</a>
      <?php endif; ?>
      <?php if ($page < $total_pages): ?>
      <a class="btn btn-outline-secondary" href="<?php echo site_url('elintom-proposals?page=' . ((int) $page + 1)); ?>">Next</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php $this->load->view('partials/footer'); ?>
