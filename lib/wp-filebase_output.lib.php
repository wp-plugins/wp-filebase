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
		'\[filebase:fileurl:file([0-9]+)\]' => "WPFilebaseFile::get_file('\\1')->get_url()",
		'\[filebase:file:file([0-9]+)\]' => "WPFilebaseFile::get_file('\\1')->parse_template()",
	);
	
	require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');
	
	foreach($replace_filters as $tag => &$callback)
	{
		if(strpos($content, $tag) !== false)
			$content = str_replace($tag, $callback(), $content);
	}
	
	foreach($regexp_filters as $pattern => $replace)
		$content = preg_replace("/$pattern/e", $replace, $content);
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
	$results = &$wpdb->get_results("SELECT * FROM " . $wpdb->wpfilebase_files . " WHERE file_post_id = $post_id ORDER BY file_display_name ASC");

	if(!empty($results) && count($results) > 0)
	{
		foreach($results as $file_row)
		{
			$file = new WPFilebaseFile($file_row);
			if($file->current_user_can_access())
				$content .= $file->parse_template();
		}
		
		$attached = true;
	}
	
	return $content;
}

function wpfilebase_filelist($cat=0)
{
	global $wpdb;
	
	require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');
	
	$cat = (int)$cat;
	$content = '';
	$extra_sql = '';
	if($cat > 0)
	{
		// check permission
		if(!WPFilebaseCategory::get_category($cat)->current_user_can_access())
			return '';
			
		$extra_sql .= 'WHERE file_category = ' . (int)$cat . ' ';
	}
	
	$sortby = wpfilebase_get_opt('filelist_sorting');
	if(!$sortby || $sortby == '')
		$sortby = 'file_display_name';
	$sortby = $wpdb->escape($sortby);
	$sortdir = wpfilebase_get_opt('filelist_sorting_dir') ? 'DESC' : 'ASC';	
	$extra_sql .= "ORDER BY `$sortby` $sortdir";
	
	$files = &WPFilebaseFile::get_files($extra_sql);
	foreach($files as &$file)
	{
		if($file->current_user_can_access())
			$content .= $file->parse_template();
	}
	
	return $content;
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