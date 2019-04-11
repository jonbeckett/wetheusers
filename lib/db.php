<?php

function db_connect(){

	$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
	return $link;
}

?>