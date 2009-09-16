<?php
/*
Plugin Name: WP-Filebase
Plugin URI: http://fabi.me/wp-filebase
Description: A powerful download manager supporting file categories, thumbnails, traffic/bit rate limits and more.
Author: Fabian Schlieper
Version: 0.1.0.0
Author URI: http://fabi.me/
*/

// db settings
if(isset($wpdb))
{
	$wpdb->wpfilebase_cats = $wpdb->prefix . 'wpfb_cats';
	$wpdb->wpfilebase_files = $wpdb->prefix . 'wpfb_files';
}

define('WPFB_PLUGIN_ROOT', dirname(__FILE__) . '/');
define('WPFB_PLUGIN_URI', str_replace(ABSPATH, get_settings('siteurl') . '/', WPFB_PLUGIN_ROOT));
define('WPFB_OPT_NAME', 'wpfilebase');
define('WPFB_PLUGIN_NAME', 'WP-Filebase');

define('WPFB_PERM_FILE', 0777);
define('WPFB_PERM_DIR', 0777);

function wpfilebase_inclib($lib) { return @include_once(WPFB_PLUGIN_ROOT . 'lib/wp-filebase_' . $lib . '.lib.php'); }

require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');
require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_file.php');
require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_category.php');
require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_widget.php');

// load libraries
wpfilebase_inclib('common');
wpfilebase_inclib('output');

register_activation_hook(__FILE__, 'wpfilebase_activate');

// add actions
add_action('plugins_loaded',	'wpfilebase_widget_init');
add_action('template_redirect',	'wpfilebase_redirect');
add_action('admin_menu',		'wpfilebase_admin_menu');
add_action('admin_head',		'wpfilebase_admin_header', 10);

// add filters
add_filter('wp_head',		'wpfilebase_head');
add_filter('the_content',	'wpfilebase_content_filter', '7');
add_filter('the_excerpt',	'wpfilebase_content_filter', '7');
add_filter('ext2type',		'wpfilebase_ext2type_filter');


wp_enqueue_script('wpfilebasejs', '/wp-content/plugins/wp-filebase/wp-filebase.js');


function wpfilebase_activate() {
	wpfilebase_inclib('setup');
	wpfilebase_inclib('admin');	
	
	wpfilebase_add_options();
	wpfilebase_create_tables();
}

/*
todo: 3 listtypen: flach, baum, ajax baum
archive lister
per-page download list
folder struktur (categorien, unterkategorien)
*/

?>