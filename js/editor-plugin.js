function tabclick(a)
{
	var href = a.getAttribute('href');	
	var tabLinks = jQuery("a", a.parentNode.parentNode).toArray();
	var h,tl,tab;
	for(var i = 0; i < tabLinks.length; i++)
	{
		h = tabLinks[i].getAttribute('href');
		h = h.substr(h.indexOf('#'));
		tab = jQuery(h);
		tl = jQuery(tabLinks[i]);
		if(href == tabLinks[i].getAttribute('href')) {
			tl.addClass('current');
			tab.show();
		} else {
			tl.removeClass('current');
			tab.hide();				
		}
	}

	currentTab = href.substr(href.indexOf('#')+1);
	if(typeof(currentTab) != 'string' || currentTab.length < 2) {
		alert('Something wrong with tab link: '+href);
		currentTab = href;
	}
	
	var showEls = {
		'fileselect': (currentTab == 'file' || currentTab == 'fileurl'),
		'filetplselect': (currentTab == 'file'),
		'catselect': (currentTab == 'list' /* || currentTab == 'browser'*/),
		'listtplselect': (currentTab == 'list')
	};

	for(var id in showEls) {
		if(showEls[id]) jQuery('#'+id).show();
		else  jQuery('#'+id).hide();
	}
	
	return false;
}

function selectCat(id, name)
{
	var selected = false;
	var el = jQuery('span.folder','#catsel-cat-'+id).first();

	for(var i=0; i<selectedCats.length; i++) {
		if (selectedCats[i] == id) {
			selected = true;
			selectedCats.splice(i, 1);
			break;
		}
	}
	if(!selected) selectedCats.push(id);	
	el.css('background-image', selected?'':('url('+yesImgUrl+')'));
}

function incAllCatsChanged(value) {
	includeAllCats = !!value;

	if(includeAllCats)
		jQuery("#catbrowser").hide();
	else
		jQuery("#catbrowser").show();
}

function editorInsert(str, close)
{
	var win = window.dialogArguments || opener || parent || top;
	if(win && win.send_to_editor) {
		win.send_to_editor(str);
		if(typeof close != 'undefined' && close) {
			if(typeof(win.tinymce) != 'undefined')
				win.tinymce.EditorManager.activeEditor.windowManager.close(window);
			else
			{/*
				var regex = /^cke_dialog_close_button_([0-9]+)/;				
				var els = win.document.getElementsByTagName('a'), aid;
				for(i=0;i<els.length;i++){
					aid = els[i].getAttribute('id');
					if(aid && aid.search(regex) == 0) {
						alert(els[i].click);
						els[i].click();
						break;
					}
				}
				*/
			}
		}
		return true;
	}
	return false;
}

function insertTag(tagObj)
{
	var str = '[wpfilebase';
	var q, v;

	if(tagObj.tag == 'fileurl' && tagObj.linktext) {
		str = '<a href="'+str;
	}
	
	for(var t in tagObj) {
		v = tagObj[t];
		if(v != '' && t != 'linktext') {
			q = (!isNaN(v) || v.search(/^[a-z0-9-]+$/i) != -1) ? "" : "'";			
			str += ' '+t+"="+q+v+q;
		}
	}
	str+=']';

	if(tagObj.tag == 'fileurl' && tagObj.linktext)
		str += '">'+tagObj.linktext+'</a>';
	return editorInsert(str, true);
}

function insAttachTag()
{
	if(editorInsert("[wpfilebase tag='attachments']", false)) {
		jQuery('#no-auto-attach-note').hide();
		return true;
	}
	return false;
}

function insListTag() {
	/*if(selectedCats.length == 0) {
		alert('Please select at least one category!');
		return;
	}*/
	var tag = {tag:currentTab};

	if(!includeAllCats) {
		if(selectedCats.length == 0) {
			alert("Please select at least one category!");
			return false;
		}
		tag.id = selectedCats.join(',');
	}
		
	var tpl = jQuery('input[name=listtpl]:checked', '#listtplselect').val();
	if(tpl && tpl != '' && tpl != 'default') tag.tpl = tpl;
	
	var sortby = jQuery('#list-sort-by').val();	
	if(sortby && sortby != '') {
		var order = jQuery('input[name=list-sort-order]:checked', '#list').val();
		if(order == 'desc') sortby = '&gt;'+sortby;
		else if(order == 'asc') sortby = '&lt;'+sortby;
		tag.sort = sortby;
	}
	
	var showcats = !!jQuery('#list-show-cats:checked').val();
	if(showcats) tag.showcats = 1;
	
	var num = parseInt(jQuery('#list-num').val());
	if(num != 0) tag.num = num;
	
	return insertTag(tag);
}

var reloadTimer = -1;
function delayedReload() {
	if(reloadTimer != -1)
		window.clearTimeout(reloadTimer);
	reloadTimer = window.setTimeout("window.location.reload()", 10000);
}


function getFilePath(id) {
	var fi = jQuery.parseJSON(jQuery.ajax({url:wpfbConf.ajurl, data: {action:"fileinfo","id":id} ,async:false}).responseText);
	return (fi != null && fi.path != '') ? fi.path : '';	
}

function getCatPath(id) {
	var ci = jQuery.parseJSON(jQuery.ajax({url:wpfbConf.ajurl, data:{action:"catinfo","id":id},async:false}).responseText);
	return (ci != null && ci.path != '') ? ci.path : '';	
};

