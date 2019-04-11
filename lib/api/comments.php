<?php

require_once("mail.php");

function comment_add(){

	if (isset($_SESSION["user_id"])){

		$post_id = (isset($_POST["post_id"])) ? $_POST["post_id"] : "";
		$body = (isset($_POST["body"])) ? $_POST["body"] : "";

		if ($post_id != "" && $body != ""){

			$mysqli = db_connect();

			$sql = "SELECT Posts.Id,Posts.Title,Users.NotifyComments,Users.Username,Users.Email,Posts.UserId FROM Posts"
				." INNER JOIN Users ON Users.Id=Posts.UserId"
				." WHERE Posts.Id=".$mysqli->real_escape_string($post_id);

			$post_result = $mysqli->query($sql);

			if ($post_result->num_rows > 0){

				$post_row = $post_result->fetch_assoc();

				$link_title = ($post_row["Title"] != "") ? $post_row["Title"] : "Untitled";
				
				// Add the comment to the comments table

				$sql = "INSERT INTO Comments ("
					."PostId,UserId,Body,Created,IPCreated"
					.") VALUES ("
					.$mysqli->real_escape_string($post_id)
					.",".$mysqli->real_escape_string($_SESSION["user_id"])
					.",'".$mysqli->real_escape_string($body)."'"
					.",Now()"
					.",'".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."'"
					.")";

				$mysqli->query($sql);

				$new_comment_id = $mysqli->insert_id;

				// Update the number of comments on the post
				$count_sql = "SELECT COUNT(*) AS NumComments FROM Comments WHERE PostId=".$mysqli->real_escape_string($post_id);
				$count_result = $mysqli->query($count_sql);
				$count_row = $count_result->fetch_assoc();

				$update_sql = "UPDATE Posts SET Comments=".$mysqli->real_escape_string($count_row["NumComments"])." WHERE Id=".$mysqli->real_escape_string($post_id);
				$update_result = $mysqli->query($update_sql);

				// do an email notification if required

				if ($post_row["UserId"] != $_SESSION["user_id"]){
				
					if ( $post_row["NotifyComments"]==1 ){

						$mail_to      = $post_row["Email"];
						$mail_subject = SITE_NAME." - ".$_SESSION["user_name"]." commented on '".$post_row["Title"]."'";
						$mail_message = "You have received a new comment on your post '".$link_title."' by ".$_SESSION["user_name"]."...\n---\n"
							.$body."\n - ".$_SESSION["user_name"]." (http://wetheusers.net/".$_SESSION["user_name"].")\n---\n"
							."http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($link_title)."\n\n";

						send_email($mail_to, $mail_subject, $mail_message);
					}

					SendSystemMessage(
						$mysqli,
						$post_row["UserId"],
						$_SESSION["user_name"]." commented on your post '".$link_title."'",
						"[".$_SESSION["user_name"]."](http://wetheusers.net/".$_SESSION["user_name"].") commented on your post [".$link_title."](http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($link_title).")\n\n".$body,
						1);
					
				}
					
				// find out people who have commented previously that have NotifyOtherComments switched on
				$sql = "SELECT DISTINCT Users.Id AS UserId, Users.Email AS Email,Users.NotifyOtherComments"
					." FROM Users"
					." INNER JOIN Comments ON Comments.UserId=Users.Id AND Comments.PostId=".$post_row["Id"]
					." INNER JOIN Posts ON Posts.Id=".$post_row["Id"]
					." WHERE Comments.UserId<>".$mysqli->real_escape_string($_SESSION["user_id"])
					." AND Posts.UserId<>Comments.UserId";

					// not if you wrote the comment
					// not if you wrote the post
					
					
				$result = $mysqli->query($sql);
				if ($result->num_rows > 0){
					while($comment_row =@ $result->fetch_assoc()){
						if ( ($comment_row["NotifyOtherComments"] == 1) && ($post_row["UserId"]!=$_SESSION["user_id"]) ){
							$mail_to      = $comment_row["Email"];
							$mail_subject = $_SESSION["user_name"]." commented on '".$post_row["Title"]."' too";
							$mail_message = "A new comment has been posted by ".$_SESSION["user_name"]." on '".$link_title."' by ".$post_row["Username"].".\n---\n"
								.$body."\n - ".$_SESSION["user_name"]." (http://wetheusers.net/".$_SESSION["user_name"].")\n---\n"
								."http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."\n\n";

							send_email($mail_to, $mail_subject, $mail_message);
						}
						SendSystemMessage(
							$mysqli,
							$comment_row["UserId"],
							"'".$_SESSION["user_name"]."' posted a new comment on '".$link_title."' by ".$post_row["Username"],
							"A new comment has been posted by [".$_SESSION["user_name"]."](http://wetheusers.net/".$_SESSION["user_name"].") on [".$link_title."](http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($link_title).") by [".$post_row["Username"]."](http://wetheusers.net/".$post_row["Username"].") (you have also commented on this post)\n\n".$body,
							2);
					}
				}


				return "success";

			} else {
				header("Location: /404");
			}
		} else {
			header("Location: ".$_SERVER["HTTP_REFERER"]."/failure");
		}
	} else {
		header("Location: /401");
	}
}

function comment_delete($comment_id){
	// find out if we are logged in, and if we are the author of the comment
	if (isset($_SESSION["user_id"])){

		$mysqli = db_connect();
		$sql = "SELECT Comments.*,Posts.UserId AS PostUserId FROM Comments"
			." INNER JOIN Posts ON Comments.PostId=Posts.Id"
			." WHERE Comments.Id=".$mysqli->real_escape_string($comment_id);
			
		$result = $mysqli->query($sql);
		if ($result->num_rows > 0){

			$comment_row = $result->fetch_assoc();

			if ($comment_row["PostUserId"] == $_SESSION["user_id"]){

				$sql = "DELETE FROM Comments WHERE Id=".$mysqli->real_escape_string($comment_id);
				$result = $mysqli->query($sql);

				$count_sql = "SELECT COUNT(*) AS NumComments FROM Comments WHERE PostId=".$mysqli->real_escape_string($comment_row["PostId"]);
				$count_result = $mysqli->query($count_sql);
				$count_row = $count_result->fetch_assoc();

				$update_sql = "UPDATE Posts SET Comments=".$mysqli->real_escape_string($count_row["NumComments"])." WHERE Id=".$mysqli->real_escape_string($comment_row["PostId"]);
				$update_result = $mysqli->query($update_sql);

			} else {
				header("Location: /401");
			}
		} else {
			header("Location: /404");
		}

	} else {
		header("Location: /401");
	}
}

?>
