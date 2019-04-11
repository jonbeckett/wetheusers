<?php

function render_chat_page(){

	if (isset($_SESSION["user_id"])){

		$html = render_header("Chat");
		
		$html .= "<div id=\"header\">\n"
			."<h1>Chat</h1>\n"
			."<p>IRC Chat powered by <a href=\"http://mibbit.com\">Mibbit</a> (server = irc.mibbit.com, channel = #WeTheUsers)</p>\n"
			."</div>";

		$html .= "<iframe style=\"display:block;margin:0px auto 0px auto;\" frameborder=\"0\" width=\"90%\" height=\"80%\" scrolling=\"no\" src=\"http://widget.mibbit.com/?settings=9092067ea4c785ce94d25452be90e031&server=irc.mibbit.net&channel=%23WeTheUsers&nick=".$_SESSION["user_name"]."\"></iframe>";
			
		$html .= render_footer();

		return $html;
		
	} else {
		header("Location: /403");
	}

}

?>
