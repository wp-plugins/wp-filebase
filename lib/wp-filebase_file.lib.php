<?php

// copy of wp's copy_dir, but moves everything
function wpfilebase_move_dir($from, $to)
{

	require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php');
	require_once(ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php');
	
	$wp_filesystem = new WP_Filesystem_Direct(null);
	
	$dirlist = $wp_filesystem->dirlist($from);

	$from = trailingslashit($from);
	$to = trailingslashit($to);

	foreach ( (array) $dirlist as $filename => $fileinfo ) {
		if ( 'f' == $fileinfo['type'] ) {
			if ( ! $wp_filesystem->move($from . $filename, $to . $filename, true) )
				return false;
			$wp_filesystem->chmod($to . $filename, WPFB_PERM_FILE);
		} elseif ( 'd' == $fileinfo['type'] ) {
			if ( !$wp_filesystem->mkdir($to . $filename, WPFB_PERM_DIR) )
				return false;
			if(!wpfilebase_move_dir($from . $filename, $to . $filename))
				return false;
		}
	}
	
	// finally delete the from dir
	@rmdir($from);
	
	return true;
}

/*
function wpfilebase_get_zip_file_list($zipfile, $skip_zero_files=false, $strip_path=true)
{
	$filelist = array();
	$output = array();
	exec('unzip -l "' . $zipfile . '"' , $output);
	$list_started = false;
	foreach($output as $i => $line)
	{
		if(!$list_started)
		{
			$p = strpos($line, '----');
			if($p !== false && $p < 4)
				$list_started = true;
			continue;
		} else {
			$p = strpos($line, '----');
			if($p !== false && $p < 4)
				break;
		}		
		if(@preg_match("/^\s*([0-9]+)\s{1,2}[\-0-9]{8}\s[:0-9]{5}\s{1,3}(.*)$/", $line, $matches))
		{
			$matches[1] = (int)$matches[1];
			if($strip_path)
			{
				$pos = strrpos($matches[2], '/');
				if($pos !== false) $matches[2] = substr($matches[2], $pos + 1);
			}
			if(!$skip_zero_files || $matches[1] != 0)
				$filelist[] = array( 'name' => $matches[2], 'size' => $matches[1]);
		}
	}	
	return $filelist;
}

function wpfilebase_get_rar_file_list($rarfile, $skip_zero_files=false)
{
	$filelist = array();
	$output = array();
	exec('unrar l "' . $rarfile . '"', $output);
	$list_started = false;
	foreach($output as $i => $line)
	{
		if(!$list_started)
		{
			$p = strpos($line, '----');
			if($p !== false && $p < 4)
				$list_started = true;
			continue;
		} else {
			$p = strpos($line, '----');
			if($p !== false && $p < 4)
				break;
		}
		if(@preg_match("/^\s*(.*)\s{1,8}([0-9]+)\s{1,8}([0-9]+)\s{2}[0-9]{1,3}%\s/", $line, $matches))
		{
			$matches[2] = (int)$matches[2];
			if(!$skip_zero_files || $matches[2] != 0)
				$filelist[] = array( 'name' => $matches[1], 'size' => $matches[2]);
		}
	}	
	return $filelist;
}

function wpfilebase_list_archive_files($archive, $skip_zero_files=false)
{
	$ext = strtolower(strrchr($archive, '.'));
	switch($ext)
	{
		case '.rar':
			return wpfilebase_get_rar_file_list($archive, $skip_zero_files);
		case '.zip':
			return wpfilebase_get_zip_file_list($archive, $skip_zero_files);
		default:
			// even try listing with other extensions, it could be an exe-package or something else
			$result = wpfilebase_get_zip_file_list($archive, $skip_zero_files);
			if(empty($result) || !is_array($result) || count($result) == 0)
				return wpfilebase_get_rar_file_list($archive, $skip_zero_files);
			else
				return $result;
	}
}
*/

function wpfilebase_get_file_content_type($name)
{
	$pos = strrpos($name, '.');
	if($pos !== false)
		$name = substr($name, $pos + 1);
	switch ($name)
	{
		case 'zip':		return 'application/zip';
		case 'bin':
		case 'dms':
		case 'lha':
		case 'lzh':
		case 'exe':
		case 'class':
		case 'so':
		case 'dll':		return 'application/octet-stream';   
		case 'ez':  	return 'application/andrew-inset';
		case 'hqx':		return 'application/mac-binhex40';
		case 'cpt':		return 'application/mac-compactpro';
		case 'doc':		return 'application/msword';
		case 'oda':		return 'application/oda';
		case 'pdf':		return 'application/pdf';
		case 'ai':
		case 'eps':
		case 'ps':		return 'application/postscript';
		case 'smi':
		case 'smil':	return 'application/smil';
		case 'xls':		return 'application/vnd.ms-excel';
		case 'ppt':		return 'application/vnd.ms-powerpoint';
		case 'wbxml':	return 'application/vnd.wap.wbxml';
		case 'wmlc':	return 'application/vnd.wap.wmlc';
		case 'wmlsc':	return 'application/vnd.wap.wmlscriptc';
		case 'bcpio':	return 'application/x-bcpio';
		case 'vcd':		return 'application/x-cdlink';
		case 'pgn':		return 'application/x-chess-pgn';
		case 'cpio':	return 'application/x-cpio';
		case 'csh':		return 'application/x-csh';
		case 'dcr':
		case 'dir':
		case 'dxr':		return 'application/x-director';
		case 'dvi':		return 'application/x-dvi';
		case 'spl':		return 'application/x-futuresplash';
		case 'gtar':	return 'application/x-gtar';
		case 'hdf':		return 'application/x-hdf';
		case 'js':  	return 'application/x-javascript';
		case 'skp':
		case 'skd':
		case 'skt':
		case 'skm':		return 'application/x-koan';
		case 'latex':	return 'application/x-latex';
		case 'nc':
		case 'cdf':		return 'application/x-netcdf';
		case 'sh':		return 'application/x-sh';
		case 'shar':	return 'application/x-shar';
		case 'swf':		return 'application/x-shockwave-flash';
		case 'sit':		return 'application/x-stuffit';
		case 'sv4cpio':	return 'application/x-sv4cpio';
		case 'sv4crc':	return 'application/x-sv4crc';
		case 'tar':		return 'application/x-tar';
		case 'tcl':		return 'application/x-tcl';
		case 'tex':		return 'application/x-tex';
		case 'texinfo':
		case 'texi':	return 'application/x-texinfo';
		case 't':
		case 'tr':
		case 'roff':	return 'application/x-troff';
		case 'man':		return 'application/x-troff-man';
		case 'me':		return 'application/x-troff-me';
		case 'ms':		return 'application/x-troff-ms';
		case 'ustar':	return 'application/x-ustar';
		case 'src':		return 'application/x-wais-source';
		case 'xhtml':
		case 'xht':		return 'application/xhtml+xml';
		case 'au':  	return 'audio/basic';
		case 'snd':		return 'audio/basic';
		case 'mid':		return 'audio/midi';
		case 'midi':	return 'audio/midi';
		case 'kar':		return 'audio/midi';
		case 'mpga':
		case 'mp2':
		case 'mp3':		return 'audio/mpeg';
		case 'aif':
		case 'aiff':
		case 'aifc':	return 'audio/x-aiff';
		case 'm3u':		return 'audio/x-mpegurl';
		case 'ram':
		case 'rm':		return 'audio/x-pn-realaudio';
		case 'rpm':		return 'audio/x-pn-realaudio-plugin';
		case 'ra':		return 'audio/x-realaudio';
		case 'wav':		return 'audio/x-wav';
		case 'pdb':		return 'chemical/x-pdb';
		case 'xyz':		return 'chemical/x-xyz';
		case 'bmp':		return 'image/bmp';
		case 'gif':		return 'image/gif';
		case 'ief':		return 'image/ief';
		case 'jpeg':
		case 'jpg':
		case 'jpe':		return 'image/jpeg';
		case 'png':		return 'image/png';
		case 'tiff':
		case 'tif':		return 'image/tiff';
		case 'djvu':
		case 'djv':		return 'image/vnd.djvu';
		case 'wbmp':	return 'image/vnd.wap.wbmp';
		case 'ras':		return 'image/x-cmu-raster';
		case 'ico':		return 'image/x-icon';
		case 'pnm':		return 'image/x-portable-anymap';
		case 'pbm':		return 'image/x-portable-bitmap';
		case 'pgm':		return 'image/x-portable-graymap';
		case 'ppm':		return 'image/x-portable-pixmap';
		case 'rgb':		return 'image/x-rgb';
		case 'xbm':		return 'image/x-xbitmap';
		case 'xpm':		return 'image/x-xpixmap';
		case 'xwd':		return 'image/x-xwindowdump';
		case 'igs':
		case 'iges':	return 'model/iges';
		case 'msh':
		case 'mesh':
		case 'silo':	return 'model/mesh';
		case 'wrl':
		case 'vrml':	return 'model/vrml';
		case 'css':		return 'text/css';
		case 'html':
		case 'htm':		return 'text/html';
		case 'asc':
		case 'c':
		case 'cc':
		case 'cs':
		case 'h':
		case 'hh':
		case 'cpp':
		case 'hpp':
		case 'txt':		return 'text/plain';
		case 'rtx':		return 'text/richtext';
		case 'rtf':		return 'text/rtf';
		case 'sgml':
		case 'sgm':		return 'text/sgml';
		case 'tsv':		return 'text/tab-separated-values';
		case 'wml':		return 'text/vnd.wap.wml';
		case 'wmls':	return 'text/vnd.wap.wmlscript';
		case 'etx':		return 'text/x-setext';
		case 'xml':
		case 'xsl':		return 'text/xml';
		case 'mpeg':
		case 'mpg':
		case 'mpe':		return 'video/mpeg';
		case 'qt':
		case 'mov':		return 'video/quicktime';
		case 'mxu':		return 'video/vnd.mpegurl';
		case 'avi':		return 'video/x-msvideo';
		case 'movie':	return 'video/x-sgi-movie';
		case 'asf':
		case 'asx':		return 'video/x-ms-asf';
		case 'wm':		return 'video/x-ms-wm';
		case 'wmv':		return 'video/x-ms-wmv';
		case 'wvx':		return 'video/x-ms-wvx';
		case 'ice':		return 'x-conference/x-cooltalk';
		
		default:		return 'application/octet-stream';
	}
}

function wpfilebase_send_file($file_path, $bitrate = 0)
{
	if(!@file_exists($file_path) || !is_file($file_path))
	{
		header('HTTP/1.x 404 Not Found');
		wp_die('File ' . basename($file_path) . ' not found!');
	}
	
	$size = filesize($file_path);
	$time = filemtime($file_path);
	
	if(!($fh = @fopen($file_path, 'rb')))
		wp_die('Could not open file!');
		
	$begin = 0;
	$end = $size;

	if(!empty($_SERVER['HTTP_RANGE']) && strpos($_SERVER['HTTP_RANGE'], 'bytes=') !== false)
	{
		$range = explode('-', trim(substr($_SERVER['HTTP_RANGE'], 6)));
		$begin = 0 + trim($range[0]);
		if(!empty($range[1]))
			$end = 0 + trim($range[1]);
	}
	
	if($begin > 0 || $end < $size)
		header('HTTP/1.0 206 Partial Content');
	else
		header('HTTP/1.0 200 OK');
		
	$length = ($end-$begin);
	wpfilebase_add_traffic($length);
	
	// modifiy some headers...
	header("Last-Modified: " . gmdate("D, d M Y H:i:s", $time) . " GMT"); 
	header("Pragma: public");
	header("Cache-Control: public");
	header("Accept-Ranges: bytes");
	
	// content headers
	header("Content-Description: File Transfer");
	header("Content-Type: " . wpfilebase_get_file_content_type($file_path));
	header("Content-Disposition: attachment; filename=\"" . basename($file_path) . "\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: " . $length);
	if(isset($_SERVER['HTTP_RANGE']))
		header("Content-Range: bytes " . $begin . "-" . ($end-1) . "/" . $size);
	
	header("Connection: close");
	
	@session_destroy();
	
	// send the file!
	
	$bitrate = (float)$bitrate;
	if($bitrate <= 0)
		$bitrate = 1024 * 1024;
	
	$buffer_size = (int)(1024 * min($bitrate, 64));
	
	// convert kib/s => bytes/ms
	$bitrate *= 1024;
	$bitrate /= 1000;

	$cur = $begin;
	fseek($fh,$begin,0);
	while(!@feof($fh) && $cur < $end && @connection_status() == 0)
	{		
		$nbytes = min($buffer_size, $end-$cur);
		$ts = microtime(true);
		
		print @fread($fh, $nbytes);
		@ob_flush();
		@flush();
		
		$dt = (microtime(true) - $ts) * 1000; // dt = time delta in ms		
		$st = ($nbytes / $bitrate) - $dt;
		if($st > 0)
			usleep($st * 1000);			
		
		$cur += $nbytes;
	}
	
	@fclose($fh);	
	return true;
}

?>