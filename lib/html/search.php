<?php

function render_search_page($search_terms = "",$page = 1){

	$start = (intval($page) - 1) * 20;

	$html = render_header("Search");

	$html .= "<div class=\"bg_menu_wrapper\">\n"
		."<ul class=\"bg_menu\">\n"
		."<li><a href=\"/explore/firehose\" title=\"Firehose\">Firehose</a></li>\n"
		."<li><a href=\"/explore/popular\" title=\"Popular\">Popular</a></li>\n"
		."<li><a href=\"/explore/tags\" title=\"Tags\">Tags</a></li>\n"
		."<li><a href=\"/explore/directory\" title=\"Directory\">Directory</a></li>\n"
		."<li><a href=\"/explore/suggested\" title=\"Suggested Users\">Suggested</a></li>\n"
		."<li class=\"selected\"><a href=\"/explore/search\" title=\"Search\">Search</a></li>\n"
		."</ul>\n"
		."<div class=\"clear\"></div>\n"
		."</div>\n";
	
	$html .= "<div id=\"header\">\n"
		."<h1>Search</h1>\n"
		."<p>Search the title and body of posts.</p>\n"
		."<table id=\"search_form\" cellspacing=\"0\" cellpadding=\"5\"><tr>\n"
		."<td><input type=\"text\" name=\"s\" id=\"search_text\" value=\"".addslashes(urldecode($search_terms))."\" size=\"20\" onKeyPress=\"return checkSubmit(event)\"/></td>\n"
		."<td><button id='search_submit_button' onClick=\"document.location.href = '/explore/search/' + $('#search_text').val();\">Go</button></td>\n"
		."</tr></table>";
	
	$html .= "<script>\n"
		."$(\"#search_text\").focus();\n"
		."</script>\n";
	
	if ($search_terms != ""){
	
		$mysqli = db_connect();

		$sql = "";
		$count_sql = "";

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
				." AND MATCH(Posts.Title, Posts.Body) AGAINST ('".$mysqli->real_escape_string($search_terms)."')"
				." ORDER BY MATCH(Posts.Title, Posts.Body) AGAINST ('".$mysqli->real_escape_string($search_terms)."') DESC LIMIT ".$mysqli->real_escape_string($start).",20";
				
			$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId"
				." WHERE"
				." ((FriendsA.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
				." OR"
				." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
				." OR"
				." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." AND MATCH(Posts.Title, Posts.Body) AGAINST ('".$mysqli->real_escape_string($search_terms)."')";

		} else {

			$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar, null AS LikeId FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." WHERE"
				." Posts.Privacy=".POST_PRIVACY_PUBLIC
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." AND MATCH(Posts.Title, Posts.Body) AGAINST ('".$mysqli->real_escape_string($search_terms)."')"
				." ORDER BY MATCH(Posts.Title, Posts.Body) AGAINST ('".$mysqli->real_escape_string($search_terms)."') DESC LIMIT ".$mysqli->real_escape_string($start).",20";

			$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." WHERE"
				." Posts.Privacy=".POST_PRIVACY_PUBLIC
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." AND MATCH(Posts.Title, Posts.Body) AGAINST ('".$mysqli->real_escape_string($search_terms)."')";
		}

		// fetch count for pagination
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumPosts"];

		$post_result = $mysqli->query($sql);

		$html .= "<p>".$count." posts found with '".$search_terms."' in the title, or body...</p>\n"
			."</div> <!-- #header -->\n";
		
		$html .= render_posts($mysqli,$post_result);

		// Pagination
		$html .= render_pagination("explore/search/".$search_terms,$page,$count,20);

		$html .= render_display_controls();
	} else {
		$html .= "</div> <!-- #header -->\n";
	}

	$html .= render_footer();

	return $html;

}

?>
