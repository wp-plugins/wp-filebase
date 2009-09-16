=== Plugin Name ===
Contributors: fabifott
Donate link: http://fabi.me/
Tags: filebase, filemanager, file, files, upload, download, downloads, downloadmanager, traffic, widget, filelist, list, trhumb, thumbnail
Requires at least: 2.0.2
Tested up to: 2.8.4
Stable tag: 0.1.0.0

Adds a powerful download manager supporting file categories, thumbnails, traffic/bit rate limits and more to your WordPress blog.

== Description ==
WP-Filebase is a powerful download manager supporting file categories, thumbnails and more.
Uploaded files can be associated with a post or page so the download URL, thumbnail and other file information are appended automatically to the content.
Additionally there are options to limit traffic and download speed.

Some more features:
* arrange files in categories and sub-categories
* automatically creates thumbnails of images (JPEG, PNG, GIF, BMP)
* powerful template engine (variables, IF-Blocks)
* associate files to posts and automatically attach them to the content
* customisable file list widget
* hotlinking protection
* daily and monthly traffic limits
* download speed limiter for registered users and anonymous
* range download (allows user to pause downloads and continue them later)
* works with permalink structure
* download counter which ignores multiple downloads from the same client
* many file properties like author, version, supported languages, platforms, license ...

== Installation ==
1. Upload the `wp-filebase` folder with all it's files to `wp-content/plugins/`
2. Create the directory `/wp-content/uploads/filebase` and make it writeable (FTP command: `CHMOD 777 wp-content/uploads/filebase`)
