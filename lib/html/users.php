<?php


function render_register_terms_page(){

	$html = render_header("Terms and Conditions");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div class=\"page terms\">\n"
		."<h1>Terms and Conditions of Membership</h1>\n";

	$html .= "<div id=\"terms\">\n"
		.file_get_contents("lib/html/terms.htm")
		."</div>\n";

	$html .= "<p><button onclick=\"document.location.href='/register/agree'\">Yes - I Agree With These Terms</button></p>\n"
		."</div>\n"
		."</div>\n";

	$html .= render_footer();

	return $html;

}

function render_register_page(){

	$html = render_header("Register");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div id=\"register_form\" class=\"page\">\n"
		."<h1>Register</h1>\n";

	if ( (strpos($_SERVER["REQUEST_URI"], 'failure') == false) ){

		$html .= "<form method=\"POST\" action=\"/api/user/register\">\n"
			."<table border=\"0\" cellspacing=\"1\" cellpadding=\"5\">\n"
			."<tr><th>Username</th><td><input type=\"text\" name=\"username\" /></td></tr>\n"
			."<tr><th>Email</th><td><input type=\"text\" name=\"email\" /></td></tr>\n"
			."</table>\n"
			."<input type=\"submit\" value=\"Sign Up\" />\n"
			."</form>\n"
			."<p>Usernames must be alphanumeric, with no spaces.</p>\n";

	} else {

		$failure_reason = end(explode("/",$_SERVER["REQUEST_URI"]));

		$html .= "<p>Unfortunately your account creation has failed</p>";

		switch ($failure_reason){
			case "-1":
				$html .= "<p>You have chosen a username and/or email address already used by another account. Usernames must be unique, and multiple accounts cannot use the same username.</p>\n";
				break;
			case "-2":
				$html .= "<p>The username you have chosen is not long enough. Usernames must consist of 4 characters or more.</p>\n";
				break;
			case "-3":
				$html .= "<p>The username you have chosen has extended characters in it. Please only use alphanumeric characters. No spaces. Remember usernames are case sensitive too.</p>\n";
				break;
			case "-4":
				$html .= "<p>The username you have chosen is a reserved term. There are certain words we have reserved to protect the system, and for our own future use.</p>\n";
				break;
			case "-5":
				$html .= "<p>Something went wrong when we updated the database. Give us a shout, and we'll have a look in the logs.</p>\n";
				break;
			case "-6":
				$html .= "<p>You MUST provide a username, and email address.</p>\n";
				break;
		}

		$html .= "<p>Hit the back button in your browser, and try again.\n";

	}
	$html .= "</div>\n"
		."</div>\n";

	$html .= render_footer();

	return $html;
}

function render_password_reset_page(){

	$html = render_header("Password Reset");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div id=\"register_form\" class=\"page\">\n"
		."<h1>Password Reset</h1>\n";

	if ( (strpos($_SERVER["REQUEST_URI"], 'success') == false) && (strpos($_SERVER["REQUEST_URI"], 'failure') == false)){

		$html .= "<p>Enter your email address in the form below, and we will reset your password, and email you a copy of it.</p>\n";

		$html .= "<form method=\"POST\" action=\"/api/user/password_reset\">\n"
			."<table border=\"0\" cellspacing=\"1\" cellpadding=\"5\">\n"
			."<tr><th>Email</th><td><input type=\"text\" name=\"email\" /></td></tr>\n"
			."</table>\n"
			."<input type=\"submit\" value=\"Reset Password\" />\n"
			."</form>\n";

	} else {

		if (strpos($_SERVER["REQUEST_URI"], 'failure') == true) {
			$failure_reason = end(explode("/",$_SERVER["REQUEST_URI"]));

			$html .= "<p>Unfortunately your password reset request has failed.</p>";

			switch ($failure_reason){
				case "-1":
					$html .= "<p>We couldn't find a user account with the email address you specified.</p>\n";
					break;
				case "-2":
					$html .= "<p>You didn't submit anything in the form.</p>\n";
					break;
			}

			$html .= "<p>Hit the back button in your browser, and try again.\n";
		} else {
			$html .= "<p>Your password has been reset, and emailed to you.</p>\n";
		}
	}
	$html .= "</div>\n"
		."</div>\n";

	$html .= render_footer();

	return $html;
}

function render_login_page(){

	$html = render_header("Login");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div id=\"login_form\" class=\"page\">\n"
		."<h1>Login</h1>\n";

	if (strpos($_SERVER["REQUEST_URI"],"failure")){
		$html .= "<div class=\"notice\"><h3>Login Failed</h3><p>Please check your username and password, and try again.</p></div>\n";
	}

	$html .= "<form method=\"POST\" action=\"/api/user/login\">\n"
		."<table border=\"0\" cellspacing=\"1\" cellpadding=\"5\">\n"
		."<tr><th>Username</th><td><input type=\"text\" name=\"username\" value=\"".(isset($_GET["username"])?$_GET["username"]:"")."\" /></td></tr>\n"
		."<tr><th>Password</th><td><input type=\"password\" name=\"password\" value=\"".(isset($_GET["password"])?$_GET["password"]:"")."\" /></td></tr>\n"
		."</table>\n"
		."<input type=\"submit\" value=\"Login\" />\n"
		."</form>\n";

	$html .= "<p><a href=\"/password_reset\">Forgotten your password?</a></p>\n";

	$html .= "</div> <!-- #login_form -->\n"
		."</div>\n";

	$html .= render_footer();

	return $html;
}



function render_profile_page_header($username,$page){

	$start = (intval($page) - 1) * 20;

	$html = render_header($username,"",true);

	$mysqli = db_connect();

	if (isset($_SESSION["user_id"])){
		$sql = "SELECT Users.*,Friends.FriendId,FriendsB.FriendId AS FriendBId FROM Users"
			." LEFT OUTER JOIN Friends ON Friends.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Friends.FriendId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsB ON FriendsB.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendsB.UserId=Users.Id"
			." WHERE Username='".$mysqli->real_escape_string($username)."'";
	} else {
		$sql = "SELECT * FROM Users WHERE Username='".$mysqli->real_escape_string($username)."'";
	}

	$user_result = $mysqli->query($sql);



	if ($user_result->num_rows > 0){

		$user_row = $user_result->fetch_assoc();

		// get CSS
		if ($user_row["CSS"] != ""){
			// $html .= "<style>\n".$user_row["CSS"]."</style>\n";
		}

		// get karma
		$sql = "SELECT COUNT(DISTINCT Posts.Id) + COUNT(DISTINCT Comments.Id) + COUNT(DISTINCT Likes.Id) AS TotalCount\n"
			." FROM Users"
			." LEFT OUTER JOIN Posts ON Posts.UserId=Users.Id AND Posts.Status=1 AND Posts.Privacy=0"
			." LEFT OUTER JOIN Comments ON Posts.Id=Comments.PostId AND Comments.UserId<>Users.Id"
			." LEFT OUTER JOIN Likes ON Posts.Id=Likes.PostId AND Likes.UserId<>Users.Id"
			." WHERE Users.Id=".$user_row["Id"]
			." AND Posts.Created > (CURRENT_TIMESTAMP - INTERVAL '7' DAY)"
			." GROUP BY Users.Id";

		$karma_result = $mysqli->query($sql);
		$karma_row = $karma_result->fetch_assoc();
		
		$html .= "<div class=\"profile_header_wrapper\">\n"
			."<div class=\"profile_header\">\n";

		$avatar_image = ($user_row["Avatar"] != "") ? $user_row["Avatar"] : "avatars/generic_64.jpg";
		$html .= "<div class=\"avatar\"><img src=\"/".$avatar_image."\" width=\"64\" height=\"64\" alt=\"".$username."\" /></div>\n"
			."<div class=\"username\"><h1>".$user_row["Username"]."</h1></div>\n"
			."<div class=\"karma\"><h1><a title=\"This is ".$username."'s Karma\">".$karma_row["TotalCount"]."</a></h1></div>\n"
			."<div class=\"clear\"></div>\n"
			."<div class=\"about\">\n";

		if ($user_row["Bio"] != "") $html .= "<div class='bio'>".Markdown($user_row["Bio"])."</div>\n";

		if ($user_row["Bio"] == "" && $_SESSION["user_id"] == $user_row["Id"]){
			$html .= "<div class='bio'><h1>Eh?</h1><p>It looks like you haven't filled in any profile information yet!<br /><br />Visit the <a href='/account'>account</a> page and tell the world about yourself!</p></div>\n";
		}
		
		// fetch tags for user
		$tags_result = $mysqli->query("SELECT Tags.Name AS TagName FROM Tags"
			." INNER JOIN UserTags ON Tags.Id=UserTags.TagId"
			." WHERE UserTags.UserId=".$mysqli->real_escape_string($user_row["Id"])
			." ORDER BY Tags.Name");
		if ($tags_result->num_rows > 0){
			$html .= "<div class=\"tags_section\">\n<h3>Tagged</h3>\n<ul class='tags'>\n";
			while ($tag_row =@ $tags_result->fetch_assoc()){
				$html .= "<li><a href=\"/explore/directory/".addslashes($tag_row["TagName"])."\" title=\"".addslashes($tag_row["TagName"])."\">#".$tag_row["TagName"]."</a></li>\n";
			}
			$html .= "</ul>\n</div>\n";
		}
		
		// fetch badges for user
		$badges_result = $mysqli->query("SELECT Badges.BadgeId AS BadgeId, BadgeIcons.URL AS BadgeURL, BadgeIcons.Title AS BadgeTitle FROM Badges"
			." INNER JOIN BadgeIcons ON Badges.BadgeId=BadgeIcons.Id"
			." WHERE Badges.UserId=".$mysqli->real_escape_string($user_row["Id"])." ORDER BY Badges.Created");
		if ($badges_result->num_rows > 0){
			$html .= "<div class=\"badges_section\">\n<h3>Badges</h3>\n<ul class='badges'>\n";
			while ($badge_row =@ $badges_result->fetch_assoc()){
				$html .= "<li><img src=\"".$badge_row["BadgeURL"]."\" width=\"32\" height=\"32\" alt=\"".$badge_row["BadgeTitle"]."\" title=\"".$badge_row["BadgeTitle"]."\" /></li>\n";
			}
			$html .= "</ul>\n</div>\n";
		}

		// controls
		$html .= "<div class=\"controls\">\n";
		
		// show the friend / unfriend link
		if (isset($_SESSION["user_id"])){
			if ($_SESSION["user_id"] != $user_row["Id"]){
				if ($user_row["FriendId"]!=""){
					$html .= "<div class=\"button\"><a href=\"/api/friends/remove/".$user_row["Username"]."\" onclick=\"return confirm('Are you sure?');\">Remove from Friends</a></div>\n";
				} else {
					$html .= "<div class=\"button\"><a href=\"/api/friends/add/".$user_row["Username"]."\">Add to Friends</a></div>\n";
				}
			}
			if ($user_row["Username"] != $_SESSION["user_name"]){
				if (($user_row["MessagesFriendsOnly"]==1 && $user_row["FriendBId"] != "") || ($user_row["MessagesFriendsOnly"]==0)){
					$html .= "<div class=\"button\"><a href=\"/messages/compose/".$user_row["Username"]."\">Send Message</a></div>\n";
				}
			} else {
				// its the logged in user
				$html .= "<div class=\"button\"><a href=\"/account\" title=\"Edit Account\">Edit Account</a></div>\n"
					."<div class=\"button\"><a href=\"/api/user/logout\" title=\"Logout\" onclick=\"return confirm('Are you sure you want to logout?');\">Logout</a></div>\n";
			}
		}

		$html .= "<div class=\"clear\"></div>\n"
			."</div> <!-- .controls -->\n";

		$html .= "<div class='social_networks'>\n";
			if ($user_row["Twitter"] != "") $html .= "<div class=\"social_network twitter\"><a href=\"http://twitter.com/".$user_row["Twitter"]."\" title=\"Twitter\">Twitter</a></div>\n";
			if ($user_row["Tumblr"] != "") $html .= "<div class=\"social_network tumblr\"><a href=\"http://".$user_row["Tumblr"].".tumblr.com\" title=\"Tumblr\">Tumblr</a></div>\n";
			if ($user_row["Facebook"] != "") $html .= "<div class=\"social_network facebook\"><a href=\"http://facebook.com/".$user_row["Facebook"]."\" title=\"Facebook\">Facebook</a></div>\n";
			if ($user_row["GooglePlus"] != "") $html .= "<div class=\"social_network googleplus\"><a href=\"http://plus.google.com/".$user_row["GooglePlus"]."/about\" title=\"Google+\">Google+</a></div>\n";
			if ($user_row["LiveJournal"] != "") $html .= "<div class=\"social_network livejournal\"><a href=\"http://".$user_row["LiveJournal"].".livejournal.com\" title=\"LiveJournal\">LiveJournal</a></div>\n";
			if ($user_row["Wordpress"] != "") $html .= "<div class=\"social_network wordpress\"><a href=\"http://".$user_row["Wordpress"]."\" title=\"Wordpress\">Wordpress</a></div>\n";
			if ($user_row["Blogger"] != "") $html .= "<div class=\"social_network blogger\"><a href=\"http://".$user_row["Blogger"]."\" title=\"Blogger\">Blogger</a></div>\n";
			$html .= "<div class=\"social_network rss\"><a href=\"http://wetheusers.net/".$user_row["Username"]."/rss\" title=\"RSS\">RSS</a></div>\n";
		$html .= "<div class='clear'></div>\n"
			."</div><!-- .social_networks -->\n";

		/*
		$html .= "<div class='im_networks'>\n";
			if ($user_row["KIK"] != "") $html .= "<div class=\"im_network kik\">".$user_row["KIK"]."</div>\n";
			if ($user_row["GoogleTalk"] != "") $html .= "<div class=\"im_network google_talk\">".$user_row["GoogleTalk"]."</div>\n";
			if ($user_row["YahooMessenger"] != "") $html .= "<div class=\"im_network yahoo_messenger\">".$user_row["YahooMessenger"]."</div>\n";
			if ($user_row["MSNMessenger"] != "") $html .= "<div class=\"im_network msn_messenger\">".$user_row["MSNMessenger"]."</div>\n";
			if ($user_row["AOLInstantMessenger"] != "") $html .= "<div class=\"im_network aol_instant_messenger\">".$user_row["AOLInstantMessenger"]."</div>\n";
			if ($user_row["ICQ"] != "") $html .= "<div class=\"im_network icq\">".$user_row["ICQ"]."</div>\n";
		$html .= "<div class='clear'></div>\n"
			."</div> <!-- .im_networks -->\n";
		*/
		
		$html .= "<div class='clear'></div>\n";



		$html .= "</div><!-- .about -->\n";

		$html .= "</div><!-- .profile_header -->\n";
	
		
		
		$html .= "</div> <!-- .profile_header_wrapper -->\n";


		return $html;
	} else {
		header("Location: /404");
	}


}

function render_profile_page_posts($username,$page){

	$start = (intval($page) - 1) * 20;

	$mysqli = db_connect();

	$html = "";
	
	if (isset($_SESSION["user_id"])){
		$sql = "SELECT Users.*,Friends.FriendId,FriendsB.FriendId AS FriendBId FROM Users"
			." LEFT OUTER JOIN Friends ON Friends.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Friends.FriendId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsB ON FriendsB.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendsB.UserId=Users.Id"
			." WHERE Username='".$mysqli->real_escape_string($username)."'";
	} else {
		$sql = "SELECT * FROM Users WHERE Username='".$mysqli->real_escape_string($username)."'";
	}

	$user_result = $mysqli->query($sql);

	if ($user_result->num_rows > 0){

		$user_row = $user_result->fetch_assoc();
		
		$html .= "<div class=\"profile_menu_wrapper\">\n"
			."<ul class=\"profile_menu\">\n"
			."<li class=\"selected\"><a href=\"/".$username."\" title=\"Posts\">Posts</a></li>\n"
			.(($user_row["ShowFriends"]==1) ? "<li><a href=\"/".$username."/friends\" title=\"Friends\">Friends</a></li>\n" : "")
			.(($user_row["ShowFriendOf"]==1) ? "<li><a href=\"/".$username."/followers\" title=\"Followers\">Followers</a></li>\n" : "")
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";	
	
		$sql = "";
		$sql_count = "";
		if (isset($_SESSION["user_id"])){

			$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar,Likes.Id AS LikeId FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." LEFT OUTER JOIN Likes ON Likes.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Likes.PostId=Posts.Id"
				." LEFT OUTER JOIN Friends FriendsOfAuthor ON Posts.UserId=FriendsOfAuthor.UserId"
				." WHERE"
				." ((FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY.")"
				." OR"
				." (Posts.Privacy=".POST_PRIVACY_PUBLIC.")"
				." OR"
				." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." AND Users.Username='".$mysqli->real_escape_string($username)."'"
				." ORDER BY Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";

			$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." LEFT OUTER JOIN Friends FriendsOfAuthor ON Posts.UserId=FriendsOfAuthor.UserId"
				." WHERE"
				." ((FriendsOfAuthor.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Posts.Privacy=".POST_PRIVACY_FRIENDS_ONLY." AND Posts.Status=".POST_STATUS_PUBLISHED.")"
				." OR"
				." (Posts.Privacy=".POST_PRIVACY_PUBLIC." AND Posts.Status=".POST_STATUS_PUBLISHED.")"
				." OR"
				." (Posts.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])."))"
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." AND Users.Username='".$mysqli->real_escape_string($username)."'";

		} else {

			$sql = "SELECT DISTINCT Posts.*,Users.Username,Users.Avatar FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." WHERE"
				." Posts.Privacy=".POST_PRIVACY_PUBLIC
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." AND Users.Username='".$mysqli->real_escape_string($username)."'"
				." ORDER BY Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";

			$sql_count = "SELECT COUNT(DISTINCT Posts.Id) AS NumPosts FROM Posts"
				." INNER JOIN Users ON Posts.UserId=Users.Id"
				." WHERE"
				." Posts.Privacy=".POST_PRIVACY_PUBLIC
				." AND Posts.Status=".POST_STATUS_PUBLISHED
				." AND Users.Username='".$mysqli->real_escape_string($username)."'";
		}

		// fetch count for pagination
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumPosts"];

		// posts
		$post_result = $mysqli->query($sql);

		$html .= render_posts($mysqli,$post_result);

		$html .= render_pagination($username,$page,$count,20);

		$html .= render_display_controls();

		$html .= render_footer();
	}
	return $html;
}


function render_account_page(){

	if (isset($_SESSION["user_id"])){

		$html = render_header("Account");

		$mysqli = db_connect();
		$user_result = $mysqli->query("SELECT * FROM Users WHERE Id=".$mysqli->real_escape_string($_SESSION["user_id"]));



		if ($user_result->num_rows > 0){

			$user_row = $user_result->fetch_assoc();

			// fetch the tags
			$tags = "";
			$tags_array = array();
			$tags_result = $mysqli->query("SELECT * FROM UserTags INNER JOIN Tags ON UserTags.TagId=Tags.Id WHERE UserTags.UserId=".$mysqli->real_escape_string($user_row["Id"])." ORDER BY Tags.Name");
			if ($tags_result->num_rows > 0) {
				while($row =@ $tags_result->fetch_assoc()){
					$tags_array[] = $row["Name"];
				}
				$tags = implode(", ",$tags_array);
			}

			$html .= "<div class=\"bg_menu_wrapper\">\n"
				."<ul class=\"bg_menu\">\n"
				."<li class=\"selected\"><a href=\"/account\" title=\"Account\">Account</a></li>\n"
				."<li><a href=\"/account/style\" title=\"Style\">Style</a></li>\n"
				."<li><a href=\"/account/friends\" title=\"Friends\">Friends</a></li>\n"
				."<li><a href=\"/account/followers\" title=\"Followers\">Followers</a></li>\n"
				."</ul>\n"
				."<div class=\"clear\"></div>\n"
				."</div>\n";
			
			$html .= "<div class=\"page_wrapper\">\n"
				."<div id=\"account_form\" class=\"page\">\n"
				."<h1>Edit Account</h1>\n";

			if (strpos($_SERVER["REQUEST_URI"],"success")){
				$html .= "<div class='notice'><h3>Account Update Successful</h3><p>The changes you requested have been made.</p></div>\n";
			}

			if (strpos($_SERVER["REQUEST_URI"],"welcome")){
				$html .= "<div class='notice'><h3>Welcome to the Site!</h3><p>Please change your password.</p></div>\n";
			}

			if (strpos($_SERVER["REQUEST_URI"],"failure")){
				$html .= "<div class='notice'><h3>Account Update Failure</h3><p>The changes you requested could not be made.</p>";
				$failure_reason = end(explode("/",$_SERVER["REQUEST_URI"]));
				switch($failure_reason){
					case "-1":
						$html .= "<p>You MUST supply a username, and an email address.</p>\n";
						break;
					case "-2":
						$html .= "<p>Usernames must be at least 4 characters long, and contain only alphanumeric characters.</p>\n";
						break;
					case "-3":
						$html .= "<p>We cannot find your user record. This is very strange - give us a shout, and we'll look into it.</p>\n";
						break;
					case "-4":
						$html .= "<p>You chose a username already in use. Please choose a different one.</p>\n";
						break;
					case "-5":
						$html .= "<p>Your account information has been updated, but the avatar image you tried to submit was too big. Uploaded images must be less than 4Mb.</p>\n";
						break;
					case "-6":
						$html .= "<p>Your account information has been updated, but the avatar image was not in a supported format. Avatars must be JPG, PNG or GIF, and less than 4Mb.</p>\n";
						break;
					case "-7":
						$html .= "<p>If you are changing your username, you need to fill in the password again.</p>\n";
						break;
				}
				$html .="</div>\n";
			}


			if ($_SESSION["user_status"] == USER_STATUS_VALIDATED){

				$html .= "<form method=\"POST\" action=\"/api/account/update\" enctype=\"multipart/form-data\">\n"
					."<table border=\"0\" cellspacing=\"1\" cellpadding=\"5\">\n"
					."<tr><th class=\"heading\" colspan=\"2\"><h3>Basic Information</h3><p>Note that you can leave your password blank unless you want to change it.</p></th></tr>\n"
					."<tr><th>Username</th><td><input type=\"text\" name=\"username\" value=\"".htmlspecialchars($_SESSION["user_name"])."\" /></td></tr>\n"
					."<tr><th>Password</th><td><input type=\"password\" name=\"password\" value=\"\" /></td></tr>\n"
					."<tr><th>Email</th><td><input type=\"text\" name=\"email\" value=\"".htmlspecialchars($_SESSION["user_email"])."\" /></td></tr>\n";

				if ($user_row["Avatar"] != ""){
					$html .= "<tr><th rowspan=\"2\">Avatar<br /><small>2Mb limit<br />jpg,png,gif</small></th><td><input type=\"file\" name=\"avatar\" id=\"avatar\" /></td></tr>\n"
						."<tr><td><img src=\"/".$user_row["Avatar"]."\" width=\"64\" height=\"64\" /></td></tr>\n";
				} else {
					$html .= "<tr><th>Avatar</th><td><input type=\"file\" name=\"avatar\" id=\"avatar\" /></td></tr>\n";
				}

				// Bio
				$html .= "<tr><th class=\"heading\" colspan=\"2\"><h3>Bio</h3><p>Tell everybody a little about yourself... (supports <a href=\"http://daringfireball.net/projects/markdown/\">markdown</a>)</p></th></tr>\n"
					."<tr><td colspan=\"2\"><textarea name=\"bio\" rows=\"5\">".$user_row["Bio"]."</textarea></td></tr>\n";

				// Tags
				$html .= "<tr><th class=\"heading\" colspan=\"2\"><h3>Tags</h3><p>Tag yourself to appear in the user directory... (e.g. writer, blogger, dad, mom, student, etc)</p></th></tr>\n"
					."<tr><th>Tags</th><td><input type=\"text\" name=\"tags\" id=\"tags\" value=\"".htmlspecialchars($tags)."\" /> <span>separate tags with commas</span></td></tr>\n";

				// Social Network Links
				$html .= "<tr><th class=\"heading\" colspan=\"2\"><h3>Social Networks</h3><p>URLs to the common social networks...</p></th></tr>\n"
					."<tr><th>Twitter</th><td><input type=\"text\" name=\"twitter\" id=\"twitter\" value=\"".htmlspecialchars($user_row["Twitter"])."\" /> <span>Username ONLY - http://twitter.com/&lt;username&gt;</span></td></tr>\n"
					."<tr><th>Tumblr</th><td><input type=\"text\" name=\"tumblr\" id=\"tumblr\" value=\"".htmlspecialchars($user_row["Tumblr"])."\" /> <span>Username ONLY - http://&lt;username&gt;.tumblr.com</span></td></tr>\n"
					."<tr><th>Facebook</th><td><input type=\"text\" name=\"facebook\" id=\"facebook\" value=\"".htmlspecialchars($user_row["Facebook"])."\" /> <span>Username ONLY - http://facebook.com/&lt;username&gt;</span></td></tr>\n"
					."<tr><th>Google+</th><td><input type=\"text\" name=\"googleplus\" id=\"googleplus\" value=\"".htmlspecialchars($user_row["GooglePlus"])."\" /> <span>User ID ONLY - http://plus.google.com/&lt;user id&gt;/about</span></td></tr>\n"
					."<tr><th>Wordpress</th><td><input type=\"text\" name=\"wordpress\" id=\"wordpress\" value=\"".htmlspecialchars($user_row["Wordpress"])."\" /> <span>Domain ONLY - http://&lt;domain&gt;</span></td></tr>\n"
					."<tr><th>Blogger</th><td><input type=\"text\" name=\"blogger\" id=\"blogger\" value=\"".htmlspecialchars($user_row["Blogger"])."\" /> <span>Domain ONLY - http://&lt;domain&gt;</span></td></tr>\n"
					."<tr><th>LiveJournal</th><td><input type=\"text\" name=\"livejournal\" id=\"livejournal\" value=\"".htmlspecialchars($user_row["LiveJournal"])."\" /> <span>Username ONLY - http://&lt;username&gt;.livejournal.com</span></td></tr>\n";

				// Instant Messaging Links
				/*
				$html .= "<tr><th class=\"heading\" colspan=\"2\"><h3>Instant Messaging (Coming Soon!)</h3><p>Usernames for instant messaging networks...</p></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"im_friends_only\" value=\"1\" type=\"checkbox\" ".(($user_row["IMFriendsOnly"]==1)?"checked":"")." /> <small>Only show IM details to friends.</small></th></tr>\n"
					."<tr><th>KIK</th><td><input type=\"text\" name=\"kik\" id=\"kik\" value=\"".htmlspecialchars($user_row["KIK"])."\" /></td></tr>\n"
					."<tr><th>Google Talk</th><td><input type=\"text\" name=\"google_talk\" id=\"google_talk\" value=\"".htmlspecialchars($user_row["GoogleTalk"])."\" /></td></tr>\n"
					."<tr><th>Yahoo Messenger</th><td><input type=\"text\" name=\"yahoo_messenger\" id=\"yahoo_messenger\" value=\"".htmlspecialchars($user_row["YahooMessenger"])."\" /></td></tr>\n"
					."<tr><th>MSN Messenger</th><td><input type=\"text\" name=\"msn_messenger\" id=\"msn_messenger\" value=\"".htmlspecialchars($user_row["MSNMessenger"])."\" /> <span>(aka 'Windows Live Messenger')</span></td></tr>\n"
					."<tr><th>AOL Instant Messenger</th><td><input type=\"text\" name=\"aol_instant_messenger\" id=\"aol_instant_messenger\" value=\"".htmlspecialchars($user_row["AOLInstantMessenger"])."\" /></td></tr>\n"
					."<tr><th>ICQ</th><td><input type=\"text\" name=\"icq\" id=\"icq\" value=\"".htmlspecialchars($user_row["ICQ"])."\" /></td></tr>\n";
				*/
					
				$html .= "<tr><th class=\"heading\" colspan=\"2\"><h3>EMail Notifications</h3><p>Receive email for the following events...</p></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"notify_messages\" value=\"1\" type=\"checkbox\" ".(($user_row["NotifyMessages"]==1)?"checked":"")." /> <small>Notify me when people send me messages.</small></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"notify_comments\" value=\"1\" type=\"checkbox\" ".(($user_row["NotifyComments"]==1)?"checked":"")." /> <small>Notify me when people comment on my posts.</small></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"notify_other_comments\" value=\"1\" type=\"checkbox\" ".(($user_row["NotifyOtherComments"]==1)?"checked":"")." /> <small>Notify me when people comment on posts where I have commented.</small></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"notify_new_friends\" value=\"1\" type=\"checkbox\" ".(($user_row["NotifyNewFriends"]==1)?"checked":"")." /> <small>Notify me when people add me as a friend.</small></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"notify_friends_posts\" value=\"1\" type=\"checkbox\" ".(($user_row["NotifyFriendsPosts"]==1)?"checked":"")." /> <small>Notify me when friends post.</small></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"notify_likes\" value=\"1\" type=\"checkbox\" ".(($user_row["NotifyLikes"]==1)?"checked":"")." /> <small>Notify me when my posts are liked.</small></th></tr>\n";

				$html .= "<tr><th class=\"heading\" colspan=\"2\"><h3>Show Friends on Profile?</h3><p>Choose if to show friends on your profile</p></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"show_friends\" value=\"1\" type=\"checkbox\" ".(($user_row["ShowFriends"]==1)?"checked":"")." /> <small>Show people I call a friend.</small></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"show_friend_of\" value=\"1\" type=\"checkbox\" ".(($user_row["ShowFriendOf"]==1)?"checked":"")." /> <small>Show people who call me a friend.</small></th></tr>\n";

				$html .= "<tr><th class=\"heading\" colspan=\"2\"><h3>Messaging</h3><p>Choose who can send you messages...</p></th></tr>\n"
					."<tr><th colspan=\"2\"><input name=\"messages_friends_only\" value=\"1\" type=\"checkbox\" ".(($user_row["MessagesFriendsOnly"]==1)?"checked":"")." /> <small>Only let people I call a friend message me.</small></th></tr>\n";

					
				$html .= "<tr><th class=\"heading\" colspan=\"2\"><h3>Defaults</h3><p>Set defaults for posts...</p></th></tr>\n"
					."<tr><th>Privacy</th><th><select name=\"default_post_privacy\">"
						."<option value=\"0\" ".(($user_row["DefaultPostPrivacy"]=="0") ? "selected" : "").">Public</option>"
						."<option value=\"1\" ".(($user_row["DefaultPostPrivacy"]=="1") ? "selected" : "").">Friends Only</option>"
						."</select></td></tr>\n"
					."<tr><th>Status</th><th><select name=\"default_post_status\">"
						."<option value=\"0\" ".(($user_row["DefaultPostStatus"]=="0") ? "selected" : "").">Draft</option>"
						."<option value=\"1\" ".(($user_row["DefaultPostStatus"]=="1") ? "selected" : "").">Published</option>"
						."</select></td></tr>\n";

				$html .= "</table>\n"
					."<input type=\"submit\" value=\"Make Changes\" onclick=\"return validateAccountForm();\" />\n"
					."</form>\n";

			} // user_validated

			$html .= "</div> <!-- #account_form -->\n"
				."</div>\n";

		} else {
			// user not found
			header("Location: /404");
		}

		$html .= render_footer();

		return $html;
	} else {
		header("Location: /401");
	}
}


function render_account_style_page(){

	if (isset($_SESSION["user_id"])){

		$html = render_header("Style");

		$mysqli = db_connect();
		$user_result = $mysqli->query("SELECT * FROM Users WHERE Id=".$mysqli->real_escape_string($_SESSION["user_id"]));

		if ($user_result->num_rows > 0){

			$user_row = $user_result->fetch_assoc();

			$html .= "<div class=\"bg_menu_wrapper\">\n"
				."<ul class=\"bg_menu\">\n"
				."<li><a href=\"/account\" title=\"Account\">Account</a></li>\n"
				."<li class=\"selected\"><a href=\"/account/style\" title=\"Style\">Style</a></li>\n"
				."<li><a href=\"/account/friends\" title=\"Friends\">Friends</a></li>\n"
				."<li><a href=\"/account/followers\" title=\"Followers\">Followers</a></li>\n"
				."</ul>\n"
				."<div class=\"clear\"></div>\n"
				."</div>\n";
			
			$html .= "<div class=\"page_wrapper\">\n"
				."<div id=\"account_form\" class=\"page\">\n"
				."<h1>Style</h1>\n";

			
			$html .= "<form method=\"POST\" action=\"/api/account/style/update\" enctype=\"multipart/form-data\">\n"
				."<table border=\"0\" cellspacing=\"1\" cellpadding=\"5\">\n";
			
			// Style
			$html .= "<tr><th class=\"heading\" colspan=\"2\"><h3>CSS</h3><p>Use cascading style sheet code to modify the look of your posts.</p></th></tr>\n"
				."<tr><td colspan=\"2\"><textarea name=\"css\" rows=\"10\" style=\"font-family:'Courier New',Courier;\">".$user_row["CSS"]."</textarea></td></tr>\n";

			$html .= "</table>\n"
				."<input type=\"submit\" value=\"Make Changes\" />\n"
				."</form>\n";



			$html .= "</div> <!-- #account_form -->\n"
				."</div>\n";

		} else {
			// user not found
			header("Location: /404");
		}

		$html .= render_footer();

		return $html;
	} else {
		header("Location: /401");
	}
}



function render_account_friends_page($page){

	if (isset($_SESSION["user_id"])){

		$start = (intval($page) - 1) * 20;

		$html = render_header("Friends");

		$mysqli = db_connect();

		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/account\" title=\"Account\">Account</a></li>\n"
			."<li><a href=\"/account/style\" title=\"Style\">Style</a></li>\n"
			."<li class=\"selected\"><a href=\"/account/friends\" title=\"Friends\">Friends</a></li>\n"
			."<li><a href=\"/account/followers\" title=\"Followers\">Followers</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";

		$sql = "SELECT Users.*, COUNT(DISTINCT Posts.Id) AS PostCount, COUNT(DISTINCT Comments.Id) AS CommentCount, COUNT(DISTINCT Likes.Id) AS LikesCount,COUNT(DISTINCT Posts.Id) + COUNT(DISTINCT Comments.Id) + COUNT(DISTINCT Likes.Id) AS TotalCount\n"
			." FROM Users"
			." INNER JOIN Friends ON Users.Id=Friends.FriendId"
			." LEFT OUTER JOIN Posts ON Posts.UserId=Users.Id AND Posts.Status=1 AND Posts.Privacy=0"
			." LEFT OUTER JOIN Comments ON Posts.Id=Comments.PostId AND Comments.UserId<>Users.Id"
			." LEFT OUTER JOIN Likes ON Posts.Id=Likes.PostId AND Likes.UserId<>Users.Id"
			." WHERE Friends.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." GROUP BY Users.Id"
			." ORDER BY TotalCount DESC"
			." LIMIT ".$mysqli->real_escape_string($start).",20";

		$sql_count = "SELECT COUNT(DISTINCT Users.Id) AS NumUsers"
			." FROM Users"
			." INNER JOIN Friends ON Users.Id=Friends.FriendId"
			." WHERE Friends.UserId=".$_SESSION["user_id"];

		// fetch count for pagination
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumUsers"];
			
		$html .= "<div id=\"header\">\n"
			."<h1>You have ".$count." friends!</h1>\n"
			."<p>This page lists the people you have marked as a friend. To change your relationship with them, visit their profile page.</p>\n"
			."</div>\n";

		$user_result = $mysqli->query($sql);
			
		if ($user_result->num_rows > 0){
			$html .= "<div class=\"directory_users\">\n";
			while ($user_row =@ $user_result->fetch_assoc()){
				$html .= render_user($user_row);
			}
			$html .= "</div>\n";
		} else {
			$html .= "<div id=\"header\"><h3>You have not added anybody as a friend yet.</h3><p>Why not check out the <a href=\"/explore/directory\">Directory</a> to find some people to follow?</p></div>\n";
		}

		$html .= render_pagination("account/friends",$page,$count,20);

		$html .= "</div> <!-- .page -->\n"
			."</div> <!-- .page_wrapper -->\n";

		$html .= render_footer();

		return $html;

	} else {
		header("Location: /401");
	}

}


function render_account_followers_page($page){

	if (isset($_SESSION["user_id"])){

		$start = (intval($page) - 1) * 20;

		$html = render_header("Friends");

		$mysqli = db_connect();

		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/account\" title=\"Account\">Account</a></li>\n"
			."<li><a href=\"/account/style\" title=\"Style\">Style</a></li>\n"
			."<li><a href=\"/account/friends\" title=\"Friends\">Friends</a></li>\n"
			."<li class=\"selected\"><a href=\"/account/followers\" title=\"Followers\">Followers</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";

		$sql = "SELECT Users.*, COUNT(DISTINCT Posts.Id) AS PostCount, COUNT(DISTINCT Comments.Id) AS CommentCount, COUNT(DISTINCT Likes.Id) AS LikesCount,COUNT(DISTINCT Posts.Id) + COUNT(DISTINCT Comments.Id) + COUNT(DISTINCT Likes.Id) AS TotalCount\n"
			." FROM Users"
			." INNER JOIN Friends ON Users.Id=Friends.UserId"
			." LEFT OUTER JOIN Posts ON Posts.UserId=Users.Id AND Posts.Status=1 AND Posts.Privacy=0"
			." LEFT OUTER JOIN Comments ON Posts.Id=Comments.PostId AND Comments.UserId<>Users.Id"
			." LEFT OUTER JOIN Likes ON Posts.Id=Likes.PostId AND Likes.UserId<>Users.Id"
			." WHERE Friends.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." GROUP BY Users.Id"
			." ORDER BY TotalCount DESC"
			." LIMIT ".$mysqli->real_escape_string($start).",20";

		$sql_count = "SELECT COUNT(DISTINCT Users.Id) AS NumUsers"
			." FROM Users"
			." INNER JOIN Friends ON Users.Id=Friends.UserId"
			." WHERE Friends.FriendId=".$_SESSION["user_id"];

		// fetch count for pagination
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumUsers"];

		$html .= "<div id=\"header\">\n"
			."<h1>You have ".$count." followers!</h1>\n"
			."<p>This page lists the people that have marked you as a friend. To change your relationship with them, visit their profile page.</p>\n"
			."</div>\n";

		$user_result = $mysqli->query($sql);
			
		if ($user_result->num_rows > 0){
			$html .= "<div class=\"directory_users\">\n";
			while ($user_row =@ $user_result->fetch_assoc()){
				$html .= render_user($user_row);
			}
			$html .= "</div>\n";
		} else {
			$html .= "<div id=\"header\"><h3>Nobody has added you as a friend yet.</h3><p>Go explore the public posts, and get to know a few people :)</p></div>\n";
		}

		$html .= render_pagination("account/followers",$page,$count,20);

		$html .= "</div> <!-- .page -->\n"
			."</div> <!-- .page_wrapper -->\n";

		$html .= render_footer();

		return $html;

	} else {
		header("Location: /401");
	}

}


function render_profile_page_friends($username,$page){

	$html = "";
	
	$start = (intval($page) - 1) * 20;

	$mysqli = db_connect();

		if (isset($_SESSION["user_id"])){
		$sql = "SELECT Users.*,Friends.FriendId,FriendsB.FriendId AS FriendBId FROM Users"
			." LEFT OUTER JOIN Friends ON Friends.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Friends.FriendId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsB ON FriendsB.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendsB.UserId=Users.Id"
			." WHERE Username='".$mysqli->real_escape_string($username)."'";
	} else {
		$sql = "SELECT * FROM Users WHERE Username='".$mysqli->real_escape_string($username)."'";
	}

	$user_result = $mysqli->query($sql);

	if ($user_result->num_rows > 0){
	
		$user_row = $user_result->fetch_assoc();
		$user_id = $user_row["Id"];
		
		$html .= "<div class=\"profile_menu_wrapper\">\n"
			."<ul class=\"profile_menu\">\n"
			."<li><a href=\"/".$username."\" title=\"Posts\">Posts</a></li>\n"
			.(($user_row["ShowFriends"]==1) ? "<li class=\"selected\"><a href=\"/".$username."/friends\" title=\"Friends\">Friends</a></li>\n" : "")
			.(($user_row["ShowFriendOf"]==1) ? "<li><a href=\"/".$username."/followers\" title=\"Followers\">Followers</a></li>\n" : "")
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";

			
		$sql = "SELECT Users.*, COUNT(DISTINCT Posts.Id) AS PostCount, COUNT(DISTINCT Comments.Id) AS CommentCount, COUNT(DISTINCT Likes.Id) AS LikesCount,COUNT(DISTINCT Posts.Id) + COUNT(DISTINCT Comments.Id) + COUNT(DISTINCT Likes.Id) AS TotalCount\n"
			." FROM Users"
			." INNER JOIN Friends ON Users.Id=Friends.FriendId"
			." LEFT OUTER JOIN Posts ON Posts.UserId=Users.Id AND Posts.Status=1 AND Posts.Privacy=0"
			." LEFT OUTER JOIN Comments ON Posts.Id=Comments.PostId AND Comments.UserId<>Users.Id"
			." LEFT OUTER JOIN Likes ON Posts.Id=Likes.PostId AND Likes.UserId<>Users.Id"
			." WHERE Friends.UserId=".$mysqli->real_escape_string($user_id)
			." GROUP BY Users.Id"
			." ORDER BY TotalCount DESC"
			." LIMIT ".$mysqli->real_escape_string($start).",20";

		$sql_count = "SELECT COUNT(DISTINCT Users.Id) AS NumUsers"
			." FROM Users"
			." INNER JOIN Friends ON Users.Id=Friends.FriendId"
			." WHERE Friends.UserId=".$user_id;

		// fetch count for pagination
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumUsers"];
			
		$user_result = $mysqli->query($sql);
			
		if ($user_result->num_rows > 0){
			$html .= "<div class=\"directory_users\">\n";
			while ($user_row =@ $user_result->fetch_assoc()){
				$html .= render_user($user_row);
			}
			$html .= "</div>\n";
		} else {
			$html .= "<div id=\"header\"><h3>You have not added anybody as a friend yet.</h3><p>Why not check out the <a href=\"/explore/directory\">Directory</a> to find some people to follow?</p></div>\n";
		}

		$html .= render_pagination($username."/friends",$page,$count,20);

		$html .= "</div> <!-- .page -->\n"
			."</div> <!-- .page_wrapper -->\n";

		$html .= render_footer();

		return $html;
	}

}


function render_profile_page_followers($username,$page){

	$html = "";

	$start = (intval($page) - 1) * 20;

	$mysqli = db_connect();

	if (isset($_SESSION["user_id"])){
		$sql = "SELECT Users.*,Friends.FriendId,FriendsB.FriendId AS FriendBId FROM Users"
			." LEFT OUTER JOIN Friends ON Friends.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Friends.FriendId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsB ON FriendsB.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendsB.UserId=Users.Id"
			." WHERE Username='".$mysqli->real_escape_string($username)."'";
	} else {
		$sql = "SELECT * FROM Users WHERE Username='".$mysqli->real_escape_string($username)."'";
	}

	$user_result = $mysqli->query($sql);

	if ($user_result->num_rows > 0){
		
		$user_row = $user_result->fetch_assoc();
		$user_id = $user_row["Id"];
		
		$html .= "<div class=\"profile_menu_wrapper\">\n"
			."<ul class=\"profile_menu\">\n"
			."<li><a href=\"/".$username."\" title=\"Posts\">Posts</a></li>\n"
			.(($user_row["ShowFriends"]==1) ? "<li><a href=\"/".$username."/friends\" title=\"Friends\">Friends</a></li>\n" : "")
			.(($user_row["ShowFriendOf"]==1) ? "<li class=\"selected\"><a href=\"/".$username."/followers\" title=\"Followers\">Followers</a></li>\n" : "")
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";

		$sql = "SELECT Users.*, COUNT(DISTINCT Posts.Id) AS PostCount, COUNT(DISTINCT Comments.Id) AS CommentCount, COUNT(DISTINCT Likes.Id) AS LikesCount,COUNT(DISTINCT Posts.Id) + COUNT(DISTINCT Comments.Id) + COUNT(DISTINCT Likes.Id) AS TotalCount\n"
			." FROM Users"
			." INNER JOIN Friends ON Users.Id=Friends.UserId"
			." LEFT OUTER JOIN Posts ON Posts.UserId=Users.Id AND Posts.Status=1 AND Posts.Privacy=0"
			." LEFT OUTER JOIN Comments ON Posts.Id=Comments.PostId AND Comments.UserId<>Users.Id"
			." LEFT OUTER JOIN Likes ON Posts.Id=Likes.PostId AND Likes.UserId<>Users.Id"
			." WHERE Friends.FriendId=".$mysqli->real_escape_string($user_id)
			." GROUP BY Users.Id"
			." ORDER BY TotalCount DESC"
			." LIMIT ".$mysqli->real_escape_string($start).",20";

		$sql_count = "SELECT COUNT(DISTINCT Users.Id) AS NumUsers"
			." FROM Users"
			." INNER JOIN Friends ON Users.Id=Friends.UserId"
			." WHERE Friends.FriendId=".$user_id;

		// fetch count for pagination
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumUsers"];

		$user_result = $mysqli->query($sql);
			
		if ($user_result->num_rows > 0){
			$html .= "<div class=\"directory_users\">\n";
			while ($user_row =@ $user_result->fetch_assoc()){
				$html .= render_user($user_row);
			}
			$html .= "</div>\n";
		} else {
			$html .= "<div id=\"header\"><h3>Nobody has added you as a friend yet.</h3><p>Go explore the public posts, and get to know a few people :)</p></div>\n";
		}

		$html .= render_pagination($username."/followers",$page,$count,20);

		$html .= "</div> <!-- .page -->\n"
			."</div> <!-- .page_wrapper -->\n";

		$html .= render_footer();

		return $html;
	}

}
?>
