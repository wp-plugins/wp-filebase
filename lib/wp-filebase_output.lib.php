<?php

function wpfilebase_head()
{
	echo "\n".'<link rel="stylesheet" type="text/css" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-filebase/wp-filebase.css" />' . "\n";
}

function wpfilebase_content_filter($content)
{
	if(is_feed())
		return $content;		
		
	// all tags start with '[filebase'
	if(strpos($content, '[filebase:') !== false)
	{
		$replace_filters = array(
			'[filebase:attachments]' => 'wpfilebase_get_post_attachments',
			'[filebase:filelist]' => 'wpfilebase_filelist',
		);
		
		$regexp_filters = array(
			'\[filebase:filelist:cat([0-9]+)\]' => "wpfilebase_filelist('\\1')",
			'\[filebase:fileurl:file([0-9]+)\]' => "WPFilebaseFile::get_file('\\1')->get_url()",
			'\[filebase:file:file([0-9]+)\]' => "WPFilebaseFile::get_file('\\1')->parse_template()",
		);
		
		foreach($replace_filters as $tag => $callback)
		{
			if(strpos($content, $tag) !== false)
				$content = str_replace($tag, $callback(), $content);
		}
		
		foreach($regexp_filters as $pattern => $replace)
		{
			$content = preg_replace("/$pattern/e", $replace, $content);
		}
	}
	
	
	if(wpfilebase_get_opt('auto_attach_files') && (is_single() || is_page()))
	{
		$content .= wpfilebase_get_post_attachments(true);
	}

    return $content;
}


function wpfilebase_redirect()
{
	global $wpdb;
	
	$file = null;

	if(!empty($_GET['wpfb_dl']))
	{
		$file = $file = WPFilebaseFile::get_file((int)$_GET['wpfb_dl']);
	}
	else
	{
		$dl_url_path = parse_url(get_option('siteurl') . '/' . wpfilebase_get_opt('download_base') . '/', PHP_URL_PATH);
		$pos = strpos($_SERVER['REQUEST_URI'], $dl_url_path);
		if($pos !== false && $pos == 0)
		{
			$filepath = substr($_SERVER['REQUEST_URI'], strlen($dl_url_path));
			$filepath = trim(urldecode($filepath), '/');
			if(!empty($filepath))
				$file = WPFilebaseFile::get_file_by_path($filepath);
		}
		else
			return;
	}
	
	if(!empty($file) && is_object($file))
	{		
		if(!$file->file_direct_linking)
		{			
			// if referer check failed, redirect to the file post
			if(!wpfilebase_referer_check())
			{
				wp_redirect(wpfilebase_get_post_url($file->file_post_id));
				exit;
			}
		}
		
		$file->download();
		exit;
	}
}

function wpfilebase_get_post_attachments($check_attached = false)
{
	global $wpdb;	
	static $attached = false;
	
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
	
	$extra_sql .= 'ORDER BY file_display_name ASC';
	
	$files = &WPFilebaseFile::get_files($extra_sql);
	foreach($files as &$file)
	{
		if($file->current_user_can_access())
			$content .= $file->parse_template();
	}
	
	return $content;
}


function wpfilebase_parse_template($tpl)
{
	echo '<!-- [WPFilebase]: parsing template ... -->';

	//escape
	$tpl = str_replace("'", "\\'", $tpl);
	
	// parse if's
	$tpl = preg_replace(
	'/<\!\-\- IF (.+?) \-\->([\s\S]+?)<!-- ENDIF -->/e',
	"'\\' . ( (' . wpfilebase_parse_template_expression('$1') . ') ? (\\'' . wpfilebase_parse_template_ifblock('$2') . '\\') ) . \\''", $tpl);
	
	// parse translation texts
	$tpl = preg_replace('/([^\w])%\\\\\'(.+?)\\\\\'%([^\w])/', '$1\' . __(\'$2\') . \'$3', $tpl);	
	$tpl = preg_replace('/%(\S+?)%/', "' . (\\$$1) . '", $tpl);
	
	// cleanup
	$tpl = str_replace(". ''", "", $tpl);
	
	$tpl = "'$tpl'";
	
	echo '<!-- done! -->';
	
	return $tpl;
}

function wpfilebase_parse_template_expression($exp)
{
	$exp = preg_replace('/%(\S+?)%/', '(\$$1)', $exp);
	$exp = preg_replace('/([^\w])AND([^\w])/', '$1&&$2', $exp);
	$exp = preg_replace('/([^\w])OR([^\w])/', '$1||$2', $exp);
	$exp = preg_replace('/([^\w])NOT([^\w])/', '$1!$2', $exp);
	return $exp;
}

function wpfilebase_parse_template_ifblock($block)
{
	static $s = '<!-- ELSE -->';
	static $r = '\') : (\'';
	if(strpos($block, $s) === false)
		$block .= $r;
	else
		$block = str_replace($s, $r, $block);
	
	// unescape "
	$block = str_replace('\"', '"', $block);
	
	return $block;
}


function wpfilebase_check_template($tpl)
{	
	$result = array('error' => false, 'msg' => '', 'line' => '');
		
	$tpl = 'return (' . $tpl . ');';
	
	if(!@eval($tpl))
	{
		$result['error'] = true;
		
		$err = error_get_last();
		if(!empty($err))
		{
			$result['msg'] = $err['message'];
			$result['line'] = $err['line'];
		}
	}
	
	return $result;
}

function wpfilebase_parent_cat_seletion_tree($parent_cat, $edit_item = null, $deepth = 0)
{
	if ( !is_object($parent_cat) ) 
		$parent_cat = &WPFilebaseCategory::get_category($parent_cat);
	
	if(empty($edit_item))
		$edit_item_id = -1;
	else
		$edit_item_id = $edit_item->get_id();
	
	// dont list the cat item or its childs
	if(is_object($edit_item) && $edit_item->is_category && $parent_cat->cat_id == $edit_item_id)
		return '';
	
	$selected = (is_object($edit_item) && $edit_item->get_parent_id() == $parent_cat->cat_id);
	
	$parent_cat_list .= '<option value="' . $parent_cat->cat_id . '"' . (  $selected ? ' selected="selected"' : '' ) . '>' . str_repeat('&nbsp;&nbsp; ', $deepth) . attribute_escape($parent_cat->cat_name) . '</option>';
	
	if(isset($parent_cat->cat_childs))
	{
		foreach($parent_cat->cat_childs as $child_cat_id) {
			$parent_cat_list .= wpfilebase_parent_cat_seletion_tree( $child_cat_id, $edit_item, $deepth + 1);
		}
	}
	
	return $parent_cat_list;
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

function wpfilebase_progress_bar($progress, $label)
{
	$progress = round(100 * $progress);
	echo "<div class='wpfilebase-progress'><div class='progress'><div class='bar' style='width: $progress%'></div></div><div class='label'><strong>$progress %</strong> ($label)</div></div>";
}

?>