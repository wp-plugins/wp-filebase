(function() {	tinymce.create('tinymce.plugins.wpfilebase', {		init: function(ed, url) {			ed.addButton('wpfbInsertTag', {				title: 'WP-Filebase',				image: url + '/images/btn.gif',				onclick: (function() {					var postId = '';					try { postId = tinymce.DOM.get("post_ID").value; } catch(ex) {}					ed.windowManager.open(													{file: (url+'/../editor_plugin.php?post_id='+postId+'&content='+escape(tinyMCE.activeEditor.selection.getContent())), title:'WP-Filebase', width: 680, height: 400, inline: 1},						{plugin_url: url}					);				})			});		},				createControl: function(n, cm) { return null; },				getInfo: function() {			return {				longname: 'WP-Filebase',				author: 'Fabian Schlieper',				authorurl: 'http://fabi.me/',				infourl: 'http://fabi.me/wp-filebase/',				version: '1.0'			};		}	});	tinymce.PluginManager.add('wpfilebase', tinymce.plugins.wpfilebase);})();