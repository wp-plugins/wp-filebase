<?php
class WPFB_ListTpl {
	
	var $tag;
	var $header;
	var $footer;
	var $file_tpl_tag;
	var $cat_tpl_tag;
		
	static function Get($tag) {
		$tag = trim($tag, '\'');
		$tpls = get_option(WPFB_OPT_NAME.'_list_tpls');
		return isset($tpls[$tag]) ? new WPFB_ListTpl($tag, $tpls[$tag]) : null;
	}
	
	static function GetAll() {
		$tpls = get_option(WPFB_OPT_NAME.'_list_tpls');
		foreach($tpls as $tag => $tpl)
			$tpls[$tag] = new WPFB_ListTpl($tag, $tpl);
		return $tpls;
	}
	
	function WPFB_ListTpl($tag=null, $data=null) {
		if(!empty($data)) {
			$vars = array_keys(get_class_vars(get_class($this)));
			foreach($vars as $var)
				if(isset($data[$var]))
					$this->$var = $data[$var];
		}				
		$this->tag = $tag;
	}
	
	function Save() {
		$tpls = get_option(WPFB_OPT_NAME.'_list_tpls');
		if(!is_array($tpls)) $tpls = array();
		$data = (array)$this;
		unset($data['tag']);
		$tpls[$this->tag] = $data; 
		update_option(WPFB_OPT_NAME.'_list_tpls', $tpls);
	}
	
	static function ParseHeaderFooter($str) {
		global $wp_query;	
		$str = preg_replace('/%sortlink:([a-z_]+)%/e', __CLASS__.'::GenSortlink(\'$1\')', $str);
		if(strpos($str, '%search_form%') !== false) {
			$search = !empty($_GET['wpfb_s']);
			if($search) {
				$sb = empty($wp_query->query_vars['s'])?null:$wp_query->query_vars['s']; 
				$wp_query->query_vars['s'] = $_GET['wpfb_s'];
			}
			$form = get_search_form(false);
			if($search) $wp_query->query_vars['s'] = $sb;
			$form = preg_replace('/action=".+?"/', 'action=""', $form);
			$form = str_replace('="s"', '="wpfb_s"', $form);
			$gets = '';
			foreach($_GET as $name => $value) if($name != 'wpfb_s') $gets.='<input type="hidden" name="'.esc_attr(stripslashes($name)).'" value="'.esc_attr(stripslashes($value)).'" />';
			$form = str_ireplace('</form>', "$gets</form>", $form);
			$str = str_replace('%search_form%', $form, $str);
		}
		return $str;
	}
	
	static function GenSortlink($by) {
		static $link;
		if(empty($link)) {
			$link = remove_query_arg('wpfb_file_sort');
			$link .= ((strpos($link, '?') > 0)?'&':'?').'wpfb_file_sort=&';	
		}
		$desc = !empty($_GET['wpfb_file_sort']) && ($_GET['wpfb_file_sort'] == $by || $_GET['wpfb_file_sort'] == "<$by"); 
		return $link.($desc?'gt;':'lt;').$by;
	}
	
	function Generate($categories, $show_cats, $file_order, $num)
	{		
		$content = self::ParseHeaderFooter($this->header);
		
		if($show_cats) $cat_tpl = WPFB_Core::GetParsedTpl('cat', $this->cat_tpl_tag);
		$file_tpl = WPFB_Core::GetParsedTpl('file', $this->file_tpl_tag);
		
		if($num > 0) {
			$page = (empty($_REQUEST['wpfb_list_page']) || $_REQUEST['wpfb_list_page'] < 1) ? 1 : intval($_REQUEST['wpfb_list_page']);
			$start = $num * ($page-1);
			$limit = " LIMIT $start, $num";
		} else $limit = '';
		
		if(!empty($_GET['wpfb_s']))
			$where = "WHERE (0 ".wpfb_call('Search','SearchWhereSql',$_GET['wpfb_s']).") ";
		else 
			$where = 'WHERE 1 ';
		
		$sort_and_limit = WPFB_Core::GetFileListSortSql($file_order).$limit;
		$num_total_files = 0;
		if(is_null($categories)) { // if null, just list all files!
			$files = WPFB_File::GetFiles($where.$sort_and_limit);
			$num_total_files = WPFB_File::GetNumFiles();// TODO: total number is not correct if user cannot access some files!
			foreach($files as $file) {
				if($file->CurUserCanAccess(true))
					$content .= $file->GenTpl($file_tpl);
			}
		} elseif(count($categories) == 1) { // single cat
			$cat = reset($categories);
			if(!$cat->CurUserCanAccess()) return '';
			if($show_cats) $content .= $cat->GenTpl($cat_tpl);
			$files = WPFB_File::GetFiles("$where AND file_category = $cat->cat_id $sort_and_limit");
			$num_total_files = $cat->cat_num_files;
			foreach($files as $file) $content .= $file->GenTpl($file_tpl);	
		} else { // multi-cat
			// TODO: multi-cat list pagination does not work properly yet
			$n = 0;
			foreach($categories as $cat)
			{
				if(!$cat->CurUserCanAccess()) continue;
				
				$num_total_files += $cat->cat_num_files;
				
				if($n > $num) break; // TODO!!
				
				if($show_cats) $content .= $cat->GenTpl($cat_tpl);	
				$files = WPFB_File::GetFiles("$where AND file_category = $cat->cat_id ".WPFB_Core::GetFileListSortSql($file_order).$limit);			
				foreach($files as $file) {
					$content .= $file->GenTpl($file_tpl);
					$n++;
				}
			}
		}
		
		$footer = self::ParseHeaderFooter($this->footer);
		
		if($num > 0 && $num_total_files > $num) {
			$pagenav = paginate_links( array(
				'base' => add_query_arg( 'wpfb_list_page', '%#%' ),
				'format' => '',
				'total' => ceil($num_total_files / $num),
				'current' => empty($_GET['wpfb_list_page']) ? 1 : absint($_GET['wpfb_list_page'])
			));
			/*
			'show_all' => false,
			'prev_next' => true,
			'prev_text' => __('&laquo; Previous'),
			'next_text' => __('Next &raquo;'),
			'end_size' => 1,
			'mid_size' => 2,
			'type' => 'plain',
			'add_args' => false, // array of query args to add
			'add_fragment' => ''*/		

			if(strpos($footer, '%page_nav%') === false)
				$footer .= "asdf".$pagenav;
			else
				$footer = str_replace('%page_nav%', $pagenav, $footer);
		} else {
			$footer = str_replace('%page_nav%', '', $footer);
		}
		
		$content .= $footer;

		return $content;
	}
	
	function Sample($cat, $file) {
		//print_r($this);
		$cat_tpl = WPFB_Core::GetParsedTpl('cat', $this->cat_tpl_tag);
		$file_tpl = WPFB_Core::GetParsedTpl('file', $this->file_tpl_tag);
		$footer = str_replace('%page_nav%', paginate_links(array(
			'base' => add_query_arg( 'wpfb_list_page', '%#%' ), 'format' => '',
			'total' => 3,
			'current' => 1
		)), self::ParseHeaderFooter($this->footer));
		return self::ParseHeaderFooter($this->header) . $cat->GenTpl($cat_tpl) . $file->GenTpl($file_tpl) . $footer;		
	}
	
	function Delete() {
		$tpls = get_option(WPFB_OPT_NAME.'_list_tpls');
		if(!is_array($tpls)) return;
		unset($tpls[$this->tag]);
		update_option(WPFB_OPT_NAME.'_list_tpls', $tpls);
	}
	
	function GetTitle() { return __(__(esc_html(WPFB_Output::Filename2Title($this->tag))), WPFB); }
}