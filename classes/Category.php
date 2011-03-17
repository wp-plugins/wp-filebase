<?php
wpfb_loadclass('Item');

class WPFB_Category extends WPFB_Item {

	var $cat_id;
	var $cat_name;
	var $cat_description;
	var $cat_folder;
	var $cat_path;
	var $cat_parent;
	//var $cat_files;
	var $cat_num_files;
	var $cat_num_files_total;
	var $cat_required_level;
	var $cat_icon;
	var $cat_exclude_browser;
	
	static $cache = array();
	static $cache_complete = false;

	static function GetCats($extra_sql=null)
	{
		global $wpdb;
					
		if(empty($extra_sql)) {
			$extra_sql = 'ORDER BY cat_name ASC';
			if(self::$cache_complete) return self::$cache;
			else self::$cache_complete = true;
		}
		
		$cats = array();
		
		$results = $wpdb->get_results("SELECT * FROM $wpdb->wpfilebase_cats $extra_sql");
		if(!empty($results)) {
			foreach(array_keys($results) as $i)
			{
				$id = (int)$results[$i]->cat_id;
				if(!isset(self::$cache[$id])) self::$cache[$id] = new WPFB_Category($results[$i]);
				$cats[$id] = self::$cache[$id]; // always use items from cache
			}
		}		

		// child cats
		foreach(array_keys($cats) as $id)
		{
			$cat =& $cats[$id];

			$pid = (int)$cat->cat_parent;
			if($pid > 0 && isset(self::$cache[$pid]))
			{
				$pcat =& self::$cache[$pid];
				if(!isset($pcat->cat_childs) || !is_array($pcat->cat_childs)) $pcat->cat_childs = array();
				$pcat->cat_childs[$id] = $cat;
			}					
		}
		
		return $cats;
	}
	
	static function GetCat($id)
	{
		$id = intval($id);
		if($id > 0 && (isset(self::$cache[$id]) || WPFB_Category::GetCats("WHERE cat_id = $id"))) return self::$cache[$id];
		return null;
	}
	
	static function GetNumCats() {
		global $wpdb;
		return $wpdb->get_var("SELECT COUNT(cat_id) FROM $wpdb->wpfilebase_cats");
	}
	
	
	static function CompareName($a, $b) { return $a->cat_name > $b->cat_name; }

	function NotifyFileAdded($file)
	{	
		if($this->IsAncestorOf($file))
		{
			if($file->file_category == $this->cat_id) $this->cat_num_files++;
			$this->cat_num_files_total++;
			if(!$this->locked) $this->DBSave();
		}
		
		$parent = $this->GetParent();
		if($parent) $parent->NotifyFileAdded($file);
	}

	function NotifyFileRemoved(&$file)
	{
		if($this->IsAncestorOf($file))
		{
			if($file->file_category == $this->cat_id) $this->cat_num_files--;
			$this->cat_num_files_total--;
			if(!$this->locked) $this->DBSave();
		}
		
		$parent = $this->GetParent();
		if($parent) $parent->NotifyFileRemoved($file);
	}

	
	function GetChildCats($recursive=false)
	{		
		if(!self::$cache_complete && empty($this->childs_complete)) {
			$this->cat_childs = self::GetCats("WHERE cat_parent = ".(int)$this->cat_id);
			$this->childs_complete = true;
		}
		
		if(empty($this->cat_childs)) return array();
			
		$cats = $this->cat_childs;
		if($recursive) {
			$keys = array_keys($cats);
			foreach($keys as $i) $cats += $cats[$i]->GetChildCats(true);
		}
		
		return $cats;
	}
	
	function Delete()
	{	
		global $wpdb;
		
		// TODO: error handling		
		$cats = $this->GetChildCats();
		$files = $this->GetChildFiles();
		$parent_id = $this->GetParentId();
		
		foreach($cats as $cat) $cat->ChangeCategoryOrName($parent_id);
		foreach($files as $file) $file->ChangeCategoryOrName($parent_id);
		
		// delete the category
		unlink($this->GetLocalPath());
		$wpdb->query("DELETE FROM $wpdb->wpfilebase_cats WHERE cat_id = " . (int)$this->GetId());
		
		return array('error' => false);
	}
	
    private function _get_tpl_var($name,&$esc)
    {
		switch($name) {			
			case 'cat_url':			return $this->GetUrl();
			case 'cat_path':		return $this->GetLocalPathRel();	
			case 'cat_parent':
			case 'cat_parent_name':	return is_object($parent =& $this->GetParent()) ? $parent->cat_name : '';
			case 'cat_icon_url':	return $this->GetIconUrl();
			case 'cat_small_icon': 	$esc=false; return '<img align="" src="'.$this->GetIconUrl('small').'" style="height:32px;vertical-align:middle;" />';
			case 'cat_num_files':		return $this->cat_num_files;
			case 'cat_num_files_total':	return $this->cat_num_files_total;
			case 'cat_required_level':	return ($this->cat_required_level - 1);			
			case 'uid':					return self::$tpl_uid;				
		}
		return isset($this->$name) ? $this->$name : '';
    }
	
	function get_tpl_var($name) {
		$esc = true;
		$v = $this->_get_tpl_var($name, $esc);
		return $esc?esc_html($v):$v;
	}
}

?>