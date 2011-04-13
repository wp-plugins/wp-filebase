<?php
class WPFB_Item {

	var $is_file;
	var $is_category;
	
	var $last_parent_id = 0;
	var $last_parent = null;
	
	var $locked = 0;
	
	static $tpl_uid = 0;
	static $id_var;
	
	function WPFB_Item($db_row=null)
	{
		if(!empty($db_row))
		{
			foreach($db_row as $col => $val){
				$this->$col = $val;
			}
			$this->is_file = isset($this->file_id);
			$this->is_category = isset($this->cat_id);
		}
	}
	
	function GetId(){return (int)($this->is_file?$this->file_id:$this->cat_id);}	
	function GetName(){return $this->is_file?$this->file_name:$this->cat_folder;}	
	function GetTitle($maxlen=0){
		$t = $this->is_file?$this->file_display_name:$this->cat_name;
		if($maxlen > 3 && strlen($t) > $maxlen) $t = substr($t, 0, $maxlen-3).'...';
		return $t;
	}	
	function Equals($item){return (isset($item->is_file) && $this->is_file == $item->is_file && $this->GetId() > 0 && $this->GetId() == $item->GetId());}	
	function GetParentId(){return ($this->is_file ? $this->file_category : $this->cat_parent);}	
	function GetParent()
	{
		if(($pid = $this->GetParentId()) != $this->last_parent_id)
		{ // caching
			if($pid > 0) $this->last_parent = WPFB_Category::GetCat($pid);
			else $this->last_parent = null;
			$this->last_parent_id = $pid;
		}
		return $this->last_parent;
	}
	function Lock($lock=true) {
		if($lock) $this->locked++;
		else $this->locked = max(0, $this->locked-1);
	}
	
	static function GetByName($name, $parent_id=0)
	{
		global $wpdb;
		$name = $wpdb->escape($name);
		$parent_id = intval($parent_id);
		
		$items = WPFB_Category::GetCats("WHERE cat_folder = '$name' AND cat_parent = $parent_id LIMIT 1");
		if(empty($items)){
			$items = WPFB_File::GetFiles("WHERE file_name = '$name' AND file_category = $parent_id LIMIT 1");
			if(empty($items)) return null;
		}

		return reset($items);
	}
	
	static function GetByPath($path, $parent_id=0)
	{
		global $wpdb;
		$path = $wpdb->escape(trim(str_replace('\\','/',$path),'/'));
		
		$items = WPFB_Category::GetCats("WHERE cat_path = '$path' LIMIT 1");
		if(empty($items)){
			$items = WPFB_File::GetFiles("WHERE file_path = '$path' LIMIT 1");
			if(empty($items)) return null;
		}

		return reset($items);
	}

	function GetEditUrl()
	{
		$fc = ($this->is_file?'file':'cat');
		return admin_url("admin.php?page=wpfilebase_{$fc}s&action=edit{$fc}&{$fc}_id=".$this->GetId());
	}
	
	function GetLocalPath($refresh=false){return WPFB_Core::UploadDir() . '/' . $this->GetLocalPathRel($refresh);}	
	function GetLocalPathRel($refresh=false)
	{		
		if($this->is_file) $cur_path =& $this->file_path;
		else $cur_path =& $this->cat_path;

		if($refresh)
		{			
			if(($parent = $this->GetParent()) != null)	$path = $parent->GetLocalPathRel($refresh) . '/';
			else $path = '';			
			$path .= $this->is_file ? $this->file_name : $this->cat_folder;
			
			if($cur_path != $path) {
				$cur_path = $path;
				if(!$this->locked) $this->DBSave();
			}
			
			return $path;			
		} else {
			if(empty($cur_path)) return $this->GetLocalPathRel(true);
			return $cur_path;	
		}
	}
	

	function DBSave()
	{
		global $wpdb;
		
		if($this->locked > 0) {
			trigger_error("Cannot save locked item '".$this->GetName()."' to database!", E_USER_WARNING);
			return false;
		}
		
		$values = array();
		
		$id_var = ($this->is_file?'file_id':'cat_id');
		
		$vars = get_class_vars(get_class($this));
		foreach($vars as $var => $def)
		{
			$pos = strpos($var, ($this->is_file?'file_':'cat_'));
			if($pos === false || $pos != 0 || $var == $id_var || is_array($this->$var) || is_object($this->$var))
				continue;			
			$values[$var] = &$this->$var;
		}
		
		$update = !empty($this->$id_var);
		$tbl = $this->is_file?$wpdb->wpfilebase_files:$wpdb->wpfilebase_cats;
		if ($update)
		{
			if( !$wpdb->update($tbl, $values, array($id_var => $this->$id_var) ))
			{
				if(!empty($wpdb->last_error))
					return array( 'error' => 'Failed to update DB! ' . $wpdb->last_error);
			}
		} else {		
			if( !$wpdb->insert($tbl, $values) )
				return array( 'error' =>'Unable to insert item into DB! ' . $wpdb->last_error);				
			$this->$id_var = (int)$wpdb->insert_id;		
		}
		
		return array( 'error' => false, $id_var => $this->$id_var, 'id' => $this->$id_var);
	}
	
	function IsAncestorOf($item)
	{			
		$p = $item->GetParent();
		if ($p == null) return false;
		if ($this->Equals($p)) return true;
		return $this->IsAncestorOf($p);
	}
	
	function CurUserCanAccess($for_tpl=false)
	{
		static $usr_level = -1;
		
		if($for_tpl && !WPFB_Core::GetOpt('hide_inaccessible')) return true;
		
		if($usr_level == -1) {
			global $current_user;
			if($current_user) foreach(array_keys($current_user->caps) as $r){
				$usr_level = max($usr_level, WPFB_Core::UserRole2Level($r)+1);
			} else $usr_level = 0;
		}		
		$level = $this->is_file?$this->file_required_level:$this->cat_required_level;
		return ( ($level <= 0 || $usr_level >= $level) && ($this->is_category || !$this->file_offline) );
	}
	
	function GetUrl()
	{
		$ps = WPFB_Core::GetOpt('disable_permalinks') ? null : get_option('permalink_structure');		
		if($this->is_file) {
			$url = trailingslashit(get_option('home'));	
			if(!empty($ps)) $url .= WPFB_Core::GetOpt('download_base').'/'.$this->GetLocalPathRel();
			else $url = add_query_arg(array('wpfb_dl' => $this->file_id), $url);
		} else {
			$url = get_permalink(WPFB_Core::GetOpt('file_browser_post_id'));	
			if(!empty($ps)) $url .= $this->GetLocalPathRel().'/';
			elseif($this->cat_id > 0) $url = add_query_arg(array('wpfb_cat' => $this->cat_id), $url);
			$url .= "#wpfb-cat-$this->cat_id";	
		}			
		return $url;
	}
	
	function GetRequiredRole()
	{
		return WPFB_Core::UserLevel2Role( ($this->is_file?$this->file_required_level:$this->cat_required_level) - 1);
	}
	
	function GenTpl($parsed_tpl=null, $context='')
	{
		if($context!='ajax')
			WPFB_Core::$load_js = true;
		
		if(empty($parsed_tpl))
		{
			$tpo = $this->is_file?'template_file_parsed':'template_cat_parsed';
			$parsed_tpl = WPFB_Core::GetOpt($tpo);
			if(empty($parsed_tpl))
			{
				$parsed_tpl = wpfb_call('TplLib', 'Parse', WPFB_Core::GetOpt($this->is_file?'template_file':'template_cat'));
				WPFB_Core::UpdateOption($tpo, $parsed_tpl); 
			}
		}
		/*
		if($this->is_file) {
			global $wpfb_file_paths;
			if(empty($wpfb_file_paths)) $wpfb_file_paths = array();
			$wpfb_file_paths[(int)$this->file_id] = $this->GetLocalPathRel();
		}
		*/
		self::$tpl_uid++;
		$f =& $this;
		return eval("return ($parsed_tpl);");
	}
	
	function GetThumbPath()
	{
		if($this->is_file) {
			if(empty($this->file_thumbnail)) return null;			
			return  dirname($this->GetLocalPath()) . '/' . $this->file_thumbnail;
		} else {		
			if(empty($this->cat_icon)) return null;
			return $this->GetLocalPath() . '/' . $this->cat_icon;
		}
	}
	
	function GetIconUrl($size=null) {
		if($this->is_category) return WPFB_PLUGIN_URI . (empty($this->cat_icon) ? ('images/'.(($size=='small')?'folder48':'crystal_cat').'.png') : 'wp-filebase_thumb.php?cid=' . $this->cat_id);

		if(!empty($this->file_thumbnail) && file_exists($this->GetThumbPath()))
		{
			return WPFB_PLUGIN_URI . 'wp-filebase_thumb.php?fid=' . $this->file_id;
		}
				
		$type = $this->GetType();
		$ext = substr($this->GetExtension(), 1);
		
		$img_path = ABSPATH . WPINC . '/images/';
		$img_url = get_option('siteurl').'/'. WPINC .'/images/';
		$custom_folder = '/images/fileicons/';
		
		// check for custom icons
		if(file_exists(WP_CONTENT_DIR.$custom_folder.$ext.'.png'))
			return WP_CONTENT_URL.$custom_folder.$ext.'.png';		
		if(file_exists(WP_CONTENT_DIR.$custom_folder.$type.'.png'))
			return WP_CONTENT_URL.$custom_folder.$type.'.png';
		

		if(file_exists($img_path . 'crystal/' . $ext . '.png'))
			return $img_url . 'crystal/' . $ext . '.png';
		if(file_exists($img_path . 'crystal/' . $type . '.png'))
			return $img_url . 'crystal/' . $type . '.png';	
				
		if(file_exists($img_path . $ext . '.png'))
			return $img_url . $ext . '.png';
		if(file_exists($img_path . $type . '.png'))
			return $img_url . $type . '.png';
		
		// fallback to default
		if(file_exists($img_path . 'crystal/default.png'))
			return $img_url . 'crystal/default.png';		
		if(file_exists($img_path . 'default.png'))
			return $img_url . 'default.png';
		
		// fallback to blank :(
		return $img_url . 'blank.gif';
	}
	
	// for a category this return an array of child files
	// for a file an array with a single element, the file itself
	function GetChildFiles($recursive=false,$sort_sql=null)
	{
		if($this->is_file) return array($this->GetId() => $this);
		if(empty($sort_sql)) $sort_sql = "ORDER BY file_id ASC";
		$files = WPFB_File::GetFiles('WHERE file_category = '.(int)$this->GetId()." $sort_sql");
		if($recursive) {
			$cats = $this->GetChildCats(true);
			foreach(array_keys($cats) as $i)
				$files += $cats[$i]->GetChildFiles(false,$sort_sql);
		}		
		return $files;
	}
	
	function ChangeCategoryOrName($new_cat_id, $new_name=null, $add_existing=false)
	{
		// 1. apply new values
		// 2. check for name collision and rename
		// 3. move stuff
		// 4. notify parents
		// 5. update child paths
		if(empty($new_name)) $new_name = $this->GetName();
		$this->Lock(true);
		
		$new_cat_id = intval($new_cat_id);
		$old_cat_id = $this->GetParentId();
		$old_path_rel = $this->GetLocalPathRel(true);
		$old_path = $this->GetLocalPath();
		$old_name = $this->GetName();
		if($this->is_file) $old_thumb_path = $this->GetThumbPath();
		
		$old_cat = $this->GetParent();
		$new_cat = WPFB_Category::GetCat($new_cat_id);
		if(!$new_cat) $new_cat_id = 0;
		
		$cat_changed = $new_cat_id != $old_cat_id;
		$name_changed = $new_name != $old_name;
		
		if($this->is_file) {
			$this->file_category = $new_cat_id;
			$this->file_name = $new_name;
			$this->file_category_name = ($new_cat_id==0) ? '' : $new_cat->GetTitle();
		} else {
			$this->cat_parent = $new_cat_id;
			$this->cat_folder = $new_name;
		}

		$new_path_rel = $this->GetLocalPathRel(true);
		$new_path = $this->GetLocalPath();

		if($new_path_rel != $old_path_rel) {
			$i = 1;
			if(!$add_existing) {
				$name = $this->GetName();
				// rename item if filename collision
				while(@file_exists($new_path)) {
					$i++;	
					if($this->is_file) {
						$p = strrpos($name, '.');
						$this->file_name = ($p <= 0) ? "$name($i)" : (substr($name, 0, $p)."($i)".substr($name, $p));
					} else
						$this->cat_folder = "$name($i)";				
					
					$new_path_rel = $this->GetLocalPathRel(true);
					$new_path = $this->GetLocalPath();
				}
			}
			
			// finally move it!
			if(!empty($old_name) && @file_exists($old_path)) {
				if($this->is_file && $this->IsLocal()) {
					if(!@rename($old_path, $new_path))
						return array( 'error' => sprintf('Unable to move file %s!', $old_path));
					@chmod($new_path, octdec(WPFB_PERM_FILE));
					
					// move thumb
					if(!empty($old_thumb_path) && @is_file($old_thumb_path)) {
						$thumb_path = $this->GetThumbPath();
						if($i > 1) {
							$p = strrpos($thumb_path, '-');
							if($p <= 0) $p = strrpos($thumb_path, '.');
							$thumb_path = substr($thumb_path, 0, $p)."($i)".substr($thumb_path, $p);
							$this->file_thumbnail = basename($thumb_path);			
						}
						if(!@rename($old_thumb_path, $thumb_path)) return array( 'error' =>'Unable to move thumbnail! '.$thumb_path);
						@chmod($thumb_path, octdec(WPFB_PERM_FILE));
					}
				} else {
					if(!@is_dir($new_path)) wp_mkdir_p($new_path);
					if(!@WPFB_Admin::MoveDir($old_path, $new_path))
						return array( 'error' => sprintf('Could not move folder %s to %s', $old_path, $new_path));
				}
			} else {
				if($this->is_category) {
					if(!@is_dir($new_path) && !wp_mkdir_p($new_path))
						return array('error' => sprintf(__( 'Unable to create directory %s. Is it\'s parent directory writable?'), $new_path));		
				}
			}
			
			$all_files = $this->GetChildFiles(true); // all children files (recursivly)
			if(!empty($all_files)) foreach($all_files as $file) {
				if($cat_changed) {
					if($old_cat) $old_cat->NotifyFileRemoved($file); // notify parent cat to remove files
					if($new_cat) $new_cat->NotifyFileAdded($file);
				}
				$file->GetLocalPathRel(true); // update file's path
			}
			
			if($this->is_category) {
				$cats = $this->GetChildCats(true);
				if(!empty($cats)) foreach($cats as $cat) {
					$cat->GetLocalPathRel(true); // update cats's path
				}
			}
		}
		
		$this->Lock(false);
		if(!$this->locked) $this->DBSave();
		return array('error'=>false);
		
		/*
		 * 		// create the directory if it doesnt exist
		// move file
		if($this->IsLocal() && !empty($old_file_path) && @is_file($old_file_path) && $new_file_path != $old_file_path) {
			if(!@rename($old_file_path, $new_file_path)) return array( 'error' => sprintf('Unable to move file %s!', $this->GetLocalPath()));
			@chmod($new_file_path, octdec(WPFB_PERM_FILE));
		}
		 */
	}
}

?>