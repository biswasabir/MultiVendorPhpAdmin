<?php
header("Content-Type: application/json");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Access-Control-Allow-Origin: *');


include('../includes/crud.php');
include('../includes/custom-functions.php');
include('verify-token.php');
$fn = new custom_functions();
$db = new Database();
$db->connect();

$config = $fn->get_configurations();
$time_slot_config = $fn->time_slot_config();
if (isset($config['system_timezone']) && isset($config['system_timezone_gmt'])) {
    date_default_timezone_set($config['system_timezone']);
    $db->sql("SET `time_zone` = '" . $config['system_timezone_gmt'] . "'");
} else {
    date_default_timezone_set('Asia/Kolkata');
    $db->sql("SET `time_zone` = '+05:30'");
}
include('../includes/variables.php');

/* 
-------------------------------------------
APIs for Multi Vendor
-------------------------------------------
1. get_user_transactions
2. add_wallet_balance
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

if (isset($_POST['get_user_transactions']) && $_POST['get_user_transactions'] == 1) {
    /* 
    1.get_user_transactions
        accesskey:90336
        get_user_transactions:1
        user_id:3
        type:transactions/wallet_transactions
        offset:0        // {optional}
        limit:5         // {optional}
    */

    $user_id  = (isset($_POST['user_id']) && !empty($_POST['user_id'])) ? $db->escapeString($fn->xss_clean($_POST['user_id'])) : "";
    $type  = (isset($_POST['type']) && !empty($_POST['type'])) ? $db->escapeString($fn->xss_clean($_POST['type'])) : "";
    $limit = (isset($_POST['limit']) && !empty($_POST['limit']) && is_numeric($_POST['limit'])) ? $db->escapeString($fn->xss_clean($_POST['limit'])) : 10;
    $offset = (isset($_POST['offset']) && !empty($_POST['offset']) && is_numeric($_POST['offset'])) ? $db->escapeString($fn->xss_clean($_POST['offset'])) : 0;

    if (!empty($user_id) && !empty($type)) {
        $sql = "SELECT count(id) as total from $type where user_id=" . $user_id;
        $db->sql($sql);
        $total = $db->getResult();
        $sql = "select * from $type where user_id=" . $user_id . " and type !='delivery_boy_cash_collection' ORDER BY date_created DESC LIMIT $offset,$limit";
        $db->sql($sql);
        $res = $db->getResult();

        $data = array();
        if (!empty($res)) {
            $response['error'] = false;
            $response['total'] = $total[0]['total'];
            if ($type == 'transactions') {
                $res[0]['last_updated'] = (isset($res[0]['last_updated']) == null)  ? "" : $res[0]['last_updated'];
                $res[0]['order_item_id'] = (isset($res[0]['order_item_id']) == null)  ? "" : $res[0]['order_item_id'];
                $res[0]['payu_txn_id'] = (isset($res[0]['payu_txn_id']) == null)  ? "" : $res[0]['payu_txn_id'];
                $response['data'] = $res;
            } else {
                $response['data'] = $res;
                for ($i = 0; $i < count($response['data']); $i++) {
                    $response['data'][$i]['last_updated'] = (isset($response['data'][$i]['last_updated']) == null)  ? "" : $response['data'][$i]['last_updated'];
                    $response['data'][$i]['status'] = $response['data'][$i]['type'];
                    $response['data'][$i]['message'] = $response['data'][$i]['message'] == 'Used against Order Placement' ? 'Order Successfully Placed' : $response['data'][$i]['message'];
                }
            }
        } else {
            $response['error'] = true;
            $response['message'] = "No data found!";
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Please pass all the fields!';
    }
    print_r(json_encode($response));
    return false;
}

if (isset($_POST['add_wallet_balance']) && $_POST['add_wallet_balance'] == 1) {
    /* 
    2.add_wallet_balance
        accesskey:90336
        add_wallet_balance:1
        user_id:3
        amount:100
        type:credit
        message: transaction by user {optional}
        order_id:1005259     //  {optional}
        order_item_id:12480 // {optional}
    */

    if (isset($_POST['user_id']) && !empty($_POST['user_id']) && is_numeric($_POST['user_id'])) {

        $id = $db->escapeString($fn->xss_clean($_POST['user_id']));
        $order_id = (isset($_POST['order_id']) && !empty($_POST['order_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_id'])) : "";
        $order_item_id = (isset($_POST['order_item_id']) && !empty($_POST['order_item_id'])) ? $db->escapeString($fn->xss_clean($_POST['order_item_id'])) : "";
        $amount = $db->escapeString($fn->xss_clean($_POST['amount']));
        $type = $db->escapeString($fn->xss_clean($_POST['type']));
        $message = !empty($_POST['message']) ? $db->escapeString($fn->xss_clean($_POST['message'])) : 'Transaction by user';

        $sql = "SELECT id from users where id = $id";
        $db->sql($sql);
        $user_data = $db->getResult();

        if (!empty($user_data)) {
            $balance = $fn->get_wallet_balance($id, 'users');
            $new_balance = ($type == 'credit') ? $balance + $amount : $balance - $amount;
            $fn->update_wallet_balance($new_balance, $id, 'users');
            if ($fn->add_wallet_transaction($order_id, $order_item_id, $id, $type, $amount, $message, 'wallet_transactions')) {
                $n_balance = $fn->get_wallet_balance($id, 'users');
                $sql = "select * from wallet_transactions where status=1 AND user_id=" . $id . " ORDER BY date_created DESC";
                $db->sql($sql);
                $res1 = $db->getResult();
                $res1[0]['last_updated'] = (isset($res1[0]['last_updated']) == null)  ? "" : $res1[0]['last_updated'];


                $response['error'] = false;
                $response['message'] = "Wallet recharged successfully!";
                $response['new_balance'] = $n_balance;
                $response['data'] = $res1[0];
            } else {
                $response['error'] = true;
                $response['message'] = "Wallet recharged failed!";
            }
        } else {
            $response['error'] = true;
            $response['message'] = "User does not exist";
        }
    } else {
        $response['error'] = true;
        $response['message'] = "User Id is required";
    }
    print_r(json_encode($response));
    return false;
}
