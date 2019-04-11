<?php

function render_mail_queue(){

	$mysqli = db_connect();

	$html = render_header("Mail Queue");
	
	$mysqli = db_connect();
	
	$sql = "SELECT * FROM MailQueue ORDER BY Id";
	
	$result = $mysqli->query($sql);
	
	$html .= "<br/><table border='1' cellspacing='1' cellpadding='5' width='80%' style='margin:auto;'>\n";
	
	$i=0;
	
	while ($row =@ $result->fetch_assoc()){
	
		$i++;
		
		if ($i==1) {
			$html .= "<tr>\n";
			foreach ($row as $key=>$val){
				$html .= "<th>".$key."</th>\n";
			}
			$html .= "</tr>\n";
		}
		
		$html .= "<tr>\n";
		foreach ($row as $key=>$val){
			$html .= "<td>".$val."</td>\n";
		}
		$html .= "</tr>\n";
	}
	
	$html .= "</table>\n";

	$html .= "<p align='center'>".$i." items waiting in the queue.</p>\n";
	
	$html .= render_footer();
	
	return $html;
}

?>