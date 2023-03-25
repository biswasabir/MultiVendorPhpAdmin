<?php
include_once '../includes/crud.php';
$db = new Database();
$db->connect();
include_once '../includes/custom-functions.php';

$function = new custom_functions();

$result = $function->update_order_status(14,27,"processed",0);
print_r($result);
?>