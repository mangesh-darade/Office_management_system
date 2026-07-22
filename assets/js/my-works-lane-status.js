/**
 * Today's Plan / lane full-view: custom status dropdown with per-value colors.
 * Native select cannot color individual options in Chrome — use custom menu.
 */
(function (window, $) {
  'use strict';
  if (!$ || window.mwLaneStatusBound) {
    return;
  }
  window.mwLaneStatusBound = true;

  function hexToRowBg(hex) {
    var h = String(hex || '#94a3b8').replace('#', '');
    if (h.length === 3) {
      h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
    }
    if (h.length !== 6) {
      return 'rgba(148,163,184,0.12)';
    }
    var r = parseInt(h.slice(0, 2), 16);
    var g = parseInt(h.slice(2, 4), 16);
    var b = parseInt(h.slice(4, 6), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',0.12)';
  }

  function applyRowStatus($row, $toggle, color, label) {
    $toggle.css({
      color: color,
      'border-color': color + '66'
    });
    $toggle.find('.mw-dash-status-label').text(label);
    $row.css({
      'background-color': hexToRowBg(color),
      '--mw-status-color': color
    });
  }

  function updateLaneCount($tbody) {
    var $lane = $tbody.closest('.mw-dash-lane');
    var count = $tbody.find('tr.mw-dash-task-row').length;
    $lane.find('.mw-dash-lane-count').first().text(count);
    if (count === 0 && $tbody.find('tr.mw-dash-lane-empty-row').length === 0) {
      var cols = parseInt($tbody.attr('data-colspan'), 10) || 4;
      $tbody.append(
        '<tr class="mw-dash-lane-empty-row"><td colspan="' + cols + '" class="mw-dash-lane-empty">No tasks planned for today</td></tr>'
      );
    }
  }

  $(document).on('click.mwDashStatus', '.mw-dash-status-option', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $opt = $(this);
    var $form = $opt.closest('form.mw-dash-status-form');
    var $row = $opt.closest('tr.mw-dash-task-row');
    var $toggle = $form.find('.mw-dash-status-toggle');
    var $hidden = $form.find('.mw-dash-status-value');
    if (!$form.length || !$row.length || $form.data('saving')) {
      return false;
    }

    var status = $opt.attr('data-status') || '';
    var color = $opt.attr('data-color') || '#6c757d';
    var leaves = $opt.attr('data-leaves-lane') === '1';
    var label = $.trim($opt.text());
    var prevVal = $hidden.val();

    if (!status || status === prevVal) {
      if (window.bootstrap && bootstrap.Dropdown) {
        var inst = bootstrap.Dropdown.getInstance($toggle[0]);
        if (inst) {
          inst.hide();
        }
      }
      return false;
    }

    $form.data('saving', true);
    $toggle.prop('disabled', true);
    $hidden.val(status);

    $.ajax({
      url: $form.attr('action'),
      type: 'POST',
      data: $form.serialize(),
      dataType: 'json',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).done(function (resp) {
      if (!resp || !resp.ok) {
        $hidden.val(prevVal);
        return;
      }

      applyRowStatus($row, $toggle, color, label);
      $toggle.attr('data-prev-status', status);
      $form.find('.mw-dash-status-option').removeClass('active');
      $opt.addClass('active');

      if (window.bootstrap && bootstrap.Dropdown) {
        var inst = bootstrap.Dropdown.getInstance($toggle[0]);
        if (inst) {
          inst.hide();
        }
      }

      if (leaves) {
        var $tbody = $row.closest('tbody');
        $row.fadeOut(150, function () {
          $row.remove();
          updateLaneCount($tbody);
        });
      }
    }).fail(function () {
      $hidden.val(prevVal);
      if (window.toastr) {
        toastr.error('Failed to update status.');
      } else {
        window.alert('Failed to update status.');
      }
    }).always(function () {
      $form.data('saving', false);
      $toggle.prop('disabled', false);
    });

    return false;
  });

  $(document).on('submit.mwDashStatus', '.mw-dash-status-form', function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    return false;
  });

  function ensurePulseNoteModal() {
    var existing = document.getElementById('mwPulseNoteModal');
    if (existing) {
      return existing;
    }
    var wrap = document.createElement('div');
    wrap.innerHTML =
      '<div class="modal fade" id="mwPulseNoteModal" tabindex="-1" aria-labelledby="mwPulseNoteModalLabel" aria-hidden="true">' +
        '<div class="modal-dialog modal-dialog-centered modal-sm">' +
          '<div class="modal-content mw-pulse-note-modal">' +
            '<div class="modal-header py-2">' +
              '<h5 class="modal-title fs-6" id="mwPulseNoteModalLabel">Notes</h5>' +
              '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
            '</div>' +
            '<div class="modal-body py-3">' +
              '<div class="mw-pulse-note-modal-name text-muted small mb-2"></div>' +
              '<div class="mw-pulse-note-modal-text"></div>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(wrap.firstChild);
    return document.getElementById('mwPulseNoteModal');
  }

  $(document).on('click.mwPulseNote', '.mw-pulse-note-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();

    var $btn = $(this);
    var name = $btn.attr('data-note-name') || '';
    var note = $btn.attr('data-note-text') || '';
    if (!note) {
      return false;
    }

    var modalEl = ensurePulseNoteModal();
    var $modal = $(modalEl);
    $modal.find('#mwPulseNoteModalLabel').text('Notes');
    $modal.find('.mw-pulse-note-modal-name').text(name);
    $modal.find('.mw-pulse-note-modal-text').text(note);

    if (window.bootstrap && bootstrap.Modal) {
      var inst = bootstrap.Modal.getOrCreateInstance(modalEl);
      inst.show();
    } else {
      $modal.addClass('show').css('display', 'block');
    }
    return false;
  });

  // Keep for older calls from unified tab load.
  window.initPulseNoteTooltips = function () {};
})(window, window.jQuery);
