<?php

// error_reporting(E_ALL);
// ini_set('display_errors', '1');

require_once("lib/common.php");

$url = $_SERVER["REQUEST_URI"];
$url_parts = explode("/",$url);

switch($url_parts[1]){

	case "":
		require_once("lib/html/common.php");
		require_once("lib/html/posts.php");
		require_once("lib/html/home.php");
		
		if (count($url_parts)>1){
			if (count($url_parts)>2){
				// e.g. /numposts/page
				print render_home_page($url_parts[1],$url_parts[2]);
			} else {
				// e.g. /numposts
				if ($url_parts[1]=="") $url_parts[1] = POSTS_PER_PAGE;
				print render_home_page($url_parts[1],1);
			}			
		} else {
			// e.g. ""
			print render_home_page(POSTS_PER_PAGE,1);
		}
		
		break;

	case "api":
		switch ($url_parts[2]){
			case "cron":
				require_once("lib/api/cron.php");
				process_mail_queue();
				break;
			case "testmail":
				require_once("lib/api/test.php");
				test_email();
				break;
			case "invite":
				require_once("lib/api/invites.php");
				send_invites();
				header("Location: /invite/finished");
				break;
			case "preferences":
				require_once("lib/api/preferences.php");
				switch ($url_parts[3]){
					case "listmode":
						preferences_list_mode();
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
					case "tilemode":
						preferences_tile_mode();
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
				}
				break;
			case "user":
				require_once("lib/api/users.php");
				switch ($url_parts[3]){
					case "register":
						$result = user_register();
						if ($result > 0) {
							header("Location: /welcome");
						} else {
							header("Location: /register/failure/".$result);
							//print $result;
						}
						break;
					case "logout":
						user_logout();
						header("Location: /");
						break;
					case "login":
						if (user_login()) {
							header("Location: /".$_SESSION["user_name"]);
						} else {
							header("Location: /login/failure");
						}
						break;
					case "password_reset":
						$result = password_reset();
						if ($result > 0){
							header("Location: /password_reset/success/".$result);
						} else {
							header("Location: /password_reset/failure/".$result);
						}
						break;
					default:
						header("Location: /404");
						break;
				}
				break; // user
				
				
			case "account":
				require_once("lib/api/users.php");
				switch($url_parts[3]){
					case "update":
						$result = account_update();
						if ($result > 0) {
							header("Location: /account/success");
						} else {
							header("Location: /account/failure/".$result);
						}
						break; // update
					case "import_feed":
						require_once("lib/api/import_feed.php");
						switch($url_parts[4]){
							case "rss":
								$result = import_rss();
								break; //rss
							case "wordpress":
								$result = import_wordpress();
								break; //wordpress
						}
						break; // import
					case "style":
						require_once("lib/api/import_feed.php");
						switch($url_parts[4]){
							case "update":
								$result = account_style_update();
								header("Location: /account/style");
								break;
						}
						break;
				}
				break; // account
			
			case "post":
				require_once("lib/api/posts.php");
				require_once("lib/api/likes.php");
				switch($url_parts[3]){
					case "add":
						$result = post_add();
						if ($result>-1){
							header("Location: /post/".$result);
						} else {
							header("Location: /post/add/failure/".$result);
						}
						break;
					case "edit":
						$result = post_edit();
						if ($result > -1){
							header("Location: /post/".$result);
						} else {
							header("Location: /post/edit/".$_POST["id"]."/failure");
						}
						break;
					case "like":
						$result = post_like($url_parts[4]);
						header('Content-Type: application/json');
						print json_encode($result);
						break;
					case "unlike":
						$result = post_unlike($url_parts[4]);
						header('Content-Type: application/json');
						print json_encode($result);
						break;
					case "delete":
						$result = post_delete($url_parts[4]);
						header("Location: /".$_SESSION["user_name"]);
						break;
				}
				break; // post
			case "comment":
				require_once("lib/api/comments.php");
				require_once("lib/api/likes.php");
				switch($url_parts[3]){
					case "add":
						$result = comment_add();
						header("Location: ".$_SERVER["HTTP_REFERER"]."#comments");
						break;
					case "delete":
						$result = comment_delete($url_parts[4]);
						header("Location: ".$_SERVER["HTTP_REFERER"]."#comments");
						break;
					case "like":
						$result = comment_like($url_parts[4],$url_parts[5]);
						header('Content-Type: application/json');
						print json_encode($result);
						break;
					case "unlike":
						$result = comment_unlike($url_parts[4],$url_parts[5]);
						header('Content-Type: application/json');
						print json_encode($result);
						break;
				}
				break; // comment
			case "friends":
				require_once("lib/api/friends.php");
				switch($url_parts[3]){
					case "add":
						$result = friend_add($url_parts[4]);
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
					case "remove":
						$result = friend_remove($url_parts[4]);
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
				}
				break; // friend
			case "message":
				require_once("lib/api/messages.php");
				switch($url_parts[3]){
					case "send":
						$result = send_message();
						if ($result > 0){
							header("Location: /message/".$result);
							// header("Location: /messages/outbox");
						} else {
							header("Location: ".$_SERVER["HTTP_REFERER"]."/failure/".$result);
						}
						break;
					case "remove":
						if (count($url_parts)>4){
							$result = mark_removed($url_parts[4]);
							header("Location: ".$_SERVER["HTTP_REFERER"]);
						}
						break;
					case "restore":
						if (count($url_parts)>4){
							$result = mark_restored($url_parts[4]);
							header("Location: ".$_SERVER["HTTP_REFERER"]);
						}
						break;
				}
				break;
			case "import":
				require_once("lib/api/import_feed.php");
				import_rss();
				break;
			case "messages":
				require_once("lib/api/messages.php");
				switch($url_parts[3]){
					case "mark_all_read":
						$result = mark_all_read();
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
					case "mark_all_inbox_read":
						$result = mark_all_inbox_read();
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
					case "mark_all_system_read":
						$result = mark_all_system_read();
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
					case "remove_all":
						$result = mark_all_removed();
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
					case "remove_all_inbox":
						$result = mark_all_inbox_removed();
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
					case "remove_all_outbox":
						$result = mark_all_outbox_removed();
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
					case "remove_all_inbox_system":
						$result = mark_all_inbox_system_removed();
						header("Location: ".$_SERVER["HTTP_REFERER"]);
						break;
					default:
				}
				break;
		}
		break; // api
		
	case "welcome":
		require_once("lib/html/common.php");
		require_once("lib/html/welcome.php");
		print render_welcome_page();
		break;
	
	case "mailqueue":
		require_once("lib/html/common.php");
		require_once("lib/html/mail_queue.php");
		print render_mail_queue();
		break;
		
	case "home":
		require_once("lib/html/common.php");
		require_once("lib/html/posts.php");
		require_once("lib/html/home.php");
		if (count($url_parts)>2){
			if (count($url_parts)>3){
				// e.g. /home/numposts/page
				print render_home_page($url_parts[2],$url_parts[3]);
			} else {
				// e.g. /home/numposts
				print render_home_page($url_parts[2],1);
			}
		} else {
			// e.g. /home
			print render_home_page(POSTS_PER_PAGE,1);
		}
		
		break;

	case "invite":
		require_once("lib/html/common.php");
		require_once("lib/html/invite.php");
		print invite_page();
		break;

	case "explore":
		require_once("lib/html/common.php");
		if (count($url_parts)>2){
			switch($url_parts[2]){
				case "firehose":
					require_once("lib/html/posts.php");
					require_once("lib/html/firehose.php");
					if (count($url_parts)>3){
						if (count($url_parts)>4){
							// e.g. /explore/firehose/numposts/page
							print render_firehose_page($url_parts[3],$url_parts[4]);
						} else {
							// e.g. /explore/firehose/numposts
							print render_firehose_page($url_parts[3],1);
						}
					} else {
						// e.g. /explore/firehose
						print render_firehose_page(POSTS_PER_PAGE,1);
					}
					break;
				case "everything":
					require_once("lib/html/posts.php");
					require_once("lib/html/everything.php");
					if (count($url_parts)>3){
						// e.g. /explore/firehose/page
						print render_everything_page($url_parts[3]);
					} else {
						// e.g. /explore/firehose
						print render_everything_page(1);
					}
					break;
				case "search":
					require_once("lib/html/posts.php");
					require_once("lib/html/search.php");
					if (count($url_parts)>3){ // e.g. /explore/search/term
						if (count($url_parts)>4) { // e.g. /explore/search/term/page
							print render_search_page($url_parts[3],$url_parts[4]);
						} else { // e.g. /explore/search/term
							print render_search_page($url_parts[3],1);
						}
					} else {
						// e.g. /explore/search
						print render_search_page("",1);
					}
					break;
				case "popular":
					require_once("lib/html/posts.php");
					require_once("lib/html/popular.php");
					if (count($url_parts)>3){
						// e.g. /explore/popular/page
						print render_popular_page($url_parts[3]);
					} else {
						// e.g. /explore/popular
						print render_popular_page(1);
					}
					break;
				case "tags":
					require_once("lib/html/tags.php");
					print render_tags_page();
					break;
				case "tag":
					require_once("lib/html/posts.php");
					require_once("lib/html/tag.php");
					if (count($url_parts)>4){
						switch($url_parts[4]){
							case "rss":
								// e.g. /explore/tags/foo/rss
								require_once("lib/html/rss.php");
								print render_tag_rss(urldecode($url_parts[3]));
								break;
							default:
								// e.g. /expore/tags/foo/page
								print render_tag_page(urldecode($url_parts[3]),$url_parts[4]);
								break;
						}
					} else {
						// e.g. /expore/tags/foo
						print render_tag_page(urldecode($url_parts[3]),1);
					}
					break;
				case "suggested":
					require_once("lib/html/suggested_users.php");
					if (count($url_parts)>4){
						print render_suggested_users($url_parts[3],$url_parts[4]);
					} else {
						if (count($url_parts)>3){
							print render_suggested_users($url_parts[3],1);
						} else {
							print render_suggested_users(7);
						}
					}
					break;
				case "directory":
					require_once("lib/html/directory.php");
					if (count($url_parts)>4){
						// e.g. /explore/directory/tagname/page
						print render_user_directory(urldecode($url_parts[3]),$url_parts[4]);
					} else {
						// e.g. /explore/directory/tagname
						if (count($url_parts)>3){
							print render_user_directory(urldecode($url_parts[3]),1);
						} else {
							// e.g. /explore/directory
							print render_user_directory();
						}
					}
					break;
			}
		} else {
			require_once("lib/html/posts.php");
			require_once("lib/html/firehose.php");
			print render_firehose_page(POSTS_PER_PAGE,1);
		}
		break;
	
	case "faq":
		require_once("lib/html/common.php");
		require_once("lib/html/faq.php");
		print render_faq();
		break;
	
	case "terms":
		require_once("lib/html/common.php");
		require_once("lib/html/terms.php");
		print render_terms();
		break;

	case "privacy":
		require_once("lib/html/common.php");
		require_once("lib/html/privacy.php");
		print render_privacy();
		break;
		
	case "register" :
		require_once("lib/html/common.php");
		require_once("lib/html/users.php");
		if (count($url_parts)>2) {
			switch ($url_parts[2]){
				case "agree":
					print render_register_page();
					break;
				case "failure":
					print render_register_page();
					break;
			}
		} else {
			print render_register_terms_page();
		}
		break;
	
	case "password_reset":
		require_once("lib/html/common.php");
		require_once("lib/html/users.php");
		print render_password_reset_page();
		break;
		
	case "login" :
		require_once("lib/html/common.php");
		require_once("lib/html/users.php");
		print render_login_page();
		break;
		
	case "account" :
		require_once("lib/html/common.php");
		require_once("lib/html/users.php");
		if (count($url_parts)>2) {
			switch($url_parts[2]){
				case "drafts":
					require_once("lib/html/posts.php");
					if (count($url_parts)>3){
						print render_account_drafts_page($url_parts[3]);
					} else {
						print render_account_drafts_page(1);
					}
					break;
				case "friends":
					require_once("lib/html/directory.php");
					if (count($url_parts)>3){
						print render_account_friends_page($url_parts[3]);
					} else {
						print render_account_friends_page(1);
					}
					break;
				case "followers":
					require_once("lib/html/directory.php");
					if (count($url_parts)>3){
						print render_account_followers_page($url_parts[3]);
					} else {
						print render_account_followers_page(1);
					}
					break;
				case "style":
					print render_account_style_page();
					break;
				case "import":
					require_once("lib/html/import.php");
					if(count($url_parts)>3){
						switch($url_parts[3]){
							case "rss":
								print render_import_rss();
								break;
						}
					} else {
						print render_import_menu();
					}
					break;
				default:
					print render_account_page();
					break;
			}
		} else {
			print render_account_page();
		}
		break;
	
	case "message":
		require_once("lib/html/common.php");
		require_once("lib/html/messages.php");
		if (count($url_parts)>2){
			print render_message_page($url_parts[2]);
		} else {
			header("Location: /404");
		}
		break;
		
	case "messages":
		require_once("lib/html/common.php");
		require_once("lib/html/messages.php");
		if (count($url_parts)>2){
			switch($url_parts[2]){
				case "everybody":
					if (count($url_parts)>3){
						print render_messages_everybody($url_parts[3]);
					} else {
						print render_messages_everybody(1);
					}
					break;
				case "all":
					if (count($url_parts)>3){
						print render_messages_all($url_parts[3]);
					} else {
						print render_messages_all(1);
					}
					break;
				case "inbox":
					if (count($url_parts)>3){
						print render_messages_inbox($url_parts[3]);
					} else {
						print render_messages_inbox(1);
					}
					break;
				case "trash":
					if (count($url_parts)>3){
						print render_messages_trash($url_parts[3]);
					} else {
						print render_messages_trash(1);
					}
					break;
				case "outbox":
					if (count($url_parts)>3){
						print render_messages_outbox($url_parts[3]);
					} else {
						print render_messages_outbox(1);
					}
					break;
				case "compose":
					if (count($url_parts)>3){
						if (count($url_parts)>4){
							print render_message_page($url_parts[4],$url_parts[4]);
						} else {
							print render_messages_form($url_parts[3]);
						}
					} else {
						print render_message_compose_page();
					}
					break;
				case "notifications":
					if (count($url_parts)>3){
						print render_messages_notifications($url_parts[3]);
					} else {
						print render_messages_notifications(1);
					}
					break;
			}
		} else {
			print render_messages_all(1);
		}
		
		break;
		
	case "404":
		require_once("lib/html/common.php");
		print render_404_page();
		break;
		
	case "401":
		require_once("lib/html/common.php");
		print render_401_page();
		break;
		
	case "post":
		require_once("lib/html/common.php");
		require_once("lib/html/posts.php");
		switch($url_parts[2]){
			case "add":
				print render_post_add_page();
				break;
			case "drafts":
				require_once("lib/html/posts.php");
				if (count($url_parts)>3){
					print render_post_drafts_page($url_parts[3]);
				} else {
					print render_post_drafts_page(1);
				}
				break;
			case "edit":
				print render_post_edit_page($url_parts[3]);
				break;
			default:
				print render_post_page($url_parts[2]);
				break;
		}
		break;
	
	case "p":
		require_once("lib/html/common.php");
		require_once("lib/html/posts.php");
		switch($url_parts[2]){
			case "add":
				print render_post_add_page();
				break;
			case "edit":
				print render_post_edit_page($url_parts[3]);
				break;
			default:
				print render_post_page($url_parts[2]);
				break;
		}
		break;
		
	case "help":
		require_once("lib/html/common.php");
		print render_help_page();
		break;

	case "chat":
		require_once("lib/html/common.php");
		require_once("lib/html/chat.php");
		print render_chat_page();
		break;
	
	
	default:
		// anything else must be a username
		if (count($url_parts)>2){
			switch ($url_parts[2]){
			
				case "followers":
					require_once("lib/html/common.php");
					require_once("lib/html/users.php");
					require_once("lib/html/user_directory.php");
					if (count($url_parts)>3){
						print render_profile_page_header($url_parts[1],$url_parts[3]);
						print render_profile_page_followers($url_parts[1],$url_parts[3]);
					} else {
						print render_profile_page_header($url_parts[1],1);
						print render_profile_page_followers($url_parts[1],1);
					}
					break;
					
				case "friends":
					require_once("lib/html/common.php");
					require_once("lib/html/users.php");
					require_once("lib/html/user_directory.php");
					if (count($url_parts)>3){
						print render_profile_page_header($url_parts[1],$url_parts[3]);
						print render_profile_page_friends($url_parts[1],$url_parts[3]);
					} else {
						print render_profile_page_header($url_parts[1],1);
						print render_profile_page_friends($url_parts[1],1);
					}
					break;
					
				case "rss":
					require_once("lib/html/rss.php");
					print render_user_rss($url_parts[1]);
					break;
					
				default:
					// just a username
					require_once("lib/html/common.php");
					require_once("lib/html/users.php");
					require_once("lib/html/posts.php");
					print render_profile_page_header($url_parts[1],$url_parts[2]);
					print render_profile_page_posts($url_parts[1],$url_parts[2]);
					break;
			}
		} else {
			require_once("lib/html/common.php");
			require_once("lib/html/users.php");
			require_once("lib/html/posts.php");
			print render_profile_page_header($url_parts[1],1);
			print render_profile_page_posts($url_parts[1],1);
		}
		break;
		
}


?>
