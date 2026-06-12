<div class="modal fade" id="mwListCommentModal" tabindex="-1" aria-labelledby="mwListCommentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="" enctype="multipart/form-data" id="mwListCommentForm" class="mw-upload-form">
        <?php $this->load->view('my_works/_csrf'); ?>
        <input type="hidden" name="redirect" id="mwListCommentRedirect" value="">
        <div class="modal-header">
          <h5 class="modal-title" id="mwListCommentModalLabel">Add comment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="small text-muted mb-2" id="mwListCommentWorkTitle"></p>
          <label class="form-label fw-semibold" for="mwListCommentText">Comment <span class="text-danger">*</span></label>
          <textarea name="comment" id="mwListCommentText" class="form-control mb-3" rows="4" required placeholder="Share an update or note…"></textarea>
          <label class="form-label fw-semibold">Attachment (optional)</label>
          <?php $this->load->view('my_works/_attachment_field', array('input_id' => 'mw-list-comment-attachment')); ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send me-1"></i>Post comment</button>
        </div>
      </form>
    </div>
  </div>
</div>
