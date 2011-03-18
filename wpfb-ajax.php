<?php

define('DOING_AJAX', true);

require_once(dirname(__FILE__).'/../../../wp-load.php');

if ( ! isset( $_REQUEST['action'] ) )
	die('-1'); 
	
@header('Content-Type: text/html; charset=' . get_option('blog_charset'));
send_nosniff_header();
error_reporting(0);

$_REQUEST = stripslashes_deep($_REQUEST);
$_POST = stripslashes_deep($_POST);
$_GET = stripslashes_deep($_GET);

switch ( $action = $_REQUEST['action'] ) {
	
	case 'tree':
		wpfb_loadclass('File','Category','Output');		
		@header('Content-Type: application/json; charset=' . get_option('blog_charset'));
		$parent_id = (empty($_REQUEST['root']) || $_REQUEST['root'] == 'source') ? 0 : intval(substr(strrchr($_REQUEST['root'],'-'),1));
		$filesel = !empty($_REQUEST['fileselect']);
		$catsel = !$filesel && !empty($_REQUEST['catselect']);
		$cat_id_format = empty($_REQUEST['cat_id_fmt']) ? 'wpfb-cat-%d' : $_REQUEST['cat_id_fmt'];
		$file_id_format = empty($_REQUEST['file_id_fmt']) ? 'wpfb-file-%d' : $_REQUEST['file_id_fmt'];
		if($filesel || $catsel) $onselect = $_REQUEST['onselect'];
		$i = 0;
		$children = array();
		
		$cat_tpl = WPFB_Core::GetParsedTpl('cat', 'filebrowser');
		$file_tpl = WPFB_Core::GetParsedTpl('file', 'filebrowser');

		$cats = WPFB_Category::GetCats("WHERE cat_exclude_browser = 0 AND cat_parent = $parent_id");
		if($parent_id == 0 && $catsel && count($cats) == 0) {
			echo json_encode(array(array(
				'id' => sprintf($cat_id_format, $c->cat_id),
				'text' => sprintf(__('You did not create a category. <a href="%s" target="_parent">Click here to create one.</a>', WPFB), admin_url('admin.php?page=wpfilebase_cats#addcat')),
				'hasChildren'=>false
			)));
			exit;
		}
		
		foreach($cats as $c)
		{
			if($c->CurUserCanAccess(true))
				$children[$i++] = array('id'=>sprintf($cat_id_format, $c->cat_id),
					'text'=>$catsel?('<a href="javascript:'.sprintf($onselect,$c->cat_id,str_replace('\'','\\\'',htmlspecialchars(stripslashes($c->cat_name)))).'">'.esc_html($c->GetTitle(24)).'</a>'):($filesel?esc_html($c->cat_name):$c->GenTpl($cat_tpl, 'ajax')),
					'hasChildren'=>($catsel?(count($c->GetChildCats())>0):($c->cat_num_files_total > 0)),
					'classes'=>($filesel||$catsel)?'folder':null);
		}
		
		if(empty($_REQUEST['cats_only']) && !$catsel) {
			$files = WPFB_File::GetFiles("WHERE file_category = $parent_id");
			foreach($files as $f)
			{
				if($f->CurUserCanAccess(true))
					$children[$i++] = array('id'=>sprintf($file_id_format, $f->file_id), 'text'=>$filesel?('<a href="javascript:'.sprintf($onselect,$f->file_id,str_replace('\'','\\\'',htmlspecialchars(stripslashes($f->file_display_name)))).'">'.esc_html($f->GetTitle(24)).'</a> <span style="font-size:75%;vertical-align:top;">'.esc_html($f->file_name).'</span>'):$f->GenTpl($file_tpl, 'ajax'), 'classes'=>$filesel?'file':null);
			}
		}

		echo json_encode($children);
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
		$base = WPFB_Core::GetPermalinkBase();
		$path = substr($_REQUEST['url'], strlen($base));		
		if(($file = WPFB_File::GetByPath($path)) != null) echo json_encode(array(
			'id' => $file->GetId(),
			'path' => $file->GetLocalPathRel()
		));
		else echo '-1';
		exit;
		
	case 'postbrowser':
		if(!current_user_can('read_private_posts'))
			die('-1');
		
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
			'orderby' => 'title', 'order' => 'ASC'
		));
		
		if($id == 0)
			$terms = array_merge($terms, get_pages(/*array('parent' => $id)*/));
			
		foreach($terms as &$t)
			$items[] = array('id' => $t->ID, 'classes' => 'file',
			'text'=> ('<a href="javascript:'.sprintf($onclick,$t->ID, str_replace('\'','\\\'',/*htmlspecialchars*/(stripslashes(get_the_title($t->ID))))).'">'.get_the_title($t->post_title).'</a>'));

		echo json_encode($items);
		exit;
	case 'toggle-context-menu':
		WPFB_Core::UpdateOption('file_context_menu', !WPFB_Core::GetOpt('file_context_menu'));
		die('1');
}