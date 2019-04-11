<?php

function render_firehose_page($numposts=20,$page=1){

	$start = (intval($page) - 1) * $numposts;

	$html = render_header("The Firehose");

	$html .= "<div class=\"bg_menu_wrapper\">\n"
		."<ul class=\"bg_menu\">\n"
		."<li class=\"selected\"><a href=\"/explore/firehose\" title=\"Firehose\">Firehose</a></li>\n"
		."<li><a href=\"/explore/popular\" title=\"Popular\">Popular</a></li>\n"
		."<li><a href=\"/explore/tags\" title=\"Tags\">Tags</a></li>\n"
		."<li><a href=\"/explore/directory\" title=\"Directory\">Directory</a></li>\n"
		."<li><a href=\"/explore/suggested\" title=\"Suggested Users\">Suggested</a></li>\n"
		."<li><a href=\"/explore/search\" title=\"Search\">Search</a></li>\n"
		."</ul>\n"
		."<div class=\"clear\"></div>\n"
		."</div>\n";
		
	$mysqli = db_connect();

	$sql = "";
	$count_sql = "";

	if (isset($_SESSION["user_id"])){

		$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar,Likes.Id AS LikeId FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Likes ON Likes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Likes.PostId=Posts.Id"
			." LEFT OUTER JOIN Friends FriendsOfAuthor ON Posts.UserId=FriendsOfAuthor.UserId AND FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." WHERE"
			." ((FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." ORDER BY Created DESC LIMIT ".$mysqli->real_escape_string($start).",".$mysqli->real_escape_string($numposts);

		$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsOfAuthor ON Posts.UserId=FriendsOfAuthor.UserId AND FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." WHERE"
			." ((FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED;

	} else {

		$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar, null AS LikeId FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." WHERE"
			." Posts.Privacy=".POST_PRIVACY_PUBLIC
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." ORDER BY Created DESC LIMIT ".$mysqli->real_escape_string($start).",".$mysqli->real_escape_string($numposts);

		$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." WHERE"
			." Posts.Privacy=".POST_PRIVACY_PUBLIC
			." AND Posts.Status=".POST_STATUS_PUBLISHED;
	}

	// fetch count for pagination
	$count_result = $mysqli->query($sql_count);
	$count_row = $count_result->fetch_assoc();
	$count = $count_row["NumPosts"];

	$post_result = $mysqli->query($sql);


	
	$html .= "<div id=\"header\">\n"
		."<h1>The Firehose</h1>\n"
		."<p>Everything posted by everybody, across the entire site (well... everything they are choosing to let you see...)</p>\n"
		."</div>";

	$html .= render_posts($mysqli,$post_result);

	/*
	$html .= "<div class=\"tiles\">\n";
	while ($post_row =@ $post_result->fetch_assoc()){
		$html .= render_tile($mysqli,$post_row,false);
	}
	$html .= "</div> <!-- .tiles -->\n";
	*/

	// Pagination
	$html .= render_pagination("explore/firehose/".$numposts,$page,$count,$numposts);

	$html .= render_display_controls();

	$html .= render_footer();

	return $html;

}

?>
