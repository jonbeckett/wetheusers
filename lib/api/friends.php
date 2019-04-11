<?php
require_once("mail.php");

function friend_add($username){

	if (isset($_SESSION["user_id"])){

		$mysqli = db_connect();

		// fetch the ID of the friend
		$sql = "SELECT * FROM Users WHERE Username='".$mysqli->real_escape_string($username)."'";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0){

			$user_row = $result->fetch_assoc();
			$user_id = $user_row["Id"];

			// delete the friendship if it exists
			$sql = "DELETE FROM Friends WHERE UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendId=".$mysqli->real_escape_string($user_id);
			$result = $mysqli->query($sql);

			// insert a new record
			$sql = "INSERT INTO Friends (UserId,FriendId,Created,IPCreated) VALUES (".$mysqli->real_escape_string($_SESSION["user_id"]).",".$mysqli->real_escape_string($user_id).",NOW(),'".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."')";
			$result = $mysqli->query($sql);

			// next find out if the user we are adding as a friend wishes to be informed
			if ($user_row["NotifyNewFriends"]==1 AND $user_row["Status"]==USER_STATUS_VALIDATED){
				$mail_to      = $user_row["Email"];
				$mail_subject = SITE_NAME." - ".$_SESSION["user_name"]." added you as a friend!";
				$mail_message = $_SESSION["user_name"]." added you as a friend!\n\n"
					."http://wetheusers.net/".$_SESSION["user_name"]."\n\n";
				send_email($mail_to, $mail_subject, $mail_message);
			}

			SendSystemMessage(
				$mysqli,
				$user_id,
				$_SESSION["user_name"]." added you as a friend!",
				"[".$_SESSION["user_name"]."](http://wetheusers.net/".$_SESSION["user_name"].") has added you as a friend",
				4);


			return true;

		} else {
			return false;
		}


	} else {
		header("Location: /401");
	}
}



function friend_remove($username){

	if (isset($_SESSION["user_id"])){

		$mysqli = db_connect();

		// fetch the ID of the friend
		$sql = "SELECT Id FROM Users WHERE Username='".$mysqli->real_escape_string($username)."'";
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0){

			$user_row = $result->fetch_assoc();
			$user_id = $user_row["Id"];

			// delete the friendship if it exists
			$sql = "DELETE FROM Friends WHERE UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendId=".$mysqli->real_escape_string($user_id);
			$result = $mysqli->query($sql);

			return "success";

		} else {
			return "failure";
		}

	} else {
		header("Location: /401");
	}
}

?>
