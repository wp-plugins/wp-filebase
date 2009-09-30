<?php

function wpfilebase_get_opt($name = null)
{
	$options = get_option(WPFB_OPT_NAME);		
	if(empty($name))
		return $options;
	else
		return $options[$name];
}

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

function wpfilebase_array_remove_empty($arr)
{
    $narr = array();

    while(list($key, $val) = each($arr))
	{
        if (is_array($val)){
            $val = wpfilebase_array_remove_empty($val);
            if (count($val)!=0)
                $narr[$key] = $val;
        } else {
            if (!empty($val) || is_int($val))
                $narr[$key] = $val;
        }
    }
    unset($arr);
    return $narr;
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

function wpfilebase_referer_check()
{
	if(empty($_SERVER['HTTP_REFERER']))
		return ((bool)wpfilebase_get_opt('accept_empty_referers'));
		
	$referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);			
	
	$allowed_referers = explode("\n", wpfilebase_get_opt('allowed_referers'));
	$allowed_referers[] = get_option('siteurl');
	
	foreach($allowed_referers as $ar)
	{
		if( !empty($ar) && (@strpos($referer, $ar) !== false || @strpos($referer, parse_url($ar, PHP_URL_HOST)) !== false) )
			return true;
	}
	
	return false;
}

function wpfilebase_extension_is_allowed($ext)
{
	static $srv_script_exts = array('php', 'php3', 'php4', 'php5', 'phtml', 'cgi', 'pl', 'asp', 'py', 'aspx');	
	
	$ext = trim($ext, '.');
	return (wpfilebase_get_opt('allow_srv_script_upload') || !in_array($ext, $srv_script_exts));
}

// add some extensions
function wpfilebase_ext2type_filter($arr)
{
	$arr['interactive'][] = 'exe';
	$arr['interactive'][] = 'msi';
	return $arr;
}


function wpfilebase_admin_menu()
{	
	wpfilebase_inclib('admin');
	
	add_options_page(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, 'manage_options', 'wpfilebase', 'wpfilebase_admin_options' );	
	add_management_page(WPFB_PLUGIN_NAME, WPFB_PLUGIN_NAME, 'manage_categories', 'wpfilebase', 'wpfilebase_admin_manage' );
	
	wpfilebase_mce_init();
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


function wpfilebase_add_traffic($bytes)
{
	$traffic = wpfilebase_get_traffic();
	$traffic['month'] = $traffic['month'] + $bytes;
	$traffic['today'] = $traffic['today'] + $bytes;	
	$traffic['time'] = time();
	wpfilebase_update_opt('traffic_stats', $traffic);
}

function wpfilebase_check_traffic($file_size)
{
	$traffic = wpfilebase_get_traffic();
	
	$limit_month = (wpfilebase_get_opt('traffic_month') * 1048576);
	$limit_day = (wpfilebase_get_opt('traffic_day') * 1073741824);
	
	return ( ($limit_month == 0 || ($traffic['month'] + $file_size) < $limit_month) && ($limit_day == 0 || ($traffic['today'] + $file_size) < $limit_day) );
}

?>