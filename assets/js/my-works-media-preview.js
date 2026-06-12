(function () {
  'use strict';

  var SIZE_CLASSES = ['mw-media-size-sm', 'mw-media-size-md', 'mw-media-size-lg', 'mw-media-size-xl', 'mw-media-size-theater'];
  var plyrInstance = null;

  function mimeFromUrl(url) {
    var path = (url || '').split('?')[0].toLowerCase();
    if (path.indexOf('.webm') !== -1) {
      return 'video/webm';
    }
    if (path.indexOf('.mov') !== -1) {
      return 'video/quicktime';
    }
    if (path.indexOf('.ogg') !== -1) {
      return 'audio/ogg';
    }
    if (path.indexOf('.wav') !== -1) {
      return 'audio/wav';
    }
    if (path.indexOf('.m4a') !== -1 || path.indexOf('.mp4') !== -1) {
      return 'video/mp4';
    }
    return 'video/mp4';
  }

  function destroyPlyr() {
    if (plyrInstance) {
      try {
        plyrInstance.destroy();
      } catch (e) {
        /* ignore */
      }
      plyrInstance = null;
    }
  }

  function initPlyr(mediaEl, kind) {
    if (typeof Plyr === 'undefined' || !mediaEl) {
      return null;
    }
    destroyPlyr();
    var controls = kind === 'audio'
      ? ['play', 'progress', 'current-time', 'duration', 'mute', 'volume', 'settings']
      : ['play-large', 'rewind', 'play', 'fast-forward', 'progress', 'current-time', 'duration', 'mute', 'volume', 'settings', 'pip', 'airplay', 'fullscreen'];
    plyrInstance = new Plyr(mediaEl, {
      controls: controls,
      settings: ['speed'],
      speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
      ratio: null,
      fullscreen: { enabled: true, fallback: true, iosNative: true },
      keyboard: { focused: true, global: false },
      tooltips: { controls: true, seek: true }
    });
    return plyrInstance;
  }

  document.addEventListener('DOMContentLoaded', function () {
    var modalEl = document.getElementById('mwMediaPreviewModal');
    if (!modalEl || typeof bootstrap === 'undefined') {
      return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var dialogEl = document.getElementById('mwMediaModalDialog');
    var titleEl = document.getElementById('mwMediaPreviewModalLabel');
    var subtitleEl = document.getElementById('mwMediaPreviewSubtitle');
    var sizeToolbar = document.getElementById('mwMediaSizeToolbar');
    var videoWrap = modalEl.querySelector('.mw-media-preview-video-wrap');
    var videoEl = document.getElementById('mwMediaPreviewVideo');
    var imageWrap = modalEl.querySelector('.mw-media-preview-image-wrap');
    var imageEl = modalEl.querySelector('.mw-media-preview-image');
    var audioWrap = modalEl.querySelector('.mw-media-preview-audio-wrap');
    var audioEl = document.getElementById('mwMediaPreviewAudio');

    function setPlayerSize(mode) {
      if (!dialogEl) {
        return;
      }
      SIZE_CLASSES.forEach(function (cls) {
        dialogEl.classList.remove(cls);
      });
      dialogEl.classList.add('mw-media-size-' + (mode || 'md'));
      if (sizeToolbar) {
        sizeToolbar.querySelectorAll('[data-size]').forEach(function (btn) {
          btn.classList.toggle('active', btn.getAttribute('data-size') === mode);
        });
      }
    }

    function resetMedia() {
      destroyPlyr();
      if (videoEl) {
        videoEl.pause();
        videoEl.removeAttribute('src');
        videoEl.load();
      }
      if (audioEl) {
        audioEl.pause();
        audioEl.removeAttribute('src');
        audioEl.load();
      }
      if (imageEl) {
        imageEl.removeAttribute('src');
      }
      if (videoWrap) {
        videoWrap.hidden = true;
      }
      if (imageWrap) {
        imageWrap.hidden = true;
      }
      if (audioWrap) {
        audioWrap.hidden = true;
      }
      if (sizeToolbar) {
        sizeToolbar.hidden = true;
      }
      if (subtitleEl) {
        subtitleEl.textContent = '';
        subtitleEl.hidden = true;
      }
      setPlayerSize('md');
    }

    if (sizeToolbar) {
      sizeToolbar.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-size]');
        if (!btn) {
          return;
        }
        setPlayerSize(btn.getAttribute('data-size'));
      });
    }

    modalEl.addEventListener('hidden.bs.modal', resetMedia);

    document.addEventListener('click', function (e) {
      var btn = e.target.closest('.mw-media-play-btn');
      if (!btn) {
        return;
      }
      e.preventDefault();
      e.stopPropagation();
      resetMedia();

      var url = btn.getAttribute('data-preview-url') || '';
      var kind = btn.getAttribute('data-media-kind') || 'video';
      var itemTitle = btn.getAttribute('data-media-title') || 'Preview';
      var fileSize = btn.getAttribute('data-media-size') || '';

      if (titleEl) {
        titleEl.textContent = itemTitle;
      }
      if (subtitleEl) {
        if (fileSize !== '') {
          subtitleEl.textContent = fileSize;
          subtitleEl.hidden = false;
        } else {
          subtitleEl.hidden = true;
        }
      }

      if (kind === 'video' && videoWrap && videoEl) {
        videoEl.src = url;
        videoWrap.hidden = false;
        if (sizeToolbar) {
          sizeToolbar.hidden = false;
        }
        modal.show();
        initPlyr(videoEl, 'video');
        if (plyrInstance) {
          plyrInstance.play().catch(function () { /* autoplay blocked */ });
        } else {
          videoEl.play().catch(function () { /* ignore */ });
        }
        return;
      }

      if (kind === 'image' && imageWrap && imageEl) {
        imageEl.src = url;
        imageEl.alt = itemTitle;
        imageWrap.hidden = false;
        modal.show();
        return;
      }

      if (kind === 'audio' && audioWrap && audioEl) {
        audioEl.src = url;
        audioWrap.hidden = false;
        modal.show();
        initPlyr(audioEl, 'audio');
        if (plyrInstance) {
          plyrInstance.play().catch(function () { /* ignore */ });
        } else {
          audioEl.play().catch(function () { /* ignore */ });
        }
      }
    });
  });
})();
