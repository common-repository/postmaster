<?php
require(dirname(__FILE__).'/../../../wp-config.php');

$image_template_default = 
		'<a href="%img_url%"><img src="%img_url_resize%" border="0" width="%img_w%" height="%img_h%" /></a><br />';

$image_post_template_default =
'%img_template%%post_text%';

$video_template_default = '<OBJECT classid=\'clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\' codebase=\'http://www.apple.com/qtactivex/qtplugin.cab\'><param name="src" value="%video_url%"><param name="autoplay" value="false"><param name="controller" value="true"><param name="loop" value="false">
	<EMBED src="%video_url%" autoplay="false" controller="true" loop="false" pluginspage=\'http://www.apple.com/quicktime/download/\'></EMBED></OBJECT><br/>%post_text%';
        
update_option('pm_image_post_template', $image_post_template_default);
update_option('pm_video_post_template', $video_template_default);
update_option('pm_image_template', $image_template_default);

// make the default directories if needed
if(!is_dir(dirname('attachments'))) {
	mkdir('attachments');
}

?>
PostMaster Upgrade complete.<br/>

<a href="<?php echo get_option('siteurl'); ?>">Home</a><br/>
<a href="<?php echo get_option('siteurl').'/wp-login.php'; ?>">Login</a><br/>
