<?php
require_once("mail.php");


function process_mail_queue(){

	// get the mail from the mail queue and send it

	$mysqli = db_connect();

	$sql = "SELECT * FROM MailQueue ORDER BY Id";

	$result = $mysqli->query($sql);

	$i = 0;

	while ($mail_item =@ $result->fetch_assoc()){

		$i++;
		
		// prepare
		$mail_id = $mail_item["Id"];
		$mail_from = $mail_item["From"];
		$mail_to = $mail_item["To"];
		$mail_subject = $mail_item["Subject"];
		$mail_body = $mail_item["Body"];
		
		// send the email
		send_email($mail_to, $mail_subject, $mail_body ,false);
		
		// delete the mail queue item
		$mysqli->query("DELETE FROM MailQueue WHERE Id=".$mail_id);
		
	}

	print $i." items processed";
}

?>
