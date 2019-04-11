<?php

/**
*	ImageHandler - ResizeToDimension()
*
* 	Resizes an image to fit into a specifie dimension
*
* 	EXAMPLE USAGE:
*
* 	$ImageHandler->ResizeToDimension(200, "file.jpg", "png", "images");
*
*	@param	int			$dimension - dimension to fit into
*	@param	string		$source - image source
*	@param	string		$extension - image source file type
*	@param	string		$destination - destination directory
*
*/

function ResizeToDimension($dimension, $source, $extension, $destination)
{
	
	//determine what the file extension of the source
	//image is
	switch($extension)
	{

		//its a gif
		case 'gif': case 'GIF':
			//create a gif from the source
			$working_image = imagecreatefromgif($source);
			break;
		case 'jpg': case 'JPG': case 'jpeg': case 'JPEG':
			//create a jpg from the source
			$working_image = imagecreatefromjpeg($source);
			break;
		case 'png': case 'PNG':
			//create a png from the source
			$working_image = imagecreatefrompng($source);
			break;

	}
	

	//exif only supports jpg in our supported file types
    if ($extension == "jpg" || $extension == "jpeg")
	{

		//fix photos taken on cameras that have incorrect
		//dimensions
		
		$exif = @exif_read_data($source);
		
		
		//get the orientation
		$ort = "";
		if (isset($exif["Orientation"])) $ort = $exif['Orientation'];
		
		//determine what oreientation the image was taken at
		switch($ort)
	    	{
			case 2: // horizontal flip
			    $this->ImageFlip2($working_image);
				break;

			case 3: // 180 rotate left
			    $working_image = imagerotate($working_image, 180, -1);
				break;

			case 4: // vertical flip
			    $this->ImageFlip2($working_image);
		       		break;

			case 5: // vertical flip + 90 rotate right
			    $this->ImageFlip2($working_image);
			    $working_image = imagerotate($working_image, -90, -1);
				break;

			case 6: // 90 rotate right
			    $working_image = imagerotate($working_image, -90, -1);
				break;

			case 7: // horizontal flip + 90 rotate right
			    $this->ImageFlip2($working_image);
			    $working_image = imagerotate($working_image, -90, -1);
				break;

			case 8: // 90 rotate left
			    $working_image = imagerotate($working_image, 90, -1);
				break;

	    	}

	}
	
	// save the working image (so we can measure it's size)
	if (file_exists($destination)) unlink($destination);
	imagejpeg($working_image, $destination, 100);
	
	//get the image size
	$size = getimagesize($destination);

	//determine dimensions
	$width = $size[0];
	$height = $size[1];
	
	if ($width>500){
		
		$newWidth = $dimension;
		$newHeight = ($height/$width) * $newWidth;
	
		// create a new image
		$resized_image = imagecreatetruecolor($newWidth, $newHeight);

		// copy resampled
		imagecopyresampled($resized_image, $working_image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

		// create the jpeg
		imagejpeg($resized_image, $destination, 90);
	}

}


/**
*	ImageHandler - ImageFlip()
*
* 	Resizes an image to set width and height
*
* 	EXAMPLE USAGE:
*
* 	$ImageHandler->Resize(200, "file.jpg", "png", "images");
*
*	@param	string		$image (image to flip)
*	@param	int			$x
*	@param	int			$y
*	@param	int			$width
*	@param	int			$height
*
*/

function ImageFlip2(&$image, $x = 0, $y = 0, $width = null, $height = null)
{

    if ($width  < 1) $width  = imagesx($image);
    if ($height < 1) $height = imagesy($image);

    // Truecolor provides better results, if possible.
    if (function_exists('imageistruecolor') && imageistruecolor($image))
    {

        $tmp = imagecreatetruecolor(1, $height);

    }
    else
    {

        $tmp = imagecreate(1, $height);

    }

    $x2 = $x + $width - 1;

    for ($i = (int)floor(($width - 1) / 2); $i >= 0; $i--)
    {

        // Backup right stripe.
        imagecopy($tmp, $image, 0, 0, $x2 - $i, $y, 1, $height);

        // Copy left stripe to the right.
        imagecopy($image, $image, $x2 - $i, $y, $x + $i, $y, 1, $height);

        // Copy backuped right stripe to the left.
        imagecopy($image, $tmp, $x + $i,  $y, 0, 0, 1, $height);

    }

    imagedestroy($tmp);

    return true;

}



?>
