<?php

require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');

global $wpfb_file_cache;
$wpfb_file_cache = array(); // (PHP 4 compatibility)

class WPFilebaseFile extends WPFilebaseItem {

	var $file_id;
	var $file_name;
	var $file_size;
	var $file_date;
	var $file_thumbnail;
	var $file_display_name;
	var $file_description;
	var $file_version;
	var $file_author;
	var $file_language;
	var $file_platform;
	var $file_requirement;
	var $file_license;
	var $file_required_level;
	var $file_offline;
	var $file_direct_linking;
	var $file_category;
	var $file_update_of; // TODO
	var $file_post_id;
	var $file_added_by;
	var $file_hits;
	var $file_ratings; // TODO
	var $file_rating_sum; // TODO
	var $file_last_dl_ip;
	var $file_last_dl_time;
	
	/* static private $_files = array(); (PHP 4 compatibility) */
	
		
	/*public static (PHP 4 compatibility) */ function get_files($extra_sql = '')
	{
		global $wpdb, $wpfb_file_cache;
		
		if(!is_array($wpfb_file_cache))
			$wpfb_file_cache = array();
		
		$files = array();
		
		$results = $wpdb->get_results('SELECT * FROM ' . $wpdb->wpfilebase_files . ' ' . $extra_sql);

		if(!empty($results) && count($results) > 0)
		{
			foreach($results as $file_row)
			{
				$file = new WPFilebaseFile($file_row);
				$id = (int)$file->file_id;
				
				$files[$id] = $file;
				$wpfb_file_cache[$id] = $file;
			}
		}
		
		return $files;
	}
	
	/*public static (PHP 4 compatibility) */ function get_file($id)
	{
		global $wpfb_file_cache;
		
		$id = (int)intval($id);
		
		if(isset($wpfb_file_cache[$id]))
			return $wpfb_file_cache[$id];
			
		$files = &WPFilebaseFile::get_files("WHERE file_id = $id");
		
		return $files[$id];
	}
	
	/*public static (PHP 4 compatibility) */ function get_file_by_path($path)
	{
		global $wpdb;
		
		$names = explode('/', $path);
		$n = count($names);
		if($n == 1) {
			$cat_folder = null;
			$file_name = $names[0];
		} else {
			$cat_folder = $names[$n-2];
			$file_name = $names[$n-1];
		}
		
		$cat_folder = trim($cat_folder, '/');
		$file_name = trim($file_name, '/');
		
		if(empty($file_name))
			return;
		
		$cat_id = 0;		
		// get parent cat id
		if(!empty($cat_folder))
		{
			if(!is_object($cat = &WPFilebaseCategory::get_category_by_folder($cat_folder)))
				return null;
			$cat_id = (int)$cat->cat_id;
		}
		
		$files = &WPFilebaseFile::get_files("WHERE file_name = '" . $wpdb->escape($file_name) . "' AND file_category = " . (int)$cat_id);
		
		if(empty($files))
			return null;
		else
			return reset(&$files);
	}
	
	/*public static (PHP 4 compatibility) */ function get_num_files()
	{
		global $wpdb;
		return $wpdb->get_var("SELECT COUNT(file_id) FROM $wpdb->wpfilebase_files WHERE 1"); 
	}

	
	// gets the extension of the file (including .)
	/*public (PHP 4 compatibility) */ function get_extension()
	{
		return strtolower(strrchr($this->file_name, '.'));
	}
	
	/*public (PHP 4 compatibility) */ function get_type()
	{
		$ext = substr($this->get_extension(), 1);
		if( ($type = wp_ext2type($ext)) )
			return $type;
		
		return $ext;
	}	
	
	/*public (PHP 4 compatibility) */ function get_thumbnail_path()
	{
		if(empty($this->file_thumbnail))
			return null;
			
		return  dirname($this->get_path()) . '/' . $this->file_thumbnail;
	}
	
	/*public (PHP 4 compatibility) */ function get_icon_url()
	{	
		if(!empty($this->file_thumbnail))
		{
			return WPFB_PLUGIN_URI . 'wp-filebase_thumb.php?fid=' . $this->file_id;
		}
				
		$type = $this->get_type();
		$ext = substr($this->get_extension(), 1);

		$images_path = ABSPATH . WPINC . '/images/';
		$url = get_option('siteurl').'/'. WPINC .'/images/';
		

		if(file_exists($images_path . 'crystal/' . $type . '.png'))
			return $url . 'crystal/' . $type . '.png';		
		if(file_exists($images_path . $type . '.png'))
			return $url . $type . '.png';
		
		if($type != $ext)
		{
			if(file_exists($images_path . 'crystal/' . $ext . '.png'))
				return $url . 'crystal/' . $ext . '.png';		
			if(file_exists($images_path . $ext . '.png'))
				return $url . $ext . '.png';
		}
		
		// fallback to default
		if(file_exists($images_path . 'crystal/default.png'))
			return $url . 'crystal/default.png';
		
		if(file_exists($images_path . 'default.png'))
			return $url . 'default.png';
		
		// fallback to blank :(
		return $url . 'blank.gif';
	}
	
	/*public (PHP 4 compatibility) */ function create_thumbnail($src_image='')
	{
		if(empty($src_image))
			$src_image = $this->get_path();
		
		if(!file_exists($src_image) || @filesize($src_image) < 3)
			return;
		
		$ext = trim($this->get_extension(), '.');
		
		if($ext != 'bmp' && !file_is_valid_image($src_image))
			return;
			
		$this->delete_thumbnail();
		
		$thumb = null;
		$thumb_size = (int)wpfilebase_get_opt('thumbnail_size');
		
		if(!function_exists('wp_create_thumbnail'))
			wp_die('Function wp_create_thumbnail does not exist!');
			
		
		if($ext != 'bmp')
		{
			$thumb = @wp_create_thumbnail($src_image, $thumb_size);
		} else {
			$extras_dir = WPFB_PLUGIN_ROOT . 'extras/';
			if(@file_exists($extras_dir . 'phpthumb.functions.php') && @file_exists($extras_dir . 'phpthumb.bmp.php'))
			{
				@include($extras_dir . 'phpthumb.functions.php');
				@include($extras_dir . 'phpthumb.bmp.php');
				
				if(class_exists('phpthumb_functions') && class_exists('phpthumb_bmp'))
				{
					$phpthumb_bmp = new phpthumb_bmp();
					
					$im = $phpthumb_bmp->phpthumb_bmpfile2gd($src_image);
					if($im) {
						$jpg_file = $src_image . '__.tmp.jpg';
						@imagejpeg($im, $jpg_file, 100);
						if(@file_exists($jpg_file) && @filesize($jpg_file) > 0)
						{
							$thumb = @wp_create_thumbnail($jpg_file, $thumb_size);
						}
						@unlink($jpg_file);
					}						
				}
			}				
		}
		
		if(empty($thumb) || !is_string($thumb)) {
			$this->file_thumbnail = null;
		} else {
			$this->file_thumbnail = basename($thumb);
			
			if(!@rename($thumb, $this->get_thumbnail_path()))
				$this->file_thumbnail = null;
			else
				@chmod($this->get_thumbnail_path(), octdec(WPFB_PERM_FILE));
		}
	}

	/*public (PHP 4 compatibility) */ function get_url()
	{	
		$url = get_option('siteurl') . '/';
		
		$ps = get_option('permalink_structure');
		if(!empty($ps))
			$url .= str_replace(wpfilebase_upload_dir(), wpfilebase_get_opt('download_base'), $this->get_path());
		else
			$url .= '?wpfb_dl=' . $this->file_id;
		
		return $url;
	}
	
	/*public (PHP 4 compatibility) */ function get_post_url()
	{
		if(empty($this->file_post_id))
			return null;
			
		return wpfilebase_get_post_url($this->file_post_id);
	}
	
	/*public (PHP 4 compatibility) */ function get_formatted_size()
	{
		return wpfilebase_format_filesize($this->file_size);
	}
	
	/*public (PHP 4 compatibility) */ function get_formatted_date()
	{
		return mysql2date(get_option('date_format'), $this->file_date);
	}
	
	/*public (PHP 4 compatibility) */ function delete()
	{
		$this->delete_thumbnail();
		
		if(@unlink($this->get_path()))
		{
			$this->file_name = null;
			$this->file_size = null;
			$this->file_date = null;		
			return true;
		}		
		return false;
	}
	
	
	/*public (PHP 4 compatibility) */ function delete_thumbnail()
	{
		$thumb = $this->get_thumbnail_path();
		if(!empty($thumb) && file_exists($thumb))
			@unlink($thumb);			
		$this->file_thumbnail = null;
	}
	

	/*public (PHP 4 compatibility) */ function remove()
	{	
		global $wpdb;

		if($this->file_category > 0 && ($parent = $this->get_parent()) != null)
			$parent->remove_file($this);
		
		// remove file entry
		$wpdb->query("DELETE FROM " . $wpdb->wpfilebase_files . " WHERE file_id = " . (int)$this->file_id);
		// remove all sub file entries TODO
		//$wpdb->query("DELETE FROM " . $wpdb->wpfilebase_subfiles . " WHERE subfile_parent_file = " . (int)$this->file_id);
			
		return $this->delete();
	}



	/*public (PHP 4 compatibility) */ function change_category($new_cat_id)
	{
		if(is_object($new_cat_id))
			$new_cat_id = $new_cat_id->get_id();
		$new_cat_id = (int)intval($new_cat_id);
			
		// get old paths
		$old_file_path = $this->get_path();
		$old_thumb_path = $this->get_thumbnail_path();
		
		// remove from current cat
		$parent = $this->get_parent();
		if($parent)
			$parent->remove_file($this);
		
		// add to current cat
		$this->file_category = (int)$new_cat_id;
		$parent = $this->get_parent();		
		if($parent)
			$parent->add_file($this);
			
		// create the directory if it doesnt exist
		if(!is_dir(dirname($this->get_path())))
		{
			if ( !wp_mkdir_p(dirname($this->get_path())) )
				return array( 'error' => sprintf( __( 'Unable to create directory %s. Is it\'s parent directory writable?' ), $this->get_path() ) );
		}
		
		// move file
		if(!@rename($old_file_path, $this->get_path()))
			return array( 'error' =>'Unable to move file!');
		@chmod($this->get_path(), octdec(WPFB_PERM_FILE));
		
		// move thumb
		if(!empty($old_thumb_path) && @file_exists($old_thumb_path))
		{
			if(!@rename($old_thumb_path, $this->get_thumbnail_path()))
				return array( 'error' =>'Unable to move thumbnail!');
			@chmod($this->get_thumbnail_path(), octdec(WPFB_PERM_FILE));
		}
		
		return array( 'error' => false);
	}
	
	/*public (PHP 4 compatibility) */ function parse_template($template='')
	{
		static $tpl_uid = 0;
		static $js_printed = false;
		
		if(empty($template))
		{
			$template = wpfilebase_get_opt('template_file_parsed');
			if(empty($template))
			{
				$tpl = wpfilebase_get_opt('template_file');
				if(!empty($tpl))
				{
					echo '<!-- parsing template ... -->';
					wpfilebase_inclib('template');
					$template = wpfilebase_parse_template($tpl);
					wpfilebase_update_opt('template_file_parsed', $template); 
				}
			}
		}
			
		$data = (array)$this;
		
		// additional data
		$data['file_url'] = $this->get_url();
		$data['file_post_url'] = $this->get_post_url();
		if(empty($data['file_post_url']))
			$data['file_post_url'] = $data['file_url'];
		$data['file_icon_url'] = $this->get_icon_url();
		$data['file_size'] = $this->get_formatted_size();
		$data['file_path'] = substr($this->get_path(), strlen(wpfilebase_upload_dir()) + 1);
		$parent = $this->get_parent();
		$data['file_category'] = $parent->cat_name;
		
		$data['file_languages'] = wpfilebase_get_tag_names('languages', $this->file_language);
		$data['file_platforms'] = wpfilebase_get_tag_names('platforms', $this->file_platform);
		$data['file_requirements'] = wpfilebase_get_tag_names('requirements', $this->file_requirement);
		$data['file_license'] = wpfilebase_get_tag_names('licenses', $this->file_license);
		
		$data['file_required_level'] = ($this->file_required_level - 1);
		
		$data['file_date'] = mysql2date(get_option('date_format'), $data['file_date']);
		$data['file_last_dl_time'] = mysql2date(get_option('date_format'), $data['file_last_dl_time']);
		
		$data['uid'] = $tpl_uid++;
		
		extract($data);
		
		$template = @eval('return (' . $template . ');');
		
		if(!$js_printed)
		{
			$js = wpfilebase_get_opt('dlclick_js');
			if(!empty($js))
			{
				// TODO: put this in a JS file
				$template .= <<<JS
<script type="text/javascript">
function wpfilebase_dlclick(file_id, file_url) {try{
{$js}
}catch(err){}}
</script>
JS;

			}
			$js_printed = true;
		}
		
		return $template;
	}
	
	/*public (PHP 4 compatibility) */ function download()
	{
		global $wpdb, $user_ID;
		
		wpfilebase_inclib('download');
		
		// check user level
		if(!$this->current_user_can_access())
			wp_die(__('Cheatin&#8217; uh?'));
		
		// check offline
		if($this->file_offline)
			wp_die(wpfilebase_get_opt('file_offline_msg'));
		
		// check referrer
		if(!$this->file_direct_linking) {			
			// if referer check failed, redirect to the file post
			if(!wpfilebase_referer_check()) {
				wp_redirect(wpfilebase_get_post_url($this->file_post_id));
				exit;
			}
		}	
		
		$downloader_ip = preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
		
		// check traffic
		if(!wpfilebase_check_traffic($this->file_size))
		{
			header('HTTP/1.x 503 Service Unavailable');
			wp_die(wpfilebase_get_opt('traffic_exceeded_msg'));
		}

		get_currentuserinfo();
		$logged_in = (!empty($user_ID));
		$is_admin = current_user_can('level_8'); 
		
		// count download
		if(!$is_admin || !wpfilebase_get_opt('ignore_admin_dls'))
		{
			$last_dl_time = mysql2date('U', $file->last_dl_time , false);
			if(empty($this->file_last_dl_ip) || $this->file_last_dl_ip != $downloader_ip || ((time() - $last_dl_time) > 86400))
				$wpdb->query("UPDATE " . $wpdb->wpfilebase_files . " SET file_hits = file_hits + 1, file_last_dl_ip = '" . $downloader_ip . "', file_last_dl_time = '" . current_time('mysql') . "' WHERE file_id = " . (int)$this->file_id);
		}
		
		wpfilebase_send_file($this->get_path(), wpfilebase_get_opt('bitrate_' . ($logged_in?'registered':'unregistered')));
		
		exit;
	}
	
	/*TODO?
	public function update_subfiles()
	{
		global $wpdb;
		
		// clear all subfiles
		$wpdb->query("DELETE FROM " . $wpdb->wpfilebase_subfiles . " WHERE subfile_parent_file = " . (int)$this->file_id);
		
		// check if the file is an archive and read it's files
		wpfilebase_inclib('file');
		$sub_files = wpfilebase_list_archive_files($full_upload_path);
		if(!empty($sub_files) && is_array($sub_files) && count($sub_files) > 0)
		{
			foreach($sub_files as $sb)
				$wpdb->insert( $wpdb->wpfilebase_subfiles, array('subfile_parent_file' => (int)$this->file_id, 'subfile_name' => $sb['name'], 'subfile_size' => (int)$sb['size']));
		}
	}
	*/
}

?>