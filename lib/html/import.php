<?php



function render_import_menu(){

	$html = render_header("Directory");

	$html .= "<div class=\"bg_menu_wrapper\">\n"
		."<ul class=\"bg_menu\">\n"
		."<li class=\"selected\"><a href=\"/account/import\" title=\"Import\">Import</a></li>\n"
		."<li><a href=\"/account/import/rss\" title=\"RSS\">RSS</a></li>\n"
		."<li><a href=\"/account/import/wordpress\" title=\"Wordpress\">Wordpress</a></li>\n"
		."</ul>\n"
		."<div class=\"clear\"></div>\n"
		."</div>\n";

	$html .= "<div id=\"header\">\n"
		."<h1>Import</h1>\n"
		."<p>Choose the type of import you want to perform;</p>\n"
		."<p><button onClick=\"document.location.href='/account/import/rss';\">RSS Import</button></p>\n"
		."<p><button onClick=\"document.location.href='/account/import/wordpress';\">Wordpress Import</button></p>\n"
		."</div>\n";

	$html .= render_footer();

	return $html;
}

function render_import_rss(){

	if (isset($_SESSION["user_id"])){

		// fetch the user row from the database to get the defaults from it
		$mysqli = db_connect();
		$user_result = $mysqli->query("SELECT * FROM Users WHERE Id=".$mysqli->real_escape_string($_SESSION["user_id"]));
		$user_row = $user_result->fetch_assoc();

		$html = render_header("Directory");

		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/account/import\" title=\"Import\">Import</a></li>\n"
			."<li class=\"selected\"><a href=\"/account/import/rss\" title=\"RSS\">RSS</a></li>\n"
			."<li><a href=\"/account/import/wordpress\" title=\"Wordpress\">Wordpress</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";

		$html .= "<div id=\"header\">\n"
			."<h1>Import RSS</h1>\n"
			."<p>Fill out the form below to import an RSS feed. This is a one-hit operation - it will not update in the future, and running it repeatedly will duplicate posts.</p>\n"
			."</div>\n";

		$html .= "<div class=\"page_wrapper\">\n"
			."<div id=\"account_form\" class=\"page\">\n"

			."<form method=\"POST\" action=\"/api/account/import_feed/rss\">\n"
			."<table border=\"0\" cellspacing=\"1\" cellpadding=\"5\">\n"
			."<tr><th class=\"heading\" colspan=\"2\"><h3>RSS Feed</h3><p>Enter the full URL of the RSS feed, and the privacy of imported posts.</p></th></tr>\n"
			."<tr><th>Feed URL</th><td><input type=\"text\" name=\"url\" /></td></tr>\n"
			."<tr><th>Privacy</th><td><select name=\"privacy\">\n"
			."  <option value=\"0\" ".(($user_row["DefaultPostPrivacy"]==0) ? "selected" : "")." >Public</option>\n"
			."  <option value=\"1\" ".(($user_row["DefaultPostPrivacy"]==1) ? "selected" : "")." >Friends Only</option>\n"
			."</select></td></tr>\n"
			."<tr><td colspan='2' align='right'><input type='submit' value='Import' /></td></tr>\n"
			."</table>\n"
			."</div> <!-- #account_form -->\n"
			."</div> <!-- .page_wrapper -->\n";




		$html .= render_footer();

		return $html;

	} else {
		header("Location: /403");
	}

}

?>
