=== WP-Filebase ===
Contributors: fabifott
Donate link: http://fabi.me/
Tags: filebase, filemanager, file, files, manager, upload, download, downloads, downloadmanager, traffic, widget, filelist, list, thumb, thumbnail, attachment, attachments, category, categories, media, template
Requires at least: 2.0.2
Tested up to: 2.8.4
Stable tag: 0.1.0.1

Adds a powerful download manager supporting file categories, thumbnails, traffic/bit rate limits and more to your WordPress blog.

== Description ==

WP-Filebase is a powerful download manager supporting file categories, thumbnails and more.
Uploaded files can be associated with a post or page so the download URL, thumbnail and other file information are appended automatically to the content.
Additionally the downloadmanager offers options to limit traffic and download speed.

Some more features:

*   Arrange files in categories and sub-categories
*   Automatically creates thumbnails of images (JPEG, PNG, GIF, BMP)
*   Powerful template engine (variables, IF-Blocks)
*   Associate files to posts and automatically attach them to the content
*   Customisable file list widget
*   Hotlinking protection
*   Daily and monthly traffic limits
*   Download speed limiter for registered users and anonymous
*   Range download (allows user to pause downloads and continue them later)
*   Works with permalink structure
*   Download counter which ignores multiple downloads from the same client
*   Many file properties like author, version, supported languages, platforms, license ...

You can see a [live demo on my Website](http://fabi.me/ "WP-Filebase demo")

== Installation ==

1. Upload the `wp-filebase` folder with all it's files to `wp-content/plugins/`
2. Create the directory `/wp-content/uploads/filebase` and make it writeable (FTP command: `CHMOD 777 wp-content/uploads/filebase`)
3. Activate the Plugin and customize the settings under `Settings->WP-Filebase`

== Screenshots ==

1. Example of three auto-attached files
2. The WP-Filebase Widget

== Changelog ==

= 0.1.0.1 =
* Added download permissions, each file can have a minimum user level
* New Editor Tag `[filebase:attachments]` which lists all files associated with the current article
* Fixed missing `file_requirements` template field. You should reset your WP-Filebase settings if you want to use this.

= 0.1.0.0 =
* First version