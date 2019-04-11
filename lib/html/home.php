<?php

function render_home_page($numposts=20,$page=1){

	$start = (intval($page) - 1) * $numposts;

	$html = render_header("Home");

	$mysqli = db_connect();

	if (isset($_SESSION["user_id"])){

		// does the logged in user have any friends yet ?
		$friends_sql = "SELECT COUNT(*) AS NumFriends FROM Friends WHERE UserId=".$mysqli->real_escape_string($_SESSION["user_id"]);
		$friends_result = $mysqli->query($friends_sql);
		$friends_row = $friends_result->fetch_assoc();
		$friends_count = $friends_row["NumFriends"];

		if ($friends_count>0){

			// get the friends only posts by people who call you a friend
			// also get friends public posts
			// also get your own posts

			$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar,Likes.Id AS LikeId FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." LEFT OUTER JOIN Friends FriendsOfMe ON FriendsOfMe.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendsOfMe.FriendId=Posts.UserId"
				." LEFT OUTER JOIN Friends FriendsOfAuthor ON Posts.UserId=FriendsOfAuthor.UserId AND FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
				." LEFT OUTER JOIN Likes ON Likes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Likes.PostId=Posts.Id"
				." WHERE"
				." ((FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY." AND FriendsOfMe.FriendId=Posts.UserId)"
				." OR"
				." (FriendsOfMe.FriendId=Posts.UserId AND Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
				." OR"
				." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." ORDER BY Created DESC LIMIT ".$mysqli->real_escape_string($start).",".$mysqli->real_escape_string($numposts);

				
			$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." LEFT OUTER JOIN Friends FriendsOfMe ON FriendsOfMe.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendsOfMe.FriendId=Posts.UserId"
				." LEFT OUTER JOIN Friends FriendsOfAuthor ON Posts.UserId=FriendsOfAuthor.UserId AND FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
				." LEFT OUTER JOIN Likes ON Likes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Likes.PostId=Posts.Id"
				." WHERE"
				." ((FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY." AND FriendsOfMe.FriendId=Posts.UserId)"
				." OR"
				." (FriendsOfMe.FriendId=Posts.UserId AND Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
				." OR"
				." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
				." AND Posts.Status=".POST_STATUS_PUBLISHED;


		} else {

			// no friends - fetch popular content from the last 7 days
			$html .= "<div id=\"header\">\n"
				."<h1>Welcome to ".SITE_NAME." - No Friends Yet?</h1>\n"
				."<p>Here is some popular content from the last 7 days. You might also like to check out the <a href=\"/explore/firehose\">Firehose</a>.</p>\n"
				."</div>\n";

			$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar,null AS LikeId FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." WHERE (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '7' DAY))"
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." AND Posts.Privacy=".POST_PRIVACY_PUBLIC
				." ORDER BY Created DESC LIMIT ".$mysqli->real_escape_string($start).",".$mysqli->real_escape_string($numposts);

			$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." WHERE (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '7' DAY))"
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." AND Posts.Privacy=".POST_PRIVACY_PUBLIC;

		}
	} else {

		// not logged in - fetch popular content from the last 7 days
		$html .= "<div id=\"header\">\n"
			."<h1>Post, Friend, Follow, Like, Comment</h1>\n"
			."<p>Welcome to a new social experience on the internet - <strong><a href=\"/register\">register</a></strong> now, and begin posting!</p>\n"
			."</div>\n";

		$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar,null AS LikeId FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." WHERE (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '7' DAY))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Posts.Privacy=".POST_PRIVACY_PUBLIC
			." ORDER BY Posts.Likes DESC LIMIT ".$mysqli->real_escape_string($start).",".$mysqli->real_escape_string($numposts);

		$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." WHERE (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '7' DAY))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Posts.Privacy=".POST_PRIVACY_PUBLIC;

		//print "<p>".$sql;

		//print "<p>".$sql_count;

	}

	// fetch count for pagination
	$count_result = $mysqli->query($sql_count);
	$count_row = $count_result->fetch_assoc();
	$count = $count_row["NumPosts"];

	$post_result = $mysqli->query($sql);

	if (isset($_GET["debug"])) print "<p><br /><br /><code>".$sql."</code></p>";
	
	$html .= render_posts($mysqli,$post_result);

	$html .= render_pagination("home/".$numposts,$page,$count,$numposts);

	$html .= render_display_controls();

	$html .= render_footer();


	// $html .= "<pre>".$sql."</pre>\n";

	return $html;
}

?>
