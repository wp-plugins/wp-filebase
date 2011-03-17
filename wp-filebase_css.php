<?php
require_once(dirname(__FILE__).'/../../../wp-config.php');
wpfb_loadclass('Core', 'Download');
$custom_file = WPFB_Core::UploadDir() .'/_wp-filebase.css';
$default_file = WPFB_PLUGIN_ROOT . 'wp-filebase.css';
$custom = file_exists($custom_file);
WPFB_Download::SendFile($custom ? $custom_file : $default_file);
?>