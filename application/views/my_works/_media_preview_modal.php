<div class="modal fade" id="mwMediaPreviewModal" tabindex="-1" aria-labelledby="mwMediaPreviewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered mw-media-modal-dialog mw-media-size-md" id="mwMediaModalDialog">
    <div class="modal-content mw-media-preview-content">
      <div class="modal-header flex-wrap gap-2">
        <div class="mw-media-preview-title-wrap flex-grow-1 min-w-0">
          <h5 class="modal-title fw-bold text-truncate mb-0" id="mwMediaPreviewModalLabel">Preview</h5>
          <div class="small text-muted text-truncate" id="mwMediaPreviewSubtitle" hidden></div>
        </div>
        <div class="mw-media-size-toolbar btn-group btn-group-sm" id="mwMediaSizeToolbar" role="group" aria-label="Player size" hidden>
          <button type="button" class="btn btn-outline-secondary" data-size="sm" title="Small player">S</button>
          <button type="button" class="btn btn-outline-secondary active" data-size="md" title="Medium player">M</button>
          <button type="button" class="btn btn-outline-secondary" data-size="lg" title="Large player">L</button>
          <button type="button" class="btn btn-outline-secondary" data-size="xl" title="Extra large player">XL</button>
          <button type="button" class="btn btn-outline-secondary" data-size="theater" title="Theater mode (wide)"><i class="bi bi-aspect-ratio"></i></button>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0 mw-media-preview-body">
        <div class="mw-media-preview-video-wrap" hidden>
          <video id="mwMediaPreviewVideo" class="mw-media-preview-video" playsinline controls></video>
        </div>
        <div class="mw-media-preview-image-wrap text-center p-3" hidden>
          <img class="mw-media-preview-image img-fluid rounded" alt="">
        </div>
        <div class="mw-media-preview-audio-wrap p-4" hidden>
          <div id="mwMediaPreviewAudioHost"></div>
          <audio id="mwMediaPreviewAudio" class="mw-media-preview-audio" controls preload="metadata"></audio>
        </div>
      </div>
    </div>
  </div>
</div>
