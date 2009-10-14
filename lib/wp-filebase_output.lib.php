<?php

wpfilebase_inclib('common');

function wpfilebase_parse_content_tags(&$content)
{
	static $replace_filters = array(
		'[filebase:attachments]' => 'wpfilebase_get_post_attachments',
		'[filebase:filelist]' => 'wpfilebase_filelist',
	);
	
	static $regexp_filters = array(
		'\[filebase:filelist:cat([0-9]+)\]' => "wpfilebase_filelist('\\1')",
		'\[filebase:fileurl:file([0-9]+)\]' => "wpfilebase_get_file_url('\\1')",
		'\[filebase:file:file([0-9]+)\]' 	=> "wpfilebase_file_parse_template('\\1')",
	);
	
	require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');
	
	foreach($replace_filters as $tag => /* & PHP 4 compability */ $callback)
	{
		if(strpos($content, $tag) !== false)
			$content = str_replace($tag, $callback(), $content);
	}
	
	foreach($regexp_filters as $pattern => $replace)
		$content = preg_replace("/$pattern/e", $replace, $content);
}

function wpfilebase_get_file_url($file_id)
{
	$file = WPFilebaseFile::get_file($file_id);
	return is_object($file) ? $file->get_url() : '';
}

function wpfilebase_file_parse_template($file_id)
{
	$file = WPFilebaseFile::get_file($file_id);
	return is_object($file) ? $file->parse_template() : '';
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
				$content .= $file->parse_template();
		}
	}
	
	return $content;
}

function wpfilebase_filelist($cat_id=0)
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
	}
	
	$extra_sql .= wpfilebase_get_filelist_sorting_sql();
	
	$files = &WPFilebaseFile::get_files($extra_sql);
	foreach($files as /* & PHP 4 compability */ $file)
	{
		if($file->current_user_can_access())
			$content .= $file->parse_template();
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


function wpfilebase_get_tag_names($opt_name, $opt_tags)
{
	$opts = wpfilebase_parse_options($opt_name);	
	$opt_tags = explode("|", $opt_tags);
	$out = '';
	for($i = 0; $i < count($opt_tags); $i++)
	{
		$opt_tags[$i] = trim($opt_tags[$i]);
		$out.= (empty($out) ? '' : ', ') . $opts[$opt_tags[$i]];
	}	
	return $out;
}

function wpfilebase_parse_options($opt_name)
{
	$opts = explode("\n", wpfilebase_get_opt($opt_name));	
	$out = array();	
	for($i = 0; $i < count($opts); $i++)
	{
		$opts[$i] = trim($opts[$i]);
		$opt = explode("|", $opts[$i]);
		if($opt[0]{0} == '*')
			$opt[0] = substr($opt[0], 1);
		$out[$opt[1]] = $opt[0];
	}	
	return $out;
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

?>