<?php
include_once('../includes/crud.php');
$db = new Database();
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
$db->connect();
date_default_timezone_set('Asia/Kolkata');
if (isset($_POST['i']) && isset($_POST['pid'])) {
    if (defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 0) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    $index = $db->escapeString($fn->xss_clean($_POST['i']));
    $product_id = $db->escapeString($fn->xss_clean($_POST['pid']));
    $result = $fn->delete_other_images($product_id,$index);
    if($result == 1){
        echo 1;
    }else{
        echo 0;
    }
   
}
