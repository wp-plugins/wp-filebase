<?php
wpfb_loadclass('Item');

class WPFB_File extends WPFB_Item {

	var $file_id = 0;
	var $file_name;
	var $file_path;
	var $file_size = 0;
	var $file_date;
	var $file_hash;
	var $file_remote_uri;
	var $file_thumbnail;
	var $file_display_name;
	var $file_description;
	var $file_version;
	var $file_author;
	var $file_language;
	var $file_platform;
	var $file_requirement;
	var $file_license;
	var $file_required_level = 0;
	var $file_offline = 0;
	var $file_direct_linking = 0;
	var $file_force_download = 0;
	var $file_category = 0;
	var $file_category_name;
	var $file_update_of = 0; // TODO
	var $file_post_id = 0;
	var $file_added_by = 0;
	var $file_hits = 0;
	var $file_ratings = 0; // TODO
	var $file_rating_sum = 0; // TODO
	var $file_last_dl_ip;
	var $file_last_dl_time;
	
	static $cache = array();
	//static $cache_complete = false;
	
	static function InitClass()
	{
		global $wpdb;
		self::$id_var = 'file_id';
	}			
		
	static function GetFiles($extra_sql = '')
	{
		global $wpdb;
		$files = array();		
		$results = $wpdb->get_results("SELECT * FROM $wpdb->wpfilebase_files $extra_sql");
		if(!empty($results)) {
			foreach(array_keys($results) as $i) {				
				$id = (int)$results[$i]->file_id;
				self::$cache[$id] = new WPFB_File($results[$i]);	
				$files[$id] = self::$cache[$id];
			}
		}		
		return $files;
	}
	
	static function GetFile($id)
	{		
		$id = intval($id);		
		if(isset(self::$cache[$id]) || WPFB_File::GetFiles("WHERE file_id = $id")) return self::$cache[$id];
		return null;
	}
	
	static function GetNumFiles($sql_or_cat = -1)
	{
		global $wpdb;
		if(is_numeric($sql_or_cat)) $sql_or_cat = (($sql_or_cat>=0)?"file_category = $sql_or_cat":"1");
		return $wpdb->get_var("SELECT COUNT(file_id) FROM $wpdb->wpfilebase_files WHERE $sql_or_cat"); 
	}
	
	static function GetAttachedFiles($post_id)
	{
		$post_id = intval($post_id);
		return WPFB_File::GetFiles("WHERE file_post_id = $post_id " . WPFB_Core::GetFileListSortSql());
	}
	
	function DBSave()
	{ // validate some values before saving (fixes for mysql strict mode)		
		$ints = array('file_size','file_category','file_post_id','file_required_level','file_added_by','file_update_of','file_hits','file_ratings','file_rating_sum');
		foreach($ints as $i) $this->$i = intval($this->$i);
		$this->file_offline = (int)!empty($this->file_offline);
		$this->file_direct_linking = (int)!empty($this->file_direct_linking);
		$this->file_force_download = (int)!empty($this->file_force_download);
		if(empty($this->file_last_dl_time)) $this->file_last_dl_time = '0000-00-00 00:00:00';
		return parent::DBSave();
	}
	
	// gets the extension of the file (including .)
	function GetExtension() { return strtolower(strrchr($this->file_name, '.')); }
	
	function GetType()
	{
		$ext = substr($this->GetExtension(), 1);
		if( ($type = wp_ext2type($ext)) ) return $type;		
		return $ext;
	}	
	
	function CreateThumbnail($src_image='', $del_src=false)
	{
		$src_set = !empty($src_image) && file_exists($src_image);
		$tmp_src = $del_src;
		if(!$src_set)
		{
			if($this->IsLocal())
				$src_image = $this->GetLocalPath();
			else {
				// if remote file, download it and use as source
				require_once(ABSPATH . 'wp-admin/includes/file.php');			
				$src_image = wpfb_call('Admin', 'SideloadFile', $this->file_remote_uri);
				$tmp_src = true;
			}
		}
		
		if(!file_exists($src_image) || @filesize($src_image) < 3)
		{
			if($tmp_src) @unlink($src_image);
			return;
		}
		
		$ext = trim($this->GetExtension(), '.');
	
		if($ext != 'bmp' && @getimagesize($src_image) === false) // check if valid image
		{
			if($tmp_src) @unlink($src_image);
			return;
		}
			
		$this->DeleteThumbnail(); // delete old thumbnail
		
		$thumb = null;
		$thumb_size = (int)WPFB_Core::GetOpt('thumbnail_size');
		
		if(!function_exists('wp_create_thumbnail'))
		{
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			if(!function_exists('wp_create_thumbnail'))
			{
				if($tmp_src) @unlink($src_image);
				wp_die('Function wp_create_thumbnail does not exist!');
				return;
			}
		}
			
		
		if($ext != 'bmp') {
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
		
		$success = (!empty($thumb) && !is_wp_error($thumb) && is_string($thumb) && file_exists($thumb));
		
		if(!$src_set && !$success) {
			$this->file_thumbnail = null;
		} else {
			// fallback to source image
			if($src_set && !$success)
				$thumb = $src_image;
			
			$this->file_thumbnail = basename(trim($thumb , '.')); // FIX: need to trim . when image has no extension
			
			if(!@rename($thumb, $this->GetThumbPath()))
				$this->file_thumbnail = null;
			else
				@chmod($this->GetThumbPath(), octdec(WPFB_PERM_FILE));
		}
		
		if($tmp_src) @unlink($src_image);
	}

	function GetPostUrl() { return empty($this->file_post_id) ? '' : WPFB_Core::GetPostUrl($this->file_post_id).'#wpfb-file-'.$this->file_id; }
	function GetFormattedSize() { return wpfb_call('Output', 'FormatFilesize', $this->file_size); }
	function GetFormattedDate() { return mysql2date(get_option('date_format'), $this->file_date); }
	function GetModifiedTime() { return mysql2date('U', $this->file_date); }
	
	// only deletes file/thumbnail on FS, keeping DB entry
	function Delete()
	{
		$this->DeleteThumbnail();
		
		$this->file_remote_uri = null;
		
		if($this->IsLocal() && @unlink($this->GetLocalPath()))
		{
			$this->file_name = null;
			$this->file_size = null;
			$this->file_date = null;		
			return true;
		}		
		return false;
	}	
	
	function DeleteThumbnail()
	{
		$thumb = $this->GetThumbPath();
		if(!empty($thumb) && file_exists($thumb)) @unlink($thumb);			
		$this->file_thumbnail = null;
		if(!$this->locked) $this->DBSave();
	}	

	// completly removes the file from DB and FS
	function Remove()
	{	
		global $wpdb;

		if($this->file_category > 0 && ($parent = $this->GetParent()) != null)
			$parent->NotifyFileRemoved($this);
		
		// remove file entry
		$wpdb->query("DELETE FROM $wpdb->wpfilebase_files WHERE file_id = " . (int)$this->file_id);
		
		$wpdb->query("DELETE FROM $wpdb->wpfilebase_files_id3 WHERE file_id = " . (int)$this->file_id);
		// remove all sub file entries TODO
		//$wpdb->query("DELETE FROM " . $wpdb->wpfilebase_subfiles . " WHERE subfile_parent_file = " . (int)$this->file_id);
			
		return $this->Delete();
	}
	
	
	private function getInfoValue(&$path)
	{
		if(!isset($this->info))
		{
			global $wpdb;
			if($this->file_id <= 0) return join('->', $path);			
			$info = $wpdb->get_var("SELECT value FROM $wpdb->wpfilebase_files_id3 WHERE file_id = $this->file_id");
			$this->info = is_null($info) ? 0 : unserialize(base64_decode($info));
		}
		
		if(empty($this->info))
			return null;
		
		$val = $this->info;
		foreach($path as $p)
		{
			if(!isset($val[$p])) {
				if(isset($val[0]) && count($val) == 1) // if single array skip to first element
					$val = $val[0];
				else
					return null;				
			}
			$val = $val[$p];
		}		
		if(is_array($val)) $val = join(', ', $val);
		if($p == 'bitrate') {
			$val /= 1000;
			$val = round($val).' kBit/s';
		}
		return $val;
	}
    
    private function getTplVar($name)
    {		
		switch($name) {
			case 'file_url':			return $this->GetUrl();
			case 'file_url_rel':		return WPFB_Core::GetOpt('download_base') . '/' . str_replace('\\', '/', $this->GetLocalPathRel());
			case 'file_post_url':		return !($url = $this->GetPostUrl()) ? $this->GetUrl() : $url;			
			case 'file_icon_url':		return $this->GetIconUrl();
			case 'file_small_icon':		return '<img src="'.$this->GetIconUrl('small').'" style="vertical-align:middle;height:32px;" />';
			case 'file_size':			return $this->GetFormattedSize();
			case 'file_path':			return $this->GetLocalPathRel();
			case 'file_category':		return is_object($parent = $this->GetParent()) ? $parent->cat_name : '';
			
			case 'file_languages':		return WPFB_Output::ParseSelOpts('languages', $this->file_language);
			case 'file_platforms':		return WPFB_Output::ParseSelOpts('platforms', $this->file_platform);
			case 'file_requirements':	return WPFB_Output::ParseSelOpts('requirements', $this->file_requirement, true);
			case 'file_license':		return WPFB_Output::ParseSelOpts('licenses', $this->file_license, true);
			
			case 'file_required_level':	return ($this->file_required_level - 1);
			
			case 'file_date':
			case 'file_last_dl_time':	return mysql2date(get_option('date_format'), $this->$name);
			
			case 'file_extension':		return strtolower(substr(strrchr($this->file_name, '.'), 1));
			case 'file_type': 			return wpfb_call('Download', 'GetFileType', $this->file_name);
			
			case 'file_url_encoded':	return urlencode($this->GetUrl());
			
			case 'uid':					return self::$tpl_uid;
		}
		
    	if(strpos($name, 'file_info/') === 0)
		{
			$path = explode('/',substr($name, 10));
			return $this->getInfoValue($path);
		}
		
		return isset($this->$name) ? $this->$name : '';
    }
	
	function get_tpl_var($name) {
		static $no_esc = array('file_languages', 'file_platforms', 'file_requirements', 'file_license', 'file_small_icon');
		return in_array($name, $no_esc) ? $this->getTplVar($name) : htmlspecialchars($this->getTplVar($name));
	}
	
	function DownloadDenied($msg_id) {
		if(WPFB_Core::GetOpt('inaccessible_redirect') && !is_user_logged_in()) {
			//auth_redirect();
			$redirect = (WPFB_Core::GetOpt('login_redirect_src') && wp_get_referer()) ? wp_get_referer() : $this->GetUrl();
			$login_url = wp_login_url($redirect, true); // force re-auth
			wp_redirect($login_url);
			exit;
		}
		$msg = WPFB_Core::GetOpt($msg_id);
		if(!$msg) $msg = $msg_id;
		wp_die(empty($msg) ? __('Cheatin&#8217; uh?') : $msg);
		exit;
	}
	
	function Download()
	{
		global $wpdb, $current_user, $user_ID;
		
		@error_reporting(0);
		wpfb_loadclass('Category', 'Download');
		$downloader_ip = preg_replace( '/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
		get_currentuserinfo();
		$logged_in = (!empty($user_ID));
		$user_role = $logged_in ? array_shift($current_user->roles) : null; // get user's highest role (like in user-eidt.php)
		$is_admin = $user_role == 'administrator'; 
		
		// check user level
		if(!$this->CurUserCanAccess())
			$this->DownloadDenied('inaccessible_msg');
		
		// check offline
		if($this->file_offline)
			wp_die(WPFB_Core::GetOpt('file_offline_msg'));
		
		// check referrer
		if(!$this->file_direct_linking) {			
			// if referer check failed, redirect to the file post
			if(!WPFB_Download::RefererCheck()) {
				$url = WPFB_Core::GetPostUrl($this->file_post_id);
				if(empty($url)) $url = home_url();
				wp_redirect($url);
				exit;
			}
		}
		
		// check traffic
		if($this->IsLocal() && !WPFB_Download::CheckTraffic($this->file_size)) {
			header('HTTP/1.x 503 Service Unavailable');
			wp_die(WPFB_Core::GetOpt('traffic_exceeded_msg'));
		}

		// check daily user limit
		if(!$is_admin && WPFB_Core::GetOpt('daily_user_limits')) {
			if(!$logged_in)
				$this->DownloadDenied('inaccessible_msg');
			
			$today = intval(date('z'));
			$usr_dls_today = intval(get_user_option(WPFB_OPT_NAME . '_dls_today'));
			$usr_last_dl_day = intval(date('z', intval(get_user_option(WPFB_OPT_NAME . '_last_dl'))));
			if($today != $usr_last_dl_day)
				$usr_dls_today = 0;
			
			// check for limit
			$dl_limit = intval(WPFB_Core::GetOpt('daily_limit_'.$user_role));
			if($usr_dls_today >= $dl_limit)
				$this->DownloadDenied(($dl_limit > 0) ? sprintf(WPFB_Core::GetOpt('daily_limit_exceeded_msg'), $dl_limit) : 'inaccessible_msg');			
			
			$usr_dls_today++;
			update_user_option($user_ID, WPFB_OPT_NAME . '_dls_today', $usr_dls_today);
			update_user_option($user_ID, WPFB_OPT_NAME . '_last_dl', time());
		}			
		
		// count download
		if(!$is_admin || !WPFB_Core::GetOpt('ignore_admin_dls')) {
			$last_dl_time = mysql2date('U', $this->file_last_dl_time , false);
			if(empty($this->file_last_dl_ip) || $this->file_last_dl_ip != $downloader_ip || ((time() - $last_dl_time) > 86400))
				$wpdb->query("UPDATE " . $wpdb->wpfilebase_files . " SET file_hits = file_hits + 1, file_last_dl_ip = '" . $downloader_ip . "', file_last_dl_time = '" . current_time('mysql') . "' WHERE file_id = " . (int)$this->file_id);
		}
		
		// download or redirect
		if($this->IsLocal())
			WPFB_Download::SendFile($this->GetLocalPath(), WPFB_Core::GetOpt('bitrate_' . ($logged_in?'registered':'unregistered')), $this->file_hash, $this->file_force_download);
		else {
			header('HTTP/1.1 301 Moved Permanently');
			header('Location: '.$this->file_remote_uri);
		}
		
		exit;
	}
	
	function SetPostId($id)
	{
		$id = intval($id);
		$this->file_post_id = $id;
		if(!$this->locked) $this->DBSave();
	}
	
	function SetModifiedTime($mysql_date)
	{
		$this->file_date = $mysql_date;
		if($this->IsLocal()) {
			if(!touch($this->GetLocalPath(), mysql2date('U', $mysql_date)+0))
				return false;
		}
		if(!$this->locked) $this->DBSave();
		return true;
	}
	
	function IsRemote() { return !empty($this->file_remote_uri); }	
	function IsLocal() { return empty($this->file_remote_uri); }
	
	/*TODO?
	public function update_subfiles()
	{
		global $wpdb;
		
		// clear all subfiles
		$wpdb->query("DELETE FROM " . $wpdb->wpfilebase_subfiles . " WHERE subfile_parent_file = " . (int)$this->file_id);
		
		// check if the file is an archive and read it's files
		wpfb_loadlib('file');
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