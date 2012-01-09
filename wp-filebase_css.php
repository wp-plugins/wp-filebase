<?php
//ob_start();

define('WPFB_SIMPLE_LOAD', true);

if(empty($_GET['rp'])) {
	require_once(dirname(__FILE__).'/../../../wp-load.php');
	$slow = true;	
} else
	require_once(dirname(__FILE__).'/wp-filebase.php');

wpfb_loadclass('Core');

$file = WPFB_Core::GetCustomCssPath(stripslashes(@$_GET['rp']));
//echo $file;
//@ob_end_clean();
header('Content-Type: text/css');

if(empty($file) || !@file_exists($file) || !@is_writable($file)) // TODO: remove writable check? this is for security!
	$file = WPFB_PLUGIN_ROOT . 'wp-filebase.css';
else echo "/* custom */\n";
if(isset($slow)) echo "/* warning: slow */\n";
readfile($file);

echo "/* " . memory_get_usage() . " */";