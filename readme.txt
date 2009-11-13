=== WP-Filebase ===
Contributors: fabifott
Donate link: http://fabi.me/donate/
Tags: filebase, filemanager, file, files, manager, upload, download, downloads, downloadmanager, traffic, widget, filelist, list, thumb, thumbnail, attachment, attachments, category, categories, media, template, ftp, http
Requires at least: 2.2.0
Tested up to: 2.8.5
Stable tag: 0.1.2.0

Adds a powerful download manager supporting file categories, thumbnails, traffic/bandwidth limits and more to your WordPress blog.

== Description ==

WP-Filebase is a powerful download manager supporting file categories, thumbnails and more.
Uploaded files can be associated with a post or page so the download URL, thumbnail and other file information are appended automatically to the content.
Additionally the downloadmanager offers options to limit traffic and download speed.

Some more features:

*   Powerful filemanger to arrange files in categories and sub-categories
*   Insert file lists in posts and pages (with Editor Button)
*   Automatically creates thumbnails of images (JPEG, PNG, GIF, BMP)
*   Upload files with your browser or FTP client (**NEW**)
*   Powerful template engine (variables, IF-Blocks)
*   Associate files to posts and automatically attach them to the content
*   Customisable file list widget
*   Multiple custom templates for filelists (**NEW**)
*   Hotlinking protection
*   Daily and monthly traffic limits
*   Download speed limiter for registered users and anonymous
*   Traffic limits
*   Range download (allows user to pause downloads and continue them later)
*   Works with permalink structure
*   Download counter which ignores multiple downloads from the same client
*   Many file properties like author, version, supported languages, platforms and license
*   Custom JavaScript code which is executed when a download link is clicked (e.g. to track downloads with Google Analytics)
*	Works with WP Super Cache

You can see a [live demo on my Website](http://fabi.me/ "WP-Filebase demo")

**If you want to report a bug or have any problems with this Plugin please post your WordPress and PHP Version!**

**Note:** If you only want to limit traffic or bandwidth of media files you should take a look at my [Traffic Limiter](http://wordpress.org/extend/plugins/traffic-limiter/ "Traffic Limiter").

== Installation ==

1. Upload the `wp-filebase` folder with all it's files to `wp-content/plugins/`
2. Create the directory `/wp-content/uploads/filebase` and make it writable (FTP command: `CHMOD 777 wp-content/uploads/filebase`)
3. Activate the Plugin and customize the settings under `Settings->WP-Filebase`

== Frequently Asked Questions ==

= How do I insert a file list into a post?  =

In the post editor click on the *WP-Filebase* button. In the appearing box click on *File list*, then select a category. Optionally you can select a custom template.

= How do I list a categories, sub categories and files?  =

To list all categories and files on your blog, create an empty page (e.g named *Downloads*) and select it in the post browser for the option *Post ID of the file browser* under WP-Filebase Settings.
Now a file browser should be appended to the content of the page.

= How do I add files with FTP? =

Upload all files you want to add to the WP-Filebase upload directory (default is `wp-content/uploads/filebase`) with your FTP client. Then goto WP-Admin -> Tools -> WP-Filebase and click *Sync Filebase*. All your uploaded files are added to the database now. Categories are created automatically if files are in sub folders.

= How do I customize the appearance of filelists and attached files? =

You can change the HTML template under WP-Admin -> Settings -> WP-Filebase. To edit the stylesheet goto WP-Admin -> Tools -> WP-Filebase and click *Edit Stylesheet*.
Since Version 0.1.2.0 you can create your custom templates for individual file lists. You can manage the templates under WP-Admin -> Tools -> WP-Filebase -> Manage templates. When adding a tag to a post/page you can now select the template.

== Screenshots ==

1. Example of three auto-attached files
2. The WP-Filebase Widget
3. The Editor Button to insert tags for filelists and download urls

== Changelog ==

= 0.1.2.1 =
* Added category template for category listing
* Added file browser which lists categories and files
* Added option to disable download permalinks
* Fixed a problem with download permalinks
* Fixed an issue with auto attaching files

= 0.1.2.0 =
* Added multiple templates support (you can now create custom templates for file lists)
* Added option *Hide inaccessible files* and *Inaccessible file message*
* When resetting WP-Filebase settings the traffic stats are retained now
* Fixed *Manage Categories* button
* Enhanced content tag parser
* Added support for HTTP `ETag` header (for caching)
* Improved template generation

= 0.1.1.5 =
* Added CSS Editor
* Added max upload size display
* Fixed settings error `Missing argument 1 for WPFilebaseItem::WPFilebaseItem()`
* Fixed widget control
* Fixed an issue with browser caching and hotlink protection

= 0.1.1.4 =
* Download charset HTTP header fixed
* Editor file list fixed
* New file list option `Uncategorized Files`

= 0.1.1.3 =
* Added FTP upload support (use `Sync Filebase` to add uploaded files)
* Code optimizations for less server load
* File requirements can include URLs now
* Fixed options checkbox bug
* Fixed an issue with the editor button
* Fixed form URL query issue
* Some fixes for Windows platform support

= 0.1.1.2 =
* Fixes - for PHP 4 only

= 0.1.1.1 =
* Now fully PHP 4 compatible (it is strongly recommended to update to PHP 5)
* Fixed a HTTP header bug causing trouble with PDF files and Adobe Acrobat Reader
* New option *Always force download*: if enabled files that can be viewed in the browser (images, videos...) can only be downloaded (no streaming)
* Attachement lists are sorted now
* The MD5 hash line in the file template is now commented out by default
* Fixed `Fatal error: Cannot redeclare wpfilebase_inclib()`

= 0.1.1.0 =
* Added simple upload form with less options which is shown by default
* Fixed editor button
* Changed editor tag box
* Selection fields in the file upload form are removed if there are no entries
* You can now enter custom JavaScript Code which is executed when a download link is clicked (e.g. to track downloads with Google Analytics)
* If no display name is entered it will be generated from the filename
* Removed the keyword `private` in class property declarations to make the plugin compatible with PHP 4
* Serveral small bug fixes
* CSS fixes
* Optimized code to decrease memory usage

= 0.1.0.3 =
* Added file list sorting options
* Rearranged options
* Fixed `Direct linking` label of upload form
* Added HTML link titles of the default template (to enable this change you must reset your options to defaults)

= 0.1.0.2 =
* Fixed a HTTP cache header
* Added support for HTTP If-Modified-Since header (better caching, lower traffic)

= 0.1.0.1 =
* Added download permissions, each file can have a minimum user level
* New Editor Tag `[filebase:attachments]` which lists all files associated with the current article
* Fixed missing `file_requirements` template field. You should reset your WP-Filebase settings if you want to use this.

= 0.1.0.0 =
* First version