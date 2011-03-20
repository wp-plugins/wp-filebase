<?php
@ob_start();
require_once(dirname(__FILE__).'/../../../wp-load.php');
wpfb_loadclass('Core', 'Download');
$custom_file = WPFB_Core::UploadDir() .'/_wp-filebase.css';
$default_file = WPFB_PLUGIN_ROOT . 'wp-filebase.css';
$custom = file_exists($custom_file);
@ob_end_clean();
WPFB_Download::SendFile($custom ? $custom_file : $default_file);
?>