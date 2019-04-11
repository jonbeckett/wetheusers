<?php

function preferences_list_mode(){
	$_SESSION["display_mode"] = "list";
}

function preferences_tile_mode(){
	$_SESSION["display_mode"] = "tile";
}

?>