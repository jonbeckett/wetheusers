<?php

require_once("mail.php");

function user_login(){

	$username = $_REQUEST["username"];
	$password = $_REQUEST["password"];

	$enc_password = crypt($password,$username);

	// look for user in database
	$mysqli = db_connect();
	$sql = "SELECT * FROM Users WHERE Username='".$mysqli->real_escape_string($username)."' AND Password='".$mysqli->real_escape_string($enc_password)."'";
	$result = $mysqli->query($sql);
	if ($result->num_rows > 0){

		$row = $result->fetch_assoc();

		$_SESSION["user_id"] = $row["Id"];
		$_SESSION["user_name"] = $row["Username"];
		$_SESSION["user_email"] = $row["Email"];
		$_SESSION["user_status"] = $row["Status"];
		$_SESSION["user_avatar"] = $row["Avatar"];

		// set cookies
		$cookie_expiry = time() + (60*60*24*30); // 30 days
		setcookie("wetheusers_validation_code",$row["ValidationCode"],$cookie_expiry,"/");

		return true;

	} else {

		// user not found - login fails
		return false;

	}
}


function user_logout(){

	// unset session variables
	unset($_SESSION["user_id"]);
	unset($_SESSION["user_name"]);
	unset($_SESSION["user_email"]);
	unset($_SESSION["user_status"]);
	unset($_SESSION["user_avatar"]);

	// unset cookies
	$cookie_expiry = time() - 3600; // 1 hour ago
	setcookie("wetheusers_validation_code","",$cookie_expiry,"/");
	

}

function check_username($username){

	$reserved_words = array("account","friends","post","register","login","logoff","explore","support","firehose","tag","blog","staff","user","401","404","403","300","error","problem","api","admin","administrator","mailqueue","faq","terms","privacy","messagequeue");

	if (!in_array($username,$reserved_words)) {
		if (preg_match('/^[a-zA-Z0-9]+$/',$username)) {
			if (strlen($username)>3){
				return 1;
			} else {
				// not long enough (4 chars minimum)
				return -2;
			}
		} else {
			// contains extended chars
			return -3;
		}
	} else {
		// is a reserved word
		return -4;
	}

}

function user_register(){

	$username = (isset($_POST["username"])) ? $_POST["username"] : "";
	$email = (isset($_POST["email"])) ? $_POST["email"] : "";

	if ($username != "" && $email != ""){

		// check the username looks ok
		$result = check_username($username);
		if ( $result > 0){

			$mysqli = db_connect();

			// first check if the username or email already exists
			$result = $mysqli->query("SELECT Id FROM Users WHERE UPPER(Username)=UPPER('".$mysqli->real_escape_string($username)."') OR UPPER(Email)=UPPER('".$mysqli->real_escape_string($email)."')");

			if ($result->num_rows > 0){

				return -1;

			} else {

				$password = rand_string(10);
				$enc_password = crypt($password,$username);

				$validation_code = rand_string(10);
				
				$mysqli->query("INSERT INTO Users (Username,Password,Email,Status,ValidationCode,Created,IPCreated) VALUES ("
					."'".$mysqli->real_escape_string($username)."'"
					.",'".$mysqli->real_escape_string($enc_password)."'"
					.",'".$mysqli->real_escape_string($email)."'"
					.",".USER_STATUS_VALIDATED
					.",'".$mysqli->real_escape_string($validation_code)."'"
					.",NOW()"
					.",'".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."'"
					.")");

				$user_id = $mysqli->insert_id;
				
				// if we are still within 1000 users, give out the founder badge
				$count_sql = "SELECT COUNT(*) AS NumUsers FROM Users";
				$count_result = $mysqli->query($count_sql);
				if ($count_result->num_rows > 0){
					$count_row = $count_result->fetch_assoc();
					if(intval($count_row["NumUsers"])<1000){
						$mysqli->query("INSERT INTO Badges (UserId,BadgeId,Created) VALUES (".$mysqli->real_escape_string($user_id).",1,Now())");
					}
				}
									
				// send the validation code to the given email address
				$mail_to   = $email;
				$mail_subject = SITE_NAME." User Registration for ".$username;
				$mail_message = "Welcome to '".SITE_NAME."'!\n\n"
					."Here is your account information for future reference :\n\n"
					."Username : ".$username."\n"
					."Password : ".$password."\n"
					."Email  : ".$email."\n\n"
					."You can login at the following URL;\n\n"
					."  http://social.wetheusers.net/login\n\n"
					."We recommend that as soon as you login, you change your password in the account page (http://social.wetheusers.net/account) to something you will remember (but don't make it too easy!).\n\n"
					."You can see how you look to others via your profile page - http://social.wetheusers.net/".$username." - visit the account page to fill in your bio, upload a photo, and tag yourself so others can find you!\n\n"
					."If you have any questions about the site, there is growing FAQ page at http://social.wetheusers.net/faq.\n\n"
					."If you're having problems logging in, you might want to try a password reset (http://social.wetheusers.net/password_reset).\n\n"
					."Thankyou for trying out ".SITE_NAME."!\n";

				send_email($mail_to, $mail_subject, $mail_message, false);

				return $user_id;

			}
		} else {
			return $result;
		}
	} else {
		return -6;
	}

}


function password_reset(){

	$email = (isset($_POST["email"])) ? $_POST["email"] : "";

	if ($email != ""){

		$mysqli = db_connect();

		$users_result = $mysqli->query("SELECT * FROM Users WHERE Email='".$mysqli->real_escape_string($email)."'");

		if ($users_result->num_rows > 0 ) {

			$user_row = $users_result->fetch_assoc();

			$new_password = rand_string(10);
			$enc_password = crypt($new_password,$user_row["Username"]);

			// update the user record
			$mysqli->query("UPDATE Users SET Password='".$mysqli->real_escape_string($enc_password)."' WHERE Id=".$mysqli->real_escape_string($user_row["Id"]));

			// email the user
			$mail_to   = $email;
			$mail_subject = SITE_NAME." Password Reset for ".$email;
			$mail_message = "Your password has been reset - here are your details for future reference;\n\n"
				."Username : ".$user_row["Username"]."\n"
				."New Password : ".$new_password."\n"
				."Email  : ".$email."\n\n"
				."Visit http://social.wetheusers.net/login to login!\n\n";

			// record the reset password
			$mysqli->query("INSERT INTO PasswordResets (UserId,Password,Created,IPCreated) VALUES ("
				.$mysqli->real_escape_string($user_row["Id"]).","
				."'".$mysqli->real_escape_string($new_password)."',"
				."Now(),"
				."'".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."'"
				.")");
			
			send_email($mail_to, $mail_subject, $mail_message, false);

			return true;
		} else {
			return -1;
		}

	} else {
		return -2;
	}

}


function account_style_update(){

	$css = (isset($_POST["css"])) ? $_POST["css"] : "";

	$css = strip_tags($css);

	$sql = "UPDATE Users SET CSS='".$mysqli->real_escape_string($css)."' WHERE Id='".$mysqli->real_escape_string($_SESSION["user_id"])."'";

	$mysqli = db_connect();

	$result = $mysqli->query($sql);

	return $result;
	
}

function account_update(){

	$update_result = 1;

	$username = (isset($_POST["username"])) ? $_POST["username"] : "";
	$password = (isset($_POST["password"])) ? $_POST["password"] : "";
	$email = (isset($_POST["email"])) ? $_POST["email"] : "";
	$avatar = (isset($_POST["avatar"])) ? $_POST["avatar"] : "";
	$bio = (isset($_POST["bio"])) ? $_POST["bio"] : "";
	
	// social networks
	$twitter = (isset($_POST["twitter"])) ? $_POST["twitter"] : "";
	$facebook = (isset($_POST["facebook"])) ? $_POST["facebook"] : "";
	$tumblr = (isset($_POST["tumblr"])) ? $_POST["tumblr"] : "";
	$livejournal = (isset($_POST["livejournal"])) ? $_POST["livejournal"] : "";
	$googleplus = (isset($_POST["googleplus"])) ? $_POST["googleplus"] : "";
	$wordpress = (isset($_POST["wordpress"])) ? $_POST["wordpress"] : "";
	$blogger = (isset($_POST["blogger"])) ? $_POST["blogger"] : "";
	
	// instant messaging
	$im_friends_only = (isset($_POST["im_friends_only"])) ? $_POST["im_friends_only"] : "";
	$kik = (isset($_POST["kik"])) ? $_POST["kik"] : "";
	$google_talk = (isset($_POST["google_talk"])) ? $_POST["google_talk"] : "";
	$yahoo_messenger = (isset($_POST["yahoo_messenger"])) ? $_POST["yahoo_messenger"] : "";
	$msn_messenger = (isset($_POST["msn_messenger"])) ? $_POST["msn_messenger"] : "";
	$aol_instant_messenger = (isset($_POST["aol_instant_messenger"])) ? $_POST["aol_instant_messenger"] : "";
	$icq = (isset($_POST["icq"])) ? $_POST["icq"] : "";
	
	// notifications
	$notify_messages = (isset($_POST["notify_messages"])) ? $_POST["notify_messages"] : "";
	$notify_comments = (isset($_POST["notify_comments"])) ? $_POST["notify_comments"] : "";
	$notify_other_comments = (isset($_POST["notify_other_comments"])) ? $_POST["notify_other_comments"] : "";
	$notify_new_friends = (isset($_POST["notify_new_friends"])) ? $_POST["notify_new_friends"] : "";
	$notify_friends_posts = (isset($_POST["notify_friends_posts"])) ? $_POST["notify_friends_posts"] : "";
	$notify_likes = (isset($_POST["notify_likes"])) ? $_POST["notify_likes"] : "";

	$default_post_privacy = (isset($_POST["default_post_privacy"])) ? $_POST["default_post_privacy"] : "";
	$default_post_status = (isset($_POST["default_post_status"])) ? $_POST["default_post_status"] : "";

	$show_friends = (isset($_POST["show_friends"])) ? $_POST["show_friends"] : "";
	$show_friend_of = (isset($_POST["show_friend_of"])) ? $_POST["show_friend_of"] : "";
	$messages_friends_only = (isset($_POST["messages_friends_only"])) ? $_POST["messages_friends_only"] : "";
	$user_tags = (isset($_POST["tags"])) ? $_POST["tags"] : "";

	$clauses = array();

	if ($username != "" && $email != ""){

		if (check_username($username)){

			// first fetch the existing user record

			$mysqli = db_connect();
			$sql = "SELECT * FROM Users WHERE Id='".$mysqli->real_escape_string($_SESSION["user_id"])."'";
			$result = $mysqli->query($sql);

			if ($result->num_rows > 0){

				// check the new username is not already used
				// but ONLY do this if they have changed from the logged in session username
				$user_check = true;
				if ($username != $_SESSION["user_name"]){
					$result = $mysqli->query("SELECT Id FROM Users WHERE UPPER(Username)=UPPER('".$mysqli->real_escape_string($username)."')");
					if ($result->num_rows > 0) $user_check = false;
				}

				if ($user_check) {

					// if password has been reset we can change the username
					if ( strlen($password)>0 && ($username != $_SESSION["user_name"])){
				
						$cancel_validation = false;

						$clauses[] = "Username='".$mysqli->real_escape_string($username)."'";

					} else {
						if ($username != $_SESSION["user_name"]) $update_result = -7;
					}
					
					// only do any of this if we are still ok
					if ($update_result>=0){
					
						// if password has been entered, change it
						if ($password != ""){
							$enc_password = crypt($password,$username);
							$clauses[] = "Password='".$mysqli->real_escape_string($enc_password)."'";					
						}
						
						$clauses[] = "Email='".$mysqli->real_escape_string($email)."'";

						if ($_FILES["avatar"]["size"] > 0){

							$allowedExts = array("jpg", "jpeg", "gif", "png");
							$extension = strtolower(end(explode(".", $_FILES["avatar"]["name"])));
							if (($_FILES["avatar"]["size"] < (4096*1024)))
							{
								if (in_array($extension, $allowedExts)){

									$destination_filename = realpath("avatars")."/".$_SESSION["user_id"].".".$extension;
									$destination_filename_64 = realpath("avatars")."/".$_SESSION["user_id"]."_64.".$extension;
									if (file_exists($destination_filename)) unlink($destination_filename);
									if (file_exists($destination_filename_64)) unlink($destination_filename_64);

									move_uploaded_file($_FILES["avatar"]["tmp_name"],$destination_filename);

									// make a 64 pixel version
									include("resize_class.php");
									$resizeObj = new resize($destination_filename);
									$resizeObj -> resizeImage(64, 64, "crop");
									$resizeObj -> saveImage(realpath("avatars")."/".$_SESSION["user_id"]."_64.".$extension, 100);

									// remove the original
									if (file_exists(realpath($destination_filename))) {
										unlink(realpath($destination_filename));
									}

									$_SESSION["user_avatar"] = realpath("avatars")."/".$_SESSION["user_id"]."_64.".$extension;

									$clauses[] = "Avatar='avatars/".$_SESSION["user_id"]."_64.".$extension."'";
								} else {
									// wrong file extensin / format
									$update_result = -6;
								}
							} else {
								// file too big
								$update_result = -5;
							}
							
						}

						// Bio Text
						$clauses[] = "Bio=\"".$mysqli->real_escape_string($bio)."\"";

						// Social Network URLs
						$clauses[] = "Twitter='".$mysqli->real_escape_string($twitter)."'";
						$clauses[] = "Facebook='".$mysqli->real_escape_string($facebook)."'";
						$clauses[] = "Tumblr='".$mysqli->real_escape_string($tumblr)."'";
						$clauses[] = "GooglePlus='".$mysqli->real_escape_string($googleplus)."'";
						$clauses[] = "Wordpress='".$mysqli->real_escape_string($wordpress)."'";
						$clauses[] = "Blogger='".$mysqli->real_escape_string($blogger)."'";
						$clauses[] = "LiveJournal='".$mysqli->real_escape_string($livejournal)."'";
						
						// IM
						$clauses[] = ($im_friends_only != "") ? "IMFriendsOnly=".$mysqli->real_escape_string($im_friends_only) : "IMFriendsOnly=0";
						$clauses[] = "KIK='".$mysqli->real_escape_string($kik)."'";
						$clauses[] = "YahooMessenger='".$mysqli->real_escape_string($yahoo_messenger)."'";
						$clauses[] = "GoogleTalk='".$mysqli->real_escape_string($google_talk)."'";
						$clauses[] = "AOLInstantMessenger='".$mysqli->real_escape_string($aol_instant_messenger)."'";
						$clauses[] = "MSNMessenger='".$mysqli->real_escape_string($msn_messenger)."'";
						$clauses[] = "ICQ='".$mysqli->real_escape_string($icq)."'";
						

						$clauses[] = ($notify_messages != "") ? "NotifyMessages=".$mysqli->real_escape_string($notify_messages) : "NotifyMessages=0";
						$clauses[] = ($notify_comments != "") ? "NotifyComments=".$mysqli->real_escape_string($notify_comments) : "NotifyComments=0";
						$clauses[] = ($notify_other_comments != "") ? "NotifyOtherComments=".$mysqli->real_escape_string($notify_other_comments) : "NotifyOtherComments=0";
						$clauses[] = ($notify_new_friends != "") ? "NotifyNewFriends=".$mysqli->real_escape_string($notify_new_friends) : "NotifyNewFriends=0";
						$clauses[] = ($notify_friends_posts != "") ? "NotifyFriendsPosts=".$mysqli->real_escape_string($notify_friends_posts) : "NotifyFriendsPosts=0";
						$clauses[] = ($notify_likes != "") ? "NotifyLikes=".$mysqli->real_escape_string($notify_likes) : "NotifyLikes=0";

						$clauses[] = ($default_post_privacy != "") ? "DefaultPostPrivacy=".$mysqli->real_escape_string($default_post_privacy) : "DefaultPostPrivacy=0";
						$clauses[] = ($default_post_status != "") ? "DefaultPostStatus=".$mysqli->real_escape_string($default_post_status) : "DefaultPostStatus=0";

						$clauses[] = ($show_friends != "") ? "ShowFriends=".$mysqli->real_escape_string($show_friends) : "ShowFriends=0";
						$clauses[] = ($show_friend_of != "") ? "ShowFriendOf=".$mysqli->real_escape_string($show_friend_of) : "ShowFriendOf=0";

						$clauses[] = ($messages_friends_only != "") ? "MessagesFriendsOnly=".$mysqli->real_escape_string($messages_friends_only) : "MessagesFriendsOnly=1";
						
						$clauses[] = "Edited=Now()";
						$clauses[] = "IPEdited='".$mysqli->real_escape_string($_SERVER["REMOTE_ADDR"])."'";

						// join the clauses together to make the SQL update
						$sql_clauses = implode(",",$clauses);

						$sql = "UPDATE Users SET ".$sql_clauses." WHERE Id=".$mysqli->real_escape_string($_SESSION["user_id"]);
						$mysqli->query($sql);

						// reset session variables
						$_SESSION["user_name"] = $username;
						$_SESSION["user_email"] = $email;

						// remove the existing user tags
						$mysqli->query("DELETE FROM UserTags WHERE UserId=".$mysqli->real_escape_string($_SESSION["user_id"]));

						// break the tags up into individual terms
						$tags = explode(",",$user_tags);

						if (count($tags)>0){

							// trim all tags
							$tags = array_map("trim",$tags);

							foreach($tags as $tag){
								if ($tag != ""){
									$tag = strtolower($tag);

									$tag_id = 0;

									// find out if the tag exists
									$sql = "SELECT * FROM Tags WHERE Name='".$mysqli->real_escape_string($tag)."'";
									$result = $mysqli->query($sql);
									if ($result->num_rows > 0) {
										// if it does exist, get it's ID
										$row =@ $result->fetch_assoc();
										$tag_id = $row["Id"];
									} else {
										// if it does not exist, add it, and get the ID
										$sql = "INSERT INTO Tags (Name) VALUES ('".$mysqli->real_escape_string($tag)."')";
										$mysqli->query($sql);
										$tag_id = $mysqli->insert_id;
									}

									// add the tag to the UserTags list
									$mysqli->query("INSERT INTO UserTags (UserId,TagId,Created) VALUES (".$mysqli->real_escape_string($_SESSION["user_id"]).",".$mysqli->real_escape_string($tag_id).",Now())");
								}
							}

						} // end tags section
					}
				} else {
					// username is already used
					$update_result = -4;
				}
			} else {
				// cannot find record
				$update_result = -3;
			}
		} else {
			// username does not pass checks
			$update_result = -2;
		}

	} else {
		// missing form info
		$update_result = -1;
	}
	return $update_result; 
}




?>
