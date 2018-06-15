<?php
/**
 * short description
 *
 * description
 *
 * @package    NETopes\Core
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2017 Hinter Universal SRL
 * @license    LICENSE.md
 * @version    2.1.0.0
 * @filesource
 */
	function get_image_height($image) {
		$size = getimagesize($image);
		$height = $size[1];
		return $height;
	}//function get_image_height

	function get_image_width($image) {
		$size = getimagesize($image);
		$width = $size[0];
		return $width;
	}//END function get_image_width

	function crop_resize_image($target_img,$target_width,$target_height,$source_img,$start_x,$start_y,$crop_width,$crop_height,$istransparent = FALSE) {
		list($imagewidth, $imageheight, $imageType) = getimagesize($source_img);
		$newImageWidth = (!is_numeric($target_width) || $target_width<=0) ? $imagewidth : $target_width;
		$newImageHeight = (!is_numeric($target_height) || $target_height<=0) ? $imageheight : $target_height;
		if($imagewidth<$newImageWidth) {
			$newImageHeight = ceil($imagewidth * $newImageHeight / $newImageWidth);
			$newImageWidth = $imagewidth;
		} elseif($imageheight<$newImageHeight) {
			$newImageWidth = ceil($imageheight * $newImageWidth / $newImageHeight);
			$newImageHeight = $imageheight;
		}//if($imagewidth<$newImageWidth)
	    $imageType = image_type_to_mime_type($imageType);
		switch($imageType) {
			case "image/gif":
				$source = imagecreatefromgif($source_img);
				break;
		    case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$source = imagecreatefromjpeg($source_img);
				break;
		    case "image/png":
			case "image/x-png":
				$source = imagecreatefrompng($source_img);
				break;
	  	} //switch($imageType)
	  	$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
	  	if($istransparent || $imageType=="image/png" || $imageType=="image/x-png") {
			imagealphablending($newImage,FALSE);
			imagesavealpha($newImage,TRUE);
			$transparent = imagecolorallocatealpha($newImage,255,255,255,127);
			imagefilledrectangle($newImage,0,0,$newImageWidth,$newImageHeight,$transparent);
		}//if($istransparent || $imageType=="image/png" || $imageType=="image/x-png")

		$t_x = 0;
		$t_y = 0;
		if($start_x<0) {
			$t_x = $start_x * (-1);
			$crop_width -= $t_x;
			$newImageWidth -= $t_x;
			$start_x = 0;
		}//if($start_x<0)
		if($start_y<0) {
			$t_y = $start_y * (-1);
			$crop_height -= $t_y;
			$newImageHeight -= $t_y;
			$start_y = 0;
		}//if($start_y<0)
		if($imagewidth<$crop_width) {
			$newImageWidth -= ($crop_width-$imagewidth);
			$crop_width = $imagewidth;
		}//if($imagewidth<$crop_width)
		if($imageheight<$crop_height) {
			$newImageHeight -= ($crop_height-$imageheight);
			$crop_height = $imageheight;
		}//if($imageheight<$crop_height)

		imagecopyresampled($newImage,$source,$t_x,$t_y,$start_x,$start_y,$newImageWidth,$newImageHeight,$crop_width,$crop_height);

		if($istransparent && $imageType!="image/png" && $imageType!="image/x-png") {
			$imageType = 'image/png';
			$image_name = $target_img.'.png';
		}//if($istransparent && $imageType!="image/png" && $imageType!="image/x-png")
		switch($imageType) {
			case "image/gif":
		  		imagegif($newImage,$target_img);
				break;
	      	case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
		  		imagejpeg($newImage,$target_img,100,9);
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage,$target_img,9);
				break;
	    } //switch($imageType)
		chmod($target_img,0655);
		return $target_img;
	}//END function crop_resize_image

	function resize_image($image_name,$image,$width,$height,$scale) {
		list($imagewidth, $imageheight, $imageType) = getimagesize($image);
		$imageType = image_type_to_mime_type($imageType);
		$newImageWidth = ceil($width * $scale);
		$newImageHeight = ceil($height * $scale);
		switch($imageType) {
			case "image/gif":
				$source = imagecreatefromgif($image);
				break;
		    case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
				$source = imagecreatefromjpeg($image);
				break;
		    case "image/png":
			case "image/x-png":
				$source = imagecreatefrompng($image);
				break;
	  	} //switch($imageType)
	  	$newImage = imagecreatetruecolor($newImageWidth,$newImageHeight);
		if($imageType=="image/png" || $imageType=="image/x-png") {
			imagealphablending($newImage,FALSE);
			imagesavealpha($newImage,TRUE);
			$transparent = imagecolorallocatealpha($newImage,255,255,255,127);
			imagefilledrectangle($newImage,0,0,$newImageWidth,$newImageHeight,$transparent);
		}//if($imageType=="image/png" || $imageType=="image/x-png")
		imagecopyresampled($newImage,$source,0,0,0,0,$newImageWidth,$newImageHeight,$width,$height);
		switch($imageType) {
			case "image/gif":
		  		imagegif($newImage,$image_name);
				break;
	      	case "image/pjpeg":
			case "image/jpeg":
			case "image/jpg":
		  		imagejpeg($newImage,$image_name,100);
				break;
			case "image/png":
			case "image/x-png":
				imagepng($newImage,$image_name);
				break;
	    } //switch($imageType)
		chmod($image_name,0655);
		return $image_name;
	}//END function resize_image
?>