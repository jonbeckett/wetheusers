<?php

require_once("class.phpmailer.php");
$mailer = new PHPMailer();

$mailer->Host = EMAIL_HOST;
$mailer->Port = EMAIL_PORT;
$mailer->SMTPSecure = EMAIL_SECURE;
$mailer->Username = EMAIL_USERNAME;
$mailer->Password = EMAIL_PASSWORD;
$mailer->SetFrom(EMAIL_REPLY_ADDRESS, SITE_NAME);

function send_email($to,$subject,$body,$queue=true){

	global $mailer;
		
	if (!$queue) {

		$mailer->LE = "\n";
		$mailer->ClearAddresses();
		$mailer->Subject = $subject;
		$mailer->Body = $body;
		$mailer->AddAddress($to, $to);
		$mailer->Send();
		
	} else {
		
		$mysqli_mail = db_connect();
		
		$sql = "INSERT INTO MailQueue (`From`,`To`,`Subject`,`Body`) VALUES ("
			."'".$mysqli_mail->real_escape_string(SITE_NAME)."',"
			."'".$mysqli_mail->real_escape_string($to)."',"
			."'".$mysqli_mail->real_escape_string($subject)."',"
			."'".$mysqli_mail->real_escape_string($body)."'"
			.")";
		
		$mysqli_mail->query($sql);
		
	}
	
}



?>
