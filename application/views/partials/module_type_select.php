<?php
  $field_name = isset($field_name) ? $field_name : 'type';
  $module_key = isset($module_key) ? $module_key : '';
  $options = isset($options) ? $options : array();
  $current = isset($current) ? (string) $current : '';
  $required = !empty($required);
  $placeholder = isset($placeholder) ? $placeholder : '— Select type —';
  $select_class = isset($select_class) ? $select_class : 'form-select';
?>
<select name="<?php echo esc_view($field_name); ?>" id="<?php echo esc_view($field_name); ?>" class="<?php echo esc_view($select_class); ?>"<?php echo $required ? ' required' : ''; ?>>
  <?php if (!$required): ?>
    <option value=""><?php echo esc_view($placeholder); ?></option>
  <?php endif; ?>
  <?php foreach ($options as $code => $label): ?>
    <option value="<?php echo esc_view($code); ?>" <?php echo $current === (string) $code ? 'selected' : ''; ?>><?php echo esc_view($label); ?></option>
  <?php endforeach; ?>
</select>
