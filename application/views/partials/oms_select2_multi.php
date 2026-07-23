<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Select2 chip-style multi-select (tag input UI).
 *
 * Expects optional:
 *   $oms_select2_selectors — array of CSS selectors, e.g. ['#assigned-to-select']
 *   $oms_select2_placeholder — string (default: Search and select…)
 *   $oms_select2_allow_clear — bool (default false for clean chip UI)
 */
$oms_select2_selectors = (isset($oms_select2_selectors) && is_array($oms_select2_selectors))
    ? $oms_select2_selectors
    : array();
$oms_select2_placeholder = isset($oms_select2_placeholder)
    ? (string) $oms_select2_placeholder
    : 'Search and select…';
$oms_select2_allow_clear = !empty($oms_select2_allow_clear);
?>
<?php if (empty($GLOBALS['oms_select2_multi_assets_loaded'])): ?>
<?php $GLOBALS['oms_select2_multi_assets_loaded'] = true; ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
/* Chip multi-select with visible × remove on each tag */
.oms-select2-multi.select2-hidden-accessible + .select2-container {
  width: 100% !important;
  display: block;
}
.oms-select2-multi + .select2-container .select2-selection--multiple {
  min-height: 38px;
  padding: 4px 8px !important;
  border-radius: 0.375rem;
  border: 1px solid #ced4da;
  background-color: #fff;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__rendered {
  display: flex !important;
  flex-wrap: wrap;
  gap: 6px;
  padding: 0 !important;
  margin: 0 !important;
  align-items: center;
  list-style: none;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice {
  display: inline-flex !important;
  flex-direction: row;
  align-items: center;
  float: none !important;
  position: relative !important;
  margin: 0 !important;
  padding: 3px 10px 3px 6px !important;
  background-color: #fff8e1 !important;
  border: 1px solid #ffe082 !important;
  color: #5d4037 !important;
  border-radius: 4px !important;
  font-size: 0.8125rem !important;
  line-height: 1.35 !important;
  max-width: 100%;
  padding-left: 6px !important;
}
/* Force visible × on every selected user tag */
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  visibility: visible !important;
  opacity: 1 !important;
  position: relative !important;
  left: auto !important;
  right: auto !important;
  top: auto !important;
  float: none !important;
  order: -1;
  width: 1.25rem !important;
  height: 1.25rem !important;
  min-width: 1.25rem !important;
  margin: 0 6px 0 0 !important;
  padding: 0 !important;
  border: 0 !important;
  border-right: 0 !important;
  border-radius: 3px !important;
  background: transparent !important;
  background-image: none !important;
  color: #6c757d !important;
  font-size: 1.15rem !important;
  font-weight: 700 !important;
  line-height: 1 !important;
  cursor: pointer !important;
  -webkit-appearance: none !important;
  appearance: none !important;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove:hover {
  color: #dc3545 !important;
  background: rgba(220, 53, 69, 0.12) !important;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove span,
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove [aria-hidden] {
  display: inline-block !important;
  visibility: visible !important;
  opacity: 1 !important;
  font-size: 1.15rem !important;
  font-weight: 700 !important;
  line-height: 1 !important;
  color: inherit !important;
}
/* If theme empties the button, draw × ourselves */
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove:empty::before,
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__remove.oms-force-x::before {
  content: '\00D7';
  display: inline-block;
  font-size: 1.15rem;
  font-weight: 700;
  line-height: 1;
  color: inherit;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__choice__display {
  display: inline-block !important;
  padding-left: 0 !important;
  cursor: default;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 14rem;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-selection__clear {
  display: none !important;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-search--inline {
  float: none !important;
  flex: 1 1 6rem;
  margin: 0 !important;
  min-width: 6rem;
}
.oms-select2-multi + .select2-container .select2-selection--multiple .select2-search--inline .select2-search__field {
  margin: 0 !important;
  padding: 2px 0 !important;
  height: 28px !important;
  min-height: 28px !important;
  font-size: 0.875rem !important;
  width: 100% !important;
  border: 0 !important;
  box-shadow: none !important;
}
.oms-select2-multi + .select2-container.select2-container--focus .select2-selection--multiple,
.oms-select2-multi + .select2-container.select2-container--open .select2-selection--multiple {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}
.oms-form-compact .oms-select2-multi + .select2-container .select2-selection--multiple {
  min-height: 34px;
  padding: 3px 6px !important;
}
.mw-create-field .oms-select2-multi + .select2-container,
.mw-form-field .oms-select2-multi + .select2-container,
.mw-quick-add-section .oms-select2-multi + .select2-container {
  width: 100% !important;
}
</style>
<script>
window.omsInitSelect2Multi = function (selector, opts) {
  opts = opts || {};
  if (!window.jQuery || !jQuery.fn.select2) {
    return;
  }
  var $el = jQuery(selector);
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
  $el.next('.select2-container').addClass('oms-select2-skin');

  function ensureChoiceRemoveButtons() {
    $el.next('.select2-container').find('.select2-selection__choice__remove').each(function () {
      var $btn = jQuery(this);
      $btn.attr('title', 'Remove');
      $btn.attr('aria-label', 'Remove');
      var text = jQuery.trim($btn.text());
      if (text === '') {
        $btn.addClass('oms-force-x');
        if ($btn.children().length === 0) {
          $btn.html('<span aria-hidden="true">\u00D7</span>');
        }
      }
    });
  }
  $el.on('select2:select select2:unselect select2:clear change', ensureChoiceRemoveButtons);
  setTimeout(ensureChoiceRemoveButtons, 0);
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
