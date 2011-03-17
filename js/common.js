var wpfbFileUrls = {};

function wpfb_print(obj) {
	var str = ' '+obj+':';
	for(var k in obj) str += ' ['+k+'] = '+obj[k]+'\n';
	alert(str);
}
function wpfilebase_filedetails(id) {
	var dtls = document.getElementById('wpfilebase-filedetails' + id);
	if(dtls) dtls.style.display = (dtls.style.display!='none') ? 'none' : 'block';	
	return false;
}

function wpfb_getFileInfo(url)
{
	var f={id:-1,path:''},uesc=unescape(url);
	for (var i in wpfbFileUrls) {
		if(wpfbFileUrls[i] == url || wpfbFileUrls[i] == uesc) {
			f.id = i;
			break;
		}
	}	
	if(f.id <= 0 || typeof wpfbFPaths[f.id] != 'string') {
		try{// to get url by ajax request
			f = jQuery.parseJSON(jQuery.ajax({url:wpfbConf.ajurl,data:{action:"fileinfo",url:url},async:false}).responseText);
			if(!f || f.id <= 0) return null;
			wpfbFileUrls[f.id] = url;
			wpfbFPaths[f.id] = f.path;
		} catch(err){return null;}
	} else f.path = wpfbFPaths[f.id];	
	return f;
}

function wpfb_ondownload(url) {
	if(typeof url != 'string') url = url.data;
	if(typeof(wpfb_ondl) == 'function') {
		var fi = wpfb_getFileInfo(url);
		try { wpfb_ondl(fi.id,'/'+wpfbConf.db+'/'+fi.path); }
		catch(err){}
	}
}

function wpfb_onclick(event)
{
	wpfb_ondownload(event);	
	if(wpfbConf.hl) { // hide links
		try {window.location=event.data;return false;}catch(err){}
	}
	return true;
}

function wpfb_findId(el, url) {
	var res,i,u,uesc=unescape(url);
	
	// by hook or query string
	if((res = url.match(/#wpfb-file-([0-9]+)/)) || (res = url.match(/\?wpfb_dl=([0-9]+)/)))
		return res[1];
	
	// by permalink in table
	for (i in wpfbFPaths) {
		u = wpfbConf.su+wpfbConf.db+'/'+wpfbFPaths[i];
		if(u == url || u == uesc) return i;
	}
	
	 // and by parent id
	if((p = el.parents('[id^="wpfb-file-"]')).size() > 0)
		return parseInt(p.attr('id').substr(10));
	
	return -1;
}

function wpfb_processlink(index, el)
{
	var url=el.href, fid=0, i;
	el = jQuery(el);
	fid = wpfb_findId(el, url);	
	if((i=url.indexOf('#')) > 0) url = url.substr(0, i); // remove hook, not actually needed
	el.unbind('click').click(url, wpfb_onclick); // bind onclick
	if(fid > 0) { // store in table
		wpfbFileUrls[fid] = url;
		if(wpfbConf.cm && typeof(wpfb_addContextMenu) == 'function') wpfb_addContextMenu(el, fid);
		if(wpfbConf.hl) url = 'javascript:;';
	}
	el.attr('href', url);
}

function wpfb_processimg(index, el)
{
	jQuery(el).unbind('load').load(el.src, wpfb_ondownload);
}

function wpfb_setupLinks() {
	if(!wpfbConf.ql) return;
	var us = wpfbConf.pl ? ('^="'+wpfbConf.su+wpfbConf.db+'/') : ('*="\\?wpfb_dl=');
	jQuery('a[href'+us+'"]').each(wpfb_processlink);
	if(wpfbConf.cm) jQuery('a[href*="#wpfb-file-"]').each(wpfb_processlink);
	jQuery('img[src'+us+'"]').each(wpfb_processimg);
}
jQuery(document).ready(wpfb_setupLinks);