<?php

function render_popular_page($page){

	$start = (intval($page) - 1) * 20;

	$html = render_header("Popular Posts");

	$html .= "<div class=\"bg_menu_wrapper\">\n"
		."<ul class=\"bg_menu\">\n"
		."<li><a href=\"/explore/firehose\" title=\"Firehose\">Firehose</a></li>\n"
		."<li class=\"selected\"><a href=\"/explore/popular\" title=\"Popular\">Popular</a></li>\n"
		."<li><a href=\"/explore/tags\" title=\"Tags\">Tags</a></li>\n"
		."<li><a href=\"/explore/directory\" title=\"Directory\">Directory</a></li>\n"
		."<li><a href=\"/explore/suggested\" title=\"Suggested Users\">Suggested</a></li>\n"
		."<li><a href=\"/explore/search\" title=\"Search\">Search</a></li>\n"
		."</ul>\n"
		."<div class=\"clear\"></div>\n"
		."</div>\n";

	$mysqli = db_connect();

	if (isset($_SESSION["user_id"])){

		$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar,Likes.Id AS LikeId FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Likes ON Likes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Likes.PostId=Posts.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId"
			." WHERE"
			." ((FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '1' DAY))"
			." ORDER BY (Posts.Likes + Posts.Comments) DESC LIMIT ".$mysqli->real_escape_string($start).",20";

		$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId"
			." WHERE"
			." ((FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '1' DAY))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED;

	} else {

		$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar, null AS LikeId FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." WHERE"
			." Posts.Privacy=".POST_PRIVACY_PUBLIC
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '1' DAY))"
			." ORDER BY (Posts.Likes + Posts.Comments) DESC LIMIT ".$mysqli->real_escape_string($start).",20";

		$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." WHERE"
			." Posts.Privacy=".POST_PRIVACY_PUBLIC
			." AND (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '1' DAY))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED;
	}

	// fetch count for pagination
	$count_result = $mysqli->query($sql_count);
	$count_row = $count_result->fetch_assoc();
	$count = $count_row["NumPosts"];

	$post_result = $mysqli->query($sql);

	$html .= "<div id=\"header\">\n"
		."<h1>Popular Posts</h1>\n"
		."<p>The most popular content available to you of the last 24 hours, judged by comments and likes...</p>\n"
		."</div>";
		
	$html .= render_posts($mysqli,$post_result);

	$html .= render_pagination("explore/popular",$page,$count,20);

	$html .= render_display_controls();

	$html .= render_footer();

	return $html;
}

?>
