<?php
 
// Site Information
define("SITE_NAME","WeTheUsers");

// Database Connection Settings
define("DB_SERVER","localhost");
define("DB_NAME","database_name");
define("DB_USERNAME","database_username");
define("DB_PASSWORD","database_password");

// Email Connection Parameters
define("EMAIL_HOST","smtp.gmail.com");
define("EMAIL_PORT",465);
define("EMAIL_SECURE","ssl");
define("EMAIL_USERNAME","email_address");
define("EMAIL_PASSWORD","email_password");
define("EMAIL_REPLY_ADDRESS","reply_address");

// User Status Codes
define("USER_STATUS_UNVALIDATED",0);
define("USER_STATUS_VALIDATED",1);
define("USER_STATUS_DEACTIVATED",2);
define("USER_STATUS_BANNED",3);

// Post Status Codes
define("POST_STATUS_DRAFT",0);
define("POST_STATUS_PUBLISHED",1);

// Post Privacy Codes
define("POST_PRIVACY_PUBLIC",0);
define("POST_PRIVACY_FRIENDS_ONLY",1);
define("POST_PRIVACY_PRIVATE",2);

// Display Modes
define("DISPLAY_MODE_TILE","tile");
define("DISPLAY_MODE_LIST","list");

// Define display parameters
define("POSTS_PER_PAGE",20);
?>
