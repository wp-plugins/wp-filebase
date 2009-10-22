<?php

wpfilebase_inclib('common');
	require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');

function wpfilebase_parse_content_tags(&$content)
{
	static $replace_filters = array(
		'[filebase:attachments]' => 'wpfilebase_get_post_attachments',
		'[filebase:filelist]' => 'wpfilebase_filelist',
	);
	
	static $regexp_filters = array(
		'\[filebase:filelist:cat([0-9]+)\]' => "wpfilebase_filelist('\\1')",
		'\[filebase:fileurl:file([0-9]+)\]' => "wpfilebase_get_file_url('\\1')",
		'\[filebase:file:file([0-9]+)\]' 	=> "wpfilebase_file_generate_template('\\1')",
	);
	
	foreach($replace_filters as $tag => /* & PHP 4 compability */ $callback)
	{
		if(strpos($content, $tag) !== false)
			$content = str_replace($tag, $callback(), $content);
	}
	
	foreach($regexp_filters as $pattern => $replace)
		$content = preg_replace("/$pattern/e", $replace, $content);
		
	// new tag parser
	/*
	$offset = 0;
	while(($tag_start = strpos('[filebase:', $content, $offset)) !== false)
	{
		$offset = $tag_start + 10; // len of '[filebase:'
		$tag_end = strpos(']', $content, $offset);
		if($tag_end === false)
			continue;
		$tag_len = $tag_end - $tag_start;
		$tag = substr($content, $offset, $tag_len);
	}
	*/
}

function wpfilebase_get_file_url($file_id)
{
	$file = WPFilebaseFile::get_file($file_id);
	return is_object($file) ? $file->get_url() : '';
}

function wpfilebase_file_generate_template($file_id)
{
	$file = WPFilebaseFile::get_file($file_id);
	return is_object($file) ? $file->generate_template() : '';
}


function wpfilebase_get_post_attachments($check_attached = false)
{
	global $wpdb;	
	static $attached = false;
	
	require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');
	
	if($check_attached && $attached)
		return '';
	
	$content = '';	
	$post_id = (int)get_the_ID();
	$files = &WPFilebaseFile::get_files("WHERE file_post_id = $post_id " . wpfilebase_get_filelist_sorting_sql());
	if(count($files) > 0)
	{
		$attached = true;
		foreach($files as $file)
		{
			if($file->current_user_can_access())
				$content .= $file->generate_template();
		}
	}
	
	return $content;
}

function wpfilebase_filelist($cat_id=-1)
{
	global $wpdb;
	
	require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');
	
	$cat_id = (int)$cat_id;
	$content = '';
	$extra_sql = '';
	if($cat_id > 0)
	{
		$cat = WPFilebaseCategory::get_category($cat_id);
		// check permission
		if($cat && !$cat->current_user_can_access())
			return '';
			
		$extra_sql .= 'WHERE file_category = ' . (int)$cat_id . ' ';
	} elseif($cat_id == 0) {
		$extra_sql .= 'WHERE file_category = 0 ';
	} else {
		// load all cats
		WPFilebaseCategory::get_categories();
	}
	
	$extra_sql .= wpfilebase_get_filelist_sorting_sql();
	
	$files = &WPFilebaseFile::get_files($extra_sql);
	foreach($files as /* & PHP 4 compability */ $file)
	{
		if($file->current_user_can_access())
			$content .= $file->generate_template();
	}
	
	return $content;
}

function wpfilebase_get_filelist_sorting_sql()
{
	global $wpdb;
	$sortby = wpfilebase_get_opt('filelist_sorting');
	if(empty($sortby))
		$sortby = 'file_display_name';
	$sortby = $wpdb->escape($sortby);
	$sortdir = wpfilebase_get_opt('filelist_sorting_dir') ? 'DESC' : 'ASC';	
	return "ORDER BY `$sortby` $sortdir";
}


function wpfilebase_parse_selected_options($opt_name, $sel_tags, $uris=false)
{
	$outarr = array();
	$opts = explode("\n", wpfilebase_get_opt($opt_name));	
	if(!is_array($sel_tags))
		$sel_tags = explode('|', $sel_tags);
	
	for($i = 0; $i < count($opts); $i++)
	{
		$opt = explode('|', trim($opts[$i]));
		if(in_array($opt[1], $sel_tags)) {
			$o = wp_specialchars(ltrim($opt[0], '*'));;
			if($uris && isset($opt[2]))
				$o = '<a href="' . attribute_escape($opt[2]) . '" target="_blank">' . $o . '</a>';
			$outarr[] = $o;
		}
	}	
	return implode(', ', $outarr);
}

function wpfilebase_format_filesize($file_size)
{
	if($file_size <= 1024) {
		$unit = 'B';
	} elseif($file_size < 1048576) {
		$file_size /= 1024;
		$unit = 'KiB';
	} elseif($file_size < 1073741824) {
		$file_size /= 1048576;
		$unit = 'MiB';
	} else {
		$file_size /= 1073741824;
		$unit = 'GiB';
	}
	
	return sprintf('%01.1f %s', $file_size, $unit);
}

function wpfilebase_filename2title($ft, $remove_ext=true)
{
	if($remove_ext) {
		$p = strrpos($ft, '.');
		if($p !== false && $p != 0)
			$ft = substr($ft, 0, $p);
	}
	$ft = str_replace(array('.', '_'), ' ', $ft);
	$ft = ucwords($ft);
	return $ft;
}

?>