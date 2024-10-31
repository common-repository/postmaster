<?php
/*
Plugin Name: PostMaster
Plugin URI: http://blog.xforward.com/?page_id=65
Description: The PostMaster plugin allows you to make posts-by-email with multimedia attachments. It processes all incomming email-posts,extracts the attachments, and embeds them into the post body. It currently supports image and mobile video (3g2) MIME types,with more to come in the future (hopefully). Please note that version 2.1.0 only supports a single attachment.
Version: 2.1.0
Author: Brett Duncavage
Author URI: http://xforward.com
*/

/*  
Copyright 2008  Brett Duncavage (email : brett@xforward.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// important that this plugin is in standard install directory, i.e. wp-content/plugins/pluginname
require(dirname(__FILE__).'/../../../wp-config.php');
// add the admin options page stuff
require_once('pm_admin_menu.php');
// only import the graphics functions if needed
if(get_option('pm_resize_enabled') == 'on') {
	require_once('pm_graphics.php');
}

// PMMailParser class
require_once('PMMailParser.php');

add_filter('phone_content', 'pm_process_post');
add_filter('category_save_pre', 'pm_process_category');
add_filter('title_save_pre', 'pm_clean_post_title');
add_action('publish_phone', 'pm_auto_publish');

function pm_auto_publish($postID) {
	// for WordPress 2.5
	// email posts not automatically published
	if(get_option('pm_auto_publish_enabled') == 'on') {
		wp_publish_post($postID);
	}
}

function pm_process_category($cats) {
	if(get_option('pm_assign_cats_enabled') == 'on') {
		$catsList = get_option('pm_temp_cats_list');
		if(isset($catsList) && $catsList != '') {
			$catsArray = explode(',', $catsList);
			$idx = 0;
			foreach($catsArray as $catName) {
				$catId = get_cat_ID($catName);
				if($catId != FALSE) {
					$catIdsToAdd[$idx] = $catId;
					$idx++;
				}
			}
		} else {
			$catIdsToAdd = $cats;
		}
		update_option('pm_temp_cats_list', '');
	} else {
		$catIdsToAdd = $cats;
	}
	return $catIdsToAdd;
}

function pm_clean_post_title($title) {
	$title = trim($title);
	if(get_option('pm_assign_cats_enabled') == 'on') {
		$title_words = explode(' ', $title);
		foreach($title_words as $word) {
			if(strstr($word, '%cats=')) {
				$cats = $word;
			} else {
				$new_title = $new_title.' '.$word;
			}
		}
		if(isset($cats)) {
			$first_split = explode('=', $cats);
			update_option('pm_temp_cats_list', $first_split[1]);
		}
	} else {
		$new_title = $title;
	}

	return $new_title;
}

function pm_doResolveImageFormat($buffer) {
	$result = "jpg"; // the default will be jpg because that's pretty common
	// just try to detect the mime type, crude? yes
	if(strstr($buffer, "image/jpeg") || strstr($buffer, "image/jpg")) {
		$result = "jpg";
	} else if(strstr($buffer, "image/png")) {
		$result = "png";
	} else if(strstr($buffer, "image/gif")) {
		$result = "gif";
	} else if(strstr($buffer, "image/bmp")) {
		$result = "bmp";
	} else if(strstr($buffer, "image/tiff")) {
		$result = "tiff";
	}
	return $result;
}

// This function fixes file paths for linux and windows
function pm_fix_path($filename)
{
	// determine if windows or linux path
	if($filename[1] == ':')
	{
		// windows path, don't do anything

		return $filename;
	}
	else if($filename[0] != '/') 
	{
		return dirname(__FILE__).'/'.$filename;
	}
	else
	{
		return $filename;
	}
}

/*
* This function will convert a UTF-8 encoded string to unicode
* Credit to: Scott Reynen
*/
function utf8_to_unicode( $str ) {
        
	$unicode = array();        
	$values = array();
	$lookingFor = 1;
	
	for ($i = 0; $i < strlen( $str ); $i++ ) {

		$thisValue = ord( $str[ $i ] );
		
		if ( $thisValue < 128 ) $unicode[] = $thisValue;
		else {
		
			if ( count( $values ) == 0 ) $lookingFor = ( $thisValue < 224 ) ? 2 : 3;
			
			$values[] = $thisValue;
			
			if ( count( $values ) == $lookingFor ) {
		
				$number = ( $lookingFor == 3 ) ?
					( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ):
					( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );
					
				$unicode[] = $number;
				$values = array();
				$lookingFor = 1;
		
			} // if
		
		} // if
		
	} // for

	return $unicode;

} // utf8_to_unicode

/*
* This function changes a unicode string into ascii values with 
* non ascii values preserved as html entities
* Credit: Scott Reynen
*/
function unicode_to_entities_preserving_ascii( $unicode ) {
    
	$entities = '';
	foreach( $unicode as $value ) {
	
		$entities .= ( $value > 127 ) ? '&#' . $value . ';' : chr( $value );
		
	} //foreach
	return $entities;
	
}

function pm_process_post($raw_content) {

	$exploded_content = explode("\r\n", $raw_content);
	$line_counter = 0;


	if(get_option('pm_debug_enabled') == 'on') {
		echo $raw_content;
	}

	// PostMaster credit sig
	$pm_credit_sig = '<p>&raquo;This post was processed by <a href="http://xforward.com">PostMaster</a></p>';

	// test for multi-part, basically if Content-Type is missing from content, then we've
	// got a vanilla post
	if(stristr($raw_content, 'Content-Type:') == FALSE) {
		// need to clean the content of tags
		return strip_tags($raw_content);
	}

	/* Notes on 2.1.0 rewrite
	*
	* Create new class for parsing the raw data.
	* This class will return an associative array containing all the parts of the email.
	* The parts will be accessed by keys that correspond to the mime type of the part,
	* e.g. $parts['text/html'] will contiain the text part and $parts['image/jpeg'] will contain
	* a jpeg image. Still only doing single attachments for now.
	*
	* Implementation notes:
	* Looks like if I can get rid of the foreach and add an instance of the email parser.
	* Then use what's in the foreach as a guide to handling the parsed message. Looking at
	* plugin options, naming the files correctly, etc.
	*/

	$mp = new PMMailParser();
	$msg_parts = $mp->process($raw_content);

	// use html by default if it is present
	if(isset($msg_parts['text/html'])) {
		$text_content = $msg_parts['text/html'];
	} else {
		$text_content = $msg_parts['text/plain'];
	}
	
	// 2.1.0 Trying to handle extended ascii/UTF-8 characters in a manageable way.
	// Giving this a try.
	$text_content = quoted_printable_decode($text_content);
	$text_content = strip_tags($text_content);
	$text_content = htmlentities($text_content, ENT_COMPAT, ISO-8859-1, false);	

	// do the image/video handling stuff
	if(count($msg_parts['images']) > 0) 
	{
		foreach($msg_parts['images'] as $image)
		{
			if(get_option('pm_rename_attachment') == 'on') {
				// genterate a new filename with timestamp
				// not using milliseconds in order to keep compatibility with PHP4
				$date_time_str = date('m-d-Y-his');
				$file_ext = pm_doResolveImageFormat($image['type']);
				$file_name = $date_time_str.".".$file_ext;

				$save_name = get_option('pm_attachment_dir').'/'.$file_name;

				// Fix the path
				$save_name = pm_fix_path($save_name);

				// 2.1.0 This logic was all fucked up. This new code ensures that 
				// filename collisions are handled correctly
				for($fname_idx = 0; file_exists($save_name); $fname_idx++)
				{
					$file_name = sprintf('%s_%d.%s', $date_time_str, $fname_idx, $file_ext);
					$save_name = pm_fix_path(get_option('pm_attachment_dir').'/'.$file_name);
				}
			} else {
				$save_name = tempnam(get_option('pm_attachment_dir'), "pm_");
			}
			$result = TRUE;

			// not using file_put_contents for backwards compatibility with PHP4
			$fp = fopen($save_name, 'x');
			if($fp != FALSE) 
			{

				$result = fwrite($fp, $image['data']);
				fclose($fp);

				// assess whether we should resize or not
				$should_resize = false;
				$resize_enabled = (get_option('pm_resize_enabled') == 'on') ? true : false;
				$image_size = getimagesize($save_name);
				$new_width = get_option('pm_resize_width');
				$new_height = get_option('pm_resize_height');

				if( (($image_size[0] > $new_width && $new_width > 0)
					|| ($image_size[1] > $new_height && $new_height > 0)) && $resize_enabled)
				{
					$should_resize = true;
				}
				

				if($should_resize) 
				{
					$new_width = get_option('pm_resize_width');
					$new_height = get_option('pm_resize_height');
					$keep_orig_image = get_option('pm_keep_orig_image');
					// pulling this out, not really worth the headache.
					//$thumb_dir = get_option('pm_thumb_dir');
					
					if($keep_orig_image == 'off') {
						$resize_name = $save_name;
					} else {
						$save_name_parts = explode('.', $save_name);
						$the_save_path = "";
						// FIX for blog.wetty.de
						// there could've been dots in some of the directory names so let's make sure that
						// we only grab the extension then smash everything else back together
						if(count($save_name_parts) > 2) {
							$nParts = count($save_name_parts);
							$the_extension = $save_name_parts[$nParts - 1];
							// now put everything before the extension back together
							for($i = 0; $i < $nParts - 1; $i++) {
								if(strlen($the_save_path) > 0) {
									$the_save_path = $the_save_path.'.'.$save_name_parts[$i];
								} else {
									$the_save_path = $save_name_parts[$i];
								}
							}
						} else {
							$the_extension = $save_name_parts[1];
							$the_save_path = $save_name_parts[0];
						}

						$file_name_parts = explode('.', $file_name);
						$resize_name = $the_save_path.'_resize.'.$the_extension;
						// reset file_name so the correct image is referenced in the post
						$file_name_resize = $file_name_parts[0].'_resize.'.$file_name_parts[1];
					}
					
					/*
					* Not really needed, but I'll keep it in here.
					echo 'Resizing image to '.$new_width.'x'.$new_height.'<br/>';
					echo 'Saving resized image as: '.$resize_name.'<br/>';
					echo "Image ext: $file_ext<br />";
					*/

					$rv = pm_resizeimage($file_ext, $new_width, $new_height, $save_name, $resize_name);
					if( ! $rv)
					{
						// something went wrong file was not saved
						echo "<strong>Resizing failed!</strong><br />";
					}
				}
			}
			// cleanup the path
			$pm_attachment_dir = get_option('pm_attachment_dir');
			$base_path = ABSPATH;
			$base_path_pos = strstr($pm_attachment_dir, $base_path);
			if($base_path_pos != FALSE) {
				$attachment_dir = substr($pm_attachment_dir, strlen($base_path));
			} else {
				// relative path stick plugin dir before it
				$plugin_dir = substr(dirname(__FILE__), strlen($base_path));
				$attachment_dir = $plugin_dir.'/'.$pm_attachment_dir;
				//$thumb_dir = $plugin_dir.'/'.get_option('pm_thumb_dir');
			}
			if($file_name_resize == FALSE) {
				$media_url = get_option('siteurl').'/'.$attachment_dir.'/'.$file_name;
				$media_url_resize = $media_url;
			} else {
				$media_url_resize = get_option('siteurl').'/'.$attachment_dir.'/'.$file_name_resize;
				$media_url = get_option('siteurl').'/'.$attachment_dir.'/'.$file_name;
			}

			/*
			* For debugging, may remove later
			echo 'Save name: '.$save_name.'<br/>';
			if($resize_name) {
				echo 'Resized name: '.$resize_name.'<br/>';
			}
			echo 'Attachment url: '.$media_url.'<br/>';
			*/

			$image_template = get_option('pm_image_template');
			$post_template = get_option('pm_image_post_template');
			$img_w = 0;
			$img_h = 0;

			if($resize_name == FALSE) {
				$img_props = getimagesize($save_name);
			} else {
				$img_props = getimagesize($resize_name);
			}

			if($img_w == 0) {
				$img_w = $img_props[0];
			}
			
			if($img_h == 0) {
				$img_h = $img_props[1];
			}
			
			// 2.1.0 make sure we don't have any backslashes in the URL (windows)
			$media_url_resize = str_replace('\\', '/', $media_url_resize);
			$media_url = str_replace('\\', '/', $media_url);

			// put the values in the post
			$image_template = str_replace('%img_url%', $media_url, $image_template);
			$image_template = str_replace('%img_url_resize%', $media_url_resize, $image_template);
			$image_template = str_replace('%img_w%', $img_w, $image_template);
			$image_template = str_replace('%img_h%', $img_h, $image_template);

			// stick the image templates together
			$image_templates = $image_templates.$image_template;

		}// end foreach(images)
		
		$final_post = str_replace('%img_template%', $image_templates, $post_template);
		$final_post = str_replace('%post_text%', $text_content, $final_post);

		if(isset($msg_parts['video'])) {
			$post_template = get_option('pm_video_post_template');
			$final_post = str_replace('%video_url%', $media_url, $post_template);
			$final_post = str_replace('%post_text%', $text_content, $final_post);
		}
		
		if(get_option('pm_credit_flag') == 'on') {
			$final_post = $final_post.$pm_credit_sig;
		}
		
	}

	if(!isset($final_post)) {
		// no image to post
		$final_post = $text_content;
	}
	echo "PostMaster message processing complete.<br />";

	return $final_post;
}

function pm_clean_text_content($text_content, $fix_to_try, $exploded_content) {
	$new_text_content = $text_content;
	// try helio-gmail fix
	if($fix_to_try == 'helio' && strstr($text_content, 'Content-Type:')) {
		$strip_from = strpos($text_content, '------=');
		$new_text_content = substr($text_content, 0, $strip_from);
	} else if($fix_to_try == 'att_centro' && strstr($text_content, 'Content-Type:')) {
		// need to regrab the blank lines, ingoring the first set
		$text_content = "";

		$vzw_counter = 0;
		$first_line = -2;
		$second_line = -1;
		foreach($exploded_content as $vzw_line) {
			if(strlen($vzw_line) == 0 && $first_line == -2) {
				$first_line = -1;
			} 
			else if(strlen($vzw_line) == 0 && $first_line == -1) {
				$first_line = $vzw_counter;
			}
			else if(strlen($vzw_line) == 0 && ($first_line > -1 && $second_line == -1)) {
				$second_line = $vzw_counter;
			}
			$vzw_counter++;
		}

		// now grab all the lines in between
		for($i = $first_line; $i < $second_line; $i++) {
			
			if($i + 1 == $second_line) {
				$text_content = $text_content.$exploded_content[$i].'<br/>';
			} else {
				$text_content = $text_content.$exploded_content[$i];
			}
		}
		$new_text_content = $text_content;
	}

	return $new_text_content;
}

?>
