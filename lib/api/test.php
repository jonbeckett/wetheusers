<?

require_once("mail.php");

function test_email(){
	$mail_to = "jonathan.beckett@gmail.com";
	$mail_subject = "Test Email";
	$mail_message = "This is a test";
	
	send_email($mail_to, $mail_subject, $mail_message, true);
}

?>
