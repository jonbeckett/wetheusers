<?php

function render_message($message_row,$show_buttons = true,$selected = false){

	if (isset($_SESSION["user_id"])){
	
		// set a class based on the readflag
		$readflag_class = ($message_row["ReadFlag"] == 1) ? "read" : "";
		
		$selected_class = ($selected) ? "selected" : "";
		
		$avatar_image = ($message_row["FromUsersAvatar"] != "") ? $message_row["FromUsersAvatar"] : "avatars/generic_64.jpg";
		$meta = "<div class=\"avatar\"><a href=\"/".$message_row["FromUsersUsername"]."\" title=\"".$message_row["FromUsersUsername"]."\"><img src=\"/".$avatar_image."\" width=\"32\" height=\"32\" alt=\"".$message_row["FromUsersUsername"]."\" /></a></div>\n"
			."<div class=\"meta\">\n"
			."<div class=\"username\">from <a href=\"/".$message_row["FromUsersUsername"]."\" title=\"".$message_row["FromUsersUsername"]."\">".$message_row["FromUsersUsername"]."</a> to <a href=\"/".$message_row["ToUsersUsername"]."\" title=\"".$message_row["ToUsersUsername"]."\">".$message_row["ToUsersUsername"]."</a></div>\n"
			."<div class=\"data\">".(($message_row["Type"]==0) ? "<a href=\"/message/".$message_row["Id"]."\">".time_ago($message_row["Created"])."</a>" : time_ago($message_row["Created"])).(($message_row["ParentMessagesId"] != null) ? " in response to a <a href=\"/message/".$message_row["ParentMessagesId"]."\">message</a> by <a href=\"/".$message_row["ParentUsersUsername"]."\">".$message_row["ParentUsersUsername"]."</a>" : "" )."</div>\n"
			."</div> <!-- .meta -->\n";
		
		// work out the remove button (remove, or restore)
		$remove_restore_button = "";
		if ( ($message_row["ToUserId"]==$_SESSION["user_id"] && $message_row["ToStatus"]==0 ) || ($message_row["FromUserId"]==$_SESSION["user_id"] && $message_row["FromStatus"]==0 )) {
			$remove_restore_button = "<div class=\"button\"><a href=\"/api/message/remove/".$message_row["Id"]."\" title=\"Remove\" onclick=\"return confirm('Are you sure?');\">Remove</a></div>";
		} else {
			$remove_restore_button = "<div class=\"button\"><a href=\"/api/message/restore/".$message_row["Id"]."\" title=\"Restore\" onclick=\"return confirm('Are you sure?');\">Restore</a></div>";
		}
		
		$html = "<div class=\"message_wrapper\">\n"
			."<div class=\"message ".$readflag_class." ".$selected_class."\">\n"
			."<div class=\"body\">".Markdown($message_row["Body"])."</div>\n"
			."<div class=\"info\">\n"
				.(($show_buttons) ? (($message_row["ToUserId"] == $_SESSION["user_id"] && $message_row["Type"]==0) ? "<div class=\"button\"><a href=\"/messages/compose/".$message_row["FromUsersUsername"]."/".$message_row["Id"]."/#form\" title=\"Reply\">Reply</a></div>" : "")
				.$remove_restore_button : "").$meta
				."<div class=\"clear\"></div>\n"
			."</div> <!-- .info -->\n"
			."</div> <!-- .message -->\n"
			."</div> <!-- .message_wrapper -->\n\n";
		
		return $html;
	}
}

function render_messages_everybody($page=1){

	if (isset($_SESSION["user_id"])){
		
		$start = (intval($page) - 1) * 20;
		
		$mysqli = db_connect();
		
		$html = render_header("Inbox");
		
		$html .= "<div id=\"header\">\n"
			."<h1>Message Queue</h1>\n"
			."<p>Messages sent by other users, and by the system.</p>\n"
			."</div> <!-- #header -->\n";
			
		$sql = "SELECT Messages.*, FromUsers.Username As FromUsersUsername, FromUsers.Avatar AS FromUsersAvatar,ToUsers.Username As ToUsersUsername, ToUsers.Avatar AS ToUsersAvatar,ParentUsers.Username AS ParentUsersUsername, ParentMessages.Id AS ParentMessagesId"
			." FROM Messages"
			." INNER JOIN Users FromUsers ON Messages.FromUserId=FromUsers.Id"
			." INNER JOIN Users ToUsers ON Messages.ToUserId=ToUsers.Id"
			." LEFT OUTER JOIN Messages ParentMessages ON ParentMessages.Id=Messages.ParentId"
			." LEFT OUTER JOIN Users ParentUsers ON ParentMessages.FromUserId=ParentUsers.Id"
			." ORDER BY Messages.Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";
		
		$sql_count = "SELECT COUNT(*) AS NumMessages"
			." FROM Messages";
		
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumMessages"];
		
		$messages_result = $mysqli->query($sql);
		
		if ($messages_result->num_rows > 0){
		

			$html .= "<div id=\"messages\">\n\n";
			
			while ($message_row =@ $messages_result->fetch_assoc()){
				
				$html .= render_message($message_row);
				
			}
			
			$html .= "</div> <!-- #messages -->\n\n";
		
		} else {
			$html .= "<div id=\"messages\">\n"
				."<div class=\"message_wrapper\">\n"
				."<div class=\"message\">\n"
				."<div class=\"body\">\n"
				."<p class='center'>There are no messages.</p>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n";
		}
		
		// Pagination
		$html .= render_pagination("messages/everybody",$page,$count,20);
	
		$html .= render_footer();
		
	} else {
	
		header("Location: /403");
		
	}
	
	return $html;
}

function render_messages_all($page=1){

	if (isset($_SESSION["user_id"])){
		
		$start = (intval($page) - 1) * 20;
		
		$mysqli = db_connect();

		$overall_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND ReadFlag=0";
		$overall_total_result = $mysqli->query($overall_total_sql);
		$overall_total_row = $overall_total_result->fetch_assoc();
		$overall_total = ($overall_total_row["NumMessages"]>0) ? " (".$overall_total_row["NumMessages"].")" : "";
		
		$inbox_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type=0 AND ReadFlag=0";
		$inbox_total_result = $mysqli->query($inbox_total_sql);
		$inbox_total_row = $inbox_total_result->fetch_assoc();
		$inbox_total = ($inbox_total_row["NumMessages"]>0) ? " (".$inbox_total_row["NumMessages"].")" : "";
		
		$notification_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type>0 AND ReadFlag=0";
		$notification_total_result = $mysqli->query($notification_total_sql);
		$notification_total_row = $notification_total_result->fetch_assoc();
		$notification_total = ($notification_total_row["NumMessages"]>0) ? " (".$notification_total_row["NumMessages"].")" : "";
		
		$html = render_header("Inbox");
		
		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/messages/compose\" title=\"Compose\">Compose</a></li>\n"
			."<li class=\"selected\"><a href=\"/messages/all\" title=\"All\">All".$overall_total."</a></li>\n"
			."<li><a href=\"/messages/inbox\" title=\"Inbox\">Inbox".$inbox_total."</a></li>\n"
			."<li><a href=\"/messages/outbox\" title=\"Outbox\">Outbox</a></li>\n"
			."<li><a href=\"/messages/notifications\" title=\"Notifications\">Notifications".$notification_total."</a></li>\n"
			."<li><a href=\"/messages/trash\" title=\"Trash\">Trash</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";

		$html .= "<div id=\"header\">\n"
			."<h1>All Incoming Messages</h1>\n"
			."<p>Messages sent by other users, and by the system.</p>\n"
			."<ul class=\"buttons\">\n"
			."<li class=\"button\"><a href=\"/api/messages/mark_all_read\" onClick=\"return confirm('Are you sure?');\">Mark All Read</a></li>\n"
			."<li class=\"button\"><a href=\"/api/messages/remove_all\" onClick=\"return confirm('Are you sure?');\">Remove All</a></li>\n"
			."</ul>\n"
			."</div> <!-- #header -->\n";
			
		$sql = "SELECT Messages.*, FromUsers.Username As FromUsersUsername, FromUsers.Avatar AS FromUsersAvatar,ToUsers.Username As ToUsersUsername, ToUsers.Avatar AS ToUsersAvatar,ParentUsers.Username AS ParentUsersUsername, ParentMessages.Id AS ParentMessagesId"
			." FROM Messages"
			." INNER JOIN Users FromUsers ON Messages.FromUserId=FromUsers.Id"
			." INNER JOIN Users ToUsers ON Messages.ToUserId=ToUsers.Id"
			." LEFT OUTER JOIN Messages ParentMessages ON ParentMessages.Id=Messages.ParentId"
			." LEFT OUTER JOIN Users ParentUsers ON ParentMessages.FromUserId=ParentUsers.Id"
			." WHERE Messages.ToStatus=0 AND Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." ORDER BY Messages.Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";
					
		$sql_count = "SELECT COUNT(*) AS NumMessages"
			." FROM Messages"
			." WHERE Messages.ToStatus=0 AND Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]);
		
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumMessages"];
		
		$messages_result = $mysqli->query($sql);
		
		if ($messages_result->num_rows > 0){
		

			$html .= "<div id=\"messages\">\n\n";
			
			while ($message_row =@ $messages_result->fetch_assoc()){
				
				$html .= render_message($message_row);
				
			}
			
			$html .= "</div> <!-- #messages -->\n\n";
		
		} else {
			$html .= "<div id=\"messages\">\n"
				."<div class=\"message_wrapper\">\n"
				."<div class=\"message\">\n"
				."<div class=\"body\">\n"
				."<p class='center'>You have no messages in your inbox.</p>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n";
		}
		
		// Pagination
		$html .= render_pagination("messages/all",$page,$count,20);
	
		$html .= render_footer();
		
	} else {
	
		header("Location: /403");
		
	}
	
	return $html;	
}

function render_messages_notifications($page=1){

	if (isset($_SESSION["user_id"])){
		
		$start = (intval($page) - 1) * 20;
		
		$html = render_header("In-Box");
		
		$mysqli = db_connect();
		
		$overall_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND ReadFlag=0";
		$overall_total_result = $mysqli->query($overall_total_sql);
		$overall_total_row = $overall_total_result->fetch_assoc();
		$overall_total = ($overall_total_row["NumMessages"]>0) ? " (".$overall_total_row["NumMessages"].")" : "";
		
		$inbox_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type=0 AND ReadFlag=0";
		$inbox_total_result = $mysqli->query($inbox_total_sql);
		$inbox_total_row = $inbox_total_result->fetch_assoc();
		$inbox_total = ($inbox_total_row["NumMessages"]>0) ? " (".$inbox_total_row["NumMessages"].")" : "";
		
		$notification_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type>0 AND ReadFlag=0";
		$notification_total_result = $mysqli->query($notification_total_sql);
		$notification_total_row = $notification_total_result->fetch_assoc();
		$notification_total = ($notification_total_row["NumMessages"]>0) ? " (".$notification_total_row["NumMessages"].")" : "";
		
		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/messages/compose\" title=\"Compose\">Compose</a></li>\n"
			."<li><a href=\"/messages/all\" title=\"All\">All".$overall_total."</a></li>\n"
			."<li><a href=\"/messages/inbox\" title=\"Inbox\">Inbox".$inbox_total."</a></li>\n"
			."<li><a href=\"/messages/outbox\" title=\"Outbox\">Outbox</a></li>\n"
			."<li class=\"selected\"><a href=\"/messages/notifications\" title=\"Notifications\">Notifications".$notification_total."</a></li>\n"
			."<li><a href=\"/messages/trash\" title=\"Trash\">Trash</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";


		$html .= "<div id=\"header\">\n"
			."<h1>Notifications</h1>\n"
			."<p>Messages sent by the system. They automatically vanish after 7 days.</p>\n"
			."<ul class=\"buttons\">\n"
			."<li class=\"button\"><a href=\"/api/messages/mark_all_system_read\" onClick=\"return confirm('Are you sure?');\">Mark All Read</a></li>\n"
			."<li class=\"button\"><a href=\"/api/messages/remove_all_inbox_system\" onClick=\"return confirm('Are you sure?');\">Remove All</a></li>\n"
			."</ul>\n"
			."</div> <!-- #header -->\n";
						
		
		$sql = "SELECT Messages.*, FromUsers.Username As FromUsersUsername, FromUsers.Avatar AS FromUsersAvatar,ToUsers.Username As ToUsersUsername, ToUsers.Avatar AS ToUsersAvatar,ParentUsers.Username AS ParentUsersUsername, ParentMessages.Id AS ParentMessagesId"
			." FROM Messages"
			." INNER JOIN Users FromUsers ON Messages.FromUserId=FromUsers.Id"
			." INNER JOIN Users ToUsers ON Messages.ToUserId=ToUsers.Id"
			." LEFT OUTER JOIN Messages ParentMessages ON ParentMessages.Id=Messages.ParentId"
			." LEFT OUTER JOIN Users ParentUsers ON ParentMessages.FromUserId=ParentUsers.Id"
			." WHERE Messages.Type>0 AND Messages.ToStatus=0 AND Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." ORDER BY Messages.Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";
		
		$sql_count = "SELECT COUNT(*) AS NumMessages"
			." FROM Messages"
			." WHERE Messages.Type>0 AND Messages.ToStatus=0 AND Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]);
		
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumMessages"];
		
		$messages_result = $mysqli->query($sql);
		
		if ($messages_result->num_rows > 0){
		

			$html .= "<div id=\"messages\">\n\n";
			
			while ($message_row =@ $messages_result->fetch_assoc()){
			
				$html .= render_message($message_row);
			}
			
			$html .= "</div> <!-- #messages -->\n\n";
		
		} else {
			$html .= "<div id=\"messages\">\n"
				."<div class=\"message_wrapper\">\n"
				."<div class=\"message\">\n"
				."<div class=\"body\">\n"
				."<p class='center'>You have no messages in your inbox.</p>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n";
		}
		
		// Pagination
		$html .= render_pagination("messages/notifications",$page,$count,20);
	
		$html .= render_footer();
		
	} else {
	
		header("Location: /403");
		
	}
	
	return $html;	
}

function render_messages_inbox($page=1){

	if (isset($_SESSION["user_id"])){
		
		$start = (intval($page) - 1) * 20;
		
		$mysqli = db_connect();
		
		$overall_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND ReadFlag=0";
		$overall_total_result = $mysqli->query($overall_total_sql);
		$overall_total_row = $overall_total_result->fetch_assoc();
		$overall_total = ($overall_total_row["NumMessages"]>0) ? " (".$overall_total_row["NumMessages"].")" : "";
		
		$inbox_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type=0 AND ReadFlag=0";
		$inbox_total_result = $mysqli->query($inbox_total_sql);
		$inbox_total_row = $inbox_total_result->fetch_assoc();
		$inbox_total = ($inbox_total_row["NumMessages"]>0) ? " (".$inbox_total_row["NumMessages"].")" : "";
		
		$notification_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type>0 AND ReadFlag=0";
		$notification_total_result = $mysqli->query($notification_total_sql);
		$notification_total_row = $notification_total_result->fetch_assoc();
		$notification_total = ($notification_total_row["NumMessages"]>0) ? " (".$notification_total_row["NumMessages"].")" : "";
		
		$html = render_header("Inbox");
		
		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/messages/compose\" title=\"Compose\">Compose</a></li>\n"
			."<li><a href=\"/messages/all\" title=\"All\">All".$overall_total."</a></li>\n"
			."<li class=\"selected\"><a href=\"/messages/inbox\" title=\"Inbox\">Inbox".$inbox_total."</a></li>\n"
			."<li><a href=\"/messages/outbox\" title=\"Outbox\">Outbox</a></li>\n"
			."<li><a href=\"/messages/notifications\" title=\"Notifications\">Notifications".$notification_total."</a></li>\n"
			."<li><a href=\"/messages/trash\" title=\"Trash\">Trash</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";

		$html .= "<div id=\"header\">\n"
			."<h1>Inbox</h1>\n"
			."<p>Messages sent by other users to you.</p>\n"
			."<ul class=\"buttons\">\n"
			."<li class=\"button\"><a href=\"/api/messages/mark_all_inbox_read\" onClick=\"return confirm('Are you sure?');\">Mark All Read</a></li>\n"
			."<li class=\"button\"><a href=\"/api/messages/remove_all_inbox\" onClick=\"return confirm('Are you sure?');\">Remove All</a></li>\n"
			."</ul>\n"
			."</div> <!-- #header -->\n";
			
		$sql = "SELECT Messages.*, FromUsers.Username As FromUsersUsername, FromUsers.Avatar AS FromUsersAvatar,ToUsers.Username As ToUsersUsername, ToUsers.Avatar AS ToUsersAvatar,ParentUsers.Username AS ParentUsersUsername, ParentMessages.Id AS ParentMessagesId"
			." FROM Messages"
			." INNER JOIN Users FromUsers ON Messages.FromUserId=FromUsers.Id"
			." INNER JOIN Users ToUsers ON Messages.ToUserId=ToUsers.Id"
			." LEFT OUTER JOIN Messages ParentMessages ON ParentMessages.Id=Messages.ParentId"
			." LEFT OUTER JOIN Users ParentUsers ON ParentMessages.FromUserId=ParentUsers.Id"
			." WHERE Messages.Type=0 AND Messages.ToStatus=0 AND Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." ORDER BY Messages.Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";
					
		$sql_count = "SELECT COUNT(*) AS NumMessages"
			." FROM Messages"
			." WHERE Messages.Type=0 AND Messages.ToStatus=0 AND Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]);
		
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumMessages"];
		
		$messages_result = $mysqli->query($sql);
		
		if ($messages_result->num_rows > 0){
		

			$html .= "<div id=\"messages\">\n\n";
			
			while ($message_row =@ $messages_result->fetch_assoc()){
				
				$html .= render_message($message_row);
				
			}
			
			$html .= "</div> <!-- #messages -->\n\n";
		
		} else {
			$html .= "<div id=\"messages\">\n"
				."<div class=\"message_wrapper\">\n"
				."<div class=\"message\">\n"
				."<div class=\"body\">\n"
				."<p class='center'>You have no messages in your inbox.</p>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n";
		}
		
		// Pagination
		$html .= render_pagination("messages/inbox",$page,$count,20);
	
		$html .= render_footer();
		
	} else {
	
		header("Location: /403");
		
	}
	
	return $html;	
}


function render_messages_trash($page){

	if (isset($_SESSION["user_id"])){
		
		$start = (intval($page) - 1) * 20;
		
		$mysqli = db_connect();
		
		$overall_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND ReadFlag=0";
		$overall_total_result = $mysqli->query($overall_total_sql);
		$overall_total_row = $overall_total_result->fetch_assoc();
		$overall_total = ($overall_total_row["NumMessages"]>0) ? " (".$overall_total_row["NumMessages"].")" : "";
		
		$inbox_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type=0 AND ReadFlag=0";
		$inbox_total_result = $mysqli->query($inbox_total_sql);
		$inbox_total_row = $inbox_total_result->fetch_assoc();
		$inbox_total = ($inbox_total_row["NumMessages"]>0) ? " (".$inbox_total_row["NumMessages"].")" : "";
		
		$notification_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type>0 AND ReadFlag=0";
		$notification_total_result = $mysqli->query($notification_total_sql);
		$notification_total_row = $notification_total_result->fetch_assoc();
		$notification_total = ($notification_total_row["NumMessages"]>0) ? " (".$notification_total_row["NumMessages"].")" : "";
		
		$html = render_header("Trash");
		
		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/messages/compose\" title=\"Compose\">Compose</a></li>\n"
			."<li><a href=\"/messages/all\" title=\"All\">All".$overall_total."</a></li>\n"
			."<li><a href=\"/messages/inbox\" title=\"Inbox\">Inbox".$inbox_total."</a></li>\n"
			."<li><a href=\"/messages/outbox\" title=\"Outbox\">Outbox</a></li>\n"
			."<li><a href=\"/messages/notifications\" title=\"Notifications\">Notifications".$notification_total."</a></li>\n"
			."<li class=\"selected\"><a href=\"/messages/trash\" title=\"Trash\">Trash</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";
	
		$html .= "<div id=\"header\">\n"
			."<h1>Trash</h1>\n"
			."<p>Messages you have removed. Note that trash does not show removed system notifications.</p>\n"
			."</div> <!-- #header -->\n";
		
		$sql = "SELECT Messages.*, FromUsers.Username As FromUsersUsername, FromUsers.Avatar AS FromUsersAvatar,ToUsers.Username As ToUsersUsername, ToUsers.Avatar AS ToUsersAvatar,ParentUsers.Username AS ParentUsersUsername, ParentMessages.Id AS ParentMessagesId"
			." FROM Messages"
			." INNER JOIN Users FromUsers ON Messages.FromUserId=FromUsers.Id"
			." INNER JOIN Users ToUsers ON Messages.ToUserId=ToUsers.Id"
			." LEFT OUTER JOIN Messages ParentMessages ON ParentMessages.Id=Messages.ParentId"
			." LEFT OUTER JOIN Users ParentUsers ON ParentMessages.FromUserId=ParentUsers.Id"
			." WHERE Messages.Type=0 AND ((Messages.ToStatus=1 AND Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]).") OR (Messages.FromStatus=1 AND Messages.FromUserId=".$_SESSION["user_id"]."))"
			." ORDER BY Messages.Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";
					
		$sql_count = "SELECT COUNT(*) AS NumMessages"
			." FROM Messages"
			." WHERE Messages.Type=0 AND Messages.ToStatus=1 AND Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"]);
		
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumMessages"];
		
		$messages_result = $mysqli->query($sql);
		
		if ($messages_result->num_rows > 0){
		

			$html .= "<div id=\"messages\">\n\n";
			
			while ($message_row =@ $messages_result->fetch_assoc()){
				
				$html .= render_message($message_row);
				
			}
			
			$html .= "</div> <!-- #messages -->\n\n";
		
		} else {
			$html .= "<div id=\"messages\">\n"
				."<div class=\"message_wrapper\">\n"
				."<div class=\"message\">\n"
				."<div class=\"body\">\n"
				."<p class='center'>You have no messages in your trash.</p>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n";
		}
		
		// Pagination
		$html .= render_pagination("messages/trash",$page,$count,20);
	
		$html .= render_footer();
		
	} else {
	
		header("Location: /403");
		
	}
	
	return $html;	
}



function render_messages_outbox($page){


	if (isset($_SESSION["user_id"])){
		
		$start = (intval($page) - 1) * 20;
		
		$mysqli = db_connect();
		
		$overall_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND ReadFlag=0";
		$overall_total_result = $mysqli->query($overall_total_sql);
		$overall_total_row = $overall_total_result->fetch_assoc();
		$overall_total = ($overall_total_row["NumMessages"]>0) ? " (".$overall_total_row["NumMessages"].")" : "";
		
		$inbox_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type=0 AND ReadFlag=0";
		$inbox_total_result = $mysqli->query($inbox_total_sql);
		$inbox_total_row = $inbox_total_result->fetch_assoc();
		$inbox_total = ($inbox_total_row["NumMessages"]>0) ? " (".$inbox_total_row["NumMessages"].")" : "";
		
		$notification_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type>0 AND ReadFlag=0";
		$notification_total_result = $mysqli->query($notification_total_sql);
		$notification_total_row = $notification_total_result->fetch_assoc();
		$notification_total = ($notification_total_row["NumMessages"]>0) ? " (".$notification_total_row["NumMessages"].")" : "";
		
		$html = render_header("In-Box");
		
		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/messages/compose\" title=\"Compose\">Compose</a></li>\n"
			."<li><a href=\"/messages/all\" title=\"All\">All".$overall_total."</a></li>\n"
			."<li><a href=\"/messages/inbox\" title=\"Inbox\">Inbox".$inbox_total."</a></li>\n"
			."<li class=\"selected\"><a href=\"/messages/outbox\" title=\"Outbox\">Outbox</a></li>\n"
			."<li><a href=\"/messages/notifications\" title=\"Notifications\">Notifications".$notification_total."</a></li>\n"
			."<li><a href=\"/messages/trash\" title=\"Trash\">Trash</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";

		$html .= "<div id=\"header\">\n"
			."<h1>Outbox</h1>\n"
			."<p>Messages you have sent to other users.</p>\n"
			."<ul class=\"buttons\">\n"
			."<li class=\"button\"><a href=\"/api/messages/remove_all_outbox\" onClick=\"return confirm('Are you sure?');\">Remove All</a></li>\n"
			."</ul>\n"
			."</div> <!-- #header -->\n";
		
		$sql = "SELECT Messages.*, FromUsers.Username As FromUsersUsername, FromUsers.Avatar AS FromUsersAvatar,ToUsers.Username As ToUsersUsername, ToUsers.Avatar AS ToUsersAvatar, ParentUsers.Username AS ParentUsersUsername, ParentMessages.Id AS ParentMessagesId"
			." FROM Messages"
			." INNER JOIN Users FromUsers ON Messages.FromUserId=FromUsers.Id"
			." INNER JOIN Users ToUsers ON Messages.ToUserId=ToUsers.Id"
			." LEFT OUTER JOIN Messages ParentMessages ON ParentMessages.Id=Messages.ParentId"
			." LEFT OUTER JOIN Users ParentUsers ON ParentMessages.FromUserId=ParentUsers.Id"
			." WHERE Messages.Type=0 AND Messages.FromStatus=0 AND Messages.FromUserId=".$mysqli->real_escape_string($_SESSION["user_id"])
			." ORDER BY Messages.Created DESC LIMIT ".$mysqli->real_escape_string($start).",20";
		
		
		$sql_count = "SELECT COUNT(*) AS NumMessages"
			." FROM Messages"
			." WHERE Messages.Type=0 AND Messages.FromStatus=0 AND Messages.FromUserId=".$mysqli->real_escape_string($_SESSION["user_id"]);
		
		$count_result = $mysqli->query($sql_count);
		$count_row = $count_result->fetch_assoc();
		$count = $count_row["NumMessages"];
		
		$messages_result = $mysqli->query($sql);
		
		if ($messages_result->num_rows > 0){
		

			$html .= "<div id=\"messages\">\n\n";
			
			while ($message_row =@ $messages_result->fetch_assoc()){
				
				$html .= render_message($message_row);
				
			}
			
			$html .= "</div> <!-- #messages -->\n\n";
		
		} else {
			$html .= "<div id=\"messages\">\n"
				."<div class=\"message_wrapper\">\n"
				."<div class=\"message\">\n"
				."<div class=\"body\">\n"
				."<p class='center'>You have no messages in your outbox.</p>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n"
				."</div>\n";
		}
		
		// Pagination
		$html .= render_pagination("messages/outbox",$page,$count,20);
	
		$html .= render_footer();
		
	} else {
	
		header("Location: /403");
		
	}
	
	return $html;	
}

function render_messages_form($username="",$inreplyto=0){

	if (isset($_SESSION["user_id"])){
		
		$mysqli = db_connect();
		
		$overall_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND ReadFlag=0";
		$overall_total_result = $mysqli->query($overall_total_sql);
		$overall_total_row = $overall_total_result->fetch_assoc();
		$overall_total = ($overall_total_row["NumMessages"]>0) ? " (".$overall_total_row["NumMessages"].")" : "";
		
		$inbox_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type=0 AND ReadFlag=0";
		$inbox_total_result = $mysqli->query($inbox_total_sql);
		$inbox_total_row = $inbox_total_result->fetch_assoc();
		$inbox_total = ($inbox_total_row["NumMessages"]>0) ? " (".$inbox_total_row["NumMessages"].")" : "";
		
		$notification_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type>0 AND ReadFlag=0";
		$notification_total_result = $mysqli->query($notification_total_sql);
		$notification_total_row = $notification_total_result->fetch_assoc();
		$notification_total = ($notification_total_row["NumMessages"]>0) ? " (".$notification_total_row["NumMessages"].")" : "";
		
		$html = render_header("Compose Message");
		
		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li class=\"selected\"><a href=\"/messages/compose\" title=\"Compose\">Compose</a></li>\n"
			."<li><a href=\"/messages/all\" title=\"All\">All".$overall_total."</a></li>\n"
			."<li><a href=\"/messages/inbox\" title=\"Inbox\">Inbox".$inbox_total."</a></li>\n"
			."<li><a href=\"/messages/outbox\" title=\"Outbox\">Outbox</a></li>\n"
			."<li><a href=\"/messages/notifications\" title=\"Notifications\">Notifications".$notification_total."</a></li>\n"
			."<li><a href=\"/messages/trash\" title=\"Trash\">Trash</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";
			
		$html .= "<div id=\"header\">\n"
				."<h1>".(($inreplyto>0) ? "Reply" : "New Message")."</h1>\n"
			."</div> <!-- #header -->\n"
			."<div id=\"messages\">\n";
			
		if ($username!=""){
	
			
			// fetch the user the message is to
			$user_sql = "SELECT Users.*,Friends.Id AS FriendRecordId FROM Users"
				." LEFT OUTER JOIN Friends ON Users.Id=Friends.UserId AND Friends.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])
				." WHERE Users.Username='".$mysqli->real_escape_string($username)."'";
	
			$user_result = $mysqli->query($user_sql);
			
			$user_row = $user_result->fetch_assoc();
			
			if ($user_row["FriendRecordId"] != "" || $user_row["MessagesFriendsOnly"]=="0"){
				
				if ($inreplyto > 0){
					
					// fetch the message
					$sql = "SELECT Messages.*,FromUsers.Id AS FromUserId, FromUsers.Username As FromUsersUsername, FromUsers.Avatar AS FromUsersAvatar, ParentUsers.Username AS ParentUsersUsername, ParentMessages.Id AS ParentMessagesId"
						." FROM Messages"
						." INNER JOIN Users FromUsers ON Messages.FromUserId=FromUsers.Id"
						." LEFT OUTER JOIN Messages ParentMessages ON ParentMessages.Id=Messages.ParentId"
						." LEFT OUTER JOIN Users ParentUsers ON ParentMessages.FromUserId=ParentUsers.Id"
						." WHERE Messages.Id=".$mysqli->real_escape_string($inreplyto)
						." AND Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])
						." AND FromUsers.Username='".$mysqli->real_escape_string($username)."'";
								
					$message_result = $mysqli->query($sql);
					
					if ($message_result->num_rows > 0){
						
						$message_row = $message_result->fetch_assoc();
						
						// show the message card
						$html .= "\n<div>\n\n"
							.render_message($message_row)
							."</div> <!-- #messages -->\n";
							
					} else {
						// requested message not found
						$html .= "<div class=\"message_form_wrapper\"><div class=\"message_form\"><h4>Message not found</h4></div></div>\n";
					}
					
				} else {
				
					$avatar_image = ($user_row["Avatar"]!="") ? $user_row["Avatar"] : "avatars/generic_64.jpg";
				
					// show the user card
					$html .= "\n<div class=\"message_form_wrapper\">\n"
						."<div class=\"message_form\">\n"
						."<h3>Sending Message To...</h3>\n"
						."<div class=\"user_card\">\n"
							."<div class=\"avatar\"><a href=\"/".$user_row["Username"]."\" title=\"".$user_row["Username"]."\"><img src=\"/".$avatar_image."\" alt=\"".$user_row["Username"]."\" width=\"64\" height=\"64\" /></a></div>\n"
							."<div class=\"username\"><h3><a href=\"/".$user_row["Username"]."\" title=\"".$user_row["Username"]."\">".$user_row["Username"]."</a></h3></div>\n"
							."<div class=\"clear\"></div>\n"
						."</div>\n"
						."</div> <!-- .message_form -->\n"
						."</div> <!-- .message_form_wrapper -->\n\n";
				
				}
				
				
				
				// Render the post message form
				$html .= "<form method=\"POST\" action=\"/api/message/send\" enctype=\"multipart/form-data\">\n"
					."<input type=\"hidden\" name=\"to\" value=\"".$username."\" />\n";
				
				if ($inreplyto > 0) $html .= "<input type=\"hidden\" name=\"in_reply_to\" value=\"".$inreplyto."\" />\n";
				
				$html .= "<div class=\"message_form_wrapper\">\n"
					."<div class=\"message_form\">\n"
					."<h3>Message</h3>\n"
					."<p>Write your message here... (supports <a href=\"http://daringfireball.net/projects/markdown/\">markdown</a>)</p>\n"
					."<div><textarea id=\"message_body\" name=\"body\" rows=\"10\"></textarea></div>\n"
					."<input type=\"submit\" value=\"Send\" />\n"
					."</div> <!-- .message_form -->\n"
					."</div> <!-- .message_form_wrapper -->\n"
					."</form>\n"
					."<a name='form'></a>\n"
					."<script>\n"
					."$(\"#message_body\").focus();\n"
					."</script>\n";
					
			} else {
			
				// user not found
				$html .= "<div class=\"message_form_wrapper\"><div class=\"message_form\"><h4>User not found - you can only send messages to people who have called you a friend.</h4></div></div>\n";
			
			}	
			
		} else {
		
			// show a list of friends ?
			$html .= "<div class=\"message_form_wrapper\"><div class=\"message_form\"><h4>You need to choose somebody to send a message to.</h4><p style=\"text-align:center;padding-top:5px;\">Visit a friend's profile page, and click the 'Send Message' button.</p><p style=\"text-align:center;padding-top:5px;\">Remember - the person you with to message may have restricted messages to only those they list as friends.</p></div></div>\n";
			
		}
		
		$html .= "</div> <!-- #messages -->\n";
	
		$html .= render_footer();
		
	} else {
	
		header("Location: /403");
		
	}
	
	return $html;
}

function render_message_compose_page(){

	if (isset($_SESSION["user_id"])){
		
		$mysqli = db_connect();
		
		$overall_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND ReadFlag=0";
		$overall_total_result = $mysqli->query($overall_total_sql);
		$overall_total_row = $overall_total_result->fetch_assoc();
		$overall_total = ($overall_total_row["NumMessages"]>0) ? " (".$overall_total_row["NumMessages"].")" : "";
		
		$inbox_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type=0 AND ReadFlag=0";
		$inbox_total_result = $mysqli->query($inbox_total_sql);
		$inbox_total_row = $inbox_total_result->fetch_assoc();
		$inbox_total = ($inbox_total_row["NumMessages"]>0) ? " (".$inbox_total_row["NumMessages"].")" : "";
		
		$notification_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type>0 AND ReadFlag=0";
		$notification_total_result = $mysqli->query($notification_total_sql);
		$notification_total_row = $notification_total_result->fetch_assoc();
		$notification_total = ($notification_total_row["NumMessages"]>0) ? " (".$notification_total_row["NumMessages"].")" : "";
		
		$html = render_header("Message");
		
		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li class=\"selected\"><a href=\"/messages/compose\" title=\"Compose\">Compose</a></li>\n"
			."<li><a href=\"/messages/all\" title=\"All\">All".$overall_total."</a></li>\n"
			."<li><a href=\"/messages/inbox\" title=\"Inbox\">Inbox".$inbox_total."</a></li>\n"
			."<li><a href=\"/messages/outbox\" title=\"Outbox\">Outbox</a></li>\n"
			."<li><a href=\"/messages/notifications\" title=\"Notifications\">Notifications".$notification_total."</a></li>\n"
			."<li><a href=\"/messages/trash\" title=\"Trash\">Trash</a></li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";
			
		$html .= "<div id=\"header\">\n"
				."<h1>Compose Message</h1>\n"
				."<p>Write a private message to another user.</p>\n"
			."</div> <!-- #header -->\n"
			."<div id=\"messages\">\n";
			
			
		// get the users who call you a friend
		$sql = "SELECT DISTINCT Users.*,Friends.FriendId,FriendsB.FriendId AS FriendBId FROM Users"
			." LEFT OUTER JOIN Friends ON Friends.UserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND Friends.FriendId=Users.Id"
			." LEFT OUTER JOIN Friends FriendsB ON FriendsB.FriendId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND FriendsB.UserId=Users.Id"
			." ORDER BY Users.Username";
			
		$user_result = $mysqli->query($sql);
		
		$user_options = "";
		while ($user_row =@ $user_result->fetch_assoc()){
			if ($user_row["Username"] != $_SESSION["user_name"]){
				if (($user_row["MessagesFriendsOnly"]==1 && $user_row["FriendBId"] != "") || ($user_row["MessagesFriendsOnly"]==0)){
					$user_options .= "<option value=\"".$user_row["Username"]."\">".$user_row["Username"]."</option>\n";
				}
			}
		}
		
		// Render the post message form
		$html .= "<form method=\"POST\" action=\"/api/message/send\" enctype=\"multipart/form-data\">\n"
			."<div class=\"message_form_wrapper\">\n"
			."<div class=\"message_form\">\n"
			."<h3>To</h3>\n"
			."<p>Choose a recipient (remember you can only message users that allow you to message them)</p>\n"
			."<div class='tal'><select id=\"to\" name=\"to\">".$user_options."</select></div>\n"
			."<h3>Message</h3>\n"
			."<p>Write your message here... (supports <a href=\"http://daringfireball.net/projects/markdown/\">markdown</a>)</p>\n"
			."<div><textarea id=\"message_body\" name=\"body\" rows=\"10\"></textarea></div>\n"
			."<div><input type=\"submit\" value=\"Send\" class=\"button\" /></div>\n"
			."</div> <!-- .message_form -->\n"
			."</div> <!-- .message_form_wrapper -->\n"
			."</form>\n"
			."<a name='form'></a>\n"
			."<script>\n"
			."$(\"#to\").focus();\n"
			."</script>\n";
		 
		
		$html .= "</div> <!-- #messages -->\n";
		
		$html .= render_footer();
		
	
		
		
	} else {
	
		header("Location: /403");
		
	}
	
	return $html;

}

function render_message_page($message_id,$in_reply_to=0){

	if (isset($_SESSION["user_id"])){
		
		$mysqli = db_connect();
		
		$overall_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND ReadFlag=0";
		$overall_total_result = $mysqli->query($overall_total_sql);
		$overall_total_row = $overall_total_result->fetch_assoc();
		$overall_total = ($overall_total_row["NumMessages"]>0) ? " (".$overall_total_row["NumMessages"].")" : "";
		
		$inbox_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type=0 AND ReadFlag=0";
		$inbox_total_result = $mysqli->query($inbox_total_sql);
		$inbox_total_row = $inbox_total_result->fetch_assoc();
		$inbox_total = ($inbox_total_row["NumMessages"]>0) ? " (".$inbox_total_row["NumMessages"].")" : "";
		
		$notification_total_sql = "SELECT COUNT(Id) AS NumMessages FROM Messages WHERE ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." AND ToStatus=0 AND Type>0 AND ReadFlag=0";
		$notification_total_result = $mysqli->query($notification_total_sql);
		$notification_total_row = $notification_total_result->fetch_assoc();
		$notification_total = ($notification_total_row["NumMessages"]>0) ? " (".$notification_total_row["NumMessages"].")" : "";
		
		$html = render_header("Message");
		
		$html .= "<div class=\"bg_menu_wrapper\">\n"
			."<ul class=\"bg_menu\">\n"
			."<li><a href=\"/messages/compose\" title=\"Compose\">Compose</a></li>\n"
			."<li><a href=\"/messages/all\" title=\"All\">All".$overall_total."</a></li>\n"
			."<li><a href=\"/messages/inbox\" title=\"Inbox\">Inbox".$inbox_total."</a></li>\n"
			."<li><a href=\"/messages/outbox\" title=\"Outbox\">Outbox</a></li>\n"
			."<li><a href=\"/messages/notifications\" title=\"Notifications\">Notifications".$notification_total."</a></li>\n"
			."<li><a href=\"/messages/trash\" title=\"Trash\">Trash</a></li>\n"
			."<li class=\"selected\">Message</li>\n"
			."</ul>\n"
			."<div class=\"clear\"></div>\n"
			."</div>\n";
			
		$html .= "<div id=\"header\">\n"
				."<h1>Message</h1>\n"
			."</div> <!-- #header -->\n"
			."<div id=\"messages\">\n";
			
			
		// fetch the message
		$sql = "SELECT Messages.RootId"
			." FROM Messages"
			." WHERE Messages.Id=".$mysqli->real_escape_string($message_id)
			." AND (Messages.ToUserId=".$mysqli->real_escape_string($_SESSION["user_id"])." OR Messages.FromUserId=".$mysqli->real_escape_string($_SESSION["user_id"]).")";
					
		$message_result = $mysqli->query($sql);
		
		if ($message_result->num_rows > 0){
			
			$message_row = $message_result->fetch_assoc();
			
			// get the messages matching the root id			
			$sql = "SELECT Messages.*,FromUsers.Username As FromUsersUsername, FromUsers.Avatar AS FromUsersAvatar, ToUsers.Username As ToUsersUsername, ToUsers.Avatar AS ToUsersAvatar, ParentUsers.Username AS ParentUsersUsername, ParentMessages.Id AS ParentMessagesId"
				." FROM Messages"
				." INNER JOIN Users FromUsers ON Messages.FromUserId=FromUsers.Id"
				." INNER JOIN Users ToUsers ON Messages.ToUserId=ToUsers.Id"
				." LEFT OUTER JOIN Messages ParentMessages ON ParentMessages.Id=Messages.ParentId"
				." LEFT OUTER JOIN Users ParentUsers ON ParentMessages.FromUserId=ParentUsers.Id"
				." WHERE ((Messages.RootId=".$mysqli->real_escape_string($message_row["RootId"])." AND Messages.Type=0) OR Messages.Id=".$mysqli->real_escape_string($message_id).")"
				." ORDER BY Created";
			
			$message_result = $mysqli->query($sql);
			
			$last_message_row = null;
			while ($message_row =@ $message_result->fetch_assoc()){
				$html .= "\n<div>\n\n"
					.render_message($message_row,false,(($message_row["Id"]==$message_id) ? true : false))
					."</div> <!-- #messages -->\n";
				if ($message_row["FromUserId"]!=$_SESSION["user_id"]) $last_message_row = $message_row;
			}
			
			// Render the post message form
			$html .= "<form method=\"POST\" action=\"/api/message/send\" enctype=\"multipart/form-data\">\n"
				."<input type=\"hidden\" name=\"to\" value=\"".$last_message_row["FromUsersUsername"]."\" />\n"
				."<input type=\"hidden\" name=\"in_reply_to\" value=\"".(($in_reply_to > 0) ? $in_reply_to : $last_message_row["Id"])."\" />\n"
				."<div class=\"message_form_wrapper\">\n"
				."<div class=\"message_form\">\n"
				."<h3>Message</h3>\n"
				."<p>Write your message here... (supports <a href=\"http://daringfireball.net/projects/markdown/\">markdown</a>)</p>\n"
				."<div><textarea id=\"message_body\" name=\"body\" rows=\"10\"></textarea></div>\n"
				."<input type=\"submit\" value=\"Send\" />\n"
				."</div> <!-- .message_form -->\n"
				."</div> <!-- .message_form_wrapper -->\n"
				."</form>\n"
				."<a name='form'></a>\n"
				."<script>\n"
				."$(\"#message_body\").focus();\n"
				."</script>\n";
			
			
			$html .= "</div> <!-- #messages -->\n";
			
		} else {
			// requested message not found
			$html .= "<div class=\"message_form_wrapper\"><div class=\"message_form\"><h4>Message not found</h4></div></div>\n";
		}
		
		$html .= render_footer();
		
	
		
		
	} else {
	
		header("Location: /403");
		
	}
	
	return $html;

}

?>
