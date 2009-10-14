<?php
require_once(WPFB_PLUGIN_ROOT . 'wp-filebase_item.php');

global $wpfb_cat_cache;
$wpfb_cat_cache = array(); // (PHP 4 compatibility)

class WPFilebaseCategory extends WPFilebaseItem {

	var $cat_id;
	var $cat_name;
	var $cat_description;
	var $cat_folder;
	var $cat_parent;
	var $cat_files;
	var $cat_required_level;
	
	/* static private (PHP 4 compatibility) $_cats = array();*/	

	/*public static (PHP 4 compatibility) */ function get_categories($extra_sql = 'ORDER BY cat_name')
	{
		global $wpdb, $wpfb_cat_cache;
		
		if(!is_array($wpfb_cat_cache))
			$wpfb_cat_cache = array();
		
		$cats = array();
		
		$results = $wpdb->get_results('SELECT * FROM ' . $wpdb->wpfilebase_cats . ' ' . $extra_sql);

		if(!empty($results) && count($results) > 0)
		{
			foreach($results as $cat_row)
			{
				$cat = &new WPFilebaseCategory($cat_row);
				$id = (int)$cat->cat_id;
				
				$cats[$id] = $cat;
				$wpfb_cat_cache[$id] = $cat;
			}
		}
		

		// child cats
		foreach($cats as /* & (PHP 4 compatibility) */ $cat)
		{				
			$p_id = (int)$cat->cat_parent;
			if($p_id > 0 && isset($wpfb_cat_cache[$p_id]))
			{
				$p_cat = &$wpfb_cat_cache[$p_id];
				if(!isset($p_cat->cat_childs) || !is_array($p_cat->cat_childs))
					$p_cat->cat_childs = array();
				$id = (int)$cat->cat_id;
				$p_cat->cat_childs[$id] = $id; // TODO? optimize?
			}					
		}
		
		return $cats;
	}
	
	/*public static (PHP 4 compatibility) */ function get_category($id)
	{
		global $wpfb_cat_cache;
		
		$id = (int)intval($id);
		
		if(isset($wpfb_cat_cache[$id]))
			return $wpfb_cat_cache[$id];
			
		$cats = &WPFilebaseCategory::get_categories("WHERE cat_id = $id");
		
		return isset($cats[$id]) ? $cats[$id] : null;
	}
	
	/*public static (PHP 4 compatibility) */ function get_category_by_folder($folder)
	{
		global $wpdb;
		$cats = &WPFilebaseCategory::get_categories("WHERE cat_folder = '" . $wpdb->escape($folder) . "'");
		if(empty($cats))
			return null;
		return reset(&$cats);
	}

	/*public (PHP 4 compatibility) */ function add_file($file)
	{	
		if($this->is_ancestor_of($file))
		{
			$this->cat_files++;
			$this->db_save();
		}
		
		$parent = $this->get_parent();
		if($parent)
			$parent->add_file($file);
	}

	/*public (PHP 4 compatibility) */ function remove_file($file)
	{
		if($this->is_ancestor_of($file))
		{
			$this->cat_files--;
			$this->db_save();
		}
		
		$parent = $this->get_parent();
		if($parent)
			$parent->remove_file($file);
	}
	
	/*public static (PHP 4 compatibility) */ function sync_categories()
	{
		$updated_cats = array();
		
		// sync file count
		$cats = &WPFilebaseCategory::get_categories();
		foreach($cats as /* & PHP 4 compability */ $cat)
		{
			$catfiles = &$cat->get_files(true);
			$count = (int)count($catfiles);
			if($count != $cat->cat_files)
			{
				$cat->cat_files = $count;
				$cat->db_save();
				
				$updated_cats[] = &$cat;
			}
		}
		
		return $updated_cats;
	}
	
	/*public (PHP 4 compatibility) */ function get_files($recursive=false)
	{
		$files = &WPFilebaseFile::get_files('WHERE file_category = ' . (int)$this->get_id() . ' ORDER BY file_id');
		
		if($recursive && !empty($this->cat_childs)) {
			foreach($this->cat_childs as $ccid) {
				$ccat = & WPFilebaseCategory::get_category($ccid);
				if($ccat) {
					$cfiles = &$ccat->get_files(true);
					$files += $cfiles;
				}
			}
		}
		
		return $files;
	}
	
	/*public (PHP 4 compatibility) */ function get_child_categories()
	{
		return WPFilebaseCategory::get_categories("WHERE cat_parent = " . (int)$this->cat_id);
	}
	
	/*public (PHP 4 compatibility) */ function change_category($cat)
	{
		if(!is_object($cat) && $cat > 0)
			$cat = WPFilebaseCategory::get_category($cat);
		
		if(empty($cat))
		{
			$cat = null;
			$cat_id = 0;
		} else {
			$cat_id = $cat->get_id();
		}
		
		$all_files = & $this->get_files(true);
		
		// update the parent cat(s)
		$parent = $this->get_parent();
		if($parent)
		{			
			foreach($all_files as /* & PHP 4 compability */ $file)
				$parent->remove_file($file);
		}
		
		$old_path = $this->get_path();
		$this->cat_parent = $cat_id;
		
		// create cat dir
		if (!wp_mkdir_p($this->get_path()))
			return array( 'error' => sprintf( __( 'Unable to create directory %s. Is its parent directory writable by the server?' ), $dir ) );
		// chmod
		@chmod ($this->get_path(), octdec(WPFB_PERM_DIR));

		if($old_path != $this->get_path())
		{
			// move everything
			wpfilebase_inclib('file');
			if(!@wpfilebase_move_dir($old_path, $this->get_path()))
				return array( 'error' => sprintf('Could not move folder %s to %s', $old_path, $this->get_path()));
		}
			
		// update the parent cat(s)
		$parent = $this->get_parent();
		if($parent)
		{
			foreach($all_files as /* & PHP 4 compability */ $file)
				$parent->add_file($file);
		}
		
		$this->db_save();
		
		return array('error' => false);
	}
	
	/*public (PHP 4 compatibility) */ function delete()
	{	
		global $wpdb;
		
		/*
		1.  move the contents of the cat folder to the parent cat
		2. update all child cats & files in 
		3. delete the db entry & folder
		*/
		
		$parent_id = (int)$this->get_parent_id();
		
		$new_path = (is_object($parent = $this->get_parent()) ? $parent->get_path() : wpfilebase_upload_dir());
		
		// move everything
		wpfilebase_inclib('file');
		if(!@wpfilebase_move_dir($this->get_path(), $new_path))
			return array( 'error' => sprintf('Could not move folder %s to %s', $this->get_path(), $new_path));
		
		// update db
		$wpdb->query("UPDATE " . $wpdb->wpfilebase_cats . " SET cat_parent = " . (int)$parent_id . " WHERE cat_parent = " . (int)$this->get_id());
		$wpdb->query("UPDATE " . $wpdb->wpfilebase_files . " SET file_category = " . (int)$parent_id . " WHERE file_category = " . (int)$this->get_id());
		
		// delete the category
		@unlink($this->get_path());
		$wpdb->query("DELETE FROM " . $wpdb->wpfilebase_cats . " WHERE cat_id = " . (int)$this->get_id());
		
		return array('error' => false);
	}
}

?>