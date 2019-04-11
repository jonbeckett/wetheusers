<?php
function render_faq(){
	$html = render_header("Frequently Asked Questions");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div id=\"faq\" class=\"page\">\n"
		."<h1>Frequently Asked Questions</h1>\n"
		."<br />\n"
		."<p>This page will be updated regularly with answers to common questions. If you don't find an answer to your question, send an email to <a href=\"mailto:support@wetheusers.net\">support@wetheusers.net</a> and we'll do our best to answer quickly.</p>\n";

	$html .= file_get_contents("lib/html/faq.htm");
		
	$html .= "</div></div>\n";
		
	$html .= render_footer();
	return $html;
}
?>
