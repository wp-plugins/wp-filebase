<?php
class WPFB_Search {

function InitClass()
{
	add_filter('posts_join', array(__CLASS__, 'SearchJoin'));
	add_filter('posts_search', array(__CLASS__, 'SearchWhere'));
}

function SearchJoin($join)
{
	global $wpdb;	
	$join .= " LEFT JOIN $wpdb->wpfilebase_files ON ( $wpdb->wpfilebase_files.file_post_id = $wpdb->posts.ID ) ";
	return $join;
}

function _GetSearchTerms()
{
	// code extract from WPs search in query.php
	global $wp_query, $wpdb;
	$s = $wp_query->query_vars['s'];
	$sentence = $wp_query->query_vars['sentence'];
	$search_terms = array();
		
	if ( !empty($s) )
	{
		$s = stripslashes($s);
		if ($sentence)
			$search_terms = array($s);
		else {
			preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $s, $matches);
			$search_terms = array_map(create_function('$a', 'return trim($a, "\\"\'\\n\\r ");'), $matches[0]);
		}
	}
	return $search_terms;
}

function SearchWhere($sql)
{
	global $wp_query, $wpdb;
	
	if(empty($sql)) return $sql;
	
	$search_fields = array('name', 'thumbnail', 'display_name', 'description', 'requirement', 'version', 'author', 'language', 'platform', 'license');	
			
	$s = $wp_query->query_vars['s'];
	$exact = !empty($wp_query->query_vars['exact']);
	$p = $exact ? '' : '%';
	$search_terms = self::_GetSearchTerms();
	//print_r($search_terms);
	$where = '';

	foreach($search_fields as $sf)
	{
		$col = "{$wpdb->wpfilebase_files}.file_{$sf}";
		$t = $wpdb->escape($s);
		$where .= " OR ({$col} LIKE '{$p}{$t}{$p}') OR (";
		
		$and = '';
		foreach($search_terms as $term) {
			$where .= " {$and} {$col} LIKE '{$p}{$term}{$p}'";
			if(empty($and)) $and = 'AND';
		}
		$where .= ")";
	}	

	// insert $where into existing sql
	$i = strlen($sql)-1;
	while($sql{--$i} == ')') {};
	$i+=2;
	$sql = substr($sql, 0, $i) . $where. substr($sql, $i);
	
	return $sql;
}	

}