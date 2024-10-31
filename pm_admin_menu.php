<?php

if(get_option('pm_debug_enabled') == 'off' || get_option('pm_debug_enabled') == '') {
	error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);
}

add_action('admin_menu', 'pm_add_options_menu');

/**
* This function asks for the current released version of PostMaster.
* If the version sent is lower than the currently available version
* the response will be markup that creates an option for the user to 
* upgrade.
*/
function pm_get_current_version() {
	$url1 = 'http://xforward.com/postmasterAPI.php?method=doUpdateCheck&version=2.0';
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $url1);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 5);
	$gurl1 = curl_exec($ch);
	curl_close($ch);

	return $gurl1;
}

function pm_add_options_menu() {
	// Add a new submenu under Options:
    add_options_page('PostMaster Settings', 'PostMaster', 8, 'pmoptions', 'pm_options_page');
}

/**
* This function loads default values if necessary
*/
function pm_load_default_options(&$show_credit, &$image_template, 
	&$post_with_image_template, &$video_template, &$img_h, &$img_w, &$attachment_dir, 
	&$rename_attachment, &$debug_enabled, &$resize_enabled, 
	&$resize_width, &$resize_height, &$keep_image, &$add_cats_enabled,
	&$auto_publish_enabled, &$thumb_dir) {

	// defaults
	// new 2/12/2009 adding image template and changing existing 'image_template'
	// to post_with_image_template
	$image_template_default = 
		'<a href="%img_url%"><img src="%img_url_resize%" border="0" width="%img_w%" height="%img_h%" /></a><br />';
	$post_with_image_template_default =
	'%img_template%%post_text%';
	$video_template_default = '<OBJECT classid=\'clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B\' codebase=\'http://www.apple.com/qtactivex/qtplugin.cab\'><param name="src" value="%video_url%"><param name="autoplay" value="false"><param name="controller" value="true"><param name="loop" value="false">
        <EMBED src="%video_url%" autoplay="false" controller="true" loop="false" pluginspage=\'http://www.apple.com/quicktime/download/\'></EMBED></OBJECT><br/>%post_text%';

	$show_credit = ($show_credit == NULL) ? 'on' : $show_credit;
	$post_with_image_template = 
		($post_with_image_template == FALSE) ? $post_with_image_template_default : $post_with_image_template;
	$image_template = ($image_template == FALSE) ? $image_template_default : $image_template;
	$video_template = ($video_template == FALSE) ? $video_template_default : $video_template;
	$img_h = ($img_h == FALSE) ? 0 : $img_h;
	$img_w = ($img_w == FALSE) ? 0 : $img_w;
	$attachment_dir = ($attachment_dir == FALSE) ? 'attachments' : $attachment_dir;
	$rename_attachment = ($rename_attachment == NULL) ? 'on' : $rename_attachment;
	$debug_enabled = ($debug_enabled == NULL) ? 'off' : $debug_enabled;
	$resize_enabled = ($resize_enabled == NULL) ? 'off' : $resize_enabled;
	$resize_width = ($resize_width == FALSE) ? 0 : $resize_width;
	$resize_height = ($resize_height == FALSE) ? 0 : $resize_height;
	$keep_image = ($keep_image == NULL) ? 'off' : $keep_image;
	$add_cats_enabled = ($add_cats_enabled == NULL) ? 'off' : $add_cats_enabled;
	$auto_publish_enabled = ($auto_publish_enabled == NULL) ? 'off' : $auto_publish_enabled;
	$thumb_dir = ($thumb_dir == FALSE) ? 'thumbs' : $thumb_dir;
}

/**
* This function creates the options menu page, and does crud operations on 
* the options.
*/
function pm_options_page() {
	// finally got around to making the options a name-value hash
	$pm_options = array('pm_image_post_template' => get_option('pm_image_post_template'),
		'pm_video_post_template' => get_option('pm_video_post_template'),
		'pm_image_template' => get_option('pm_image_template'),
		'pm_credit_flag' => get_option('pm_credit_flag'),
		'pm_image_width' => get_option('pm_image_width'),
		'pm_image_height' => get_option('pm_image_height'),
		'pm_attachment_dir' => get_option('pm_attachment_dir'),
		'pm_rename_attachment' => get_option('pm_rename_attachment'),
		'pm_debug_enabled' => get_option('pm_debug_enabled'),
		'pm_resize_enabled' => get_option('pm_resize_enabled'),
		'pm_resize_width' => get_option('pm_resize_width'),
		'pm_resize_height' => get_option('pm_resize_height'),
		'pm_keep_orig_image' => get_option('pm_keep_orig_image'),
		'pm_assign_cats_enabled' => get_option('pm_assign_cats_enabled'),
		'pm_auto_publish_enabled' => get_option('pm_auto_publish_enabled'),
		'pm_thumb_dir' => get_option('pm_thumb_dir'));

    $hidden_field_name = 'mt_submit_hidden';

	// see if the user is testing the attachment directory
	if( $_POST[ $hidden_field_name ] == 'Y' && isset($_POST['pm_test_dir'])) {
		$test_attach_dir_result = pm_test_attachment_dir($_POST['pm_attachment_dir']);
		$pm_options['pm_attachment_dir'] = $_POST['pm_attachment_dir'];
	}
    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( $_POST[ $hidden_field_name ] == 'Y' && !isset($_POST['pm_test_dir'])) {
        // Read their posted value
		$pm_options['pm_credit_flag'] = (!isset($_POST['pm_credit_flag' ])) ? 'off' : 'on';
        $pm_options['pm_image_post_template'] = str_replace('\\', '', $_POST[ 'pm_image_post_template' ]);
		$pm_options['pm_video_post_template'] = str_replace('\\', '', $_POST[ 'pm_video_post_template' ]);
		$pm_options['pm_image_template'] = str_replace('\\', '', $_POST['pm_image_template']);
		$pm_options['pm_image_width'] = $_POST[ 'pm_image_width' ];
		$pm_options['pm_image_height'] = $_POST[ 'pm_image_height' ];
		$pm_options['pm_attachment_dir'] = $_POST[ 'pm_attachment_dir' ];
		$pm_options['pm_rename_attachment'] = (!isset($_POST['pm_rename_attachment'])) ? 'off' : 'on';
		$pm_options['pm_debug_enabled'] = (!isset($_POST['pm_debug_enabled'])) ? 'off' : 'on';
		$pm_options['pm_resize_enabled'] = (!isset($_POST['pm_resize_enabled'])) ? 'off' : 'on';
		$pm_options['pm_resize_width'] = $_POST[ 'pm_resize_width' ];
		$pm_options['pm_resize_height'] = $_POST[ 'pm_resize_height' ];
		$pm_options['pm_keep_orig_image'] = (!isset($_POST['pm_keep_orig_image'])) ? 'off' : 'on';
		$pm_options['pm_assign_cats_enabled'] = (!isset($_POST['pm_assign_cats_enabled'])) ? 'off' : 'on';
		$pm_options['pm_auto_publish_enabled'] = (!isset($_POST['pm_auto_publish_enabled'])) ? 'off' : 'on';
		$pm_options['pm_thumb_dir'] = $_POST['pm_thumb_dir'];
        // Put an options updated message on the screen
?>
<div class="updated"><p><strong>Options saved</strong></p></div>
<?php

    }
	// run default loader function
	pm_load_default_options($pm_options['pm_credit_flag'], $pm_options['pm_image_template'], 
		$pm_options['pm_image_post_template'], 
		$pm_options['pm_video_post_template'], $pm_options['pm_image_height'], 
		$pm_options['pm_image_width'], $pm_options['pm_attachment_dir'], 
		$pm_options['pm_rename_attachment'], $pm_options['pm_debug_enabled'],
		$pm_options['pm_resize_enabled'], $pm_options['pm_resize_width'],
		$pm_options['pm_resize_height'], $pm_options['pm_keep_orig_image'],
		$pm_options['pm_assign_cats_enabled'], $pm_options['pm_auto_publish_enabled'],
		$pm_options['pm_thumb_dir']);

	// save the options
	if( $_POST[ $hidden_field_name ] == 'Y' && !isset($_POST['pm_test_dir'])) {
		update_option( 'pm_image_template', $pm_options['pm_image_template']);
		update_option( 'pm_image_post_template', $pm_options['pm_image_post_template'] );
		update_option( 'pm_video_post_template', $pm_options['pm_video_post_template'] );
		update_option( 'pm_credit_flag', $pm_options['pm_credit_flag'] );
		update_option( 'pm_image_width', $pm_options['pm_image_width'] );
		update_option( 'pm_image_height', $pm_options['pm_image_height'] );
		update_option( 'pm_attachment_dir', $pm_options['pm_attachment_dir'] );
		update_option( 'pm_rename_attachment', $pm_options['pm_rename_attachment'] );
		update_option( 'pm_debug_enabled', $pm_options['pm_debug_enabled'] );
		update_option( 'pm_resize_enabled', $pm_options['pm_resize_enabled'] );
		update_option( 'pm_resize_width', $pm_options['pm_resize_width'] );
		update_option( 'pm_resize_height', $pm_options['pm_resize_height'] );
		update_option( 'pm_keep_orig_image', $pm_options['pm_keep_orig_image'] );
		update_option( 'pm_assign_cats_enabled', $pm_options['pm_assign_cats_enabled'] );
		update_option( 'pm_auto_publish_enabled', $pm_options['pm_auto_publish_enabled'] );
		update_option( 'pm_thumb_dir', $pm_options['pm_thumb_dir']);
	}
    // Now display the options editing screen
    echo '<div class="wrap">';
    // header
    echo '<h2>PostMaster</h2>';
	// is there an update?
	echo pm_get_current_version();
    // options form
?>

<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">



<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
<p><h3>Attachment Upload Directory</h3>
This is where PostMaster will save attachments. This directory *must exist* and have the proper permissions, PostMaster will not
create any directories. Also note that specifying a directory outside of the WordPress root directory may cause the embedded images
not to appear in the post.
<br/><br/>
If you check the 'use date-time stamps' box, attachments will be saved with file names like this:
<i><?php echo date('m-d-Y-his').'.jpg'; ?></i>
<br/>
Otherwise the orignial attachment name will be used (file name collisions will result in the clobbering of the old file).
<br/>
<br/>
<strong>Attachment Directory:</strong><br/>
Use date-time stamps for file names?
<input type="checkbox" name="<?php echo 'pm_rename_attachment'; ?>"
<?php if($pm_options['pm_rename_attachment'] == 'on') { echo 'checked'; } ?> />
<br/>
If you use a relative path your starting directory is: <i><?php echo dirname(__FILE__); ?></i><br/>
<?php
if(isset($test_attach_dir_result)) {
	if($test_attach_dir_result == TRUE) {
		echo '<font color="green">Success! PostMaster can write to the directory.</font><br/>';
	} else {
		echo '<font color="red">Failed! PostMaster cannot write to the directory.</font><br/>';
	}
}
?>
<input type="text" class="code" size="60" name="<?php echo 'pm_attachment_dir'; ?>" value="<?php echo $pm_options['pm_attachment_dir']; ?>" />
<input type="submit" name="pm_test_dir" value="Test Directory"/>
<hr/>

<p><h3>Image Resizing</h3>
Enabled: <input type="checkbox" name="<?php echo 'pm_resize_enabled'; ?>"
<?php if($pm_options['pm_resize_enabled'] == 'on') { echo 'checked';} ?> /><br/>
If this feature is enabled, PostMaster will resize attached images (not video) to the desired width and height.
<br/>
*Aspect ratio is preserved. Values of zero will be ignored (no resize will occur).
<br/>
<table>
<tr><td>
Width:</td>
<td><input class="code" type="text" name="<?php echo 'pm_resize_width'; ?>" 
value="<?php echo $pm_options['pm_resize_width']; ?>" size="4"/>
</td></tr>
<tr><td>Height:</td>
<td><input class="code" type="text" 
name="<?php echo 'pm_resize_height'; ?>" value="<?php echo $pm_options['pm_resize_height']; ?>" size="4"/>
</td></tr>
</table>
<br/>
Keep original image: <input type="checkbox" name="<?php echo 'pm_keep_orig_image'; ?>"
<?php if($pm_options['pm_keep_orig_image'] == 'on') { echo 'checked';} ?> /><br/>

If <em>enabled</em>, the original image will be saved and the resized image named like so: &lt;filename&gt;_resize.jpg<br/>
The resized image will be embedded in the post and will link to the original.
<br/><br/>
If <em>disabled</em>, the original image will be deleted. The resized image will be embedded in the post and will link to itself.
<br/><br/>
<em>Note: You must have GD2 installed and enabled on your host to use these features.</em>
</p>
<hr/>

<p><h3>Category Assignment</h3>
Enabled: <input type="checkbox" name="<?php echo 'pm_assign_cats_enabled'; ?>"
<?php if($pm_options['pm_assign_cats_enabled'] == 'on') { echo 'checked';} ?> /><br/>
<br/>
If enabled, this option allows you to assign categories to your post through the subject line of the email.<br/>
<code>
<em>Example</em><br/>
Subject: My post title %cats=News,Uploads,Photos
</code><br/><br/>
The categories to add the post to are defined by the comma-separated list after the %cats parameter.
<br/>Make sure you
<em>always</em> have a space before the %cats parameter.<br/>
The %cats parameter will not show up in the post title.
<br/><br/>
<em>Note: The category must exist in order for the post to be added to it. PostMaster will not create 
<br/>categories.</em>
</p>
<hr/>

<p><h3>Auto-Publish</h3>
Enabled: <input type="checkbox" name="<?php echo 'pm_auto_publish_enabled'; ?>"
<?php if($pm_options['pm_auto_publish_enabled'] == 'on') { echo 'checked';} ?> /><br/>
<br/>
If you are using WordPress 2.5, you <em>must</em> enable this option in order for your posts to be pushed to <br/>
a published state automatically. Otherwise your email-posts will be put in a 'pending' state until you manually 
publish it.<br/><br/>
If you're using WordPress 2.3.x this option won't have any effect.
</p>
<hr/>

<p><h3>Templates</h3>
The markup here will determine how your posts will be displayed when viewed. If you do not like the default
template, make changes here.<br/>
Image template variables<br/>
<ul>
<li>%img_url% - The URL of the image. All extracted attachments are stored in the user-defined upload directory</li>
<li>%img_url_resize% - The URL of the resized image. If resizing is not enabled: %img_url_resize% = %img_url%</li>
<li>%img_h% - The height constraint for the image when being displayed in the post.</li>
<li>%img_w% - The width constraint for the image when being displayed in the post.</li>
<li>%img_template% - This will be replaced by the entire image template. Reference it in the "Post with Image" template. This allows multiple images to be embedded. Keep in mind that multiple images will be embedded one after the other wherever the %img_template% token is.</li>
<li>%post_text% - The text of the post.</li>
</ul>
Video template variables<br/>
<ul>
<li>%video_url% - The URL of the video</li>
<li>%post_text% - The text of the post</li>
</ul>
<br />
<strong>Notes:</strong><br/>
<ul>
<li>You can restore the default templates by deleting them and saving. This will cause the defaults to be reloaded.</li>
<li>Be *very* careful when editing the video template. Linebreaks in the wrong place will wreak havoc on your page.</li>
</ul>
<br />
<strong>Image template:</strong><br/>
<textarea class="code" name="<?php echo 'pm_image_template'; ?>" rows="6" cols="100">
<?php echo $pm_options['pm_image_template']; ?>
</textarea>
<br />
<strong>Post with image template:</strong><br/>
<textarea class="code" name="<?php echo 'pm_image_post_template'; ?>" rows="6" cols="100">
<?php echo $pm_options['pm_image_post_template']; ?>
</textarea>
</p>

<p><strong>Post with video template:</strong><br/>
<textarea class="code" name="<?php echo 'pm_video_post_template'; ?>" rows="8" cols="100">
<?php echo $pm_options['pm_video_post_template']; ?>
</textarea>
</p>
<hr/>

<p><h3>Miscellaneous</h3>
Check the box below to add a credit link to posts that have been run through the PostMaster plugin.
<blockquote>
<font face="courier">
E.g.<br/>
&raquo; This post was processed by <a href="http://xforward.com">PostMaster</a>
</font>
</blockquote>
Show credit link: <input type="checkbox" name="<?php echo 'pm_credit_flag'; ?>" 
<?php if($pm_options['pm_credit_flag'] == 'on') {  echo 'checked'; } ?> />
<br/><br/>
This is completely optional, but it would be appreciated if you left it on :D<br/><br/>

<b>Debug raw content dump enabled:</b><input type="checkbox" name="<?php echo 'pm_debug_enabled'; ?>"
<?php if($pm_options['pm_debug_enabled'] == 'on') { echo 'checked';} ?> />
<br/>
This enables/disables debug output being dumped to the screen when messages are being processed by wp-mail.
Only enable this option if you are debugging the plugin.
</p>

<p class="submit" style="align: left;">
<input type="submit" name="Submit" value="Update Options &raquo;" />
</p>

</form>
</div>

<?php
 
}

function pm_test_attachment_dir($path_to_test) {
	$result = FALSE;
	$test_file_name = $path_to_test.'/pm_test_file';

	if($test_file_name[0] != '/') {
		$test_file_name = dirname(__FILE__).'/'.$test_file_name;
	}

	$testFile = fopen($test_file_name, 'x');
	if($testFile != FALSE) {
		fclose($testFile);
		unlink($test_file_name);
		$result = TRUE;
	}

	return $result;
}
?>
