<?php

require('../../../wp-config.php');

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
wpfilebase_inclib('file');
wpfilebase_send_file($file->get_thumbnail_path());

?>