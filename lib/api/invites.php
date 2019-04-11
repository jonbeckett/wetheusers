<?php 

require_once("mail.php");

function send_invites(){

	$invites_sent = 0;

	$mysqli = db_connect();

	$raw_data = (isset($_POST["raw_data"])) ? $_POST["raw_data"] : "";

	if ($raw_data != ""){
		
		$pattern = "/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}/";

		// preg_match_all returns an associative array
		preg_match_all($pattern, $raw_data, $matches);

		// the data you want is in $matches[0], dump it with var_export() to see it

//		print_r($matches[0]);
		foreach($matches[0] as $email_address){
			

			$mail_to      = $email_address;
			$mail_subject = "Invitiation to join ".SITE_NAME;

			$mail_message = "Your friend ".$_SESSION["user_name"]." has invited you to join WeTheUsers - a free online social blogging platform.\n\n"
				."Here's a quick rundown of some of the features;\n\n"
				." - It's free to join, and there's no advertising in the site AT ALL\n"
				." - You can browse and search everybody elses posts in all sorts of ways\n"
				." - You can modify the CSS of your own profile page, and posts\n"
				." - Posts use markdown syntax (no broken rich text editors!)\n"
				." - You can post some posts friends only\n"
				." - You can comment, and reply to comments!\n"
				." - You can private message each other - and read the thread of messages\n"
				." - You can chat in real time with one another\n"
				." - You can add yourself to a directory of users to make yourself discoverable\n"
				." - You can browse the firehose of everybody's content really easily\n"
				." - The mobile interface is beautiful on phones and tablets\n"
				." - You can choose to have as many, or as few events wired up to send email notifications as you want - and still not miss notifications even if you switch email notifications off - the site records them for you anyway!\n\n"
 				."Visit http://wetheusers.net in your browser, and try it out today!\n";

			send_email($mail_to, $mail_subject, $mail_message, true);

		}

	}

	return $invites_sent;

}


?>
