<?php
function render_welcome_page(){
	$html = render_header("Welcome to ".SITE_NAME."!");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div id=\"faq\" class=\"page\">\n"
		."<h1>Welcome!</h1>\n"
		."<h2 class=\"center\">Go check your email!</h2>"
		."<br />\n";

	$html .= "<p>Your account has been successfully created, and a temporary password has been emailed to you that you can change later. Please note that some online email systems (we have noticed Yahoo and Outlook.com are the worst offenders) sometimes take a few minutes to receive machine generated emails. Gmail seems to be fine.</p>\n"
		."<p>Once you have the email, head to the <a href=\"/login\">Login</a> page. Remember to check your spam folder if you don't think the email has arrived.</p>\n"
		."<p>If you do not received the password, you can use the password reset form to have another one sent.</p>\n"
		."<p>If you suspect you may have typed your email address incorrectly, please get in touch with <a href=\"mailto:support@wetheusers.net\">support@wetheusers.net</a>, and we'll help you out.</p>\n";
		
	$html .= "</div></div>\n";
		
	$html .= render_footer();
	return $html;
}
?>
