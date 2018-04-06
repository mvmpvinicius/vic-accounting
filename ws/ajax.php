<?php

	require_once("config.php");

	if(isset($_SERVER["CONTENT_TYPE"]) && strpos($_SERVER["CONTENT_TYPE"], "application/json") !== false) {
	    $_POST = array_merge($_POST, (array) json_decode(trim(file_get_contents('php://input')), true));
	}

	$post = $_POST["_p"];
	require_once("ajax/".$_POST["_f"].".php");

?>