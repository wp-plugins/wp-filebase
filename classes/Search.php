<?php
class WPFB_Search {

static function InitClass()
{
	add_filter('posts_join', array(__CLASS__, 'PostsJoin'));
	add_filter('posts_search', array(__CLASS__, 'PostsSearch'));
}

static function PostsJoin($join)
{
	global $wpdb;	
	$join .= " LEFT JOIN $wpdb->wpfilebase_files ON ( $wpdb->wpfilebase_files.file_post_id = $wpdb->posts.ID ) ";
	if(WPFB_Core::GetOpt('search_id3')) 
		$join .= " LEFT JOIN $wpdb->wpfilebase_files_id3 ON ( $wpdb->wpfilebase_files_id3.file_id = $wpdb->wpfilebase_files.file_id ) ";
	return $join;
}

static function _GetSearchTerms($s)
{
	// code extract from WPs search in query.php
	global $wp_query, $wpdb;
	
	$sentence = empty($wp_query->query_vars['sentence']) ? (empty($_GET['sentence']) ? null : stripslashes($_GET['sentence'])) : $wp_query->query_vars['sentence'];
	$search_terms = array();
		
	if ( !empty($s) )
	{
		$s = $wpdb->escape(stripslashes($s));
		if ($sentence)
			$search_terms = array($s);
		else {
			preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
			$search_terms = array_map(create_function('$a', 'return trim($a, "\\"\'\\n\\r ");'), $matches[0]);
		}
	}
	return $search_terms;
}

static function SearchWhereSql($s=null) {
	global $wp_query, $wpdb;
	
	static $search_fields = array('name', 'thumbnail', 'display_name', 'description', 'requirement', 'version', 'author', 'language', 'platform', 'license');	
	
	if(empty($s)) {
		$s = empty($wp_query->query_vars['s']) ? (empty($_GET['s']) ? null : stripslashes($_GET['s'])) : $wp_query->query_vars['s'];
		if(empty($s)) return null;
	}
	$exact = !empty($wp_query->query_vars['exact']);
	$p = $exact ? '' : '%';
	$search_terms = self::_GetSearchTerms($s);
	//print_r($search_terms);
	$where = '';
	
	$t = $wpdb->escape($s);
			
	foreach($search_fields as $sf)
	{
		$col = "{$wpdb->wpfilebase_files}.file_{$sf}";
		$where .= " OR ({$col} LIKE '{$p}{$t}{$p}') OR (";
		
		$and = '';
		foreach($search_terms as $term) {
			$where .= " {$and} {$col} LIKE '{$p}{$term}{$p}'";
			if(empty($and)) $and = 'AND';
		}
		$where .= ")";
	}
	
	if(WPFB_Core::GetOpt('search_id3')) {
		$col = "{$wpdb->wpfilebase_files_id3}.keywords";
		$where .= " OR ({$col} LIKE '{$p}{$t}{$p}') OR (";		
		$and = '';
		foreach($search_terms as $term) {
			$where .= " {$and} {$col} LIKE '{$p}{$term}{$p}'";
			if(empty($and)) $and = 'AND';
		}
		$where .= ")";
	}

	return $where;
}

static function PostsSearch($sql)
{
	global $wp_query, $wpdb;
	
	if(empty($sql)) return $sql;

	$where = self::SearchWhereSql();

	
	// new sql mod., old one was listing drafts!	
	$p = strrpos($sql, ")))");	
	$sql = substr($sql, 0, $p+1) . " $where " . substr($sql, $p+1);
	
	// OLD: insert $where into existing sql
	/*
	$i = strlen($sql)-1;
	while($sql{--$i} == ')') {};
	$i+=2;
	$sql = substr($sql, 0, $i) . $where. substr($sql, $i);
	*/
	
	return $sql;
}
}