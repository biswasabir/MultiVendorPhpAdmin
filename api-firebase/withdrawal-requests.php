<?php
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
include '../includes/crud.php';
require_once '../includes/functions.php';
include_once('../includes/custom-functions.php');
$fn = new custom_functions;
include_once('../includes/variables.php');
include_once('verify-token.php');
$db = new Database();
$db->connect();
// date_default_timezone_set('Asia/Kolkata');
$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}

/* 
-------------------------------------------
APIs for Multi Vendor
-------------------------------------------
1. send_request
2. get_requests
-------------------------------------------
-------------------------------------------
*/

if (!verify_token()) {
    return false;
}


if (!isset($_POST['accesskey'])  || trim($_POST['accesskey']) != $access_key) {
    $response['error'] = true;
    $response['message'] = "No Accsess key found!";
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['send_request']) && $_POST['send_request'] == 1) {
    /*
    1.send_request
        accesskey:90336
        send_request:1
        type:users/delivery_boys/seller
        type_id:3
        amount:1000
        order_id:1005253	
        order_item_id:12474
        message:Message {optional}
    */

    $type = $db->escapeString($fn->xss_clean($_POST['type']));
    $type_id = $db->escapeString($fn->xss_clean($_POST['type_id']));
    $amount  = $db->escapeString($fn->xss_clean($_POST['amount']));
    $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_id'])) : "";
    $order_item_id = (isset($_POST['order_item_id']) && !empty($_POST['order_item_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_item_id'])) : "";
    $message = (isset($_POST['message']) && !empty($_POST['message'])) ? $db->escapeString($fn->xss_clean($_POST['message'])) : "";
    // $type1 = $type == 'user' ? 'user' : 'delivery boy';
    if (!empty($type) && !empty($type_id) && !empty($amount)) {
        // check if such user or delivery boy exists or not
        if ($fn->is_user_or_dboy_exists($type, $type_id)) {
            // checking if balance is greater than amount requested or not 
            $balance = $fn->get_user_or_delivery_boy_balance($type, $type_id);
            if ($balance >= $amount) {
                // Debit amount requeted
                $new_balance =  $balance - $amount;
                if ($fn->debit_balance($type, $type_id, $new_balance)) {
                    // store wallet transaction
                    if ($type == 'delivery_boy') {
                        $sql = "INSERT INTO `fund_transfers` (`delivery_boy_id`,`type`,`amount`,`opening_balance`,`closing_balance`,`status`,`message`) VALUES ('" . $type_id . "','debit','" . $amount . "','" . $balance . "','" . $new_balance . "','SUCCESS','Balance debited against withdrawal request.')";
                        $db->sql($sql);
                    }
                    if ($type == 'user') {
                        $fn->add_wallet_transaction($order_id, $order_item_id, $type_id, $type, $amount, $message, 'wallet_transactions');
                    }
                    if ($type == 'seller') {
                        $fn->add_wallet_transaction($order_id, $order_item_id, $type_id, $type, $amount, $message, 'seller_wallet_transactions');
                    }
                    // store withdrawal request
                    if ($fn->store_withdrawal_request($type, $type_id, $amount, $message)) {
                        $response['error'] = false;
                        $response['message'] = 'Withdrawal request accepted successfully!please wait for confirmation.';
                    } else {
                        $response['error'] = true;
                        $response['message'] = 'Something went wrong please try again later!';
                    }
                } else {
                    $response['error'] = true;
                    $response['message'] = 'Something went wrong please try again later!';
                }
            } else {
                $response['error'] = true;
                $response['message'] = 'Insufficient balance';
            }
        } else {
            $response['error'] = true;
            $response['message'] = 'No such ' . $type1 . ' exists';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['get_requests']) && $_POST['get_requests'] == 1) {
    /*
    2.get_requests
        accesskey:90336
        get_requests:1
        type:users/delivery_boys/seller
        type_id:3
        offset:0    // {optional}
        limit:5     // {optional}
    */

    $type  = $db->escapeString($fn->xss_clean($_POST['type']));
    $type_id = $db->escapeString($fn->xss_clean($_POST['type_id']));
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;
    if (!empty($type) && !empty($type_id)) {
        $result = $fn->is_records_exists($type, $type_id, $offset, $limit);
        if (!empty($result)) {
            /* if records found return data */
            $sql = "SELECT count(id) as total from withdrawal_requests where `type` = '" . $type . "' AND `type_id` = " . $type_id;
            $db->sql($sql);
            $total = $db->getResult();
            $response['error'] = false;
            $response['total'] = $total[0]['total'];
            $response['data'] = array_values($result);
        } else {
            $response['error'] = true;
            $response['message'] = "Data does't exists!";
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }

    print_r(json_encode($response));
    return false;
}
