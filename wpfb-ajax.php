<?php
define('DOING_AJAX', true);
error_reporting(0);

require_once(dirname(__FILE__).'/../../../wp-load.php');

function wpfb_print_json($obj) {
	@ob_end_clean();
	if(!WP_DEBUG)
		@header('Content-Type: application/json; charset=' . get_option('blog_charset'));
	echo json_encode($obj);
	@ob_flush();
	@flush();
	exit;
}

if(!isset($_REQUEST['action']))
	die('-1'); 

@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
if(!WP_DEBUG) {
	send_nosniff_header();
	error_reporting(0);
}

$_REQUEST = stripslashes_deep($_REQUEST);
$_POST = stripslashes_deep($_POST);
$_GET = stripslashes_deep($_GET);

switch ( $action = $_REQUEST['action'] ) {
	
	case 'tree':
		$type = $_REQUEST['type'];
		
		wpfb_loadclass('File','Category','Output');
		
		$base_id = (empty($_REQUEST['base']) ? 0 : $_REQUEST['base']);
				
		if(empty($_REQUEST['root']) || $_REQUEST['root'] == 'source')
			$parent_id = $base_id;
		else {
			$root = $_REQUEST['root'];
			$parent_id = is_numeric($root) ? intval($root) : intval(substr(strrchr($root,'-'),1));
		}
			
		$browser = ($type=='browser');
		$filesel = (!$browser && $type=='fileselect');
		$catsel = (!$filesel && $type=='catselect');
		$cat_id_format = empty($_REQUEST['cat_id_fmt']) ? 'wpfb-cat-%d' : $_REQUEST['cat_id_fmt'];
		$file_id_format = empty($_REQUEST['file_id_fmt']) ? 'wpfb-file-%d' : $_REQUEST['file_id_fmt'];
		if($filesel || $catsel) $onselect = $_REQUEST['onselect'];
		$i = 0;
		$children = array();
		
		$cat_tpl = WPFB_Core::GetParsedTpl('cat', 'filebrowser');
		$file_tpl = WPFB_Core::GetParsedTpl('file', 'filebrowser');
	
		
		$cats = $browser ? WPFB_Category::GetFileBrowserCats($parent_id) : WPFB_Category::GetCats("WHERE cat_parent = $parent_id ORDER BY cat_name ASC");	
		if($parent_id == 0 && $catsel && count($cats) == 0) {
			wpfb_print_json(array(array(
				'id' => sprintf($cat_id_format, $c->cat_id),
				'text' => sprintf(__('You did not create a category. <a href="%s" target="_parent">Click here to create one.</a>', WPFB), admin_url('admin.php?page=wpfilebase_cats#addcat')),
				'hasChildren'=>false
			)));
			exit;
		}
		
		foreach($cats as $c)
		{
			if($c->CurUserCanAccess())
				$children[$i++] = array('id'=>sprintf($cat_id_format, $c->cat_id),
					'text'=>$catsel?('<a href="javascript:'.sprintf($onselect,$c->cat_id,str_replace('\'','\\\'',htmlspecialchars(stripslashes($c->cat_name)))).'">'.esc_html($c->GetTitle(24)).'</a>'):($filesel?esc_html($c->cat_name):$c->GenTpl($cat_tpl, 'ajax')),
					'hasChildren'=>($catsel?(count($c->GetChildCats())>0):($c->cat_num_files_total > 0)),
					'classes'=>($filesel||$catsel)?'folder':null);
		}
		
		if((empty($_REQUEST['cats_only']) || $_REQUEST['cats_only'] == 'false') && !$catsel) {
			$sql = "WHERE file_category = $parent_id";
			if(!empty($_REQUEST['exclude_attached']) && $_REQUEST['exclude_attached'] != 'false') $sql .= " AND file_post_id = 0";
			if($browser) $sql .= " ".WPFB_Core::GetFileListSortSql((WPFB_Core::GetOpt('file_browser_file_sort_dir')?'>':'<').WPFB_Core::GetOpt('file_browser_file_sort_by'));
			$files = WPFB_File::GetFiles($sql);
			foreach($files as $f)
			{
				if($f->CurUserCanAccess(true))
					$children[$i++] = array('id'=>sprintf($file_id_format, $f->file_id), 'text'=>$filesel?('<a href="javascript:'.sprintf($onselect,$f->file_id,str_replace('\'','\\\'',htmlspecialchars(stripslashes($f->file_display_name)))).'">'.esc_html($f->GetTitle(24)).'</a> <span style="font-size:75%;vertical-align:top;">'.esc_html($f->file_name).'</span>'):$f->GenTpl($file_tpl, 'ajax'), 'classes'=>$filesel?'file':null);
			}
		}
		
		wpfb_print_json($children);
		exit;
	
	case 'delete':
		wpfb_loadclass('File','Category');
		$file_id = intval($_REQUEST['file_id']);		
		if(!current_user_can('upload_files') || $file_id <= 0 || ($file = WPFB_File::GetFile($file_id)) == null)
			die('-1');

		$file->Remove();
		die('1');
		
	case 'tpl-sample':
		if(!current_user_can('edit_posts')) die('-1');
		
		wpfb_loadclass('File','Category', 'TplLib', 'Output');
		
		if(isset($_POST['tpl']) && empty($_POST['tpl'])) exit;
		
		$cat = new WPFB_Category(array(
			'cat_id' => 0,
			'cat_name' => 'Example Category',
			'cat_description' => 'This is a sample description.',
			'cat_folder' => 'example',
			'cat_num_files' => 0, 'cat_num_files_total' => 0
		));
		$cat->Lock();
		
		$file = new WPFB_File(array(
			'file_name' => 'example.pdf',
			'file_display_name' => 'Example Document',
			'file_size' => 1024*1024*1.5,
			'file_date' => gmdate('Y-m-d H:i:s', time()),
			'file_hash' => md5(''),
			'file_thumbnail' => 'thumb.png',
			'file_description' => 'This is a sample description.',
			'file_version' => WPFB_VERSION,
			'file_author' => $user_identity,
			'file_hits' => 3
		));
		$file->Lock();
		
		if(!empty($_POST['type']) && $_POST['type'] == 'cat')
			$item = $cat;
		elseif(!empty($_POST['type']) && $_POST['type'] == 'list')
		{
			$tpl = new WPFB_ListTpl('sample', $_REQUEST);
			echo $tpl->Sample($cat, $file);
			exit;
		}
		elseif(empty($_POST['file_id']) || ($item = WPFB_File::GetFile($_POST['file_id'])) == null || !$file->CurUserCanAccess(true))
			$item = $file;
		else
			die('-1');
		
		$tpl = empty($_POST['tpl']) ? null : WPFB_TplLib::Parse($_POST['tpl']);
		echo $item->GenTpl($tpl, 'ajax');
		exit;
		
	case 'fileinfo':
		wpfb_loadclass('File','Category');
		if(empty($_REQUEST['url'])) die('-1');
		$url = $_REQUEST['url'];
		$file = null;
		$matches = array();

		if(preg_match('/\?wpfb_dl=([0-9]+)$/', $url, $matches) || preg_match('/#wpfb-file-([0-9]+)$/', $url, $matches))
			$file = WPFB_File::GetFile($matches[1]);
		else {
			$base = WPFB_Core::GetPermalinkBase();
			$path = substr($url, strlen($base));
			$path_u = substr(urldecode($url), strlen($base));			
			$file = WPFB_File::GetByPath($path);
			if($file == null) $file = WPFB_File::GetByPath($path_u);
		}
		
		if($file != null && $file->CurUserCanAccess(true)) {
			wpfb_print_json(array(
				'id' => $file->GetId(),
				'url' => $file->GetUrl(),
				'path' => $file->GetLocalPathRel()
			));			
		} else {
			echo '-1';
		}
		exit;
		
	case 'postbrowser':
		if(!current_user_can('read_private_posts')) {
			wpfb_print_json(array(array('id'=>'0','text'=>__('Cheatin&#8217; uh?'), 'classes' => '','hasChildren'=>false)));
			exit;
		}
		
		$id = (empty($_REQUEST['root']) || $_REQUEST['root'] == 'source') ? 0 : intval($_REQUEST['root']);
		$onclick = empty($_REQUEST['onclick']) ? '' : $_REQUEST['onclick'];
			
		$args = array('hide_empty' => 0, 'hierarchical' => 1, 'orderby' => 'name', 'parent' => $id);
		$terms = get_terms('category', $args );
		
		$items = array();	
		foreach($terms as &$t) {
			$items[] = array(
				'id' => $t->term_id, 'text'=> esc_html($t->name), 'classes' => 'folder',
				'hasChildren' => ($t->count > 0)
			);
		}
		
		$terms = get_posts(array(
			'numberposts' => 0, 'nopaging' => true,
			//'category' => $id,
			'category__in' => array($id), // undoc: dont list posts of child cats!
			'orderby' => 'title', 'order' => 'ASC',
			'post_status' => 'any' // undoc: get private posts aswell
		));
		
		if($id == 0)
			$terms = array_merge($terms, get_pages(/*array('parent' => $id)*/));
			
		foreach($terms as &$t) {
			$items[] = array('id' => $t->ID, 'classes' => 'file',
			'text'=> ('<a href="javascript:'.sprintf($onclick,$t->ID, str_replace('\'','\\\'',/*htmlspecialchars*/(stripslashes(get_the_title($t->ID))))).'">'.get_the_title($t->ID).'</a>'));
		}

		wpfb_print_json($items);
		exit;
	case 'toggle-context-menu':
		if(!current_user_can('upload_files')) die('-1');
		WPFB_Core::UpdateOption('file_context_menu', !WPFB_Core::GetOpt('file_context_menu'));
		die('1');
		
	case 'attach-file':
		wpfb_loadclass('File');
		if(!current_user_can('upload_files') || empty($_REQUEST['post_id']) || empty($_REQUEST['file_id']) || !($file = WPFB_File::GetFile($_REQUEST['file_id'])))
			die('-1');
		$file->file_post_id = $_REQUEST['post_id'];
		$file->DBSave();
		die('1');
}