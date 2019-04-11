<?php

function render_header($page_title,$description="Post, Friend, Follow, Like, Comment - Welcome to a new social experience on the internet!",$rss=false){

	global $CSS;

	$stylesheet_file = "desktop.css";
	$javascript_file = "desktop.js";

	if ($CSS == "desktop") {
		$stylesheet_file = "desktop.css";
		$javascript_file = "desktop.js";
	}

	if ($CSS == "mobile") {
		$stylesheet_file = "mobile.css";
		$javascript_file = "mobile.js";
	}

	if (strpos($_SERVER["HTTP_USER_AGENT"],"Nexus 7") || strpos($_SERVER["HTTP_USER_AGENT"],"iPad")){
		$CSS = "tablet";
		$stylesheet_file = "tablet.css";
		$javascript_file = "mobile.js";
	}

	if (isset($_GET["css"])) $_SESSION["css"] = $_GET["css"];
	if (isset($_SESSION["css"])) {
		$stylesheet_file = $_SESSION["css"].".css";
	}
	
	
	$title = ($page_title != "Home") ? $page_title." - ".SITE_NAME : SITE_NAME;

	$html = "<html>\n"
		."<head>\n"
		."  <title>".$title."</title>\n"
		."  <meta name=\"description\" content=\"".$description."\" />\n"
		.(($rss==true) ? "  <link rel=\"alternate\" type=\"application/rss+xml\" title=\"".$page_title." - ".SITE_NAME."\" href=\"http://wetheusers.net".$_SERVER["REQUEST_URI"]."/rss\" />\n" : "")
		."  <link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:400,700,900,400italic' rel='stylesheet' type='text/css'>\n"
		."  <link rel=\"stylesheet\" href=\"/css/".$stylesheet_file."?v=2012-11-30\" />\n"
		."  <link rel=\"icon\" type=\"image/png\" href=\"/img/picture_empty.png\" />\n"
		."  <script type=\"text/javascript\" src=\"/js/jquery-1.8.0.min.js\"></script>\n"
		.(($CSS=="desktop") ? "  <script type=\"text/javascript\" src=\"/js/isotope.js\"></script>\n" : "")
		.(($CSS=="desktop") ? "  <script type=\"text/javascript\" src=\"/js/freetile.min.js\"></script>\n" : "")
		."  <script type=\"text/javascript\" src=\"/js/core.js?20140610\"></script>\n"
		."  <script type=\"text/javascript\" src=\"/js/".$javascript_file."?20140619\"></script>\n"
		.(($CSS=="mobile" || $CSS=="tablet") ? "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0\"/>\n" : "")
		."  <meta http-equiv=\"content-type\" content=\"text/html;charset=UTF-8\">\n"
		."</head>\n"
		."<body>\n";


	// draw the menu
	if (isset($_SESSION["user_id"])){

		// logged in

		// find out how unread many messages we have
		$mysqli = db_connect();

		$messages_result = $mysqli->query("SELECT COUNT(*) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND ReadFlag=0");
		$message_row = $messages_result->fetch_assoc();
		$message_count = ($message_row["NumMessages"] > 0) ? " (".$message_row["NumMessages"].")" : "";

		$avatar_image = ($_SESSION["user_avatar"] != "") ? $_SESSION["user_avatar"] : "avatars/generic_64.jpg";

		$html .= "<div class=\"top-menu-wrapper\">\n"
			."<ul class=\"top-menu\">\n"
			."<li class=\"sitename\"><a href='/' title=\"WeTheUsers\">WeTheUsers</a></li>\n"
			."<li><a href=\"/post/add\" title=\"Compose\">Compose</a></li>\n"
			."<li><a href=\"/explore\" title=\"Explore\">Explore</a></li>\n"
			."<li><a href=\"/messages\" title=\"Messages\">Messages".$message_count."</a></li>\n"
			."<li><a href=\"/chat\" title=\"Chat\">Chat</a></li>\n"
			."<li><a href=\"/".$_SESSION["user_name"]."\" title=\"Profile\">Profile</a></li>\n"
			."</ul> <!-- .top-menu -->\n"
			."<div class=\"clear\"></div>\n"
			."</div> <!-- .top-menu-wrapper -->\n";
/*
			."<table align=\"center\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\" class=\"user\"><tr>"
				."<td><a href=\"/".$_SESSION["user_name"]."\" title=\"".$_SESSION["user_name"]."\"><img src=\"/".$avatar_image."\" width=\"24\" height=\"24\" border=\"0\" /></a></td>"
				."<td><a href=\"/".$_SESSION["user_name"]."\" title=\"".$_SESSION["user_name"]."\">".$_SESSION["user_name"]."</a></td>"
			."</tr></table>"
*/

	} else {

		// logged out

		$html .= "<div class=\"top-menu-wrapper\">\n"
			."<ul class=\"top-menu\">\n"
			."<li class=\"sitename\"><a href='/' title=\"WeTheUsers\">WeTheUsers</a></li>\n"
			."<li><a href=\"/login\" title=\"Login\"><strong>Login</strong></a></li>\n"
			."<li><a href=\"/register\" title=\"Register\"><strong>Register</strong></a></li>\n"
			."<li><a href=\"/explore\" title=\"Explore\">Explore</a></li>\n"
			."</ul> <!-- .top-menu -->\n"
			."<div class=\"clear\"></div>\n"
			."</div> <!-- .top-menu-wrapper -->\n";


	}

	
	$html .= "<div id=\"container\">\n";
	
		
	return $html;
}

function render_display_controls(){
	global $CSS;
	if ($CSS == "desktop" && isset($_SESSION["user_id"])){
		return "<ul class=\"site_controls\">\n"
			."<li class=\"button\"><a href=\"/api/preferences/tilemode\">Tile Mode</a></li>\n"
			."<li class=\"button\"><a href=\"/api/preferences/listmode\">List Mode</a></li>\n"
			."</ul>\n";
	}
}

function render_footer(){

	global $CSS;

	$html = "";

	$html .= "<div id=\"footer\">\n"
		."<p><a href=\"http://wetheusers.net\" title=\"WeTheUsers\"><span style='font-size:1.2em !important;'><strong>WeTheUsers</strong></span></a><br />"
		.((!isset($_SESSION["user_id"])) ? "Post, Friend, Follow, Like, Comment - <a href=\"/register\">Register now</a> and take part in a new social experience on the internet." : "Post, Friend, Follow, Like, Comment")
		."</p>\n"
		."<p><small>"
		."&copy; ".SITE_NAME.", All Rights Reserved."
		." | <a href=\"/terms\">Terms and Conditions</a>"
		." | <a href=\"/privacy\">Privacy Policy</a>"
		." | <a href=\"/faq\">Frequently Asked Questions</a>"
		.((isset($_SESSION["user_id"])) ? " | <a href=\"/api/user/logout\" title=\"Logout\" onclick=\"return confirm('Are you sure you want to logout?');\">Logout</a>" : "")
		."</small></p>\n"
		."</div> <!-- #footer -->\n"
		."</div> <!-- #container -->\n"
		."<script>(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)})(window,document,'script','//www.google-analytics.com/analytics.js','ga');ga('create', 'UA-40768670-11', 'wetheusers.net');ga('send', 'pageview');</script>\n"
		."<script src=\"//my.stats2.com/js\" type=\"text/javascript\"></script><script type=\"text/javascript\">try{ stats2.init(100739401); }catch(e){}</script><noscript><p><img alt=\"Stats2\" width=\"1\" height=\"1\" src=\"//my.stats2.com/100739401ns.gif\" /></p></noscript>\n"
		."</body>\n"
		."</html>\n";

		

	return $html;
}



function render_404_page(){

	$html = render_header("404 - Not Found")
		."<div class=\"page_wrapper\">\n"
		."<div class=\"page\">\n"
		."<h1>Page Not Found</h1>\n"
		."<p>The page you were looking for was not found.</p>\n"
		."<p><small>Seeing as you are here anyway, why not check out some of the following;</small></p>\n"
		."<p><strong><a href=\"/explore/firehose\">The Firehose</a></strong><br /><small>The torrent of content flowing through the Userverse</small></p>\n"
		."<p><strong><a href=\"/explore/popular\">Popular Content</a></strong><br /><small>Popular content from the last few days</small></p>\n"
		."<p><strong><a href=\"/explore/tags\">Tags</a></strong><br /><small>Tags used by content in the last few days</small></p>\n"
		."<p><strong><a href=\"/explore/directory\">Directory</a></strong><br /><small>The directory of users within the WeTheUserverse.</small></p>\n"
		."</div>\n"
		."</div>\n"
		.render_footer();

	return $html;
}



function render_401_page(){

	$html = render_header("401 - Unauthorised")
		."<div class=\"page_wrapper\">\n"
		."<div class=\"page\">\n"
		."<h1>Unauthorised</h1>\n"
		."<p>You have tried to access something you are unauthorised for.</p>\n"
		."</div>\n"
		."</div>\n"
		.render_footer();

	return $html;
}





function render_help_page(){

	$html = render_header("Help");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div class=\"page\">\n"
		."<h1>Help</h1>\n"
		."<p>In the very likely event that you find something that doesn't work within the site, please send us an email and try to be as descriptive as possible about what you were doing, and what the site did.</p>\n"
		."<p><a href=\"mailto:support@wetheusers.net\">support@wetheusers.net</a></p>\n"
		."<p>Thankyou for your support!</p>\n"
		."</div>\n"
		."</div>\n";

	$html .= render_footer();

	return $html;
}

function render_pagination($url,$page,$count,$per_page){

	// scenarios
	//                ^^
	// 01 ..          15          20  Mid Page
	// 01 02 03 04 05 06 07 08 09 10  Normal
	// -- -- -- -- -- -- -- -- -- --

	$html = "<div class=\"pagination\"><ul>\n";

	$j=0;

	if ( ceil($count/$per_page) <= 10){

		// less than or equal to 10 pages - show them all

		for( $i=0 ; $i < $count ; $i = $i + $per_page){
			$j++;
			$selected = ($j==$page) ? "selected" : "";
			$html .= "<li><a href=\"/".$url."/".$j."\" class=\"".$selected."\">".$j."</a></li>\n";
		}

	} else {

		// more than ten pages - base the range on the $page variable, and count 3 each way, and then end markers
		// page = 8  (of 16)   start at 5, end at 11
		$start_page = $page - 3;
		$end_page = $page + 3;

		$num_pages = ceil($count/$per_page);

		// middle of range (further than 3 away from the start or end)
		if (($page>3) && ($page<($num_pages-3))){

			$html .= "<li><a href=\"/".$url."/1\" class=\"".(($page==1) ? "selected" : "")."\">1</a></li>\n"
				."<li>...</li>\n";
			for ($i=$start_page;$i<=$end_page;$i++){
				$html .= "<li><a href=\"/".$url."/".$i."\" class=\"".(($i==$page) ? "selected" : "")."\">".$i."</a></li>\n";
			}
			$html .= "<li>...</li>\n"
				."<li><a href=\"/".$url."/".$num_pages."\" class=\"".(($page==$num_pages) ? "selected" : "")."\">".$num_pages."</a></li>\n";
		}

		if ($page<=3){
			// show first 8, followed by space, then end marker
			for ($i=1;$i<=8;$i++){
				$html .= "<li><a href=\"/".$url."/".$i."\" class=\"".(($i==$page) ? "selected" : "")."\">".$i."</a></li>\n";
			}
			$html .= "<li>...</li>\n"
				."<li><a href=\"/".$url."/".$num_pages."\" class=\"".(($page==$num_pages) ? "selected" : "")."\">".$num_pages."</a></li>\n";
		}

		if ($page >= ($num_pages - 3)){
			// show the first page, followed by space, then 8 left
			$html .= "<li><a href=\"/".$url."/1\" class=\"".(($page==1) ? "selected" : "")."\">1</a></li>\n"
				."<li>...</li>\n";
			for ($i=$num_pages-8;$i<=$num_pages;$i++){
				$html .= "<li><a href=\"/".$url."/".$i."\" class=\"".(($i==$page) ? "selected" : "")."\">".$i."</a></li>\n";
			}

		}

	}



	$html .= "</ul></div>\n";

	return $html;
}


?>
