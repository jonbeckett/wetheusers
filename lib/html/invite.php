<?php
function invite_page(){


	$html = render_header("Invite");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div id=\"invite\" class=\"page\">\n"
		."<h1>Invite</h1>\n"
		."<br />\n";

	if (isset($_SESSION["user_id"])){

		$html .= "<p>Use this page to invite people into the system - just paste text that includes email addresses into the box below, and hit Go - you don't have to clean the text up - the site will find all the valid looking email addresses for you.</p>\n";
		$raw_data = (isset($_POST["raw_data"])) ? $_POST["raw_data"] : "";

		if (strpos($_SERVER["REQUEST_URI"],"finished")>0) {

			$html .= "<br /><br /><h1>Finished!</h1><p>Your email invites have been sent.</p>\n\n";

		} else {

			$html .= "<form method=\"POST\" action=\"/api/invite\">\n"
				."<textarea id=\"raw_data\" name=\"raw_data\" rows=\"10\" style=\"width:100%;\">\n"
				.$raw_data
				."</textarea>\n"
				."<input type=\"submit\" value=\"Go!\" />\n"
				."</form>\n"
				."<br /><p><small>WeTheUsers does not record invited email addresses.</small></p>\n";

		}
	} else {
		$html .= "<p>You must be logged in to use this feature.</p>\n";
	}

	$html .= "</div></div>\n";
		
	$html .= render_footer();
	return $html;
}
?>
