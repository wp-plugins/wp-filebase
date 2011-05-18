<?php
class WPFB_GetID3 {
	static $engine;
	
	static function InitClass()
	{
		require_once(WPFB_PLUGIN_ROOT.'extras/getid3/getid3.php');		
		self::$engine = new getID3;
	//$getID3->setOption(array(
	//	'option_md5_data'  => $AutoGetHashes,
	//	'option_sha1_data' => $AutoGetHashes,
	//));		
	}
	
	static function AnalyzeFile($filename)
	{
		return self::$engine->analyze($filename);
	}
	
	static function StoreFileInfo($file_id, $info)
	{
		global $wpdb;
		
		self::cleanInfoByRef($info);

		$data = empty($info) ? '0' : base64_encode(serialize($info));
		
		$keywords = array();
		self::getKeywords($info, $keywords);
		
		return $wpdb->replace($wpdb->wpfilebase_files_id3, array(
			'file_id' => (int)$file_id,
			'analyzetime' => time(),
			'value' => $data,
			'keywords' => join(' ',$keywords)
		));
	}
	
	static function UpdateCachedFileInfo($file)
	{
		$info = self::AnalyzeFile($file->GetLocalPath());
		self::StoreFileInfo($file->GetId(), $info);
		return $info;
	}
	
	// gets file info out of the cache or analyzes the file if not cached
	static function GetFileInfo($file)
	{
		global $wpdb;
		$info = $wpdb->get_var("SELECT value FROM $wpdb->wpfilebase_files_id3 WHERE file_id = " . $file->GetId());
		if(is_null($info))
			return self::UpdateCachedFileInfo($file);
		return ($info=='0') ? null : unserialize(base64_decode($info));
	}
	
	static function GetFileAnalyzeTime($file)
	{
		global $wpdb;
		$t = $wpdb->get_var("SELECT analyzetime FROM $wpdb->wpfilebase_files_id3 WHERE file_id = ".$file->GetId());
		if(is_null($t)) $t = 0;
		return $t;
	}
	
	private static function cleanInfoByRef(&$info)
	{
		static $skip_keys = array('getid3_version','streams','seektable','streaminfo',
		'comments_raw','encoding', 'flags', 'image_data','toc','lame', 'filename', 'filesize', 'md5_file',
		'data', 'warning', 'error', 'filenamepath', 'filepath','popm','email','priv','ownerid');

		foreach($info as $key => &$val)
		{
			if(empty($val) || in_array(strtolower($key), $skip_keys))
			{
				unset($info[$key]);
				continue;
			}
				
			if(is_array($val) || is_object($val))
				self::cleanInfoByRef($info[$key]);
			else if(is_string($val))
			{
				$a = ord($val{0});
				if($a < 32 || $a > 126)  // check for binary data
				{
					unset($info[$key]);
					continue;
				}
			}
		}
	}
	
	private static function getKeywords($info, &$keywords) {
		foreach($info as $key => $val)
		{
			if(is_array($val) || is_object($val))
				self::getKeywords($val, $keywords);
			else if(is_string($val)) {				
				if(!in_array($val, $keywords))
					array_push($keywords, $val);
			}
		}
		return $keywords;
	}
}