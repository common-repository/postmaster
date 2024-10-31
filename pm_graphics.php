<?php
/* Resizes the source image to the desired size.
Maintains the aspect ratio (i.e. does not distort the image).
Supports the following image formats:

GIF, JPEG, PNG, BMP

*/

// if you want to maintain the original aspect ratio only define a new width or new height
// and set the other to 0 or less
function pm_resizeimage($img_format, $new_width, $new_height, $srcfile, $destfile) {
	$src_img = open_image($img_format, $srcfile);
	if($src_img === false) {
		return false;
	}

	$og_w = imagesx($src_img);
	$og_h = imagesy($src_img);

	if($new_width >= $og_w && ($new_height >= $og_h || $new_height <= 0)) {
		//save_image($src_img, $dest, $mime_type);
		copy($src, $dest);
		return true;
	}

	if($new_width && $new_height <= 0) {
		$new_height = $og_h * ($new_width/$og_w);
	}
	if($new_height && $new_width <= 0) {
		$new_width = $og_w * ($new_height/$og_h);
	}

	$canvas = imagecreatetruecolor($new_width, $new_height);

	$rv = imagecopyresampled( $canvas, $src_img, 0, 0, 0, 0, $new_width, $new_height, $og_w, $og_h );

	if($rv == true) {
		$rv = save_image($canvas, $destfile, $img_format);
	}

	return $rv;
}

function open_image ($img_format, $src) {
	error_reporting(E_ERROR);

	if($img_format == 'gif') {
		$image = imagecreatefromgif($src);
	}
	elseif($img_format == 'jpeg' || $img_format == 'pjpeg') {
		$image = imagecreatefromjpeg($src);
	}
	elseif($img_format == 'jpg') {
		$image = imagecreatefromjpeg($src);
	}
	elseif($img_format == 'png') {
		$image = imagecreatefrompng($src);
	}

	return $image;

}

function save_image($image, $save_name, $img_format) {
	$rv = true;

	if($img_format == 'png') {
		$rv = imagepng($image, $save_name);
	}
	else if($img_format == 'jpeg' || $img_format == 'pjpeg') {
		$rv = imagejpeg($image, $save_name, 95);
	}
	else if($img_format == 'jpg') {
		$rv = imagejpeg($image, $save_name, 95);
	}
	else if($img_format == 'gif') {
		$rv = imagegif($image, $save_name);
	}
	
	return $rv;
}

function pm_resizeimage_old($format, $new_width, $new_height, $srcfile, $destfile ) {
    $err = TRUE;

	$img_size = getimagesize( $srcfile );
    if( $img_size[0] >= $img_size[1] ) {
        $orientation = 0;
    }
    else {
        $orientation = 1;
        $new_width = $new_height;
        $new_height = $new_width;
    }
    if ( $img_size[0] > $new_width || $img_size[1] > $new_height ) {
        if( ( $img_size[0] - $new_width ) >= ( $img_size[1] - $new_height ) ) {
            $iw = $new_width;
            $ih = ( $new_width / $img_size[0] ) * $img_size[1];
        }
        else {
            $ih = $new_height;
            $iw = ( $ih / $img_size[1] ) * $img_size[0];
        }
        $do_resize = TRUE;
    }

    if ( $do_resize ) {
		// use the correct method for the format type
		if($format == 'jpg' || $format == 'jpeg') {
			$img_src = imagecreatefromjpeg( $srcfile );
		} else if($format == 'png') {
			$img_src = imagecreatefrompng( $srcfile );
		} else if($format == 'bmp') {
			$img_src = imagecreatefromwbmp( $srcfile );
		} else if($format == 'gif') {
			$img_src = imagecreatefromgif( $srcfile );
		}
        $img_dst = imagecreatetruecolor( $iw, $ih );
        imagecopyresampled( $img_dst, $img_src, 0, 0, 0, 0, $iw, $ih, $img_size[0], $img_size[1] );
        
		
		if( ($format == 'jpg' || $format == 'jpeg') && !imagejpeg( $img_dst, $destfile, 90 ) ) {
            $err = FALSE;
        } else if($format == 'png' && !imagepng($img_dst, $destfile, 9)) {
			$err = FALSE;
		} else if($format == 'gif' && !imagegif($img_dst, $destfile)) {
			$err = FALSE;
		} else if($format == 'bmp' && !imagewbmp($img_dst, $destfile)) {
			$err = FALSE;
		}
    }

	return $err;

}
?>