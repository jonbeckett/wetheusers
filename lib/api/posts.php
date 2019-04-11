<?php

require_once("mail.php");

function post_add(){

	if (isset($_SESSION["user_id"])){

		$post_title = (isset($_POST["title"])) ? $_POST["title"] : "";
		$post_body = (isset($_POST["body"])) ? $_POST["body"] : "";
		$post_tags = (isset($_POST["tags"])) ? $_POST["tags"] : "";
		$post_privacy = (isset($_POST["privacy"])) ? $_POST["privacy"] : "";
		$post_status = (isset($_POST["status"])) ? $_POST["status"] : "";

		$link_title = ($post_title != "") ? $post_title : "Untitled";
		
		if ($post_privacy != "" && $post_status != "") {

			$new_post_id = 0;

			$mysqli = db_connect();

			$mysqli->query("INSERT INTO Posts (UserId,Title,Body,Privacy,Status,Created,IPCreated) VALUES ("
				."'".$mysqli->real_escape_string($_SESSION["user_id"])."',"
				."'".$mysqli->real_escape_string($post_title)."',"
				."'".$mysqli->real_escape_string($post_body)."',"
				."'".$mysqli->real_escape_string($post_privacy)."',"
				."'".$mysqli->real_escape_string($post_status)."',"
				."NOW(),"
				."'".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."'"
				.")");

			$new_post_id = $mysqli->insert_id;

			// do we have a photo ?
			upload_photo($new_post_id,$mysqli);

			// break the tags up into individual terms
			$tags = explode(",",$post_tags);

			if (count($tags)>0){

				// trim all tags
				$tags = array_map("trim",$tags);

				foreach($tags as $tag){

					if ($tag != ""){
						$tag = strtolower($tag);

						$tag_id = 0;

						// find out if the tag exists
						$sql = "SELECT * FROM Tags WHERE Name='".$mysqli->real_escape_string($tag)."'";
						$result = $mysqli->query($sql);
						if ($result->num_rows > 0) {
							// if it does exist, get it's ID
							$row =@ $result->fetch_assoc();
							$tag_id = $row["Id"];
						} else {
							// if it does not exist, add it, and get the ID
							$sql = "INSERT INTO Tags (Name) VALUES ('".$mysqli->real_escape_string($tag)."')";
							$mysqli->query($sql);
							$tag_id = $mysqli->insert_id;
						}

						// add the tag to the PostTags list
						$mysqli->query("INSERT INTO PostTags (PostId,TagId,Created) VALUES (".$mysqli->real_escape_string($new_post_id).",".$mysqli->real_escape_string($tag_id).",Now())");
					}
				}

			}

			if ($post_status == POST_STATUS_PUBLISHED){

				

				// check if we have any users to notify
				if ($post_privacy == POST_PRIVACY_FRIENDS_ONLY){

					// fetch people that the writer calls a friend AND where the people call the writer a friend
					$sql = "SELECT DISTINCT Users.Id,Users.Email,Users.NotifyFriendsPosts FROM Users"
						." LEFT OUTER JOIN Friends FriendsOfMe ON FriendsOfMe.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendsOfMe.FriendId=Users.Id"
						." LEFT OUTER JOIN Friends FriendsOfAuthor ON Users.Id=FriendsOfAuthor.UserId AND FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
						." WHERE (FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendsOfMe.FriendId=Users.Id)";

				} else {
					if ($post_privacy != POST_PRIVACY_PRIVATE){
						// fetch everybody that calls the author a friend
						$sql = "SELECT Users.Id,Users.Email,Users.NotifyFriendsPosts FROM Users"
							." INNER JOIN Friends ON Friends.UserId=Users.Id"
							." WHERE Friends.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"]);
					} else {
						$sql = "SELECT * FROM Friends WHERE 1=2";
					}
				}

				$user_result = $mysqli->query($sql);
				if ($user_result->num_rows > 0){
					while ($user_row =@ $user_result->fetch_assoc()){
						if ($user_row["NotifyFriendsPosts"] == 1){
						
							$mail_to      = $user_row["Email"];
							$mail_subject = SITE_NAME." - '".$_SESSION["user_name"]."' has a new post!";
							$mail_message = "Your friend '".$_SESSION["user_name"]."' has just posted the following...\n\n"
								.$post_title."\n"
								."http://wetheusers.net/post/".$new_post_id."/".toAscii($link_title)."\n\n";

							
							send_email($mail_to, $mail_subject, $mail_message);
						}

						// send the system message
						SendSystemMessage($mysqli,
							$user_row["Id"],
							$_SESSION["user_name"]." has written a new post - ".$post_title,
							"[".$_SESSION["user_name"]."](http://wetheusers.net/".$_SESSION["user_name"].") has written a new post - [".$link_title."](http://wetheusers.net/post/".$new_post_id."/".toAscii($link_title).")",
							3);

					}
				}
				
			}

			return $new_post_id;

		} else {
			return -1;
		}
	} else {
		header("Location: /401");
	}

}



function post_edit(){

	if (isset($_SESSION["user_id"])){

		$post_id = $_POST["id"];
		$post_title = (isset($_POST["title"])) ? $_POST["title"] : "";
		$post_body = (isset($_POST["body"])) ? $_POST["body"] : "";
		$post_tags = (isset($_POST["tags"])) ? $_POST["tags"] : "";
		$post_privacy = (isset($_POST["privacy"])) ? $_POST["privacy"] : "";
		$post_status = (isset($_POST["status"])) ? $_POST["status"] : "";
		
		if ($post_privacy != "" && $post_status != "") {

			$mysqli = db_connect();

			$mysqli->query("UPDATE Posts SET"
				."  UserId = '".$mysqli->real_escape_string($_SESSION["user_id"])."'"
				.", Title = '".$mysqli->real_escape_string($post_title)."'"
				.", Body = '".$mysqli->real_escape_string($post_body)."'"
				.", Privacy = '".$mysqli->real_escape_string($post_privacy)."'"
				.", Status = '".$mysqli->real_escape_string($post_status)."'"
				.", Edited = NOW()"
				.", IPEdited = '".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."'"
				." WHERE Id=".$mysqli->real_escape_string($post_id));

			// do we have a photo ?
			upload_photo($post_id,$mysqli);

			// remove the existing post tags
			$mysqli->query("DELETE FROM PostTags WHERE PostId=".$mysqli->real_escape_string($post_id));

			// break the tags up into individual terms
			$tags = explode(",",$post_tags);

			if (count($tags)>0){

				// trim all tags
				$tags = array_map("trim",$tags);

				foreach($tags as $tag){
					if ($tag != ""){
						$tag = strtolower($tag);

						$tag_id = 0;

						// find out if the tag exists
						$sql = "SELECT * FROM Tags WHERE Name='".$mysqli->real_escape_string($tag)."'";
						$result = $mysqli->query($sql);
						if ($result->num_rows > 0) {
							// if it does exist, get it's ID
							$row =@ $result->fetch_assoc();
							$tag_id = $row["Id"];
						} else {
							// if it does not exist, add it, and get the ID
							$sql = "INSERT INTO Tags (Name) VALUES ('".$mysqli->real_escape_string($tag)."')";
							$mysqli->query($sql);
							$tag_id = $mysqli->insert_id;
						}

						// add the tag to the PostTags list
						$mysqli->query("INSERT INTO PostTags (PostId,TagId,Created) VALUES (".$mysqli->real_escape_string($post_id).",".$mysqli->real_escape_string($tag_id).",Now())");
					}
				}

			}

			return $post_id;

		} else {
			return -1;
		}
	} else {
		header("Location: /401");
	}
}

function post_delete($post_id){

	if (isset($_SESSION["user_id"])){

		// check if we own the post
		// look for user in database
		$mysqli = db_connect();
		$sql = "SELECT * FROM Posts WHERE Id=".$mysqli->real_escape_string($post_id);
		$result = $mysqli->query($sql);

		if ($result->num_rows > 0){
			$row = $result->fetch_assoc();

			if ($row["UserId"] == $_SESSION["user_id"]){

				// remove the tags
				$mysqli->query("DELETE FROM PostTags WHERE PostId=".$mysqli->real_escape_string($post_id));

				// remove the post
				$mysqli->query("DELETE FROM Posts WHERE Id=".$mysqli->real_escape_string($post_id));

				// remove the comments
				$mysqli->query("DELETE FROM Comments WHERE PostId=".$mysqli->real_escape_string($post_id));

				// remove image if there is one
				if ($row["Photo"] != ""){
					if (file_exists(realpath("../".$row["Photo"]))) {
						unlink(realpath("../".$row["Photo"]));
					}
				}

				return true;

			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		header("Location: /401");
	}
}



?>
