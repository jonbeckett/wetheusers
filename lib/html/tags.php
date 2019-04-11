<?php


function render_tags_page(){

	$html = render_header("Tags");

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

	if (isset($_SESSION["user_id"])){
	
		$sql = "SELECT Tags.Name AS TagName, COUNT(Tags.Id) AS TagCount FROM Tags"
			." INNER JOIN PostTags ON Tags.Id=PostTags.TagId"
			." INNER JOIN Posts ON PostTags.PostId=Posts.Id"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsOfAuthor ON Posts.UserId=FriendsOfAuthor.UserId AND FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." WHERE ((FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '7' DAY))"
			." GROUP BY Tags.Name"
			." ORDER BY Tags.Name";
	
	} else {

		$sql = "SELECT Tags.Name AS TagName, COUNT(Tags.Id) AS TagCount FROM Tags"
			." INNER JOIN PostTags ON Tags.Id=PostTags.TagId"
			." INNER JOIN Posts ON PostTags.PostId=Posts.Id"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." WHERE Posts.Privacy=".POST_PRIVACY_PUBLIC
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '7' DAY))"
			." GROUP BY Tags.Name"
			." ORDER BY Tags.Name";

	}

	$tags_result_a = $mysqli->query($sql);
	$tags_result_b = $mysqli->query($sql);

	$html .= "<div id=\"header\">\n"
		."<h1>Tags</h1>\n"
		."<p>Explore the tags of posts from the last 7 days.</p>\n"
		."</div>\n";

	// find the highest number of tags

	$max_tags = 0;
	while ($tags_row =@ $tags_result_a->fetch_assoc()){
		if (intval($tags_row["TagCount"]) > $max_tags) $max_tags = intval($tags_row["TagCount"]);
	}

	$range = 2;

	$html .= "<div id='tags_page'>\n"
		."<div class=\"tags\">\n";
	while ($tags_row =@ $tags_result_b->fetch_assoc()){
		$tag_count = $tags_row["TagCount"];
		$ratio = $tag_count / $max_tags;
		$size = number_format((1 + ($ratio * $range)),1);
		$html .= "<div class='tag' style='font-size:".$size."em !important;'><a title='".addslashes($tags_row["TagName"])."' href='/explore/tag/".$tags_row["TagName"]."'>".str_replace(" ","&nbsp;",$tags_row["TagName"])."</a><br /><small>".$tags_row["TagCount"]." posts</small></div>\n";
	}
	$html .= "<div class='clear'></div>\n"
		."</div> <!-- .tags -->\n"
		."</div> <!-- #tags_page -->\n";


	$html .= render_footer();

	return $html;

}

?>
