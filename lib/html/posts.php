<?php

function render_posts($mysqli,$post_result){

	$html = "<div class=\"".(($_SESSION["display_mode"] == DISPLAY_MODE_TILE) ? "tiles" : "posts")."\">\n";
	while ($post_row =@ $post_result->fetch_assoc()){
		if ($_SESSION["display_mode"] == DISPLAY_MODE_TILE){
			$html .= render_tile($mysqli,$post_row,false);
		} else {
			$html .= render_post($mysqli,$post_row,false);
		}
	}
	$html .= "</div> <!-- .tiles -->\n";

	return $html;
}

function render_post($mysqli,$post_row, $display_comments){

	global $CSS;
	
	$privacy_class = ($post_row["Privacy"] == POST_PRIVACY_FRIENDS_ONLY) ? "friends_only" : "";

	$html = "";

	// get CSS
	if (isset($post_row["CSS"])){
		$html .= "<style>\n".$post_row["CSS"]."</style>\n";
	}

	$html .= "<div class=\"post_wrapper ".$privacy_class."\">\n";

	// photo
	if ($post_row["Photo"] != ""){
	
		//determine dimensions
		$width = intval($post_row["PhotoWidth"]);
		$height = intval($post_row["PhotoHeight"]);
		
		// make it smaller
		//$ratio = 280/500; // we want to end up at 280 width
		//$width = round($width * $ratio);
		//$height = round($height * $ratio);
		
		$html .= "<div class='photo'><img src=\"/".$post_row["Photo"]."\" width=\"".$width."\" height=\"".$height."\" alt=\"".$post_row["Title"]."\" /></div>\n";
	}

	$html .= "<div class=\"post\">\n";

	
	// title
	if ($post_row["Title"] != ""){
		$html .= "<h2><a href=\"/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."\" title=\"".htmlspecialchars($post_row["Title"])."\">".htmlspecialchars($post_row["Title"])."</a></h2>\n";
	}

	// look for twitter style name references in the body
	$pattern = "/@([a-zA-Z0-9_]+)/";
	$replace = "[@$1](http://wetheusers.net/$1)";
	$body = preg_replace($pattern,$replace,$post_row["Body"]);
	
	// look for hashtags
	$pattern = "/#([a-zA-Z0-9_]+)/";
	$replace = "[#$1](http://wetheusers.net/explore/tag/$1)";
	$body = preg_replace($pattern,$replace,$body);
	
	$html .= "<div class=\"body\">\n"
		.Markdown($body)
		."</div> <!-- .body -->\n"
		."</div> <!-- .post -->\n";

	$html .= "<div class=\"meta\">\n";

	$avatar_image = ($post_row["Avatar"] != "") ? $post_row["Avatar"] : "avatars/generic_64.jpg";

	$like_button = "";
	if (isset($_SESSION["user_id"])){
		if ($post_row["UserId"] != $_SESSION["user_id"]){
			$liked = ($post_row["LikeId"] != null) ? "liked" : "";
			$like_button = "<div title=\"Click to like or unlike\" class=\"like_button ".$liked."\" post_id=\"".$post_row["Id"]."\" post_like_count_id=\"like_count_".$post_row["Id"]."\">&nbsp;</div>\n";
		}
	}

	$html .= "<div class='avatar'><a href=\"/".$post_row["Username"]."\" title=\"".$post_row["Username"]."\"><img src='/".$avatar_image."' alt=\"".$post_row["Username"]."\" width=\"64\" height=\"64\" border=\"0\" /></a></div>"
		."<div class='info'>\n"
		."<div class='username'><h3><a href=\"/".$post_row["Username"]."\" title=\"/".$post_row["Username"]."\">".$post_row["Username"]."</a></h3></div>\n";

	// render the controls
	$html .= "<ul class='controls'>\n"
		."<li><a href=\"/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."\" title=\"".$post_row["Title"]."\">".time_ago($post_row["Created"])."</a></li>\n"
		."<li><a href=\"/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."/#comments\" title=\"".$post_row["Title"]."\">".$post_row["Comments"]." comments</a></li>\n"
		."<li><span id=\"post_like_count_".$post_row["Id"]."\">".$post_row["Likes"]."</span> likes</li>\n";

	if (isset($_SESSION["user_id"])){
		if ($post_row["UserId"] == $_SESSION["user_id"]){
			$html .= "<li><a href='/post/edit/".$post_row["Id"]."'>Edit</a></li>\n"
				."<li><a href='/api/post/delete/".$post_row["Id"]."' onclick=\"return confirm('Are you sure?');\">Delete</a></li>\n";
		}
	}

	$html .= "</ul>\n";

	// render the tags
	$tags_result = $mysqli->query("SELECT PostTags.TagId,Tags.Name FROM PostTags"
			." INNER JOIN Tags ON PostTags.TagId=Tags.Id"
			." WHERE PostTags.PostId=".$mysqli->real_escape_string($post_row["Id"])
			." ORDER BY Tags.Name");

	if ($tags_result->num_rows > 0){
		$html .= "<ul class='tags'>\n";
		while ($tag_row =@ $tags_result->fetch_assoc()){
			$html .= "<li class='tag'><a href='/explore/tag/".$tag_row["Name"]."'>#".htmlspecialchars($tag_row["Name"])."</a></li>\n";
		}
		$html .= "</ul>\n";
	}


	// render the likes
	$sql = "SELECT Users.* FROM Likes"
		." INNER JOIN Users ON Likes.UserId=Users.Id"
		." WHERE PostId=".$mysqli->real_escape_string($post_row["Id"])
		." ORDER BY Users.Username";

	$likes_result = $mysqli->query($sql);

	if ($likes_result->num_rows > 0){
		$html .= "<a name=\"likes\"></a><ul class='likes'><li>Liked by</li>\n";
		while ($like_row =@ $likes_result->fetch_assoc()){
			$html .= "<li><a href=\"/".$like_row["Username"]."\">".$like_row["Username"]."</a></li>\n";
		}
		$html .= "</ul> <!-- .likes -->\n";
	}


	$html .= "</div> <!-- .info -->\n"
		.$like_button;

	$html .= "<div class=\"clear\"></div>\n"
		."</div> <!-- .meta -->\n";

	if ($display_comments){
		

		
		// output the comments
		if (isset($_SESSION["user_id"])){
			$sql = "SELECT Comments.*,Users.Username,Users.Avatar, CommentLikes.Id AS LikeId"
				." FROM Comments"
				." INNER JOIN Users ON Comments.UserId=Users.Id"
				." LEFT OUTER JOIN CommentLikes ON CommentLikes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND CommentLikes.CommentId=Comments.Id"
				." WHERE Comments.PostId=".$mysqli->real_escape_string($post_row["Id"])
				." ORDER BY Comments.Created";
			
		} else {
			$sql = "SELECT Comments.*,Users.Username,Users.Avatar"
				." FROM Comments"
				." INNER JOIN Users ON Comments.UserId=Users.Id"
				." WHERE Comments.PostId=".$mysqli->real_escape_string($post_row["Id"])
				." ORDER BY Comments.Created";
		}
		

		$comments_result = $mysqli->query($sql);

		if ($comments_result->num_rows > 0){
			$html .= "<a name=\"comments\"></a><div class=\"comments\">\n";
			while ($comment_row =@ $comments_result->fetch_assoc()){

				// if its our post, show the delete comment button
				$delete_link = "";
				if (isset($_SESSION["user_id"])){
					if ($post_row["UserId"] == $_SESSION["user_id"]) $delete_link = "(<a href=\"/api/comment/delete/".$comment_row["Id"]."\" onclick=\"return confirm('Are you sure?');\">Delete</a>)";
				}

				$avatar_image = ($comment_row["Avatar"] != "") ? $comment_row["Avatar"] : "avatars/generic_64.jpg";
				
				// look for twitter style name references in the body
				$pattern = "/@([a-zA-Z0-9_]+)/";
				$replace = "[@$1](http://wetheusers.net/$1)";
				$comment_body = preg_replace($pattern,$replace,$comment_row["Body"]);

				// prepare the comment like button
				$comment_like_button = "";
				if (isset($_SESSION["user_id"])){
					if ($comment_row["UserId"] != $_SESSION["user_id"]){
						$comment_liked = ($comment_row["LikeId"] != null) ? "comment_liked" : "";
						$comment_like_button = "<div title=\"Click to like or unlike\" class=\"comment_like_button ".$comment_liked."\" post_id=\"".$post_row["Id"]."\" comment_id=\"".$comment_row["Id"]."\" comment_like_count_id=\"comment_like_count_".$comment_row["Id"]."\" likeid=\"".$comment_row["LikeId"]."\">&nbsp;</div>\n";
					}
				}
				
				$html .= "<div class=\"comment\">"
					."<div class=\"avatar\"><a href=\"/".$comment_row["Username"]."\" title=\"".$comment_row["Username"]."\"><img src=\"/".$avatar_image."\" width=\"32\" height=\"32\" border=\"0\" alt=\"".$comment_row["Username"]."\" /></a></div>\n"
					."<div class=\"body\">\n"
					.$comment_like_button
					.Markdown($comment_body)
					."<div class=\"comment_meta\">".time_ago($comment_row["Created"])." by <a href=\"/".$comment_row["Username"]."\">".$comment_row["Username"]."</a> - <span id=\"comment_like_count_".$comment_row["Id"]."\">".$comment_row["Likes"]."</span> likes ".$delete_link."</div>\n"
					."</div><!-- .body -->\n"
					."<div class='clear'></div>\n"
					."</div><!-- .comment -->\n";
			}
			$html .= "</div> <!-- .comments -->\n";
		}
		if (isset($_SESSION["user_id"])){
			$html .= "<div class=\"comment_form\">\n"
				."<h3>New Comment</h3>\n"
				."<form method=\"POST\" action=\"/api/comment/add\">\n"
				."<input type=\"hidden\" name=\"post_id\" value=\"".$post_row["Id"]."\">\n"
				."<div class=\"body\"><textarea name=\"body\" id=\"body\"></textarea></div>\n"
				."<div class=\"controls\"><input type=\"submit\" value=\"Save\" /></div>\n"
				."</form>\n"
				."</div> <!-- .comment_form -->\n"
				."<script>if (window.location.hash == \"#comments\") $(\"#body\").focus();</script>\n";
				
		} else {
			$html .= "<div class=\"comment_form\">\n"
				."<p class=\"center\">You need to <a href=\"/login\">login</a> or <a href=\"/register\">register</a> to like posts, or post comments.</p>\n"
				."</div>\n";
		}
		
		

	} // display comments

	$html .= "</div> <!-- .post_wrapper -->\n\n"
		."<div class=\"clear\"></div>\n\n";

	return $html;
}


function render_tile($mysqli,$post_row,$display_comments){

	$privacy_class = ($post_row["Privacy"] == POST_PRIVACY_FRIENDS_ONLY) ? "friends_only" : "";

	$html = "<div class=\"tile ".$privacy_class."\">\n";
	
	if ($post_row["Photo"] != ""){
		
		$target_width = 320;
		
		//determine dimensions
		$width = intval($post_row["PhotoWidth"]);
		$height = intval($post_row["PhotoHeight"]);
		
		// make it smaller
		$ratio = $target_width/$width; // we want to end up at 280 width
		$width = round($width * $ratio);
		$height = round($height * $ratio);
		
		$html .= "<div class='photo'><a href=\"/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."\" title=\"".$post_row["Title"]."\"><img src=\"/".$post_row["Photo"]."\" width=\"".$width."\" height=\"".$height."\" alt=\"".$post_row["Title"]."\" /></a></div>\n";
	}
	
	$html .= "<div class=\"post\">\n";

	


	// render the post content
	$html .= "<h2><a href=\"/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."\" title=\"".htmlspecialchars($post_row["Title"])."\">".$post_row["Title"]."</a></h2>\n";

	// look for twitter style name references in the body
	$pattern = "/@([a-zA-Z0-9_]+)/";
	$replace = "[@$1](http://wetheusers.net/$1)";
	$body = preg_replace($pattern,$replace,$post_row["Body"]);

	// look for hashtags
	$pattern = "/#([a-zA-Z0-9_]+)/";
	$replace = "[#$1](http://wetheusers.net/explore/tag/$1)";
	$body = preg_replace($pattern,$replace,$body);
	
	$html .= Markdown($body);

	// if we have no avatar, substitute with a generic image
	$avatar_image = ($post_row["Avatar"] != "") ? $post_row["Avatar"] : "avatars/generic_64.jpg";

	$like_button = "";
	if (isset($_SESSION["user_id"])){
		if ($post_row["UserId"] != $_SESSION["user_id"]){
			$liked = ($post_row["LikeId"] != null) ? "liked" : "";
			$like_button = "<div title=\"Click to like or unlike\" class=\"like_button ".$liked."\" post_id=\"".$post_row["Id"]."\" like_count_id=\"like_count_".$post_row["Id"]."\">&nbsp;</div>\n";
		}
	}

	// render the meta data
	$html .= "</div> <!-- .post -->\n"
		."<div class=\"meta\">\n"
		."<div class=\"avatar\"><a href=\"/".$post_row["Username"]."\" title=\"".$post_row["Username"]."\"><img src=\"/".$avatar_image."\" width=\"32\" height=\"32\" alt=\"".$post_row["Username"]."\" /></a></div>\n"
		."<div class=\"info\"><a href=\"/".$post_row["Username"]."\" title=\"".$post_row["Username"]."\">".$post_row["Username"]."</a>\n"
		." <ul class='controls'>\n"
		."  <li><a href=\"/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."\">".time_ago($post_row["Created"])."</a></li>\n"
		."  <li><a href=\"/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."/#comments\">".$post_row["Comments"]." comments</a></li>\n"
		."  <li><a href=\"/post/".$post_row["Id"]."/".toAscii($post_row["Title"])."/#likes\"><span id=\"like_count_".$post_row["Id"]."\">".$post_row["Likes"]."</span> likes</a></li>\n"
		." </ul>\n"
		."</div> <!-- .info -->\n"
		.$like_button
		."<div class=\"clear\"></div>\n"
		."</div> <!-- .meta -->\n"
		."</div> <!-- .tile -->\n";

	return $html;
}


function render_post_page($post_id){

	$mysqli = db_connect();

	$sql = "";
	if (isset($_SESSION["user_id"])){
	
		$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar,Likes.Id AS LikeId, Users.CSS"
			." FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Likes ON Likes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Likes.PostId=Posts.Id"
			." LEFT OUTER JOIN Friends FriendsOfAuthor ON Posts.UserId=FriendsOfAuthor.UserId AND FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." WHERE"
			." ((FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY." AND Posts.Status=".POST_STATUS_PUBLISHED.")"
			." OR"
			." (Posts.Privacy=".POST_PRIVACY_PUBLIC." AND Posts.Status=".POST_STATUS_PUBLISHED.")"
			." OR"
			." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
			." AND Posts.Id='".$mysqli->real_escape_string($post_id)."'";
			
	} else {
	
		$sql = "SELECT Posts.*,Users.Username,Users.Avatar, Users.CSS FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." WHERE"
			." Posts.Privacy=".POST_PRIVACY_PUBLIC
			." AND Posts.Status=".POST_STATUS_PUBLISHED
			." AND Posts.Id='".$mysqli->real_escape_string($post_id)."'";
	}

	$post_result = $mysqli->query($sql);

	// print "<br /><br /><code>".$sql."</code>";
	
	if ($post_result->num_rows > 0){

		$post_row =@ $post_result->fetch_assoc();

		$body_excerpt = htmlspecialchars((strlen($post_row["Body"])>140) ? substr($post_row["Body"],0,140) : $post_row["Body"]);

		$html = render_header($post_row["Title"],$body_excerpt);

		$html .= "<div class='posts'>\n";

		$html .= render_post($mysqli,$post_row,true);

		$html .= "</div> <!-- .posts -->\n";

		$html .= render_footer();

		return $html;
	} else {
		header("Location: /404/".$post_id);
	}
}







function render_post_add_page(){

	if (isset($_SESSION["user_id"])){

		$html = render_header("New Post");

		// fetch the user row from the database to get the defaults from it
		$mysqli = db_connect();
		$user_result = $mysqli->query("SELECT * FROM Users WHERE Id=".$mysqli->real_escape_string($_SESSION["user_id"]));
		$user_row = $user_result->fetch_assoc();

		$html .= "<div class=\"bg_menu_wrapper\">\n"
				."<ul class=\"bg_menu\">\n"
				."<li class=\"selected\"><a href=\"/post/add\" title=\"Compose\">Compose</a></li>\n"
				."<li><a href=\"/post/drafts\" title=\"Drafts\">Draft Posts</a></li>\n"
				."</ul>\n"
				."<div class=\"clear\"></div>\n"
				."</div>\n";
				
		$html .= "<div class=\"page_wrapper\">\n"
			."<div id=\"post_form\" class=\"page\">\n"
			."<h1>Compose New Post</h1>\n";

		if (strpos($_SERVER["REQUEST_URI"],"failure")){
			$html .= "<div class=\"notice\"><h3>Post Failed</h3><p>You must provide a title for posts, and uploaded images must be 4Mb or smaller. Hit the back button in your browser to add a title.</p></div>\n";
		}

		$html .= "<form method=\"POST\" action=\"/api/post/add\" enctype=\"multipart/form-data\">\n"
			."	<div class=\"form_field\">\n"
			."		<div class=\"form_field_label\">Title</div>\n"
			."		<div class=\"form_field_control\"><input type=\"text\" name=\"title\" id=\"title_field\" /></div>\n"
			."	</div>\n"
			."	\n"
			."	<div class=\"form_field\">\n"
			."		<div class=\"form_field_label\">Photo <small>(optional - 4Mb upload limit per image, jpg, png or gif)</small></div>\n"
			."		<div class=\"form_field_control\"><input type=\"file\" name=\"photo\" id=\"photo\" /></div>\n"
			."	</div>\n"
			."	\n"
			."	<div class=\"form_field\">\n"
			."		<div class=\"form_field_label\">Body <small>(supports <a href='http://daringfireball.net/projects/markdown/basics' target='_blank'>markdown</a>)</small></div>\n"
			."		<div class=\"form_field_control\"><textarea name=\"body\" rows=\"15\" ></textarea></div>\n"
			."	</div>\n"
			."	\n"
			."	<div class=\"form_field\">\n"
			."		<div class=\"form_field_label\">Tags <small>(comma separated)</small></div>\n"
			."		<div class=\"form_field_control\"><input type=\"text\" name=\"tags\" /></div>\n"
			."	</div>\n"
			."	\n"
			."	<div class=\"form_field\">\n"
			."		<div class=\"form_field_label\">Privacy</div>\n"
			."		<div class=\"form_field_control\"><select name=\"privacy\">\n"
			."			<option value=\"0\" ".(($user_row["DefaultPostPrivacy"]==0) ? "selected" : "")." >Public</option>\n"
			."			<option value=\"1\" ".(($user_row["DefaultPostPrivacy"]==1) ? "selected" : "")." >Friends Only</option>\n"
			."		</select></div>\n"
			."	</div>\n"
			."	<div class=\"form_field\">\n"
			."		<div class=\"form_field_label\">Status</div>\n"
			."		<div class=\"form_field_control\"><select name=\"status\">\n"
			."			<option value=\"0\" ".(($user_row["DefaultPostStatus"]==0) ? "selected" : "")." >Draft</option>\n"
			."			<option value=\"1\" ".(($user_row["DefaultPostStatus"]==1) ? "selected" : "")." >Published</option>\n"
			."		</select></div>\n"
			."	</div>\n"
			."	\n"
			."	<input type=\"submit\" value=\"Submit Post\" />\n"
			."	\n"
			."</form>\n"
			."</div> <!-- #post_form -->\n"
			."</div>\n"
			."<script>\n"
			."$(\"#title_field\").focus();\n"
			."</script>\n";

		$html .= render_footer();

		return $html;
	} else {
		header("Location: /403");
	}
}

function render_post_drafts_page($page){

	if (isset($_SESSION["user_id"])){

		$start = (intval($page) - 1) * 20;

		$html = render_header("Draft Posts");

		$mysqli = db_connect();

		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/post/add\" title=\"Compose\">Compose</a></li>\n"
			."<li class=\"selected\"><a href=\"/post/drafts\" title=\"Draft Posts\">Draft Posts</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";
		
		$sql = "";
		$count_sql = "";

		
		$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar,Likes.Id AS LikeId FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Likes ON Likes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Likes.PostId=Posts.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId"
			." WHERE Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." AND Posts.Status=".POST_STATUS_DRAFT
			." ORDER BY Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";

		$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
			." INNER JOIN Users ON Posts.UserId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsA ON Posts.UserId=FriendsA.UserId"
			." WHERE Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." AND Posts.Status=".POST_STATUS_DRAFT;

		// fetch count for pagination
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumPosts"];

		$post_result = $mysqli->query($sql);

		$html .= "<div id=\"header\">\n"
			."<h1>You have ".$count." draft posts</h1>\n"
			."<p>All of your draft posts.</p>\n"
			."</div>";

		$html .= render_posts($mysqli,$post_result);

		// Pagination
		$html .= render_pagination("account/drafts",$page,$count,20);

		$html .= render_display_controls();

		$html .= render_footer();

		return $html;
		
		

	} else {
		header("Location: /401");
	}

}



function render_post_edit_page($post_id){

	$html = "";

	if (isset($_SESSION["user_id"])){

		// fetch the post to edit
		$mysqli = db_connect();
		$post_result = $mysqli->query("SELECT * FROM Posts WHERE Id=".$mysqli->real_escape_string($post_id));

		if ($post_result->num_rows > 0){

			$post_row = $post_result->fetch_assoc();

			// check if the logged in user wrote the post
			if ($_SESSION["user_id"] == $post_row["UserId"]){

				// fetch the tags
				$tags = "";
				$tags_array = array();
				$tags_result = $mysqli->query("SELECT * FROM PostTags INNER JOIN Tags ON PostTags.TagId=Tags.Id WHERE PostTags.PostId=".$mysqli->real_escape_string($post_id)." ORDER BY Tags.Name");
				if ($tags_result->num_rows > 0) {
					while($row =@ $tags_result->fetch_assoc()){
						$tags_array[] = $row["Name"];
					}
					$tags = implode(", ",$tags_array);
				}

				$html .= render_header("Edit Post");

				$html .= "<div class=\"page_wrapper\">\n"
					."<div id=\"post_form\" class=\"page\">\n"
					."<h1>Edit Post</h1>\n"
					."<form method=\"POST\" action=\"/api/post/edit\" enctype=\"multipart/form-data\">\n"
					."  <input type=\"hidden\" name=\"id\" value=\"".$post_row["Id"]."\" />\n"
					."  <div class=\"form_field\">\n"
					."  <div class=\"form_field_label\">Title <small>(required)</small></div>\n"
					."		<div class=\"form_field_control\"><input type=\"text\" name=\"title\"  value=\"".htmlspecialchars($post_row["Title"])."\" /></div>\n"
					."	</div>\n"
					."	<div class=\"form_field\">\n";

				// <small>(optional) - <a href=\"lib/api.php?action=post_remove_image&id=".$post_row["Id"]."\">Remove Existing Photo</a>)</small>

				if ($post_row["Photo"] != ""){
					$html .= "<div class=\"form_field_label\">Photo <small>(optional - 4Mb upload limit per image, jpg, png or gif)</small></div>\n"
						."<div class=\"form_field_control\"><img src=\"/".$post_row["Photo"]."\" width=\"500\" /></div>\n"
						."<div class=\"form_field_control\"><input type=\"file\" name=\"photo\" id=\"photo\" /> <small>(choose to replace current image)</small></div>\n";
				} else {
					$html .= "<div class=\"form_field_control\"><input type=\"file\" name=\"photo\" id=\"photo\" /></div>\n";
				}

				$html .= "	</div>\n"
					."	<div class=\"form_field\">\n"
					."		<div class=\"form_field_label\">Body <small>(supports <a href='http://daringfireball.net/projects/markdown/basics' target='_blank'>markdown</a>)</small></div>\n"
					."		<div class=\"form_field_control\"><textarea name=\"body\" rows=\"15\" >".$post_row["Body"]."</textarea></div>\n"
					."	</div>\n"
					."	<div class=\"form_field\">\n"
					."		<div class=\"form_field_label\">Tags <small>(comma separated)</small></div>\n"
					."		<div class=\"form_field_control\"><input type=\"text\" name=\"tags\" value=\"".htmlspecialchars($tags)."\" /></div>\n"
					."	</div>\n"
					."	<div class=\"form_field\">\n"
					."		<div class=\"form_field_label\">Privacy</div>\n"
					."		<div class=\"form_field_control\"><select name=\"privacy\">\n"
					."			<option value=\"0\" ".(($post_row["Privacy"]==0) ? "selected" : "")." >Public</option>\n"
					."			<option value=\"1\" ".(($post_row["Privacy"]==1) ? "selected" : "")." >Friends Only</option>\n"
					."		</select></div>\n"
					."	</div>\n"
					."	<div class=\"form_field\">\n"
					."		<div class=\"form_field_label\">Status</div>\n"
					."		<div class=\"form_field_control\"><select name=\"status\">\n"
					."			<option value=\"0\" ".(($post_row["Status"]==0) ? "selected" : "")." >Draft</option>\n"
					."			<option value=\"1\" ".(($post_row["Status"]==1) ? "selected" : "")." >Published</option>\n"
					."		</select></div>\n"
					."	</div>\n"
					."	<input type=\"submit\" value=\"Make Changes\" />\n"
					."</form>\n"
					."</div>\n"
					."</div>\n";

			} else {
				header("Location: /401?reason=not_author&loggedin=".$_SESSION["user_id"]."&author=".$row["UserId"]);
			}

			$html .= render_footer();

		} else {
			header("Location: /404?reason=post_not_found");
		}

	} else {
		header("Location: /401?reason=not_logged_in");
	}

	return $html;

}

?>
