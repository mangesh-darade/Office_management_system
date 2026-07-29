<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Select2 chip-style multi-select — single-row assignee tags + × remove.
 * Used by Tasks, Requirements, My Works, Template Tasks Assigned-to fields.
 *
 * Expects optional:
 *   $oms_select2_selectors — array of CSS selectors
 *   $oms_select2_placeholder — string
 *   $oms_select2_allow_clear — bool
 *   $oms_select2_auto_init — bool (default true)
 */
$oms_select2_selectors = (isset($oms_select2_selectors) && is_array($oms_select2_selectors))
    ? $oms_select2_selectors
    : array();
$oms_select2_placeholder = isset($oms_select2_placeholder)
    ? (string) $oms_select2_placeholder
    : 'Search and select…';
$oms_select2_allow_clear = !empty($oms_select2_allow_clear);
$oms_select2_auto_init = !isset($oms_select2_auto_init) || !empty($oms_select2_auto_init);
?>
<?php if (empty($GLOBALS['oms_select2_multi_assets_loaded'])): ?>
<?php $GLOBALS['oms_select2_multi_assets_loaded'] = true; ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* ===== OMS Assign-to multi-select — wrap chips (no horizontal scroll) ===== */
.oms-select2-multi.select2-hidden-accessible + .select2-container,
.select2-container.oms-select2-skin {
  width: 100% !important;
  display: block;
  max-width: 100%;
}

/* Input shell — grows with rows, like a normal multi-tag box */
.oms-select2-multi + .select2-container .select2-selection--multiple,
.select2-container.oms-select2-skin .select2-selection--multiple,
.select2-container--bootstrap-5.oms-select2-skin .select2-selection--multiple {
  min-height: 38px !important;
  height: auto !important;
  max-height: none !important;
  padding: 6px 8px !important;
  border-radius: 4px !important;
  border: 1px solid #ced4da !important;
  background-color: #fff !important;
  display: block !important;
  overflow: visible !important;
}

/* Chips wrap to multiple rows — no scrollbar */
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__rendered,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__rendered,
.select2-container--bootstrap-5.oms-select2-skin .select2-selection--multiple .select2-selection__rendered {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: wrap !important;
  align-items: center !important;
  gap: 6px !important;
  padding: 0 !important;
  margin: 0 !important;
  list-style: none !important;
  width: 100% !important;
  overflow: visible !important;
  white-space: normal !important;
}

/* Tag chip — cream/yellow like reference (× left, label right) */
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__choice,
.select2-container--bootstrap-5.oms-select2-skin .select2-selection--multiple .select2-selection__choice {
  display: inline-flex !important;
  flex: 0 0 auto !important;
  flex-direction: row !important;
  align-items: center !important;
  float: none !important;
  position: relative !important;
  margin: 0 !important;
  padding: 3px 10px 3px 6px !important;
  height: auto !important;
  min-height: 26px !important;
  max-width: 100% !important;
  background: #fff8e1 !important;
  border: 1px solid #ffe082 !important;
  color: #5d4037 !important;
  border-radius: 3px !important;
  font-size: 0.8125rem !important;
  font-weight: 500 !important;
  line-height: 1.25 !important;
  gap: 6px !important;
  box-shadow: none !important;
}

.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__display,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__choice__display {
  display: inline-block !important;
  order: 2;
  padding: 0 !important;
  margin: 0 !important;
  max-width: 14rem !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
  cursor: default;
}

/* × on the LEFT of each tag */
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__choice__remove,
.select2-container--bootstrap-5.oms-select2-skin .select2-selection--multiple .select2-selection__choice__remove {
  display: inline-flex !important;
  order: 1;
  align-items: center !important;
  justify-content: center !important;
  visibility: visible !important;
  opacity: 1 !important;
  position: relative !important;
  left: auto !important;
  right: auto !important;
  top: auto !important;
  float: none !important;
  width: auto !important;
  height: auto !important;
  min-width: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
  border: 0 !important;
  border-radius: 0 !important;
  background: transparent !important;
  background-image: none !important;
  box-shadow: none !important;
  color: #757575 !important;
  font-size: 0 !important;
  line-height: 1 !important;
  cursor: pointer !important;
  overflow: visible !important;
  text-indent: 0 !important;
  -webkit-appearance: none !important;
  appearance: none !important;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove:hover,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__choice__remove:hover {
  color: #c62828 !important;
  background: transparent !important;
  background-image: none !important;
  box-shadow: none !important;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove > span,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__choice__remove > span,
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove [aria-hidden],
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__choice__remove [aria-hidden] {
  display: inline-block !important;
  visibility: visible !important;
  opacity: 1 !important;
  font-size: 14px !important;
  font-weight: 700 !important;
  line-height: 1 !important;
  color: inherit !important;
  text-indent: 0 !important;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove::before,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__choice__remove::before,
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove::after,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__choice__remove::after {
  content: none !important;
  display: none !important;
}

.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__clear,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-selection__clear {
  display: none !important;
}

/* Search field sits with chips and wraps naturally */
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-search--inline,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-search--inline,
.select2-container--bootstrap-5.oms-select2-skin .select2-selection--multiple .select2-search--inline {
  display: inline-flex !important;
  float: none !important;
  flex: 1 1 6rem !important;
  align-items: center !important;
  margin: 0 !important;
  padding: 0 !important;
  min-width: 6rem !important;
  width: auto !important;
  max-width: 100% !important;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-search--inline .select2-search__field,
.select2-container.oms-select2-skin .select2-selection--multiple .select2-search--inline .select2-search__field,
.select2-container--bootstrap-5.oms-select2-skin .select2-selection--multiple .select2-search--inline .select2-search__field {
  margin: 0 !important;
  padding: 2px 0 !important;
  height: 26px !important;
  min-height: 26px !important;
  min-width: 6rem !important;
  width: 100% !important;
  max-width: 100% !important;
  font-size: 0.875rem !important;
  line-height: 1.25 !important;
  border: 0 !important;
  box-shadow: none !important;
  background: transparent !important;
}

.oms-select2-multi + .select2-container.select2-container--focus .select2-selection--multiple,
.select2-container.oms-select2-skin.select2-container--focus .select2-selection--multiple,
.oms-select2-multi + .select2-container.select2-container--open .select2-selection--multiple,
.select2-container.oms-select2-skin.select2-container--open .select2-selection--multiple {
  border-color: #86b7fe !important;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12) !important;
}

.oms-form-compact .oms-select2-multi + .select2-container .select2-selection--multiple,
.oms-form-compact .select2-container.oms-select2-skin .select2-selection--multiple {
  min-height: 34px !important;
  max-height: none !important;
  padding: 4px 6px !important;
}

.mw-create-field .oms-select2-multi + .select2-container,
.mw-form-field .oms-select2-multi + .select2-container,
.mw-quick-add-section .oms-select2-multi + .select2-container,
.mw-create-field .select2-container.oms-select2-skin,
.mw-form-field .select2-container.oms-select2-skin,
.mw-quick-add-section .select2-container.oms-select2-skin {
  width: 100% !important;
}

.select2-container.oms-select2-skin .select2-dropdown {
  border-color: #ced4da;
  border-radius: 4px;
  box-shadow: 0 0.5rem 1rem rgba(15, 23, 42, 0.12);
}
.select2-container.oms-select2-skin .select2-results__option--highlighted.select2-results__option--selectable {
  background-color: #0d6efd;
}
</style>
<script>
window.omsInitSelect2Multi = function (selector, opts) {
  opts = opts || {};
  if (!window.jQuery || !jQuery.fn.select2) {
    return;
  }
  var $el = (selector && selector.jquery) ? selector : jQuery(selector);
  if (!$el.length || $el.data('select2')) {
    return;
  }
  $el.addClass('oms-select2-multi');
  $el.select2({
    theme: 'bootstrap-5',
    placeholder: opts.placeholder || 'Search and select…',
    allowClear: !!opts.allowClear,
    width: '100%',
    closeOnSelect: false,
    multiple: true,
    matcher: function (params, data) {
      if (data.element) {
        var $option = jQuery(data.element);
        if ($option.length > 0 && $option.prop('selected') === true) {
          return null;
        }
      }
      if (!params.term || params.term.trim() === '') {
        return data;
      }
      var term = params.term.toLowerCase();
      if (data.text && typeof data.text === 'string' && data.text.toLowerCase().indexOf(term) > -1) {
        return data;
      }
      return null;
    },
    templateResult: function (data) {
      if (!data.id) {
        return data.text;
      }
      if (data.element) {
        var $option = jQuery(data.element);
        if ($option.length > 0 && $option.prop('selected') === true) {
          return null;
        }
      }
      return data.text;
    }
  });
  var $container = $el.next('.select2-container');
  $container.addClass('oms-select2-skin');

  function fixSearchWidth() {
    $container.find('.select2-search--inline .select2-search__field').each(function () {
      this.style.setProperty('width', '100%', 'important');
      this.style.setProperty('min-width', '5rem', 'important');
    });
  }

  function ensureChoiceRemoveButtons() {
    $container.find('.select2-selection__choice').each(function () {
      var $choice = jQuery(this);
      var $btns = $choice.find('.select2-selection__choice__remove');
      if (!$btns.length) {
        return;
      }
      $btns.slice(1).remove();
      var $btn = $choice.find('.select2-selection__choice__remove').first();
      $btn.attr('title', 'Remove');
      $btn.attr('aria-label', 'Remove');
      $btn.html('<span aria-hidden="true">\u00D7</span>');
      // × on the left (reference UI), then name
      var $display = $choice.find('.select2-selection__choice__display').first();
      if ($display.length) {
        $display.before($btn);
      } else {
        $choice.prepend($btn);
      }
    });
    fixSearchWidth();
  }

  $el.on('select2:select select2:unselect select2:clear select2:open change', ensureChoiceRemoveButtons);
  setTimeout(ensureChoiceRemoveButtons, 0);
  setTimeout(ensureChoiceRemoveButtons, 50);
};

window.omsInitRemainingSelect2Multi = function (opts) {
  opts = opts || {};
  if (!window.jQuery || !window.omsInitSelect2Multi) {
    return;
  }
  jQuery('select.oms-select2-multi[multiple]').each(function () {
    var $s = jQuery(this);
    if ($s.data('select2')) {
      return;
    }
    window.omsInitSelect2Multi($s, {
      placeholder: $s.attr('data-placeholder') || opts.placeholder || 'Select assignee(s)…',
      allowClear: $s.attr('data-allow-clear') === '1' || !!opts.allowClear
    });
  });
};
</script>
<?php endif; ?>
<?php if (!empty($oms_select2_selectors)): ?>
<script>
(function () {
  function run() {
    if (!window.omsInitSelect2Multi) {
      return;
    }
    var sels = <?php echo json_encode(array_values($oms_select2_selectors)); ?>;
    var ph = <?php echo json_encode($oms_select2_placeholder); ?>;
    var allowClear = <?php echo $oms_select2_allow_clear ? 'true' : 'false'; ?>;
    for (var i = 0; i < sels.length; i++) {
      window.omsInitSelect2Multi(sels[i], { placeholder: ph, allowClear: allowClear });
    }
  }
  if (window.jQuery) {
    jQuery(run);
  } else {
    document.addEventListener('DOMContentLoaded', run);
  }
})();
</script>
<?php endif; ?>
<?php if ($oms_select2_auto_init): ?>
<script>
(function () {
  function runAuto() {
    if (window.omsInitRemainingSelect2Multi) {
      window.omsInitRemainingSelect2Multi({
        placeholder: <?php echo json_encode($oms_select2_placeholder); ?>,
        allowClear: <?php echo $oms_select2_allow_clear ? 'true' : 'false'; ?>
      });
    }
  }
  if (window.jQuery) {
    jQuery(function () { setTimeout(runAuto, 120); });
  } else {
    document.addEventListener('DOMContentLoaded', function () { setTimeout(runAuto, 120); });
  }
})();
</script>
<?php endif; ?>
