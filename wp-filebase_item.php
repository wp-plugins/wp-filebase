<?php

class WPFilebaseItem {

	var $is_file;
	var $is_category;
	
	private $last_parent_id = 0;
	private $last_parent = null;
	
	function WPFilebaseItem($db_row)
	{
		if(!empty($db_row))
		{
			foreach($db_row as $col => $val)
			{
				$this->$col = $val;
			}		

			$this->is_file = isset($this->file_id);
			$this->is_category = isset($this->cat_id);
		}
	}
	
	function get_id()
	{
		if($this->is_file)
			return (int)$this->file_id;
		else
			return (int)$this->cat_id;
	}
	
	function get_name()
	{
		if($this->is_file)
			return $this->file_name;
		else
			return $this->cat_name;
	}
	
	function equals($item)
	{
		if(!is_object($item))
			return false;
			
		return ( ($this->is_file == $item->is_file) && ($this->get_id() > 0) && ($this->get_id() == $item->get_id()) );
	}
	
	function get_parent_id()
	{
		if($this->is_file)
			return (int)$this->file_category;
		else if($this->is_category)
			return (int)$this->cat_parent;
			
		return -1;
	}
	
	public function get_parent()
	{
		$pid = ($this->is_file ? $this->file_category : $this->cat_parent);
		
		// caching
		if($pid != $this->last_parent_id)
		{		
			$this->last_parent = &WPFilebaseCategory::get_category($pid);
			$this->last_parent_id = $pid;
		}

		return $this->last_parent;
	}

/*
	function get_parent_cats()
	{
		$parent_cats = array();
		
		$item = $this;
		
		while( !empty($item) && ( ($parent_id = $item->get_parent_id()) > 0) )
		{
			if(!wpfilebase_category_exists($parent_id))
				break;
			$parent_cats[] = (int)$parent_id;
			$item = wpfilebase_get_category($parent_id);
		}
		
		return $parent_cats;
	}
*/
	
	function get_path()
	{			
		$path = '/' . (($this->is_file) ? ($this->file_name) : (trim($this->cat_folder, '/')));

		if($this->get_parent() != null)
			$path = $this->get_parent()->get_path() . $path;
		else
			$path = wpfilebase_upload_dir() . $path;
			
		return $path;
	}
	
	public function db_save()
	{
		global $wpdb;
		
		$values = array();
		
		$id_var = ($this->is_file?'file_id':'cat_id');
		$db_name = ($this->is_file ? $wpdb->wpfilebase_files : $wpdb->wpfilebase_cats);
		
		foreach($this as $key => $val)
		{
			$pos = strpos($key, ($this->is_file?'file_':'cat_'));
			if($pos === false || $pos != 0 || $key == $id_var || is_array($val) || is_object($val))
				continue;
			
			$values[$key] = $val;
		}
		
		$update = !empty($this->$id_var);
			
		if ($update)
		{
			if( !$wpdb->update( $db_name, $values, array($id_var => $this->$id_var) ))
			{
				if(!empty($wpdb->last_error))
					return array( 'error' => 'Failed to update DB! ' . $wpdb->last_error);
			}
		} else {		
			if( !$wpdb->insert($db_name, $values) )
				return array( 'error' =>'Unable to insert item into DB! ' . $wpdb->last_error);				
			$this->$id_var = (int)$wpdb->insert_id;		
		}
		
		return array( 'error' => false, $id_var => $this->$id_var);
	}
	
	public function is_ancestor_of($item)
	{			
		$p = &$item->get_parent();
		if ($p == null)
			return false;

		if ($this->equals($p))
			return true;

		return $this->is_ancestor_of($p);
	}
}

?>