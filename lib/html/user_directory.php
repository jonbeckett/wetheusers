<?php

function render_user($user_row){

	$html = "<div class=\"directory_user\">\n";

	$avatar_image = ($user_row["Avatar"] != "") ? $user_row["Avatar"] : "avatars/generic_64.jpg";

	$html .= "<div class=\"karma\"><a title=\"This is ".$user_row["Username"]."'s Karma\">".$user_row["TotalCount"]."</a></div>\n"
		."<div class=\"avatar\"><a href=\"/".$user_row["Username"]."\" title=\"".$user_row["Username"]."\"><img src=\"/".$avatar_image."\" width=\"64\" height=\"64\" alt=\"".$user_row["Username"]."\" border=\"0\" /></a></div>\n"
		."<div class=\"username\">\n"
			."<h3><a href=\"/".$user_row["Username"]."\" title=\"".$user_row["Username"]."\">".$user_row["Username"]."</a></h3>\n"
			."<ul class=\"counts\">\n"
				."<li>".$user_row["PostCount"]." public posts</li>"
				."<li>".$user_row["CommentCount"]." comments received</li>"
				."<li>".$user_row["LikesCount"]." likes received</li>"
			."</ul>\n"
		."</div> <!-- .username -->\n"
		."<div class=\"clear\"></div>\n";

	if ($user_row["Bio"] != "") $html .= "<div class='bio'>".Markdown($user_row["Bio"])."</div>\n";

	$html .= "<ul class='social_networks'>\n";
		if ($user_row["Twitter"] != "") $html .= "<li><a href=\"http://twitter.com/".$user_row["Twitter"]."\" title=\"Twitter\"><img src=\"/img/twitter.png\" width=\"16\" height=\"16\" alt=\"Twitter\" border=\"0\" /></a></li>\n";
		if ($user_row["Tumblr"] != "") $html .= "<li><a href=\"http://".$user_row["Tumblr"].".tumblr.com\" title=\"Tumblr\"><img src=\"/img/tumblr.png\" width=\"16\" height=\"16\" alt=\"Tumblr\" border=\"0\" /></a></li>\n";
		if ($user_row["Facebook"] != "") $html .= "<li><a href=\"http://facebook.com/".$user_row["Facebook"]."\" title=\"Facebook\"><img src=\"/img/facebook.png\" width=\"16\" height=\"16\" alt=\"Facebook\" border=\"0\" /></a></li>\n";
		if ($user_row["GooglePlus"] != "") $html .= "<li><a href=\"http://plus.google.com/".$user_row["GooglePlus"]."/about\" title=\"Google+\"><img src=\"/img/googleplus.png\" width=\"16\" height=\"16\" alt=\"Google Plus\" border=\"0\" /></a></li>\n";
		if ($user_row["LiveJournal"] != "") $html .= "<li><a href=\"http://".$user_row["LiveJournal"].".livejournal.com\" title=\"LiveJournal\"><img src=\"/img/livejournal.png\" width=\"16\" height=\"16\" alt=\"LiveJournal\" border=\"0\" /></a></li>\n";
		$html .= "<li><a href=\"http://wetheusers.net/".$user_row["Username"]."/rss\" title=\"RSS\"><img src=\"/img/feed.png\" width=\"16\" height=\"16\" alt=\"Twitter\" border=\"0\" /></a></li>\n";
	$html .= "</ul><!-- .social_networks -->\n";
	
	$html .= "<ul class='links'>\n"
		.(($user_row["ShowFriends"]==1) ? "<li><a href=\"/".$user_row["Username"]."/friends\" title=\"Friends\">Friends</a></li>\n" : "")
		.(($user_row["ShowFriendOf"]==1) ? "<li><a href=\"/".$user_row["Username"]."/followers\" title=\"Followers\">Followers</a></li>\n" : "")
		."</ul> <!-- .links -->\n";
	
	$html .= "<div class='clear'></div>\n";
	
	$html .= "</div> <!-- .user -->\n";

	return $html;

}

function render_user_directory($tag_name="",$page=1){

	$start = (intval($page) - 1) * 20;

	$html = render_header("User Directory");

	$mysqli = db_connect();

	// check if a tag is passed in
	if ($tag_name == ""){

		// No tag - draw the tags
		$sql = "SELECT Tags.Name AS TagName, COUNT(Tags.Id) AS TagCount"
			." FROM Tags"
			." INNER JOIN UserTags ON Tags.Id=UserTags.TagId"
			." INNER JOIN Users ON UserTags.UserId=Users.Id"
			." GROUP BY Tags.Name"
			." ORDER BY Tags.Name";
			// ." HAVING COUNT(Tags.Id)>1" - goes above ORDER BY

		$html .= "<div id=\"header\">\n"
			."<h1>User Directory</h1>\n"
			."<p>Explore the tags users have filed themselves under - edit your <a href=\"/account\">account</a> details to file yourself under some tags.</p>\n"
			."</div>\n";


		$tags_result_a = $mysqli->query($sql);
		$tags_result_b = $mysqli->query($sql);

		// find the most tags to do sizing
		$max_tags = 0;
		while ($tags_row =@ $tags_result_a->fetch_assoc()){
			if (intval($tags_row["TagCount"]) > $max_tags) $max_tags = intval($tags_row["TagCount"]);
		}

		$range = 2;

		$html .= "<div id='tags_page'>\n"
			."<div class=\"tags\">\n";

		while ($tags_row =@ $tags_result_b->fetch_assoc()){

			// math to work out size of font
			$tag_count = $tags_row["TagCount"];
			$ratio = $tag_count / $max_tags;
			$size = number_format((1 + ($ratio * $range)),1);

			$html .= "<div class='tag' style='font-size:".$size."em !important;'><a title='".addslashes($tags_row["TagName"])."' href='/directory/".htmlspecialchars($tags_row["TagName"])."'>".str_replace(" ","&nbsp;",$tags_row["TagName"])."</a><br /><small>".$tags_row["TagCount"]." users</small></div>\n";
		}
		$html .= "<div class='clear'></div>\n"
			."</div> <!-- .tags -->\n"
			."</div> <!-- #tags_page -->\n";

	} else {

		$html .= "<div id=\"header\">\n"
			."<h1>User Directory : &#8216;<span>".$tag_name."</span>&#8217;</h1>\n"
			."<p>Here are the users that have filed themselves under the tag '".$tag_name."'</p>\n"
			."</div>\n";

		// get all the users with a particular tag
		$sql = "SELECT Users.*, COUNT(DISTINCT Posts.Id) AS PostCount, COUNT(DISTINCT Comments.Id) AS CommentCount, COUNT(DISTINCT Likes.Id) AS LikesCount,COUNT(DISTINCT Posts.Id) + COUNT(DISTINCT Comments.Id) + COUNT(DISTINCT Likes.Id) AS TotalCount\n"
			." FROM Users"
			." INNER JOIN UserTags ON Users.Id=UserTags.UserId"
			." INNER JOIN Tags ON Tags.Id=UserTags.TagId"
			." LEFT OUTER JOIN Posts ON Posts.UserId=Users.Id AND Posts.Status=1 AND Posts.Privacy=0"
			." LEFT OUTER JOIN Comments ON Posts.Id=Comments.PostId AND Comments.UserId<>Users.Id"
			." LEFT OUTER JOIN Likes ON Posts.Id=Likes.PostId AND Likes.UserId<>Users.Id"
			." WHERE Tags.Name='".$mysqli->real_escape_string($tag_name)."'"
			." GROUP BY Users.Id"
			." ORDER BY TotalCount DESC"
			." LIMIT ".$mysqli->real_escape_string($start).",20";


		$sql_count = "SELECT COUNT(DISTINCT Users.Id) AS NumUsers"
			." FROM Users"
			." INNER JOIN UserTags ON Users.Id=UserTags.UserId"
			." INNER JOIN Tags ON Tags.Id=UserTags.TagId"
			." INNER JOIN Posts ON Posts.UserId=Users.Id AND Posts.Status=1 AND Posts.Privacy=0"
			." LEFT OUTER JOIN Comments ON Posts.Id=Comments.PostId"
			." LEFT OUTER JOIN Likes ON Posts.Id=Likes.PostId"
			." WHERE Tags.Name='".$mysqli->real_escape_string($tag_name)."'"
			." GROUP BY Users.Id";

		$user_result = $mysqli->query($sql);

		if ($user_result->num_rows > 0){
			$html .= "<div class=\"directory_users\">\n";
			while ($user_row =@ $user_result->fetch_assoc()){
				$html .= render_user($user_row);
			}
			$html .= "</div>\n";
		} else {
			$html .= "<p>There are no users filed under the tag '".$tag_name."'</p>\n";
		}

		// fetch count for pagination
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumUsers"];

		$html .= render_pagination("user_directory/".$tag_name,$page,$count,20);
	}

	$html .= render_footer();

	return $html;
}
?>
