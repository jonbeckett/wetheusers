<?php
function render_privacy(){
	$html = render_header("Privacy Policy");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div id=\"faq\" class=\"page\">\n"
		."<h1>Privacy Policy</h1>\n"
		."<br />\n";

	$html .= file_get_contents("lib/html/privacy.htm");
		
	$html .= "</div></div>\n";
		
	$html .= render_footer();
	return $html;
}
?>