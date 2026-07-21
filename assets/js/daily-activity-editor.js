/**
 * Daily Activity Summernote helpers — custom expand (layout-safe fullscreen).
 */
(function(window, $) {
  'use strict';

  if (!$ || !$.summernote) {
    return;
  }

  function findEditor($note) {
    var $editor = $note.next('.note-editor');
    if (!$editor.length) {
      $editor = $note.siblings('.note-editor').first();
    }
    return $editor;
  }

  function setExpanded($editor, on, baseHeight) {
    if (!$editor || !$editor.length) {
      return;
    }
    if (on) {
      $editor.addClass('da-note-expanded');
      document.body.classList.add('da-note-fullscreen');
      var toolbarH = $editor.find('.note-toolbar').outerHeight() || 48;
      var statusH = $editor.find('.note-statusbar').outerHeight() || 0;
      $editor.find('.note-editable').css({
        height: Math.max(120, window.innerHeight - toolbarH - statusH) + 'px',
        minHeight: 0
      });
    } else {
      $editor.removeClass('da-note-expanded');
      document.body.classList.remove('da-note-fullscreen');
      $editor.find('.note-editable').css({
        height: (baseHeight ? baseHeight + 'px' : ''),
        minHeight: ''
      });
    }
  }

  window.daInitSummernote = function(options) {
    var opts = options || {};
    var $note = $(opts.selector || '#summernote');
    if (!$note.length) {
      return $note;
    }

    // Recover from a previous broken fullscreen session.
    document.body.classList.remove('da-note-fullscreen');
    $('body > .note-editor').each(function() {
      var $orphan = $(this);
      if ($orphan.find('.note-editable').length) {
        $orphan.insertAfter($note);
      } else {
        $orphan.remove();
      }
      $orphan.removeClass('da-note-expanded fullscreen');
    });

    var baseHeight = opts.height || 120;
    var imageUpload = opts.onImageUpload || null;

    $note.summernote({
      placeholder: opts.placeholder || 'What did you work on?',
      tabsize: 2,
      height: baseHeight,
      toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
        ['fontsize', ['fontsize']],
        ['color', ['color']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['table', ['table']],
        ['insert', ['link', 'picture', 'hr']],
        ['view', ['daExpand', 'codeview']]
      ],
      styleTags: ['p', 'blockquote', 'pre', 'h3', 'h4', 'h5'],
      fontSizes: ['10', '12', '14', '16', '18', '24'],
      buttons: {
        daExpand: function(context) {
          var ui = $.summernote.ui;
          return ui.button({
            contents: '<i class="note-icon-arrows-alt"></i>',
            tooltip: 'Full screen',
            click: function() {
              var $editor = context.layoutInfo.editor;
              var turningOn = !$editor.hasClass('da-note-expanded');
              setExpanded($editor, turningOn, baseHeight);
              if (turningOn) {
                context.invoke('editor.focus');
              }
            }
          }).render();
        }
      },
      callbacks: {
        onImageUpload: function(files) {
          if (typeof imageUpload === 'function') {
            imageUpload(files);
          }
        }
      }
    });

    $(document).on('keydown.daNoteFs', function(e) {
      if (e.key !== 'Escape') {
        return;
      }
      var $editor = findEditor($note);
      if ($editor.hasClass('da-note-expanded')) {
        setExpanded($editor, false, baseHeight);
      }
    });

    $(window).on('resize.daNoteFs', function() {
      var $editor = findEditor($note);
      if ($editor.hasClass('da-note-expanded')) {
        setExpanded($editor, true, baseHeight);
      }
    });

    return $note;
  };
})(window, window.jQuery);
