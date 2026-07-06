<?php defined('BASEPATH') OR exit('No direct script access allowed');
$back_url = isset($back_url) ? (string) $back_url : site_url('my-works');
$back_label = isset($back_label) ? (string) $back_label : 'Back';
$back_title = isset($back_title) ? (string) $back_title : $back_label;
?>
<a class="btn btn-outline-secondary btn-sm mw-back-btn" href="<?php echo esc_view($back_url, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo esc_view($back_title, ENT_QUOTES, 'UTF-8'); ?>">
  <i class="bi bi-arrow-left" aria-hidden="true"></i><span class="d-none d-sm-inline ms-1"><?php echo esc_view($back_label, ENT_QUOTES, 'UTF-8'); ?></span>
</a>
