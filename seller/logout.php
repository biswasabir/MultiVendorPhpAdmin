<?php
//session_save_path("../temp");
	session_start();	
	unset($_SESSION['seller_name']);
	unset($_SESSION['seller_id']);
	//unset($_SESSION['timeout']);
	header("location:index.php");
