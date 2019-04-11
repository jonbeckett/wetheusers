<?php
function render_terms(){
	$html = render_header("Terms and Conditions");

	$html .= "<div class=\"page_wrapper\">\n"
		."<div id=\"faq\" class=\"page\">\n"
		."<h1>Terms and Conditions</h1>\n"
		."<br />\n";

	$html .= file_get_contents("lib/html/terms.htm");
		
	$html .= "</div></div>\n";
		
	$html .= render_footer();
	return $html;
}
?>