=== Plugin Name ===
Contributors: CadetCrusher
Donate link: http://xforward.com/
Tags: email posting, image attachments, media attachments, mobile posting, post by email, video posts, WordPress plugin
Requires at least: 2.5
Tested up to: 2.7
Stable tag: 2.1.0

PostMaster plugin processes all posts-by-email, extracting any attached files and embedding them into the 
body of the post. Now with multiple attachment support!

== Description ==

Posting text, photos and videos from your phone has never been easier!
With PostMaster you can post via email with attachments. PostMaster embeds attached media into your post, 
so no more boring text-only posts from your phone!

Version 2.1.0 introduces support for multiple image attachments!
Now you can attach as many images as you'd like to your message and they'll all be extracted, resized, and embedded.

PostMaster allows you to control the resizing of attached images. So, you can have thumbnail images created 
on the fly! You can also have the generated thumbnail link to the full-size image, automatically.

PostMaster gives you control over the categories you want your post to appear in. With the category assignment 
feature you can define the categories for your post from the subject line.

You can control how media is embedded in the post body by editing PostMaster templates. There is a template 
for each media type group (images, videos, etc).

Almost all phones and email services are supported.

Visit [xforward.com](http://xforward.com/index.php/work/project-detail/postmaster_wordpress_plugin/) for more information and updates.


== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the 'postmaster' directory to the '/wp-content/plugins/' directory of your WordPress installation. If you are upgrading, just replace all of the files with the ones from the downloaded zip archive.
Keep in mind that we will be moving the wp-mail.php file (that is part of the PostMaster Distro) to the root directory of your WordPress installation in the following steps.
2. Make a copy of your original wp-mail.php and call it wp-mail.php.bak. This file is found in the root directory of your WordPress installation.
3. Upload/move the wp-mail.php that is included with the distribution to the root directory of your WordPress installation.
4. Run the `pm_upgrade.php` script. *Do this even if it's a fresh install!* You can just run it through a URL like: http://host/wp-content/plugins/postmaster/pm_upgrade.php
5. Activate the plugin through the 'Plugins' menu in WordPress
6. Create a cron job to run the wp-mail.php script (e.g. wget -N http://yourdomain.com/wp-mail.php). This is necessary in order to make
WordPress check it's email account periodically. For more info on this see this page [WordPress Blog by Email](http://codex.wordpress.org/Blog_by_Email).
7. Start sending emails with attachments to your blog-by-email account.

== Frequently Asked Questions ==

= What video formats are supported ?=

Currently (2.1.0) only the 3g2 format is supported (actually it supports application/octet-stream MIME type). You can try other video formats, but it is not guaranteed that they will work.

= Are there any guidelines I should follow when sending an email post? =

No.
Version 2.1.0 fixes the blank line issue, so you can write anything you want.

= My email-posts aren't showing up correctly. What's the deal? =

PostMaster only extracts the plaintext portion of your message. It disregards the HTML text because more often than not the HTML in the message will cause your site to break.
Support for HTML email may be added in the future.

It also could be due to the email service you are using. Unfortunately, not every email service sends attachments the same way. PostMaster has been tested with most major email services and phones.
If you would like your email service supported by PostMaster, send the following information to pm_requests@xforward.com
1. The name of the service (i.e. @hotmail.com)
2. A text file containing the output produced by PostMaster. Turn on the 'Debuging' feature in the PostMaster Options menu. Then run wp-mail.php, you will see the debugging output.
3. Any other information that might be useful (message sent through webmail, pop client? Were you sending video, image, none? etc).

= My attachments aren't showing up in the post, why? =

This may be due to the fact that the directory you have chosen to save attachments to does not exist, does not have the proper permissions, or is outside of the 
WordPress root directory.

= My video isn't embedding correctly, why? =

The default video template has been tested with 3g2 video and FireFox. I don't guarantee it will work with other video format's or browsers.

= I found a bug, you dolt! How can I punish you? =

Send any bugs to pm_bugs@xforward.com
Send any feature requests to pm_requests@xforward.com
