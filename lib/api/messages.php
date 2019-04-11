<?php

require_once("mail.php");

function mark_all_read(){
	if (isset($_SESSION["user_id"])){
		$mysqli = db_connect();

		$mysqli->query("UPDATE Messages SET ReadFlag=1 WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]));

		return true;
	} else {
		header("Location: /403");
	}
}

function mark_all_inbox_read(){
	if (isset($_SESSION["user_id"])){
		$mysqli = db_connect();

		$mysqli->query("UPDATE Messages SET ReadFlag=1 WHERE Type=0 AND ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]));

		return true;
	} else {
		header("Location: /403");
	}
}

function mark_all_system_read(){
	if (isset($_SESSION["user_id"])){
		$mysqli = db_connect();

		$mysqli->query("UPDATE Messages SET ReadFlag=1 WHERE Type>0 AND ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]));

		return true;
	} else {
		header("Location: /403");
	}
}

function mark_removed($message_id){
	if (isset($_SESSION["user_id"])){
		$mysqli = db_connect();
		
		$message_result = $mysqli->query("SELECT * FROM Messages WHERE Id=".$mysqli->real_escape_string($message_id));
		if ($message_result->num_rows > 0){
			$message_row = $message_result->fetch_assoc();
			if ($_SESSION["user_id"] == $message_row["ToUserId"] || $_SESSION["user_id"] == $message_row["FromUserId"]){
				
				if ($_SESSION["user_id"] == $message_row["ToUserId"]){
					$mysqli->query("UPDATE Messages SET ToStatus=1 WHERE Id=".$mysqli->real_escape_string($message_id));
				}
				if ($_SESSION["user_id"] == $message_row["FromUserId"]){
					$mysqli->query("UPDATE Messages SET FromStatus=1 WHERE Id=".$mysqli->real_escape_string($message_id));
				}
				
				return true;
				
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		header("Location: /403");
	}
}


function mark_restored($message_id){
	if (isset($_SESSION["user_id"])){
		$mysqli = db_connect();
		
		$message_result = $mysqli->query("SELECT * FROM Messages WHERE Id=".$mysqli->real_escape_string($message_id));
		if ($message_result->num_rows > 0){
			$message_row = $message_result->fetch_assoc();
			if ($_SESSION["user_id"] == $message_row["ToUserId"] || $_SESSION["user_id"] == $message_row["FromUserId"]){
				
				if ($_SESSION["user_id"] == $message_row["ToUserId"]){
					$mysqli->query("UPDATE Messages SET ToStatus=0 WHERE Id=".$mysqli->real_escape_string($message_id));
				}
				if ($_SESSION["user_id"] == $message_row["FromUserId"]){
					$mysqli->query("UPDATE Messages SET FromStatus=0 WHERE Id=".$mysqli->real_escape_string($message_id));
				}
				
				return true;
				
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		header("Location: /403");
	}
}

function mark_all_removed(){
	if (isset($_SESSION["user_id"])){
		$mysqli = db_connect();
		$mysqli->query("UPDATE Messages SET ToStatus=1 WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]));
	} else {
		header("Location: /403");
	}
}

function mark_all_inbox_removed(){
	if (isset($_SESSION["user_id"])){
		$mysqli = db_connect();
		$mysqli->query("UPDATE Messages SET ToStatus=1 WHERE Type=0 AND ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]));
	} else {
		header("Location: /403");
	}
}

function mark_all_inbox_system_removed(){
	if (isset($_SESSION["user_id"])){
		$mysqli = db_connect();
		$mysqli->query("UPDATE Messages SET ToStatus=1 WHERE Type>0 AND ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Type=1");
	} else {
		header("Location: /403");
	}
}

function mark_all_outbox_removed(){
	if (isset($_SESSION["user_id"])){
		$mysqli = db_connect();
		$mysqli->query("UPDATE Messages SET FromStatus=1 WHERE Type=0 AND FromUserId=".$mysqli->real_escape_string($_SESSION["user_id"]));
	} else {
		header("Location: /403");
	}
}

function send_message(){

	$result = 0;

	if (isset($_SESSION["user_id"])){

		$to = (isset($_POST["to"])) ? $_POST["to"] : "";
		$body = (isset($_POST["body"])) ? $_POST["body"] : "";
		$in_reply_to = (isset($_POST["in_reply_to"])) ? $_POST["in_reply_to"] : "";

		if ($to != "" && $body != ""){

			// get a database connection
			$mysqli = db_connect();

			// get the message it is a reply to (for the root id)
			$root_id = 0;
			if ($in_reply_to != ""){
				$root_result = $mysqli->query("SELECT RootId FROM Messages WHERE Id=".$mysqli->real_escape_string($in_reply_to));
				if ($root_result->num_rows > 0){
					$root_row = $root_result->fetch_assoc();
					$root_id = $root_row["RootId"];
				}
			}
			
			// get user id of 'to' parameter
			$user_result = $mysqli->query("SELECT Users.* FROM Users WHERE Users.Username='".$mysqli->real_escape_string($to)."'");
			if ($user_result->num_rows > 0){

				$user_row = $user_result->fetch_assoc();

				$sql = "INSERT INTO Messages ("
					." FromUserId,ToUserId,ParentId,Body,Created,IPCreated"
					.") VALUES ("
					.$mysqli->real_escape_string($_SESSION["user_id"]).","
					.$mysqli->real_escape_string($user_row["Id"]).","
					.(($in_reply_to!="") ? $mysqli->real_escape_string($in_reply_to)."," : "0,")
					."'".$mysqli->real_escape_string($body)."',"
					."Now(),"
					."'".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."'"
					.")";

				$mysqli->query($sql);

				$message_id = $mysqli->insert_id;

				// update the root id
				if ($in_reply_to!=""){
					// its a reply - use root id found earlier
					$mysqli->query("UPDATE Messages SET RootId=".$mysqli->real_escape_string($root_id)." WHERE Id=".$mysqli->real_escape_string($message_id));
				} else {
					// its not a reply - use message id
					$mysqli->query("UPDATE Messages SET RootId=".$mysqli->real_escape_string($message_id)." WHERE Id=".$mysqli->real_escape_string($message_id));
				}
				
				// find out if the user wants email notification of the message

				if ($user_row["NotifyMessages"] == 1){

					$mail_to      = $user_row["Email"];
					$mail_subject = SITE_NAME." - ".$_SESSION["user_name"]." has sent you a message";
					$mail_message = $_SESSION["user_name"]." has sent you a message...\n\n"
						.$body."\n - ".$_SESSION["user_name"]." (http://wetheusers.net/".$_SESSION["user_name"].")\n\n"
						."http://wetheusers.net/message/".$message_id."\n\n";

					send_email($mail_to, $mail_subject, $mail_message);
				}

				// if the message was a reply, set the message it replied to as "Read"
				if ($in_reply_to!=""){
					$sql = "UPDATE Messages SET ReadFlag=1 WHERE Id=".$mysqli->real_escape_string($in_reply_to);
					$mysqli->query($sql);
				}

				if ($in_reply_to!=""){
					return $in_reply_to;
				} else {
					return $message_id;
				}

			} else {
				// user not found
				$result = -2;
			}


		} else {
			// required information is not present
			$result = -1;
		}

	} else {
		header("Location: /403");
	}

	return $result;
}

?>
