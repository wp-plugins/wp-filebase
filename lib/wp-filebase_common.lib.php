<?php

function wpfilebase_update_opt($opt, $value = null)
{
	$options = get_option(WPFB_OPT_NAME);
	$options[$opt] = $value;
	update_option(WPFB_OPT_NAME, $options);
}

function wpfilebase_upload_dir() {
	$upload_path = wpfilebase_get_opt('upload_path');
	if ( trim($upload_path) == '' )
		$upload_path = WP_CONTENT_DIR . '/uploads/filebase';
	$dir = $upload_path;
	// $dir is absolute, $path is (maybe) relative to ABSPATH
	$dir = path_join( ABSPATH, $upload_path );	
	return $dir;
}

function wpfilebase_get_post_url($post_id)
{
	return get_permalink(intval($post_id));	
}

function wpfilebase_get_traffic()
{
	$traffic = wpfilebase_get_opt('traffic_stats');
	$time = intval($traffic['time']);
	$year = intval(date('Y', $time));
	$month = intval(date('m', $time));
	$day = intval(date('z', $time));
	
	$same_year = ($year == intval(date('Y')));
	if(!$same_year || $month != intval(date('m')))
		$traffic['month'] = 0;
	if(!$same_year || $day != intval(date('z')))
		$traffic['today'] = 0;
		
	return $traffic;
}

?>