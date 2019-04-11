<?php

function import_rss(){

	if (isset($_SESSION["user_id"])){

		$url = (isset($_POST["url"])) ? $_POST["url"] : "";
		$privacy = (isset($_POST["privacy"])) ? $_POST["privacy"] : "";
		
		$html = "Foo";

		if ($url != ""){
		
			$file_content = file_get_contents($url);
			$xml = new SimpleXmlElement($file_content);

			$html = "Foo";

			$mysqli = db_connect();

			foreach($xml->channel->item as $post) {

				/*
				$mysqli->query("INSERT INTO Posts (UserId,Title,Body,Privacy,Status,Created,IPCreated) VALUES ("
					."'".$mysqli->real_escape_string($_SESSION["user_id"])."',"
					."'".$mysqli->real_escape_string($post_title)."',"
					."'".$mysqli->real_escape_string($post_body)."',"
					."'".$mysqli->real_escape_string($post_privacy)."',"
					."'".$mysqli->real_escape_string($post_status)."',"
					."NOW(),"
					."'".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."'"
					.")");
				*/

				$html .= "<pre>";
				$html .= print_r($post,false);
				$html .= "</pre>";
				$html .= "<br />";
			}



			$html .= "";
		
		} else {
			$html = "<p>URL Not Set</p>";
		}
		
		print $html;
	}
}
?>
