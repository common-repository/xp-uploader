=== XP Uploader ===
Contributors: Reuzel
Tags: YAPB, images, upload, wizard
Requires at least: 2.5
Tested up to: 2.5.1
Stable tag: 0.2

This plugin allows images to be uploaded directly from Windows Explorer to a YAPB photo-blog using the
Windows XP Publish to Web Wizard.

== Description ==

This [Wordpress][1] plugin allows images to be uploaded to a photo-blog using the
[Windows XP Publish to Web Wizard][2]. The current implementation requires the 
excellent [Yet Another Photo Blog' plugin from J.P.Jarolim][3] to be installed
and active.

When this plugin is activated, an additional Options page is added titled XP-Upload. 
This page contains a link that will lead to the download of a xppubwiz.reg file, 
containing the necessary windows registry settings to enable the upload functionality. 
Register the entries in this file by double-clicking the file. When done so, select 
some images in explorer, and in the left pane select the "publish to web" option. 
There you will find your blog. 

[1]: http://wordpress.org/
[2]: http://msdn.microsoft.com/en-us/library/bb776790(VS.85).aspx
[3]: http://wordpress.org/extend/plugins/yet-another-photoblog/

== Installation ==

This section describes how to install the plugin and get it working.

1. Install and activate the [YAPB plugin][1]
1. Copy the contents of the zip file to <your wp install dir>/wp-content/plugins. 
1. Activate the plugin
1. Download and install the registry file on the machines you want to upload from

When this plugin is activated, an additional Options page is added titled XP-Upload. 
This page contains a link that will lead to the download of a xppubwiz.reg file, 
containing the necessary windows registry settings to enable the upload functionality. 
Register the entries in this file by double-clicking the file. When done so, select 
some images in explorer, and in the left explorer pane select the "publish to web" option. 
There you will find your blog. 

[1]: http://wordpress.org/extend/plugins/yet-another-photoblog/

== Frequently Asked Questions ==

= Is it secure? =

Only registered users can use this wizard. The plugin reuses Wordpress' security/login mechanisms, so basically
if a user is allowed to post, he or she is allowed to use this publish wizard.

= Hey, no image is uploaded! =

For some reason there are many issues regarding image upload. Here some hints on why no image seems to be uploaded:

* Make sure that YAPB is installed, because this wizard only works in combination with that
* Make sure you have enough memory. Memory problems occur often when processing the images. This may  lead to situations that the image is uploaded, but not displayed. 
* Make sure that the image is not too large, resulting in a timeouts or file-upload-size refusals.

= I deactivated this plugin and it still shows up in Windows XP! =

Yep, that happens. Regrettably you can not remove registry entries using .reg files. This means that you will have to delete those entries by hand. Note: Manually editing registry entries may harm your PC's install. For those who dare:

1. Go to the XP 'start' menu, and click 'run...'
1. type 'regedit' in the run dialog box and press enter
1. browse to `HKEY_CURRENT_USER\Software\Microsoft\Windows\CurrentVersion\Explorer\PublishingWizard\PublishingWizard\Providers` 
1. there, select your site. It will typically start with `http___<your_domain_name>`.
1. make really sure you have selected your site
1. press delete. regedit will ask for confirmation: "Are you sure you want to delete this key and all of its subkeys?"
1. If you're really sure, click 'Yes'
1. Done, your site should be gone from the wizard's menus.


== Change Log ==

= 26 May 2008 =

* Updated login code to work with refactored sign-on code of wordpress 2.5
* fixed a bug in URL generation
* fixed a bug in category selection form