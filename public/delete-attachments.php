<?php
include_once('../includes/crud.php');
$db = new Database();
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
$db->connect();
date_default_timezone_set('Asia/Kolkata');
if (isset($_POST['i']) && isset($_POST['pid'])) {
    if (ALLOW_MODIFICATION == 0 && !defined(ALLOW_MODIFICATION)) {
        echo '<label class="alert alert-danger">This operation is not allowed in demo panel!.</label>';
        return false;
    }
    $i = $db->escapeString($fn->xss_clean($_POST['i']));
    $pid = $db->escapeString($fn->xss_clean($_POST['pid']));
    $sql = "SELECT attachment FROM order_bank_transfers WHERE order_id =$pid AND id = $i";
    $db->sql($sql);
    $res = $db->getResult();
    unlink("../" . $res[0]['attachment']); /*remove the image from the folder*/

    /*Delete the table*/
    $sql1 = " DELETE FROM `order_bank_transfers` WHERE order_id =$pid AND id = $i";
    if ($db->sql($sql1))
        echo 1;
    else
        echo 0;
}
