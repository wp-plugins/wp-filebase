<?php

wpfilebase_inclib('common');
wpfilebase_inclib('admin_lite');
require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');

function wpfilebase_options()
{
	$multiple_entries_desc = __('One entry per line. Seperate the shown name and a short tag (not longer than 8 characters) with \'|\'.<br />All lines beginning with \'*\' are selected by default.');
	$multiple_line_desc = __('One entry per line.');
	$bitrate_desc = __('Limits the maximum tranfer rate for downloads. 0 = unlimited');
	$traffic_desc = __('Limits the maximum data traffic. 0 = unlimited');
	
	return array (
	
	'upload_path'			=> array('default' => get_option('upload_path') . '/filebase', 'title' => __('Upload Path'), 'type' => 'text', 'class' => 'code', 'size' => 65),

	'thumbnail_size'		=> array('default' => 120, 'title' => __('Thumbnail size'), 'type' => 'number', 'class' => 'num', 'size' => 8),
	
	'auto_attach_files' 	=> array('default' => true,'title' => __('Show attached files'), 'type' => 'checkbox', 'desc' => __('If enabled, all associated files are listed below an article')),
	'filelist_sorting'		=> array('default' => 'file_display_name', 'title' => __('Default sorting'), 'type' => 'select', 'desc' => __('The file property lists are sorted by'), 'options' => wpfilebase_sorting_options()),
	'filelist_sorting_dir'	=> array('default' => 0, 'title' => __('Sorting direction'), 'type' => 'select', 'desc' => __('The sorting direction of file lists'), 'options' => array(0 => __('Ascending'), 1 => __('Descending'))),
	
	'template_file'			=> array('default' =>
<<<TPLFILE
<div class="wpfilebase-attachment">
 <div class="wpfilebase-fileicon"><a href="%file_url%" title="Download %file_display_name%"><img align="middle" src="%file_icon_url%" /></a></div>
 <div class="wpfilebase-rightcol">
  <div class="wpfilebase-filetitle">
   <a href="%file_url%" title="Download %file_display_name%">%file_display_name%</a>
   %file_name%
   <!-- IF %file_version% -->%'Version:'% %file_version%
   <!-- ENDIF --><!-- IF %file_post_id% AND get_the_ID() != %file_post_id% --><a href="%file_post_url%" class="wpfilebase-postlink">%'View post'%</a><!-- ENDIF -->
  </div>
  <div class="wpfilebase-filedetails" id="wpfilebase-filedetails%uid%" style="display: none;">
  %file_description%
  <table border="0" width="220">
   <!-- IF %file_languages% --><tr><th>%'Languages'%:</th><td>%file_languages%</td></tr><!-- ENDIF -->
   <!-- IF %file_author% --><tr><th>%'Author'%:</th><td>%file_author%</td></tr><!-- ENDIF -->
   <!-- IF %file_platforms% --><tr><th>%'Platforms'%:</th><td>%file_platforms%</td></tr><!-- ENDIF -->
   <!-- IF %file_requirements% --><tr><th>%'Requirements'%:</th><td>%file_requirements%</td></tr><!-- ENDIF -->
   <!-- IF %file_category% --><tr><th>%'Category:'%</th><td>%file_category%</td></tr><!-- ENDIF -->
   <!-- IF %file_license% --><tr><th>%'License'%:</th><td>%file_license%</td></tr><!-- ENDIF -->
   <tr><th>%'Date'%:</th><td>%file_date%</td></tr>
   <!-- <tr><th>%'MD5 Hash'%:</th><td><small>%file_hash%</small></td></tr> -->
  </table>
  </div>
 </div>
 <div class="wpfilebase-fileinfo">
  %file_size%
  %file_hits% %'Downloads'%
  <a href="#" onclick="return wpfilebase_filedetails(%uid%);">%'Details'%...</a>
 </div>
 <div style="clear: both;"></div>
</div>
TPLFILE
, 'title' => __('Attachment Template'), 'type' => 'textarea', 'desc' => (wpfilebase_template_fields_select('template_file') . '<br />' . __('The template for attachments')), 'class' => 'code'),

	'languages'				=> array('default' => "English|en\nDeutsch|de", 'title' => __('Languages'), 'type' => 'textarea', 'desc' => &$multiple_entries_desc),
	'platforms'				=> array('default' => "Windows 95|win95\n*Windows 98|win98\n*Windows 2000|win2k\n*Windows XP|winxp\n*Windows Vista|vista\n*Windows 7|win7\nLinux|linux\nMac OS X|mac", 'title' => __('Platforms'), 'type' => 'textarea', 'desc' => &$multiple_entries_desc),	
	'licenses'				=> array('default' => "*Freeware|free\nShareware|share\nGNU General Public License|gpl\nGNU Lesser General Public License|lgpl\nGNU Affero General Public License|agpl", 'title' => __('Licenses'), 'type' => 'textarea', 'desc' => &$multiple_entries_desc),
	'requirements'			=> array('default' => ".NET Framework 2.0|.net2\n.NET Framework 3.0|.net3\n.NET Framework 3.5|.net35", 'title' => __('Requirements'), 'type' => 'textarea', 'desc' => &$multiple_entries_desc),
	
	'allowed_referers' 		=> array('default' => '', 'title' => __('Allowed referers'), 'type' => 'textarea', 'desc' => 'Sites with matching URLs can link to files directly.<br />'.$multiple_line_desc),
	'accept_empty_referers'	=> array('default' => true, 'title' => __('Accept empty referers'), 'type' => 'checkbox', 'desc' => 'If enabled, direct-link-protected files can be downloaded when the referer is empty (i.e. user entered file url in address bar or browser does not send referers)'),
	
	'bitrate_unregistered'	=> array('default' => 0, 'title' => __('Bit rate limit for guests'), 'type' => 'number', 'unit' => 'KiB/Sec', 'desc' => &$bitrate_desc),
	'bitrate_registered'	=> array('default' => 0, 'title' => __('Bit rate limit for registered users'), 'type' => 'number', 'unit' => 'KiB/Sec', 'desc' => &$bitrate_desc),	
	'traffic_day'			=> array('default' => 0, 'title' => __('Daily traffic limit'), 'type' => 'number', 'unit' => 'MiB', 'desc' => &$traffic_desc),
	'traffic_month'			=> array('default' => 0, 'title' => __('Monthly traffic limit'), 'type' => 'number', 'unit' => 'GiB', 'desc' => &$traffic_desc),
	'traffic_exceeded_msg'	=> array('default' => __('Traffic limit exceeded! Please try again later.'), 'title' => __('Traffic exceeded message'), 'type' => 'text', 'size' => 65),
	'file_offline_msg'		=> array('default' => __('This file is currently offline.'), 'title' => __('File offline message'), 'type' => 'text', 'size' => 65),
	
	'download_base'			=> array('default' => 'download', 'title' => __('Download URL base'), 'type' => 'text', 'desc' => __('The download url base. (Only used when Permalinks are enabled.)')),
	'force_download'		=> array('default' => false, 'title' => __('Always force download'), 'type' => 'checkbox', 'desc' => __('If enabled files that can be viewed in the browser (linke images, PDF cocuments and videos) can only be downloaded (no streaming).')),
	'ignore_admin_dls'		=> array('default' => true, 'title' => __('Ignore downloads by admins'), 'type' => 'checkbox'),
	
	'allow_srv_script_upload'	=> array('default' => false, 'title' => __('Allow script upload'), 'type' => 'checkbox', 'desc' => __('If you enable this, scripts like PHP or CGI can be uploaded. <b>WARNING:</b> Enabling script uploads is a <b>security risk</b>!')),
	
	'dlclick_js'			=> array('default' =>
<<<JS
if(typeof pageTracker == 'object') {
	pageTracker._trackPageview(file_url); // new google analytics tracker
} else if(typeof urchinTracker == 'function') {	
	urchinTracker(file_url); // old google analytics tracker
}
JS
, 'title' => __('Download JavaScript'), 'type' => 'textarea', 'desc' => __('Here you can enter JavaScript Code which is executed when a user clicks on file download link. The following variables can be used: <i>file_id</i>: the ID of the file, <i>file_url</i>: the clicked download url'), 'class' => 'code'),

	//'max_dls_per_ip'			=> array('default' => 10, 'title' => __('Maximum downloads'), 'type' => 'number', 'unit' => 'per file, per IP Address', 'desc' => 'Maximum number of downloads of a file allowed for an IP Address. 0 = unlimited'),
	//'archive_lister'			=> array('default' => false, 'title' => __('Archive lister'), 'type' => 'checkbox', 'desc' => __('Uploaded files are scanned for archives')),
	//'enable_ratings'			=> array('default' => false, 'title' => __('Ratings'), 'type' => 'checkbox', 'desc' => ''),
	);
}


function wpfilebase_template_fields_desc()
{
	return array(	
	'file_name'				=> 'Name of the file',
	'file_size'				=> 'Formatted file size',
	'file_date'				=> 'Formatted file date',
	'file_thumbnail'		=> 'Name of the thumbnail file',
	'file_display_name'		=> 'Title',
	'file_description'		=> 'Short description',
	'file_version'			=> 'File version',
	'file_author'			=> 'Author',
	'file_languages'		=> 'Supported languages',
	'file_platforms'		=> 'Supported platforms (operating systems)',
	'file_requirements'		=> 'Requirements to use this file',
	'file_license'			=> 'License',
	'file_required_level'	=> 'The minimum user level to download this file (-1 = guest, 0 = Subscriber ...)',
	'file_offline'			=> '1 if file is offline, otherwise 0',
	'file_direct_linking'	=> '1 if direct linking is allowed, otherwise 0',
	'file_category'			=> 'The category name',
	//'file_update_of'		=>
	'file_post_id'			=> 'ID of the post/page this file belongs to',
	'file_added_by'			=> 'User ID of the uploader',
	'file_hits'				=> 'How many times this file has been downloaded.',
	//'file_ratings'			=>
	//'file_rating_sum'		=>
	'file_last_dl_ip'		=> 'IP Address of the last downloader',
	'file_last_dl_time'		=> 'Time of the last download',
	
	'file_url'				=> 'Download URL',
	'file_post_url'			=> 'URL of the post/page this file belongs to',
	'file_icon_url'			=> 'URL of the thumbnail or icon',
	'file_path'				=> 'Category path and file name (e.g cat1/cat2/file.ext)',
	
	'uid'					=> 'A unique ID number to indetify elements within a template',
	);
}

function wpfilebase_sorting_options()
{
	return array(	
	'file_name'				=> 'Name of the file',
	'file_size'				=> 'Formatted file size',
	'file_date'				=> 'Formatted file date',
	'file_display_name'		=> 'Title',
	'file_description'		=> 'Short description',
	'file_version'			=> 'File version',
	'file_author'			=> 'Author',
	'file_license'			=> 'License',
	'file_required_level'	=> 'The minimum user level to download this file (-1 = guest, 0 = Subscriber ...)',
	'file_offline'			=> 'Offline > Online',
	'file_direct_linking'	=> 'Direct linking > redirect to post',
	'file_category'			=> 'Category',
	'file_post_id'			=> 'ID of the post/page this file belongs to',
	'file_added_by'			=> 'User ID of the uploader',
	'file_hits'				=> 'How many times this file has been downloaded.',
	'file_last_dl_time'		=> 'Time of the last download',
	);
}

function wpfilebase_template_fields_select($input, $short=false)
{
	$out = __('Add template field:') . '<select name="_wpfb_tpl_fields" onchange="wpfilebaseAddTplField(this, \'' . $input . '\')"><option value=""></option>';	
	foreach(wpfilebase_template_fields_desc() as $tag => $desc)
	{
		$out .= '<option value="' . $tag . '" title="'.$desc.'">' . $tag . ($short ? '' : ' (' . __($desc) . ')') . '</option>';
	}
	$out .= '</select>';
	return $out;
}


function wpfilebase_insert_category($catarr)
{
	global $wpdb;
	$cat_defaults = array('cat_id' => 0, 'cat_name' => '', 'cat_description' => '', 'cat_parent' => 0, 'cat_folder' => '');
	$catarr = wp_parse_args($catarr, $cat_defaults);
	extract($catarr, EXTR_SKIP);

	$cat_id = intval($cat_id);
	$cat_parent = intval($cat_parent);
	
	// Are we updating or creating?
	$update = ($cat_id > 0);
	if ($update)
		$cat = WPFilebaseCategory::get_category($cat_id);
	else
		$cat = &new WPFilebaseCategory(array('cat_id' => 0));
	
	$cat->cat_name = trim($cat_name);
	$cat->cat_description = trim($cat_description);
	$cat->cat_folder = trim($cat_folder);
		
	// permission
	$cat_members_only = !empty($cat_members_only);
	$cat->cat_required_level = $cat_members_only ? (min(max(intval($cat_required_level), 0), 10) + 1) : 0;
	$cat_child_apply_perm = $update && !empty($cat_child_apply_perm);
	if($cat_child_apply_perm)
	{
		//WPFilebaseCategory::get_categories();
		// apply permissions to all child files
		$files = $cat->get_files();
		foreach($files as /* & PHP 4 compability */ $file)
		{
			$file->file_required_level = $cat->cat_required_level;
			$file->db_save();
		}
	}
	
	if (empty($cat->cat_name))
		return array( 'error' => __('You did not enter a category name.') );
	if (empty($cat->cat_folder))
		return array( 'error' => __('You did not enter a category folder name.') );
		
	if(!preg_match('/^[0-9a-z-_.+,]+$/i', $cat->cat_folder))
		return array( 'error' => __('The category folder name contains invalid characters.') );
		
	// handle parent cat
	if($cat_parent <= 0 || $cat_parent == $cat_id) {
		$cat_parent = 0;
		$pcat = null;
	} else {
		$pcat = WPFilebaseCategory::get_category($cat_parent);
		if($pcat == null || ($update && $cat->is_ancestor_of($pcat)))
			$cat_parent = 0;
	}
	
	// if create new and dir already exists, cancel
	if(!$update)
	{
		$prev_parent = $cat->cat_parent;
		$cat->cat_parent = $cat_parent;
		if( @file_exists($cat->get_path()))
		{			
			return array( 'error' => sprintf( __( 'The directory %s already exists!' ), $cat->get_path() ) );
		}
		$cat->cat_parent = $prev_parent;
	}
	
	$result = $cat->change_category($cat_parent);
	if(!empty($result['error']))
		return $result;	
	
	// save into db
	$result = $cat->db_save();	
	if(!empty($result['error']))
		return $result;		
	$cat_id = (int)$result['cat_id'];	
	
	return array( 'error' => false, 'cat_id' => $cat_id);
}


function wpfilebase_insert_file($filearr)
{	
	extract($filearr, EXTR_SKIP);
	
	$file_id = isset($file_id) ? (int)$file_id : 0;
	
	// are we updating or creating?
	$update = ( !empty($file_id) && $file_id > 0 && (($file = &WPFilebaseFile::get_file($file_id)) != null) );
	if(!$update)
		$file = new WPFilebaseFile(array('file_id' => 0));
	
	// are we uploading a file?
	$upload = (@is_uploaded_file($file_upload['tmp_name']) && !empty($file_upload['name']));
	
	// are we uploading a thumbnail?
	$upload_thumb = ( @is_uploaded_file($file_upload_thumb['tmp_name']) && file_is_valid_image($file_upload_thumb['tmp_name']));
		
	// handle category
	$file_category = (int)$file_category;
	if ($file_category > 0 && WPFilebaseCategory::get_category($file_category) == null)
		$file_category = 0;
	
	if($update && $file->file_category != $file_category)
	{
		$result = $file->change_category($file_category);
		if(!empty($result['error']))
			return $result;
	}
	
	
	// delete thumbnail if user wants to
	if(!empty($file_delete_thumb))
		$file->delete_thumbnail();
	
	// if there is an uploaded file 
	if($upload)
	{		
		// if we update, delete the old file
		if ($update)
			$file->delete();
		
		// set the new filename
		$file->file_name = $file_upload['name'];
		
		// check extension
		if(!wpfilebase_extension_is_allowed($file->get_extension()))
		{
			@unlink($file_upload['tmp_name']);
			return array( 'error' => sprintf( __( 'The file extension <b>%s</b> is forbidden!' ), $file->get_extension() ) );
		}
		
		// if create new and file already exists, cancel
		if(!$update && @file_exists($file->get_path()))
		{			
			return array( 'error' => sprintf( __( 'File %s already exists. You have to delete it first!' ), $file->get_path() ) );
		}

		// move uploaded file to the right place
		if(!@move_uploaded_file($file_upload['tmp_name'], $file->get_path()) || !@file_exists($file->get_path()))
			return array( 'error' => sprintf( __( 'Unable to move file %s! Is the upload directory writeable?' ), $file->file_name ) );

		// set permissions
		@chmod ($file->get_path(), octdec(WPFB_PERM_FILE));
		
		// get file info
		$file->file_size = (int)filesize($file->get_path());
		$file->file_hash = md5_file($file->get_path());

		// check if the file is an image
		if(empty($file->file_thumbnail) && !$upload_thumb && ($file->get_extension() == '.bmp' || file_is_valid_image($file->get_path())))
			$file->create_thumbnail();		
		
	} elseif (!$update) {
		return array( 'error' => __('No file was uploaded.') );
	}

	// set display name
	$file->file_display_name = $file_display_name;
	if (empty($file->file_display_name)) {
		$ft = $file->file_name;
		$ft = substr($ft, 0, strrpos($ft, '.'));
		$ft = str_replace(array('.', '_'), ' ', $ft);
		//$ft{0} = strtoupper($ft{0});
		$ft = ucwords($ft);
		$file->file_display_name = $ft;
	}
	
	$file_language = $file_platform = $file_requirement = '';
	if(!empty($file_languages))
		$file_language = implode('|', $file_languages);
	if(!empty($file_platforms))
		$file_platform = implode('|', $file_platforms);
	if(!empty($file_requirements))
		$file_requirement = implode('|', $file_requirements);
		
	$file_offline = empty($file_offline) ? 0 : 1;
	
	// permission
	$file_members_only = !empty($file_members_only);
	$file_required_level = $file_members_only ? (min(max(intval($file_required_level), 0), 10) + 1) : 0;
	
	if(!isset($file_direct_linking))
	{
		// allow direct linking by default
		$file_direct_linking = 1;
	}
		
	$var_names = array('version', 'author', 'date', 'post_id', 'direct_linking', 'description', 'hits', 'language', 'platform', 'requirement', 'license', 'offline', 'required_level');
	for($i = 0; $i < count($var_names); $i++)
	{
		$vn = 'file_' . $var_names[$i];
		if(isset(${$vn}))
			$file->$vn = ${$vn};
	}
		

	// set the user id	
	$current_user = wp_get_current_user();
	if(empty($current_user->ID))
		return array( 'error' => __('Could not get user id!') );	
	if(!$update)
		$file->file_added_by = $current_user->ID;	

	// if thumbnail was uploaded
	if($upload_thumb)
	{
		// delete the old thumbnail (if existing)
		$file->delete_thumbnail();
		
		$thumb_dest_path = dirname($file->get_path()) . '/thumb_' . $file_upload_thumb['name'];
				
		if(@move_uploaded_file($file_upload_thumb['tmp_name'], $thumb_dest_path))
		{
			$file->create_thumbnail($thumb_dest_path);			
			@unlink($thumb_dest_path); 
		}
	}
	
	
	// save into db
	$result = $file->db_save();	
	if(!empty($result['error']))
		return $result;		
	$file_id = (int)$result['file_id'];	
	
	if($upload)
	{
		// TODO?
		//$file->update_subfiles();
	}
	
	return array( 'error' => false, 'file_id' => $file_id);
}


function wpfilebase_sync()
{
	$result = array('not_found' => array(), 'changed' => array(), 'not_added' => array(), 'error' => array(), 'updated_categories' => array());
	$files = &WPFilebaseFile::get_files();
	
	$file_paths = array();
	
	foreach($files as $id => /* & PHP 4 compability */ $file)
	{
		$file_path = $file->get_path();
		$file_paths[] = $file_path;
		if($file->get_thumbnail_path())
			$file_paths[] = $file->get_thumbnail_path();
		
		if(!@is_file($file_path) || !@is_readable($file_path))
		{
			$result['not_found'][] = &$file;
			continue;
		}
		
		
		$file_hash = @md5_file($file_path);
		$file_size = (int)@filesize($file_path);
		
		if($file->file_hash != $file_hash || $file->file_size != $file_size)
		{
			$file->file_size = $file_size;
			$file->file_hash = $file_hash;
			
			$result = $file->db_save();
			
			if(!empty($result['error']))
				$result['error'][] = &$file;
			else
				$result['changed'][] = &$file;
		}
	}
	
	// sync categories
	$result['updated_categories'] = &WPFilebaseCategory::sync_categories();
	
	// search for not added files
	$upload_dir = wpfilebase_upload_dir();	
	$uploaded_files = list_files($upload_dir);
	
	for($i = 0; $i < count($uploaded_files); $i++)
	{
		$fn = $uploaded_files[$i];
		$fbn = basename($fn);
		if($fbn{0} == '.')
			continue;
		if(!in_array($fn, $file_paths) && is_file($fn) && is_readable($fn))
			$result['not_added'][] = substr($fn, strlen($upload_dir));
	}
	
	// chmod
	@chmod ($upload_dir, octdec(WPFB_PERM_FILE));
	for($i = 0; $i < count($file_paths); $i++)
	{		
		@chmod ($file_paths[$i], octdec(WPFB_PERM_FILE));
	}
		
	wpfilebase_protect_upload_path();
	
	return $result;
}

function wpfilebase_wpcache_reject_uri($add_uri, $remove_uri='')
{
	// changes the settings of wp cache
	
	global $cache_rejected_uri;
	
	$added = false;

	if(!isset($cache_rejected_uri))
		return false;

	// remove uri
	if(!empty($remove_uri))
	{
		$new_cache_rejected_uri = array();
			
		foreach($cache_rejected_uri as $i => $v)
		{
			if($v != $remove_uri)
				$new_cache_rejected_uri[$i] = $v;
		}
		
		$cache_rejected_uri = $new_cache_rejected_uri;
	}
	
	if(!in_array($add_uri, $cache_rejected_uri))
	{
		$cache_rejected_uri[] = $add_uri;
		$added = true;
	}
	
	return (wpfilebase_wpcache_save_rejected_uri() && $added);
}

function wpfilebase_wpcache_save_rejected_uri()
{
	global $cache_rejected_uri, $wp_cache_config_file;
	
	if(!isset($cache_rejected_uri) || empty($wp_cache_config_file) || !function_exists('wp_cache_replace_line'))
		return false;	
	
	$text = var_export($cache_rejected_uri, true);
	$text = preg_replace('/[\s]+/', ' ', $text);
	wp_cache_replace_line('^ *\$cache_rejected_uri', "\$cache_rejected_uri = $text;", $wp_cache_config_file);

	return true;
}

function wpfilebase_make_options_list($opt_name, $selected = null, $add_empty_opt = false)
{
	$options = wpfilebase_get_opt($opt_name);	
	$options = explode("\n", $options);
	$def_sel = (is_null($selected) && !is_string($selected));
	$list = $add_empty_opt ? ('<option value=""' . ( (is_string($selected) && $selected == '') ? ' selected="selected"' : '') . '>-</option>') : '';
	$selected = explode('|', $selected);
	
	foreach($options as $opt)
	{
		$opt = trim($opt);
		$tmp = explode('|', $opt);
		$list .= '<option value="' . attribute_escape(trim($tmp[1])) . '"' . ( (($def_sel && $opt{0} == '*') || (!$def_sel && in_array($tmp[1], $selected)) ) ? ' selected="selected"' : '' ) . '>' . wp_specialchars(trim($tmp[0], '*')) . '</option>';
	}
	
	return $list;
}


function wpfilebase_cat_seletion_tree($selected_id = 0, $exclude_id = 0, $cat_id = 0, $deepth = 0)
{
	if($cat_id <= 0)
	{
		echo '<option value="0">' . __('None') . '</option>';
		$cats = &WPFilebaseCategory::get_categories();
		foreach($cats as $c)
		{
			if($c->cat_id != $exclude_id && $c->cat_parent <= 0)
				wpfilebase_cat_seletion_tree($selected_id, $exclude_id, $c->cat_id, 0);	
		}
	} else {
		$cat = &WPFilebaseCategory::get_category($cat_id);	
		echo '<option value="' . $cat_id . '"' . (($cat_id == $selected_id) ? ' selected="selected"' : '') . '>' . str_repeat('&nbsp;&nbsp; ', $deepth) . attribute_escape($cat->cat_name) . '</option>';

		if(isset($cat->cat_childs)) {
			foreach($cat->cat_childs as $child_id) {
				if($child_id != $exclude_id)
					wpfilebase_cat_seletion_tree($selected_id, $exclude_id, $child_id, $deepth + 1);
			}
		}
	}
}

function wpfilebase_admin_table_sort_link($order)
{
	$desc = (!empty($_GET['order']) && $order == $_GET['order'] && empty($_GET['desc']));
	$uri = remove_query_arg(array('order', 'desc'), $_SERVER['REQUEST_URI']);
	$uri .= '&order=' . $order . '&desc=' . ($desc ? '1' : '0');
	return $uri;
}

function wpfilebase_protect_upload_path()
{
	$htaccess = wpfilebase_upload_dir() . '/.htaccess';
	@unlink($htaccess);
	if( is_writable(wpfilebase_upload_dir()) && ($fp = @fopen($htaccess, 'w')) )
	{
		@fwrite($fp, "Order deny,allow\n");
		@fwrite($fp, "Deny from all\n");
		@fclose($fp);
		return @chmod($htaccess, octdec(WPFB_PERM_FILE));
	}	
	return false;
}

function wpfilebase_extension_is_allowed($ext)
{
	static $srv_script_exts = array('php', 'php3', 'php4', 'php5', 'phtml', 'cgi', 'pl', 'asp', 'py', 'aspx');	
	
	$ext = strtolower(trim($ext, '.'));
	return (wpfilebase_get_opt('allow_srv_script_upload') || !in_array($ext, $srv_script_exts));
}

function wpfilebase_uninstall()
{
	wpfilebase_inclib('setup');
	wpfilebase_remove_options();
	wpfilebase_drop_tables();
}

function wpfilebase_progress_bar($progress, $label)
{
	$progress = round(100 * $progress);
	echo "<div class='wpfilebase-progress'><div class='progress'><div class='bar' style='width: $progress%'></div></div><div class='label'><strong>$progress %</strong> ($label)</div></div>";
}

function wpfilebase_admin_form($name, $item=null, $exform=false)
{
	include(WPFB_PLUGIN_ROOT . 'lib/wp-filebase_form_' . $name . '.php');
}

function wpfilebase_mkdir($dir)
{
	$parent = trim(dirname($dir), '.');
	if($parent != '' && !is_dir($parent)) {
		$result = wpfilebase_mkdir($parent);
		if($result['error'])
			return $result;
	}
	return array('error' => !(@mkdir($dir, octdec(WPFB_PERM_DIR)) && @chmod($dir, octdec(WPFB_PERM_DIR))), 'dir' => $dir, 'parent' => $parent);
}

?>