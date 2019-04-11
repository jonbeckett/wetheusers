<?php

require_once("mail.php");

function post_like($post_id){

	if (isset($_SESSION["user_id"])){

		$mysqli = db_connect();

		$sql = "SELECT Posts.*,Users.Username,Users.Avatar,Users.NotifyLikes AS NotifyLikes,Users.Email AS Email FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Likes ON Likes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Likes.PostId=Posts.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId AND FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." WHERE"
			." ((FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Posts.Id='".$mysqli->real_escape_string($post_id)."'";
			
		
		$post_result = $mysqli->query($sql);

		if ($post_result->num_rows > 0){

			$post_row = $post_result->fetch_assoc();

			$link_title = ($post_row["Title"] != "") ? $post_row["Title"] : "Untitled";
			
			// remove previous likes (to prevent repeated calls)
			$sql = "DELETE FROM Likes WHERE UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND PostId=".$mysqli->real_escape_string($post_id);
			$mysqli->query($sql);

			// add a new like
			$sql = "INSERT INTO Likes (UserId,PostId,Created,IPCreated) VALUES (".$mysqli->real_escape_string($_SESSION["user_id"]).",".$mysqli->real_escape_string($post_id).",NOW(),'".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."')";
			$mysqli->query($sql);

			// find out how many likes the post now has
			$sql = "SELECT COUNT(Id) AS NumLikes FROM Likes WHERE PostId=".$mysqli->real_escape_string($post_id);
			$likes_result = $mysqli->query($sql);
			$likes_row = $likes_result->fetch_assoc();

			// update the like count on the post
			$sql = "UPDATE Posts SET Likes=".$mysqli->real_escape_string($likes_row["NumLikes"])." WHERE Id=".$mysqli->real_escape_string($post_id);
			$mysqli->query($sql);

			// find out if the User wants a notification
			if ($post_row["NotifyLikes"] == 1){
			
				$mail_to      = $post_row["Email"];
				$mail_subject = SITE_NAME." - ".$_SESSION["user_name"]." liked your post '".$link_title."'";
				$mail_message = $_SESSION["user_name"]." liked your post '".$link_title."'. The post now has ".$likes_row["NumLikes"]." likes.\n\n"
					."http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($link_title)."\n\n";

				send_email($mail_to, $mail_subject, $mail_message);
			}

			SendSystemMessage(
				$mysqli,
				$post_row["UserId"],
				$_SESSION["user_name"]." liked your post '".$link_title."'",
				"[".$_SESSION["user_name"]."](http://wetheusers.net/".$_SESSION["user_name"].") liked your post [".$link_title."](http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($link_title)."). The post now has ".$likes_row["NumLikes"]." likes.",
				5);


			return $likes_row["NumLikes"];

		} else {

			return -1;

		}


	} else {
		return -1;
	}
}

function post_unlike($post_id){

	if (isset($_SESSION["user_id"])){

		$mysqli = db_connect();

		// can we see the post ?
		$sql = "SELECT Posts.*,Users.Username,Users.Avatar FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId"
			." WHERE"
			." ((FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Posts.Id='".$mysqli->real_escape_string($post_id)."'";

		$post_result = $mysqli->query($sql);

		if ($post_result->num_rows > 0){

			// remove previous likes (to prevent repeated calls)
			$sql = "DELETE FROM Likes WHERE UserId=".$_SESSION["user_id"]." AND PostId=".$mysqli->real_escape_string($post_id);
			$mysqli->query($sql);

			// find out how many likes the post now has
			$sql = "SELECT COUNT(Id) AS NumLikes FROM Likes WHERE PostId=".$mysqli->real_escape_string($post_id);
			$likes_result = $mysqli->query($sql);
			$likes_row = $likes_result->fetch_assoc();

			// update the like count on the post
			$sql = "UPDATE Posts SET Likes=".$mysqli->real_escape_string($likes_row["NumLikes"])." WHERE Id=".$mysqli->real_escape_string($post_id);
			$mysqli->query($sql);

			return $likes_row["NumLikes"];

		} else {

			return -1;

		}
	} else {
		return -1;
	}

}

function comment_like($post_id,$comment_id){

	if (isset($_SESSION["user_id"])){

		// open database connection
		$mysqli = db_connect();

		// get the post
		$sql = "SELECT Posts.*,Users.Username,Users.Avatar,Users.NotifyLikes AS NotifyLikes,Users.Email AS Email FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId AND FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." WHERE"
			." ((FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Posts.Id='".$mysqli->real_escape_string($post_id)."'";
		
		$post_result = $mysqli->query($sql);

		$sql = "SELECT * FROM Comments"
			." INNER JOIN Users ON Comments.UserId=Users.Id"
			." WHERE Comments.Id='".$mysqli->real_escape_string($comment_id)."'";
		
		$comment_result = $mysqli->query($sql);
		
		if ($post_result->num_rows > 0 && $comment_result->num_rows > 0 ){

			$post_row = $post_result->fetch_assoc();

			$comment_row = $comment_result->fetch_assoc();
			
			// remove previous likes (to prevent repeated calls)
			$sql = "DELETE FROM CommentLikes WHERE UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND PostId=".$mysqli->real_escape_string($post_id)." AND CommentId=".$mysqli->real_escape_string($comment_id);
			$mysqli->query($sql);

			// add a new like
			$sql = "INSERT INTO CommentLikes (UserId,PostId,CommentId,Created,IPCreated) VALUES (".$mysqli->real_escape_string($_SESSION["user_id"]).",".$mysqli->real_escape_string($post_id).",".$mysqli->real_escape_string($comment_id).",NOW(),'".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."')";
			$mysqli->query($sql);

			// find out how many likes the comment now has
			$sql = "SELECT COUNT(Id) AS NumLikes FROM CommentLikes WHERE PostId=".$mysqli->real_escape_string($post_id)." AND CommentId=".$mysqli->real_escape_string($comment_id);
			$likes_result = $mysqli->query($sql);
			$likes_row = $likes_result->fetch_assoc();

			// update the like count on the post
			$sql = "UPDATE Comments SET Likes=".$mysqli->real_escape_string($likes_row["NumLikes"])." WHERE Id=".$mysqli->real_escape_string($comment_id);
			$mysqli->query($sql);

			// find out if the User wants a notification
			if ($comment_row["NotifyLikes"] == 1){
			
				$mail_to      = $comment_row["Email"];
				$mail_subject = SITE_NAME." - ".$_SESSION["user_name"]." liked your comment to the post '".$post_row["Title"]."'";
				$mail_message = $_SESSION["user_name"]." liked your comment to the post '".$post_row["Title"]."'. The comment now has ".$likes_row["NumLikes"]." likes.\n\n"
					."http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."\n\n";

				send_email($mail_to, $mail_subject, $mail_message);
			}

			SendSystemMessage(
				$mysqli,
				$comment_row["UserId"],
				$_SESSION["user_name"]." liked your comment to the post '".$post_row["Title"]."'",
				"[".$_SESSION["user_name"]."](http://wetheusers.net/".$_SESSION["user_name"].") liked your comment to the post [".$post_row["Title"]."](http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."). The comment now has ".$likes_row["NumLikes"]." likes.",
				6);


			return $likes_row["NumLikes"];

		} else {

			return -1;

		}


	} else {
		return -1;
	}
}

function comment_unlike($post_id,$comment_id){

	if (isset($_SESSION["user_id"])){

		$mysqli = db_connect();

		// can we see the post ?
		$sql = "SELECT Posts.*,Users.Username,Users.Avatar FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId"
			." WHERE"
			." ((FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Posts.Id='".$mysqli->real_escape_string($post_id)."'";

		$post_result = $mysqli->query($sql);

		$sql = "SELECT * FROM Comments WHERE Id=".$mysqli->real_escape_string($comment_id);
		
		$comment_result = $mysqli->query($sql);

		if ($post_result->num_rows > 0 && $comment_result){

			// remove previous likes (to prevent repeated calls)
			$sql = "DELETE FROM CommentLikes WHERE UserId=".$_SESSION["user_id"]." AND PostId=".$mysqli->real_escape_string($post_id)." AND CommentId=".$mysqli->real_escape_string($comment_id);
			$mysqli->query($sql);

			// find out how many likes the post now has
			$sql = "SELECT COUNT(Id) AS NumLikes FROM CommentLikes WHERE PostId=".$mysqli->real_escape_string($post_id)." AND CommentId=".$mysqli->real_escape_string($comment_id);
			$likes_result = $mysqli->query($sql);
			$likes_row = $likes_result->fetch_assoc();

			// update the like count on the post
			$sql = "UPDATE Comments SET Likes=".$mysqli->real_escape_string($likes_row["NumLikes"])." WHERE Id=".$mysqli->real_escape_string($comment_id);
			$mysqli->query($sql);

			return $likes_row["NumLikes"];

		} else {

			return -1;

		}
	} else {
		return -1;
	}

}

?>
