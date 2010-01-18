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
		$dl_url = parse_url(get_option('home') . '/' . wpfilebase_get_opt('download_base') . '/');
		$dl_url_path = $dl_url['path'];
		$pos = strpos($_SERVER['REQUEST_URI'], $dl_url_path);
		if($pos !== false && $pos == 0) {
			$filepath = trim(urldecode(substr($_SERVER['REQUEST_URI'], strlen($dl_url_path))), '/');
			if(!empty($filepath)) {
				require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');
				$file = WPFilebaseFile::get_file_by_path($filepath);
			}
		} else {	
			// no download, set site visited coockie to disable referer check
			if(empty($_COOKIE[WPFB_OPT_NAME])) {
				@setcookie(WPFB_OPT_NAME, '1');
				$_COOKIE[WPFB_OPT_NAME] = '1';
			}
			return;
		}
	}
	
	if(!empty($file) && is_object($file)) {
		wpfilebase_inclib('common');	
		$file->download();
		exit;
	}
}


// add filters
add_filter('wp_head',		'wpfilebase_head');
function wpfilebase_head() {
	echo "\n".'<link rel="stylesheet" type="text/css" href="' . WPFB_PLUGIN_URI . 'wp-filebase_css.php" />' . "\n";
}

add_filter('ext2type',		'wpfilebase_ext2type_filter');
function wpfilebase_ext2type_filter($arr) {
	$arr['interactive'][] = 'exe';
	$arr['interactive'][] = 'msi';
	return $arr;
}

add_filter('the_content',	'wpfilebase_content_filter', 9); // must be lower than 11 (before do_shortcode)
add_filter('the_excerpt',	'wpfilebase_content_filter', 9);
add_filter('the_content_rss',	'wpfilebase_content_filter', 9);
add_filter('the_excerpt_rss ',	'wpfilebase_content_filter', 9);
function wpfilebase_content_filter($content)
{
	global $id;	
	if(!wpfilebase_get_opt('parse_tags_rss') && is_feed())
		return $content;	
	
		
	// all tags start with '[filebase'
	if(strpos($content, '[filebase') !== false)
	{
		wpfilebase_inclib('output');
		wpfilebase_parse_content_tags(&$content);
	}	
	
	if(!empty($id) && $id > 0 && (is_single() || is_page()))
	{
		if($id == wpfilebase_get_opt('file_browser_post_id'))
		{
			wpfilebase_inclib('output');
			wpfilebase_file_browser(&$content);
		}
	
		if(wpfilebase_get_opt('auto_attach_files'))
		{
			wpfilebase_inclib('output');
			wpfilebase_get_post_attachments(&$content, true);
		}
	}

    return $content;
}

wp_enqueue_script('wpfilebasejs', '/wp-content/plugins/wp-filebase/wp-filebase.js');

if(is_admin()) {
	wpfilebase_inclib('admin_lite');
}

add_action('generate_rewrite_rules', 'wpfilebase_add_rewrite_rules');
function wpfilebase_add_rewrite_rules($wp_rewrite) {	
	$browser_base = wpfilebase_get_opt('file_browser_base');
	$redirect = wpfilebase_get_opt('file_browser_redirect');
	if(empty($browser_base) || empty($redirect))
		return;
    $new_rules = array('^' . $browser_base . '/(.+)$' => $redirect);
    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
}

add_filter('query_vars', 'wpfilebase_queryvars' );
function wpfilebase_queryvars($qvars){
	$qvars[] = 'wpfb_cat_path';
	$qvars[] = 'wpfb_cat';
	$qvars[] = 'wpfb_dl';
    return $qvars;
}

function wpfilebase_mce_addbuttons() {
	if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
		return;
	wpfilebase_inclib('admin_lite');
	add_filter('mce_external_plugins', 'wpfilebase_mce_plugins');
	add_filter('mce_buttons', 'wpfilebase_mce_buttons');
}
add_action('init', 'wpfilebase_mce_addbuttons');

?>