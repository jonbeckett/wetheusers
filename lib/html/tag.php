<?php

function render_tag_page($tag_name,$page){

	$start = (intval($page) - 1) * 20;

	$html = render_header($tag_name,"",true);

	$html .= "<div class=\"bg_menu_wrapper\">\n"
		."<ul class=\"bg_menu\">\n"
		."<li><a href=\"/explore/firehose\" title=\"Firehose\">Firehose</a></li>\n"
		."<li><a href=\"/explore/popular\" title=\"Popular\">Popular</a></li>\n"
		."<li class=\"selected\"><a href=\"/explore/tags\" title=\"Tags\">Tags</a></li>\n"
		."<li><a href=\"/explore/directory\" title=\"Directory\">Directory</a></li>\n"
		."<li><a href=\"/explore/suggested\" title=\"Suggested Users\">Suggested</a></li>\n"
		."<li><a href=\"/explore/search\" title=\"Search\">Search</a></li>\n"
		."</ul>\n"
		."<div class=\"clear\"></div>\n"
		."</div>\n";
		
	$mysqli = db_connect();

	$sql = "";
	$sql_count = "";
	if (isset($_SESSION["user_id"])){

		$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar,Likes.Id AS LikeId FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." INNER JOIN PostTags ON Posts.Id=PostTags.PostId"
			." INNER JOIN Tags ON PostTags.TagId=Tags.Id"
			." LEFT OUTER JOIN Likes ON Likes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Likes.PostId=Posts.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId"
			." WHERE"
			." ((FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Tags.Name='".$mysqli->real_escape_string($tag_name)."'"
			." ORDER BY Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";

		$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." INNER JOIN PostTags ON Posts.Id=PostTags.PostId"
			." INNER JOIN Tags ON PostTags.TagId=Tags.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId"
			." WHERE"
			." ((FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Tags.Name='".$mysqli->real_escape_string($tag_name)."'";


	} else {

		$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." INNER JOIN PostTags ON Posts.Id=PostTags.PostId"
			." INNER JOIN Tags ON PostTags.TagId=Tags.Id"
			." WHERE"
			." Posts.Privacy=".POST_PRIVACY_PUBLIC
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Tags.Name='".$mysqli->real_escape_string($tag_name)."'"
			." ORDER BY Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";

		$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." INNER JOIN PostTags ON Posts.Id=PostTags.PostId"
			." INNER JOIN Tags ON PostTags.TagId=Tags.Id"
			." WHERE"
			." Posts.Privacy=".POST_PRIVACY_PUBLIC
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Tags.Name='".$mysqli->real_escape_string($tag_name)."'";
	}

	// fetch count for pagination
	$count_result = $mysqli->query($sql_count);
	$count_row = $count_result->fetch_assoc();
	$count = $count_row["NumPosts"];

	$post_result = $mysqli->query($sql);

	$html .= "<div id=\"header\"><h1>Posts tagged &#8216;<span>".$tag_name."</span>&#8217;</h1></div>\n";

	$html .= render_posts($mysqli,$post_result);

	$html .= render_pagination("explore/tag/".$tag_name,$page,$count,20);

	$html .= render_display_controls();

	$html .= render_footer();

	return $html;
}

?>
