<?php

function render_user_rss($username){

	$mysqli = db_connect();

	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
		."<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n"
		."<channel>\n"
		."<title>".$username." - ".SITE_NAME."</title>\n"
		."<link>http://wetheusers.net/".$username."</link>\n"
		."<atom:link href=\"http://wetheusers.net/".$username."/rss\" rel=\"self\" type=\"application/rss+xml\" />\n"
		."<description>The public posts by ".$username." at wetheusers.net</description>\n"
		."<lastBuildDate>".date("r")."</lastBuildDate>\n"
		."<language>en-gb</language>\n";

	$sql = "SELECT Posts.*,DATE_FORMAT(Posts.Created, '%a, %d %b %Y %T') AS RssPubDate, Users.Username,Users.Avatar FROM Posts"
		." INNER JOIN Users ON Posts.UserId=Users.Id"
		." WHERE Posts.Status=".$mysqli->real_escape_string(POST_STATUS_PUBLISHED)
		." AND Posts.Privacy=".$mysqli->real_escape_string(POST_PRIVACY_PUBLIC)
		." AND Users.Username='".$mysqli->real_escape_string($username)."'"
		." ORDER BY Posts.Created DESC LIMIT 20";

	$posts_result = $mysqli->query($sql);

	while ($post_row =@ $posts_result->fetch_assoc()){

		$rss_pub_date = $post_row["RssPubDate"]." GMT";

		$img_html = ($post_row["Photo"] != "") ? "<p><img src=\"http://wetheusers.net/".$post_row["Photo"]."\" /></p>\n" : "";

		$xml .= "<item>\n"
			."<title>".strip_tags($post_row["Title"])."</title>\n"
			."<link>http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."</link>\n"
			."<guid>http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."</guid>\n"
			."<pubDate>".$rss_pub_date."</pubDate>\n"
			."<description><![CDATA[".$img_html.Markdown($post_row["Body"])."]]></description>\n"
			."</item>\n";

	}


	// end the feed
	$xml .= "</channel>\n"
		."</rss>\n";


	return $xml;
}


function render_tag_rss($tag){

	$mysqli = db_connect();

	$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n"
		."<rss version=\"2.0\" xmlns:atom=\"http://www.w3.org/2005/Atom\">\n"
		."<channel>\n"
		."<title>".$tag." - ".SITE_NAME."</title>\n"
		."<atom:link href=\"http://wetheusers.net/tag/".$tag."/rss\" rel=\"self\" type=\"application/rss+xml\" />\n"
		."<link>http://wetheusers.net/tag/".$tag."</link>\n"
		."<description>The public posts tagged '".$tag."' at wetheusers.net</description>\n"
		."<lastBuildDate>".date("r")."</lastBuildDate>\n"
		."<language>en-gb</language>\n";

		$sql = "SELECT DISTINCT Posts.*,DATE_FORMAT(Posts.Created, '%a, %d %b %Y %T') AS RssPubDate, Users.Username,Users.Avatar,null AS LikeId FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." INNER JOIN PostTags ON Posts.Id=PostTags.PostId"
			." INNER JOIN Tags ON PostTags.TagId=Tags.Id"
			." WHERE Posts.Privacy=".POST_PRIVACY_PUBLIC
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Tags.Name='".$mysqli->real_escape_string($tag)."'"
			." ORDER BY Created DESC LIMIT 20";

	$posts_result = $mysqli->query($sql);

	while ($post_row =@ $posts_result->fetch_assoc()){

		$rss_pub_date = $post_row["RssPubDate"]." GMT";

		$img_html = ($post_row["Photo"] != "") ? "<p><img src=\"http://wetheusers.net/".$post_row["Photo"]."\" /></p>\n" : "";

		$xml .= "<item>\n"
			."<title>".strip_tags($post_row["Title"])."</title>\n"
			."<link>http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."</link>\n"
			."<guid>http://wetheusers.net/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."</guid>\n"
			."<pubDate>".$rss_pub_date."</pubDate>\n"
			."<description><![CDATA[".$img_html.Markdown($post_row["Body"])."]]></description>\n"
			."</item>\n";

	}


	// end the feed
	$xml .= "</channel>\n"
		."</rss>\n";


	return $xml;
}
?>
