<?php

/*
* This is a simple class that encapsulates the parsing code for the 
* PostMaster plugin. It is not a full-fledged email parser as it does not parse
* message headers, and it only extracts images and text at this time.
*/

class PMMailParser {

	public function __construct($msg_data) {
	}

	public function process($msg) 
	{

		$msg_parts = array();
		$images = array();
		$boundary_begin = stripos($msg, "boundary=\"");
		$boundary_name_skip = strlen("boundary=\"");
		if(!$boundary_begin) {
			// try without quotes
			$boundary_begin = stripos($msg, "boundary=");
			$boundary_name_skip = strlen("boundary=");
		}
		$boundary_begin += $boundary_name_skip;
		$msg_part = substr($msg, $boundary_begin);
		$boundary_end = strpos($msg_part, "\"");

		if(!$boundary_end)
			$boundary_end = $boundary_name_skip;

		$boundary = substr($msg_part, 0, $boundary_end);

		// look for \n in the boundary, if one exists then we went too far
		// goddamn iPhone!
		$newline_pos = strpos($boundary, "\n");
		if($newline_pos) 
		{
			$boundary = substr($boundary, 0, $newline_pos);
		}

		$msg_part = substr($msg_part, strlen($boundary) + 1);

		// now look for the next boundary
		$next_boundary = strstr($msg_part, $boundary);
		$next_pos = strpos($msg_part, $boundary);
		
		$image_count = 0;
		while($next_boundary) 
		{
			// remove any unwanted \r and \n
			$boundary = str_replace("\r", '', $boundary);
			$boundary = str_replace("\n", '', $boundary);

			// get the chunk
			$msg_ptr = substr($next_boundary, strlen($boundary));
			$end_pos = strpos($msg_ptr, $boundary);  
			$chunk = substr($msg_ptr, 0, $end_pos);

			// inspect the chunk for image data
			if(stristr($chunk, "Content-Type: image/")) {
				$img_tmp_ptr = stristr($chunk, "Content-Type: image/");
				$img_ptr = strstr($img_tmp_ptr, "\n\n");

				// if not found try \r\n
				if(!$img_ptr) {
					$img_ptr = strstr($img_tmp_ptr, "\r\n\r\n");
				}

				$img_ptr = substr($img_ptr, 2); // skips the \n\n
				
				$img_end_pos = strpos($img_ptr, "\n\n");
				if(!$img_end_pos) {
					$img_end_pos = strpos($img_ptr, "\r\n");
				}
				if(!$img_end_pos) {
					// possible there's no blank line at end of content
					$img_end_pos = strlen($img_ptr) - 2; // get rid of "--"
				}

				$img_data = substr($img_ptr, 0, $img_end_pos);

				// Don't need to save the image binary to file, just stuff it into 
				// the array.
				$images[$image_count]['data'] = base64_decode($img_data);
				$images[$image_count]['type'] = $this->get_image_mime_type_from_string($img_tmp_ptr);
				$image_count++;
				// done!

				$next_boundary = strstr($msg_ptr, $boundary);

			} else if(stristr($chunk, "Content-Type: text/plain")) {
				// this is where we find the text/html and text/plain parts
				// not supporting html text, causes to many problems when embedded into 
				// a post. It can break the whole page
				$part_key_name = "text/plain";

				// a little kludgy
				if(stristr($chunk, "boundary=\""))
				{
					$multipart_boundary = stristr($chunk, "boundary=\"");
					$multipart_boundary = substr($multipart_boundary, strlen("boundary=\""));
					$multipart_boundary = substr($multipart_boundary, 0, strpos($multipart_boundary, "\""));
				}
				else
				{
					// no quotes
					$multipart_boundary = stristr($chunk, "boundary=");
					$multipart_boundary = substr($multipart_boundary, strlen("boundary="));
					$multipart_boundary = substr($multipart_boundary, 0, strpos($multipart_boundary, "\n"));
				}

				$text_tmp_ptr = stristr($chunk, "Content-Type: $part_key_name");
				$text_ptr = strstr($text_tmp_ptr, "\n\n");
				// if not found try \r\n
				if(!$text_ptr) {
					$text_ptr = strstr($text_tmp_ptr, "\r\n\r\n");
				}
				$text_ptr = substr($text_ptr, 2); // skips the \n\n
				

				$text_end_pos = strpos($text_ptr, $boundary);
				if(!$text_end_pos)
				{
					$text_end_pos = strpos($text_ptr, $multipart_boundary);
				}

				// holding on to this cuz this might work for mail other than exchange
				//$text_data = substr($text_ptr, 0, $text_end_pos - strlen($boundary));
				$text_data = substr($text_ptr, 0, $text_end_pos - 2);

				$msg_parts[$part_key_name] = $text_data;

				$msg_ptr = substr($msg_ptr, $end_pos);

				$next_boundary = strstr($msg_ptr, $boundary);
				
				if(!$next_boundary && $multipart_boundary)
				{
					$next_boundary =  strstr($msg_ptr, $multipart_boundary);
					$boundary = $multipart_boundary;
				}

			} else if(stristr($chunk, "Content-Type: application/octet-stream")) {

				$vid_tmp_ptr = stristr($chunk, "Content-Type: application/octet-stream");
				$vid_ptr = strstr($vid_tmp_ptr, "\n\n");
				// if not found try \r\n
				if(!$vid_ptr) {
					$vid_ptr = strstr($vid_tmp_ptr, "\r\n\r\n");
				}
				$vid_ptr = substr($vid_ptr, 2); // skips the \n\n
				$vid_end_pos = strpos($vid_ptr, "\n\n");
				if(!$vid_end_pos) {
					$vid_end_pos = strpos($vid_ptr, "\r\n");
				}
				if(!$vid_end_pos) {
					// possible there's no blank line at end of content
					$vid_end_pos = strlen($vid_ptr) - 2; // get rid of "--"
				}

				$vid_data = substr($vid_ptr, 0, $vid_end_pos);

				$msg_parts['application/octet-stream'] = $vid_data;

				$msg_ptr = substr($msg_ptr, $end_pos);
				$next_boundary = strstr($msg_ptr, $boundary);

			} else {
				// keep looking
				$msg_ptr = substr($msg_ptr, $end_pos);
				$next_boundary = strstr($msg_ptr, $boundary);
			}
		}
		$msg_parts['images'] = $images;
		return $msg_parts;
	}
	// END process method //

	function get_image_mime_type_from_string($buffer) {
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
}
?>