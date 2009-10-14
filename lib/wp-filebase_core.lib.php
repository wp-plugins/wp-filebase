<?php

function wpfilebase_get_opt($name = null)
{
	$options = get_option(WPFB_OPT_NAME);		
	if(empty($name))
		return $options;
	else
		return isset($options[$name]) ? $options[$name] : null;
}

wp_register_sidebar_widget(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, '_wpfilebase_widget_filelist');
function _wpfilebase_widget_filelist($args)
{
	wpfilebase_inclib('widget');
	return wpfilebase_widget_filelist($args);
}

add_action('template_redirect',	'wpfilebase_redirect');
function wpfilebase_redirect()
{
	global $wpdb;
	
	$file = null;

	if(!empty($_GET['wpfb_dl'])) {
		require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');
		$file = $file = WPFilebaseFile::get_file((int)$_GET['wpfb_dl']);
	} else {
		$dl_url_path = parse_url(get_option('siteurl') . '/' . wpfilebase_get_opt('download_base') . '/', PHP_URL_PATH);
		$pos = strpos($_SERVER['REQUEST_URI'], $dl_url_path);
		if($pos !== false && $pos == 0) {
			$filepath = trim(urldecode(substr($_SERVER['REQUEST_URI'], strlen($dl_url_path))), '/');
			if(!empty($filepath)) {
				require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');
				$file = WPFilebaseFile::get_file_by_path($filepath);
			}
		} else
			return;
	}
	
	if(!empty($file) && is_object($file)) {		
		wpfilebase_inclib('common');	
		$file->download();
		exit;
	}
}


// add filters
add_filter('wp_head',		'wpfilebase_head');
function wpfilebase_head() { echo "\n".'<link rel="stylesheet" type="text/css" href="' . WPFB_PLUGIN_URI . '/wp-filebase.css" />' . "\n"; }

add_filter('ext2type',		'wpfilebase_ext2type_filter');
function wpfilebase_ext2type_filter($arr) {
	$arr['interactive'][] = 'exe';
	$arr['interactive'][] = 'msi';
	return $arr;
}

add_filter('the_content',	'wpfilebase_content_filter', '7');
add_filter('the_excerpt',	'wpfilebase_content_filter', '7');
function wpfilebase_content_filter($content)
{
	if(is_feed())
		return $content;		
		
	// all tags start with '[filebase'
	if(strpos($content, '[filebase:') !== false)
	{
		wpfilebase_inclib('output');
		wpfilebase_parse_content_tags(&$content);
	}	
	
	if(wpfilebase_get_opt('auto_attach_files') && (is_single() || is_page()))
	{
		wpfilebase_inclib('output');
		$content .= wpfilebase_get_post_attachments(true);
	}

    return $content;
}

wp_enqueue_script('wpfilebasejs', '/wp-content/plugins/wp-filebase/wp-filebase.js');

if(is_admin()) {
	wpfilebase_inclib('admin_lite');
}

?>