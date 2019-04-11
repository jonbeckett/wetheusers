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
				."<li>".$user_row["LikesCount"]." likes received on posts</li>"
			."</ul>\n"
		."</div> <!-- .username -->\n"
		."<div class=\"clear\"></div>\n";

	if ($user_row["Bio"] != "") $html .= "<div class='bio'>".Markdown($user_row["Bio"])."</div>\n";

	$html .= "<ul class='social_networks'>\n";
		if ($user_row["Twitter"] != "") $html .= "<li><a href=\"http://twitter.com/".$user_row["Twitter"]."\" title=\"Twitter\"><img src=\"/img/twitter.png\" width=\"16\" height=\"16\" alt=\"Twitter\" border=\"0\" /></a></li>\n";
		if ($user_row["Tumblr"] != "") $html .= "<li><a href=\"http://".$user_row["Tumblr"].".tumblr.com\" title=\"Tumblr\"><img src=\"/img/tumblr.png\" width=\"16\" height=\"16\" alt=\"Tumblr\" border=\"0\" /></a></li>\n";
		if ($user_row["Wordpress"] != "") $html .= "<li><a href=\"".$user_row["Wordpress"]."\" title=\"Wordpress\"><img src=\"/img/wordpress.png\" width=\"16\" height=\"16\" alt=\"Wordpress\" border=\"0\" /></a></li>\n";
		if ($user_row["Facebook"] != "") $html .= "<li><a href=\"http://facebook.com/".$user_row["Facebook"]."\" title=\"Facebook\"><img src=\"/img/facebook.png\" width=\"16\" height=\"16\" alt=\"Facebook\" border=\"0\" /></a></li>\n";
		if ($user_row["GooglePlus"] != "") $html .= "<li><a href=\"http://plus.google.com/".$user_row["GooglePlus"]."/about\" title=\"Google+\"><img src=\"/img/googleplus.png\" width=\"16\" height=\"16\" alt=\"Google Plus\" border=\"0\" /></a></li>\n";
		if ($user_row["LiveJournal"] != "") $html .= "<li><a href=\"http://".$user_row["LiveJournal"].".livejournal.com\" title=\"LiveJournal\"><img src=\"/img/livejournal.png\" width=\"16\" height=\"16\" alt=\"LiveJournal\" border=\"0\" /></a></li>\n";
		$html .= "<li><a href=\"http://wetheusers.net/".$user_row["Username"]."/rss\" title=\"RSS\"><img src=\"/img/feed.png\" width=\"16\" height=\"16\" alt=\"Twitter\" border=\"0\" /></a></li>\n";
	$html .= "</ul><!-- .social_networks -->\n";

	$html .= "</div> <!-- .user -->\n";

	return $html;

}

function render_suggested_users($days=7,$page=1){

	$start = (intval($page) - 1) * 20;

	$html = render_header("Suggested Users");

	$html .= "<div class=\"bg_menu_wrapper\">\n"
		."<ul class=\"bg_menu\">\n"
		."<li><a href=\"/explore/firehose\" title=\"Firehose\">Firehose</a></li>\n"
		."<li><a href=\"/explore/popular\" title=\"Popular\">Popular</a></li>\n"
		."<li><a href=\"/explore/tags\" title=\"Tags\">Tags</a></li>\n"
		."<li><a href=\"/explore/directory\" title=\"Directory\">Directory</a></li>\n"
		."<li class=\"selected\"><a href=\"/explore/suggested\" title=\"Suggested Users\">Suggested</a></li>\n"
		."<li><a href=\"/explore/search\" title=\"Search\">Search</a></li>\n"
		."</ul>\n"
		."<div class=\"clear\"></div>\n"
		."</div>\n";
		
	$mysqli = db_connect();

	$html .= "<div id=\"header\">\n"
		."<h1>Suggested Users</h1>\n"
		."<p>Users with the most popular public content over the last ".$days." days.</p>\n"
		."</div>\n";

	$sql = "SELECT Users.*, COUNT(DISTINCT Posts.Id) AS PostCount, COUNT(DISTINCT Comments.Id) AS CommentCount, COUNT(DISTINCT Likes.Id) AS LikesCount, COUNT(DISTINCT Posts.Id) + COUNT(DISTINCT Comments.Id) + COUNT(DISTINCT Likes.Id) AS TotalCount\n"
		." FROM Users"
		." INNER JOIN Posts ON Posts.UserId=Users.Id AND Posts.Status=1 AND Posts.Privacy=0"
		." LEFT OUTER JOIN Comments ON Posts.Id=Comments.PostId AND Comments.UserId<>Users.Id"
		." LEFT OUTER JOIN Likes ON Posts.Id=Likes.PostId AND Likes.UserId<>Users.Id"
		." WHERE Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '".$mysqli->real_escape_string($days)."' DAY)"
		." GROUP BY Users.Id"
		." ORDER BY TotalCount DESC"
		." LIMIT ".$mysqli->real_escape_string($start).",20";

	$sql_count = "SELECT COUNT(DISTINCT Users.Id) AS NumUsers"
		." FROM Users"
		." INNER JOIN Posts ON Posts.UserId=Users.Id AND Posts.Status=1 AND Posts.Privacy=0"
		." WHERE (Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '".$mysqli->real_escape_string($days)."' DAY))";

	$user_result = $mysqli->query($sql);

	if ($user_result->num_rows > 0){
		$html .= "<div class=\"directory_users\">\n";
		while ($user_row =@ $user_result->fetch_assoc()){
			$html .= render_user($user_row);
		}
		$html .= "<div class=\"clear\"></div>\n"
			."</div>\n";
	}

	// fetch count for pagination
	$count_result = $mysqli->query($sql_count);
	$count_row = $count_result->fetch_assoc();
	$count = $count_row["NumUsers"];

	$html .= render_pagination("explore/suggested/".$days,$page,$count,20);
	
	$html .= render_footer();

	return $html;
}
?>
