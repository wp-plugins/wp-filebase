<?php

function wpfilebase_parse_template($tpl)
{
	echo '<!-- [WPFilebase]: parsing template ... -->';

	//escape
	$tpl = str_replace("'", "\\'", $tpl);
	
	// parse if's
	$tpl = preg_replace(
	'/<\!\-\- IF (.+?) \-\->([\s\S]+?)<!-- ENDIF -->/e',
	"'\\' . ( (' . wpfilebase_parse_template_expression('$1') . ') ? (\\'' . wpfilebase_parse_template_ifblock('$2') . '\\') ) . \\''", $tpl);
	
	// parse translation texts
	$tpl = preg_replace('/([^\w])%\\\\\'(.+?)\\\\\'%([^\w])/', '$1\' . __(\'$2\') . \'$3', $tpl);	
	$tpl = preg_replace('/%(\S+?)%/', "' . (\\$$1) . '", $tpl);
	
	// cleanup
	$tpl = str_replace(". ''", "", $tpl);
	
	$tpl = "'$tpl'";
	
	echo '<!-- done! -->';
	
	return $tpl;
}

function wpfilebase_parse_template_expression($exp)
{
	$exp = preg_replace('/%(\S+?)%/', '(\$$1)', $exp);
	$exp = preg_replace('/([^\w])AND([^\w])/', '$1&&$2', $exp);
	$exp = preg_replace('/([^\w])OR([^\w])/', '$1||$2', $exp);
	$exp = preg_replace('/([^\w])NOT([^\w])/', '$1!$2', $exp);
	return $exp;
}

function wpfilebase_parse_template_ifblock($block)
{
	static $s = '<!-- ELSE -->';
	static $r = '\') : (\'';
	if(strpos($block, $s) === false)
		$block .= $r;
	else
		$block = str_replace($s, $r, $block);
	
	// unescape "
	$block = str_replace('\"', '"', $block);
	
	return $block;
}


function wpfilebase_check_template($tpl)
{	
	$result = array('error' => false, 'msg' => '', 'line' => '');
		
	$tpl = 'return (' . $tpl . ');';
	
	if(!@eval($tpl))
	{
		$result['error'] = true;
		
		$err = error_get_last();
		if(!empty($err))
		{
			$result['msg'] = $err['message'];
			$result['line'] = $err['line'];
		}
	}
	
	return $result;
}

?>