<?php
  $field_name = isset($field_name) ? $field_name : 'status';
  $module_type = isset($module_type) ? $module_type : '';
  $current = isset($current) ? (string) $current : '';
  $default_code = isset($default_code) ? (string) $default_code : '';
  $required = !empty($required);
  $select_class = isset($select_class) ? $select_class : 'form-select';
  $select_id = isset($select_id) ? $select_id : $field_name;

  if (!function_exists('module_status_records')) {
    get_instance()->load->helper('module_status');
  }
  $status_records = isset($status_records) ? $status_records : module_status_records($module_type);
?>
<select name="<?php echo esc_view($field_name); ?>" id="<?php echo esc_view($select_id); ?>" class="<?php echo esc_view($select_class); ?>"<?php echo $required ? ' required' : ''; ?>>
  <?php foreach ($status_records as $st): ?>
    <?php
      $code = (string) $st->code;
      $selected = ($current !== '' && $current === $code) || ($current === '' && $default_code !== '' && $default_code === $code);
    ?>
    <option value="<?php echo esc_view($code); ?>" <?php echo $selected ? 'selected' : ''; ?>><?php echo esc_view($st->name); ?></option>
  <?php endforeach; ?>
</select>
