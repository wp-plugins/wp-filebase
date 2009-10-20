<?php

require('../../../wp-config.php');

wpfilebase_inclib('common');
require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');

$file = WPFilebaseFile::get_file(intval($_GET['fid']));
if($file == null || !$file->current_user_can_access())
	exit;
	
// if no thumbnail, redirect
if(empty($file->file_thumbnail))
{
	header('Location: ' . $file->get_icon_url());
	exit;
}

// send thumbnail
wpfilebase_inclib('download');
wpfilebase_send_file($file->get_thumbnail_path());

?>