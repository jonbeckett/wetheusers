<?php
session_start();

// look for a cookie
if ( isset($_COOKIE["wetheusers_validation_code"]) ) {

	$mysqli = db_connect();
	$result = $mysqli->query("SELECT * FROM Users WHERE ValidationCode='".$mysqli->real_escape_string($_COOKIE["wetheusers_validation_code"])."'");
	
	if ($result->num_rows > 0){
	
		$row = $result->fetch_assoc();
		
		$_SESSION["user_id"]     = $row["Id"];
		$_SESSION["user_name"]   = $row["Username"];
		$_SESSION["user_email"]  = $row["Email"];
		$_SESSION["user_status"] = $row["Status"];
		$_SESSION["user_avatar"] = $row["Avatar"];
		
	}
}

if (!isset($_SESSION["display_mode"])) {
	$_SESSION["display_mode"] = DISPLAY_MODE_TILE;
}

?>
