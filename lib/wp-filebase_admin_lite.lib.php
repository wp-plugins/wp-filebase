<?php

function wpfilebase_admin_header()
{
	wpfilebase_head();	
	echo '<script type="text/javascript" src="' . WPFB_PLUGIN_URI . 'wp-filebase_admin.js"></script>';
	wpfilebase_version_update_check();
}
add_action('admin_head', 'wpfilebase_admin_header', 10);


function wpfilebase_admin_menu()
{	
	add_options_page(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, 'manage_options', 'wpfilebase', '_wpfilebase_admin_options' );	
	add_management_page(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, 'manage_categories', 'wpfilebase', '_wpfilebase_admin_manage' );
}
add_action('admin_menu', 'wpfilebase_admin_menu');

function wpfilebase_mce_plugins($plugins)
{
	$plugins[WPFB_OPT_NAME] = WPFB_PLUGIN_URI . 'mce/editor_plugin.js';
	return $plugins;
}

function wpfilebase_mce_buttons($buttons) {
	array_push($buttons, 'separator', WPFB_OPT_NAME);
	return $buttons;
}

function wpfilebase_mce_addbuttons()
{
	if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
		return;
	 
	add_filter('mce_external_plugins', 'wpfilebase_mce_plugins');
	add_filter('mce_buttons', 'wpfilebase_mce_buttons');
}
add_action('init', 'wpfilebase_mce_addbuttons');

function _wpfilebase_admin_options()
{
	wpfilebase_inclib('admin_gui');
	wpfilebase_admin_options();
}

function _wpfilebase_admin_manage()
{
	wpfilebase_inclib('admin_gui');
	wpfilebase_admin_manage();
}

function _wpfilebase_widget_filelist_control()
{
	wpfilebase_inclib('widget');
	wpfilebase_widget_filelist_control();
}
wp_register_widget_control(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, '_wpfilebase_widget_filelist_control');

function wpfilebase_version_update_check()
{
	$ver = wpfilebase_get_opt('version');
	if($ver != WPFB_VERSION)
	{
		wpfilebase_activate();
		echo '<!-- WPFilebase: version changed -->';
	}
}

?>