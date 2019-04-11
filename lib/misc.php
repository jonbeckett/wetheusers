<?php

function rand_string( $length ) {
	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";

	$size = strlen( $chars );
	$str = "";
	for( $i = 0; $i < $length; $i++ ) {
		$str .= $chars[ rand( 0, $size - 1 ) ];
	}

	return $str;
}

function resolve_user_status($status){

	switch($status) {
		case USER_STATUS_UNVALIDATED:
			return "Unvalidated";
			break;
		case USER_STATUS_VALIDATED:
			return "Validated";
			break;
		case USER_STATUS_DEACTIVATED:
			return "Deactivated";
			break;
		case USER_STATUS_BANNED:
			return "Banned";
			break;
	}
}


function time_ago($date)
{
	if(empty($date)) {
		return "No date provided";
	}

	$periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
	$lengths = array("60","60","24","7","4.35","12","10");
	$now = time();
	$unix_date = strtotime($date);

	// check validity of date
	if(empty($unix_date)) {
		return "Bad date";
	}

	if($now > $unix_date) {
		$difference = $now - $unix_date;
		$tense = "ago";
	} else {
		$difference = $unix_date - $now;
		$tense = "from now";
	}
	for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
		$difference /= $lengths[$j];
	}
	$difference = round($difference);
	if($difference != 1) {
		$periods[$j].= "s";
	}
	return "$difference $periods[$j] {$tense}";
}

function toAscii($str, $replace=array(), $delimiter='-') {
	if( !empty($replace) ) {
		$str = str_replace((array)$replace, ' ', $str);
	}

	$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
	$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
	$clean = strtolower(trim($clean, '-'));
	$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

	return $clean;
}

function SendSystemMessage($mysqli,$to_id,$title,$body,$type=1){

	if (isset($_SESSION["user_id"])){
	
		$sql = "DELETE FROM Messages WHERE FromUserId=1 AND Created < CURRENT_TIMESTAMP - INTERVAL '7' DAY";
		$mysqli->query($sql);

		// add the new message
		$sql = "INSERT INTO Messages ("
			."ParentId,FromUserId,ToUserId,Type,Title,Body,Created,IPCreated"
			.") VALUES ("
			."0"
			.",".$_SESSION["user_id"]
			.",".$mysqli->real_escape_string($to_id)
			.",".$type
			.",'".$mysqli->real_escape_string($title)."'"
			.",'".$mysqli->real_escape_string($body)."'"
			.",NOW()"
			.",'".$mysqli->real_escape_string($_SERVER["SERVER_ADDR"])."'"
			.")";

		$mysqli->query($sql);

	}

}

function upload_photo($post_id,$mysqli){
	if ($_FILES["photo"]["size"] > 0){

		$allowedExts = array("jpg", "jpeg", "gif", "png");
		$uploaded_filename = strtolower($_FILES["photo"]["name"]);
		$uploaded_filename_parts = explode(".", $uploaded_filename);
		$extension = end($uploaded_filename_parts);
		if (($_FILES["photo"]["size"] < (4096 * 1024)) && in_array($extension, $allowedExts))
		{

			// do we need to make a folder for the user ?
			if (!file_exists(realpath("photos")."/".$_SESSION["user_id"])){
				mkdir(realpath("photos")."/".$_SESSION["user_id"],0777);
			}

			// look for the file pre-existing
			$destination_filename = realpath("photos")."/".$_SESSION["user_id"]."/".$post_id.".".$extension;

			if (file_exists($destination_filename)) unlink($destination_filename);

			move_uploaded_file($_FILES["photo"]["tmp_name"],$destination_filename);

			// make a 500 pixel version
			include("api/resize_to_dimension.php");
			ResizeToDimension(500, $destination_filename, $extension, realpath("photos")."/".$_SESSION["user_id"]."/".$post_id."_500.".$extension);
			
			// remove the original (large) file
			unlink($destination_filename);

			// find out the resulting size of the uploaded photo
			$size = getimagesize("photos/".$_SESSION["user_id"]."/".$post_id."_500.".$extension);
			$width = $size[0];
			$height = $size[1];
			
			// update the Post record with the path of the image
			$mysqli->query("UPDATE Posts SET Photo='photos/".$mysqli->real_escape_string($_SESSION["user_id"])."/".$mysqli->real_escape_string($post_id)."_500.".$mysqli->real_escape_string($extension)."', PhotoWidth='".$mysqli->real_escape_string($width)."',PhotoHeight='".$mysqli->real_escape_string($height)."' WHERE Id=".$mysqli->real_escape_string($post_id));

		}
	}
}
?>
